<?php
require_once "loxberry_system.php";
require_once "loxberry_web.php";
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
		$alt = mbp_read_config($configfile);
		$cfg = mbp_config_from_post($_POST, $alt["loglevel"]);
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
	}
}

$cfg = mbp_read_config($configfile);
$status = mbp_daemon_status($ctlscript);
$pipversion = mbp_installed_version();

mbp_navbar("index.php", $L);
$pagetitle = $L["TITLE.PAGETITLE"] . (!empty($version) ? " V$version" : "");
LBWeb::lbheader($pagetitle, "https://pypi.org/project/modbus-proxy/", "help.html");
mbp_styles();
?>

<?php if ($message): ?>
	<div class="mbp-msg-<?php echo $messagetype; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<p class="wide"><?php echo $L["STATUS.HEAD"]; ?></p>
<div id="mbp-status-box" class="mbp-box <?php echo $status["running"] ? "mbp-status-ok" : "mbp-status-bad"; ?>">
	<div class="mbp-status-head" id="mbp-status-text">
	<?php if ($status["running"]): ?>
		<span class="mbp-dot mbp-dot-up"></span><b><?php echo $L["STATUS.RUNNING"]; ?></b> (<?php echo $L["STATUS.PID"]; ?>: <?php echo $status["pid"]; ?>)
	<?php else: ?>
		<span class="mbp-dot mbp-dot-down"></span><b><?php echo $L["STATUS.STOPPED"]; ?></b>
	<?php endif; ?>
	</div>
	<div class="mbp-status-sub" id="mbp-status-version">
	<?php if ($pipversion): ?>
		<?php echo $L["STATUS.INSTALLED_VERSION"]; ?>: <?php echo htmlspecialchars($pipversion); ?>
	<?php else: ?>
		<span style="color:#c0392b;"><?php echo $L["STATUS.NOT_INSTALLED"]; ?></span>
	<?php endif; ?>
	</div>

	<div id="mbp-status-devices">
	<?php foreach ($cfg["devices"] as $d):
		$port = mbp_bind_port($d["bind"]);
		$up = $status["running"] && mbp_port_reachable($port);
		$tr = mbp_device_traffic($d);
		$rxact = mbp_is_active($tr["client"]["last_rx_ms"]);
		$txact = mbp_is_active($tr["client"]["last_tx_ms"]);
	?>
		<div class="mbp-conn">
			<div class="mbp-conn-head">
				<span class="mbp-dot <?php echo $up ? "mbp-dot-up" : "mbp-dot-down"; ?>"></span>
				<span class="mbp-conn-route"><?php echo htmlspecialchars($d["bind"]); ?> &rarr; <?php echo htmlspecialchars($d["url"]); ?></span>
				&nbsp;<?php echo $up ? $L["STATUS.PORT_OPEN"] : $L["STATUS.PORT_CLOSED"]; ?>
			</div>
			<div class="mbp-conn-meta">
				<span><?php echo $L["STATUS.CLIENTS"]; ?>: <?php echo (int)$tr["client"]["conns"]; ?></span>
				<span><span class="mbp-dot <?php echo $rxact ? "mbp-dot-active" : "mbp-dot-idle"; ?>"></span><?php echo $L["STATUS.RX"]; ?> <?php echo mbp_format_bytes($tr["client"]["rx"]); ?></span>
				<span><span class="mbp-dot <?php echo $txact ? "mbp-dot-active" : "mbp-dot-idle"; ?>"></span><?php echo $L["STATUS.TX"]; ?> <?php echo mbp_format_bytes($tr["client"]["tx"]); ?></span>
				<span><span class="mbp-dot <?php echo $tr["device"]["conns"] > 0 ? "mbp-dot-up" : "mbp-dot-idle"; ?>"></span><?php echo $tr["device"]["conns"] > 0 ? $L["STATUS.DEVICE_CONNECTED"] : $L["STATUS.DEVICE_DISCONNECTED"]; ?></span>
			</div>
		</div>
	<?php endforeach; ?>
	</div>

	<div class="mbp-btnrow">
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
</div>

<div class="mbp-sechead">
	<p class="wide"><?php echo $L["CONFIG.HEAD"]; ?></p>
	<div class="mbp-sechead-action">
		<button type="button" data-role="button" data-inline="true" data-mini="true" data-icon="plus" onclick="mbpAddDevice();"><?php echo $L["CONFIG.ADD_DEVICE"]; ?></button>
	</div>
</div>

<form action="index.php" method="post" id="mbpform">
	<input type="hidden" name="action" value="save">
	<div class="mbp-box">
		<div id="mbp-devices">
			<?php foreach ($cfg["devices"] as $i => $d): ?>
				<div class="mbp-device">
					<div class="mbp-device-head">
						<span class="mbp-device-title"><?php echo $L["CONFIG.DEVICE"]; ?> <span class="mbp-device-nr"><?php echo $i + 1; ?></span></span>
						<span class="mbp-device-remove">
							<button type="button" data-role="button" data-inline="true" data-mini="true" data-icon="delete" onclick="mbpRemoveDevice(this);"><?php echo $L["CONFIG.REMOVE_DEVICE"]; ?></button>
						</span>
					</div>

					<label><?php echo $L["CONFIG.MODBUS_URL"]; ?></label>
					<p class="mbp-hint"><?php echo $L["CONFIG.MODBUS_URL_HINT"]; ?></p>
					<input data-mini="true" type="text" name="devices[<?php echo $i; ?>][url]" value="<?php echo htmlspecialchars($d["url"]); ?>" required>

					<label><?php echo $L["CONFIG.LISTEN_BIND"]; ?></label>
					<p class="mbp-hint"><?php echo $L["CONFIG.LISTEN_BIND_HINT"]; ?></p>
					<input data-mini="true" type="text" name="devices[<?php echo $i; ?>][bind]" value="<?php echo htmlspecialchars($d["bind"]); ?>" required>

					<label><?php echo $L["CONFIG.TIMEOUT"]; ?></label>
					<p class="mbp-hint"><?php echo $L["CONFIG.TIMEOUT_HINT"]; ?></p>
					<input data-mini="true" type="number" step="0.1" min="0" name="devices[<?php echo $i; ?>][timeout]" value="<?php echo htmlspecialchars($d["timeout"]); ?>">

					<label><?php echo $L["CONFIG.CONNECTION_TIME"]; ?></label>
					<p class="mbp-hint"><?php echo $L["CONFIG.CONNECTION_TIME_HINT"]; ?></p>
					<input data-mini="true" type="number" step="0.1" min="0" name="devices[<?php echo $i; ?>][connection_time]" value="<?php echo htmlspecialchars($d["connection_time"]); ?>">

					<label><?php echo $L["CONFIG.UNIT_ID_REMAPPING"]; ?></label>
					<p class="mbp-hint"><?php echo $L["CONFIG.UNIT_ID_REMAPPING_HINT"]; ?></p>
					<input data-mini="true" type="text" name="devices[<?php echo $i; ?>][unit_id_remapping]" value="<?php echo htmlspecialchars(mbp_remap_to_text($d["unit_id_remapping"])); ?>">
				</div>
			<?php endforeach; ?>
		</div>

		<button type="submit" data-role="button" data-inline="true" data-theme="b" data-icon="check"><?php echo $L["CONFIG.SAVE"]; ?></button>
	</div>
</form>

<template id="mbp-device-template">
	<div class="mbp-device">
		<div class="mbp-device-head">
			<span class="mbp-device-title"><?php echo $L["CONFIG.DEVICE"]; ?> <span class="mbp-device-nr">__IDX_LABEL__</span></span>
			<span class="mbp-device-remove">
				<button type="button" data-role="button" data-inline="true" data-mini="true" data-icon="delete" onclick="mbpRemoveDevice(this);"><?php echo $L["CONFIG.REMOVE_DEVICE"]; ?></button>
			</span>
		</div>

		<label><?php echo $L["CONFIG.MODBUS_URL"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.MODBUS_URL_HINT"]; ?></p>
		<input data-mini="true" type="text" name="devices[__IDX__][url]" value="" required>

		<label><?php echo $L["CONFIG.LISTEN_BIND"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.LISTEN_BIND_HINT"]; ?></p>
		<input data-mini="true" type="text" name="devices[__IDX__][bind]" value="" required>

		<label><?php echo $L["CONFIG.TIMEOUT"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.TIMEOUT_HINT"]; ?></p>
		<input data-mini="true" type="number" step="0.1" min="0" name="devices[__IDX__][timeout]" value="10">

		<label><?php echo $L["CONFIG.CONNECTION_TIME"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.CONNECTION_TIME_HINT"]; ?></p>
		<input data-mini="true" type="number" step="0.1" min="0" name="devices[__IDX__][connection_time]" value="0">

		<label><?php echo $L["CONFIG.UNIT_ID_REMAPPING"]; ?></label>
		<p class="mbp-hint"><?php echo $L["CONFIG.UNIT_ID_REMAPPING_HINT"]; ?></p>
		<input data-mini="true" type="text" name="devices[__IDX__][unit_id_remapping]" value="">
	</div>
</template>

<div class="mbp-modal-overlay" id="mbp-confirm-overlay">
	<div class="mbp-modal">
		<h3><?php echo $L["CONFIG.REMOVE_DEVICE"]; ?></h3>
		<p id="mbp-confirm-text"><?php echo $L["CONFIG.REMOVE_DEVICE_CONFIRM"]; ?></p>
		<div class="mbp-modal-btns">
			<a href="#" class="ui-btn ui-btn-inline ui-corner-all" id="mbp-confirm-cancel"><?php echo $L["CONFIG.CANCEL"]; ?></a>
			<a href="#" class="ui-btn ui-btn-inline ui-corner-all ui-btn-b" id="mbp-confirm-ok"><?php echo $L["CONFIG.REMOVE_DEVICE"]; ?></a>
		</div>
	</div>
</div>

<script>
var mbpL = {
	running: <?php echo json_encode($L["STATUS.RUNNING"]); ?>,
	stopped: <?php echo json_encode($L["STATUS.STOPPED"]); ?>,
	pid: <?php echo json_encode($L["STATUS.PID"]); ?>,
	installedversion: <?php echo json_encode($L["STATUS.INSTALLED_VERSION"]); ?>,
	notinstalled: <?php echo json_encode($L["STATUS.NOT_INSTALLED"]); ?>,
	portopen: <?php echo json_encode($L["STATUS.PORT_OPEN"]); ?>,
	portclosed: <?php echo json_encode($L["STATUS.PORT_CLOSED"]); ?>,
	clients: <?php echo json_encode($L["STATUS.CLIENTS"]); ?>,
	rx: <?php echo json_encode($L["STATUS.RX"]); ?>,
	tx: <?php echo json_encode($L["STATUS.TX"]); ?>,
	devconnected: <?php echo json_encode($L["STATUS.DEVICE_CONNECTED"]); ?>,
	devdisconnected: <?php echo json_encode($L["STATUS.DEVICE_DISCONNECTED"]); ?>,
	removeconfirm: <?php echo json_encode($L["CONFIG.REMOVE_DEVICE_CONFIRM"]); ?>,
	device: <?php echo json_encode($L["CONFIG.DEVICE"]); ?>
};

function mbpEsc(s) {
	return String(s).replace(/[&<>"']/g, function(c) {
		return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
	});
}

function mbpDot(cls) {
	return "<span class='mbp-dot " + cls + "'></span>";
}

function mbpPollStatus() {
	fetch("status.php").then(function(r) { return r.json(); }).then(function(s) {
		var box = document.getElementById("mbp-status-box");
		box.classList.toggle("mbp-status-ok", s.running);
		box.classList.toggle("mbp-status-bad", !s.running);
		document.getElementById("mbp-status-text").innerHTML = s.running
			? mbpDot("mbp-dot-up") + "<b>" + mbpEsc(mbpL.running) + "</b> (" + mbpEsc(mbpL.pid) + ": " + mbpEsc(s.pid) + ")"
			: mbpDot("mbp-dot-down") + "<b>" + mbpEsc(mbpL.stopped) + "</b>";
		document.getElementById("mbp-status-version").innerHTML = s.version
			? mbpEsc(mbpL.installedversion) + ": " + mbpEsc(s.version)
			: "<span style='color:#c0392b;'>" + mbpEsc(mbpL.notinstalled) + "</span>";

		var html = "";
		s.devices.forEach(function(d) {
			html += "<div class='mbp-conn'>"
				+ "<div class='mbp-conn-head'>"
				+ mbpDot(d.reachable ? "mbp-dot-up" : "mbp-dot-down")
				+ "<span class='mbp-conn-route'>" + mbpEsc(d.bind) + " &rarr; " + mbpEsc(d.url) + "</span>&nbsp;"
				+ mbpEsc(d.reachable ? mbpL.portopen : mbpL.portclosed)
				+ "</div>"
				+ "<div class='mbp-conn-meta'>"
				+ "<span>" + mbpEsc(mbpL.clients) + ": " + mbpEsc(d.clients) + "</span>"
				+ "<span>" + mbpDot(d.rx_active ? "mbp-dot-active" : "mbp-dot-idle") + mbpEsc(mbpL.rx) + " " + mbpEsc(d.rx_human) + "</span>"
				+ "<span>" + mbpDot(d.tx_active ? "mbp-dot-active" : "mbp-dot-idle") + mbpEsc(mbpL.tx) + " " + mbpEsc(d.tx_human) + "</span>"
				+ "<span>" + mbpDot(d.device_connected ? "mbp-dot-up" : "mbp-dot-idle")
				+ mbpEsc(d.device_connected ? mbpL.devconnected : mbpL.devdisconnected) + "</span>"
				+ "</div></div>";
		});
		document.getElementById("mbp-status-devices").innerHTML = html;
	}).catch(function() {});
}
setInterval(mbpPollStatus, 5000);

var mbpNextIdx = <?php echo count($cfg["devices"]); ?>;
var mbpZuEntfernen = null;

function mbpRenumberDevices() {
	var nrs = document.querySelectorAll("#mbp-devices .mbp-device-nr");
	for (var i = 0; i < nrs.length; i++) {
		nrs[i].textContent = i + 1;
	}
}

function mbpAddDevice() {
	var tpl = document.getElementById("mbp-device-template").innerHTML;
	tpl = tpl.split("__IDX__").join(mbpNextIdx).split("__IDX_LABEL__").join(mbpNextIdx + 1);
	var wrapper = document.createElement("div");
	wrapper.innerHTML = tpl;
	var node = wrapper.firstElementChild;
	document.getElementById("mbp-devices").appendChild(node);
	mbpNextIdx++;
	mbpRenumberDevices();
	if (window.jQuery && jQuery(node).trigger) {
		jQuery(node).trigger("create");
	}
}

function mbpEntfernenAusfuehren() {
	if (mbpZuEntfernen) {
		mbpZuEntfernen.remove();
		mbpZuEntfernen = null;
		mbpRenumberDevices();
	}
}

function mbpRemoveDevice(el) {
	var karte = el.closest(".mbp-device");
	mbpZuEntfernen = karte;

	var nr = karte.querySelector(".mbp-device-nr");
	var adresse = karte.querySelector("input[name$='[url]']");
	var text = mbpL.removeconfirm;
	if (nr) {
		text = mbpL.device + " " + nr.textContent
			+ (adresse && adresse.value ? " (" + adresse.value + ")" : "")
			+ ": " + text;
	}
	document.getElementById("mbp-confirm-text").textContent = text;
	document.getElementById("mbp-confirm-overlay").classList.add("mbp-modal-open");
}

function mbpConfirmSchliessen() {
	document.getElementById("mbp-confirm-overlay").classList.remove("mbp-modal-open");
	mbpZuEntfernen = null;
}

document.getElementById("mbp-confirm-ok").addEventListener("click", function(e) {
	e.preventDefault();
	mbpEntfernenAusfuehren();
	mbpConfirmSchliessen();
});
document.getElementById("mbp-confirm-cancel").addEventListener("click", function(e) {
	e.preventDefault();
	mbpConfirmSchliessen();
});
document.getElementById("mbp-confirm-overlay").addEventListener("click", function(e) {
	if (e.target === this) {
		mbpConfirmSchliessen();
	}
});
</script>

<?php
LBWeb::lbfooter();
