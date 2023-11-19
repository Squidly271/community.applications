<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2023, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################
//error_reporting(E_ALL);
ini_set('memory_limit','256M');  // REQUIRED LINE

$unRaidSettings = parse_ini_file("/etc/unraid-version");

### Translations section has to be first so that nothing else winds up caching the file(s)

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

$_SERVER['REQUEST_URI'] = "docker/apps";

require_once "$docroot/plugins/dynamix/include/Wrappers.php";
require_once "$docroot/plugins/dynamix/include/Translations.php";
require_once "$docroot/plugins/dynamix.docker.manager/include/DockerClient.php"; # must be first include due to paths defined
require_once "$docroot/plugins/community.applications/include/paths.php";
require_once "$docroot/plugins/community.applications/include/helpers.php";
require_once "$docroot/plugins/community.applications/skins/Narrow/skin.php";
require_once "$docroot/plugins/dynamix.plugin.manager/include/PluginHelpers.php";
require_once "$docroot/webGui/include/Markdown.php";

################################################################################
# Set up any default settings (when not explicitely set by the settings module #
################################################################################

$caSettings = parse_plugin_cfg("community.applications");
$dynamixSettings = parse_plugin_cfg("dynamix");

$caSettings['dockerSearch']  = "yes";
$caSettings['unRaidVersion'] = $unRaidSettings['version'];
$caSettings['favourite']     = isset($caSettings['favourite']) ? str_replace("*","'",$caSettings['favourite']) : "";
$caSettings['dynamixTheme']  = $dynamixSettings['theme'];

$caSettings['maxPerPage']    = (integer)$caSettings['maxPerPage'] ?: "24"; // Handle possible corruption on file
if ( $caSettings['maxPerPage'] < 24 ) $caSettings['maxPerPage'] = 24;

if ( ! is_file($caPaths['warningAccepted']) )
  $caSettings['NoInstalls'] = true;

$DockerClient = new DockerClient();
$DockerTemplates = new DockerTemplates();

if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
  $caSettings['dockerRunning'] = true;
} else {
  $caSettings['dockerSearch'] = "no";
  $caSettings['dockerRunning'] = false;
}

@mkdir($caPaths['tempFiles'],0777,true);

if ( !is_dir($caPaths['templates-community']) ) {
  @mkdir($caPaths['templates-community'],0777,true);
  @unlink($caPaths['community-templates-info']);
}

debug("POST CALLED ({$_POST['action']})\n".print_r($_POST,true));


$sortOrder = readJsonFile($caPaths['sortOrder']);
if ( ! $sortOrder ) {
  $sortOrder['sortBy'] = "Name";
  $sortOrder['sortDir'] = "Up";
  writeJsonFile($caPaths['sortOrder'],$sortOrder);
}

$GLOBALS['templates'] = readJsonFile($caPaths['community-templates-info']);

############################################
##                                        ##
## BEGIN MAIN ROUTINES CALLED BY THE HTML ##
##                                        ##
############################################

switch ($_POST['action']) {
  case 'get_content':
    get_content();
    break;
  case 'force_update':
    force_update();
    break;
  case 'display_content':
    display_content();
    break;
  case 'dismiss_warning':
    dismiss_warning();
    break;
  case 'dismiss_plugin_warning':
    dismiss_plugin_warning();
    break;
  case 'previous_apps':
    previous_apps();
    break;
  case 'remove_application':
    remove_application();
    break;
  case 'updatePLGstatus':
    updatePLGstatus();
    break;
  case 'uninstall_docker':
    uninstall_docker();
    break;
  case "pinApp":
    pinApp();
    break;
  case "areAppsPinned":
    areAppsPinned();
    break;
  case "pinnedApps":
    pinnedApps();
    break;
  case 'displayTags':
    displayTags();
    break;
  case 'statistics':
    statistics();
    break;
  case 'populateAutoComplete':
    populateAutoComplete();
    break;
  case 'caChangeLog':
    caChangeLog();
    break;
  case 'get_categories':
    get_categories();
    break;
  case 'getPopupDescription':
    getPopupDescription();
    break;
  case 'getRepoDescription':
    getRepoDescription();
    break;
  case 'createXML':
    createXML();
    break;
  case 'switchLanguage':
    switchLanguage();
    break;
  case 'remove_multiApplications':
    remove_multiApplications();
    break;
  case 'getCategoriesPresent':
    getCategoriesPresent();
    break;
  case 'toggleFavourite':
    toggleFavourite();
    break;
  case 'getFavourite':
    getFavourite();
    break;
  case 'changeSortOrder':
    changeSortOrder();
    break;
  case 'getSortOrder':
    getSortOrder();
    break;
  case 'defaultSortOrder':
    defaultSortOrder();
    break;
  case 'javascriptError':
    javascriptError();
    break;
  case 'onStartupScreen':
    onStartupScreen();
    break;
  case 'convert_docker':
    convert_docker();
    break;
  case 'search_dockerhub':
    search_dockerhub();
    break;
  case 'getPortsInUse':
    postReturn(["portsInUse"=>getPortsInUse()]);
    break;
  case 'getLastUpdate':
    postReturn(['lastUpdate'=>getLastUpdate(getPost("ID","Unknown"))]);
    break;
  case 'changeMaxPerPage':
    changeMaxPerPage();
    break;
  case 'enableActionCentre':
    enableActionCentre();
    break;
  case 'var_dump':
    break;
  case 'checkRequirements':
    checkRequirements();
    break;
  case 'saveMultiPluginPending':
    saveMultiPluginPending();
    break;
  case 'downloadStatistics':
    downloadStatistics();
    break;
  case 'checkPluginInProgress':
    checkPluginInProgress();
    break;
  ###############################################
  # Return an error if the action doesn't exist #
  ###############################################
  default:
    postReturn(["error"=>"Unknown post action {$_POST['action']}"]);
    break;
}
#  DownloadApplicationFeed MUST BE CALLED prior to DownloadCommunityTemplates in order for private repositories to be merged correctly.

function DownloadApplicationFeed() {
  global $caPaths, $caSettings, $statistics;

  $info = readJsonFile($caPaths['info']);
  exec("rm -rf '{$caPaths['tempFiles']}'");
  @mkdir($caPaths['templates-community'],0777,true);

  $currentFeed = "Primary Server";
  $downloadURL = randomFile();
  $ApplicationFeed = download_json($caPaths['application-feed'],$downloadURL,"",20);
  if ( (! is_array($ApplicationFeed['applist'])) || empty($ApplicationFeed['applist']) ) {
    $currentFeed = "Backup Server";
    $ApplicationFeed = download_json($caPaths['application-feedBackup'],$downloadURL);
  }
  @unlink($downloadURL);
  if ( (! is_array($ApplicationFeed['applist'])) || empty($ApplicationFeed['applist']) ) {
    @unlink($caPaths['currentServer']);
    ca_file_put_contents($caPaths['appFeedDownloadError'],$downloadURL);
    return false;
  }
  ca_file_put_contents($caPaths['currentServer'],$currentFeed);
  $i = 0;
  $lastUpdated['last_updated_timestamp'] = $ApplicationFeed['last_updated_timestamp'];
  writeJsonFile($caPaths['lastUpdated-old'],$lastUpdated);

  $invalidXML = [];
  foreach ($ApplicationFeed['applist'] as $o) {
    if ( (! isset($o['Repository']) ) && (! isset($o['Plugin']) ) && (!isset($o['Language']) )){
      $invalidXML[] = $o;
      continue;
    }
    $o = addMissingVars($o);

    if ( $o['CategoryList'] ) {
      foreach ($o['CategoryList'] as $cat) {
        $cat = str_replace("-",":",$cat);
        if ( ! strpos($cat,":") )
          $cat .= ":";
        $o['Category'] .= "$cat ";
      }
    }
    if ( $o['Category'] === null )
      $o['Category'] = "";
    $o['Category'] = trim($o['Category']);
    if ( ! $o['Category'] )
      $o['Category'] = "Other:";

    if ( $o['RecommendedRaw'] ) {
      $o['RecommendedDate'] = strtotime($o['RecommendedRaw']);
      $o['Category'] .= " spotlight:";
    }

    if ( $o['Language'] ) {
      $o['Category'] = "Language:";
      $o['Compatible'] = true;
      $o['Repository'] = "library/";
    }

    # Move the appropriate stuff over into a CA data file
    $o['ID']            = $i;
    $o['Displayable']   = true;
    $o['Author']        = getAuthor($o);
    $o['DockerHubName'] = strtolower($o['Name']);
    $o['RepoName']      = $o['Repo'];
    $o['SortAuthor']    = $o['Author'];
    $o['SortName']      = str_replace("-"," ",$o['Name']);
    $o['SortName']      = preg_replace('/\s+/',' ',$o['SortName']);
    $o['random']        = rand();

    if ( $o['CAComment'] ) {
        $tmpComment = explode("&zwj;",$o['CAComment']);  // non printable delimiter character
        $o['CAComment'] = "";
        foreach ($tmpComment as $comment) {
          if ( $comment )
            $o['CAComment'] .= tr($comment)."  ";
        }
    }
    if ( $o['RequiresFile'] ) $o['RequiresFile'] = trim($o['RequiresFile']);
    if ( $o['Requires'] ) 		$o['Requires'] = trim($o['Requires']);

    $des = $o['OriginalOverview'] ?? $o['Overview'];
    $des = $o['Language'] ? $o['Description'] : $des;
    if ( ! $des && $o['Description'] ) $des = $o['Description'];
    if ( ! $o['Language'] ) {
      $des = str_replace(["[","]"],["<",">"],$des);
      $des = str_replace("\n","  ",$des);
      $des = html_entity_decode($des);
    }

    if ( $o['PluginURL'] ) {
      $o['Author']        = $o['PluginAuthor'];
      $o['Repository']    = $o['PluginURL'];
    }

    $o['Blacklist'] = $o['CABlacklist'] ? true : $o['Blacklist'];
    $o['MinVer'] = max([$o['MinVer'],$o['UpdateMinVer']]);
    $tag = explode(":",$o['Repository']);
    if (! isset($tag[1]))
      $tag[1] = "latest";
    $o['Path'] = $caPaths['templates-community']."/".alphaNumeric($o['RepoName'])."/".alphaNumeric($o['Author'])."-".alphaNumeric($o['Name'])."-{$tag[1]}";
    if ( file_exists($o['Path'].".xml") ) {
      $o['Path'] .= "(1)";
    }
    $o['Path'] .= ".xml";

    $o = fixTemplates($o);
    if ( ! $o ) continue;

    if ( is_array($o['trends']) && count($o['trends']) > 1 ) {
      $o['trendDelta'] = round(end($o['trends']) - $o['trends'][0],4);
      $o['trendAverage'] = round(array_sum($o['trends'])/count($o['trends']),4);
    }

    $o['Category'] = str_replace("Status:Beta","",$o['Category']);    # undo changes LT made to my xml schema for no good reason
    $o['Category'] = str_replace("Status:Stable","",$o['Category']);
    $myTemplates[$i] = $o;

    if ( ! $o['DonateText'] && ($ApplicationFeed['repositories'][$o['RepoName']]['DonateText'] ?? false) )
      $o['DonateText'] = $ApplicationFeed['repositories'][$o['RepoName']]['DonateText'];
    if ( ! $o['DonateLink'] && ($ApplicationFeed['repositories'][$o['RepoName']]['DonateLink'] ?? false) )
      $o['DonateLink'] = $ApplicationFeed['repositories'][$o['RepoName']]['DonateLink'];

    $ApplicationFeed['repositories'][$o['RepoName']]['downloads'] = $ApplicationFeed['repositories'][$o['RepoName']]['downloads'] ?? 0;
    $ApplicationFeed['repositories'][$o['RepoName']]['trending'] = $ApplicationFeed['repositories'][$o['RepoName']]['trending'] ?? 0;

    $ApplicationFeed['repositories'][$o['RepoName']]['downloads']++;
    $ApplicationFeed['repositories'][$o['RepoName']]['trending'] += $o['trending'];
    if ( ! $o['ModeratorComment'] == "Duplicated Template" ) {
      if ( $ApplicationFeed['repositories'][$o['RepoName']]['FirstSeen'] ?? false) {
        if ( $o['FirstSeen'] < $ApplicationFeed['repositories'][$o['RepoName']]['FirstSeen'])
          $ApplicationFeed['repositories'][$o['RepoName']]['FirstSeen'] = $o['FirstSeen'];
      } else {
        $ApplicationFeed['repositories'][$o['RepoName']]['FirstSeen'] = $o['FirstSeen'];
      }
    }
    if ( is_array($o['Branch']) ) {
      if ( ! isset($o['Branch'][0]) ) {
        $tmp = $o['Branch'];
        unset($o['Branch']);
        $o['Branch'][] = $tmp;
      }
      foreach($o['Branch'] as $branch) {
        $i = ++$i;
        $subBranch = $o;
        $masterRepository = explode(":",$subBranch['Repository']);
        $o['BranchDefault'] = $masterRepository[1] ?? null;
        $subBranch['Repository'] = $masterRepository[0].":". ($branch['Tag'] ?? ""); #This takes place before any xml elements are overwritten by additional entries in the branch, so you can actually change the repo the app draws from
        $subBranch['BranchName'] = $branch['Tag'] ?? "";
        $subBranch['BranchDescription'] = $branch['TagDescription'] ? $branch['TagDescription'] : $branch['Tag'];
        $subBranch['Path'] = $caPaths['templates-community']."/".$i.".xml";
        $subBranch['Displayable'] = false;
        $subBranch['ID'] = $i;
        $subBranch['Overview'] = $o['OriginalOverview'] ?: $o['Overview'];
        $subBranch['Description'] = $o['OriginalDescription'] ?: $o['Description'];
        $replaceKeys = array_diff(array_keys($branch),["Tag","TagDescription"]);
        foreach ($replaceKeys as $key) {
          $subBranch[$key] = $branch[$key];
        }
        unset($subBranch['Branch']);
        $myTemplates[$i] = $subBranch;
        $o['BranchID'][] = $i;
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
  }

  if ( $invalidXML )
    writeJsonFile($caPaths['invalidXML_txt'],$invalidXML);
  else
    @unlink($caPaths['invalidXML_txt']);

  writeJsonFile($caPaths['community-templates-info'],$myTemplates);
  $GLOBALS['templates'] = $myTemplates;
  writeJsonFile($caPaths['categoryList'],$ApplicationFeed['categories']);

  foreach ($ApplicationFeed['repositories'] as &$repo) {
    if ( $repo['downloads'] ?? false ) {
      $repo['trending'] = $repo['trending'] / $repo['downloads'];
    }
  }

  writeJsonFile($caPaths['repositoryList'],$ApplicationFeed['repositories']);
  writeJsonFile($caPaths['extraBlacklist'],$ApplicationFeed['blacklisted']);
  writeJsonFile($caPaths['extraDeprecated'],$ApplicationFeed['deprecated']);

  updatePluginSupport($myTemplates);
  touch($caPaths['haveTemplates']);

  return true;
}

function updatePluginSupport($templates) {
  $plugins = glob("/boot/config/plugins/*.plg");

  foreach ($plugins as $plugin) {
    $pluginURL = @plugin("pluginURL",$plugin);
    $pluginEntry = searchArray($templates,"PluginURL",$pluginURL);
    if ( $pluginEntry === false ) {
      $pluginEntry = searchArray($templates,"PluginURL",str_replace("https://raw.github.com/","https://raw.githubusercontent.com/",$pluginURL));
    }
    if ( $pluginEntry !== false && $templates[$pluginEntry]['PluginURL']) {
      $xml = simplexml_load_file($plugin);
      if ( ! $templates[$pluginEntry]['Support'] ) {
        continue;
      }
      if ( @plugin("support",$plugin) !== $templates[$pluginEntry]['Support'] ) {
        // remove existing support attribute if it exists
        if ( @plugin("support",$plugin) ) {
          $existing_support = $xml->xpath("//PLUGIN/@support");
          foreach ($existing_support as $node) {
            unset($node[0]);
          }
        }
        $xml->addAttribute("support",$templates[$pluginEntry]['Support']);
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        ca_file_put_contents($plugin, $dom->saveXML());
      }
    }
  }
}

function getConvertedTemplates() {
  global $caPaths, $caSettings, $statistics;

# Start by removing any pre-existing private (converted templates)
  $templates = &$GLOBALS['templates'];

  if ( empty($templates) ) return false;

  $myTemplates = [];
  foreach ($templates as $template) {
    if ( ! $template['Private'] )
      $myTemplates[] = $template;
  }
  $appCount = count($myTemplates);
  $i = $appCount;

  if ( ! is_dir($caPaths['convertedTemplates']) ) {
    writeJsonFile($caPaths['community-templates-info'],$myTemplates);
    $GLOBALS['templates'] = $myTemplates;
    return;
  }

  $privateTemplates = glob($caPaths['convertedTemplates']."*/*.xml");
  foreach ($privateTemplates as $templateXML) {
    $o = addMissingVars(readXmlFile($templateXML));
    if ( ! $o['Repository'] ) continue;

    $o['Private']      = true;
    $o['RepoName']     = basename(pathinfo($templateXML,PATHINFO_DIRNAME))." Repository";
    $o['ID']           = $i;
    $o['Displayable']  = true;
    $o['Date']         = ( $o['Date'] ) ? strtotime( $o['Date'] ) : 0;
    $o['SortAuthor']   = $o['Author'];
    $o['Compatible']   = versionCheck($o);
    $o['Description']  = $o['Description'] ?: $o['Overview'];
    $o['CardDescription'] = strip_tags(trim(markdown($o['Description'])));
    $o = fixTemplates($o);
    $myTemplates[$i]  = $o;
    $i = ++$i;
  }
  writeJsonFile($caPaths['community-templates-info'],$myTemplates);
  $GLOBALS['templates'] = $myTemplates;
}

#############################
# Selects an app of the day #
#############################
function appOfDay($file) {
  global $caPaths,$caSettings,$sortOrder,$dynamixSettings;

  $max = ( is_file("/boot/config/plugins/unlimited-width.plg") || ($dynamixSettings['width'] ?? false) ) ? 12 : 5;
  $appOfDay = null;

  switch ($caSettings['startup']) {
    case "random":
      $oldAppDay = @filemtime($caPaths['appOfTheDay']);
      $oldAppDay = $oldAppDay ?: 1;
      $oldAppDay = intval($oldAppDay / 86400);
      $currentDay = intval(time() / 86400);
      if ( $oldAppDay == $currentDay ) {
        $appOfDay = readJsonFile($caPaths['appOfTheDay']);
        $flag = false;
        foreach ($appOfDay as $testApp) {
          if ( ! checkRandomApp($file[$testApp]) ) {
            $flag = true;
            break;
          }
        }
        if ( $flag )
          $appOfDay = null;
      }
      if ( ! $appOfDay ) {
        shuffle($file);
        foreach ($file as $template) {
          if ( ! checkRandomApp($template) ) continue;
          $appOfDay[] = $template['ID'];
          if (count($appOfDay) == $max) break;
        }
      }
      writeJsonFile($caPaths['appOfTheDay'],$appOfDay);

      break;
    case "onlynew":
      $sortOrder['sortBy'] = "FirstSeen";
      $sortOrder['sortDir'] = "Down";
      usort($file,"mySort");
      foreach ($file as $template) {
        if ( ! $template['Compatible'] == "true" && $caSettings['hideIncompatible'] == "true" ) continue;
        if ( $template['FirstSeen'] > 1538357652 ) {
          if ( checkRandomApp($template) ) {
            $appOfDay[] = $template['ID'];
            if ( count($appOfDay) == $max ) break;
          }
        }
      }
      break;
    case "topperforming":
      $sortOrder['sortBy'] = "trending";
      $sortOrder['sortDir'] = "Down";
      usort($file,"mySort");
      $repos = [];
      foreach ($file as $template) {
        if ( ! is_array($template['trends']) ) continue;
        if ( count($template['trends']) < 6 ) continue;
        if ( startsWith($template['Repository'],"ich777/steamcmd") ) continue; // because a ton of apps all use the same repo
        if ( $template['trending'] && ($template['downloads'] > 100000) ) {
          if ( checkRandomApp($template) ) {
            if ( in_array($template['Repository'],$repos) )
              continue;
            $repos[] = $template['Repository'];
            $appOfDay[] = $template['ID'];
            if ( count($appOfDay) == $max ) break;
          }
        }
      }
      break;
    case "trending":
      $sortOrder['sortBy'] = "trendDelta";
      $sortOrder['sortDir'] = "Down";
      usort($file,"mySort");
      $repos = [];
      foreach ($file as $template) {
        if ( count($template['trends'] ) < 3 ) continue;
        if ( startsWith($template['Repository'],"ich777/steamcmd") ) continue; // because a ton of apps all use the same repo`
        if ( $template['trending'] && ($template['downloads'] > 10000) ) {
          if ( checkRandomApp($template) ) {
            if ( in_array($template['Repository'],$repos) )
              continue;
            $repos[] = $template['Repository'];
            $appOfDay[] = $template['ID'];
            if ( count($appOfDay) == $max ) break;
          }
        }
      }
      break;
    case "spotlight":
      $sortOrder['sortBy'] = "RecommendedDate";
      $sortOrder['sortDir'] = "Down";
      usort($file,"mySort");
      foreach($file as $template) {
        if ($template['RecommendedDate']) {
          if ( ! checkRandomApp($template) ) continue;

          $appOfDay[] = $template['ID'];
          if ( count($appOfDay) == $max ) break;
        } else {
          break;
        }
      }
      break;
    case "featured":
      $containers = getAllInfo();
      $sortOrder['sortBy'] = "Featured";
      $sortOrder['sortDir'] = "Down";
      usort($file,"mySort");
      foreach($file as $template) {
        if ( ! isset($template['Featured'] ) )
          break;
          // Don't show it if the plugin is installed

        if ( $template['PluginURL'] && is_file("/var/log/plugins/".basename($template['PluginURL'])) ) {
          if ( checkPluginUpdate($template['PluginURL']) ) {
            $appOfDay[] = $template['ID'];
            if ( count($appOfDay) == $max )
              break;
            continue;
          }
        }
        if ( $template['PluginURL'] && is_file("/var/log/plugins/".basename($template['PluginURL'])) && ! $template['UninstallOnly'])
          continue;
        // Don't show it if the container is installed
        if ( ! $template['PluginURL'] ) {
          if ( $caSettings['dockerRunning'] ) {
            $selected = false;

            foreach ($containers as $testDocker) {
              if ( ($template['Repository'] == $testDocker['Image'] ) || ($template['Repository'].":latest" == $testDocker['Image']) || (str_replace(":latest","",$template['Repository']) == $testDocker['Image']) ) {
                $selected = true;
                break;
              }
            }
          }
          if ( $selected )
            continue;
        }
        $appOfDay[] = $template['ID'];
        if ( count($appOfDay) == $max ) break;
      }
  }
  return $appOfDay ?: [];
}

#####################################################
# Checks selected app for eligibility as app of day #
#####################################################
function checkRandomApp($test) {
  global $caSettings;

  if ( $test['Name'] == "Community Applications" )  return false;
  if ( $test['BranchName'] )                        return false;
  if ( ! $test['Displayable'] )                     return false;
  if ( ! $test['Compatible'] && $caSettings['hideIncompatible'] == "true" ) return false;
  if ( $test['Blacklist'] )                         return false;
  if ( $test['Deprecated'] && ( $caSettings['hideDeprecated'] == "true" ) ) return false;

  return true;
}
##############################################################
# Gets the repositories that are listed on any given display #
##############################################################
function displayRepositories() {
  global $caPaths, $caSettings;

  $repositories = readJsonFile($caPaths['repositoryList']);
  if ( is_file($caPaths['community-templates-allSearchResults']) ) {
    $temp = readJsonFile($caPaths['community-templates-allSearchResults']);
    $templates = &$temp['community'];
  } else {
    $temp = readJsonFile($caPaths['community-templates-displayed']);
    $templates = &$temp['community'];
  }
  if ( is_file($caPaths['startupDisplayed']) ) {
    $templates = &$GLOBALS['templates'];
  }
  $templates = $templates ?: [];
  $allRepos = [];
  $bio = [];
  foreach ($templates as $template) {
    if ( $template['Blacklist'] ) continue;
    if ( $template['Deprecated'] && $caSettings['hideDeprecated'] == "true" ) continue;
    if ( ! $template['Compatible'] && $caSettings['hideIncompatible'] == "true" ) continue;
    $repoName = $template['RepoName'];
    if ( ! $repoName ) continue;
    if ( $repoName == $caSettings['favourite'] ) {
      $fav = $repositories[$repoName];
      $fav['RepositoryTemplate'] = true;
      $fav['RepoName'] = $repoName;
      $fav['SortName'] = $repoName;
    } else {
      if ( isset($repositories[$repoName]['bio']) ) {
        $bio[$repoName] = $repositories[$repoName];
        $bio[$repoName] = $repositories[$repoName];
        $bio[$repoName]['RepositoryTemplate'] = true;
        $bio[$repoName]['RepoName'] = $repoName;
        $bio[$repoName]['SortName'] = $repoName;
        $bio[$repoName] = addMissingVars($bio[$repoName]);
      } else {
        $allRepos[$repoName] = $repositories[$repoName];
        $allRepos[$repoName]['RepositoryTemplate'] = true;
        $allRepos[$repoName]['RepoName'] = $repoName;
        $allRepos[$repoName]['SortName'] = $repoName;
        $allRepos[$repoName] = addMissingVars($allRepos[$repoName]);
      }
    }
  }
  usort($bio,"mySort");
  usort($allRepos,"mySort");
  $allRepos = array_merge($bio,$allRepos);
  if ( isset($fav) )
    array_unshift($allRepos,$fav);
  $file['community'] = $allRepos;
  writeJsonFile($caPaths['repositoriesDisplayed'],$file);
}

######################################################################################
# get_content - get the results from templates according to categories, filters, etc #
######################################################################################
function get_content() {
  global $caPaths, $caSettings;

  $filter      = getPost("filter",false);
  $category    = getPost("category",false);
  $newApp      = filter_var(getPost("newApp",false),FILTER_VALIDATE_BOOLEAN);

  $caSettings['startup'] = getPost("startupDisplay",false);
  @unlink($caPaths['repositoriesDisplayed']);
  @unlink($caPaths['dockerSearchActive']);

  $noInstallComment = "";
  $displayBlacklisted = false;
  $displayDeprecated = false;
  $displayIncompatible = false;
  $displayPrivates = false;
  switch ($category) {
    case "PRIVATE":
      $category = false;
      $displayPrivates = true;
      break;
    case "DEPRECATED":
      $category = false;
      $displayDeprecated = true;
      $noInstallComment = tr("Deprecated Applications are able to still be installed if you have previously had them installed. New installations of these applications are blocked unless you enable Display Deprecated Applications within CA's General Settings")."<br><br>";
      break;
    case "BLACKLIST":
      $category = false;
      $displayBlacklisted = true;
      $noInstallComment = tr("The following applications are blacklisted.  CA will never allow you to install or reinstall these applications")."<br><br>";
      break;
    case "INCOMPATIBLE":
      $category = false;
      $displayIncompatible = true;
      $noInstallComment = tr("While highly not recommended to do, incompatible applications can be installed by enabling Display Incompatible Applications within CA's General Settings")."<br><br>";
      break;
    case "repos":
      postReturn(displayRepositories());
      break;
    case "":
      $category = false;
      break;
  }
  $category = $category ? "/$category/i" : false;

  if ( strpos($category,":") && $filter ) {
    $disp = readJsonFile($caPaths['community-templates-allSearchResults']);
    $file = &$disp['community'];
  } else
    $file = &$GLOBALS['templates'];

  if ( empty($file)) return;

  if ( !$filter && $category === "/NONE/i" ) {
    getConvertedTemplates();  // Only scan for private XMLs when going HOME

    ca_file_put_contents($caPaths['startupDisplayed'],"startup");
    $displayApplications = [];
    $displayApplications['community'] = [];
    if ( count($file) > 200) {
      $startupTypes = [
        [
          "type"=>"featured",
          "text1"=>tr("Featured Applications"),
          "text2"=>"",
          "sortby"=>"Name",
          "sortdir"=>"Up"
        ],
        [
          "type"=>"onlynew",
          "text1"=>tr("Recently Added"),
          "text2"=>tr("Check out these newly added applications from our awesome community"),
          "cat"=>"All",
          "sortby"=>"FirstSeen",
          "sortdir"=>"Down"
        ],
        [
          "type"=>"spotlight",
          "text1"=>tr("Spotlight Apps"),
          "text2"=>tr("Each month we highlight some of the amazing work from our community"),
          "cat"=>"spotlight:",
          "sortby"=> "RecommendedDate",
          "sortdir"=> "Down",
        ],
        [
          "type"=>"trending",
          "text1"=>tr("Top Trending Apps"),
          "text2"=>tr("Check out these up and coming apps"),
          "cat"=>"All",
          "sortby"=>"topTrending",
          "sortdir"=>"Down"
        ],
        [
          "type"=>"topperforming",
          "text1"=>tr("Top New Installs"),
          "text2"=>tr("These apps have the highest percentage of new installs"),
          "cat"=>"All",
          "sortby"=>"topPerforming",
          "sortdir"=>"Down"
        ],
        [
          "type"=>"random",
          "text1"=>tr("Random Apps"),
          "text2"=>tr("An assortment of randomly chosen apps"),
          "cat"=>"All",
          "sortby"=>"random",
          "sortdir"=>"Down"
        ]
      ];
      $o['display'] = "";
      foreach ($startupTypes as $type) {
        $display = [];

        $caSettings['startup'] = $type['type'];
        $appsOfDay = appOfDay($file);

        if ( ! $appsOfDay || empty($appsOfDay) )
          continue;

        for ($i=0;$i<$caSettings['maxPerPage'];$i++) {
          if ( ! isset($appsOfDay[$i])) continue;
          $file[$appsOfDay[$i]]['NewApp'] = ($caSettings['startup'] != "random");
          $spot = $file[$appsOfDay[$i]];
          $spot['homeScreen'] = true;
          $displayApplications['community'][] = $spot;
          $display[] = $spot;
        }
        if ( $displayApplications['community'] ) {
          $o['display'] .= "<div class='ca_homeTemplatesHeader'>{$type['text1']}</div>";
          $o['display'] .= "<div class='ca_homeTemplatesLine2'>{$type['text2']} ";
          if ( $type['cat'] ?? false )
            $o['display'] .= "<span class='homeMore' data-des='{$type['text1']}' data-category='{$type['cat']}' data-sortby='{$type['sortby']}' data-sortdir='{$type['sortdir']}'>".tr("SHOW MORE");
          $o['display'] .= "</div>";
          $homeClass = "caHomeSpotlight";

          $o['display'] .= "<div class='ca_homeTemplates $homeClass'>".my_display_apps($display,"1")."</div>";
          $o['script'] = "$('#templateSortButtons,#sortButtons,.maxPerPage').hide();$('.ca_holder').addClass('mobileHolderFix');";

        } else {
          switch ($caSettings['startup']) {
            case "onlynew":
              $startupType = "New"; break;
            case "new":
              $startupType = "Updated"; break;
            case "trending":
              $startupType = "Top Performing"; break;
            case "random":
              $startupType = "Random"; break;
            case "upandcoming":
              $startupType = "Trending"; break;
            case "featured":
              $startupType = "Featured"; break;
          }

          $o['display'] .=  "<br><div class='ca_center'><font size='4' color='purple'><span class='ca_bold'>".sprintf(tr("An error occurred.  Could not find any %s Apps"),$startupType)."</span></font><br><br>";
          $o['script'] = "$('#templateSortButtons,#sortButtons,.maxPerPage').hide();";

          writeJsonFile($caPaths['community-templates-displayed'],$displayApplications);
          postReturn($o);
          return;
        }
      }
      @unlink($caPaths['community-templates-allSearchResults']);
      @unlink($caPaths['community-templates-catSearchResults']);
      writeJsonFile($caPaths['community-templates-displayed'],$displayApplications);
      postReturn($o);
      return;
    }
  } else {
    @unlink($caPaths['startupDisplayed']);
  }
  $display  = [];
  $official = [];

  foreach ($file as $template) {
    $template['NoInstall'] = $noInstallComment;

    if ( $displayBlacklisted ) {
      if ( $template['Blacklist'] ) {
        $display[] = $template;
        continue;
      } else continue;
    }

    if ( $displayIncompatible) {
      if ( ! $template['Compatible'] && $displayIncompatible) {
        $display[] = $template;
        continue;
      } else continue;
    }
    if ( $template['Deprecated'] && $displayDeprecated && ! $template['Blacklist']) {
      if ( ! ($template['BranchID']??false) )
        $display[] = $template;
      continue;
    }
    if ( ($caSettings['hideDeprecated'] == "true") && ($template['Deprecated'] && ! $displayDeprecated) ) continue;
    if ( $displayDeprecated && ! $template['Deprecated'] ) continue;
    if ( ! $template['Displayable'] ) continue;
    if ( $caSettings['hideIncompatible'] == "true" && ! $template['Compatible'] && ! $displayIncompatible  && ! $template['Featured']) continue;
    if ( $template['Blacklist'] ) continue;

    $name = $template['Name'];

    if ( $template['Plugin'] && file_exists("/var/log/plugins/".basename($template['PluginURL'])) )
      $template['InstallPath'] = $template['PluginURL'];

    $template['NewApp'] = $newApp;

    if ( $category && ! preg_match($category,$template['Category'])) {
      continue;
    }
    if ( $category == "/spotlight:/i" )
      $template['class'] = "spotlightHome";

    if ( $displayPrivates && ! $template['Private'] ) continue;

    if ($filter) {
      # Can't be done at appfeed download time because the translation may or may not exist if the user switches languages
      foreach (explode(" ",$template['Category']) as $trCat) {
        $template['translatedCategories'] .= tr($trCat)." ";
      }
      if ( endsWith($filter," Repository") && $template['RepoName'] !== $filter) {
        continue;
      }
      if ( filterMatch($filter,[$template['SortName']]) && $caSettings['favourite'] == $template['RepoName']) {
        $searchResults['favNameHit'][] = $template;
        continue;
      }
      if ( strpos($filter,"/") && filterMatch($filter,[$template['Repository']]) )
        $searchResults['nameHit'][] = $template;
      else {
        if ( filterMatch($filter,[$template['SortName'],$template['RepoShort'],$template['Language'],$template['LanguageLocal'],$template['ExtraSearchTerms']]) ) {
          if ( filterMatch($filter,[$template['ExtraSearchTerms']]) && $template['ExtraPriority'] )
            $searchResults['extraHit'][] = $template;
          else
            $searchResults['nameHit'][] = $template;
        } elseif ( filterMatch($filter,[$template['Author'],$template['RepoName'],$template['Overview'],$template['translatedCategories']]) ) {
          if ( $template['RepoName'] == $caSettings['favourite'] ) {
            $searchResults['nameHit'][] = $template;
          } else {
            $searchResults['anyHit'][] = $template;
          }
        } else continue;
      }
    }
    $display[] = $template;
  }
  if ( $filter ) {
    if ( isset($searchResults['nameHit']) ) {
      usort($searchResults['nameHit'],"mySort");
      if ( ! strpos($filter," Repository") ) {
        if ( $caSettings['favourite'] && $caSettings['favourite'] !== "none" ) {
          usort($searchResults['nameHit'],"favouriteSort");
        }
      }
    }
    else
      $searchResults['nameHit'] = [];

    if ( isset($searchResults['anyHit']) ) {
      usort($searchResults['anyHit'],"mySort");
    }
    else
      $searchResults['anyHit'] = [];
    if ( isset($searchResults['favNameHit']) )
      usort($searchResults['favNameHit'],"mySort");
    else
      $searchResults['favNameHit'] = [];

    if ( isset($searchResults['extraHit']) )
      usort($searchResults['extraHit'],"mySort");
    else
      $searchResults['extraHit'] = [];

    $displayApplications['community'] = array_merge($searchResults['extraHit'],$searchResults['favNameHit'],$searchResults['nameHit'],$searchResults['anyHit']);
  } else {
    usort($display,"mySort");
    $displayApplications['community'] = $display;
  }
  if ( ! $category && $filter ) {
    writeJsonFile($caPaths['community-templates-allSearchResults'],$displayApplications);
    writeJsonFile($caPaths['community-templates-catSearchResults'],$displayApplications);
  }
  if ( $category && $filter) {
    writeJsonFile($caPaths['community-templates-catSearchResults'],$displayApplications);
  }
  if ( ! $filter ) {
    writeJsonFile($caPaths['community-templates-displayed'],$displayApplications);
    @unlink($caPaths['community-templates-allsearchResults']);
    @unlink($caPaths['community-templates-catSearchResults']);
  }
  $o['display'] = "<div class='ca_templatesDisplay'>".display_apps()."</div>";

  postReturn($o);
}

########################################################
# force_update -> forces an update of the applications #
########################################################
function force_update() {
  global $caPaths;

  $lastUpdatedOld = readJsonFile($caPaths['lastUpdated-old']);
  debug("old feed timestamp: ".($lastUpdatedOld['last_updated_timestamp'] ?? ""));
  @unlink($caPaths['lastUpdated']);
  $latestUpdate = download_json($caPaths['application-feed-last-updated'],$caPaths['lastUpdated'],"",5);
  if ( ! $latestUpdate['last_updated_timestamp'] ?? false )
    $latestUpdate = download_json($caPaths['application-feed-last-updatedBackup'],$caPaths['lastUpdated'],"",5);
  debug("new appfeed timestamp: {$latestUpdate['last_updated_timestamp']}");
  if ( ! isset($latestUpdate['last_updated_timestamp']) ) {
    $latestUpdate['last_updated_timestamp'] = INF;
    $badDownload = true;
    @unlink($caPaths['lastUpdated']);
  }

  if ( ($latestUpdate['last_updated_timestamp'] ?? 0) != ($lastUpdatedOld['last_updated_timestamp'] ?? 0) ) {
    exec("rm -rf '{$caPaths['tempFiles']}'");
    $GLOBALS['templates'] = [];
  }

  if (!file_exists($caPaths['community-templates-info']) || ! $GLOBALS['templates']) {
    $updatedSyncFlag = true;
    if (! DownloadApplicationFeed() ) {
      $o['script'] = "$('.onlyShowWithFeed').hide();";
      if ( checkServerDate() )
        $o['data'] =  "<div class='ca_center'><font size='4'><span class='ca_bold'>".tr("Download of appfeed failed.")."</span></font><font size='3'><br><br>Community Applications requires your server to have internet access.  The most common cause of this failure is a failure to resolve DNS addresses.  You can try and reset your modem and router to fix this issue, or set static DNS addresses (Settings - Network Settings) of 208.67.222.222 and 208.67.220.220 and try again.<br><br>Alternatively, there is also a chance that the server handling the application feed is temporarily down.  See also <a href='https://forums.unraid.net/topic/120220-fix-common-problems-more-information/page/2/?tab=comments#comment-1101084' target='_blank'>this post</a> for more information";
      else 
        $o['data'] =  "<div class='ca_center'><font size='4'><span class='ca_bold'>".tr("Download of appfeed failed.")."</span></font><font size='3'><br><br>Community Applications requires your server to have internet access.  This could be because it appears that the current date and time of your server is incorrect.  Correct this within Settings - Date And Time.  See also <a href='https://forums.unraid.net/topic/120220-fix-common-problems-more-information/page/2/?tab=comments#comment-1101084' target='_blank'>this post</a> for more information";
      
      $tempFile = @file_get_contents($caPaths['appFeedDownloadError']);
      $downloaded = @file_get_contents($tempFile);
      if (strlen($downloaded) > 100)
        $o['data'] .= "<font size='2' color='red'><br><br>It *appears* that a partial download of the application feed happened (or is malformed), therefore it is probable that the application feed is temporarily down.  Please try again later)</font>";

      $o['data'] .=  "<div class='ca_center'>Last JSON error Recorded: ";
      $jsonDecode = json_decode($downloaded,true);
      $o['data'] .= json_last_error_msg();

      $o['data'] .= "</div>";
      @unlink($caPaths['appFeedDownloadError']);
      @unlink($caPaths['community-templates-info']);
      $GLOBALS['templates'] = [];
      postReturn($o);
      return;
    }
  }
  getConvertedTemplates();
  moderateTemplates();
  $currentServer = @file_get_contents($caPaths['currentServer']);

  $appFeedTime = readJsonFile($caPaths['lastUpdated-old']);
  $updateTime = tr(date("F",$appFeedTime['last_updated_timestamp']),0).date(" d, Y @ g:i a",$appFeedTime['last_updated_timestamp']);
  $updateTime = str_replace("'","&apos;",$updateTime);
  postReturn(['status'=>"ok",'script'=>"feedWarning('$currentServer');$('.statistics').attr('title','{$updateTime}');"]);
}


####################################################################################
# display_content - displays the templates according to view mode, sort order, etc #
####################################################################################
function display_content() {
  global $caPaths;

  $pageNumber = getPost("pageNumber","1");
  $startup = getPost("startup",false);
  $selectedApps = json_decode(getPost("selected",false),true);
  $o['display'] = "";
  if ( file_exists($caPaths['community-templates-displayed']) || file_exists($caPaths['repositoriesDisplayed']) ) {
    $o['display'] = "<div class='ca_templatesDisplay'>".display_apps($pageNumber,$selectedApps,$startup)."</div>";
  }

  $displayedApps = readJsonFile($caPaths['community-templates-displayed']);
  $currentServer = @file_get_contents($caPaths['currentServer']);
  $o['script'] = "feedWarning('$currentServer');";
  postReturn($o);
}

#####################################################################
# dismiss_warning - dismisses the warning from appearing at startup #
#####################################################################
function dismiss_warning() {
  global $caPaths;

  ca_file_put_contents($caPaths['warningAccepted'],"warning dismissed");
  postReturn(['status'=>"warning dismissed"]);
}
function dismiss_plugin_warning() {
  global $caPaths;

  ca_file_put_contents($caPaths['pluginWarning'],"disclaimer ok");
  postReturn(['status'=>"disclaimed"]);
}

###############################################################
# Displays the list of installed or previously installed apps #
###############################################################
function previous_apps() {
  global $caPaths, $caSettings, $DockerClient;

  $installed = getPost("installed","");
  $filter = getPost("filter","");
  $info = getAllInfo();

  @unlink($caPaths['community-templates-allSearchResults']);
  @unlink($caPaths['community-templates-catSearchResults']);
  @unlink($caPaths['repositoriesDisplayed']);
  @unlink($caPaths['startupDisplayed']);
  @unlink($caPaths['dockerSearchActive']);

  $file = &$GLOBALS['templates'];
  $extraBlacklist = readJsonFile($caPaths['extraBlacklist']);
  $extraDeprecated = readJsonFile($caPaths['extraDeprecated']);
  $displayed = [];
  $updateCount = 0;

  if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
    $dockerUpdateStatus = readJsonFile($caPaths['dockerUpdateStatus']);
  } else {
    $dockerUpdateStatus = [];
  }


# $info contains all installed containers
# now correlate that to a template;
# this section handles containers that have not been renamed from the appfeed
  if ( $caSettings['dockerRunning'] ) {
    $all_files = glob("{$caPaths['dockerManTemplates']}/*.xml");
    $all_files = $all_files ?: [];
    if ( $installed == "true" || $installed == "action") {
      if ( !$filter || $filter == "docker" ) {
        foreach ($all_files as $xmlfile) {
          $o = readXmlFile($xmlfile);
          $o['Overview'] = fixDescription($o['Overview']);
          $o['Description'] = $o['Overview'];
          $o['CardDescription'] = $o['Overview'];
          $o['InstallPath'] = $xmlfile;
          $o['UnknownCompatible'] = true;
          $containerID = false;

          $runningflag = false;
          foreach ($info as $installedDocker) {
            $installedImage = str_replace("library/","",$installedDocker['Image']);
            $installedName = $installedDocker['Name'];
            if ( $installedName == $o['Name'] ) {
              if ( startsWith($installedImage, $o['Repository']) ) {
                $runningflag = true;
                $searchResult = searchArray($file,'Repository',$o['Repository']);
                if ( $searchResult === false) {
                  $searchResult = searchArray($file,'Repository',explode(":",$o['Repository'])[0]);
                }
                if ( $searchResult !== false ) {
                  $tempPath = $o['InstallPath'];
                  $containerID = $file[$searchResult]['ID'];
                  $tmpOvr = $o['Overview'];
                  $o = $file[$searchResult];
                  $o['Name'] = $installedName;
                  $o['Overview'] = $tmpOvr;
                  $o['CardDescription'] = $tmpOvr;
                  $o['InstallPath'] = $tempPath;
                  $o['SortName'] = str_replace("-"," ",$installedName);
                  if ( $installedName !== $file[$searchResult]['Name'] )
                    $o['NoPin'] = true;  # This is renamed and effectively outside of CA's control
                } else {
                  $runningFlag = true;
                }
                break;
              }
            }
          }
          if ( $runningflag ) {
            $o['Uninstall'] = true;
            $o['ID'] = $containerID;

            if ( $installed == "action" ) {
              $tmpRepo = strpos($o['Repository'],":") ? $o['Repository'] : $o['Repository'].":latest";

              if ( $tmpRepo && ($dockerUpdateStatus[$tmpRepo]['status'] ?? null) == "false" ) {
                $o['actionCentre'] = true;
                $o['updateAvailable'] = true;
                $updateCount++;
              }

              if ( ! $o['Blacklist'] && ! $o['Deprecated'] ) {
                if ( $extraBlacklist[$o['Repository']] ?? false) {
                  $o['Blacklist'] = true;
                  $o['ModeratorComment'] = $extraBlacklist[$o['Repository']];
                }
                if ( $extraDeprecated[$o['Repository']] ?? false ) {
                  $o['Deprecated'] = true;
                  $o['ModeratorComment'] = $extraDeprecated[$o['Deprecated']];
                }
              }

              if ( !$o['Blacklist'] && !$o['Deprecated'] && !$o['actionCentre']  )
                continue;
            }
            if ( $installed == "action" )
              $o['actionCentre'] = true;

            $displayed[] = $o;
          }
        }
      }
    } else {
      if ( ! $filter || $filter == "docker" ) {
    # now get the old not installed docker apps
        foreach ($all_files as $xmlfile) {
          $o = readXmlFile($xmlfile);
          if ( ! $o ) continue;
          $o['Overview'] = fixDescription($o['Overview']);
          $o['Description'] = $o['Overview'];
          $o['CardDescription'] = $o['Overview'];
          $o['InstallPath'] = $xmlfile;
          $o['UnknownCompatible'] = true;
          $o['Removable'] = true;
    # is the container running?

          $flag = false;
          foreach ($info as $installedDocker) {
            $installedImage = $installedDocker['Image'];
            $installedImage = str_replace("library/","",$installedImage);
            $installedName = $installedDocker['Name'];
            if ( startsWith($installedImage, $o['Repository']) ) {
              if ( $installedName == $o['Name'] ) {
                $flag = true;
                continue;
              }
            }
          }
          if ( ! $flag ) {
            $testRepo = explode(":",$o['Repository'])[0];
    # now associate the template back to a template in the appfeed

            foreach ($file as $appTemplate) {
              if (startsWith($appTemplate['Repository'],$testRepo)) {
                $tempPath = $o['InstallPath'];
                $tempName = $o['Name'];
                $tempOvr = $o['Overview'];
                $o = $appTemplate;
                $o['Overview'] = $tempOvr;
                $o['Description'] = $tempOvr;
                $o['CardDescription'] = $tempOvr;
                $o['Removable'] = true;
                $o['InstallPath'] = $tempPath;
                $o['Name'] = $tempName;
                $o['SortName'] = str_replace("-"," ",$o['Name']);
                $o['NoPin'] = true;
                break;
              }
            }

            if ( ! $o['Blacklist'] )
              $displayed[] = $o;
          }
        }
      }
    }
  }
# Now work on plugins
  if ( $installed == "true" || $installed == "action" ) {
    if ( ! $filter || $filter == "plugins" ) {
      foreach ($file as $template) {
        if ( ! $template['Plugin'] ) continue;

        $filename = pathinfo($template['Repository'],PATHINFO_BASENAME);

        if ( checkInstalledPlugin($template) ) {
          $template['InstallPath'] = "/var/log/plugins/$filename";
          $template['Uninstall'] = true;

          if ( $installed == "action" && $template['PluginURL'] && $template['Name'] !== "Community Applications") {
            $installedVersion = plugin("version","/var/log/plugins/$filename");
            if ( ( strcmp($installedVersion,$template['pluginVersion']) < 0 || $template['UpdateAvailable']) ) {
              $template['actionCentre'] = true;
              $template['UpdateAvailable'] = true;
              $updateCount++;
            }
            if ( is_file("/tmp/plugins/$filename") && strcmp($installedVersion,plugin("version","/tmp/plugins/$filename")) < 0 ) {
              $template['actionCentre'] = true;
              $template['UpdateAvailable'] = true;
              $updateCount++;
            }
          }

          if ( $installed == "action" && !$template['Blacklist'] && !$template['Deprecated'] && $template['Compatible'] && !$template['actionCentre'] )
            continue;
          if ( $installed == "action" )
            $template['actionCentre'] = true;
          $displayed[] = $template;
        }
      }
      $installedLanguages = array_diff(scandir($caPaths['languageInstalled']),[".","..","en_US"]);
      foreach ($installedLanguages as $language) {
        $index = searchArray($file,"LanguagePack",$language);
        if ( $index !== false ) {
          $tmpL = $file[$index];
          $tmpL['Uninstall'] = true;

          if ( $installed == "action" ) {
            $tmpL['actionCentre'] = true;
            if ( !languageCheck($tmpL) )
              continue;
            $tmpL['Updated'] = true;
            $updateCount++;
          }

          $displayed[] = $tmpL;
        }
      }
    }
  } else {
    if ( ! $filter || $filter == "plugins" ) {
      $all_plugs = array_merge(glob("/boot/config/plugins-error/*.plg"),glob("/boot/config/plugins-removed/*.plg"));
      foreach ($all_plugs as $oldplug) {
        foreach ($file as $template) {
          if ( basename($oldplug) == basename($template['Repository']) ) {
            if ( ! file_exists("/boot/config/plugins/".basename($oldplug)) ) {
              if ( $template['Blacklist'] || ( ($caSettings['hideIncompatible'] == "true") && (! $template['Compatible']) ) ) continue;
              $oldPlugURL = trim(plugin("pluginURL",$oldplug));
              if ( ! $oldPlugURL )
                continue;
              if ( strtolower(trim($template['PluginURL'])) != strtolower(trim($oldPlugURL)) ) {
                continue;
              }
              $template['Removable'] = true;
              $template['InstallPath'] = $oldplug;
              if ( isset($alreadySeen[$oldPlugURL]) )
                continue;
              $alreadySeen[$oldPlugURL] = true;
              $displayed[] = $template;
              break;
            }
          }
        }
      }
    }
  }
  if ( isset($displayed) && is_array($displayed) ) {
    usort($displayed,"mySort");
  }
  $displayedApplications['community'] = $displayed;
  writeJsonFile($caPaths['community-templates-displayed'],$displayedApplications);
  if ( $installed == "action" && empty($displayed) ) {
    postReturn(['status'=>"ok",'script'=>'$(".actionCentre").hide();$(".startupButton").trigger("click");']);
  } else {
    postReturn(['status'=>"ok",'updateCount'=>$updateCount]);
  }
}

####################################################################################
# Removes an app from the previously installed list (ie: deletes the user template #
####################################################################################
function remove_application() {
  $application = realpath(getPost("application",""));
  if ( ! (strpos($application,"/boot/config") === false) ) {
    if ( pathinfo($application,PATHINFO_EXTENSION) == "xml" || pathinfo($application,PATHINFO_EXTENSION) == "plg" )
      @unlink($application);
  }
  postReturn(['status'=>"ok"]);
}

###################################################################################
# Checks for an update still available (to update display) after update installed #
###################################################################################
function updatePLGstatus() {
  global $caPaths;

  $filename = getPost("filename","");
  $displayed = readJsonFile($caPaths['community-templates-displayed']);
  $superCategories = array_keys($displayed);
  foreach ($superCategories as $category) {
    foreach ($displayed[$category] as $template) {
      if ( strpos($template['PluginURL'],$filename) )
        $template['UpdateAvailable'] = checkPluginUpdate($filename);

      $newDisplayed[$category][] = $template;
    }
  }
  writeJsonFile($caPaths['community-templates-displayed'],$newDisplayed);
  postReturn(['status'=>"ok"]);
}

#######################
# Uninstalls a docker #
#######################
function uninstall_docker() {
  global $DockerClient, $caPaths, $caSettings;
  $application = getPost("application","");

# get the name of the container / image
  $doc = new DOMDocument();
  $doc->load($application);
  $containerName  = stripslashes($doc->getElementsByTagName( "Name" )->item(0)->nodeValue);

  $dockerRunning = $DockerClient->getDockerContainers();
  $container = searchArray($dockerRunning,"Name",$containerName);

  if ( $dockerRunning[$container]['Running'] )
    myStopContainer($dockerRunning[$container]['Id']);

  $DockerClient->removeContainer($containerName,$dockerRunning[$container]['Id']);
  $DockerClient->removeImage($dockerRunning[$container]['ImageId']);
  exec("/usr/bin/docker volume prune");

  $info = getAllInfo(true);

  postReturn(['status'=>"Uninstalled"]);
}

##################################################
# Pins / Unpins an application for later viewing #
##################################################
function pinApp() {
  global $caPaths;

  $repository = getPost("repository","oops");
  $name = getPost("name","oops");
  $pinnedApps = readJsonFile($caPaths['pinnedV2']);
  if (isset($pinnedApps["$repository&$name"]) )
    $pinnedApps["$repository&$name"] = false;
  else
  $pinnedApps["$repository&$name"] = "$repository&$name";
  $pinnedApps = array_filter($pinnedApps);
  writeJsonFile($caPaths['pinnedV2'],$pinnedApps);
  postReturn(['status' => in_array(true,$pinnedApps)]);
}

######################################
# Gets if any apps are pinned or not #
######################################
function areAppsPinned() {
  global $caPaths;

  postReturn(['status' => in_array(true,readJsonFile($caPaths['pinnedV2']))]);
}

####################################
# Displays the pinned applications #
####################################
function pinnedApps() {
  global $caPaths, $caSettings;

  $pinnedApps = readJsonFile($caPaths['pinnedV2']);
  $file = &$GLOBALS['templates'];
  @unlink($caPaths['community-templates-allSearchResults']);
  @unlink($caPaths['community-templates-catSearchResults']);
  @unlink($caPaths['repositoriesDisplayed']);
  @unlink($caPaths['startupDisplayed']);
  @unlink($caPaths['dockerSearchActive']);

  $displayed = [];
  foreach ($pinnedApps as $pinned) {
    $startIndex = 0;
    $search = explode("&",$pinned);
    for ($i=0;$i<10;$i++) {
      $index = searchArray($file,"Repository",$search[0],$startIndex);
      if ( $index === false && (strpos($search[0],"library/") !== false)) {
        $index = searchArray($file,"Repository",str_replace("library/","",$search[0]),$startIndex);
      }

      if ( $index !== false ) {
        if ( $file[$index]['Blacklist'] ) { #This handles things like duplicated templates
          $startIndex = $index + 1;
          continue;
        }
        if ($file[$index]['SortName'] !== $search[1]) {
          $startIndex = $index +1;
          continue;
        }
        if (!$file[$index]['Compatible'] && $caSettings['hideIncompatible'] == "true") {
          $startIndex = $index +1;
          continue;
        }
        $displayed[] = $file[$index];
        break;
      }
    }
  }
  usort($displayed,"mySort");
  if ( empty($displayed) )
    $script = "$('.caPinnedMenu').addClass('caMenuDisabled').removeClass('caMenuEnabled');";
  $displayedApplications['community'] = $displayed;
  $displayedApplications['pinnedFlag']  = true;
  writeJsonFile($caPaths['community-templates-displayed'],$displayedApplications);
  postReturn(["status"=>"ok","script"=>$script ?? ""]);
}

################################################
# Displays the possible branch tags for an app #
################################################
function displayTags() {
  $leadTemplate = getPost("leadTemplate","oops");
  $rename = getPost("rename","false");
  postReturn(['tags'=>formatTags($leadTemplate,$rename)]);
}

###########################################
# Displays The Statistics For The Appfeed #
###########################################
function statistics() {
  global $caPaths, $caSettings;

  @unlink($caPaths['community-templates-displayed']);
  @unlink($caPaths['community-templates-allSearchResults']);
  @unlink($caPaths['community-templates-catSearchResults']);
  if ( ! is_file($caPaths['statistics']) )
    $statistics = download_json($caPaths['statisticsURL'],$caPaths['statistics']);
  else
    $statistics = readJsonFile($caPaths['statistics']);

  download_json($caPaths['moderationURL'],$caPaths['moderation']);
  $statistics['totalModeration'] = count(readJsonFile($caPaths['moderation']));
  $repositories = readJsonFile($caPaths['repositoryList']);
  $templates = &$GLOBALS['templates'];
  pluginDupe();
  $invalidXML = readJsonFile($caPaths['invalidXML_txt']);
  $statistics['blacklist'] = $statistics['plugin'] = $statistics['docker'] = $statistics['private'] = $statistics['totalDeprecated'] = $statistics['totalIncompatible'] = $statistics['official'] = $statistics['invalidXML'] = 0;

  foreach ($templates as $template) {
    if ( ($template['Deprecated']??false) && ! ($template['Blacklist']??false) && ! ($template['BranchID']??false)) $statistics['totalDeprecated']++;

    if ( ! ($template['Compatible']??false) ) $statistics['totalIncompatible']++;

    if ( $template['Blacklist']??false ) $statistics['blacklist']++;

    if ( ($template['Private']??false) && ! ($template['Blacklist']??false)) {
      if ( ! ($caSettings['hideDeprecated'] == 'true' && ($template['Deprecated']??false)) )
        $statistics['private']++;
    }

    if ( ($template['Official']??false) && ! ($template['Blacklist']??false) )
      $statistics['official']++;

    if ( ! ($template['PluginURL']??false) && ! ($template['Repository']??false) )
      $statistics['invalidXML']++;
    else {
      if ( $template['PluginURL'] ?? false)
        $statistics['plugin']++;
      else {
        if ( $template['BranchID'] ?? false) {
          continue;
        } else {
          $statistics['docker']++;
        }
      }
    }
  }
  $statistics['totalApplications'] = $statistics['plugin']+$statistics['docker'];
  if ( $statistics['fixedTemplates'] )
    writeJsonFile($caPaths['fixedTemplates_txt'],$statistics['fixedTemplates']);
  else
    @unlink($caPaths['fixedTemplates_txt']);

  if ( is_file($caPaths['lastUpdated-old']) )
    $appFeedTime = readJsonFile($caPaths['lastUpdated-old']);

  $updateTime = tr(date("F",$appFeedTime['last_updated_timestamp']),0).date(" d, Y @ g:i a",$appFeedTime['last_updated_timestamp']);
  $defaultArray = ['caFixed' => 0,'totalApplications' => 0, 'repository' => 0, 'docker' => 0, 'plugin' => 0, 'invalidXML' => 0, 'blacklist' => 0, 'totalIncompatible' =>0, 'totalDeprecated' => 0, 'totalModeration' => 0, 'private' => 0, 'NoSupport' => 0];
  $statistics = array_merge($defaultArray,$statistics);

  foreach ($statistics as &$stat) {
    if ( ! $stat ) $stat = "0";
  }

  $currentServer = @file_get_contents($caPaths['currentServer']);
  if ( $currentServer != "Primary Server" )
    $currentServer = "<i class='fa fa-exclamation-triangle ca_serverWarning' aria-hidden='true'></i> $currentServer";

  $statistics['invalidXML'] = @count($invalidXML) ?: tr("unknown");
  $statistics['repositories'] = @count($repositories) ?: tr("unknown");

  $o =  "
    <div style='height:auto;overflow:scroll; overflow-x:hidden; overflow-y:hidden;margin:auto;width:700px;'>
      <table style='margin-top:1rem;'>
        <tr style='height:6rem;'>
          <td colspan='2'>
            <div class='ca_center'>
              <i class='fa fa-users' style='font-size:6rem;'></i>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan='2'>
            <div class='ca_center'>
              <font size='5rem;'>Community Applications</font>
            </div>
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            ".tr("Last Change To Application Feed")."
          </td>
          <td class='ca_stat'>
            $updateTime<br>".tr($currentServer)."
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            ".tr("Docker Applications")."
          </td>
          <td class='ca_stat'>
            {$statistics['docker']}
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            ".tr("Plugin Applications")."
          </td>
          <td class='ca_stat'>
            {$statistics['plugin']}
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            ".tr("Templates")."
          </td>
          <td class='ca_stat'>
            {$statistics['totalApplications']}
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            ".tr("Official Containers")."
          </td>
          <td class='ca_stat'>
            {$statistics['official']}
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            <a onclick='showModeration(&quot;Repository&quot;,&quot;".tr("Repositories")."&quot;);' style='cursor:pointer;' class='popUpLink'>".tr("Repositories")."</a>
          </td>
          <td class='ca_stat'>
            {$statistics['repositories']}
          </td>
        </tr>
        ";
  if ($statistics['private']) {
    $o .= "<tr><td class='ca_table'><a class='popUpLink' data-category='PRIVATE' onclick='showSpecialCategory(this);' style='cursor:pointer;'>".tr("Private Docker Applications")."</a></td><td class='ca_stat'>{$statistics['private']}</td></tr>";
  }
  $o .= "
        <tr>
          <td class='ca_table'>
            <a class='popUpLink' onclick='showModeration(&quot;Invalid&quot;,&quot;".tr("Invalid Templates")."&quot;);' style='cursor:pointer'>".tr("Invalid Templates")."</a>
          </td>
          <td class='ca_stat'>
            {$statistics['invalidXML']}
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            <a class='popUpLink' onclick='showModeration(&quot;Fixed&quot;,&quot;".tr("Template Errors")."&quot;);' style='cursor:pointer'>".tr("Template Errors")."</a>
          </td>
          <td class='ca_stat'>
            {$statistics['caFixed']}+
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            <a class='popUpLink' data-category='BLACKLIST' onclick='showSpecialCategory(this);' style='cursor:pointer'>".tr("Blacklisted Apps")."</a>
          </td>
          <td class='ca_stat'>
            {$statistics['blacklist']}
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            <a class='popUpLink' data-category='INCOMPATIBLE' onclick='showSpecialCategory(this);' style='cursor:pointer'>".tr("Incompatible Applications")."</a>
          </td>
          <td class='ca_stat'>
            {$statistics['totalIncompatible']}
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            <a class='popUpLink' data-category='DEPRECATED' onclick='showSpecialCategory(this);' style='cursor:pointer'>".tr("Deprecated Applications")."</a>
          </td>
          <td class='ca_stat'>
            {$statistics['totalDeprecated']}
          </td>
        </tr>
        <tr>
          <td class='ca_table'>
            <a class='popUpLink' onclick='showModeration(&quot;Moderation&quot;,&quot;".tr("Moderation Entries")."&quot;);' style='cursor:pointer'>".tr("Moderation Entries")."</a>
          </td>
          <td class='ca_stat'>
            {$statistics['totalModeration']}+
          </td>
        </tr>
        <tr>
        <td class='ca_table'>
          <a class='popUpLink' href='{$caPaths['application-feed']}' target='_blank'>".tr("Primary Server")."</a> / <a class='popUpLink' href='{$caPaths['application-feedBackup']}' target='_blank'> ".tr("Backup Server")."</a>
        </td>
      </tr>
    </table>
  <div class='ca_center'>
    <a class='popUpLink' href='https://forums.unraid.net/topic/87144-ca-application-policies/' target='_blank'>".tr("Application Policy")."</a>
  </div>";

  postReturn(['statistics'=>$o]);
}

####################################################
# Creates the entries for autocomplete on searches #
####################################################
function populateAutoComplete() {
  global $caPaths, $caSettings;

  $templates = null;
  while ( ! $templates ) {
    $templates = &$GLOBALS['templates'];
    if ( ! $templates )
      sleep(1);
  }
  $autoComplete = array_map(function($x){return str_replace(":","",tr($x['Cat']));},readJsonFile($caPaths['categoryList']));
  foreach ($templates as $template) {
    if ( $template['RepoTemplate'] )
      continue;
    if ( ! $template['Blacklist'] && ! ($template['Deprecated'] && $caSettings['hideDeprecated'] == "true") && ($template['Compatible'] || $caSettings['hideIncompatible'] != "true") || ($template['Featured']??false) ) {
      if ( $template['Language'] && $template['LanguageLocal'] ) {
        $autoComplete[strtolower($template['Language'])] = $template['Language'];
        $autoComplete[strtolower($template['LanguageLocal'])] = $template['LanguageLocal'];
      } else {
        if ( isset($template['Repo']) )
          $autoComplete[$template['Repo']] = $template['Repo'];
      }
      $name = trim(strtolower($template['SortName']));

      $autoComplete[$name] = $name;
      if ( startsWith($autoComplete[$name],"dynamix ") )
        $autoComplete[$name] = str_replace("dynamix ","",$autoComplete[$name]);
      if ( startsWith($autoComplete[$name],"ca ") )
        $autoComplete[$name] = str_replace("ca ","",$autoComplete[$name]);
      if ( startsWith($autoComplete[$name],"binhex ") )
        $autoComplete[$name] = str_replace("binhex ","",$autoComplete[$name]);
      if ( startsWith($autoComplete[$name],"activ ") )
        $autoComplete[$name] = str_replace("activ ","",$autoComplete[$name]);

      if ( ! isset($autoComplete[strtolower($template['Author'])."'s Repository"]) && ! isset($autoComplete[strtolower($template['Author']."' Repository")])) {
        $autoComplete[strtolower($template['Author'])] = $template['Author'];
      }

      if ( $template['ExtraSearchTerms'] ) {
        foreach (explode(" ",$template['ExtraSearchTerms']) as $searchTerm) {
          $searchTerm = str_replace("%20"," ",$searchTerm);
          $autoComplete[strtolower($searchTerm)] = strtolower($searchTerm);
        }
      }
    }
  }
  $autoComplete[tr("language")] = tr("Language");

  postReturn(['autocomplete'=>array_values(array_filter(array_unique($autoComplete)))]);
}

##########################
# Displays the changelog #
##########################
function caChangeLog() {
  $o = "<div style='margin:auto;width:500px;'>";
  $o .= "<div class='ca_center'><font size='4rem'>".tr("Community Applications Changelog")."</font></div><br><br>";
  postReturn(["changelog"=>$o.Markdown(plugin("changes","/var/log/plugins/community.applications.plg"))."<br><br>"]);
}

###############################
# Populates the category list #
###############################
function get_categories() {
  global $caPaths, $sortOrder, $caSettings, $DockerClient, $DockerTemplates;
  $categories = readJsonFile($caPaths['categoryList']);
  if ( ! is_array($categories) || empty($categories) ) {
    $cat = "Category list N/A<br><br>";
    postReturn(['categories'=>$cat]);
    return;
  } else {
    $categories[] = ["Des"=>"Language","Cat"=>"Language:"];

    foreach ($categories as $category) {
      $category['Des'] = tr($category['Des']);
      if ( isset($category['Sub']) && is_array($category['Sub']) ) {
        unset($subCat);
        foreach ($category['Sub'] as $subcategory) {
          $subcategory['Des'] = tr($subcategory['Des']);
          $subCat[] = $subcategory;
        }
        $category['Sub'] = $subCat;
      }
      $newCat[] = $category;
    }
    $sortOrder['sortBy'] = "Des";
    $sortOrder['sortDir'] = "Up";
    usort($newCat,"mySort"); // Sort it alphabetically according to the language.  May not work right in non-roman charsets

    $cat = "";
    foreach ($newCat as $category) {
      $cat .= "<li class='categoryMenu caMenuItem nonDockerSearch' data-category='{$category['Cat']}'>".$category['Des']."</li>";
      if (isset($category['Sub']) && is_array($category['Sub'])) {
        $cat .= "<ul class='subCategory'>";
        foreach($category['Sub'] as $subcategory) {
          $cat .= "<li class='categoryMenu caMenuItem nonDockerSearch' data-category='{$subcategory['Cat']}'>".$subcategory['Des']."</li>";
        }
        $cat .= "</ul>";
      }
    }
    $templates = &$GLOBALS['templates'];
    foreach ($templates as $template) {
      if ($template['Private'] == true && ! $template['Blacklist']) {
        $cat .= "<li class='categoryMenu caMenuItem nonDockerSearch' data-category='PRIVATE'>".tr("Private Apps")."</li>";
        break;
      }
    }
  }
  postReturn(["categories"=>$cat]);
}

##############################
# Get the html for the popup #
##############################
function getPopupDescription() {
  $appNumber = getPost("appPath","");
  postReturn(getPopupDescriptionSkin($appNumber));
}

#################################
# Get the html for a repo popup #
#################################
function getRepoDescription() {
  $repository = html_entity_decode(getPost("repository",""),ENT_QUOTES);
  postReturn(getRepoDescriptionSkin($repository));
}

###########################################
# Creates the XML for a container install #
###########################################
function createXML() {
  global $caPaths, $caSettings;

  $xmlFile = getPost("xml","");
  $type = getPost("type","");
  if ( ! $xmlFile ) {
    postReturn(["error"=>"CreateXML: XML file was missing"]);
    return;
  }
  $templates = &$GLOBALS['templates'];
  if ( ! $templates ) {
    postReturn(["error"=>"Create XML: templates file missing or empty"]);
    return;
  }
  if ( !startsWith($xmlFile,"/boot/") ) {
    $index = searchArray($templates,"Path",$xmlFile);
    if ( $index === false ) {
      postReturn(["error"=>"Create XML: couldn't find template with path of $xmlFile"]);
      return;
    }
    $template = $templates[$index];
    if ( $template['OriginalOverview'] )
      $template['Overview'] = $template['OriginalOverview'];
    if ( $template['OriginalDescription'] )
      $template['Description'] = $template['OriginalDescription'];
    $template['Icon'] = $template["Icon-{$caSettings['dynamixTheme']}"] ?? $template['Icon'];
    
// Handle paths directly referencing disks / poola that aren't present in the user's system, and replace the path with the first disk present
    $unRaidDisks = parse_ini_file($caPaths['disksINI'],true);

    $disksPresent = array_keys(array_filter($unRaidDisks, function($k) {
      return ($k['status'] !== "DISK_NP" && ! preg_match("/(parity|parity2|disks|diskP|diskQ)/",$k['name']));
    }));

    $unRaidVersion = parse_ini_file($caPaths['unRaidVersion']);
    $cachePools = array_filter($unRaidDisks, function($k) {
      return ! preg_match("/disk\d(\d|$)|(parity|parity2|disks|flash|diskP|diskQ)/",$k['name']);
    });
    $cachePools = array_keys(array_filter($cachePools, function($k) {
      return $k['status'] !== "DISK_NP";
    }));

    // always prefer the default cache pool
    if ( in_array("cache",$cachePools) )
      array_unshift($cachePools,"cache"); // This will be a duplicate, but it doesn't matter as we only reference item0

    // Prefer cache pools over disks
    $disksPresent = array_merge($cachePools,$disksPresent,["disks"]);

    // check to see if user shares enabled
    $unRaidVars = parse_ini_file($caPaths['unRaidVars']);
    if ( $unRaidVars['shareUser'] == "e" )
      $disksPresent[] = "user";
    if ( @is_array($template['Data']['Volume']) ) {
      $testarray = $template['Data']['Volume'];
      if ( ( ! isset($testarray[0]) ) || ( ! is_array($testarray[0]) ) ) $testarray = [$testarray];
      foreach ($testarray as &$volume) {
        $diskReferenced = array_values(array_filter(explode("/",$volume['HostDir'])));
        if ( $diskReferenced[0] == "mnt" && $diskReferenced[1] && ! in_array($diskReferenced[1],$disksPresent) ) {
          $volume['HostDir'] = str_replace("/mnt/{$diskReferenced[1]}/","/mnt/{$disksPresent[0]}/",$volume['HostDir']);
        }
      }
      $template['Data']['Volume'] = $testarray;
    }

    if ( $template['Config'] ) {
      $testarray = $template['Config'] ?: [];
      if (!($testarray[0]??false)) $testarray = [$testarray];

      foreach ($testarray as &$config) {
        if ( is_array($config['@attributes']) ) {
          if ( $config['@attributes']['Type'] == "Path" ) {
            $defaultReferenced = array_values(array_filter(explode("/",$config['@attributes']['Default'])));

            if ( isset($defaultReferenced[0]) && isset($defaultReferenced[1]) ) {
              if ( $defaultReferenced[0] == "mnt" && $defaultReferenced[1] && ! in_array($defaultReferenced[1],$disksPresent) )
                $config['@attributes']['Default'] = str_replace("/mnt/{$defaultReferenced[1]}/","/mnt/{$disksPresent[0]}/",$config['@attributes']['Default']);
              }

            $valueReferenced = array_values(array_filter(explode("/",$config['value'])));
            if ( isset($valueReferenced[0]) && isset($valueReferenced[1]) ) {
              if ( $valueReferenced[0] == "mnt" && $valueReferenced[1] && ! in_array($valueReferenced[1],$disksPresent) )
                $config['value'] = str_replace("/mnt/{$valueReferenced[1]}/","/mnt/{$disksPresent[0]}/",$config['value']);
            }

            // Check for pre-existing folders only differing by "case" and adjust accordingly

            // Default path
            if ( ! $config['value'] ) { // Don't override default if value exists
              $configPath = explode("/",$config['@attributes']['Default']);
              $testPath = "/";
              foreach ($configPath as &$entry) {
                $directories = @scandir($testPath);
                if ( ! $directories ) {
                  break;
                }
                foreach ($directories as $testDir) {
                  if ( strtolower($testDir) == strtolower($entry) ) {
                    if ( $testDir == $entry )
                      break;

                    $entry = $testDir;
                  }
                }
                $testPath .= $entry."/";
              }
              $config['@attributes']['Default'] = implode("/",$configPath);
            }

            // entered path
            if ( $config['value'] ) {
              $configPath = explode("/",$config['value']);
              $testPath = "/";
              foreach ($configPath as &$entry) {
                $directories = @scandir($testPath);
                if ( ! $directories ) {
                  break;
                }
                foreach ($directories as $testDir) {
                  if ( strtolower($testDir) == strtolower($entry) ) {
                    if ( $testDir == $entry )
                      break;

                    $entry = $testDir;
                  }
                }
                $testPath .= $entry."/";
              }
              $config['value'] = implode("/",$configPath);
            }
          }
        }
      }
      $template['Config'] = $testarray;
    }
    $template['Name'] = str_replace(" ","-",$template['Name']);
    $alreadyInstalled = getAllInfo();
    foreach ( $alreadyInstalled as $installed ) {
      if ( strtolower($template['Name']) == $installed['Name'] ) {
        for ( ;; ) {
          if (is_file("{$caPaths['dockerManTemplates']}/my-{$template['Name']}.xml") ) {
            $template['Name'] .= "-1";
          } else break;
        }
      }
    }
    for ( ;; ) {
      if ($type == "second" && is_file("{$caPaths['dockerManTemplates']}/my-{$template['Name']}.xml") ) {
        $template['Name'] .= "-1";
      } else break;
    }
    if (! isset($template['Environment']) )
      $template['Environment']['Variable'] = ["Name"=>"test","Value"=>"yes"];
    if ( empty($template['Config']) ) // handles extra garbage entry being created on templates that are v1 only
      unset($template['Config']);
    $xml = makeXML($template);
    @mkdir(dirname($xmlFile));
    ca_file_put_contents($xmlFile,$xml);
  }
  postReturn(["status"=>"ok","cache"=>$cacheVolume ?? ""]);
}

########################
# Switch to a language #
########################
function switchLanguage() {
  global $caPaths;

  $language = getPost("language","");
  if ( $language == "en_US" )
    $language = "";

  if ( ! is_dir("/usr/local/emhttp/languages/$language") )  {
    postReturn(["error"=>"language $language is not installed"]);
    return;
  }
  $dynamixSettings = @parse_ini_file($caPaths['dynamixSettings'],true);
  $dynamixSettings['display']['locale'] = $language;
  write_ini_file($caPaths['dynamixSettings'],$dynamixSettings);
  postReturn(["status"=> "ok"]);
}

#######################################################
# Delete multiple checked off apps from previous apps #
#######################################################
function remove_multiApplications() {
  $apps = getPostArray("apps");
  if ( ! count($apps) ) {
    postReturn(["error"=>"No apps were in post when trying to remove multiple applications"]);
    return;
  }
  foreach ($apps as $app) {
    if ( strpos(realpath($app),"/boot/config/") === false ) {
      $error = "Remove multiple apps: $app was not in /boot/config";
      break;
    }
    @unlink($app);
  }
  if ( $error ?? false)
    postReturn(["error"=>$error]);
  else
    postReturn(["status"=>"ok"]);
}

############################################
# Get's the categories present on a search #
############################################
function getCategoriesPresent() {
  global $caPaths;

  if ( is_file($caPaths['community-templates-allSearchResults']) )
    $displayed = readJsonFile($caPaths['community-templates-allSearchResults']);
  else
    $displayed = readJsonFile($caPaths['community-templates-displayed']);

  $categories = [];
  foreach ($displayed['community'] as $template) {
    $cats = explode(" ",$template['Category']);
    foreach ($cats as $category) {
      if (strpos($category,":")) {
        $categories[] = explode(":",$category)[0].":";
      }
      $categories[] = $category;
    }
  }
  if (! empty($categories) ) {
    $categories[] = "repos";
    $categories[] = "All";
  }

  postReturn(array_values(array_unique($categories)));
}

##################################
# Set's the favourite repository #
##################################
function toggleFavourite() {
  global $caPaths, $caSettings;

  $repository = html_entity_decode(getPost("repository",""),ENT_QUOTES);
  if ( $caSettings['favourite'] == $repository )
    $repository = "";

  $caSettings['favourite'] = $repository;
  write_ini_file($caPaths['pluginSettings'],$caSettings);
  postReturn(['status'=>"ok",'fav'=>$repository]);
}

####################################
# Returns the favourite repository #
####################################
function getFavourite() {
  global $caSettings;

  postReturn(["favourite"=>$caSettings['favourite']]);
}
##########################
# Changes the sort order #
##########################
function changeSortOrder() {
  global $caPaths, $sortOrder;

  $sortOrder = getPostArray("sortOrder");
  writeJsonFile($caPaths['sortOrder'],$sortOrder);

  if ( is_file($caPaths['community-templates-displayed']) ) {
    $displayed = readJsonFile($caPaths['community-templates-displayed']);
    if ($displayed['community'])
      usort($displayed['community'],"mySort");
    writeJsonFile($caPaths['community-templates-displayed'],$displayed);
  }
  if ( is_file($caPaths['community-templates-allSearchResults']) ) {
    $allSearchResults = readJsonFile($caPaths['community-templates-allSearchResults']);
    if ( $allSearchResults['community'] )
      usort($allSearchResults['community'],"mySort");
    writeJsonFile($caPaths['community-templates-allSearchResults'],$allSearchResults);
  }
  if ( is_file($caPaths['community-templates-catSearchResults']) ) {
    $catSearchResults = readJsonFile($caPaths['community-templates-catSearchResults']);
    if ( $catSearchResults['community'] )
      usort($catSearchResults['community'],"mySort");
    writeJsonFile($caPaths['community-templates-catSearchResults'],$catSearchResults);
  }
  if ( is_file($caPaths['repositoriesDisplayed']) ) {
    $reposDisplayed = readJsonFile($caPaths['repositoriesDisplayed']);
    $bio = [];
    $nonbio = [];
    foreach ($reposDisplayed['community'] as $repo) {
      if ($repo['bio'])
        $bio[] = $repo;
      else
        $nonbio[] = $repo;
    }
    usort($bio,"mysort");
    usort($nonbio,"mysort");
    $reposDisplayed['community'] = array_merge($bio,$nonbio);
    writeJsonFile($caPaths['repositoriesDisplayed'],$reposDisplayed);
  }
  postReturn(['status'=>"ok"]);
}
############################################
# Gets the sort order when restoring state #
############################################
function getSortOrder() {
  global $sortOrder;

  postReturn(["sortBy"=>$sortOrder['sortBy'],"sortDir"=>$sortOrder['sortDir']]);
}

############################################################
# Reset the sort order to default when reloading Apps page #
############################################################
function defaultSortOrder() {
  global $caPaths, $sortOrder;

  $sortOrder['sortBy'] = "Name";
  $sortOrder['sortDir'] = "Up";
  writeJsonFile($caPaths['sortOrder'],$sortOrder);
  postReturn(['status'=>"ok"]);
}

###################################################################
# Checks whether we're on the startup screen when restoring state #
###################################################################
function onStartupScreen() {
  global $caPaths;

  postReturn(['status'=>is_file($caPaths['startupDisplayed'])]);
}

#######################################################################
# convert_docker - called when system adds a container from dockerHub #
#######################################################################
function convert_docker() {
  global $caPaths, $dockerManPaths;

  $dockerID = getPost("ID","");

  $file = readJsonFile($caPaths['dockerSearchResults']);
  $dockerIndex = searchArray($file['results'],"ID",$dockerID);
  $docker = $file['results'][$dockerIndex];
  $docker['Description'] = str_replace("&", "&amp;", $docker['Description']);

  $dockerfile['Name'] = $docker['Name'];
  $dockerfile['Support'] = $docker['DockerHub'];
  $dockerfile['Description'] = $docker['Description']."\n\nConverted By Community Applications   Always verify this template (and values)  against the support page for the container\n\n{$docker['DockerHub']}";
  $dockerfile['Overview'] = $dockerfile['Description'];
  $dockerfile['Registry'] = $docker['DockerHub'];
  $dockerfile['Repository'] = $docker['Repository'];
  $dockerfile['BindTime'] = "true";
  $dockerfile['Privileged'] = "false";
  $dockerfile['Networking']['Mode'] = "bridge";

  $existing_templates = array_diff(scandir($dockerManPaths['templates-user']),[".",".."]);
  foreach ( $existing_templates as $template ) {
    if ( strtolower($dockerfile['Name']) == strtolower(str_replace(["my-",".xml"],["",""],$template)) )
      $dockerfile['Name'] .= "-1";
  }

  $dockerXML = makeXML($dockerfile);

  ca_file_put_contents($caPaths['dockerSearchInstall'],$dockerXML);
  postReturn(['xml'=>$caPaths['dockerSearchInstall']]);
}

#########################################################
# search_dockerhub - returns the results from dockerHub #
#########################################################
function search_dockerhub() {
  global $caPaths;

  $filter     = getPost("filter","");
  $pageNumber = getPost("page","1");

  $communityTemplates = &$GLOBALS['templates'];
  $filter = str_replace(" ","%20",$filter);
  $filter = str_replace("/","%20",$filter);
  $jsonPage = shell_exec("curl -s -X GET 'https://registry.hub.docker.com/v1/search?q=$filter&page=$pageNumber'");
  $pageresults = json_decode($jsonPage,true);
  $num_pages = $pageresults['num_pages'];

  if ($pageresults['num_results'] == 0) {
    $o['display'] = "<div class='ca_NoDockerAppsFound'>".tr("No Matching Applications Found On Docker Hub")."</div>";
    $o['script'] = "$('#dockerSearch').hide();";
    postReturn($o);
    @unlink($caPaths['dockerSearchResults']);
    @unlink($caPaths['dockerSearchActive']);
    return;
  }

  touch($caPaths['dockerSearchActive']);
  $i = 0;
  foreach ($pageresults['results'] as $result) {
    unset($o);
    $o['IconFA'] = "docker";
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
    } else
      $o['DockerHub'] = "https://hub.docker.com/r/".$result['name']."/";

    $o['ID'] = $i;
    $searchName = str_replace("docker-","",$o['Name']);
    $searchName = str_replace("-docker","",$searchName);

    $dockerResults[$i] = addMissingVars($o);
    $i=++$i;
  }
  $dockerFile['num_pages'] = $num_pages;
  $dockerFile['page_number'] = $pageNumber;
  $dockerFile['results'] = $dockerResults;

  writeJsonFile($caPaths['dockerSearchResults'],$dockerFile);
  postReturn(['display'=>displaySearchResults($pageNumber)]);
}
##############################################
# Gets the last update issued to a container #
##############################################
function getLastUpdate($ID) {
  global $caPaths;

  $count = 0;
  $registry_json = null;
  while ( $count < 5 ) {
    $templates = &$GLOBALS['templates'];
    if ( $templates ) break;
    sleep(1); # keep trying in case of a collision between reading and writing
    $count++;
  }
  $index = searchArray($templates,"ID",$ID);
  if ( $index === false )
    return "Unknown";

  $app = $templates[$index];
  if ( $app['PluginURL'] || $app['LanguageURL'] )
    return;

  if ( strpos($app['Repository'],"ghcr.io") !== false || strpos($app['Repository'],"cr.hotio.dev") !== false || strpos($app['Repository'],"lscr.io") !== false) { // try dockerhub for info on ghcr stuff
    $info = pathinfo($app['Repository']);
    $regs = basename($info['dirname'])."/".$info['filename'];
  } else {
    $regs = $app['Repository'];
  }
  $reg = explode(":",$regs);
  if ( ($reg[1] ?? "latest") !== "latest" )
    return tr("Unknown");

  if ( !strpos($reg[0],"/") )
    $reg[0] = "library/{$reg[0]}";

  $count = 0;
  $registry = false;
  while ( ! $registry && $count < 5 ) {
    $registry = download_url("https://registry.hub.docker.com/v2/repositories/{$reg[0]}");
    if ( ! $registry ) {
      $count++;
      sleep(1);
      continue;
    }
    $registry_json = json_decode($registry,true);
    if ( ! $registry_json['last_updated'] )
      return;

  }
  $registry_json['last_updated'] = $registry_json['last_updated'] ??  false;
  $lastUpdated = $registry_json['last_updated'] ? tr(date("M j, Y",strtotime($registry_json['last_updated'])),0) : "Unknown";

  return $lastUpdated;
}
######################################
# Changes the max per page displayed #
######################################
function changeMaxPerPage() {
  global $caPaths, $caSettings;

  $max = getPost("max",24);
  if ($caSettings['maxPerPage'] == $max)
    postReturn(["status"=>"same"]);
  else {
    $caSettings['maxPerPage'] = $max;
    write_ini_file($caPaths['pluginSettings'],$caSettings);
    postReturn(["status"=>"updated"]);
  }
}
################################################################
# Enables if necessary the action centre                       #
# Basically a duplicate of action centre code in previous apps #
################################################################
function enableActionCentre() {
  global $caPaths, $caSettings, $DockerClient;

# wait til check for updates is finished
  for ( $i=0;$i<100;$i++ ) {
    if ( is_file($caPaths['updateRunning']) && file_exists("/proc/".@file_get_contents($caPaths['updateRunning'])) ) {
      debug("Action Centre sleeping -> update running");
      sleep(5);
      clearstatcache();
    }
    else break;
  }
  if ( $i >= 100 ) {
    debug("Something went wrong.  EnableActionCentre ran longer than 500 seconds");
    postReturn(['status'=>"noaction"]);
    return;
  }
# wait til templates are downloaded
  for ( $i=0;$i<100;$i++ ) {
    if ( ! is_file($caPaths['haveTemplates']) ) {
      debug("Action Centre sleeping - no templates yet");
      sleep(5);
    } else {
      debug("action centre: have templates");
      break;
    }
  }
  if ( $i >= 100 ) {
    debug("Something went wrong.  EnableActionCentre ran longer than 500 seconds");
    postReturn(['status'=>"noaction"]);
    return;
  }
  $file = readJsonFile($caPaths['community-templates-info']);
  $extraBlacklist = readJsonFile($caPaths['extraBlacklist']);
  $extraDeprecated = readJsonFile($caPaths['extraDeprecated']);

  if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
    $dockerUpdateStatus = readJsonFile($caPaths['dockerUpdateStatus']);
  } else {
    $dockerUpdateStatus = [];
  }

  $info = getAllInfo();
# $info contains all installed containers
# now correlate that to a template;
# this section handles containers that have not been renamed from the appfeed
  if ( $caSettings['dockerRunning'] ) {
    $all_files = glob("{$caPaths['dockerManTemplates']}/*.xml");
    $all_files = $all_files ?: [];
    foreach ($all_files as $xmlfile) {
      $o = readXmlFile($xmlfile);

      $runningflag = false;
      foreach ($info as $installedDocker) {
        $installedImage = str_replace("library/","",$installedDocker['Image']);
        $installedName = $installedDocker['Name'];
        if ( $installedName == $o['Name'] ) {
          if ( startsWith($installedImage, $o['Repository']) ) {
            $runningflag = true;
            $searchResult = searchArray($file,'Repository',$o['Repository']);
            if ( $searchResult === false) {
              $searchResult = searchArray($file,'Repository',explode(":",$o['Repository'])[0]);
            }
            if ( $searchResult !== false ) 
              $o = $file[$searchResult];
              
            if ( $searchResult === false ) {
              $runningFlag = true;
              if ( $extraBlacklist[$o['Repository']] ?? false ) {
                $o['Blacklist'] = true;
              }
              if ( $extraDeprecated[$o['Repository']] ?? false ) {
                $o['Deprecated'] = true;
              }
            }
            break;
          }
        }
      }
      if ( $runningflag ) {
        $tmpRepo = strpos($o['Repository'],":") ? $o['Repository'] : $o['Repository'].":latest";
        $tmpRepo = strpos($tmpRepo,"/") ? $tmpRepo : "library/$tmpRepo";
        if ( $tmpRepo ) {
          if ( isset($dockerUpdateStatus[$tmpRepo]['status']) && $dockerUpdateStatus[$tmpRepo]['status'] == "false" )
            $o['actionCentre'] = true;
        }
        if ( ! $o['Blacklist'] && ! $o['Deprecated'] ) {
          if ( isset($extraBlacklist[$o['Repository']]) ) {
            $o['Blacklist'] = true;
          }
          if ( isset($extraDeprecated[$o['Repository']]) ) {
            $o['Deprecated'] = true;
          }
        }

        if ( !$o['Blacklist'] && !$o['Deprecated'] && !$o['actionCentre']  )
          continue;

        $displayed[] = $o;
        break;
      }
    }
  }
# Now work on plugins
  foreach ($file as $template) {
    if ( ! $template['Plugin'] ) continue;

    if ( $template['Name'] == "Community Applications" )
      continue;

    $filename = pathinfo($template['Repository'],PATHINFO_BASENAME);

    if ( checkInstalledPlugin($template) ) {
      $template['InstallPath'] = "/var/log/plugins/$filename";
      $template['Uninstall'] = true;
      if ( plugin("pluginURL","/var/log/plugins/$filename") !== $template['PluginURL'] )
        continue;

      $installedVersion = plugin("version","/var/log/plugins/$filename");
      if ( ( strcmp($installedVersion,$template['pluginVersion']) < 0 || $template['UpdateAvailable']) ) {
        $template['actionCentre'] = true;
      }
      if ( ! $template['actionCentre'] && is_file("/tmp/plugins/$filename") ) {
        if ( strcmp($installedVersion,plugin("version","/tmp/plugins/$filename")) < 0 )
          $template['actionCentre'] = true;
      }

      if ( !$template['Blacklist'] && !$template['Deprecated'] && $template['Compatible'] && !$template['actionCentre'] )
        continue;
      $displayed[] = $template;
      break;
    }
  }
  $installedLanguages = array_diff(scandir($caPaths['languageInstalled']),[".","..","en_US"]);
  foreach ($installedLanguages as $language) {
    $index = searchArray($file,"LanguagePack",$language);
    if ( $index !== false ) {
      $tmpL = $file[$index];
      $tmpL['Uninstall'] = true;

      if ( !languageCheck($tmpL) )
        continue;

      $displayed[] = $tmpL;
      break;
    }
  }
  if ( isset($displayed) ) {
    debug("action center enabled");
    postReturn(['status'=>"action"]);
  } else {
    debug("action centre disabled");
    postReturn(['status'=>"noaction"]);
  }
}

###################################################
# Checks the requirements being met on an upgrade #
###################################################
function checkRequirements() {
  $requiresFile = getPost("requires","");
  if (! $requiresFile || ($requiresFile && is_file($requiresFile) ) ) {
    postReturn(['met'=>true]);
  } else {
    postReturn(['met'=>""]);
  }
}

########################################################
# Saves the list of plugins which are pending installs #
########################################################
function saveMultiPluginPending() {
  global $caPaths;

  $plugin = getPost("plugin","");
  $plugins = array_filter(explode("*",$plugin));
  if ( count($plugins) > 1 ) {
    exec("mkdir -p {$caPaths['pluginPending']}");
    foreach ($plugins as $plg) {
      if (! $plg ) continue;
      $pluginName = basename($plg);
      touch($caPaths['pluginPending'].$pluginName);
    }
  }
  postReturn(['status'=>'ok']);
}

##############################################
# Downloads the stats file in the background #
##############################################
function downloadStatistics() {
  global $caPaths;

  if ( ! is_file($caPaths['statistics']) )
    download_json($caPaths['statisticsURL'],$caPaths['statistics']);
}

###########################################################################
# Checks to see if a plugin installation or update is already in progress #
###########################################################################
function checkPluginInProgress() {
  global $caPaths;

  $pluginsPending = glob("{$caPaths['pluginPending']}/*");

  postReturn(['inProgress'=>empty($pluginsPending)? "" : "true"]);
}


#######################################
# Logs Javascript errors being caught #
#######################################
function javascriptError() {
  return;
  global $caPaths, $caSettings;

  debug("******* ERROR **********\n".print_r($_POST,true));
}
?>