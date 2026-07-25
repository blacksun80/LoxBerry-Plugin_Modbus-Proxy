#!/bin/bash

# Wird beim Deinstallieren des Plugins ausgefuehrt.
# Stoppt den laufenden modbus-proxy-Dienst. Das per pip installierte
# Python-Paket "modbus-proxy" selbst wird bewusst NICHT entfernt, falls
# es anderweitig verwendet wird - siehe README.

COMMAND=$0
PTEMPDIR=$1
PSHNAME=$2
PDIR=$3
PVERSION=$4
PTEMPPATH=$6

PBIN=$LBPBIN/$PDIR

echo "<INFO> Stoppe modbus-proxy-Dienst..."
if [ -x "$PBIN/modbus-proxy-ctl.sh" ]; then
	"$PBIN/modbus-proxy-ctl.sh" stop
else
	pkill -f "modbus-proxy --config-file" 2>/dev/null
fi

exit 0
