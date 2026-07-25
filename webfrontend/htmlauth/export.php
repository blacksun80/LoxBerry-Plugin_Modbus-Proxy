<?php
require_once "loxberry_system.php";

$configfile = "$lbpconfigdir/modbus-proxy.yml";

if (!file_exists($configfile)) {
	http_response_code(404);
	echo "Keine Konfiguration vorhanden.";
	exit;
}

$filename = "modbus-proxy-config_" . date("Y-m-d_His") . ".yml";
header("Content-Type: application/x-yaml");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Length: " . filesize($configfile));
readfile($configfile);
