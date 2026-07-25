<?php
require_once "loxberry_system.php";
require_once "mbpconfig.inc.php";

$configfile = "$lbpconfigdir/modbus-proxy.yml";
$ctlscript = "$lbpbindir/modbus-proxy-ctl.sh";

$cfg = mbp_read_config($configfile);
$status = mbp_daemon_status($ctlscript);

$devices = [];
foreach ($cfg["devices"] as $d) {
	$port = mbp_bind_port($d["bind"]);
	$devices[] = [
		"bind" => $d["bind"],
		"url" => $d["url"],
		"reachable" => $status["running"] && mbp_port_reachable($port),
	];
}

header("Content-Type: application/json");
echo json_encode([
	"running" => $status["running"],
	"pid" => $status["pid"],
	"version" => mbp_installed_version(),
	"devices" => $devices,
]);
