<?php
// Lesen/Schreiben der modbus-proxy.yml.
// Kein generischer YAML-Parser: die Datei folgt immer exakt dem Format, das
// mbp_config_to_yaml() unten erzeugt - das macht Parsen und Schreiben robust
// und überschaubar, ohne eine externe YAML-Bibliothek einzubinden.

define("MBP_LOGLEVELS", ["DEBUG", "INFO", "WARNING", "ERROR"]);

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

// Baut das Config-Array aus den POST-Daten des Formulars.
function mbp_config_from_post($post) {
	$cfg = ["loglevel" => "INFO", "devices" => []];
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
