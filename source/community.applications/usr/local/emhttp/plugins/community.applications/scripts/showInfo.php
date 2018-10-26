<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2018, Andrew Zawadzki #
#                                                             #
###############################################################
 
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/helpers.php");
require_once 'webGui/include/Markdown.php';

$app = urldecode($_POST['app']);
$name = urldecode($_POST['name']);

$path = $app ? "/tmp/plugins/$app" : "/var/log/plugins/community.applications.plg";
$changes = Markdown(shell_exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin changes $path"));
if ( $app == "community.applications.plg" ) {
	echo "<div style='overflow:scroll; height:60rem; width:55rem; overflow-x:hidden; overflow-y:hidden;'><center><img height='60px' src='/plugins/community.applications/images/community.applications.png'><br><font size='3' color='white'>Community Applications Updated Change Log</font><br></center>";
	echo "<center><a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7M7CBCVU732XG' target='_blank'><img src='https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif' height='20px'></a></center>";
} else {
	echo "<center><font size='3' color='white'>$name Change Log</center>";
}
echo "<br>";
echo "<style>body { margin-left:2rem;margin-right:2rem; }</style>";
echo "<div style='overflow:scroll; max-height:55rem; height:55rem; width:55rem; overflow-x:hidden; overflow-y:auto;'>$changes</div>";

?>