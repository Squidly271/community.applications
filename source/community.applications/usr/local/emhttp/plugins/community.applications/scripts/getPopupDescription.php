<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2019, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

libxml_use_internal_errors(true); # Suppress any warnings from xml errors.  FCP will catch those errors

require_once("/usr/local/emhttp/plugins/dynamix/include/Helpers.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/dynamix/include/Wrappers.php");
require_once("/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php");
require_once("webGui/include/Markdown.php");

$unRaidVars = parse_ini_file("/var/local/emhttp/var.ini");
$communitySettings = parse_plugin_cfg("community.applications");
$csrf_token = $unRaidVars['csrf_token'];
$tabMode = '_parent';

if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
	$communitySettings['dockerRunning'] = "true";
	$DockerTemplates = new DockerTemplates();
	$DockerClient = new DockerClient();
	$info = $DockerTemplates->getAllInfo();
	$dockerRunning = $DockerClient->getDockerContainers();
} else {
	unset($communitySettings['dockerRunning']);
	$info = array();
	$dockerRunning = array();
}
if ( ! is_file($communityPaths['warningAccepted']) ) {
	$communitySettings['NoInstalls'] = true;
}
$appNumber =  urldecode($_POST['appPath']);
$appName = urldecode($_POST['appName']);

if ( $appNumber == "ca" ) {
	plugin("check","community.applications.plg");
	echo Markdown(plugin("changes","/tmp/plugins/community.applications.plg"));
	return;
}
# $appNumber is actually the path to the template.  It's pretty much always going to be the same even if the database is out of sync.
$displayed = readJsonFile($communityPaths['community-templates-displayed']);
foreach ($displayed as $file) {
	$index = searchArray($file,"Path",$appNumber);
	if ( $index === false ) {
		continue;
	} else {
		$template = $file[$index];
		$Displayed = true;
		break;
	}
}
# handle case where the app being asked to display isn't on the most recent displayed list (ie: multiple browser tabs open)
if ( ! $template ) {
	$file = readJsonFile($communityPaths['community-templates-info']);
	$index = searchArray($file,"Path",$appNumber);

	if ( $index === false ) {
		echo json_encode(array("description"=>"Something really wrong happened<br>Reloading the Apps tab will probably fix the problem"));
		return;
	}
	$template = $file[$index];
	$Displayed = false;
}

$ID = $template['ID'];

$donatelink = $template['DonateLink'];
if ( $donatelink ) {
	$donatetext = $template['DonateText'];
	if ( ! $donatetext ) {
		$donatetext = $template['Plugin'] ? "Donate To Author" : "Donate To Maintainer";
	}
}

if ( ! $template['Plugin'] ) {
	foreach ($dockerRunning as $testDocker) {
		$templateRepo = explode(":",$template['Repository']);
		$testRepo = explode(":",$testDocker['Image']);
		if ($templateRepo[0] == $testRepo[0]) {
			$selected = true;
			$name = $testDocker['Name'];
			break;
		}
	}
} else {
	$pluginName = basename($template['PluginURL']);
}

if ( $template['trending'] ) {
	$allApps = readJsonFile($communityPaths['community-templates-info']);

	$allTrends = array_unique(array_column($allApps,"trending"));
	rsort($allTrends);
	$trendRank = array_search($template['trending'],$allTrends) + 1;
}

$template['Category'] = categoryList($template['Category'],true);
$template['Icon'] = $template['Icon'] ? $template['Icon'] : "/plugins/dynamix.docker.manager/images/question.png";
$template['Description'] = trim($template['Description']);
$template['ModeratorComment'] .= $template['CAComment'];

$templateDescription .= "<div style='width:60px;height:60px;display:inline-block;position:absolute;'>";
if ( $template['IconFA'] ) {
	$template['IconFA'] = $template['IconFA'] ?: $template['Icon'];
	$templateIcon = startsWith($template['IconFA'],"icon-") ? $template['IconFA'] : "fa fa-{$template['IconFA']}";
	$templateDescription .= "<i class='$templateIcon popupIcon ca_center' id='icon'></i>";
} else {
	$templateDescription .= "<img class='popupIcon' id='icon' src='{$template['Icon']}'>";
}
$templateDescription .= "</div><div style='display:inline-block;margin-left:105px;'>";
$templateDescription .= "<table style='font-size:0.9rem;'>";
$author = $template['PluginURL'] ? $template['PluginAuthor'] : $template['SortAuthor'];
$templateDescription .= "<tr><td style='width:25%;'>Author:</td><td>$author</a></td></tr>";
if ( ! $template['Plugin'] ) {
	$repository = explode(":",$template['Repository']);
	$official =  ( count(explode("/",$repository[0])) == 1 ) ? "_" : "r";
	$templateDescription .= "<tr><td>DockerHub:</td><td><a class='popUpLink' href='{$template['Registry']}' target='_blank'>{$repository[0]}</a></td></tr>";
}
$templateDescription .= "<tr><td>Repository:</td><td>";
$repoSearch = explode("'",$template['RepoName']);
$templateDescription .= "{$template['RepoName']}</a>";
if ( $template['Profile'] ) {
	$profileDescription = $template['Plugin'] ? "Author" : "Maintainer";
	$templateDescription .= "<span>&nbsp;&nbsp;<a class='popUpLink' href='{$template['Profile']}' target='_blank'>$profileDescription Profile</a></span>";
}
$templateDescription .= "</td></tr>";
$templateDescription .= ($template['Private'] == "true") ? "<tr><td></td><td><font color=red>Private Repository</font></td></tr>" : "";
if ( $template['Category'] ) {
	$templateDescription .= "<tr><td>Categories:</td><td>".$template['Category'];
	if ( $template['Beta'] ) {
		$templateDescription .= " (Beta)";
	}
	$templateDescription .= "</td></tr>";
}
if ( ! $template['Plugin'] ) {
	if ( strtolower($template['Base']) == "unknown" || ! $template['Base']) {
		$template['Base'] = $template['BaseImage'];
	}
	if ( $template['Base'] ) {
		$templateDescription .= "<tr><td nowrap>Base OS:</td><td>".$template['Base']."</td></tr>";
	}
}
$templateDescription .= $template['stars'] ? "<tr><td nowrap>DockerHub Stars:</td><td><span class='dockerHubStar'></span> ".$template['stars']."</td></tr>" : "";

if ( $template['FirstSeen'] > 1 ) {
	$templateDescription .= "<tr><td>Added to CA:</td><td>".date("M d, Y",$template['FirstSeen'])."</td></tr>";
}

# In this day and age with auto-updating apps, NO ONE keeps up to date with the date updated.  Remove from docker containers to avoid confusion
if ( $template['Date'] && $template['Plugin'] ) {
	$niceDate = date("F j, Y",$template['Date']);
	$templateDescription .= "<tr><td nowrap>Date Updated:</td><td>$niceDate<br></td></tr>";
}
$unraidVersion = parse_ini_file($communityPaths['unRaidVersion']);
if ( version_compare($unRaidVersion['version'],$template['MinVer'],">") ) {
	$templateDescription .= ($template['MinVer'] != "6.0")&&($template['MinVer'] != "6.1") ? "<tr><td nowrap>Minimum OS:</td><td>unRaid v".$template['MinVer']."</td></tr>" : "";
}
$template['MaxVer'] = $template['MaxVer'] ?: $template['DeprecatedMaxVer'];
$templateDescription .= $template['MaxVer'] ? "<tr><td nowrap>Max OS:</td><td>unRaid v".$template['MaxVer']."</td></tr>" : "";
$downloads = getDownloads($template['downloads']);
if ($downloads) {
	$templateDescription .= "<tr><td>Total&nbsp;Downloads:</td><td>$downloads</td></tr>";
}
$templateDescription .= $template['Licence'] ? "<tr><td>Licence:</td><td>".$template['Licence']."</td></tr>" : "";
if ( $template['trending'] ) {
	$templateDescription .= "<tr><td>Monthly Trend:</td><td>{$template['trending']}% (Rank: #$trendRank)";
	if (is_array($template['trends']) && (count($template['trends']) > 1) ){
		$templateDescription .= (end($template['trends']) > $template['trends'][count($template['trends'])-2]) ? " <span class='trendingUp'></span>" : " <span class='trendingDown'></span>";
		$templateDescription .= " <span>&nbsp;&nbsp;<a class='graphLink' href='#' onclick='showGraphs();'>Show Graphs</a></span></td></tr>";
	}
	$templateDescription .= "<tr><td></td><td>(As of ".date("M d, Y - h:i a",$template['LastUpdateScan']).")</td></tr>";
	if (is_array($template['trends']) && (count($template['trends']) > 1) ){
		$templateDescription .= "<tr><td colspan='2'><canvas id='trendChart' class='caChart' height=1 width=3 style='display:none;'></canvas></td></tr>";
		if ( $template['downloadtrend'] ) {
			$templateDescription .= "<tr><td colspan='2'><canvas id='downloadChart' class='caChart' height=1 width=3 style='display:none;'></canvas></td></tr>";
			$templateDescription .= "<tr><td colspan='2'><canvas id='totalDownloadChart' class='caChart' height=1 width=3 style='display:none;'></canvas></td></tr>";
		}
	}
	$template['description'] .= "</td></tr>";
}
$templateDescription .= "</table></div>";

$templateDescription .= "<div class='ca_center'><span class='popUpDeprecated'>";
if ($template['Blacklist']) {
	$templateDescription .= "This application / template has been blacklisted<br>";
}
if ($template['Deprecated']) {
	$templateDescription .= "This application / template has been deprecated<br>";
}
if ( !$template['Compatible'] ) {
	$templateDescription .= "This application is not compatible with your version of unRaid<br>";
}
$templateDescription .= "</span></div><hr>";

if ( ! $Displayed ) {
	$templateDescription .= "<div><span class='ca_fa-warning warning-yellow'></span>&nbsp; <font size='1'>Another browser tab or device has updated the displayed templates.  Some actions are not available</font></div>";
}
if ( $Displayed && ! $template['NoInstall'] && ! $communitySettings['NoInstalls']) {
	if ( ! $template['Plugin'] ) {
		if ( $communitySettings['dockerRunning'] ) {
			if ( $selected ) {
				$installLine .= $communitySettings['defaultReinstall'] == "true" ? "<a class='ca_apptooltip appIconsPopUp ca_fa-install' href='/Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='$tabMode'>&nbsp;&nbsp;Reinstall (default)</a>" : "";
				$installLine .= "<a class='ca_apptooltip appIconsPopUp ca_fa-edit' href='/Apps/UpdateContainer?xmlTemplate=edit:".addslashes($info[$name]['template'])."' target='$tabMode'>&nbsp;&nbsp;Edit</a>";
				if ( $info[$name]['url'] && $info[$name]['running'] ) {
					$installLine .= "<a class='ca_apptooltip appIconsPopUp ca_fa-globe' href='{$info[$name]['url']}' target='_blank'>&nbsp;&nbsp;WebUI</a>";
				}
			} else {
				if ( $template['MyPath'] ) {
					$installLine .= "<a class='ca_apptooltip appIconsPopUp ca_fa-install' href='/Apps/AddContainer?xmlTemplate=user:".addslashes($template['MyPath'])."' target='$tabMode'>&nbsp;&nbsp;Reinstall</a>";
				} else {
					$install = "<a class='ca_apptooltip appIconsPopUp ca_fa-install' href='/Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='$tabMode'>&nbsp;&nbsp;Install</a>";
					$installLine .= $template['BranchID'] ? "<a style='cursor:pointer' class='ca_apptooltip appIconsPopUp ca_fa-install' onclick='$(&quot;#branch&quot;).show(500);'>&nbsp;&nbsp;Install</a>" : $install;
				}
			}
		}
	} else {
		if ( file_exists("/var/log/plugins/$pluginName") ) {
			$pluginSettings = $pluginName == "community.applications.plg" ? "ca_settings" : plugin("launch","/var/log/plugins/$pluginName");
			if ( $pluginSettings ) {
				$installLine .= "<a class='ca_apptooltip appIconsPopUp ca_fa-pluginSettings' href='/Apps/$pluginSettings' target='$tabMode'>&nbsp;&nbsp;Settings</a>";
			}
		} else {
			$buttonTitle = $template['MyPath'] ? "Reinstall" : "Install";
			$installLine .= "<a style='cursor:pointer' class='ca_apptooltip appIconsPopUp ca_fa-install pluginInstall' onclick=installPlugin('".$template['PluginURL']."');>&nbsp;&nbsp;$buttonTitle</a>";
		}
	}
}
if ( $template['Support'] || $template['Project'] ) {
	$installLine .= "<span style='float:right;'>";
	$installLine .= $template['Support'] ? "<a class='appIconsPopUp ca_fa-support' href='".$template['Support']."' target='_blank'>&nbsp;&nbsp;Support</strong></a>&nbsp;&nbsp;" : "";
	$installLine .= $template['Project'] ? "<a class='appIconsPopUp ca_fa-project' href='".$template['Project']."' target='_blank'>&nbsp;&nbsp;Project</strong></a>" : "";
	$installLine .= "</span>";
}
if ( $installLine ) {
	$templateDescription .= "<font size:0.9rem;>$installLine</font><br>";
	if ($template['BranchID']) {
		$templateDescription .= "<span id='branch' style='display:none;'>";
		$templateDescription .= formatTags($template['ID'],"_parent");
		$templateDescription .= "</span>";
	}
	$templateDescription .= "<hr>";
}
$templateDescription .= strip_tags($template['Description']);
$templateDescription .= $template['ModeratorComment'] ? "<br><br><span class='ca_bold'><font color='red'>Moderator Comments:</font></span> ".$template['ModeratorComment'] : "";
$templateDescription .= "</p><br><div class='ca_center'>";

if ( $donatelink ) {
	$templateDescription .= "<span style='float:right;text-align:right;'><font size=0.75rem;>$donatetext</font>&nbsp;&nbsp;<a class='popup-donate donateLink' href='$donatelink' target='_blank'>Donate</a></span><br><br>";
}
$templateDescription .= "</div>";
if ($template['Plugin']) {
	$dupeList = readJsonFile($communityPaths['pluginDupes']);
	if ( $dupeList[basename($template['Repository'])] == 1 ){
		$allTemplates = readJsonFile($communityPaths['community-templates-info']);
		foreach ($allTemplates as $testTemplate) {
			if ($testTemplate['Repository'] == $template['Repository']) {
				continue;
			}
			if ($testTemplate['Plugin'] && (basename($testTemplate['Repository']) == basename($template['Repository']))) {
				$duplicated .= $testTemplate['Author']." - ".$testTemplate['Name'];
			}
		}
		$templateDescription .= "<br>This plugin has a duplicated name from another plugin $duplicated.  This will impact your ability to install both plugins simultaneously<br>";
	}
}

if ( $template['Plugin'] ) {
	download_url($template['PluginURL'],$communityPaths['pluginTempDownload']);
	$template['Changes'] = @plugin("changes",$communityPaths['pluginTempDownload']);
}
$changeLogMessage = "<div class='ca_center'><font size='0'>Note: not all ";
$changeLogMessage .= $template['PluginURL'] ? "authors" : "maintainers";
$changeLogMessage .= " keep up to date on change logs</font></div><br>";

if ( trim($template['Changes']) ) {
	if ( $appNumber != "ca" && $appNumber != "ca_update" ) {
		$templateDescription .= "</div><hr>";
	}
	if ( $template['Plugin'] ) {
		if ( file_exists("/var/log/plugins/$pluginName") ) {
			$appInformation = "Currently Installed Version: ".plugin("version","/var/log/plugins/$pluginName");
			if ( plugin("version","/var/log/plugins/$pluginName") != plugin("version",$communityPaths['pluginTempDownload']) ) {
				copy($communityPaths['pluginTempDownload'],"/tmp/plugins/$pluginName");
				$appInformation .= " - <span class='ca_bold'>Install the update <a href='/Apps/Plugins' target='_parent'>HERE</a></span>";
			} else {
				$appInformation .= " - <font color='green'>Latest Version</font>";
			}
		}

		$appInformation .= Markdown($template['Changes']);
	} else {
		$appInformation = $template['Changes'];
		$appInformation = str_replace("\n","<br>",$appInformation);
		$appInformation = str_replace("[","<",$appInformation);
		$appInformation = str_replace("]",">",$appInformation);
	}
	$templateDescription .= "<div class='ca_center'><font size='4'><span class='ca_bold'>Change Log</span></div></font><br>$changeLogMessage$appInformation";
}

if ( is_array($template['trends']) ) {
	$chartLabel = array_fill(0,count($template['trends']),"");
	if ( is_array($template['downloadtrend']) ) {
		#get what the previous download value would have been based upon the trend
		$minDownload = intval(  ((100 - $template['trends'][0]) / 100)  * ($template['downloadtrend'][0]) );
		foreach ($template['downloadtrend'] as $download) {
			$totalDown[] = $download;
			$down[] = intval($download - $minDownload);
			$minDownload = $download;
		}
		$downloadLabel = array_fill(0,count($template['downloadtrend']),"");
	}
	$down = is_array($down) ? $down : array();
}

@unlink($communityPaths['pluginTempDownload']);
echo json_encode(array("description"=>$templateDescription,"trendData"=>$template['trends'],"trendLabel"=>$chartLabel,"downloadtrend"=>$down,"downloadLabel"=>$downloadLabel,"totaldown"=>$totalDown,"totaldownLabel"=>$downloadLabel));
?>