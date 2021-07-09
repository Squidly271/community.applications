<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2021, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

if ( $translations ) {
	$_SERVER['REQUEST_URI'] = "docker/apps";
	require_once "$docroot/plugins/dynamix/include/Translations.php";
}

require_once "$docroot/plugins/community.applications/include/paths.php";
require_once "$docroot/plugins/dynamix/include/Wrappers.php";
require_once "$docroot/plugins/community.applications/include/helpers.php";

$unRaidVersion = parse_ini_file($caPaths['unRaidVersion']);
$translations  = is_file("$docroot/plugins/dynamix/include/Translations.php");

$exeFile = "/usr/local/emhttp/plugins/dynamix.docker.manager/include/CreateDocker.php";
$javascript = file_get_contents("/usr/local/emhttp/plugins/dynamix/javascript/dynamix.js");
echo "<script>$javascript</script>";

if ( $_GET['docker'] ) {
	echo "<div id='output'>";
	$dockers = explode(",",$_GET['docker']);
	$msg = $_GET['upgrade'] ? "Upgrading docker application %s" : "Installing docker applications %s";
	echo sprintf(tr($msg)),str_replace(",",", ",$_GET['docker']))."<br>";
	$_GET['updateContainer'] = true;
	$_GET['ct'] = $dockers;
	$_GET['communityApplications'] = true;
	@include($exeFile); # under new GUI, this line returns a duplicated session_start() error.  
	echo "</div>";
?>
<script>
$("input,#output").hide();

var cursor = "";
function addLog(logLine) {
	var scrollTop = (window.pageYOffset !== undefined) ? window.pageYOffset : (document.documentElement || document.body.parentNode).scrollTop;
	var clientHeight = (document.documentElement || document.body.parentNode).clientHeight;
	var scrollHeight = (document.documentElement || document.body.parentNode).scrollHeight;
	var isScrolledToBottom = scrollHeight - clientHeight <= scrollTop + 1;
	if (logLine.slice(-1) == "\n") {
		document.body.innerHTML = document.body.innerHTML.slice(0,cursor) + logLine.slice(0,-1) + "<br>";
		lastLine = document.body.innerHTML.length;
		cursor = lastLine;
	}
	else if (logLine.slice(-1) == "\r") {
		document.body.innerHTML = document.body.innerHTML.slice(0,cursor) + logLine.slice(0,-1);
		cursor = lastLine;
	}
	else if (logLine.slice(-1) == "\b") {
		if (logLine.length > 1)
			document.body.innerHTML = document.body.innerHTML.slice(0,cursor) + logLine.slice(0,-1);
		cursor += logLine.length-2;
	}
	else {
		document.body.innerHTML += logLine;
		cursor += logLine.length;
	}
	if (isScrolledToBottom) {
		window.scrollTo(0,document.body.scrollHeight);
	}
}
function addCloseButton() {
	addLog("<p class='centered'><button class='logLine' type='button' onclick='" + (top.Shadowbox ? "top.Shadowbox" : "window") + ".close()'><?=tr("Done")?></button></p>");
}
</script>
<?
	if ( ! $_GET['upgrade'] ) {
		foreach ($dockers as $docker) {
			echo sprintf(tr("Starting %s"),"<span class='ca_bold'>$docker</span>")."<br>";
			unset($output);
			exec("docker start $docker 2>&1",$output,$retval);
			if ($retval) {
				$failFlag = true;
				echo sprintf(tr("%s failed to start.  You should install it by itself to fix the errors"),"<span class='ca_bold'>$docker</span>")."<br>";
				foreach ($output as $line) {
					echo "<tt>$line</tt><br>";
				}
				echo "<br>";
			}
		}
		echo "<br>".tr("Setting installed applications to autostart")."<br>";
		$autostartFile = @file("/var/lib/docker/unraid-autostart",FILE_IGNORE_NEW_LINES);
		if ( ! $autostartFile ) {
			$autostartFile = array();
		}
		foreach ($autostartFile as $line) {
			$autostart[$line] = true;
		}
		foreach ($dockers as $docker) {
			$autostart[$docker] = true;
		}
		$autostartFile = implode("\n",array_keys($autostart));
		file_put_contents("/var/lib/docker/unraid-autostart",$autostartFile);

		if ( $failFlag || !$_GET['plugin']) {
			echo "<br>".tr("Docker Application Installation finished")."<br><script>addCloseButton();</script>";
		} else {
			echo "<script>top.Shadowbox.close();</script>";
		}
		@unlink("/tmp/community.applications/tempFiles/newCreateDocker.php");
	}
}
?>