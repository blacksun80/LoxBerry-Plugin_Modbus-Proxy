#!/bin/bash

# Start/Stop/Restart/Status-Steuerung für den modbus-proxy-Dienst.
# Wird sowohl vom Boot-Daemon (system/daemons/plugins/modbus-proxy, als root)
# als auch von der Plugin-GUI (als User loxberry) aufgerufen.
#
# Aufruf: modbus-proxy-ctl.sh {start|stop|restart|status|trimlog}

set -u

BINARY=/usr/local/bin/modbus-proxy
RUNUSER=loxberry

CONFIGFILE="$LBPCONFIG/modbus-proxy/modbus-proxy.yml"
LOGDIR="$LBPLOG/modbus-proxy"
PIDFILE="$LOGDIR/modbus-proxy.pid"

# Eine einzige Logdatei für alles: Start-/Stopp-Meldungen dieses Skripts, die Ausgabe
# des Dienstes und Startfehler (Python-Tracebacks). Der Dienst schreibt deshalb nur
# nach stderr, siehe logging-Block in der modbus-proxy.yml.
LOGFILE="$LOGDIR/modbus-proxy.log"

# Obergrenze der Logdatei; beim Überschreiten bleiben die letzten KEEPBYTES erhalten.
# Das Log liegt unter $LBPLOG, das LoxBerry auf Raspberry-Pi-Systemen in eine RAM-Disk
# einhängt (siehe sbin/createtmpfsfoldersinit.sh) - es darf also nicht unbegrenzt wachsen.
MAXBYTES=1048576
KEEPBYTES=524288

log() {
	echo "$(date '+%Y-%m-%d %H:%M:%S') $1" >>"$LOGFILE"
}

trim_log() {
	# Kürzt die Logdatei auf die letzten KEEPBYTES. Bewusst "in place" (Umleitung mit >
	# auf dieselbe Datei) statt Umbenennen: der laufende Dienst hält die Datei geöffnet
	# und würde nach einem Umbenennen weiter in die alte, unsichtbare Datei schreiben.
	[ -f "$LOGFILE" ] || return 0
	SIZE=$(stat -c %s "$LOGFILE" 2>/dev/null || echo 0)
	[ "$SIZE" -le "$MAXBYTES" ] && return 0
	REST=$(tail -c "$KEEPBYTES" "$LOGFILE")
	printf '%s\n' "$REST" > "$LOGFILE"
	log "Logdatei gekürzt (war $SIZE Bytes)"
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

ensure_logdir() {
	# Legt Log-Verzeichnis und Logdatei an und stellt sicher, dass beide $RUNUSER
	# gehören. Als root aufgerufen entstünden sonst root-eigene Dateien, in die
	# weder der als $RUNUSER laufende Dienst noch die GUI schreiben können.
	mkdir -p "$LOGDIR"
	touch "$LOGFILE" 2>/dev/null
	if [ "$(id -u)" = "0" ]; then
		chown -R "$RUNUSER:$RUNUSER" "$LOGDIR"
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
	# PYTHONUNBUFFERED sorgt dafür, dass die Meldungen sofort in der Logdatei
	# stehen und nicht erst, wenn Python seinen Ausgabepuffer leert.
	as_runuser "PYTHONUNBUFFERED=1 nohup $BINARY --config-file $CONFIGFILE >>$LOGFILE 2>&1 & echo \$! > $PIDFILE"
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
		ensure_logdir
		trim_log
		do_start
		;;
	stop)
		ensure_logdir
		do_stop
		;;
	restart)
		ensure_logdir
		trim_log
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
	trimlog)
		ensure_logdir
		trim_log
		;;
	*)
		echo "Verwendung: $0 {start|stop|restart|status|trimlog}"
		exit 2
		;;
esac
