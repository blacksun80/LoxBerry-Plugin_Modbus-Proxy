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
/* jQuery Mobile gibt Buttons einen eigenen Aussenabstand - hier weg, damit sie
   bündig unter dem darüberliegenden Feld beginnen. */
.mbp-btnrow .ui-btn { margin-left: 0; }
.mbp-autoinfo { font-size: 0.8em; color: #999; margin-left: 10px; }

.mbp-device { border: 1px solid #ddd; background: #fafafa; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; }
.mbp-device-head { overflow: hidden; margin-bottom: 6px; }
.mbp-device-title { float: left; font-weight: bold; line-height: 2.4; }
.mbp-device-remove { float: right; }
.mbp-device-remove .ui-btn { margin: 0; }
.mbp-hint { font-size: 0.85em; color: #777; margin: 0 0 6px 0; }

/* Ziel-Adresse: Host und Port nebeneinander, getrennt durch einen Doppelpunkt. */
.mbp-addr { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 14px; }
.mbp-addr-host { flex: 1 1 auto; min-width: 0; }
.mbp-addr-port { flex: 0 0 130px; }
.mbp-addr-sep { font-weight: bold; color: #888; line-height: 2.6; }
.mbp-addr .ui-input-text { margin: 0; }
.mbp-subhint { display: block; font-size: 0.78em; color: #999; margin: 3px 0 0 2px; }

/* Beispielwerte deutlich blasser als echte Eingaben, damit sie nicht mit einem
   bereits eingetragenen Wert verwechselt werden. */
.ui-input-text input::placeholder,
.mbp-device input::placeholder { color: #c4c4c4; font-style: italic; opacity: 1; }
.ui-input-text input::-webkit-input-placeholder,
.mbp-device input::-webkit-input-placeholder { color: #c4c4c4; font-style: italic; }
.ui-input-text input::-moz-placeholder,
.mbp-device input::-moz-placeholder { color: #c4c4c4; font-style: italic; opacity: 1; }
.mbp-portonly { max-width: 130px; margin-bottom: 14px; }
.mbp-portonly .ui-input-text { margin: 0; }

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

define("MBP_DEFAULT_LISTEN_PORT", 9010);
define("MBP_DEFAULT_DEVICE_PORT", 502);

// Host-Teil der listen-Adresse, den das Plugin schreibt: der Proxy lauscht immer auf
// allen Netzwerkschnittstellen. modbus-proxy ersetzt einen leeren Host intern durch "0",
// beides bedeutet dasselbe.
define("MBP_BIND_HOST", "0");

// Host-Teile, die "alle Netzwerkschnittstellen" bedeuten. Steht in einer eingelesenen
// Datei etwas anderes, war die Adresse auf eine einzelne Schnittstelle eingeschränkt.
define("MBP_BIND_HOST_ANY", ["0", "0.0.0.0", "", "::"]);

// Muster für die Ziel-Adresse. Wird auch als HTML-pattern an das Eingabefeld gegeben,
// damit Browser und Server dieselbe Regel prüfen. Zwei Alternativen:
//   1. eine vollständige IPv4-Adresse mit Oktetten von 0 bis 255
//   2. ein Hostname, dessen erster Namensteil mindestens einen Buchstaben enthält
// Der Buchstabe in Teil 2 ist entscheidend: sonst wäre "192.168.178.195a" ein formal
// gültiger Hostname und würde durchrutschen, obwohl es ein vertippter IP-Adresse ist.
define("MBP_IPV4_PATTERN", '((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])');
define("MBP_HOSTNAME_PATTERN", '[A-Za-z0-9\-]*[A-Za-z][A-Za-z0-9\-]*(\.[A-Za-z0-9]([A-Za-z0-9\-]*[A-Za-z0-9])?)*');
define("MBP_HOST_PATTERN", "(" . MBP_IPV4_PATTERN . "|" . MBP_HOSTNAME_PATTERN . ")");

// Muster für die Unit-ID-Umleitung: "1:0" oder "1:0, 2:5". Leer ist ebenfalls zulässig,
// ein HTML-pattern greift bei leeren Feldern nicht.
define("MBP_REMAP_PATTERN", '\s*\d{1,3}\s*:\s*\d{1,3}\s*(,\s*\d{1,3}\s*:\s*\d{1,3}\s*)*');

// Obergrenzen der Zahlenfelder. Sie begrenzen nur die Eingabe in der GUI - modbus-proxy
// selbst kennt keine solchen Grenzen.
define("MBP_MAX_TIMEOUT", 3600);
define("MBP_MAX_CONNECTION_TIME", 600);

// Modbus-Unit-IDs sind ein einzelnes Byte.
define("MBP_MAX_UNIT_ID", 255);

// Anzahl Zeilen, die die Log-Seite anzeigt.
define("MBP_LOG_LINES", 150);

// Abstand der automatischen Aktualisierung in Millisekunden.
define("MBP_POLL_MS", 5000);

function mbp_default_config() {
	return [
		"loglevel" => "INFO",
		"devices" => [mbp_default_device()],
	];
}

function mbp_default_device() {
	return [
		"host" => "",
		"device_port" => MBP_DEFAULT_DEVICE_PORT,
		"listen_port" => MBP_DEFAULT_LISTEN_PORT,
		"timeout" => 10,
		"connection_time" => 0,
		"unit_id_remapping" => [],
	];
}

// Hostname oder IPv4-Adresse. Die Einschränkung auf dieses Format verhindert nebenbei,
// dass Sonderzeichen (Anführungszeichen etc.) in die YAML-Datei gelangen können.
function mbp_valid_host($s) {
	if (strlen($s) < 1 || strlen($s) > 253) {
		return false;
	}
	// Sieht die Eingabe nach einer IP-Adresse aus (beginnt mit einer reinen Zahl und
	// enthält einen Punkt), muss es auch eine gültige sein - sonst ist es ein Vertipper.
	if (preg_match('/^[0-9]+\./', $s)) {
		return filter_var($s, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
	}
	return (bool)preg_match('/^' . MBP_HOSTNAME_PATTERN . '$/', $s);
}

function mbp_valid_port($p) {
	return is_numeric($p) && (int)$p == $p && (int)$p >= 1 && (int)$p <= 65535;
}

// Zahl innerhalb der Grenzen? Nicht-Zahlen und Werte ausserhalb liefern den Ersatzwert.
function mbp_clamp_number($wert, $min, $max, $ersatz) {
	if (!is_numeric($wert)) {
		return $ersatz;
	}
	$wert = (float)$wert;
	return ($wert < $min || $wert > $max) ? $ersatz : $wert;
}

function mbp_valid_unit_id($id) {
	return is_numeric($id) && (int)$id == $id && (int)$id >= 0 && (int)$id <= MBP_MAX_UNIT_ID;
}

// Zerlegt eine "host:port"-Adresse. Liefert null, wenn das Format nicht stimmt.
function mbp_split_address($adresse) {
	$pos = strrpos($adresse, ":");
	if ($pos === false) {
		return null;
	}
	$host = substr($adresse, 0, $pos);
	$port = substr($adresse, $pos + 1);
	if (!ctype_digit($port) || !mbp_valid_port($port)) {
		return null;
	}
	return ["host" => $host, "port" => (int)$port];
}

function mbp_yaml_dq($s) {
	return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $s) . '"';
}

function mbp_config_to_yaml($cfg) {
	$loglevel = in_array($cfg["loglevel"], MBP_LOGLEVELS) ? $cfg["loglevel"] : "INFO";

	$lines = [];
	$lines[] = "# Von der LoxBerry Modbus-Proxy Plugin-GUI verwaltet.";
	$lines[] = "# Manuelle Änderungen werden beim nächsten Speichern in der GUI überschrieben.";
	$lines[] = "# modbus-proxy-plugin-loglevel: " . $loglevel;
	$lines[] = "devices:";
	foreach ($cfg["devices"] as $d) {
		$lines[] = "- modbus:";
		$lines[] = "    url: " . mbp_yaml_dq($d["host"] . ":" . (int)$d["device_port"]);
		$lines[] = "    timeout: " . (float)$d["timeout"];
		$lines[] = "    connection_time: " . (float)$d["connection_time"];
		$lines[] = "  listen:";
		$lines[] = "    bind: " . mbp_yaml_dq(MBP_BIND_HOST . ":" . (int)$d["listen_port"]);
		if (!empty($d["unit_id_remapping"])) {
			$pairs = [];
			foreach ($d["unit_id_remapping"] as $k => $v) {
				$pairs[] = ((int)$k) . ": " . ((int)$v);
			}
			$lines[] = "  unit_id_remapping: {" . implode(", ", $pairs) . "}";
		}
	}
	// Der Dienst schreibt bewusst nur nach stderr und in keine eigene Datei: das
	// Steuerskript leitet seine Ausgabe in die gemeinsame Logdatei um, in der auch
	// die Start-/Stopp-Meldungen und Startfehler stehen. So gibt es nur ein Log.
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
	$lines[] = "  root:";
	$lines[] = "    handlers: ['console']";
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
	$hostverworfen = false;
	foreach ($blocks as $block) {
		if (trim($block) === "") {
			continue;
		}
		$d = mbp_default_device();
		if (!preg_match('/^\s*url:\s*"?([^"\r\n]+?)"?\s*$/m', $block, $mm)) {
			continue;
		}
		$ziel = mbp_split_address(trim($mm[1]));
		if ($ziel === null || !mbp_valid_host($ziel["host"])) {
			continue;
		}
		$d["host"] = $ziel["host"];
		$d["device_port"] = $ziel["port"];

		if (!preg_match('/^\s*bind:\s*"?([^"\r\n]*?)"?\s*$/m', $block, $mm)) {
			continue;
		}
		$bind = mbp_split_address(trim($mm[1]));
		if ($bind === null) {
			continue;
		}
		// Das Plugin lauscht immer auf allen Schnittstellen; ein eingeschränkter Host
		// aus einer fremden Datei geht beim Einlesen verloren und wird gemeldet.
		if (!in_array($bind["host"], MBP_BIND_HOST_ANY, true)) {
			$hostverworfen = true;
		}
		$d["listen_port"] = $bind["port"];
		if (preg_match('/^\s*timeout:\s*([0-9.]+)/m', $block, $mm)) {
			$d["timeout"] = mbp_clamp_number($mm[1], 0, MBP_MAX_TIMEOUT, 10);
		}
		if (preg_match('/^\s*connection_time:\s*([0-9.]+)/m', $block, $mm)) {
			$d["connection_time"] = mbp_clamp_number($mm[1], 0, MBP_MAX_CONNECTION_TIME, 0);
		}
		if (preg_match('/unit_id_remapping:\s*\{([^}]*)\}/', $block, $mm)) {
			foreach (explode(",", $mm[1]) as $pair) {
				if (strpos($pair, ":") !== false) {
					[$k, $v] = array_map("trim", explode(":", $pair, 2));
					if (mbp_valid_unit_id($k) && mbp_valid_unit_id($v)) {
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

	return [
		"ok" => true,
		"config" => ["loglevel" => $loglevel, "devices" => $devices],
		"host_verworfen" => $hostverworfen,
	];
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
	$portsvergeben = [];
	foreach ($post["devices"] as $dev) {
		$host = trim($dev["host"] ?? "");
		$deviceport = trim($dev["device_port"] ?? "");
		$listenport = trim($dev["listen_port"] ?? "");
		if (!mbp_valid_host($host) || !mbp_valid_port($deviceport) || !mbp_valid_port($listenport)) {
			continue;
		}
		// Zwei Geräte auf demselben Port kann modbus-proxy nicht öffnen.
		if (in_array((int)$listenport, $portsvergeben, true)) {
			$cfg["portkonflikt"] = true;
			continue;
		}
		$portsvergeben[] = (int)$listenport;
		$remap = [];
		$remapraw = trim($dev["unit_id_remapping"] ?? "");
		if ($remapraw !== "") {
			foreach (explode(",", $remapraw) as $pair) {
				if (strpos($pair, ":") !== false) {
					[$k, $v] = array_map("trim", explode(":", $pair, 2));
					if (mbp_valid_unit_id($k) && mbp_valid_unit_id($v)) {
						$remap[(int)$k] = (int)$v;
					}
				}
			}
		}
		$cfg["devices"][] = [
			"host" => $host,
			"device_port" => (int)$deviceport,
			"listen_port" => (int)$listenport,
			"timeout" => mbp_clamp_number($dev["timeout"] ?? null, 0, MBP_MAX_TIMEOUT, 10),
			"connection_time" => mbp_clamp_number($dev["connection_time"] ?? null, 0, MBP_MAX_CONNECTION_TIME, 0),
			"unit_id_remapping" => $remap,
		];
	}
	return $cfg;
}

// Lauscht auf diesem Port ein Dienst? Bewusst über die Socket-Tabelle statt über einen
// echten Verbindungsversuch: jede Testverbindung würde modbus-proxy als Client-Zugriff
// werten und vier Zeilen ins Log schreiben - bei einer alle paar Sekunden aktualisierten
// Statusseite läuft die Logdatei damit in kurzer Zeit voll.
function mbp_port_listening($port) {
	if (!mbp_valid_port($port)) {
		return false;
	}
	$out = [];
	exec("ss -ltn " . escapeshellarg("( sport = :" . (int)$port . " )") . " 2>/dev/null", $out);
	foreach ($out as $line) {
		if (preg_match('/^\s*LISTEN\s/', $line)) {
			return true;
		}
	}
	return false;
}

// Letzte Zeilen einer Logdatei lesen, ohne die komplette Datei in den Speicher zu laden.
function mbp_tail($pfad, $zeilen = 120) {
	if (!file_exists($pfad) || !is_readable($pfad)) {
		return null;
	}
	$out = [];
	exec("tail -n " . (int)$zeilen . " " . escapeshellarg($pfad) . " 2>/dev/null", $out);
	return implode("\n", $out);
}

// Sucht im Log die letzte aussagekräftige Fehlerzeile. Startfehler (z.B. ein belegter
// Port) erscheinen als Python-Traceback, nicht als formatierte Log-Meldung.
function mbp_start_error($logfile, $zeilen = 40) {
	if (!file_exists($logfile) || !is_readable($logfile)) {
		return null;
	}
	$out = [];
	exec("tail -n " . (int)$zeilen . " " . escapeshellarg($logfile) . " 2>/dev/null", $out);
	$treffer = null;
	foreach ($out as $zeile) {
		if (preg_match('/^\s*(OSError|[A-Za-z_.]*(Error|Exception)):\s*(.+)$/', trim($zeile), $m)) {
			$treffer = trim($zeile);
		}
	}
	return $treffer;
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
	$leer = ["conns" => 0, "rx" => 0, "tx" => 0, "last_rx_ms" => null, "last_tx_ms" => null];
	$port = (int)$device["listen_port"];
	$client = mbp_valid_port($port) ? mbp_socket_stats("( sport = :$port )") : $leer;
	$dev = mbp_valid_host($device["host"]) && mbp_valid_port($device["device_port"])
		? mbp_socket_stats("( dst " . $device["host"] . ":" . (int)$device["device_port"] . " )")
		: $leer;
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
