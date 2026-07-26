<?php
require_once "loxberry_system.php";
require_once "mbpconfig.inc.php";

$configfile = "$lbpconfigdir/modbus-proxy.yml";
$ctlscript = "$lbpbindir/modbus-proxy-ctl.sh";

$cfg = mbp_read_config($configfile);
$status = mbp_daemon_status($ctlscript);

$devices = [];
foreach ($cfg["devices"] as $d) {
	$port = (int)$d["listen_port"];
	$tr = mbp_device_traffic($d);
	$devices[] = [
		"listen_port" => $port,
		"url" => $d["host"] . ":" . (int)$d["device_port"],
		"reachable" => $status["running"] && mbp_port_listening($port),
		"clients" => $tr["client"]["conns"],
		"rx" => $tr["client"]["rx"],
		"tx" => $tr["client"]["tx"],
		"rx_human" => mbp_format_bytes($tr["client"]["rx"]),
		"tx_human" => mbp_format_bytes($tr["client"]["tx"]),
		"rx_active" => mbp_is_active($tr["client"]["last_rx_ms"]),
		"tx_active" => mbp_is_active($tr["client"]["last_tx_ms"]),
		"device_connected" => $tr["device"]["conns"] > 0,
	];
}

header("Content-Type: application/json");
echo json_encode([
	"running" => $status["running"],
	"pid" => $status["pid"],
	"version" => mbp_installed_version(),
	"devices" => $devices,
]);
