<?
  $_GET['updateContainer'] = "true";
//	$_GET['communityApplications'] = true;
	include("/usr/local/emhttp/plugins/dynamix.docker.manager/include/CreateDocker.php");
?>
<script>
function addCloseButton() {
	addLog("<p class='centered'><button class='logLine' type='button' onclick='" + (top.Shadowbox ? "top.Shadowbox" : "window") + ".close()'>Done</button></p>");
}
addCloseButton();
</script>