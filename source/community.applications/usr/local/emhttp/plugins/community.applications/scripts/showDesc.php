<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2019, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

# Adjust some styles for the popupIcon
?>
<style>
p {margin-left:2rem;margin-right:2rem;}
.popUpLink {cursor:pointer;color:inherit;}
a.popUpLink {text-decoration:none;}
.popUpDeprecated {color:#FF8C2F;}
i.popupIcon {color:#626868;font-size:3.5rem;padding-left:1rem;width:4.8rem}
img.popupIcon {width:4.8rem;height:4.8rem;padding:0.3rem;border-radius:1rem 1rem 1rem 1rem;}
.display_beta {color:#FF8C2F;}
body {margin-left:1.5rem;margin-right:1.5rem;font-family:sans-serif;}
a.appIconsPopUp { text-decoration:none;color:inherit;}
</style>
<?PHP
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

$appNumber =  urldecode($_GET['appPath']);
$appName = urldecode($_GET['appName']);
if ( ! $appNumber ) {
  $appNumber = $_POST['appPath'];
  $color="<font color='white'>";
}

if ( $appNumber != "ca" && $appNumber != "ca_update" ) {
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
      echo "Something really wrong happened<br>Reloading the Apps tab will probably fix the problem";
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
  }
  $template['Category'] = categoryList($template['Category'],true);
  $template['Icon'] = $template['Icon'] ? $template['Icon'] : "/plugins/dynamix.docker.manager/images/question.png";
  $template['Description'] = trim($template['Description']);
  $template['ModeratorComment'] .= $template['CAComment'];

  if ( $color ) {
    $templateDescription .= "<center><font size=6rem;><strong>{$template['SortName']}</strong></font>";
    if ( $template['Beta'] ) {
      $templateDescription .= " <span class='display_beta'>BETA</span>";
    }
    $templateDescription .= "</center><br>";
  }
  $templateDescription .= "<div style='width:60px;height:60px;display:inline-block;position:absolute;'>";
  if ( $template['IconFA'] ) {
    $template['IconFA'] = $template['IconFA'] ?: $template['Icon'];
    $templateIcon = startsWith($template['IconFA'],"icon-") ? $template['IconFA'] : "fa fa-{$template['IconFA']}";
    $templateDescription .= "<i class='$templateIcon popupIcon' id='icon'></i>";
  } else {
    $templateDescription .= "<img class='popupIcon' id='icon' src='{$template['Icon']}'>";
  }
  $templateDescription .= "</div><div style='display:inline-block;margin-left:105px;'>";
  $templateDescription .= "<table>";
  $templateDescription .= "<tr><td>{$color}Author:</td><td>{$template['SortAuthor']}</a></td></tr>";
  if ( ! $template['Plugin'] ) {
    $repository = explode(":",$template['Repository']);
    $official =  ( count(explode("/",$repository[0])) == 1 ) ? "_" : "r";
    $templateDescription .= "<tr><td>{$color}DockerHub:</td><td>{$repository[0]}</td></tr>";
  }
  $templateDescription .= "<tr><td>{$color}Repository:</td><td>$color";
  $repoSearch = explode("'",$template['RepoName']);
  $templateDescription .= "{$template['RepoName']}</a>";
  if ( $template['Profile'] ) {
    $profileDescription = $template['Plugin'] ? "Author" : "Maintainer";
    $templateDescription .= "&nbsp;&nbsp;&nbsp;&nbsp;<a class='popUpLink' href='{$template['Profile']}' target='_blank'>($profileDescription Profile)</a>";
  }
  $templateDescription .= "</td></tr>";
  $templateDescription .= ($template['Private'] == "true") ? "<tr><td></td><td><font color=red>Private Repository</font></td></tr>" : "";
  $templateDescription .= "<tr><td>{$color}Categories:</td><td>$color".$template['Category']."</td></tr>";

  if ( ! $template['Plugin'] ) {
    if ( strtolower($template['Base']) == "unknown" || ! $template['Base']) {
      $template['Base'] = $template['BaseImage'];
    }
    if ( $template['Base'] ) {
      $templateDescription .= "<tr><td nowrap>{$color}Base OS:</td><td>$color".$template['Base']."</td></tr>";
    }
  }
  $templateDescription .= $template['stars'] ? "<tr><td nowrap>{$color}DockerHub Stars:</td><td>$color<i class='fa fa-star dockerHubStar'></i> ".$template['stars']."</td></tr>" : "";

  # In this day and age with auto-updating apps, NO ONE keeps up to date with the date updated.  Remove from docker containers to avoid confusion
  if ( $template['Date'] && $template['Plugin'] ) {
    $niceDate = date("F j, Y",$template['Date']);
  $templateDescription .= "<tr><td nowrap>{$color}Date Updated:</td><td>$color$niceDate<br></td></tr>";
  }
  $unraidVersion = parse_ini_file($communityPaths['unRaidVersion']);
  if ( version_compare($unRaidVersion['version'],$template['MinVer'],">") ) {
    $templateDescription .= ($template['MinVer'] != "6.0")&&($template['MinVer'] != "6.1") ? "<tr><td nowrap>{$color}Minimum OS:</td><td>{$color}unRaid v".$template['MinVer']."</td></tr>" : "";
  }
  $template['MaxVer'] = $template['MaxVer'] ?: $template['DeprecatedMaxVer'];
  $templateDescription .= $template['MaxVer'] ? "<tr><td nowrap>{$color}Max OS:</td><td>{$color}unRaid v".$template['MaxVer']."</td></tr>" : "";
  $downloads = getDownloads($template['downloads']);
  if ($downloads) {
    $templateDescription .= "<tr><td>{$color}Downloads:</td><td>$color$downloads</td></tr>";
  }
  $templateDescription .= $template['Licence'] ? "<tr><td>{$color}Licence:</td><td>$color".$template['Licence']."</td></tr>" : "";
  if ( $template['trending'] ) {
    $templateDescription .= "<tr><td>{$color}Monthly Trend:</td><td>$color+{$template['trending']}%";
    if ( is_array($template['trends']) && (count($template['trends']) > 1) ) {
      $templateDescription .= (end($template['trends']) > $template['trends'][count($template['trends'])-2]) ? " <i class='fa fa-arrow-up'></i>" : " <i class='fa fa-arrow-down'></i>";
    }
    $template['description'] .= "</td></tr>";
  }
  $templateDescription .= "</table></div>";
  $templateDescription .= "<center><span class='popUpDeprecated'>";
  if ($template['Blacklist']) {
    $templateDescription .= "This application / template has been blacklisted<br>";
  }
  if ($template['Deprecated']) {
    $templateDescription .= "This application / template has been deprecated<br>";
  }
  if ( !$template['Compatible'] ) {
    $templateDescription .= "This application is not compatible with your version of unRaid<br>";
  }
  $templateDescription .= "</span></center><hr>";

  if ( $Displayed && ! $template['NoInstall'] ) {
    if ( ! $template['Plugin'] ) {
      if ( $communitySettings['dockerRunning'] ) {
        if ( $selected ) {
          $installLine .= "<a class='ca_apptooltip appIconsPopUp ca_fa-install' href='/Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='$tabMode'> Reinstall</a>";
          $installLine .= "<a class='ca_apptooltip appIconsPopUp ca_fa-edit' title='Click to edit the application values' href='/Apps/UpdateContainer?xmlTemplate=edit:".addslashes($info[$name]['template'])."' target='$tabMode'> Edit</a>";
          if ( $info[$name]['url'] && $info[$name]['running'] ) {
            $installLine .= "<a class='ca_apptooltip appIconsPopUp ca_fa-globe' href='{$info[$name]['url']}' target='_blank' title='Click To Go To The App&#39;s UI'> WebUI</a>";
          }
        } else {
          if ( $template['MyPath'] ) {
            $installLine .= "<a class='ca_apptooltip appIconsPopUp ca_fa-install' title='Click to reinstall the application' href='/Apps/AddContainer?xmlTemplate=user:".addslashes($template['MyPath'])."' target='$tabMode'> Reinstall</a>";
          } else {
            $install              = "<a class='ca_apptooltip appIconsPopUp ca_fa-install' title='Click to install the application' href='/Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='$tabMode'> Install</a>";
            $installLine .= $template['BranchID'] ? "<a style='cursor:pointer' class='ca_apptooltip appIconsPopUp ca_fa-install' title='Click to install the application' onclick='displayTags(&quot;$ID&quot;);'> Install</a>" : $install;
          }
        }
      }
    } else {
      $pluginName = basename($template['PluginURL']);
      if ( file_exists("/var/log/plugins/$pluginName") ) {
        $pluginSettings = plugin("launch","/var/log/plugins/$pluginName");
        if ( $pluginSettings ) {
          $installLine .= "<a class='ca_apptooltip appIconsPopUp ca_fa-globe' title='Click to go to the plugin settings' href='/Apps/$pluginSettings' target='$tabMode'> Settings</a>";
        }
      } else {
        $buttonTitle = $template['MyPath'] ? "Reinstall Plugin" : "Install Plugin";
        $installLine .= "<a style='cursor:pointer' class='ca_apptooltip appIconsPopUp ca_fa-install' title='Click to install this plugin' onclick=installPlugin('".$template['PluginURL']."');> Install</a>";
      }
    }
  }
  if ( $template['Support'] || $template['Project'] ) {
    $installLine .= "<span style='float:right;'>";
    $installLine .= $template['Support'] ? "<a class='appIconsPopUp ca_fa-support' href='".$template['Support']."' target='_blank'> Support</strong></a>&nbsp;&nbsp;" : "";
    $installLine .= $template['Project'] ? "<a class='appIconsPopUp ca_fa-project' href='".$template['Project']."' target='_blank'> Project</strong></a>&nbsp;&nbsp;" : "";
    $installLine .= "</span>";
  }
  if ( $installLine ) {
    $templateDescription .= "$installLine<br><hr>";
  }
  $templateDescription .= $template['Description'];
  $templateDescription .= $template['ModeratorComment'] ? "<br><br><b><font color='red'>Moderator Comments:</font></b> ".$template['ModeratorComment'] : "";
  $templateDescription .= "</p><br><center>";

  if ( $donatelink ) {
    $templateDescription .= "<span style='float:right;text-align:right;'>$donatetext&nbsp;&nbsp;<a class='popup-donate donateLink' href='$donatelink' target='_blank'>Donate</a></span><br>";
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
?>
<div style='height:20px;'></div>
<?=$templateDescription?>
<script src='<?autov("/plugins/dynamix/javascript/dynamix.js")?>'></script>
<link type="text/css" rel="stylesheet" href='<?autov("/webGui/styles/font-awesome.css")?>'>
<link type="text/css" rel="stylesheet" href='<?autov("/plugins/community.applications/skins/Narrow/css.php")?>'>
<script>
  $('img').each(function() { // This handles any http images embedded in changelogs
    if ( $(this).hasClass('displayIcon') ) { // ie: don't change any images on the main display
      return;
    }
    var origSource = $(this).attr("src");
    if ( origSource.startsWith("http://") ) {
      var newSource = origSource.replace("http://","https://");
      $(this).attr("src",newSource);
    }
  });
  $('img').on("error",function() {
    var origSource = $(this).attr('src');
    var newSource = origSource.replace("https://","http://");
    if ( document.referrer.startsWith("https") && "<?=$communitySettings['secureImage']?>" == "secure" ) {
      $(this).attr('src',"/plugins/dynamix.docker.manager/images/question.png");
    } else {
      $(this).attr('src',newSource);
      $(this).on("error",function() {
        $(this).attr('src',"/plugins/dynamix.docker.manager/images/question.png");
      });
    }
  });
</script>