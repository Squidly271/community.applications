<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php");

$options = getPostArray("options");
$plugin = $options['plugin'];

if ( ! $plugin ) {
	echo json_encode(array("updateAvailable"=>false));
	return;
}

@unlink("/tmp/plugins/$plugin");
$url = @plugin("pluginURL","/boot/config/plugins/$plugin");
download_url($url,"/tmp/plugins/$plugin");

$changes = @plugin("changes","/tmp/plugins/$plugin");
$version = trim(@plugin("version","/tmp/plugins/$plugin"));
$min = @plugin("min","/tmp/plugins/$plugin") ?: "6.4.0";
file_put_contents("/tmp/plugins/".pathinfo($plugin, PATHINFO_FILENAME).".txt",$changes);
if ( $version > @plugin("version","/boot/config/plugins/$plugin") ) {
	$unraid = parse_ini_file($communityPaths['unRaidVersion']);
	if ( version_compare($min,$unraid['version'],">") ) {
		$update = false;
	} else {
		$update = true;
	}
} else {
	$update = false;
}
$output = json_encode(array("updateAvailable" => $update,"version" => $version,"min"=>$min,"changes"=>$changes));
file_put_contents("/tmp/blah",$output);
echo $output;
?>