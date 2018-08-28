<div style='overflow:scroll; max-height:450px; height:450px; overflow-x:hidden; overflow-y:auto;font-size:12px;'>
<style>p { margin-left:20px;margin-right:20px; }
.popUpLink { color:cyan; }
</style>
<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2018, Andrew Zawadzki #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/dynamix/include/Wrappers.php");
require_once("/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php");
require_once("webGui/include/Markdown.php");

function getDownloads($downloads,$lowFlag=false) {
	$downloadCount = array("500000000","100000000","50000000","10000000","5000000","2500000","1000000","500000","250000","100000","50000","25000","10000","5000","1000","500","100");
	foreach ($downloadCount as $downloadtmp) {
		if ($downloads > $downloadtmp) {
			return "More than ".number_format($downloadtmp);
		}
	}
	return ($lowFlag) ? $downloads : "";
}

$fontAwesomeInstall = "<i class='appIcons fa fa-download' style='color:green;' aria-hidden='true'></i>";
$fontAwesomeEdit = "<i class='appIcons fa fa-edit' style='color:green;' aria-hidden='true'></i>";
$fontAwesomeGUI = "<i class='appIcons fa fa-globe' style='color:green;' aria-hidden='true'></i>";
$fontAwesomeUpdate = "<i class='appIcons fa fa-refresh' style='color:green;' aria-hidden='true'></i>";
$fontAwesomeDelete = "<i class='fa fa-window-close' aria-hidden='true' style='color:maroon; font-size:20px;cursor:pointer;'></i>";

$unRaidVars = parse_ini_file("/var/local/emhttp/var.ini");
$csrf_token = $unRaidVars['csrf_token'];
$tabMode = '_self';

$unRaidSettings = my_parse_ini_file($communityPaths['unRaidVersion']);
$unRaidVersion = $unRaidSettings['version'];

$dockerDaemon = "/var/run/dockerd.pid";
if ( is_file($dockerDaemon) && is_dir("/proc/".@file_get_contents($dockerDaemon)) ) {
	$communitySettings['dockerRunning'] = "true";
} else {
	$communitySettings['dockerSearch'] = "no";
	unset($communitySettings['dockerRunning']);
}
if ( $communitySettings['dockerRunning'] ) {
	$DockerTemplates = new DockerTemplates();
	$info = $DockerTemplates->getAllInfo();
	$DockerClient = new DockerClient();
	$dockerRunning = $DockerClient->getDockerContainers();
} else {
	$info = array();
	$dockerRunning = array();
}
$appNumber =  urldecode($_GET['appPath']);
$appName = urldecode($_GET['appName']);
if ( ! $appNumber ) {
	$appNumber = $_POST['appPath'];
	$color="<font color='white'>";
}
if ( $appNumber != "ca" && $appNumber != "ca_update" ) {
	# $appNumber is actually the path to the template.  It's pretty much always going to be the same even if the database is out of sync.
	$repos = readJsonFile($communityPaths['Repositories']);
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
			echo "Something really wrong happened<br>Reloading the Apps tab will probably fix the problem";
			return;
		}
		$template = $file[$index];
		$Displayed = false;
	}

	$ID = $template['ID'];
	$repoIndex = searchArray($repos,"name",$template['RepoName']);
	$webPageURL = $repos[$repoIndex]['web'];

	$donatelink = $template['DonateLink'];
	$donatetext = $template['DonateText'];

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
	}
	$template['Category'] = str_replace(":,",",",$template['Category']);
	$template['Category'] = str_replace(" ",",",$template['Category']);

	$categories = explode(",",$template['Category']);
	sort($categories);
	unset($template['Category']);
	foreach ($categories as $category) {
		if ( ! $category ) { continue; }
		$category = preg_replace('/(?<! )(?<!^)(?<![A-Z])[A-Z]/',' $0', $category);
		$category = rtrim(str_replace(": ",":",$category),":");
		$template['Category'] .= "<a class='popUpLink' style='cursor:pointer;' onclick='doSearch(false,&quot;$category&quot;);'>$category</a>, ";
	}
	$template['Category'] = rtrim($template['Category'],", ");
	$template['Icon'] = $template['Icon'] ? $template['Icon'] : "/plugins/dynamix.docker.manager/images/question.png";
	$template['Description'] = trim($template['Description']);

	if ( $color ) {
		$templateDescription .= "<center><font size='4'><strong>{$template['Name']}<br><br></strong></font></center><br><br><br>";
	}
	$templateDescription .= "<center><table><tr><td><figure style='margin-right:10px'><img id='icon' src='".$template['Icon']."' style='width:96px;height:96px;background-color:#C7C5CB;padding:3px;border-radius:10px 10px 10px 10px' onerror='this.src=&quot;/plugins/dynamix.docker.manager/images/question.png&quot;';>";
	$templateDescription .= ($template['Beta'] == "true") ? "<figcaption><font size='2' color='red'><center><strong>BETA</strong></center></font></figcaption>" : "";
	$templateDescription .= "</figure>";
	$templateDescription .= "</td><td></td><td><table>";
	$templateDescription .= "<tr><td>$color<strong>Author: </strong></td><td>$color".$template['Author']."</td></tr>";
	if ( ! $template['Plugin'] ) {
		$repository = explode(":",$template['Repository']);
		$official =  ( count(explode("/",$repository[0])) == 1 ) ? "_" : "r";
		$templateDescription .= "<tr><td>$color<strong>DockerHub: </strong></td><td><a class='popUpLink' href='https://hub.docker.com/$official/{$repository[0]}' target='_blank'>{$repository[0]}</a></td></tr>";
	}
	$templateDescription .= "<tr><td>$color<strong>Repository: </strong></td><td>$color";
	$repoSearch = explode("'",$template['RepoName']);
	$templateDescription .= $template['Forum'] ? "<a class='popUpLink' style='cursor:pointer;' onclick='authorSearch(&quot;{$repoSearch[0]}&quot;);'>".$template['RepoName']."</a>" : "{$template['RepoName']}";
	if ( $template['Profile'] ) {
		$profileDescription = $template['Plugin'] ? "Author" : "Maintainer";
		$templateDescription .= "&nbsp;&nbsp;&nbsp;&nbsp;<a class='popUpLink' href='{$template['Profile']}' target='_blank'>($profileDescription Profile)</a>";
	}
	$templateDescription .= "</td></tr>";
	$templateDescription .= ($template['Private'] == "true") ? "<tr><td></td><td><font color=red>Private Repository</font></td></tr>" : "";
	$templateDescription .= "<tr><td>$color<strong>Categories: </strong></td><td>$color".$template['Category']."</td></tr>";

	if ( ! $template['Plugin'] ) {
		if ( strtolower($template['Base']) == "unknown" ) {
			$template['Base'] = $template['BaseImage'];
		}
		if ( ! $template['Base'] ) {
			$template['Base'] = "Could Not Determine";
		}
		$templateDescription .= "<tr><td nowrap>$color<strong>Base OS: </strong></td><td>$color".$template['Base']."</td></tr>";
		$templateDescription .= $template['stars'] ? "<tr><td nowrap>$color<strong>DockerHub Stars: </strong></td><td>$color<i class='fa fa-star' style='font-size:15px;color:magenta;'></i> ".$template['stars']."</td></tr>" : "";
	}
	# In this day and age with auto-updating apps, NO ONE keeps up to date with the date updated.  Remove from docker containers to avoid confusion
	if ( $template['Date'] && $template['Plugin'] ) {
		$niceDate = date("F j, Y",$template['Date']);
		$templateDescription .= "<tr><td nowrap>$color<strong>Date Updated: </strong></td><td>$color$niceDate<br></td></tr>";
	}
	$templateDescription .= ($template['MinVer'] != "6.0")&&($template['MinVer'] != "6.1") ? "<tr><td nowrap>$color<b>Minimum OS:</strong></td><td>{$color}unRaid v".$template['MinVer']."</td></tr>" : "";
	$template['MaxVer'] = $template['MaxVer'] ?: $template['DeprecatedMaxVer'];
	$templateDescription .= $template['MaxVer'] ? "<tr><td nowrap>$color<strong>Max OS:</strong></td><td>{$color}unRaid v".$template['MaxVer']."</td></tr>" : "";
	$downloads = getDownloads($template['downloads']);
	if ($downloads) {
		$templateDescription .= "<tr><td>$color<strong>Downloads:</strong></td><td>$color$downloads</td></tr>";
	}
	$templateDescription .= $template['Licence'] ? "<tr><td>$color<strong>Licence:</strong></td><td>$color".$template['Licence']."</td></tr>" : "";

	$templateDescription .= "</table></td></tr></table>";

	$templateDescription .= "<center>";
	$templateDescription .= "<form method='get'>";
	$templateDescription .= "<input type='hidden' name='csrf_token' value='$csrf_token'>";

	if ( $Displayed && ! $template['NoInstall'] ) {
		if ( ! $template['Plugin'] ) {
			if ( $communitySettings['dockerRunning'] ) {
				if ( $selected ) {
					$templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' title='Click to reinstall the application using default values' href='Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='$tabMode'>$fontAwesomeInstall</a>&nbsp;&nbsp;";
					$templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' title='Click to edit the application values' href='Apps/UpdateContainer?xmlTemplate=edit:".addslashes($info[$name]['template'])."' target='$tabMode'>$fontAwesomeEdit</a>&nbsp;&nbsp;";
					if ( $info[$name]['url'] && $info[$name]['running'] ) {
						$templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' href='{$info[$name]['url']}' target='_blank' title='Click To Go To The App&#39;s UI'>$fontAwesomeGUI</a>&nbsp;&nbsp;";
					}
				} else {
					if ( $template['MyPath'] ) {
						$templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' title='Click to reinstall the application' href='Apps/AddContainer?xmlTemplate=user:".addslashes($template['MyPath'])."' target='$tabMode'>$fontAwesomeInstall</a>&nbsp;&nbsp;";
					} else {
						$install              = "&nbsp;&nbsp;<a class='ca_apptooltip' title='Click to install the application' href='Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='$tabMode'>$fontAwesomeInstall</a>&nbsp;&nbsp;";
						$templateDescription .= $template['BranchID'] ? "&nbsp;&nbsp;<a style='cursor:pointer' class='ca_apptooltip' title='Click to install the application' onclick='displayTags(&quot;$ID&quot;);'>$fontAwesomeInstall</a>&nbsp;&nbsp;" : $install;
					}
				}
			}
		} else {
			$pluginName = basename($template['PluginURL']);
			if ( file_exists("/var/log/plugins/$pluginName") ) {
				$pluginSettings = plugin("launch","/var/log/plugins/$pluginName");
				if ( $pluginSettings ) {
					$templateDescription .= "<a class='ca_apptooltip' title='Click to go to the plugin settings' href='$pluginSettings'>$fontAwesomeGUI</a>";
				}
			} else {
				$buttonTitle = $template['MyPath'] ? "Reinstall Plugin" : "Install Plugin";
				$templateDescription .= "&nbsp;&nbsp;<a style='cursor:pointer' class='ca_apptooltip' title='Click to install this plugin' onclick=installPlugin('".$template['PluginURL']."');>$fontAwesomeInstall</a>&nbsp;&nbsp;";
			}
			if ( checkPluginUpdate($template['PluginURL']) ) {
				$templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' title='Update Available.  Click To Install' onclick='installPLGupdate(&quot;".basename($template['PluginURL'])."&quot;,&quot;".$template['Name']."&quot;);' style='cursor:pointer'>$fontAwesomeUpdate</a>&nbsp;&nbsp;";
			}
		}
	}
	$templateDescription .= "</form>";
	$templateDescription .= "<br></center></center>";
	$templateDescription .= $template['Description'];
	$templateDescription .= $template['ModeratorComment'] ? "<br><br><b><font color='red'>Moderator Comments:</font></b> ".$template['ModeratorComment'] : "";
	$templateDescription .= "</p><br><center>";
	$templateDescription .= $template['Support'] ? "&nbsp;&nbsp;<a class='popUpLink' href='".$template['Support']."' target='_blank'>Support Thread</strong></a>&nbsp;&nbsp;" : "";
	$templateDescription .= $template['Project'] ? "&nbsp;&nbsp;<a class='popUpLink' href='".$template['Project']."' target='_blank'>Project Page</strong></a>&nbsp;&nbsp;" : "";
	$templateDescription .= $template['WebPageURL'] ? "&nbsp;&nbsp;<a class='popUpLink' href='".$template['WebPageURL']."' target='_blank'>Web Page</strong></a>&nbsp;&nbsp;" : "";

	if ( $donatelink ) {
		$templateDescription .= "<br><br><center><span class='donateLink'>$donatetext</span><br><a href='$donatelink' target='_blank'><img height='20px;' src='https://github.com/Squidly271/community.applications/raw/master/webImages/donate-button.png'></a>";
		if ( $template['RepoName'] != "Squid's plugin Repository" ) {
			$templateDescription .= "<br><font size='0'>The above link is set by the author of the template, not the author of Community Applications</font></center>";
		}
	}
	$templateDescription .= "</center>";
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
	if ( $template['Plugin'] && is_file("/var/log/plugins/$pluginName") && ! $template['Changes'] ) {
		$template['Changes'] = "This change log is from the already installed version and may not be up to date if an upgrade to the plugin is available\n\n".shell_exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin changes /var/log/plugins/$pluginName");
	}
	$changeLogMessage = "<center><font size='0'>Note: not all maintainers keep up to date on change logs</font></center><br>";
} else {
	$template['Changes'] = ($appNumber == "ca") ? plugin("changes","/var/log/plugins/community.applications.plg") : plugin("changes","/tmp/plugins/community.applications.plg");
	$template['Plugin'] = true;
}

if ( $template['Changes'] ) {
	if ( $appNumber != "ca" && $appNumber != "ca_update" ) {
		$templateDescription .= "</center><hr>";
	}
	if ( $template['Plugin'] ) {
		$appInformation = Markdown($template['Changes']);
	} else {
		$appInformation = $template['Changes'];
		$appInformation = str_replace("\n","<br>",$appInformation);
		$appInformation = str_replace("[","<",$appInformation);
		$appInformation = str_replace("]",">",$appInformation);
	}
	$templateDescription .= "<center><font size='4'><b>Change Log</b></center></font><br>$changeLogMessage$appInformation";
}
echo $templateDescription;
?>
</div>