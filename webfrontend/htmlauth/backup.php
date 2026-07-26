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

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && ($_POST["action"] ?? "") === "import") {
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
			// Übernimmt den eingestellten Log-Level; er ist nicht Teil des Exports.
			$neu = $parsed["config"];
			$neu["loglevel"] = mbp_read_config($configfile)["loglevel"];
			$yaml = mbp_config_to_yaml($neu);
			file_put_contents($configfile, $yaml);
			exec(escapeshellarg($ctlscript) . " restart 2>&1");
			$message = $L["EXPORT.IMPORT_OK"];
			$messagetype = "ok";
			// Meldet, wenn in der Datei eine auf eine Schnittstelle eingeschränkte
			// listen-Adresse stand; der Proxy lauscht danach auf allen Schnittstellen.
			if (!empty($parsed["host_verworfen"])) {
				$message .= " " . $L["EXPORT.IMPORT_HOST_RESET"];
				$messagetype = "error";
			}
		}
	}
}

$cfg = mbp_read_config($configfile);

mbp_navbar("backup.php", $L);
$pagetitle = $L["TITLE.PAGETITLE"] . (!empty($version) ? " V$version" : "");
LBWeb::lbheader($pagetitle, "https://pypi.org/project/modbus-proxy/", "help.html");
mbp_styles();
?>

<?php if ($message): ?>
	<div class="mbp-msg-<?php echo $messagetype; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<p class="wide"><?php echo $L["EXPORT.EXPORT_HEAD"]; ?></p>
<div class="mbp-box">
	<p class="mbp-hint"><?php echo $L["EXPORT.EXPORT_HINT"]; ?></p>
	<p><?php echo $L["EXPORT.CURRENT_DEVICES"]; ?>: <b><?php echo count($cfg["devices"]); ?></b></p>
	<a href="export.php" data-role="button" data-inline="true" data-mini="true" data-icon="arrow-d"><?php echo $L["EXPORT.EXPORT_BTN"]; ?></a>
</div>

<p class="wide"><?php echo $L["EXPORT.IMPORT_HEAD"]; ?></p>
<div class="mbp-box">
	<p class="mbp-hint"><?php echo $L["EXPORT.IMPORT_HINT"]; ?></p>
	<form action="backup.php" method="post" enctype="multipart/form-data">
		<input type="hidden" name="action" value="import">
		<label for="mbp-importfile"><?php echo $L["EXPORT.IMPORT_LABEL"]; ?></label>
		<input id="mbp-importfile" type="file" name="importfile" accept=".yml,.yaml" data-mini="true">
		<button type="submit" data-role="button" data-inline="true" data-mini="true" data-icon="arrow-u"><?php echo $L["EXPORT.IMPORT_BTN"]; ?></button>
	</form>
</div>

<?php
LBWeb::lbfooter();
