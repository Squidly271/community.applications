#!/usr/bin/php
<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2022, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################
if ( $argv[1] == "check" || $argv[1] == "checkall" )
  return;
	
echo "Executing Community Applications Pre-Plugin Settings\n";
@mkdir("/tmp/community.applications/pluginPending",0777,true);
touch("/tmp/community.applications/pluginPending/{$argv[2]}");
if ( $argv[1] == "update" ) {
	if ( is_file("/var/log/plugins/{$argv[2]}") )
		passthru("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin check ".escapeshellarg($argv[2]));
	return;
}

?>