#!/bin/bash

# Laeuft als root, VOR dem Loeschen einer evtl. vorhandenen alten Installation.
# Aufgaben:
#  1. Python-Paket modbus-proxy (mit YAML-Unterstuetzung) installieren/aktualisieren.
#  2. Bei einem Update: bestehende Konfiguration sichern, bevor sie durch die
#     Installationsroutine geloescht wird (siehe postroot.sh fuer die Wiederherstellung).

COMMAND=$0
PTEMPDIR=$1
PSHNAME=$2
PDIR=$3
PVERSION=$4
PTEMPPATH=$6

PCONFIG=$LBPCONFIG/$PDIR
BACKUPDIR=/tmp/modbus-proxy_configbackup

echo "<INFO> preroot.sh gestartet fuer $PSHNAME Version $PVERSION"

echo "<INFO> Installiere/aktualisiere Python-Paket modbus-proxy..."
pip3 install --upgrade --break-system-packages "modbus-proxy[yaml]" 2>/tmp/modbus-proxy_pipinstall.log
if [ $? -ne 0 ]; then
	# Manche Systeme kennen --break-system-packages nicht (aeltere pip-Version) - Fallback ohne dieses Flag.
	pip3 install --upgrade "modbus-proxy[yaml]" 2>>/tmp/modbus-proxy_pipinstall.log
fi
if [ $? -ne 0 ]; then
	echo "<WARNING> Installation von modbus-proxy per pip3 ist fehlgeschlagen. Details: /tmp/modbus-proxy_pipinstall.log"
	echo "<WARNING> Der Dienst kann erst nach manueller Installation gestartet werden."
else
	echo "<OK> modbus-proxy Python-Paket ist installiert."
fi

if [ -f "$PCONFIG/modbus-proxy.yml" ]; then
	echo "<INFO> Bestehende Konfiguration gefunden - sichere sie fuer die Wiederherstellung nach dem Update."
	rm -rf "$BACKUPDIR"
	mkdir -p "$BACKUPDIR"
	cp -a "$PCONFIG/modbus-proxy.yml" "$BACKUPDIR/modbus-proxy.yml"
	echo "<OK> Konfiguration gesichert nach $BACKUPDIR/modbus-proxy.yml"
fi

exit 0
