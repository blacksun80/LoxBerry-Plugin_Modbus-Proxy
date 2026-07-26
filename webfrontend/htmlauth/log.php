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

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && ($_POST["action"] ?? "") === "saveloglevel") {
	$cfg = mbp_read_config($configfile);
	if (isset($_POST["loglevel"]) && in_array($_POST["loglevel"], MBP_LOGLEVELS)) {
		$cfg["loglevel"] = $_POST["loglevel"];
	}
	$yaml = mbp_config_to_yaml($cfg);
	if (file_put_contents($configfile, $yaml) !== false) {
		exec(escapeshellarg($ctlscript) . " restart 2>&1");
		$message = $L["LOG.SAVED_OK"];
		$messagetype = "ok";
	} else {
		$message = $L["CONFIG.SAVE_ERROR"];
		$messagetype = "error";
	}
}

$cfg = mbp_read_config($configfile);

$loginhalt = mbp_tail($logfile, MBP_LOG_LINES);
// Pfad relativ zum LoxBerry-Log-Verzeichnis, so erwartet ihn der System-Logviewer.
$logrelativ = "plugins/" . basename($lbplogdir) . "/" . basename($logfile);

mbp_navbar("log.php", $L);
$pagetitle = $L["TITLE.PAGETITLE"] . (!empty($version) ? " V$version" : "");
LBWeb::lbheader($pagetitle, "https://pypi.org/project/modbus-proxy/", "help.html");
mbp_styles();
?>

<?php if ($message): ?>
	<div class="mbp-msg-<?php echo $messagetype; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<p class="wide"><?php echo $L["LOG.LEVEL_HEAD"]; ?></p>
<div class="mbp-box">
	<p class="mbp-hint"><?php echo $L["LOG.LEVEL_HINT"]; ?></p>
	<form action="log.php" method="post" id="mbploglevelform">
		<input type="hidden" name="action" value="saveloglevel">
		<div class="mbp-field">
			<label for="mbp-loglevel"><?php echo $L["CONFIG.LOG_LEVEL"]; ?></label>
			<select id="mbp-loglevel" name="loglevel" data-mini="true" onchange="this.form.submit();">
				<?php foreach (MBP_LOGLEVELS as $lvl): ?>
					<option value="<?php echo $lvl; ?>" <?php echo ($cfg["loglevel"] === $lvl) ? "selected" : ""; ?>><?php echo $lvl; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</form>
</div>

<div class="mbp-sechead">
	<p class="wide"><?php echo $L["LOG.HEAD"]; ?></p>
	<div class="mbp-sechead-action">
		<a href="/admin/system/tools/logfile.cgi?logfile=<?php echo urlencode($logrelativ); ?>&amp;header=html&amp;format=template" target="_blank" data-role="button" data-inline="true" data-mini="true" data-icon="action"><?php echo $L["LOG.OPEN_FULL"]; ?></a>
	</div>
</div>
<div class="mbp-box">
	<p class="mbp-hint"><?php echo $L["LOG.HINT"]; ?></p>
	<?php if ($loginhalt === null): ?>
		<p><?php echo $L["LOG.NO_LOGFILE"]; ?></p>
	<?php elseif (trim($loginhalt) === ""): ?>
		<p><?php echo $L["LOG.EMPTY"]; ?></p>
	<?php else: ?>
		<pre class="mbp-logview" id="mbp-logview"><?php echo htmlspecialchars($loginhalt); ?></pre>
		<p class="mbp-loginfo"><?php echo $L["LOG.TAIL_INFO"]; ?> &mdash; <?php echo htmlspecialchars($logfile); ?></p>
	<?php endif; ?>
	<div class="mbp-btnrow">
		<button type="button" data-role="button" data-inline="true" data-mini="true" data-icon="refresh" onclick="mbpLogsLaden();"><?php echo $L["LOG.REFRESH"]; ?></button>
		<span class="mbp-autoinfo"><?php echo $L["LOG.AUTO_REFRESH"]; ?></span>
	</div>
</div>

<script>
// Gilt als "am Ende", solange der Abstand kleiner als diese Zahl an Pixeln ist.
var MBP_ENDE_TOLERANZ = 30;

function mbpAmEnde(el) {
	return el.scrollHeight - el.scrollTop - el.clientHeight < MBP_ENDE_TOLERANZ;
}

function mbpAnsEnde(el) {
	el.scrollTop = el.scrollHeight;
}

// Ans Ende der Logausgabe springen, dort stehen die neuesten Meldungen.
(function() {
	var v = document.getElementById("mbp-logview");
	if (v) {
		mbpAnsEnde(v);
	}
})();

// Neuen Logtext einsetzen. Wer gerade weiter oben liest, wird nicht ans Ende gerissen.
function mbpLogSetzen(id, text) {
	var v = document.getElementById(id);
	if (!v || text === null || text === undefined) {
		return;
	}
	if (v.textContent === text) {
		return;
	}
	var warAmEnde = mbpAmEnde(v);
	v.textContent = text;
	if (warAmEnde) {
		mbpAnsEnde(v);
	}
}

function mbpLogsLaden() {
	fetch("logdata.php").then(function(r) { return r.json(); }).then(function(d) {
		mbpLogSetzen("mbp-logview", d.log);
	}).catch(function() {});
}

setInterval(mbpLogsLaden, <?php echo MBP_POLL_MS; ?>);
</script>

<?php
LBWeb::lbfooter();
