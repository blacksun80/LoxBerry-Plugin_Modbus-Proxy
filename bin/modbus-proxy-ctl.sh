#!/bin/bash

# Start/Stop/Restart/Status-Steuerung für den modbus-proxy-Dienst.
# Wird sowohl vom Boot-Daemon (system/daemons/plugins/modbus-proxy, als root)
# als auch von der Plugin-GUI (als User loxberry) aufgerufen.
#
# Aufruf: modbus-proxy-ctl.sh {start|stop|restart|status}

set -u

BINARY=/usr/local/bin/modbus-proxy
CONFIGFILE="$LBPCONFIG/modbus-proxy/modbus-proxy.yml"
PIDFILE="$LBPLOG/modbus-proxy/modbus-proxy.pid"
LOGFILE="$LBPLOG/modbus-proxy/daemon.log"
RUNUSER=loxberry

log() {
	echo "$(date '+%Y-%m-%d %H:%M:%S') $1" >>"$LOGFILE"
}

as_runuser() {
	# Führt das übergebene Kommando als $RUNUSER aus - direkt, wenn der
	# Aufrufer schon dieser User ist, sonst per su (z.B. wenn root aufruft).
	if [ "$(id -un)" = "$RUNUSER" ]; then
		bash -c "$1"
	else
		su "$RUNUSER" -c "$1"
	fi
}

is_running() {
	if [ -f "$PIDFILE" ]; then
		PID=$(cat "$PIDFILE" 2>/dev/null)
		if [ -n "$PID" ] && kill -0 "$PID" 2>/dev/null; then
			return 0
		fi
	fi
	return 1
}

do_start() {
	mkdir -p "$LBPLOG/modbus-proxy"
	if is_running; then
		log "Start übersprungen, läuft bereits (PID $(cat "$PIDFILE"))"
		return 0
	fi
	if [ ! -x "$BINARY" ]; then
		log "FEHLER: $BINARY nicht gefunden - Python-Paket modbus-proxy fehlt"
		return 1
	fi
	if [ ! -f "$CONFIGFILE" ]; then
		log "FEHLER: Konfigurationsdatei $CONFIGFILE fehlt"
		return 1
	fi
	# Wichtig: "cd X && nohup CMD &" würde die gesamte &&-Kette als Subshell
	# hintergrunden - "$!" wäre dann die PID der Subshell, nicht des
	# tatsächlichen Prozesses. Deshalb hier ein einzelner Befehl ohne "&&".
	as_runuser "nohup $BINARY --config-file $CONFIGFILE >>$LOGFILE 2>&1 & echo \$! > $PIDFILE"
	sleep 1
	if is_running; then
		log "Gestartet mit PID $(cat "$PIDFILE")"
		return 0
	else
		log "FEHLER: Start fehlgeschlagen, siehe $LOGFILE"
		return 1
	fi
}

do_stop() {
	if is_running; then
		PID=$(cat "$PIDFILE")
		kill "$PID" 2>/dev/null
		for i in 1 2 3 4 5; do
			kill -0 "$PID" 2>/dev/null || break
			sleep 1
		done
		kill -0 "$PID" 2>/dev/null && kill -9 "$PID" 2>/dev/null
		log "Gestoppt (PID $PID)"
	fi
	rm -f "$PIDFILE"
	return 0
}

case "${1:-}" in
	start)
		do_start
		;;
	stop)
		do_stop
		;;
	restart)
		do_stop
		sleep 1
		do_start
		;;
	status)
		if is_running; then
			echo "running $(cat "$PIDFILE")"
			exit 0
		else
			echo "stopped"
			exit 3
		fi
		;;
	*)
		echo "Verwendung: $0 {start|stop|restart|status}"
		exit 2
		;;
esac
