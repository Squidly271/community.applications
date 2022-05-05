#!/usr/bin/php
<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2022, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

require_once "$docroot/plugins/community.applications/include/paths.php";

if ( is_file($caPaths['updateRunning']) && file_exists("/proc/".@file_get_contents($caPaths['updateRunning'])) ) {
	exit();
}

file_put_contents($caPaths['Running'],getmypid());

$updateFile = is_file("$docroot/plugins/dynamix.docker.manager/scripts/dockerupdate") ? "dockerupdate" : "dockerupdate.php";
exec("$docroot/plugins/dynamix.docker.manager/scripts/dockerupdate check nonotify > /dev/null 2>&1");
foreach (glob("/var/log/plugins/*.plg") as $plg) {
	if ( $plg == "/var/log/plugins/community.applications.plg" || $plg == "unRAIDServer.plg" || $plg == "gui.search.plg" || $plg == "page.notes.plg")
		continue; // avoid possible race condition since CA / gui.search automatically check for updates for themselves when on Apps tab

	exec("$docroot/plugins/dynamix.plugin.manager/scripts/plugin check ".escapeshellarg(basename($plg))." > /dev/null 2>&1");
}
@unlink($caPaths['updateRunning']);
?>