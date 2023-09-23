#!/usr/bin/php
<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2023, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################
require_once "/usr/local/emhttp/plugins/community.applications/include/paths.php";
require_once "/usr/local/emhttp/plugins/community.applications/include/helpers.php";

$apps = readJsonFile($caPaths['community-templates-info']);
$plugins = explode("*",$argv[1]);
foreach ($plugins as $plugin) {
  echo $plugin;
  if (! $plugin ) continue;
  $pluginName = basename($plugin);
  $pathInfo = pathinfo($plugin);
  if ( $pathInfo['extension'] !== "plg" ) {
    if ( is_file("/var/log/plugins/lang-$pluginName.xml") ) {
      passthru("/usr/local/emhttp/plugins/community.applications/scripts/languageInstall.sh update $pluginName");
      continue;
    }
  }
  if ( searchArray($apps,"PluginURL",$plugin) !== false ) {
    if ( is_file("/var/log/plugins/$pluginName") ) {
      passthru("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin update $pluginName");
    } else {
      passthru("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin install ".escapeshellarg($plugin));
    }
  } else
    echo "$plugin not found in application feed\n";
  @unlink("{$caPaths['pluginPending']}/$pluginName");
}
passthru("/usr/local/emhttp/plugins/community.applications/scripts/updatePluginSupport.php");
?>