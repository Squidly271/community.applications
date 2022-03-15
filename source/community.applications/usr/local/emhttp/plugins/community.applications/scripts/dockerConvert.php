<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2022, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################
?>
<style>
.logLine{color:black !important;}
</style>
<?php
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

$_SERVER['REQUEST_URI'] = "docker/apps";
@require_once "$docroot/plugins/dynamix/include/Translations.php";

require_once "$docroot/plugins/community.applications/include/paths.php";
require_once "$docroot/plugins/dynamix/include/Wrappers.php";
require_once "$docroot/plugins/community.applications/include/helpers.php";

$unRaidVersion = parse_ini_file($caPaths['unRaidVersion']);

$exeFile = "/usr/local/emhttp/plugins/dynamix.docker.manager/include/CreateDocker.php";

$javascript = file_get_contents("/usr/local/emhttp/plugins/dynamix/javascript/dynamix.js");
echo "<script>$javascript</script>";

if ( $_GET['ID'] !== false) {
	$dockerID = $_GET['ID'];
	$file = readJsonFile($caPaths['dockerSearchResults']);
	$dockerIndex = searchArray($file['results'],"ID",$dockerID);
	$docker = $file['results'][$dockerIndex];
	$docker['Description'] = str_replace("&", "&amp;", $docker['Description']);

	$dockerfile['Name'] = "CA_TEST_CONTAINER_DOCKERHUB";
	$dockerfile['Support'] = $docker['DockerHub'];
	$dockerfile['Description'] = $docker['Description']."\n\nConverted By Community Applications   Always verify this template (and values)  against the support page for the container\n\n{$docker['DockerHub']}";
	$dockerfile['Overview'] = $dockerfile['Description'];
	$dockerfile['Registry'] = $docker['DockerHub'];
	$dockerfile['Repository'] = $docker['Repository'];
	$dockerfile['BindTime'] = "true";
	$dockerfile['Privileged'] = "false";
	$dockerfile['Networking']['Mode'] = "bridge";
	$dockerfile['Icon'] = "/plugins/dynamix.docker.manager/images/question.png";
	$dockerXML = makeXML($dockerfile);
	file_put_contents("/boot/config/plugins/dockerMan/templates-user/my-CA_TEST_CONTAINER_DOCKERHUB.xml",$dockerXML);
	
	
	echo "<div id='output'>";
	$dockers = ["CA_TEST_CONTAINER_DOCKERHUB"];
	echo sprintf(tr("Installing test container"),str_replace(",",", ",$_GET['docker']))."<br>";
	$_GET['updateContainer'] = true;
	$_GET['ct'] = $dockers;
	$_GET['communityApplications'] = true;
	$_GET['mute'] = false;
	@include($exeFile); # under new GUI, this line returns a duplicated session_start() error.  
	echo "</div>";
?>

<script>
$("button").hide();
var cursor = "";
function addLog(logLine) {
	var scrollTop = (window.pageYOffset !== undefined) ? window.pageYOffset : (document.documentElement || document.body.parentNode).scrollTop;
	var clientHeight = (document.documentElement || document.body.parentNode).clientHeight;
	var scrollHeight = (document.documentElement || document.body.parentNode).scrollHeight;
	var isScrolledToBottom = scrollHeight - clientHeight <= scrollTop + 1;
	if (logLine.slice(-1) == "\n") {
		document.body.innerHTML = document.body.innerHTML.slice(0,cursor) + logLine.slice(0,-1) + "<br>";
		lastLine = document.body.innerHTML.length;
		cursor = lastLine;
	}
	else if (logLine.slice(-1) == "\r") {
		document.body.innerHTML = document.body.innerHTML.slice(0,cursor) + logLine.slice(0,-1);
		cursor = lastLine;
	}
	else if (logLine.slice(-1) == "\b") {
		if (logLine.length > 1)
			document.body.innerHTML = document.body.innerHTML.slice(0,cursor) + logLine.slice(0,-1);
		cursor += logLine.length-2;
	}
	else {
		document.body.innerHTML += logLine;
		cursor += logLine.length;
	}
	if (isScrolledToBottom) {
		window.scrollTo(0,document.body.scrollHeight);
	}
}
function addCloseButton() {
	addLog("<p class='centered'><button class='logLine' type='button' onclick='" + (top.Shadowbox ? "top.Shadowbox" : "window") + ".close()'><?=tr("Done")?></button></p>");
}
</script>
<?
	$output = shell_exec("docker inspect CA_TEST_CONTAINER_DOCKERHUB");
	echo "<br>".tr("Removing test installation")."<br>";
	exec("docker rm CA_TEST_CONTAINER_DOCKERHUB");
	
	exec("docker rmi {$docker['Repository']}");
	@unlink("/boot/config/plugins/dockerMan/templates-user/my-CA_TEST_CONTAINER_DOCKERHUB.xml");
	
	$json = json_decode($output,true);
	if ( $json ) {
		$paths = isset($json[0]['Mounts']) ? $json[0]['Mounts'] : [];
		$ports = isset($json[0]['Config']['ExposedPorts']) ? $json[0]['Config']['ExposedPorts'] : [];
		$vars = isset($json[0]['Config']['Env']) ? $json[0]['Config']['Env'] : [];
		
		foreach ($paths as $path) {
			$p = ["Name"=>"Path",'Type'=>"Path","Target"=>$path['Destination'],"Default"=>"","Mode"=>"rw","Display"=>"always","Required"=>"false","Mask"=>"false"];
			$Config[]['@attributes'] = $p;
			
		}
		foreach ($ports as $port => $name) {
			$pp = explode("/",$port);
			$p = ["Name"=>"Port",'Type'=>"Port","Target"=>$pp[0],"Default"=>$pp[0],"Mode"=>$pp[1],"Display"=>"always","Required"=>"false","Mask"=>"false","Description"=>""];
			$Config[]['@attributes'] = $p;
		}
		foreach ($vars as $var) {
			$textvars .= "$var\n";
		}
		$testvars = parse_ini_string($textvars);
		$defaultvars = ["HOST_HOSTNAME","HOST_OS","HOST_CONTAINERNAME","TZ","PATH"];
		foreach ($testvars as $var => $varcont) {
			if ( in_array($var,$defaultvars) )
				continue;
			$p = ["Name"=>"Variable",'Target'=>$var,"Type"=>"Variable","Default"=>$varcont,"Description"=>"","Required"=>"false","Mask"=>"false","Display"=>"always"];
			$Config[]['@attributes'] = $p;
		}
		if ( $Config )
			$dockerfile['Config'] = $Config;
	}
	$dockerfile['Name'] = $docker['Name'];
	file_put_contents($caPaths['dockerSearchInstall'],makeXML($dockerfile));	
}
?>
<script>
	<? if ( $json ): ?>
		window.parent.location = "/Apps/AddContainer?xmlTemplate=default:<?=$caPaths['dockerSearchInstall']?>";

	<? else: ?>
		alert("<?tr("An error occurred - Could not determine configuration")?>");
		window.parent.location = "/Apps/AddContainer?xmlTemplate=default:<?=$caPaths['dockerSearchInstall']?>";
	
	<? endif; ?>
</script>