<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2019, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/dynamix/include/Wrappers.php");
require_once("/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/xmlHelpers.php");

$unRaidSettings = parse_ini_file($communityPaths['unRaidVersion']);

################################################################################
# Set up any default settings (when not explicitely set by the settings module #
################################################################################

$communitySettings = parse_plugin_cfg("community.applications");
$communitySettings['skin'] = "Narrow";
$communityPaths['defaultSkin'] = "/usr/local/emhttp/plugins/community.applications/skins/{$communitySettings['skin']}/skin.json";
$skinSettings = readJsonFile($communityPaths['defaultSkin']);
$communityPaths['defaultSkinPHP'] = $skinSettings['detail']['php'];

require_once($communityPaths['defaultSkinPHP']);

$communitySettings['maxPerPage']    = isMobile() ? 10 : 25;
$communitySettings['unRaidVersion'] = $unRaidSettings['version'];
$communitySettings['timeNew']       = "-10 years";

if ( $communitySettings['favourite'] != "None" ) {
  $officialRepo = str_replace("*","'",$communitySettings['favourite']);
  $separateOfficial = true;
}
$DockerClient = new DockerClient();
$DockerTemplates = new DockerTemplates();

if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
  $communitySettings['dockerRunning'] = true;
  $dockerRunning = $DockerClient->getDockerContainers();
} else {
  $communitySettings['dockerSearch'] = "no";
  unset($communitySettings['dockerRunning']);
  $dockerRunning = array();
}

@mkdir($communityPaths['tempFiles'],0777,true);

if ( !is_dir($communityPaths['templates-community']) ) {
  @mkdir($communityPaths['templates-community'],0777,true);
  @unlink($communityPaths['community-templates-info']);
}


############################################
##                                        ##
## BEGIN MAIN ROUTINES CALLED BY THE HTML ##
##                                        ##
############################################

$communitySettings['fontSize'] = getPost("fontSize",12.5);

switch ($_POST['action']) {

######################################################################################
# get_content - get the results from templates according to categories, filters, etc #
######################################################################################
case 'get_content':
  $filter      = getPost("filter",false);
  $category    = "/".getPost("category",false)."/i";
  $newApp      = filter_var(getPost("newApp",false),FILTER_VALIDATE_BOOLEAN);
  $sortOrder   = getSortOrder(getPostArray("sortOrder"));
  $windowWidth = getPost("windowWidth",false);
  getMaxColumns($windowWidth);
  $communitySettings['startup'] = getPost("startupDisplay",false);

  switch ($category) {
    case "/PRIVATE/i":
      $category = false;
      $displayPrivates = true;
      break;
    case "/DEPRECATED/i":
      $category = false;
      $displayDeprecated = true;
      $noInstallComment = "Deprecated Applications are able to still be installed if you have previously had them installed. New installations of these applications are blocked unless you enable Display Deprecated Applications within CA's General Settings<br><br>";
      break;
    case "/BLACKLIST/i":
      $category = false;
      $displayBlacklisted = true;
      $noInstallComment = "The following applications are blacklisted.  CA will never allow you to install or reinstall these applications<br><br>";
      break;
    case "/INCOMPATIBLE/i":
      $displayIncompatible = true;
      $noInstallComment = "<b>While highly not recommended to do</b>, incompatible applications can be installed by enabling Display Incompatible Applications within CA's General Settings<br><br>";
      break;
  }
  $newAppTime = strtotime($communitySettings['timeNew']);

  if ( file_exists($communityPaths['addConverted']) ) {
    @unlink($communityPaths['community-templates-info']);
    @unlink($communityPaths['addConverted']);
    getConvertedTemplates();
  }

  $file = readJsonFile($communityPaths['community-templates-info']);
  if ( empty($file)) break;

  if ( $category === "/NONE/i" ) {
    $displayApplications = array();
    if ( count($file) > 200) {
      $appsOfDay = appOfDay($file,$startupMsg,$startupMsg2);

      $displayApplications['community'] = array();
      for ($i=0;$i<$communitySettings['maxPerPage'];$i++) {
        if ( ! $appsOfDay[$i]) continue;
        $file[$appsOfDay[$i]]['NewApp'] = ($communitySettings['startup'] != "random");
        $displayApplications['community'][] = $file[$appsOfDay[$i]];
      }
      if ( $displayApplications['community'] ) {
        writeJsonFile($communityPaths['community-templates-displayed'],$displayApplications);
        echo "<script>$('#templateSortButtons,#sortButtons').hide();enableIcon('#sortIcon',false);</script>";

/*        echo "<br><center><span class='startupMessage'>$startupMsg</span></center>";
        if ( $startupMsg2 ) {
          echo "<center><span class='startupMessage2'>$startupMsg2</span></center><br>";
        } */
        $sortOrder['sortBy'] = "noSort";
        echo my_display_apps($displayApplications['community'],"1",$runningDockers,$imagesDocker);
        break;
      } else {
        echo "<script>$('#templateSortButtons,#sortButtons').hide();enableIcon('#sortIcon',false);</script>";
        echo "<br><center><font size='4' color='purple'><b>An error occurred.  Could not find any Random Apps of the day</b></font><br><br>";
        break;
      }
    }
  }
  $display             = array();
  $official            = array();

  $communitySettingsBackup = $communitySettings;
  if ( $displayBlacklisted || $displayDeprecated || $displayIncompatible || $displayPrivates || $displayNoSupport) {
    $communitySettings['separateInstalled'] = false; # show installed containers in the "special" categories
  }
  foreach ($file as $template) {
    $template['NoInstall'] = $noInstallComment;

    if ( $displayBlacklisted ) {
      if ( $template['Blacklist'] ) {
        $display[] = $template;
        continue;
      } else {
        continue;
      }
    }
    if ( ($communitySettings['hideDeprecated'] == "true") && ($template['Deprecated'] && ! $displayDeprecated) ) continue;
    if ( $displayDeprecated && ! $template['Deprecated'] ) continue;
    if ( ! $template['Displayable'] ) continue;
    if ( $communitySettings['hideIncompatible'] == "true" && ! $template['Compatible'] && ! $displayIncompatible) continue;
    if ( $template['Blacklist'] ) continue;
    if ( ! $template['Compatible'] && $displayIncompatible ) {
      $display[] = $template;
      continue;
    }

    $name = $template['Name'];

# Skip over installed containers

    if ( $newApp != "true" && $filter == "" && $communitySettings['separateInstalled'] == "true" && ! $displayPrivates) {
      if ( $template['Plugin'] ) {
        $pluginName = basename($template['PluginURL']);

        if ( file_exists("/var/log/plugins/$pluginName") ) continue;
      } else {
        $selected = false;
        foreach ($dockerRunning as $installedDocker) {
          $installedImage = $installedDocker['Image'];
          $installedName = $installedDocker['Name'];

          if ( startsWith($installedImage,$template['Repository']) ) {
            if ( $installedName == $template['Name'] ) {
              $selected = true;
              break;
            }
          }
        }
        if ( $selected ) continue;
      }
    }
    if ( $template['Plugin'] && file_exists("/var/log/plugins/".basename($template['PluginURL'])) ) {
      $template['MyPath'] = $template['PluginURL'];
    }

    if ( ($newApp) && ($template['Date'] < $newAppTime) ) continue;
    $template['NewApp'] = $newApp;

    if ( $category && ! preg_match($category,$template['Category'])) continue;
    if ( $displayPrivates && ! $template['Private'] ) continue;

    if ($filter) {
      if ( filterMatch($filter,array($template['Name'],$template['Author'],$template['Description'],$template['RepoName'],$template['Category'])) ) {
        $template['Description'] = highlight($filter, $template['Description']);
        $template['Author'] = highlight($filter, $template['Author']);
        $template['Name'] = highlight($filter, $template['Name']);
      } else continue;
    }

    if ( $separateOfficial ) {
      if ( $template['RepoName'] == $officialRepo ) {
        $official[] = $template;
      } else {
        $display[] = $template;
      }
    } else {
      $display[] = $template;
    }
  }
  $communitySettings = $communitySettingsBackup; # restore backup settings
  $displayApplications['official']  = $official;
  $displayApplications['community'] = $display;

  writeJsonFile($communityPaths['community-templates-displayed'],$displayApplications);
  display_apps();
  break;

########################################################
# force_update -> forces an update of the applications #
########################################################
case 'force_update':
  $lastUpdatedOld = readJsonFile($communityPaths['lastUpdated-old']);

  @unlink($communityPaths['lastUpdated']);
  $latestUpdate = download_json($communityPaths['application-feed-last-updated'],$communityPaths['lastUpdated']);
  if ( ! $latestUpdate['last_updated_timestamp'] ) {
    $latestUpdate = download_json($communityPaths['application-feed-last-updatedBackup'],$communityPaths['lastUpdated']);
  }

  if ( ! $latestUpdate['last_updated_timestamp'] ) {
    $latestUpdate['last_updated_timestamp'] = INF;
    $badDownload = true;
    @unlink($communityPaths['lastUpdated']);
  }

  if ( $latestUpdate['last_updated_timestamp'] > $lastUpdatedOld['last_updated_timestamp'] ) {
    if ( $latestUpdate['last_updated_timestamp'] != INF ) {
      copy($communityPaths['lastUpdated'],$communityPaths['lastUpdated-old']);
    }
    if ( ! $badDownload ) {
      @unlink($communityPaths['community-templates-info']);
    }
  }

  if (!file_exists($communityPaths['community-templates-info'])) {
    $updatedSyncFlag = true;
    DownloadApplicationFeed();
    if (!file_exists($communityPaths['community-templates-info'])) {
      $tmpfile = randomFile();
      download_url($communityPaths['PublicServiceAnnouncement'],$tmpfile,false,10);
      $publicServiceAnnouncement = trim(@file_get_contents($tmpfile));
      @unlink($tmpfile);
      echo "<script>$('.startupButton').hide();</script>";
      echo "<center><font size='4'><strong>Download of appfeed failed.</strong></font><font size='3'><br><br>Community Applications <em><b>requires</b></em> your server to have internet access.  The most common cause of this failure is a failure to resolve DNS addresses.  You can try and reset your modem and router to fix this issue, or set static DNS addresses (Settings - Network Settings) of <b>208.67.222.222 and 208.67.220.220</b> and try again.<br><br>Alternatively, there is also a chance that the server handling the application feed is temporarily down.  You can check the server status by clicking <a href='https://www.githubstatus.com/' target='_blank'>HERE</a>";
      $tempFile = @file_get_contents($communityPaths['appFeedDownloadError']);
      $downloaded = @file_get_contents($tempFile);
      if (strlen($downloaded) > 100) {
        echo "<font size='2' color='red'><br><br>It *appears* that a partial download of the application feed happened (or is malformed), therefore it is probable that the application feed is temporarily down.  Please try again later)</font>";
      }
      echo "<center>Last JSON error Recorded: ";
      $jsonDecode = json_decode($downloaded,true);
      echo "JSON Error: ".jsonError(json_last_error());
      if ( $publicServiceAnnouncement ) {
        echo "<br><font size='3' color='purple'>$publicServiceAnnouncement</font>";
      }
      echo "</center>";
      echo "<script>$('.ca_stats').hide();</script>";
      @unlink($communityPaths['appFeedDownloadError']);
      @unlink($communityPaths['community-templates-info']);
      break;
    }
  }
  getConvertedTemplates();
  moderateTemplates();
  echo "ok";
  break;

####################################################################################
# display_content - displays the templates according to view mode, sort order, etc #
####################################################################################
case 'display_content':
  $sortOrder = getSortOrder(getPostArray('sortOrder'));
  $windowWidth = getPost("windowWidth",false);
  $pageNumber = getPost("pageNumber","1");
  $selectedApps = json_decode(getPost("selected",false),true);
  getMaxColumns($windowWidth);
  $communitySettings['fontSize'] = getPost("fontSize",false);

  if ( file_exists($communityPaths['community-templates-displayed']) ) {
    display_apps($pageNumber,$selectedApps);
  }
  break;

########################################################################
# change_docker_view - called when the view mode for dockerHub changes #
########################################################################
case 'change_docker_view':
  $sortOrder = getSortOrder(getPostArray('sortOrder'));
  if ( ! file_exists($communityPaths['dockerSearchResults']) ) {
    break;
  }
  $file = readJsonFile($communityPaths['dockerSearchResults']);
  $pageNumber = $file['page_number'];
  displaySearchResults($pageNumber);
  break;

#######################################################################
# convert_docker - called when system adds a container from dockerHub #
#######################################################################
case 'convert_docker':
  $dockerID = getPost("ID","");

  $file = readJsonFile($communityPaths['dockerSearchResults']);
  $docker = $file['results'][$dockerID];
  $docker['Description'] = str_replace("&", "&amp;", $docker['Description']);
  @unlink($communityPaths['Dockerfile']);

  if ( ! $docker['Official'] ) {
    $dockerURL = $docker['DockerHub']."/Dockerfile/";
    download_url($dockerURL,$communityPaths['dockerfilePage']);

    $dockerPage = file_get_contents($communityPaths['dockerfilePage']);
    $regex = '/".*?"|\'.*?\'/';  #regex that finds all quoted items
    preg_match_all($regex,$dockerPage,$quoted);
    @unlink($communityPaths['Dockerfile']);
    foreach ($quoted[0] as $testline) {
      $testline = str_replace('\u002F',"/",$testline);
      $testline = str_replace('"',"",$testline);
#can only download from specific place, as if we search for and download the entire contents, it *could* be multi-megabyte/gigabyte download
      if ( validURL($testline) ) {
        $tst = str_replace("github.com","raw.githubusercontent.com",$testline);
        if (strpos($tst,"raw.githubusercontent.com")) {
          if ( $alreadyAttempted[$testline] ) {
            continue;
          }
          $alreadyAttempted[$testline] = true;
//          logger("Community Applications: Attempting download of dockerfile");
          download_url("$tst/master/Dockerfile",$communityPaths['Dockerfile']);
          if ( is_file($communityPaths['Dockerfile']) ) {
//            logger("Community Applications: Download succeeded");
            break;
          }
        }
      }
    }
    if ( ! is_file($communityPaths['Dockerfile']) ) {  #couldn't easily locate dockerfile.  Revert to scraping webpage
//      logger("Community Applications: Could not locate dockerfile.  Falling back to scraping web-page");
      $mystring = $dockerPage;
      $thisstring = strstr($mystring,'"dockerfile":"');
      $thisstring = trim($thisstring);
      $thisstring = explode("}",$thisstring);
      $thisstring = explode(":",$thisstring[0]);
      unset($thisstring[0]);
      $teststring = implode(":",$thisstring);
      $teststring = str_replace('\n',"\n",$teststring);
      $teststring = str_replace("\u002F", "/", $teststring);
      $teststring = trim($teststring,'"');
      $teststring = stripslashes($teststring);
      $teststring = substr($teststring,2);
      $docker['Description'] = str_replace("&", "&amp;", $docker['Description']);
      $teststring = str_replace("\\\n"," ",$teststring);
      file_put_contents($communityPaths['Dockerfile'],$teststring);
    }
    $dockerfileContents = @file_get_contents($communityPaths['Dockerfile']);
    $dockerfileContents = $dockerfileContents ?: "";
    $dockerfileContents = str_replace("\\\n","*",$dockerfileContents); # get rid of readability newlines
    $dockerFile = explode("\n",$dockerfileContents);

    $volumes = array();
    $ports = array();
    $env = array();

    foreach ( $dockerFile as $dockerLine ) {
      $dockerCompare = trim(strtoupper($dockerLine));
      $dockerCmp = strpos($dockerCompare, "VOLUME");
      if ( $dockerCmp === 0 ) {
        $dockerLine = str_replace(array("*","'","[","]",",",'"')," ",$dockerLine);
        $volumes[] = $dockerLine;
      }

      $dockerCmp = strpos($dockerCompare, "EXPOSE");
      if ( $dockerCmp === 0 ) {
        $dockerLine = str_replace(array("*","'","[","]",",",'"')," ",$dockerLine);
        $ports[] = $dockerLine;
      }

      $dockerCmp = strpos($dockerCompare,"ENV");
      if ( $dockerCmp === 0 ) {
        if (strpos($dockerLine,"*") ) {
          $tempLine = str_replace("*","\n",$dockerLine);
          $environments = parse_ini_string($tempLine);
          $keys = array_keys($environments);
          foreach ($keys as $key) {
            $env[] = "$key {$environments[$key]}";
          }
        } else {
          $env[] = $dockerLine;
        }
      }
    }

    $allVolumes = array();
    foreach ( $volumes as $volume ) {
      $volumeList = explode(" ", $volume);
      unset($volumeList[0]);

      foreach ($volumeList as $myVolume) {
        $allVolumes[] = $myVolume;
      }
    }

    $allPorts = array();
    foreach ( $ports as $port) {
      $portList = str_replace("/tcp", "", $port);
      $portList = explode(" ", $portList);
      unset($portList[0]);
      foreach ( $portList as $myPort ) {
        if ( ! is_numeric($myPort) ) {
          continue;
        }
        $allPorts[] = $myPort;
      }
    }

    $allEnvironments = array();
    foreach ( $env as $environment ) {
      $environment = first_str_replace($environment,"ENV ","");
      if ( ! strpos($environment,"=") ) {
        $environment = first_str_replace($environment," ","=");
      }
      $envRaw = explode("=",$environment);
      for ( $i = 0; $i<=count($envRaw); $i+=2) {
        $envVar[0] = $envRaw[$i];
        if ( $glue == "=" ) {
          $envVar[1] = str_replace('"',"",$envRaw[$i+1]);
        } else {
          unset($envRaw[$i]);
          $envVar[1] = implode("=",$envRaw);
        }
        $allEnvironments[] = $envVar;
      }
    }

    $dockerfile['Name'] = $docker['Name'];
    $dockerfile['Support'] = $docker['DockerHub'];
    $dockerfile['Description'] = $docker['Description']."   Converted By Community Applications   Always verify this template (and values) against the dockerhub support page for the container";
    $dockerfile['Overview'] = $dockerfile['Description'];
    $dockerfile['Registry'] = $dockerURL;
    $dockerfile['Repository'] = $docker['Repository'];
    $dockerfile['BindTime'] = "true";
    $dockerfile['Privileged'] = "false";
    $dockerfile['Networking']['Mode'] = "bridge";

    foreach ($allPorts as $addPort) {
      if ( strpos($addPort, "/udp") === FALSE ) {
        $dockerfileport['HostPort'] = $addPort;
        $dockerfileport['ContainerPort'] = $addPort;
        $dockerfileport['Protocol'] = "tcp";
        $webUI[] = $addPort;
        $dockerfile['Networking']['Publish']['Port'][] = $dockerfileport;
      } else {
        $addPort = str_replace("/udp","",$addPort);
        $dockerfileport['HostPort'] = $addPort;
        $dockerfileport['ContainerPort'] = $addPort;
        $dockerfileport['Protocol'] = "udp";
        $dockerfile['Networking']['Publish']['Port'][] = $dockerfileport;
      }
    }
    foreach ( $allVolumes as $addVolume ) {
      if ( ! $addVolume ) { continue; }
      $dockervolume['HostDir'] = "";
      $dockervolume['ContainerDir'] = $addVolume;
      $dockervolume['Mode'] = "rw";
      $dockerfile['Data']['Volume'][] = $dockervolume;
    }
    foreach ($allEnvironments as $environment) {
      $variable['Name'] = $environment[0];
      $variable['Value'] = str_replace('"',"",$environment[1]);
      $dockerfile['Environment']['Variable'][] = $variable;
    }

    $dockerfile['Icon'] = "/plugins/dynamix.docker.manager/images/question.png";

    if ( is_array($webUI) ) {
      if ( count($webUI) == 1 ) {
        $dockerfile['WebUI'] .= "http://[IP]:[PORT:".$webUI[0]."]";
      }
      if ( count($webUI) > 1 ) {
        foreach ($webUI as $web) {
          if ( $web[0] == "8" ) {
            $webPort = $web;
          }
        }
        $dockerfile['WebUI'] .= "http://[IP]:[PORT:".$webPort."]";
      }
    }
  } else {
# Container is Official.  Add it as such
    $dockerURL = $docker['DockerHub'];
    $dockerfile['Name'] = $docker['Name'];
    $dockerfile['Support'] = $docker['DockerHub'];
    $dockerfile['Overview'] = $docker['Description']."   Converted By Community Applications.  Always verify this template (and values) against the dockerhub support page for the container";
    $dockerfile['Description'] = $dockerfile['Overview'];
    $dockerfile['Registry'] = $dockerURL;
    $dockerfile['Repository'] = $docker['Repository'];
    $dockerfile['BindTime'] = "true";
    $dockerfile['Privileged'] = "false";
    $dockerfile['Networking']['Mode'] = "bridge";
    $dockerfile['Icon'] = "/plugins/dynamix.docker.manager/images/question.png";
  }
  $dockerXML = makeXML($dockerfile);

  $xmlFile = $communityPaths['convertedTemplates']."DockerHub/";
  @mkdir($xmlFile,0777,true);
  $xmlFile .= str_replace("/","-",$docker['Repository']).".xml";
  file_put_contents($xmlFile,$dockerXML);
  file_put_contents($communityPaths['addConverted'],"Dante");
  echo $xmlFile;
  break;

#########################################################
# search_dockerhub - returns the results from dockerHub #
#########################################################
case 'search_dockerhub':
  $filter     = getPost("filter","");
  $pageNumber = getPost("page","1");
  $sortOrder  = getSortOrder(getPostArray('sortOrder'));
  $communitySettings['fontSize'] = getPost("fontSize",false);

  $communityTemplates = readJsonFile($communityPaths['community-templates-info']);
  $filter = str_replace(" ","%20",$filter);
  $jsonPage = shell_exec("curl -s -X GET 'https://registry.hub.docker.com/v1/search?q=$filter\&page=$pageNumber'");
  $pageresults = json_decode($jsonPage,true);
  $num_pages = $pageresults['num_pages'];

  if ($pageresults['num_results'] == 0) {
    echo "<center>No matching content found on dockerhub</center>";
    echo "<script>$('#dockerSearch').hide();</script>";
    @unlink($communityPaths['dockerSerchResults']);
    break;
  }

  $i = 0;
  foreach ($pageresults['results'] as $result) {
    unset($o);
    $o['Repository'] = $result['name'];
    $details = explode("/",$result['name']);
    $o['Author'] = $details[0];
    $o['Name'] = $details[1];
    $o['Description'] = $result['description'];
    $o['Automated'] = $result['is_automated'];
    $o['Stars'] = $result['star_count'];
    $o['Official'] = $result['is_official'];
    $o['Trusted'] = $result['is_trusted'];
    if ( $o['Official'] ) {
      $o['DockerHub'] = "https://hub.docker.com/_/".$result['name']."/";
      $o['Name'] = $o['Author'];
    } else {
      $o['DockerHub'] = "https://hub.docker.com/r/".$result['name']."/";
    }
    $o['ID'] = $i;
    $searchName = str_replace("docker-","",$o['Name']);
    $searchName = str_replace("-docker","",$searchName);

    $dockerResults[$i] = $o;
    $i=++$i;
  }
  $dockerFile['num_pages'] = $num_pages;
  $dockerFile['page_number'] = $pageNumber;
  $dockerFile['results'] = $dockerResults;

  writeJsonFile($communityPaths['dockerSearchResults'],$dockerFile);
  displaySearchResults($pageNumber);
  break;

#####################################################################
# dismiss_warning - dismisses the warning from appearing at startup #
#####################################################################
case 'dismiss_warning':
  file_put_contents($communityPaths['warningAccepted'],"warning dismissed");
  echo "warning dismissed";
  break;

###############################################################
# Displays the list of installed or previously installed apps #
###############################################################
case 'previous_apps':
  $installed = getPost("installed","");
  $communitySettings['fontSize'] = getPost("fontSize",false);
  $dockerUpdateStatus = readJsonFile($communityPaths['dockerUpdateStatus']);
  $moderation = readJsonFile($communityPaths['moderation']);
  if ( $communitySettings['dockerRunning'] ) {
    $info = $DockerClient->getDockerContainers();
  } else {
    $info = array();
  }
  $file = readJsonFile($communityPaths['community-templates-info']);

# $info contains all installed containers
# now correlate that to a template;
# this section handles containers that have not been renamed from the appfeed
if ( $communitySettings['dockerRunning'] ) {
  $all_files = glob("/boot/config/plugins/dockerMan/templates-user/*.xml");
  $all_files = $all_files ?: array();

  if ( $installed == "true" ) {
    foreach ($info as $installedDocker) {
      $installedImage = $installedDocker['Image'];
      $installedName = $installedDocker['Name'];
      if ( startsWith($installedImage,"library/") ) { # official images are in DockerClient as library/mysql eg but template just shows mysql
        $installedImage = str_replace("library/","",$installedImage);
      }

      foreach ($file as $template) {
        if ( $installedName == $template['Name'] ) {
          $template['testrepo'] = $installedImage;
          if ( startsWith($installedImage,$template['Repository']) ) {
            $template['Uninstall'] = true;
            $template['MyPath'] = $template['Path'];
            if ( $dockerUpdateStatus[$installedImage]['status'] == "false" || $dockerUpdateStatus[$template['Name']] == "false" ) {
              $template['UpdateAvailable'] = true;
              $template['FullRepo'] = $installedImage;
            }
            if ($template['Blacklist'] ) {
              continue;
            }
            $displayed[] = $template;
            break;
          }
        }
      }
    }
# handle renamed containers
    foreach ($all_files as $xmlfile) {
      $o = readXmlFile($xmlfile,$moderation);
      $o['Description'] = fixDescription($o['Description']);
      $o['Overview'] = fixDescription($o['Overview']);
      $o['MyPath'] = $xmlfile;
      $o['UnknownCompatible'] = true;

      if ( is_array($moderation[$o['Repository']]) ) {
        $o = array_merge($o, $moderation[$o['Repository']]);
      }
      $flag = false;
      $containerID = false;
      foreach ($file as $templateDocker) {
# use startsWith to eliminate any version tags (:latest)
        if ( startsWith($templateDocker['Repository'], $o['Repository']) ) {
          if ( $templateDocker['Name'] == $o['Name'] ) {
            $flag = true;
            $containerID = $template['ID'];
            break;
          }
        }
      }
      if ( ! $flag ) {
        $runningflag = false;
        foreach ($info as $installedDocker) {
          $installedImage = $installedDocker['Image'];
          $installedName = $installedDocker['Name'];
          if ( startsWith($installedImage, $o['Repository']) ) {
            if ( $installedName == $o['Name'] ) {
              $runningflag = true;
              $searchResult = searchArray($file,'Repository',$o['Repository']);
              if ( $searchResult !== false ) {
                $tempPath = $o['MyPath'];
                $containerID = $file[$searchResult]['ID'];
                $o = $file[$searchResult];
                $o['Name'] = $installedName;
                $o['MyPath'] = $tempPath;
                $o['SortName'] = $installedName;
                if ( $dockerUpdateStatus[$installedImage]['status'] == "false" || $dockerUpdateStatus[$template['Name']] == "false" ) {
                  $o['UpdateAvailable'] = true;
                  $o['FullRepo'] = $installedImage;
                }
              }
              break;;
            }
          }
        }
        if ( $runningflag ) {
          $o['Uninstall'] = true;
          $o['ID'] = $containerID;
          if ( $o['Blacklist'] ) {
            continue;
          }
          # handle a PR from LT where it is possible for an identical template (xml) to be present twice, with different filenames.
          # Without this, an app could appear to be shown in installed apps twice
          $fat32Fix[$searchResult]++;
          if ($fat32Fix[$searchResult] > 1) {continue;}
          $displayed[] = $o;
        }
      }
    }
  } else {
# now get the old not installed docker apps
    foreach ($all_files as $xmlfile) {
      $o = readXmlFile($xmlfile);
      $o['Description'] = fixDescription($o['Description']);
      $o['Overview'] = fixDescription($o['Overview']);
      $o['MyPath'] = $xmlfile;
      $o['UnknownCompatible'] = true;
      $o['Removable'] = true;
# is the container running?

      $flag = false;
      foreach ($info as $installedDocker) {
        $installedImage = $installedDocker['Image'];
        $installedName = $installedDocker['Name'];
        if ( startsWith($installedImage, $o['Repository']) ) {
          if ( $installedName == $o['Name'] ) {
            $flag = true;
            continue;
          }
        }
      }
      if ( ! $flag ) {
# now associate the template back to a template in the appfeed
        foreach ($file as $appTemplate) {
          if ($appTemplate['Repository'] == $o['Repository']) {
            $tempPath = $o['MyPath'];
            $tempName = $o['Name'];
            $o = $appTemplate;
            $o['Removable'] = true;
            $o['MyPath'] = $tempPath;
            $o['Name'] = $tempName;
            break;
          }
        }
        if ( $moderation[$o['Repository']]['Blacklist'] ) {
          continue;
        }
        if ( ! $o['Blacklist'] ) {
          $displayed[] = $o;
        }
      }
    }
  }
}
# Now work on plugins
  if ( $installed == "true" ) {
    foreach ($file as $template) {
      if ( ! $template['Plugin'] ) {
        continue;
      }
      $filename = pathinfo($template['Repository'],PATHINFO_BASENAME);

      if ( checkInstalledPlugin($template) ) {
        if ( $template['Blacklist'] ) {
          continue;
        }
        $template['MyPath'] = "/var/log/plugins/$filename";
        $template['Uninstall'] = true;
        $displayed[] = $template;
      }
    }
  } else {
    $all_plugs = glob("/boot/config/plugins-removed/*.plg");

    foreach ($all_plugs as $oldplug) {
      foreach ($file as $template) {
        if ( basename($oldplug) == basename($template['Repository']) ) {
          if ( ! file_exists("/boot/config/plugins/".basename($oldplug)) ) {
            if ( $template['Blacklist'] || ( ($communitySettings['hideIncompatible'] == "true") && (! $template['Compatible']) ) ) {
              continue;
            }
            if ( strtolower(trim($template['PluginURL'])) != strtolower(trim(plugin("pluginURL","$oldplug"))) ) {
              if ( strtolower(trim($template['PluginURL'])) != strtolower(trim(str_replace("raw.github.com","raw.githubusercontent.com",plugin("pluginURL",$oldplug)))) ) {
                continue;
              }
            }
            $template['Removable'] = true;
            $template['MyPath'] = $oldplug;

            $displayed[] = $template;
            break;
          }
        }
      }
    }
  }
  $displayedApplications['community'] = $displayed;
  writeJsonFile($communityPaths['community-templates-displayed'],$displayedApplications);
  echo "ok";
  break;

####################################################################################
# Removes an app from the previously installed list (ie: deletes the user template #
####################################################################################
case 'remove_application':
  $application = getPost("application","");
  if ( pathinfo($application,PATHINFO_EXTENSION) == "xml" || pathinfo($application,PATHINFO_EXTENSION) == "plg" ) {
    @unlink($application);
  }
  echo "ok";
  break;

#######################
# Uninstalls a plugin #
#######################
case 'uninstall_application':
  $application = getPost("application","");

  $filename = basename($application);
  shell_exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin remove ".escapeshellarg($filename));
  echo "ok";
  break;

###################################################################################
# Checks for an update still available (to update display) after update installed #
###################################################################################
case 'updatePLGstatus':
  $filename = getPost("filename","");
  $displayed = readJsonFile($communityPaths['community-templates-displayed']);
  $superCategories = array_keys($displayed);
  foreach ($superCategories as $category) {
    foreach ($displayed[$category] as $template) {
      if ( strpos($template['PluginURL'],$filename) ) {
        $template['UpdateAvailable'] = checkPluginUpdate($filename);
      }
      $newDisplayed[$category][] = $template;
    }
  }
  writeJsonFile($communityPaths['community-templates-displayed'],$newDisplayed);
  echo "ok";
  break;

#######################
# Uninstalls a docker #
#######################
case 'uninstall_docker':
  $application = getPost("application","");

# get the name of the container / image
  $doc = new DOMDocument();
  $doc->load($application);
  $containerName  = stripslashes($doc->getElementsByTagName( "Name" )->item(0)->nodeValue);

  $dockerInfo = $DockerClient->getDockerContainers();
  $container = searchArray($dockerInfo,"Name",$containerName);

  if ( $dockerRunning[$container]['Running'] ) {
    myStopContainer($dockerRunning[$container]['Id']);
  }
  $DockerClient->removeContainer($containerName,$dockerRunning[$container]['Id']);
  $DockerClient->removeImage($dockerRunning[$container]['ImageId']);

  echo "Uninstalled";
  break;

##################################################
# Pins / Unpins an application for later viewing #
##################################################
case "pinApp":
  $repository = getPost("repository","oops");
  $pinnedApps = readJsonFile($communityPaths['pinned']);
  $pinnedApps[$repository] = $pinnedApps[$repository] ? false : $repository;
  writeJsonFile($communityPaths['pinned'],$pinnedApps);
  break;

####################################
# Displays the pinned applications #
####################################
case "pinnedApps":
  $pinnedApps = readJsonFile($communityPaths['pinned']);
  $file = readJsonFile($communityPaths['community-templates-info']);
  $startIndex = 0;
  foreach ($pinnedApps as $pinned) {
    for ($i=0;$i<10;$i++) {
      $index = searchArray($file,"Repository",$pinned,$startIndex);
      if ( $index !== false ) {
        if ( $file[$index]['Blacklist'] ) { #This handles things like duplicated templates
          $startIndex = $index + 1;
          continue;
        }
        $displayed[] = $file[$index];
        break;
      }
    }
  }
  $displayedApplications['community'] = $displayed;
  $displayedApplications['pinnedFlag']  = true;
  writeJsonFile($communityPaths['community-templates-displayed'],$displayedApplications);
  echo "fini!";
  break;

################################################
# Displays the possible branch tags for an app #
################################################
case 'displayTags':
  $leadTemplate = getPost("leadTemplate","oops");
  $file = readJsonFile($communityPaths['community-templates-info']);
  $template = $file[$leadTemplate];
  $childTemplates = $file[$leadTemplate]['BranchID'];
  if ( ! is_array($childTemplates) ) {
    echo "Something really went wrong here";
  } else {
    $defaultTag = $template['BranchDefault'] ? $template['BranchDefault'] : "latest";
    echo "<table>";
    echo "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href='Apps/AddContainer?xmlTemplate=default:".$template['Path']."' target='_self'>Default</a></td><td>Install Using The Template's Default Tag (<font color='purple'>:$defaultTag</font>)</td></tr>";
    foreach ($childTemplates as $child) {
      echo "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href='Apps/AddContainer?xmlTemplate=default:".$file[$child]['Path']."' target='_self'>".$file[$child]['BranchName']."</a></td><td>".$file[$child]['BranchDescription']."</td></tr>";
    }
    echo "</table>";
  }
  break;

###########################################
# Displays The Statistics For The Appfeed #
###########################################
case 'statistics':
  $statistics = download_json($communityPaths['statisticsURL'],$communityPaths['statistics']);
  download_json($communityPaths['moderationURL'],$communityPaths['moderation']);
  $statistics['totalModeration'] = count(readJsonFile($communityPaths['moderation']));
  $repositories = download_json($communityPaths['community-templates-url'],$communityPaths['Repositories']);
  $templates = readJsonFile($communityPaths['community-templates-info']);
  pluginDupe($templates);
  $invalidXML = readJsonFile($communityPaths['invalidXML_txt']);
  $sortOrder['sortBy'] = "RepoName";
  $sortOrder['sortDir'] = "Up";
  usort($templates,"mySort");
  foreach ($templates as $template) {
    if ( $template['Deprecated'] ) {
      $statistics['totalDeprecated']++;
    }
    if ( ! $template['Compatible'] ) {
      $statistics['totalIncompatible']++;
    }
    if ( $template['Blacklist'] ) {
      $statistics['blacklist']++;
    }
    if ( $template['Private'] && ! $template['Blacklist']) {
      if ( ! ($communitySettings['hideDeprecated'] == 'true' && $template['Deprecated']) ) {
        $statistics['private']++;
      }
    }
    if ( ! $template['PluginURL'] && ! $template['Repository'] ) {
      $statistics['invalidXML']++;
    } else {
      if ( $template['PluginURL'] ) {
        $statistics['plugin']++;
      } else {
        $statistics['docker']++;
      }
    }
  }
  $statistics['totalApplications'] = $statistics['plugin']+$statistics['docker'];
  if ( $statistics['fixedTemplates'] ) {
    writeJsonFile($communityPaths['fixedTemplates_txt'],$statistics['fixedTemplates']);
  } else {
    @unlink($communityPaths['fixedTemplates_txt']);
  }
  if ( is_file($communityPaths['lastUpdated-old']) ) {
    $appFeedTime = readJsonFile($communityPaths['lastUpdated-old']);
  }
  $updateTime = date("F d, Y @ g:i a",$appFeedTime['last_updated_timestamp']);
  $defaultArray = Array('caFixed' => 0,'totalApplications' => 0, 'repository' => 0, 'docker' => 0, 'plugin' => 0, 'invalidXML' => 0, 'blacklist' => 0, 'totalIncompatible' =>0, 'totalDeprecated' => 0, 'totalModeration' => 0, 'private' => 0, 'NoSupport' => 0);
  $statistics = array_merge($defaultArray,$statistics);

  foreach ($statistics as &$stat) {
    if ( ! $stat ) $stat = "0";
  }

  $totalCA = exec("du -h -s /usr/local/emhttp/plugins/community.applications/");
  $totalTmp = exec("du -h -s /tmp/community.applications/");
  $totalFlash = exec("du -h -s /boot/config/plugins/community.applications/");
  $memCA = explode("\t",$totalCA);
  $memTmp = explode("\t",$totalTmp);
  $memFlash = explode("\t",$totalFlash);

  $currentServer = @file_get_contents($communityPaths['currentServer']);
  if ( $currentServer != "Primary Server" ) {
    $currentServer = "<i class='fa fa-exclamation-triangle ca_serverWarning' aria-hidden='true'></i> $currentServer";
  }

?>
<div style='height:auto;overflow:scroll; overflow-x:hidden; overflow-y:hidden;margin:auto;width:700px;'>
<table style='margin-top:1rem;'>
<tr style='height:6rem;'><td colspan='2'><center><img style='height:4.8rem;' src='https://raw.githubusercontent.com/Squidly271/plugin-repository/master/CA.png'></td></tr>
<tr><td colspan='2'><center><font size='5rem;' color='white'>Community Applications</font></center></td></tr>
<tr><td class='ca_table'><a href='/Apps/Appfeed' target='_blank'>Last Change To Application Feed</a></td><td class='ca_stat'><?=$updateTime?><br><?=$currentServer?> active</td></tr>
<tr><td class='ca_table'>Number Of Templates</td><td class='ca_stat'><?=$statistics['totalApplications']?></td></tr>
<tr><td class='ca_table'><a onclick='showModeration(&quot;Repository&quot;,&quot;Repository List&quot;);' style='cursor:pointer;'>Number Of Repositories</a></td><td class='ca_stat'><?=count($repositories)?></td></tr>
<tr><td class='ca_table'>Number Of Docker Applications</td><td class='ca_stat'><?=$statistics['docker']?></td></tr>
<tr><td class='ca_table'>Number Of Plugins</td><td class='ca_stat'><?=$statistics['plugin']?></td></tr>
<tr><td class='ca_table'><a data-category='PRIVATE' onclick='showSpecialCategory(this);' style='cursor:pointer;'>Number Of Private Docker Applications</a></td><td class='ca_stat'><?=$statistics['private']?></td></tr>
<tr><td class='ca_table'><a onclick='showModeration(&quot;Invalid&quot;,&quot;All Invalid Templates Found&quot;);' style='cursor:pointer'>Number Of Invalid Templates</a></td><td class='ca_stat'><?=count($invalidXML)?></td></tr>
<tr><td class='ca_table'><a onclick='showModeration(&quot;Fixed&quot;,&quot;Template Errors&quot;);' style='cursor:pointer'>Number Of Template Errors</a></td><td class='ca_stat'><?=$statistics['caFixed']?>+</td></tr>
<tr><td class='ca_table'><a data-category='BLACKLIST' onclick='showSpecialCategory(this);' style='cursor:pointer'>Number Of Blacklisted Apps</a></td><td class='ca_stat'><?=$statistics['blacklist']?></td></tr>
<tr><td class='ca_table'><a data-category='INCOMPATIBLE' onclick='showSpecialCategory(this);' style='cursor:pointer'>Number Of Incompatible Applications</a></td><td class='ca_stat'><?=$statistics['totalIncompatible']?></td></tr>
<tr><td class='ca_table'><a data-category='DEPRECATED' onclick='showSpecialCategory(this);' style='cursor:pointer'>Number Of Deprecated Applications</a></td><td class='ca_stat'><?=$statistics['totalDeprecated']?></td></tr>
<tr><td class='ca_table'><a onclick='showModeration(&quot;Moderation&quot;,&quot;All Moderation Entries&quot;);' style='cursor:pointer'>Number Of Moderation Entries</a></td><td class='ca_stat'><?=$statistics['totalModeration']?>+</td></tr>
<tr><td class='ca_table'>Memory Usage (CA / DataFiles / Flash)</td><td class='ca_stat'><?=$memCA[0]?> / <?=$memTmp[0]?> / <?=$memFlash[0]?></td></tr>
</table>
<center><a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7M7CBCVU732XG' target='_blank'><img style='height:2.5rem;' src='https://github.com/Squidly271/community.applications/raw/master/webImages/donate-button.png'></a></center>
<center>Ensuring only safe applications are present is a full time job</center><br>
<?
  break;

#######################################
# Removes a private app from the list #
#######################################
case 'removePrivateApp':
  $path = getPost("path",false);

  if ( ! $path || pathinfo($path,PATHINFO_EXTENSION) != "xml") {
    echo "something went wrong";
    break;
  }
  $templates = readJsonFile($communityPaths['community-templates-info']);
  $displayed = readJsonFile($communityPaths['community-templates-displayed']);
  foreach ( $displayed as &$displayType ) {
    foreach ( $displayType as &$display ) {
      if ( $display['Path'] == $path ) {
        $display['Blacklist'] = true;
      }
    }
  }
  foreach ( $templates as &$template ) {
    if ( $template['Path'] == $path ) {
      $template['Blacklist'] = true;
    }
  }
  writeJsonFile($communityPaths['community-templates-info'],$templates);
  writeJsonFile($communityPaths['community-templates-displayed'],$displayed);
  @unlink($path);
  echo "done";
  break;

####################################################
# Creates the entries for autocomplete on searches #
####################################################
case 'populateAutoComplete':
  $templates = readJsonFile($communityPaths['community-templates-info']);

  $autoComplete = array("backup"=>"Backup","cloud"=>"Cloud","downloaders"=>"Downloaders","homeautomation"=>"Home Automation","network"=>"Network","mediaapp"=>"Media App","mediaserver"=>"Media Server","productivity"=>"Productivity","tools"=>"Tools","other"=>"Other","plugins"=>"Plugins","uncategorized"=>"Uncategorized");
  foreach ($templates as $template) {
    if ( ! $template['Blacklist'] && ! ($template['Deprecated'] && $communitySettings['hideDeprecated'] == "true") && ($template['Compatible'] || $communitySettings['hideIncompatible'] != "true") ) {
      $autoComplete[strtolower($template['Name'])] = $template['Name'];
      $autoComplete[strtolower($template['Author'])] = $template['Author'];
      $repo = explode("'",$template['Repo']);
      $autoComplete[strtolower($repo[0])] = $repo[0];
    }
  }
  foreach ($autoComplete as $auto) {
    $autoScript .= "'$auto',";
  }
  echo "<script>searchBoxAwesomplete.list = [".rtrim($autoScript,",")."];</script>";
  break;

#############################
# Stops a running container #
#############################
case 'stopStartContainer':
  $containerName = getPost("name","");
  $containerID = getPost("id","");
  $stopStart = filter_var(getPost("stopStart",""),FILTER_VALIDATE_BOOLEAN);
  if ( $stopStart ) {
    myStartContainer($containerID);
  } else {
    myStopContainer($containerID);
  }
  $info = getRunningContainers();
  $runState = $stopStart ? $info[$containerName]['running'] : !$info[$containerName]['running'];
  if ($runState) {
    echo "ok";
  } else {
    echo "problem";
  }
  break;

case 'getCurrentServer':
  $server = @file_get_contents($communityPaths['currentServer']);
  if ($server != "Primary Server") {
    $server = "<i class='fa fa-exclamation-triangle ca_serverWarning' aria-hidden='true'></i> $server";
  }
  echo $server ? "<br>$server Active" : "<br>Appfeed Download Failed";
  break;

case 'showCredits':
  echo file_get_contents("/usr/local/emhttp/plugins/community.applications/include/caCredits.html");
  break;

}

#  DownloadApplicationFeed MUST BE CALLED prior to DownloadCommunityTemplates in order for private repositories to be merged correctly.

function DownloadApplicationFeed() {
  global $communityPaths, $communitySettings, $statistics;

  exec("rm -rf '{$communityPaths['templates-community']}'");
  @mkdir($communityPaths['templates-community'],0777,true);

  $downloadURL = randomFile();
  $currentFeed = "Primary Server";
  $ApplicationFeed = download_json($communityPaths['application-feed'],$downloadURL);
  if ( ! is_array($ApplicationFeed['applist']) ) {
    $currentFeed = "Backup Server";
    $ApplicationFeed = download_json($communityPaths['application-feedBackup'],$downloadURL);
    if ( ! is_array($ApplicationFeed['applist']) ) {
      if ( is_file($communityPaths['appFeedBackupUSB']) ) {
        $currentFeed = "USB Backup File";
        $ApplicationFeed = readJsonFile($communityPaths['appFeedBackupUSB']);
      }
    }
  }
  if ( ! is_array($ApplicationFeed['applist']) ) {
    @unlink($communityPaths['currentServer']);
    file_put_contents($communityPaths['appFeedDownloadError'],$downloadURL);
    return false;
  }
  file_put_contents($communityPaths['currentServer'],$currentFeed);
  @unlink($downloadURL);
  $i = 0;
  $lastUpdated['last_updated_timestamp'] = $ApplicationFeed['last_updated_timestamp'];
  writeJsonFile($communityPaths['lastUpdated-old'],$lastUpdated);
  $myTemplates = array();

  foreach ($ApplicationFeed['applist'] as $o) {
    if ( (! $o['Repository']) && (! $o['Plugin']) ){
      $invalidXML[] = $o;
      continue;
    }
    # Move the appropriate stuff over into a CA data file
    $o['ID']            = $i;
    $o['Displayable']   = true;
    $o['Author']        = getAuthor($o);
    $o['DockerHubName'] = strtolower($o['Name']);
    $o['RepoName']      = $o['Repo'];
    $o['SortAuthor']    = $o['Author'];
    $o['SortName']      = $o['Name'];
    if ( $o['PluginURL'] ) {
      $o['Author']        = $o['PluginAuthor'];
      $o['Repository']    = $o['PluginURL'];
    }
    $o['MinVer'] = max(array($o['MinVer'],$o['UpdateMinVer']));

    $o['Path']          = $communityPaths['templates-community']."/".alphaNumeric($o['RepoName'])."/".alphaNumeric($o['Name']).".xml";
    $o = fixTemplates($o);
    if ( ! $o ) {
      continue;
    }

    $o['Category'] = str_replace("Status:Beta","",$o['Category']);    # undo changes LT made to my xml schema for no good reason
    $o['Category'] = str_replace("Status:Stable","",$o['Category']);
    $myTemplates[$i] = $o;

    if ( is_array($o['Branch']) ) {
      if ( ! $o['Branch'][0] ) {
        $tmp = $o['Branch'];
        unset($o['Branch']);
        $o['Branch'][] = $tmp;
      }
      foreach($o['Branch'] as $branch) {
        $i = ++$i;
        $subBranch = $o;
        $masterRepository = explode(":",$subBranch['Repository']);
        $o['BranchDefault'] = $masterRepository[1];
        $subBranch['Repository'] = $masterRepository[0].":".$branch['Tag']; #This takes place before any xml elements are overwritten by additional entries in the branch, so you can actually change the repo the app draws from
        $subBranch['BranchName'] = $branch['Tag'];
        $subBranch['BranchDescription'] = $branch['TagDescription'] ? $branch['TagDescription'] : $branch['Tag'];
        $subBranch['Path'] = $communityPaths['templates-community']."/".$i.".xml";
        $subBranch['Displayable'] = false;
        $subBranch['ID'] = $i;
        $replaceKeys = array_diff(array_keys($branch),array("Tag","TagDescription"));
        foreach ($replaceKeys as $key) {
          $subBranch[$key] = $branch[$key];
        }
        unset($subBranch['Branch']);
        $myTemplates[$i] = $subBranch;
        $o['BranchID'][] = $i;
        file_put_contents($subBranch['Path'],makeXML($subBranch));
      }
    }
    unset($o['Branch']);
    $myTemplates[$o['ID']] = $o;
    $i = ++$i;
    if ( $o['OriginalOverview'] ) {
      $o['Overview'] = $o['OriginalOverview'];
      unset($o['OriginalOverview']);
      unset($o['Description']);
    }
    if ( $o['OriginalDescription'] ) {
      $o['Description'] = $o['OriginalDescription'];
      unset($o['OriginalDescription']);
    }
    $templateXML = makeXML($o);
    @mkdir(dirname($o['Path']),0777,true);
    if ( file_exists($o['Path']) ) {
      $o['Path'] .= "(1).xml";
    }
    file_put_contents($o['Path'],$templateXML);
  }
  if ( $invalidXML ) {
    writeJsonFile($communityPaths['invalidXML_txt'],$invalidXML);
  } else {
    @unlink($communityPaths['invalidXML_txt']);
  }
  writeJsonFile($communityPaths['community-templates-info'],$myTemplates);

  return true;
}

function getConvertedTemplates() {
  global $communityPaths, $communitySettings, $statistics;

# Start by removing any pre-existing private (converted templates)
  $templates = readJsonFile($communityPaths['community-templates-info']);

  if ( empty($templates) ) {
    return false;
  }
  foreach ($templates as $template) {
    if ( ! $template['Private'] ) {
      $myTemplates[] = $template;
    }
  }
  $appCount = count($myTemplates);
  $moderation = readJsonFile($communityPaths['moderation']);
  $i = $appCount;
  unset($Repos);

  if ( ! is_dir($communityPaths['convertedTemplates']) ) {
    return;
  }

  $privateTemplates = glob($communityPaths['convertedTemplates']."*/*.xml");
  foreach ($privateTemplates as $template) {
    $o = readXmlFile($template);
    if ( ! $o['Repository'] ) {
      continue;
    }
    $o['Private']      = true;
    $o['RepoName']     = basename(pathinfo($template,PATHINFO_DIRNAME))." Repository";
    $o['ID']           = $i;
    $o['Displayable']  = true;
    $o['Date']         = ( $o['Date'] ) ? strtotime( $o['Date'] ) : 0;
    $o['SortAuthor']   = $o['Author'];
    $o['Compatible']   = versionCheck($o);

    $o = fixTemplates($o);
    $myTemplates[$i]  = $o;
    $i = ++$i;
  }
  writeJsonFile($communityPaths['community-templates-info'],$myTemplates);
  return true;
}


#############################
# Selects an app of the day #
#############################
function appOfDay($file,&$startupMsg,&$startupMsg2) {
  global $communityPaths,$communitySettings,$sortOrder;

  $communitySettings['separateInstalled'] = "false";
  $info = getRunningContainers();
  switch ($communitySettings['startup']) {
    case "random":
      $oldAppDay = @filemtime($communityPaths['appOfTheDay']);
      $oldAppDay = $oldAppDay ?: 1;
      $oldAppDay = intval($oldAppDay / 86400);
      $currentDay = intval(time() / 86400);
      if ( $oldAppDay == $currentDay ) {
        $app = readJsonFile($communityPaths['appOfTheDay']);
        $flag = false;
        foreach ($app as $testApp) {
          if ( ! checkRandomApp($testApp,$file,false,$info) ) {
            $flag = true;
            break;
          }
        }
        if ( $flag ) {
          $app = array();
        }
      }
      if ( ! $app ) {
        for ( $ii=0; $ii<25; $ii++ ) {
          $flag = false;
          if ( $app[$ii] ) {
            $flag = checkRandomApp($app[$ii],$file);
          }
          if ( ! $flag ) {
            for ( $jj = 0; $jj<20; $jj++) { # only give it 20 shots to find an app of the day
              $randomApp = mt_rand(0,count($file) -1);
              $flag = checkRandomApp($randomApp,$file,false,$info);
              if ( $flag ) {
                break;
              }
            }
          }
          if ( ! $flag ) {
            continue;
          }
          $app[$ii] = $randomApp;
        }
      }
      if (! $app) { $app = array(); }
      $appOfDay = array_values(array_unique($app));
      writeJsonFile($communityPaths['appOfTheDay'],$appOfDay);
      $startupMsg = "Random Applications Of The Day <i class='startup-icon fa fa-question-circle ca_staticTips' title='This list changes every 24 hours'></i>";
      break;
    case "new":
      $sortOrder['sortBy'] = "Date";
      $sortOrder['sortDir'] = "Down";
      usort($file,"mySort");
      for ( $i = 0; $i <100; $i++) {
        if ( ! checkRandomApp($i,$file,true,$info) ) continue;
        $appOfDay[] = $file[$i]['ID'];
      }
      $startupMsg = "Recently Updated Applications <i class='startup-icon fa fa-question-circle ca_staticTips' title='<center>Select the New/Updated Category for the complete list<br><font size=&quot;0&quot;>Note that many authors and maintainers do not flag the application as being updated</font></center>'></i>";
      $startupMsg2 = "";
      break;
    case "onlynew":
      $sortOrder['sortBy'] = "FirstSeen";
      $sortOrder['sortDir'] = "Down";
      usort($file,"mySort");
      foreach ($file as $template) {
        if ( $template['FirstSeen'] > 1538357652 ) {
          if ( $template['BranchName'] ) continue;
          $appOfDay[] = $template['ID'];
          if ( count($appOfDay) == 25 ) break;
        }
      }
      $startupMsg = "Newly Added Applications";
      break;
    case "trending":
      $sortOrder['sortBy'] = "trending";
      $sortOrder['sortDir'] = "Down";
      usort($file,"mySort");
      foreach ($file as $template) {
        if ( $template['trending'] && ($template['downloads'] > 10000) ) {
          if ( $template['Deprecated'] && ($communitySettings['hideDeprecated'] == "true" ) ) continue;
          if ( $template['Blacklist'] ) continue;
          if ( $template['BranchName'] ) continue; # stops all the sub branches from appearing in the list when only the first is necessary
          $appOfDay[] = $template['ID'];
          if ( count($appOfDay) == 25 ) break;
        }
      }
      $startupMsg = "Trending Applications <i class='startup-icon fa fa-question-circle ca_staticTips' title='<center>Largest % increase in downloads over 30 days.<br><font size=&quot;0&quot;>Note that this does not mean that any particular app is recommended.<br>Plugins are NOT included in this list</font></center>'></i>";
      $startupMsg2 = "";
      break;
  }
  return $appOfDay ?: array();
}

#####################################################
# Checks selected app for eligibility as app of day #
#####################################################
function checkRandomApp($randomApp,$file,$newApp=false,$info=array() ) {
  global $communitySettings;

  $test = $file[$randomApp];
  if ( $test['BranchName'] )                        return false;
  if ( ! $test['Displayable'] )                     return false;
  if ( ! $test['Compatible'] )                      return false;
  if ( $test['Blacklist'] )                         return false;
  if ( ($test['ModeratorComment']) && (! $newApp) ) return false;
  if ( $test['Deprecated'] )                        return false;
  if ( ($test['Beta'] == "true" ) && (! $newApp ) ) return false;
  if ( $communitySettings['separateInstalled'] != "false" ) {
    if ( $test['Plugin'] ) {
      if ( file_exists("/var/log/plugins/".basename($test['PluginURL'])) ) { return false; }
    } else {
      if ( ! strpos($test['Repository'],":") ) {
        $test['Repository'] .= ":latest";
      }
      foreach ($info as $tst) {
        if ($test['Repository'] == $tst['repository'] ) return false;
      }
    }
  }
  return true;
}
?>