#!/bin/bash

# Läuft als root, VOR dem Löschen einer evtl. vorhandenen alten Installation.
# Aufgaben:
#  1. Python-Paket modbus-proxy (mit YAML-Unterstützung) installieren/aktualisieren.
#  2. Bei einem Update: bestehende Konfiguration sichern, bevor sie durch die
#     Installationsroutine gelöscht wird (siehe postroot.sh für die Wiederherstellung).

COMMAND=$0
PTEMPDIR=$1
PSHNAME=$2
PDIR=$3
PVERSION=$4
PTEMPPATH=$6

PCONFIG=$LBPCONFIG/$PDIR
BACKUPDIR=/tmp/modbus-proxy_configbackup

echo "<INFO> preroot.sh gestartet für $PSHNAME Version $PVERSION"

# pip3 install kann je nach Netzwerk/Paketgröße eine Weile dauern und gibt
# selbst nichts aus - ohne Lebenszeichen wirkt das Installations-Log dann, als
# wäre es hängengeblieben. Deshalb währenddessen ein periodisches <INFO>.
run_with_heartbeat() {
	"$@" &
	local cmdpid=$!
	while kill -0 "$cmdpid" 2>/dev/null; do
		sleep 15
		echo "<INFO> ...läuft noch (PID $cmdpid)"
	done
	wait "$cmdpid"
}

echo "<INFO> Installiere/aktualisiere Python-Paket modbus-proxy..."
run_with_heartbeat pip3 install --upgrade --break-system-packages "modbus-proxy[yaml]" 2>/tmp/modbus-proxy_pipinstall.log
if [ $? -ne 0 ]; then
	# Manche Systeme kennen --break-system-packages nicht (ältere pip-Version) - Fallback ohne dieses Flag.
	run_with_heartbeat pip3 install --upgrade "modbus-proxy[yaml]" 2>>/tmp/modbus-proxy_pipinstall.log
fi
if [ $? -ne 0 ]; then
	echo "<WARNING> Installation von modbus-proxy per pip3 ist fehlgeschlagen. Details: /tmp/modbus-proxy_pipinstall.log"
	echo "<WARNING> Der Dienst kann erst nach manueller Installation gestartet werden."
else
	echo "<OK> modbus-proxy Python-Paket ist installiert."
fi

if [ -f "$PCONFIG/modbus-proxy.yml" ]; then
	echo "<INFO> Bestehende Konfiguration gefunden - sichere sie für die Wiederherstellung nach dem Update."
	rm -rf "$BACKUPDIR"
	mkdir -p "$BACKUPDIR"
	cp -a "$PCONFIG/modbus-proxy.yml" "$BACKUPDIR/modbus-proxy.yml"
	echo "<OK> Konfiguration gesichert nach $BACKUPDIR/modbus-proxy.yml"
fi

exit 0
