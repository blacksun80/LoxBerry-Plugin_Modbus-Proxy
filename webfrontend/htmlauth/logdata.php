<?php
// Liefert die Logdatei als JSON für die automatische Aktualisierung der Log-Seite.
require_once "loxberry_system.php";
require_once "mbpconfig.inc.php";

$logfile = "$lbplogdir/modbus-proxy.log";

header("Content-Type: application/json");
echo json_encode([
	"log" => mbp_tail($logfile, MBP_LOG_LINES),
]);
