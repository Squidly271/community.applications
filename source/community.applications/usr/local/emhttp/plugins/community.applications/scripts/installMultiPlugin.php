#!/usr/bin/php
<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2021, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################
require_once "/usr/local/emhttp/plugins/community.applications/include/paths.php";
require_once "/usr/local/emhttp/plugins/community.applications/include/helpers.php";

$apps = readJsonFile($caPaths['community-templates-info']);
$plugins = explode("*",$argv[1]);
foreach ($plugins as $plugin) {
	if (! $plugin ) continue;
	if ( searchArray($apps,"PluginURL",$plugin) !== false ) 
		passthru("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin install ".escapeshellarg($plugin));
	else
		echo "$plugin not found in application feed\n";
}
passthru("/usr/local/emhttp/plugins/community.applications/scripts/updatePluginSupport.php");
?>