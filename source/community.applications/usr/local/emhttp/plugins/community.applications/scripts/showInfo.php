<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2018, Andrew Zawadzki #
#                                                             #
###############################################################
 
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/helpers.php");
require_once 'webGui/include/Markdown.php';

  $appNumber = urldecode($_GET['appNumber']);
  if ( ! $appNumber ) {
    $appNumber = $_POST['appNumber'];
  }
  if ( $appNumber == "CA" ) {
    $template['Changes'] = shell_exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin changes /tmp/plugins/community.applications.plg");
    $template['Plugin'] = true;
    $template['Support'] = "http://lime-technology.com/forum/index.php?topic=40262.0";
  } else {
    $file = readJsonFile($communityPaths['community-templates-info']);
    $templateIndex = searchArray($file,"ID",$appNumber);
  
    if ($templateIndex === false) {
      echo "An unidentified error has happened";
      exit;
    }
    $template = $file[$templateIndex];
    $donatelink = $template['DonateLink'];
    $donateimg  = $template['DonateImg'];
    $donatetext = $template['DonateText'];
  }

  if ( $template['Plugin'] )
  {
    $appInformation = Markdown($template['Changes']);
  } else {
    $appInformation = $template['Changes'];
    $appInformation = str_replace("\n","<br>",$appInformation);
    $appInformation = str_replace("[","<",$appInformation);
    $appInformation = str_replace("]",">",$appInformation);
  }
  $appInformation .= "<br><hr><br><center><table>";
  $appInformation .= $template['Support'] ? "<tr><td><a href='".$template['Support']."' target='_blank'><strong>Support Thread</strong></a></td><td></td>" : "";
  $appInformation .= $template['Project'] ? "<td><a href='".$template['Project']."' target='_blank'><strong>Project Page</strong></a></td>" : "";
  $appInformation .= $template['WebPageURL'] ? "<td></td><td><a href='".$template['WebPageURL']."' target='_blank'><strong>Web Page</strong></a></td>" : "";
  $appInformation .= "</tr></table><br><br>";
  if ( ($donatelink) && ($donateimg) ) {
    $appInformation .= "<br><center><font size='0'>$donatetext</font><br><a href='$donatelink' target='_blank'><img src='$donateimg' style='max-height:25px;'></a>";
    if ( $template['RepoName'] != "Squid's plugin Repository" ) {
      $appInformation .= "<br><font size='0'>The above link is set by the author of the template, not the author of Community Applications</font></center>";
    }
  }
  $appInformation = "<style>body { margin-left:20px;margin-right:20px }</style>$appInformation";
  echo "<div style='overflow:scroll; max-height:550px; height:550px; overflow-x:hidden; overflow-y:auto;'>";
  echo $appInformation;
  echo "</div>";
  
?>
