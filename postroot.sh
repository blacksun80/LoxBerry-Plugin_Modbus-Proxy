#!/bin/bash

# Läuft als root, als allerletzter Schritt der Installation/des Updates.
# Aufgaben:
#  1. Falls preroot.sh eine Konfiguration gesichert hat (Update-Fall): wiederherstellen,
#     damit die Einstellungen des Nutzers das Update überleben.
#  2. Berechtigungen sicherstellen.
#  3. Dienst (neu) starten, damit die aktuelle Konfiguration sofort aktiv ist.

COMMAND=$0
PTEMPDIR=$1
PSHNAME=$2
PDIR=$3
PVERSION=$4
PTEMPPATH=$6

PCONFIG=$LBPCONFIG/$PDIR
PBIN=$LBPBIN/$PDIR
PLOG=$LBPLOG/$PDIR
BACKUPDIR=/tmp/modbus-proxy_configbackup

echo "<INFO> postroot.sh gestartet für $PSHNAME Version $PVERSION"

if [ -f "$BACKUPDIR/modbus-proxy.yml" ]; then
	echo "<INFO> Stelle gesicherte Konfiguration wieder her."
	cp -a "$BACKUPDIR/modbus-proxy.yml" "$PCONFIG/modbus-proxy.yml"
	rm -rf "$BACKUPDIR"
	echo "<OK> Konfiguration wiederhergestellt."
fi

chown -R loxberry:loxberry "$PCONFIG" "$PBIN" 2>/dev/null
chmod 755 "$PBIN"/*.sh 2>/dev/null
mkdir -p "$PLOG"
chown -R loxberry:loxberry "$PLOG"

echo "<INFO> Starte modbus-proxy-Dienst neu, damit die Konfiguration übernommen wird."
"$PBIN/modbus-proxy-ctl.sh" restart

exit 0
