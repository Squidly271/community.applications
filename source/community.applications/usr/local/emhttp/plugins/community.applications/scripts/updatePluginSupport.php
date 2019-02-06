#!/usr/bin/php
<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2019, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

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
foreach ($plugins as $plugin) {
  if ( ! plugin("support",$plugin) ) {
    $pluginURL = plugin("pluginURL",$plugin);
    $pluginEntry = searchArray($templates,"PluginURL",$pluginURL);
    if ( $pluginEntry === false ) {
      $pluginEntry = searchArray($templates,"PluginURL",str_replace("https://raw.github.com/","https://raw.githubusercontent.com/",$pluginURL));
    }
    if ( $pluginEntry !== false && $templates[$pluginEntry]['PluginURL']) {
      $xml = simplexml_load_file($plugin);
      if ( ! $templates[$pluginEntry]['Support'] ) {
        continue;
      }
      $xml->addAttribute("support",$templates[$pluginEntry]['Support']);
      $dom = new DOMDocument('1.0');
      $dom->preserveWhiteSpace = false;
      $dom->formatOutput = true;
      $dom->loadXML($xml->asXML());
      file_put_contents($plugin, $dom->saveXML()); 
      echo "<b>".plugin("name",$plugin)."</b> --> ".$templates[$pluginEntry]['Support']."\n";
    }
  }
}
?>