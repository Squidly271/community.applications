<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2017, Andrew Zawadzki #
#                                                             #
###############################################################
 
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/dynamix/include/Wrappers.php");
require_once("/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php");
require_once 'webGui/include/Markdown.php';


$unRaidVars = parse_ini_file("/var/local/emhttp/var.ini");
$csrf_token = $unRaidVars['csrf_token'];
$communitySettings = parse_plugin_cfg("community.applications");
$tabMode = $communitySettings['newWindow'];
if ( is_dir("/var/lib/docker/containers") ) {
  $communitySettings['dockerRunning'] = true;
} else {
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
$appNumber =  urldecode($_GET['appNumber']);
$appName = urldecode($_GET['appName']);
if ( ! $appNumber ) {
  $appNumber = $_POST['appNumber'];
  $color="<font color='white'>";
}

$repos = readJsonFile($communityPaths['Repositories']);
if ( ! $repos ) {
  $repos = array();
}
$displayed = readJsonFile($communityPaths['community-templates-displayed']);
foreach ($displayed as $file) {
  $index = searchArray($file,"ID",$appNumber);
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
  $index = searchArray($file,"ID",$appNumber);
  if ( $index === false ) {
    echo "Something really wrong happened";
    return;
  }
  $template = $file[$index];
  $Displayed = false;
}

$ID = $appNumber;
$repoIndex = searchArray($repos,"name",$template['RepoName']);
$webPageURL = $repos[$repoIndex]['web'];

$donatelink = $template['DonateLink'];
$donateimg = $template['DonateImg'];
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
$template['Category'] = rtrim(str_replace(":,",",",implode(", ",explode(" ",$template['Category']))),": ,");
$template['Icon'] = $template['Icon'] ? $template['Icon'] : "/plugins/community.applications/images/question.png";
$template['Description'] = trim($template['Description']);

$templateDescription .= "<style>p { margin-left:20px;margin-right:20px }</style>";
if ( $color ) {
  $templateDescription .= "<center><font size='4'><strong>{$template['Name']}</strong></font></center><br><br><br>";
}
$templateDescription .= "<center><table><tr><td><figure style='margin:0px'><img id='icon' src='".$template['Icon']."' style='width:96px;height:96px' onerror='this.src=&quot;/plugins/community.applications/images/question.png&quot;';>";
$templateDescription .= ($template['Beta'] == "true") ? "<figcaption><font size='1' color='red'><center><strong>(beta)</strong></center></font></figcaption>" : "";
$templateDescription .= "</figure>";
$templateDescription .= "</td><td></td><td><table>";
$templateDescription .= "<tr><td>$color<strong>Author: </strong></td><td>$color".$template['Author']."</td></tr>";
if ( ! $template['Plugin'] ) {
	$repository = explode(":",$template['Repository']);
	$official =  ( count(explode("/",$repository[0])) == 1 ) ? "_" : "r";
	$templateDescription .= "<tr><td>$color<strong>DockerHub: </strong></td><td><a href='https://hub.docker.com/$official/{$repository[0]}' target='_blank'>{$repository[0]}</a></td></tr>";
}
$templateDescription .= "<tr><td>$color<strong>Repository: </strong></td><td>$color";
$repoSearch = explode("'",$template['RepoName']);
$templateDescription .= $template['Forum'] ? "<b><a style='cursor:pointer;' onclick='authorSearch(&quot;{$repoSearch[0]}&quot;);'>".$template['RepoName']."</a></b>" : "<b>{$template['RepoName']}</b>";
if ( $template['Profile'] ) {
  $profileDescription = $template['Plugin'] ? "Author" : "Maintainer";
  $templateDescription .= "&nbsp;&nbsp;&nbsp;&nbsp;<b><a href='{$template['Profile']}' target='_blank'>($profileDescription Profile)</a></b>";
}
$templateDescription .= "</td></tr>";
$templateDescription .= ($template['Private'] == "true") ? "<tr><td></td><td><font color=red>Private Repository</font></td></tr>" : "";
$templateDescription .= "<tr><td>$color<strong>Categories: </strong></td><td>$color".$template['Category']."</td></tr>";

$template['Base'] = $template['Plugin'] ? "$color<font color='red'>unRaid Plugin</font>" : $template['Base'];

if ( strtolower($template['Base']) == "unknown" ) {
  $template['Base'] = $template['BaseImage'];
}
if ( ! $template['Base'] ) {
  $template['Base'] = "Could Not Determine";
}

$templateDescription .= "<tr><td nowrap>$color<strong>Base OS: </strong></td><td>$color".$template['Base']."</td></tr>";
$templateDescription .= $template['stars'] ? "<tr><td nowrap>$color<strong>DockerHub Stars: </strong></td><td>$color<i class='fa fa-star' style='font-size:15px;color:magenta;'></i> ".$template['stars']."</td></tr>" : "";

# In this day and age with auto-updating apps, NO ONE keeps up to date with the date updated.  Remove from docker containers to avoid confusion
if ( $template['Date'] && $template['Plugin'] ) {
  $niceDate = date("F j, Y",$template['Date']);
  $templateDescription .= "<tr><td nowrap>$color<strong>Date Updated: </strong><br>See below</td><td>$color$niceDate<br></td></tr>";
}
$templateDescription .= $template['MinVer'] ? "<tr><td nowrap>$color<b>Minimum OS:</strong></td><td>{$color}unRaid v".$template['MinVer']."</td></tr>" : "";
$templateDescription .= $template['MaxVer'] ? "<tr><td nowrap>$color<strong>Max OS:</strong></td><td>{$color}unRaid v".$template['MaxVer']."</td></tr>" : "";
if ($template['downloads']) {
	$templateDescription .= "<tr><td>$color<strong>Downloads:</strong></td><td>$color{$template['downloads']}</td></tr>";
}
$templateDescription .= $template['Licence'] ? "<tr><td>$color<strong>Licence:</strong></td><td>$color".$template['Licence']."</td></tr>" : "";
  
$templateDescription .= "</table></td></tr></table>";

$templateDescription .= "<center>";
$templateDescription .= "<form method='get'>";
$templateDescription .= "<input type='hidden' name='csrf_token' value='$csrf_token'>";

if ( $Displayed ) {
  if ( ! $template['Plugin'] ) {
    if ( $communitySettings['dockerRunning'] ) {
      if ( $selected ) {
        $templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' title='Click to reinstall the application using default values' href='AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='$tabMode'><img src='/plugins/community.applications/images/install.png' height='40px'></a>&nbsp;&nbsp;";
        $templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' title='Click to edit the application values' href='UpdateContainer?xmlTemplate=edit:".addslashes($info[$name]['template'])."' target='$tabMode'><img src='/plugins/community.applications/images/edit.png' height='40px'></a>&nbsp;&nbsp;";
        if ( $info[$name]['url'] && $info[$name]['running'] ) {
          $templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' href='{$info[$name]['url']}' target='_blank' title='Click To Go To The App&#39;s UI'><img src='/plugins/community.applications/images/WebPage.png' height='40px'></a>&nbsp;&nbsp;";
        }
      } else {
        if ( $template['MyPath'] ) {
          $templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' title='Click to reinstall the application' href='AddContainer?xmlTemplate=user:".addslashes($template['MyPath'])."' target='$tabMode'><img src='/plugins/community.applications/images/install.png' height='40px'></a>&nbsp;&nbsp;";
        } else {
          $install              = "&nbsp;&nbsp;<a class='ca_apptooltip' title='Click to install the application' href='AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='$tabMode'><img src='/plugins/community.applications/images/install.png' height='40px'></a>&nbsp;&nbsp;";
          $templateDescription .= $template['BranchID'] ? "&nbsp;&nbsp;<a style='cursor:pointer' class='ca_apptooltip' title='Click to install the application' onclick='displayTags(&quot;$ID&quot;);'><img src='/plugins/community.applications/images/install.png' height='40px'></a>&nbsp;&nbsp;" : $install;
        }
      } 
    }  
  } else {
    $pluginName = basename($template['PluginURL']);
    if ( file_exists("/var/log/plugins/$pluginName") ) {
      $pluginSettings = isset($template['CAlink']) ? $template['CAlink'] : getPluginLaunch($pluginName);
      if ( $pluginSettings ) {
        $templateDescription .= "<a class='ca_apptooltip' title='Click to go to the plugin settings' href='$pluginSettings'><img src='/plugins/community.applications/images/WebPage.png' height='40px'></a>";
      }
    } else {
      $buttonTitle = $template['MyPath'] ? "Reinstall Plugin" : "Install Plugin";
      $templateDescription .= "&nbsp;&nbsp;<a style='cursor:pointer' class='ca_apptooltip' title='Click to install this plugin' onclick=installPlugin('".$template['PluginURL']."');><img src='/plugins/community.applications/images/install.png' height='40px'></a>&nbsp;&nbsp;";
    }
    if ( checkPluginUpdate($template['PluginURL']) ) {
      $templateDescription .= "&nbsp;&nbsp;<a class='ca_apptooltip' title='Update Available.  Click To Install' onclick='installPLGupdate(&quot;".basename($template['PluginURL'])."&quot;,&quot;".$template['Name']."&quot;);' style='cursor:pointer'><img src='/plugins/community.applications/images/update.png' height='40px'></a>&nbsp;&nbsp;";
    }
  }
}
$templateDescription .= "</form>";
$templateDescription .= "<br></center></center>";
$templateDescription .= $template['Description'];
$templateDescription .= $template['ModeratorComment'] ? "<br><br><b><font color='red'>Moderator Comments:</font></b> ".$template['ModeratorComment'] : "";
$templateDescription .= "</p><br><center>";
$templateDescription .= $template['Support'] ? "&nbsp;&nbsp;<a href='".$template['Support']."' target='_blank'><strong>Support Thread</strong></a>&nbsp;&nbsp;" : "";
$templateDescription .= $template['Project'] ? "&nbsp;&nbsp;<a href='".$template['Project']."' target='_blank'><strong>Project Page</strong></a>&nbsp;&nbsp;" : "";
$templateDescription .= $template['WebPageURL'] ? "&nbsp;&nbsp;<a href='".$template['WebPageURL']."' target='_blank'><strong>Web Page</strong></a>&nbsp;&nbsp;" : "";

if ( ($donatelink) && ($donateimg) ) {
  $templateDescription .= "<br><br><center><font size='0'>$donatetext</font><br><a href='$donatelink' target='_blank'><img src='$donateimg' style='max-height:25px;'></a>";
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
if ( $template['Changes'] ) {
  if ( $template['Plugin'] ) {
    $appInformation = Markdown($template['Changes']);
  } else {
    $appInformation = $template['Changes'];
    $appInformation = str_replace("\n","<br>",$appInformation);
    $appInformation = str_replace("[","<",$appInformation);
    $appInformation = str_replace("]",">",$appInformation);
  }
  $templateDescription .= "</center><hr><font size='2'><b>Change Log</b> <font size='0'>Note: not all maintainers keep up to date on change logs</font><br><br>$appInformation";
}
echo "<div style='overflow:scroll; max-height:450px; height:450px; overflow-x:hidden; overflow-y:auto;'>";
echo $templateDescription;
echo "</div>";
?>
