<?php
// Lesen/Schreiben der modbus-proxy.yml.
// Kein generischer YAML-Parser: die Datei folgt immer exakt dem Format, das
// mbp_config_to_yaml() unten erzeugt - das macht Parsen und Schreiben robust
// und überschaubar, ohne eine externe YAML-Bibliothek einzubinden.

define("MBP_LOGLEVELS", ["DEBUG", "INFO", "WARNING", "ERROR"]);

// Tab-Leiste im Kopfbereich. $aktiv ist der Dateiname der gerade offenen Seite.
function mbp_navbar($aktiv, $L) {
	global $navbar;
	$seiten = [
		1 => ["index.php", $L["NAVBAR.MAIN"]],
		2 => ["log.php", $L["NAVBAR.LOG"]],
		3 => ["backup.php", $L["NAVBAR.BACKUP"]],
	];
	foreach ($seiten as $nr => $seite) {
		$navbar[$nr]["Name"] = $seite[1];
		$navbar[$nr]["URL"] = $seite[0];
		if ($seite[0] === $aktiv) {
			$navbar[$nr]["active"] = True;
		}
	}
}

// Gemeinsames Stylesheet aller Plugin-Seiten.
function mbp_styles() {
	?>
<style>
.mbp-box { border: 1px solid #ccc; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; }
.mbp-status-ok { background: #eefaee; border-color: #7bc77b; }
.mbp-status-bad { background: #fdeeee; border-color: #d98a8a; }
.mbp-msg-ok { background: #dff2df; border: 1px solid #7bc77b; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; }
.mbp-msg-error { background: #f7dede; border: 1px solid #d98a8a; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; }

.mbp-sechead { position: relative; }
.mbp-sechead-action { position: absolute; right: 0; bottom: 8px; }

.mbp-status-head { font-size: 1.05em; }
.mbp-status-sub { color: #666; font-size: 0.9em; margin-top: 2px; }

.mbp-conn { background: rgba(255,255,255,0.75); border: 1px solid #d5d5d5; border-radius: 6px; padding: 8px 10px; margin: 10px 0; }
.mbp-conn-head { font-size: 0.95em; }
.mbp-conn-route { font-family: monospace; font-size: 1em; }
.mbp-conn-meta { margin-top: 6px; font-size: 0.85em; color: #555; }
.mbp-conn-meta > span { display: inline-block; margin-right: 14px; white-space: nowrap; }

.mbp-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
.mbp-dot-up { background: #4caf50; }
.mbp-dot-down { background: #c0392b; }
.mbp-dot-idle { background: #bbb; }
.mbp-dot-active { background: #2196f3; box-shadow: 0 0 5px #2196f3; }

.mbp-btnrow { margin-top: 12px; }

.mbp-device { border: 1px solid #ddd; background: #fafafa; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; }
.mbp-device-head { overflow: hidden; margin-bottom: 6px; }
.mbp-device-title { float: left; font-weight: bold; line-height: 2.4; }
.mbp-device-remove { float: right; }
.mbp-device-remove .ui-btn { margin: 0; }
.mbp-hint { font-size: 0.85em; color: #777; margin: 0 0 6px 0; }

.mbp-field { margin-bottom: 4px; }
.mbp-field label { display: block; margin: 0 0 5px 0; }
.mbp-field .ui-select { margin: 0; width: 190px; }

.mbp-logview { background: #1e1e1e; color: #d4d4d4; font-family: monospace; font-size: 0.8em;
	padding: 10px; border-radius: 6px; max-height: 460px; overflow: auto; white-space: pre-wrap;
	word-break: break-all; margin: 0; }
.mbp-loginfo { color: #666; font-size: 0.85em; margin: 8px 0 0 0; }

.mbp-modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
	background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; }
.mbp-modal-overlay.mbp-modal-open { display: flex; }
.mbp-modal { background: #fff; border-radius: 10px; padding: 20px 22px; max-width: 90%; width: 420px;
	box-shadow: 0 6px 24px rgba(0,0,0,0.3); }
.mbp-modal h3 { margin: 0 0 12px 0; }
.mbp-modal p { margin: 0 0 18px 0; }
.mbp-modal-btns { text-align: right; }
.mbp-modal-btns .ui-btn { margin-left: 8px; }
</style>
	<?php
}

function mbp_default_config() {
	return [
		"loglevel" => "INFO",
		"devices" => [
			["url" => "", "timeout" => 10, "connection_time" => 0, "bind" => "", "unit_id_remapping" => []],
		],
	];
}

// Modbus-proxy erwartet reine "host:port"-Adressen. Die Einschränkung auf dieses
// Format verhindert nebenbei, dass Sonderzeichen (Anführungszeichen etc.) überhaupt
// in die YAML-Datei gelangen können.
function mbp_valid_hostport($s) {
	return (bool)preg_match('/^[A-Za-z0-9.\-]+:[0-9]{1,5}$/', $s);
}

function mbp_yaml_dq($s) {
	return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $s) . '"';
}

function mbp_config_to_yaml($cfg, $logfile) {
	$loglevel = in_array($cfg["loglevel"], MBP_LOGLEVELS) ? $cfg["loglevel"] : "INFO";

	$lines = [];
	$lines[] = "# Von der LoxBerry Modbus-Proxy Plugin-GUI verwaltet.";
	$lines[] = "# Manuelle Änderungen werden beim nächsten Speichern in der GUI überschrieben.";
	$lines[] = "# modbus-proxy-plugin-loglevel: " . $loglevel;
	$lines[] = "devices:";
	foreach ($cfg["devices"] as $d) {
		$lines[] = "- modbus:";
		$lines[] = "    url: " . mbp_yaml_dq($d["url"]);
		$lines[] = "    timeout: " . (float)$d["timeout"];
		$lines[] = "    connection_time: " . (float)$d["connection_time"];
		$lines[] = "  listen:";
		$lines[] = "    bind: " . mbp_yaml_dq($d["bind"]);
		if (!empty($d["unit_id_remapping"])) {
			$pairs = [];
			foreach ($d["unit_id_remapping"] as $k => $v) {
				$pairs[] = ((int)$k) . ": " . ((int)$v);
			}
			$lines[] = "  unit_id_remapping: {" . implode(", ", $pairs) . "}";
		}
	}
	$lines[] = "logging:";
	$lines[] = "  version: 1";
	$lines[] = "  formatters:";
	$lines[] = "    standard:";
	$lines[] = '      format: "%(asctime)s %(levelname)8s %(name)s: %(message)s"';
	$lines[] = "  handlers:";
	$lines[] = "    console:";
	$lines[] = "      class: logging.StreamHandler";
	$lines[] = "      level: " . $loglevel;
	$lines[] = "      formatter: standard";
	$lines[] = "    info_file_handler:";
	$lines[] = "      class: logging.handlers.RotatingFileHandler";
	$lines[] = "      level: " . $loglevel;
	$lines[] = "      formatter: standard";
	$lines[] = "      filename: " . mbp_yaml_dq($logfile);
	$lines[] = "      maxBytes: 204800";
	$lines[] = "      backupCount: 1";
	$lines[] = "      encoding: utf8";
	$lines[] = "  root:";
	$lines[] = "    handlers: ['console', 'info_file_handler']";
	$lines[] = "    level: " . $loglevel;
	return implode("\n", $lines) . "\n";
}

// Liefert ["ok"=>bool, "config"=>[...], "error"=>string]
function mbp_yaml_to_config($text) {
	$loglevel = "INFO";
	if (preg_match('/#\s*modbus-proxy-plugin-loglevel:\s*(\w+)/', $text, $m)) {
		if (in_array(strtoupper($m[1]), MBP_LOGLEVELS)) {
			$loglevel = strtoupper($m[1]);
		}
	}

	// Nur den Bereich zwischen "devices:" und "logging:" (oder Dateiende) auswerten.
	if (!preg_match('/devices:\s*\n(.*?)(\nlogging:|\z)/s', $text, $m)) {
		return ["ok" => false, "error" => "no_devices_section"];
	}
	$devicesblock = $m[1];

	$blocks = preg_split('/^- modbus:\s*$/m', $devicesblock);
	$devices = [];
	foreach ($blocks as $block) {
		if (trim($block) === "") {
			continue;
		}
		$d = ["url" => "", "timeout" => 10, "connection_time" => 0, "bind" => "", "unit_id_remapping" => []];
		if (!preg_match('/^\s*url:\s*"?([^"\r\n]+?)"?\s*$/m', $block, $mm)) {
			continue;
		}
		$d["url"] = trim($mm[1]);
		if (!preg_match('/^\s*bind:\s*"?([^"\r\n]+?)"?\s*$/m', $block, $mm)) {
			continue;
		}
		$d["bind"] = trim($mm[1]);
		if (!mbp_valid_hostport($d["url"]) || !mbp_valid_hostport($d["bind"])) {
			continue;
		}
		if (preg_match('/^\s*timeout:\s*([0-9.]+)/m', $block, $mm)) {
			$d["timeout"] = $mm[1] + 0;
		}
		if (preg_match('/^\s*connection_time:\s*([0-9.]+)/m', $block, $mm)) {
			$d["connection_time"] = $mm[1] + 0;
		}
		if (preg_match('/unit_id_remapping:\s*\{([^}]*)\}/', $block, $mm)) {
			foreach (explode(",", $mm[1]) as $pair) {
				if (strpos($pair, ":") !== false) {
					[$k, $v] = array_map("trim", explode(":", $pair, 2));
					if ($k !== "" && $v !== "") {
						$d["unit_id_remapping"][(int)$k] = (int)$v;
					}
				}
			}
		}
		$devices[] = $d;
	}

	if (empty($devices)) {
		return ["ok" => false, "error" => "no_valid_device"];
	}

	return ["ok" => true, "config" => ["loglevel" => $loglevel, "devices" => $devices]];
}

function mbp_read_config($path) {
	if (!file_exists($path)) {
		return mbp_default_config();
	}
	$result = mbp_yaml_to_config(file_get_contents($path));
	if (!$result["ok"]) {
		return mbp_default_config();
	}
	return $result["config"];
}

// Baut das Config-Array aus den POST-Daten des Formulars. Der Log-Level wird auf
// einer eigenen Seite gepflegt - fehlt er im POST, bleibt der bisherige erhalten.
function mbp_config_from_post($post, $loglevel_fallback = "INFO") {
	$cfg = ["loglevel" => in_array($loglevel_fallback, MBP_LOGLEVELS) ? $loglevel_fallback : "INFO", "devices" => []];
	if (isset($post["loglevel"]) && in_array($post["loglevel"], MBP_LOGLEVELS)) {
		$cfg["loglevel"] = $post["loglevel"];
	}
	if (!isset($post["devices"]) || !is_array($post["devices"])) {
		return $cfg;
	}
	foreach ($post["devices"] as $dev) {
		$url = trim($dev["url"] ?? "");
		$bind = trim($dev["bind"] ?? "");
		if (!mbp_valid_hostport($url) || !mbp_valid_hostport($bind)) {
			continue;
		}
		$remap = [];
		$remapraw = trim($dev["unit_id_remapping"] ?? "");
		if ($remapraw !== "") {
			foreach (explode(",", $remapraw) as $pair) {
				if (strpos($pair, ":") !== false) {
					[$k, $v] = array_map("trim", explode(":", $pair, 2));
					if ($k !== "" && $v !== "" && is_numeric($k) && is_numeric($v)) {
						$remap[(int)$k] = (int)$v;
					}
				}
			}
		}
		$cfg["devices"][] = [
			"url" => $url,
			"timeout" => is_numeric($dev["timeout"] ?? null) ? (float)$dev["timeout"] : 10,
			"connection_time" => is_numeric($dev["connection_time"] ?? null) ? (float)$dev["connection_time"] : 0,
			"bind" => $bind,
			"unit_id_remapping" => $remap,
		];
	}
	return $cfg;
}

function mbp_bind_port($bind) {
	$pos = strrpos($bind, ":");
	if ($pos === false) {
		return null;
	}
	$port = substr($bind, $pos + 1);
	return ctype_digit($port) ? (int)$port : null;
}

function mbp_port_reachable($port, $timeout = 0.5) {
	if ($port === null) {
		return false;
	}
	$fp = @fsockopen("127.0.0.1", $port, $errno, $errstr, $timeout);
	if ($fp) {
		fclose($fp);
		return true;
	}
	return false;
}

function mbp_daemon_status($ctlscript) {
	$out = [];
	$rc = 0;
	exec(escapeshellarg($ctlscript) . " status 2>&1", $out, $rc);
	$running = ($rc === 0);
	$pid = null;
	if ($running && !empty($out[0]) && preg_match('/running (\d+)/', $out[0], $m)) {
		$pid = (int)$m[1];
	}
	return ["running" => $running, "pid" => $pid];
}

// Liest TCP-Socket-Statistiken zu einem ss-Filterausdruck: Anzahl der Verbindungen,
// übertragene Bytes und die Zeit seit dem letzten Datenpaket (in Millisekunden).
function mbp_socket_stats($filter) {
	$stats = ["conns" => 0, "rx" => 0, "tx" => 0, "last_rx_ms" => null, "last_tx_ms" => null];
	$out = [];
	exec("ss -tin state established " . escapeshellarg($filter) . " 2>/dev/null", $out);
	foreach ($out as $line) {
		// Verbindungszeile: "Recv-Q Send-Q <lokal>:<port> <peer>:<port>"
		if (preg_match('/^\s*\d+\s+\d+\s+\S+:\d+\s+\S+:\d+/', $line)) {
			$stats["conns"]++;
			continue;
		}
		if (preg_match('/bytes_received:(\d+)/', $line, $m)) {
			$stats["rx"] += (int)$m[1];
		}
		if (preg_match('/bytes_sent:(\d+)/', $line, $m)) {
			$stats["tx"] += (int)$m[1];
		} elseif (preg_match('/bytes_acked:(\d+)/', $line, $m)) {
			$stats["tx"] += (int)$m[1];
		}
		if (preg_match('/lastrcv:(\d+)/', $line, $m)) {
			$stats["last_rx_ms"] = is_null($stats["last_rx_ms"]) ? (int)$m[1] : min($stats["last_rx_ms"], (int)$m[1]);
		}
		if (preg_match('/lastsnd:(\d+)/', $line, $m)) {
			$stats["last_tx_ms"] = is_null($stats["last_tx_ms"]) ? (int)$m[1] : min($stats["last_tx_ms"], (int)$m[1]);
		}
	}
	return $stats;
}

// Datenverkehr eines konfigurierten Geräts: clientseitig (Clients -> Proxy) und
// geräteseitig (Proxy -> echtes Modbus-Gerät).
function mbp_device_traffic($device) {
	$port = mbp_bind_port($device["bind"]);
	$client = $port === null
		? ["conns" => 0, "rx" => 0, "tx" => 0, "last_rx_ms" => null, "last_tx_ms" => null]
		: mbp_socket_stats("( sport = :$port )");
	$dev = mbp_valid_hostport($device["url"])
		? mbp_socket_stats("( dst " . $device["url"] . " )")
		: ["conns" => 0, "rx" => 0, "tx" => 0, "last_rx_ms" => null, "last_tx_ms" => null];
	return ["client" => $client, "device" => $dev];
}

// Gilt als aktiv, wenn innerhalb dieser Zeitspanne Daten geflossen sind.
define("MBP_ACTIVITY_MS", 10000);

function mbp_is_active($last_ms) {
	return $last_ms !== null && $last_ms < MBP_ACTIVITY_MS;
}

function mbp_format_bytes($bytes) {
	$bytes = (int)$bytes;
	if ($bytes < 1024) {
		return $bytes . " B";
	}
	if ($bytes < 1024 * 1024) {
		return number_format($bytes / 1024, 1, ",", ".") . " kB";
	}
	return number_format($bytes / (1024 * 1024), 1, ",", ".") . " MB";
}

function mbp_installed_version() {
	$out = [];
	exec("pip3 show modbus-proxy 2>/dev/null", $out);
	foreach ($out as $line) {
		if (preg_match('/^Version:\s*(.+)$/', $line, $m)) {
			return trim($m[1]);
		}
	}
	return null;
}
