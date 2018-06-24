#!/usr/bin/php
<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/helpers.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
require_once("/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php");

$plugins = glob("/boot/config/plugins/*.plg");
$templates = readJsonFile($communityPaths['community-templates-info']);
if ( ! $templates ) {
	echo "You must enter the apps tab before using this script\n";
	return;
}

echo "\n<b>Updating Support Links</b>\n\n";
echo "<table>";
foreach ($plugins as $plugin) {
	if ( ! plugin("support",$plugin) ) {
		$pluginURL = plugin("pluginURL",$plugin);
		$pluginEntry = searchArray($templates,"PluginURL",$pluginURL);
		if ( $pluginEntry !== false ) {
			$xml = simplexml_load_file($plugin);
			$xml->addAttribute("support",$templates[$pluginEntry]['Support']);
			$dom = new DOMDocument('1.0');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dom->loadXML($xml->asXML());
			file_put_contents($plugin, $dom->saveXML()); 
			echo "<tr><td><b>".plugin("name",$plugin)."</b></td><td> --> </td><td>".$templates[$pluginEntry]['Support']."</tr>";
		}
	}
}
echo "</table>";
?>