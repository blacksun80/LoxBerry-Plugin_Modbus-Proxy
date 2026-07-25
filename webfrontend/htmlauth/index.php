<?php
require_once "loxberry_system.php";
require_once "loxberry_web.php";
require_once "loxberry_log.php";
require_once "mbpconfig.inc.php";

$version = LBSystem::pluginversion();
$L = LBSystem::readlanguage("language.ini");

$configfile = "$lbpconfigdir/modbus-proxy.yml";
$ctlscript = "$lbpbindir/modbus-proxy-ctl.sh";
$logfile = "$lbplogdir/modbus-proxy.log";

$message = "";
$messagetype = "";

function mbp_remap_to_text($map) {
	if (empty($map)) {
		return "";
	}
	$pairs = [];
	foreach ($map as $k => $v) {
		$pairs[] = "$k:$v";
	}
	return implode(", ", $pairs);
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
	$action = $_POST["action"] ?? "";

	if ($action === "save") {
		$cfg = mbp_config_from_post($_POST);
		if (empty($cfg["devices"])) {
			$message = $L["CONFIG.VALIDATION_ERROR"];
			$messagetype = "error";
		} else {
			$yaml = mbp_config_to_yaml($cfg, $logfile);
			if (file_put_contents($configfile, $yaml) !== false) {
				exec(escapeshellarg($ctlscript) . " restart 2>&1");
				$message = $L["CONFIG.SAVED_OK"];
				$messagetype = "ok";
			} else {
				$message = $L["CONFIG.SAVE_ERROR"];
				$messagetype = "error";
			}
		}
	} elseif (in_array($action, ["start", "stop", "restart"])) {
		exec(escapeshellarg($ctlscript) . " " . escapeshellarg($action) . " 2>&1", $out, $rc);
		$message = ($rc === 0 || $action !== "start") ? $L["STATUS.ACTION_OK"] : $L["STATUS.ACTION_ERROR"];
		$messagetype = ($rc === 0) ? "ok" : "error";
	} elseif ($action === "import") {
		if (empty($_FILES["importfile"]["tmp_name"]) || !is_uploaded_file($_FILES["importfile"]["tmp_name"])) {
			$message = $L["EXPORT.IMPORT_NOFILE"];
			$messagetype = "error";
		} else {
			$content = file_get_contents($_FILES["importfile"]["tmp_name"]);
			$parsed = mbp_yaml_to_config($content);
			if (!$parsed["ok"]) {
				$message = $L["EXPORT.IMPORT_ERROR"];
				$messagetype = "error";
			} else {
				if (file_exists($configfile)) {
					copy($configfile, "$configfile.bak");
				}
				$yaml = mbp_config_to_yaml($parsed["config"], $logfile);
				file_put_contents($configfile, $yaml);
				exec(escapeshellarg($ctlscript) . " restart 2>&1");
				$message = $L["EXPORT.IMPORT_OK"];
				$messagetype = "ok";
			}
		}
	}
}

$cfg = mbp_read_config($configfile);
$status = mbp_daemon_status($ctlscript);
$pipversion = mbp_installed_version();

LBWeb::lbheader($L["TITLE.PAGETITLE"] . " V$version", "https://pypi.org/project/modbus-proxy/", "help.html");
?>

<style>
.mbp-status-box { border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }
.mbp-status-ok { background: #dff2df; border: 1px solid #7bc77b; }
.mbp-status-bad { background: #f7dede; border: 1px solid #d98a8a; }
.mbp-msg-ok { background: #dff2df; border: 1px solid #7bc77b; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; }
.mbp-msg-error { background: #f7dede; border: 1px solid #d98a8a; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; }
.mbp-device { border: 1px solid #ccc; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; position: relative; }
.mbp-device-remove { float: right; }
.mbp-port-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
.mbp-port-up { background: #4caf50; }
.mbp-port-down { background: #c0392b; }
.mbp-hint { font-size: 0.85em; color: #777; margin: 0 0 8px 0; }
.mbp-box { border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; border: 1px solid #ccc; }
</style>

<?php if ($message): ?>
	<div class="mbp-msg-<?php echo $messagetype; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<p class="wide"><?php echo $L["STATUS.HEAD"]; ?></p>
<div id="mbp-status-box" class="mbp-status-box <?php echo $status["running"] ? "mbp-status-ok" : "mbp-status-bad"; ?>">
	<span id="mbp-status-text">
	<?php if ($status["running"]): ?>
		<b><?php echo $L["STATUS.RUNNING"]; ?></b> (<?php echo $L["STATUS.PID"]; ?>: <?php echo $status["pid"]; ?>)
	<?php else: ?>
		<b><?php echo $L["STATUS.STOPPED"]; ?></b>
	<?php endif; ?>
	</span>
	<br>
	<span id="mbp-status-version">
	<?php if ($pipversion): ?>
		<?php echo $L["STATUS.INSTALLED_VERSION"]; ?>: <?php echo htmlspecialchars($pipversion); ?>
	<?php else: ?>
		<span style="color:#c0392b;"><?php echo $L["STATUS.NOT_INSTALLED"]; ?></span>
	<?php endif; ?>
	</span>
	<br><br>
	<div id="mbp-status-devices">
	<?php foreach ($cfg["devices"] as $d):
		$port = mbp_bind_port($d["bind"]);
		$up = $status["running"] && mbp_port_reachable($port);
	?>
		<div><span class="mbp-port-dot <?php echo $up ? "mbp-port-up" : "mbp-port-down"; ?>"></span><?php echo htmlspecialchars($d["bind"]); ?> &rarr; <?php echo htmlspecialchars($d["url"]); ?> (<span class="mbp-porttext"><?php echo $up ? $L["STATUS.PORT_OPEN"] : $L["STATUS.PORT_CLOSED"]; ?></span>)</div>
	<?php endforeach; ?>
	</div>
	<br>
	<form action="index.php" method="post" style="display:inline;">
		<input type="hidden" name="action" value="start">
		<button type="submit" data-role="button" data-inline="true" data-mini="true"><?php echo $L["STATUS.BTN_START"]; ?></button>
	</form>
	<form action="index.php" method="post" style="display:inline;">
		<input type="hidden" name="action" value="stop">
		<button type="submit" data-role="button" data-inline="true" data-mini="true"><?php echo $L["STATUS.BTN_STOP"]; ?></button>
	</form>
	<form action="index.php" method="post" style="display:inline;">
		<input type="hidden" name="action" value="restart">
		<button type="submit" data-role="button" data-inline="true" data-mini="true"><?php echo $L["STATUS.BTN_RESTART"]; ?></button>
	</form>
</div>

<p class="wide"><?php echo $L["CONFIG.HEAD"]; ?></p>
<form action="index.php" method="post" id="mbpform">
	<input type="hidden" name="action" value="save">
	<div id="mbp-devices">
		<?php foreach ($cfg["devices"] as $i => $d): ?>
			<div class="mbp-device">
				<a href="#" class="mbp-device-remove" onclick="mbpRemoveDevice(this); return false;">&#10060; <?php echo $L["CONFIG.REMOVE_DEVICE"]; ?></a>
				<p><b><?php echo $L["CONFIG.DEVICE"]; ?> <?php echo $i + 1; ?></b></p>

				<label><?php echo $L["CONFIG.MODBUS_URL"]; ?></label>
				<p class="mbp-hint"><?php echo $L["CONFIG.MODBUS_URL_HINT"]; ?></p>
				<input data-inline="true" data-mini="true" type="text" name="devices[<?php echo $i; ?>][url]" value="<?php echo htmlspecialchars($d["url"]); ?>" required>

				<label><?php echo $L["CONFIG.LISTEN_BIND"]; ?></label>
				<p class="mbp-hint"><?php echo $L["CONFIG.LISTEN_BIND_HINT"]; ?></p>
				<input data-inline="true" data-mini="true" type="text" name="devices[<?php echo $i; ?>][bind]" value="<?php echo htmlspecialchars($d["bind"]); ?>" required>

				<label><?php echo $L["CONFIG.TIMEOUT"]; ?></label>
				<p class="mbp-hint"><?php echo $L["CONFIG.TIMEOUT_HINT"]; ?></p>
				<input data-inline="true" data-mini="true" type="number" step="0.1" min="0" name="devices[<?php echo $i; ?>][timeout]" value="<?php echo htmlspecialchars($d["timeout"]); ?>">

				<label><?php echo $L["CONFIG.CONNECTION_TIME"]; ?></label>
				<p class="mbp-hint"><?php echo $L["CONFIG.CONNECTION_TIME_HINT"]; ?></p>
				<input data-inline="true" data-mini="true" type="number" step="0.1" min="0" name="devices[<?php echo $i; ?>][connection_time]" value="<?php echo htmlspecialchars($d["connection_time"]); ?>">

				<label><?php echo $L["CONFIG.UNIT_ID_REMAPPING"]; ?></label>
				<p class="mbp-hint"><?php echo $L["CONFIG.UNIT_ID_REMAPPING_HINT"]; ?></p>
				<input data-inline="true" data-mini="true" type="text" name="devices[<?php echo $i; ?>][unit_id_remapping]" value="<?php echo htmlspecialchars(mbp_remap_to_text($d["unit_id_remapping"])); ?>">
			</div>
		<?php endforeach; ?>
	</div>

	<button type="button" data-role="button" data-inline="true" data-mini="true" onclick="mbpAddDevice();"><?php echo $L["CONFIG.ADD_DEVICE"]; ?></button>

	<p><label><?php echo $L["CONFIG.LOG_LEVEL"]; ?></label>
	<select name="loglevel" data-inline="true" data-mini="true">
		<?php foreach (["DEBUG", "INFO", "WARNING", "ERROR"] as $lvl): ?>
			<option value="<?php echo $lvl; ?>" <?php echo ($cfg["loglevel"] === $lvl) ? "selected" : ""; ?>><?php echo $lvl; ?></option>
		<?php endforeach; ?>
	</select></p>

	<br>
	<button type="submit" data-role="button" data-inline="true"><?php echo $L["CONFIG.SAVE"]; ?></button>
</form>

<template id="mbp-device-template">
	<div class="mbp-device">
		<a href="#" class="mbp-device-remove" onclick="mbpRemoveDevice(this); return false;">&#10060; <?php echo $L["CONFIG.REMOVE_DEVICE"]; ?></a>
		<p><b><?php echo $L["CONFIG.DEVICE"]; ?> __IDX_LABEL__</b></p>

		<label><?php echo $L["CONFIG.MODBUS_URL"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.MODBUS_URL_HINT"]; ?></p>
		<input data-inline="true" data-mini="true" type="text" name="devices[__IDX__][url]" value="" required>

		<label><?php echo $L["CONFIG.LISTEN_BIND"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.LISTEN_BIND_HINT"]; ?></p>
		<input data-inline="true" data-mini="true" type="text" name="devices[__IDX__][bind]" value="" required>

		<label><?php echo $L["CONFIG.TIMEOUT"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.TIMEOUT_HINT"]; ?></p>
		<input data-inline="true" data-mini="true" type="number" step="0.1" min="0" name="devices[__IDX__][timeout]" value="10">

		<label><?php echo $L["CONFIG.CONNECTION_TIME"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.CONNECTION_TIME_HINT"]; ?></p>
		<input data-inline="true" data-mini="true" type="number" step="0.1" min="0" name="devices[__IDX__][connection_time]" value="0">

		<label><?php echo $L["CONFIG.UNIT_ID_REMAPPING"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.UNIT_ID_REMAPPING_HINT"]; ?></p>
		<input data-inline="true" data-mini="true" type="text" name="devices[__IDX__][unit_id_remapping]" value="">
	</div>
</template>

<script>
var mbpL = {
	running: <?php echo json_encode($L["STATUS.RUNNING"]); ?>,
	stopped: <?php echo json_encode($L["STATUS.STOPPED"]); ?>,
	pid: <?php echo json_encode($L["STATUS.PID"]); ?>,
	installedversion: <?php echo json_encode($L["STATUS.INSTALLED_VERSION"]); ?>,
	notinstalled: <?php echo json_encode($L["STATUS.NOT_INSTALLED"]); ?>,
	portopen: <?php echo json_encode($L["STATUS.PORT_OPEN"]); ?>,
	portclosed: <?php echo json_encode($L["STATUS.PORT_CLOSED"]); ?>,
	removeconfirm: <?php echo json_encode($L["CONFIG.REMOVE_DEVICE_CONFIRM"]); ?>
};

function mbpPollStatus() {
	fetch("status.php").then(function(r) { return r.json(); }).then(function(s) {
		var box = document.getElementById("mbp-status-box");
		box.classList.toggle("mbp-status-ok", s.running);
		box.classList.toggle("mbp-status-bad", !s.running);
		document.getElementById("mbp-status-text").innerHTML = s.running
			? "<b>" + mbpL.running + "</b> (" + mbpL.pid + ": " + s.pid + ")"
			: "<b>" + mbpL.stopped + "</b>";
		document.getElementById("mbp-status-version").innerHTML = s.version
			? mbpL.installedversion + ": " + s.version
			: "<span style='color:#c0392b;'>" + mbpL.notinstalled + "</span>";
		var devicesHtml = "";
		s.devices.forEach(function(d) {
			devicesHtml += "<div><span class='mbp-port-dot " + (d.reachable ? "mbp-port-up" : "mbp-port-down") + "'></span>"
				+ d.bind + " &rarr; " + d.url + " (" + (d.reachable ? mbpL.portopen : mbpL.portclosed) + ")</div>";
		});
		document.getElementById("mbp-status-devices").innerHTML = devicesHtml;
	}).catch(function() {});
}
setInterval(mbpPollStatus, 5000);

var mbpNextIdx = <?php echo count($cfg["devices"]); ?>;
function mbpAddDevice() {
	var tpl = document.getElementById("mbp-device-template").innerHTML;
	tpl = tpl.split("__IDX__").join(mbpNextIdx).split("__IDX_LABEL__").join(mbpNextIdx + 1);
	var wrapper = document.createElement("div");
	wrapper.innerHTML = tpl;
	document.getElementById("mbp-devices").appendChild(wrapper.firstElementChild);
	mbpNextIdx++;
}
function mbpRemoveDevice(el) {
	if (!confirm(mbpL.removeconfirm)) {
		return;
	}
	el.closest(".mbp-device").remove();
}
</script>

<p class="wide"><?php echo $L["EXPORT.HEAD"]; ?></p>
<div class="mbp-box">
	<a href="export.php" data-role="button" data-inline="true" data-mini="true"><?php echo $L["EXPORT.EXPORT_BTN"]; ?></a>

	<form action="index.php" method="post" enctype="multipart/form-data" style="margin-top:10px;">
		<input type="hidden" name="action" value="import">
		<label><?php echo $L["EXPORT.IMPORT_LABEL"]; ?></label>
		<input type="file" name="importfile" accept=".yml,.yaml">
		<button type="submit" data-role="button" data-inline="true" data-mini="true"><?php echo $L["EXPORT.IMPORT_BTN"]; ?></button>
	</form>
</div>

<?php
LBWeb::lbfooter();
