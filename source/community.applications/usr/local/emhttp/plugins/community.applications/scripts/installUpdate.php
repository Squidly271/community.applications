<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2021, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################
require_once "/usr/local/emhttp/plugins/dynamix/include/Helpers.php";

$_GET['updateContainer'] = "true";
$_GET['mute'] = false;
//	$_GET['communityApplications'] = true;
	include("/usr/local/emhttp/plugins/dynamix.docker.manager/include/CreateDocker.php");
?>
<script src='<?autov("/plugins/dynamix/javascript/dynamix.js")?>'></script>
<script>
// Redefine the done button to something CA can use
$(":button").attr("onclick","top.Shadowbox.close();");
</script>
