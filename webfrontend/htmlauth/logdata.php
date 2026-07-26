<?php
// Liefert die beiden Logdateien als JSON für die automatische Aktualisierung
// der Log-Seite.
require_once "loxberry_system.php";
require_once "mbpconfig.inc.php";

$logfile = "$lbplogdir/modbus-proxy.log";
$daemonlog = "$lbplogdir/daemon.log";

header("Content-Type: application/json");
echo json_encode([
	"log" => mbp_tail($logfile),
	"daemon" => mbp_tail($daemonlog, MBP_DAEMONLOG_LINES),
]);
