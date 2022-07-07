#!/usr/bin/php
<?
if ( $argv[1] == "check" || $argv[1] == "checkall" )
  return;
	
echo "Executing Community Applications Post-Plugin Settings\n";
@unlink("/tmp/community.applications/pluginPending/{$argv[2]}");
echo "Finished";
?>