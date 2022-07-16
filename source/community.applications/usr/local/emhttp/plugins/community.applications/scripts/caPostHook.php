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
echo "Executing Community Applications Post-Plugin Settings\n";
@unlink("/tmp/community.applications/pluginPending/{$argv[2]}");
echo "Finished";
?>