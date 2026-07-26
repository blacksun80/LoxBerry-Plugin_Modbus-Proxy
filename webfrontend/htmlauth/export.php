<?php
// Lädt die konfigurierten Geräte als YAML-Datei herunter, ohne Log-Level und
// logging-Block.
require_once "loxberry_system.php";
require_once "mbpconfig.inc.php";

$configfile = "$lbpconfigdir/modbus-proxy.yml";

if (!file_exists($configfile)) {
	http_response_code(404);
	echo "Keine Konfiguration vorhanden.";
	exit;
}

$yaml = mbp_config_to_yaml(mbp_read_config($configfile), false);

$filename = "modbus-proxy-config_" . date("Y-m-d_His") . ".yml";
header("Content-Type: application/x-yaml");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Length: " . strlen($yaml));
echo $yaml;
