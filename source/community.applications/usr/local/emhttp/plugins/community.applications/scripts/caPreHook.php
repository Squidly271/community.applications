#!/usr/bin/php
<?
if ( $argv[1] == "check" || $argv[1] == "checkall" )
  return;
	
echo "Executing Community Applications Pre-Plugin Settings\n";
@mkdir("/tmp/community.applications/pluginPending",0777,true);
touch("/tmp/community.applications/pluginPending/{$argv[2]}");
?>