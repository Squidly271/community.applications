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

$pluginURL = $argv[2];

if ( ! $pluginURL ) {
  echo "No URL passed";
  exit(1);
}
$apps = readJsonFile($caPaths['community-templates-info']);
if ( searchArray($apps,"PluginURL",$pluginURL) !== false || $argv[1] == "update" || $argv[1] == "remove") {
  passthru("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin ".escapeshellarg($argv[1])." ".escapeshellarg($argv[2]));
  passthru("/usr/local/emhttp/plugins/community.applications/scripts/updatePluginSupport.php");
}
else
  echo "URL passed for installation does not exist in application feed\n";
?>
