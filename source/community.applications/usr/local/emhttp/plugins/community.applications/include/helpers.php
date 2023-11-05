<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2023, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################

##################################################################################################################
# Convert Array("one","two","three") to be Array("one"=>$defaultFlag, "two"=>$defaultFlag, "three"=>$defaultFlag #
##################################################################################################################
function arrayEntriesToObject($sourceArray,$defaultFlag=true) {
  return is_array($sourceArray) ? array_fill_keys($sourceArray,$defaultFlag) : [];
}
###########################################################################
# Helper function to determine if a plugin has an update available or not #
###########################################################################
function checkPluginUpdate($filename) {
  global $caSettings;

  $filename = basename($filename);
  if ( ! is_file("/var/log/plugins/$filename") ) return false;
  $upgradeVersion = (is_file("/tmp/plugins/$filename")) ? plugin("version","/tmp/plugins/$filename") : "0";
  $installedVersion = $upgradeVersion ? plugin("version","/var/log/plugins/$filename") : 0;

  if ( $installedVersion < $upgradeVersion ) {
    $unRaid = plugin("unRAID","/tmp/plugins/$filename");
    return ( $unRaid === false || version_compare($caSettings['unRaidVersion'],$unRaid,">=") ) ? true : false;
  }
  return false;
}
###################################################################################
# returns a random file name (/tmp/community.applications/tempFiles/34234234.tmp) #
###################################################################################
function randomFile() {
  global $caPaths;

  return tempnam($caPaths['tempFiles'],"CA-Temp-");
}
##################################################################
# 7 Functions to avoid typing the same lines over and over again #
##################################################################
function readJsonFile($filename) {
  global $caSettings, $caPaths;

  debug("CA Read JSON file $filename");

  $json = json_decode(@file_get_contents($filename),true);
  if ( $json === false ) {
    if ( ! is_file($filename) )
      debug("$filename not found");

    debug("JSON Read Error ($filename)");
  }
  debug("Memory Usage:".round(memory_get_usage()/1048576,2)." MB");
  return is_array($json) ? $json : array();
}
function writeJsonFile($filename,$jsonArray) {
  global $caSettings, $caPaths;

  debug("Write JSON File $filename");
  $jsonFlags = $caSettings['dev'] == "yes" ? JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT : JSON_UNESCAPED_SLASHES;
  $result = ca_file_put_contents($filename,json_encode($jsonArray, $jsonFlags));
  debug("Memory Usage:".round(memory_get_usage()/1048576,2)." MB");
}

function ca_file_put_contents($filename,$data,$flags=0) {
  $result = @file_put_contents($filename,$data,$flags);

  if ( $result === false ) {
    debug("Failed to write to $filename");
    $GLOBALS['script'] = "alert('Failed to write to ".htmlentities($filename,ENT_QUOTES)."');";
  }
  return $result;
}
function download_url($url, $path = "", $bg = false, $timeout = 45) {
  global $caSettings, $caPaths;

  debug("DOWNLOAD starting $url\n");
  $startTime = time();

  $ch = curl_init();
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch,CURLOPT_FRESH_CONNECT,true);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
  curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
  curl_setopt($ch,CURLOPT_ENCODING,"");
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch,CURLOPT_FAILONERROR,true);

  if ( is_file("/boot/config/plugins/community.applications/proxy.cfg") ) {
    $proxyCFG = parse_ini_file("/boot/config/plugins/community.applications/proxy.cfg");
    curl_setopt($ch, CURLOPT_PROXYPORT,intval($proxyCFG['port']));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL,intval($proxyCFG['tunnel']));
    curl_setopt($ch, CURLOPT_PROXY,$proxyCFG['proxy']);
  }
  $out = curl_exec($ch);
  curl_close($ch);
  if ( $path )
    ca_file_put_contents($path,$out);

  $totalTime = time() - $startTime;
  debug("DOWNLOAD $url Time: $totalTime  RESULT:\n".var_dump_ret($out));
  return $out ?: false;
}
function download_json($url,$path="",$bg=false,$timeout=45) {
  return json_decode(download_url($url,$path,$bg,$timeout),true);
}
function getPost($setting,$default) {
  return isset($_POST[$setting]) ? urldecode(($_POST[$setting])) : $default;
}
function getPostArray($setting) {
  return $_POST[$setting];
}

function var_dump_ret($mixed = null) {
  ob_start();
  var_dump($mixed);
  $content = ob_get_contents();
  ob_end_clean();
  return $content;
}
##############################################
# Determine if $haystack begins with $needle #
##############################################
function startsWith($haystack, $needle) {
  if ( !is_string($haystack) || ! is_string($needle) ) return false;
  return $needle === "" || strripos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
#############################################
# Determine if $string ends with $endstring #
#############################################
function endsWith($string, $endString) {
  $len = strlen($endString);
  if ($len == 0) {
    return true;
  }
  return (substr($string, -$len) === $endString);
}
###########################################
# Replace the first occurance in a string #
###########################################
function first_str_replace($haystack, $needle, $replace) {
  $pos = strpos($haystack, $needle);
  return ($pos !== false) ? substr_replace($haystack, $replace, $pos, strlen($needle)) : $haystack;
}
##########################################
# Replace the last occurance in a string #
##########################################
function last_str_replace($haystack, $needle, $replace) {
  $pos = strrpos($haystack, $needle);
  return ($pos !== false) ? substr_replace($haystack, $replace, $pos, strlen($needle)) : $haystack;
}
#######################
# Custom sort routine #
#######################
function mySort($a, $b) {
  global $sortOrder;

  if ( $sortOrder['sortBy'] == "Name" )
    $sortOrder['sortBy'] = "SortName";
  if ( $sortOrder['sortBy'] != "downloads" && $sortOrder['sortBy'] != "trendDelta") {
    $c = strtolower($a[$sortOrder['sortBy']] ?? "");
    $d = strtolower($b[$sortOrder['sortBy']] ?? "");
  } else {
    $c = $a[$sortOrder['sortBy']];
    $d = $b[$sortOrder['sortBy']];
  }

  $return1 = ($sortOrder['sortDir'] == "Down") ? -1 : 1;
  $return2 = ($sortOrder['sortDir'] == "Down") ? 1 : -1;

  if ( ! is_numeric($c) ) {
    $c = strtolower($c ?? "");
    $d = strtolower($d ?? "");
  }
  if ($c > $d) return $return1;
  else if ($c < $d) return $return2;
  else return 0;
}

function repositorySort($a,$b) {
  global $caSettings;

  if ( $a['RepoName'] == $caSettings['favourite'] ) return -1;
  if ( $b['RepoName'] == $caSettings['favourite'] ) return 1;
  return 0;
}

function favouriteSort($a,$b) {
  global $caSettings;

  if ( $a['Repo'] == $caSettings['favourite'] ) return -1;
  if ( $b['Repo'] == $caSettings['favourite'] ) return 1;
  return 0;
}
###############################################
# Search array for a particular key and value #
# returns the index number of the array       #
# return value === false if not found         #
###############################################
function searchArray($array,$key,$value,$startingIndex=0) {
  $result = false;
  if (is_array($array) && count($array) ) {
    for ($i = $startingIndex; $i <= max(array_keys($array)); $i++) {
      if ( $array[$i][$key] == $value ) {
        $result = $i;
        break;
      }
    }
  }
  return $result;
}
########################################################
# Fix common problems (maintainer errors) in templates #
########################################################
function fixTemplates($template) {
  global $statistics, $caSettings;

  if ( ! $template['MinVer'] ) $template['MinVer'] = $template['Plugin'] ? "6.1" : "6.0";
  if ( ! $template['Date'] ) $template['Date'] = (is_numeric($template['DateInstalled'])) ? $template['DateInstalled'] : 0;
  $template['Date'] = max($template['Date'],$template['FirstSeen']);
  if ($template['Date'] == 1) $template['Date'] = null;
  if ( ($template['Date'] == $template['FirstSeen']) && ( $template['FirstSeen'] >= 1538357652 )) {# 1538357652 is when the new appfeed first started
    $template['BrandNewApp'] = true;
    $template['Date'] = null;
  }

  # fix where template author includes <Blacklist> or <Deprecated> entries in template (CA used booleans, but appfeed winds up saying "FALSE" which equates to be true
  $template['Deprecated'] = filter_var($template['Deprecated'],FILTER_VALIDATE_BOOLEAN);
  $template['Blacklist'] = filter_var($template['Blacklist'],FILTER_VALIDATE_BOOLEAN);

  if ( $template['DeprecatedMaxVer'] && version_compare($caSettings['unRaidVersion'],$template['DeprecatedMaxVer'],">") )
    $template['Deprecated'] = true;

  if ( version_compare($caSettings['unRaidVersion'],"6.10.0-beta4",">") ) {
    if ( $template['Config'] ) {
      if ( $template['Config']['@attributes'] ?? false ) {
        if (preg_match("/^(Container Path:|Container Port:|Container Label:|Container Variable:|Container Device:)/",$template['Config']['@attributes']['Description']) ) {
          $template['Config']['@attributes']['Description'] = "";
        }
      } else {
        if (is_array($template['Config'])) {
          foreach ($template['Config'] as &$config) {
            if (preg_match("/^(Container Path:|Container Port:|Container Label:|Container Variable:|Container Device:)/",$config['@attributes']['Description']??"") ) {
              $config['@attributes']['Description'] = "";
            }
          }
        }
      }
    }
  }
  return $template;
}
###############################################
# Function used to create XML's from appFeeds #
###############################################
function makeXML($template) {
  # ensure its a v2 template if the Config entries exist
  if ( isset($template['Config']) && ! isset($template['@attributes']) )
    $template['@attributes'] = ["version"=>2];

  if ($template['Overview']) $template['Description'] = $template['Overview'];

  fixAttributes($template,"Network");
  fixAttributes($template,"Config");

# Sanitize the Requires entry if there is any CA links within it
  if ($template['Requires'] ?? false) {
    preg_match_all("/\/\/(.*?)&#92;/m",$template['Requires'],$searchMatches);

    if ( isset($searchMatches[1]) && count($searchMatches[1]) ) {
      foreach ($searchMatches[1] as $searchResult) {
        $template['Requires'] = str_replace("//$searchResult\\\\",$searchResult,$template['Requires']);
      }
    }
  }
  $Array2XML = new Array2XML();
  $xml = $Array2XML->createXML("Container",$template);
  return $xml->saveXML();
}
#################################################################################
# Function to fix differing schema in the appfeed vs what Array2XML class wants #
#################################################################################
function fixAttributes(&$template,$attribute) {
  if ( ! isset($template[$attribute]) ) return;
  if ( ! is_array($template[$attribute]) ) return;
  if ( isset($template[$attribute]['@attributes']) ) {
    $template[$attribute][0]['@attributes'] = $template[$attribute]['@attributes'];
    if ( $template[$attribute]['value'])
      $template[$attribute][0]['value'] = $template[$attribute]['value'];

    unset($template[$attribute]['@attributes']);
    unset($template[$attribute]['value']);
  }

  if ( $template[$attribute] ) {
    foreach ($template[$attribute] as $tempArray)
      $tempArray2[] = isset($tempArray['value']) ? ['@attributes'=>$tempArray['@attributes'],'@value'=>$tempArray['value']] : ['@attributes'=>$tempArray['@attributes']];
    $template[$attribute] = $tempArray2;
  }
}
#################################################################
# checks the Min/Max version of an app against unRaid's version #
# Returns: TRUE if it's valid to run, FALSE if not              #
#################################################################
function versionCheck($template) {
  global $caSettings;

  if ( $template['IncompatibleVersion'] ) {
    if ( ! is_array($template['IncompatibleVersion']) ) {
      $incompatible[] = $template['IncompatibleVersion'];
    } else {
      $incompatible = $template['IncompatibleVersion'];
    }
    foreach ($incompatible as $ver) {
      if ( $ver == $template['pluginVersion'] ) return false;
    }
  }

  if ( $template['MinVer'] && ( version_compare($template['MinVer'],$caSettings['unRaidVersion']) > 0 ) ) return false;
  if ( $template['MaxVer'] && ( version_compare($template['MaxVer'],$caSettings['unRaidVersion']) < 0 ) ) return false;
  return true;
}
###############################################
# Function to read a template XML to an array #
###############################################
function readXmlFile($xmlfile,$generic=false,$stats=true) {
  global $statistics;

  if ( ! $xmlfile || ! is_file($xmlfile) ) return false;
  $xml = file_get_contents($xmlfile);
  $o = TypeConverter::xmlToArray($xml,TypeConverter::XML_GROUP);
  $o = addMissingVars($o);
  if ( ! $o ) return false;
  if ( $generic ) return $o;

  # Fix some errors in templates prior to continuing

  $o['Path']          = $xmlfile;
  $o['Author']        = getAuthor($o);
  $o['DockerHubName'] = strtolower($o['Name']);
  $o['Base']          = $o['BaseImage'] ?? "";
  $o['SortAuthor']    = $o['Author'];
  $o['SortName']      = $o['Name'];
  $o['Forum']         = $Repo['forum'] ?? "";
# configure the config attributes to same format as appfeed
# handle the case where there is only a single <Config> entry

  if ( isset($o['Config']['@attributes']) )
    $o['Config'] = ['@attributes'=>$o['Config']['@attributes'],'value'=>$o['Config']['value']];

  if ( $stats) {
    $statistics['plugin'] = $statistics['plugin'] ?? 0;
    $statistics['docker'] = $statistics['docker'] ?? 0;
    if ( $o['Plugin'] ) {
      $o['Author']     = $o['PluginAuthor'];
      $o['Repository'] = $o['PluginURL'];
      $o['SortAuthor'] = $o['Author'];
      $o['SortName']   = $o['Name'];
      $statistics['plugin']++;
    } else
      $statistics['docker']++;
  }
  return $o;
}
###################################################################
# Function To Merge Moderation into templates array               #
# (Because moderation can be updated when templates are not )     #
# If appfeed is updated, this is done when creating the templates #
###################################################################
function moderateTemplates() {
  global $caPaths,$caSettings;

//	$templates = readJsonFile($caPaths['community-templates-info']);
  $templates = &$GLOBALS['templates'];

  if ( ! $templates ) return;
  foreach ($templates as $template) {
    $template['Compatible'] = versionCheck($template);
    if ( $template['MaxVer'] && version_compare($template['MaxVer'],$caSettings['unRaidVersion']) < 0 )
      $template['Featured'] = false;
    if ( $template['CAMinVer'] ?? false ) {
      $template['UninstallOnly'] = version_compare($template['CAMinVer'],$caSettings['unRaidVersion'],">=");
    }

    if ( $template["DeprecatedMaxVer"] && version_compare($caSettings['unRaidVersion'],$template["DeprecatedMaxVer"],">") )
      $template['Deprecated'] = true;

    $template['ModeratorComment'] = $template['CaComment'] ?: $template['ModeratorComment'];
    $o[] = $template;
  }
  writeJsonFile($caPaths['community-templates-info'],$o);
  $GLOBALS['templates'] = $o;
  pluginDupe();
}
#######################################################
# Function to check for a valid URL                   #
#######################################################
function validURL($URL) {
  return filter_var($URL, FILTER_VALIDATE_URL);
}
#######################################################
# Function used to determine if a search term matches #
#######################################################
function filterMatch($filter,$searchArray,$exact=true) {
  $filterwords = explode(" ",$filter);
  $foundword = null;
  foreach ( $filterwords as $testfilter) {
    if ( ! trim($testfilter) ) continue;
    foreach ($searchArray as $search) {
      if ( ! $search ) continue;
      if ( stripos($search,$testfilter) !== false ) {
        $foundword++;
        break;
      }
    }
  }
  return $exact ? ($foundword == count($filterwords)) : ($foundword > 0);
}
##########################################################
# Used to figure out which plugins have duplicated names #
##########################################################
function pluginDupe() {
  global $caPaths;

  $pluginList = [];
  $dupeList = [];
  foreach ($GLOBALS['templates'] as $template) {
    if ( $template['Plugin'] ) {
      if ( ! isset($pluginList[basename($template['Repository'])]) )
        $pluginList[basename($template['Repository'])] = 0;
      $pluginList[basename($template['Repository'])]++;
    }
  }
  foreach (array_keys($pluginList) as $plugin) {
    if ( $pluginList[$plugin] > 1 )
      $dupeList[$plugin] = 1;
  }
  writeJsonFile($caPaths['pluginDupes'],$dupeList);
}
###################################
# Checks if a plugin is installed #
###################################
function checkInstalledPlugin($template) {
  global $caPaths;

  $pluginName = basename($template['PluginURL']);
  if ( ! file_exists("/var/log/plugins/$pluginName") ) return false;
  $dupeList = readJsonFile($caPaths['pluginDupes']);
  if ( ! isset($dupeList[$pluginName]) ) return true;
  return strtolower(trim(plugin("pluginURL","/var/log/plugins/$pluginName"))) == strtolower(trim($template['PluginURL']));
}

###########################################################
# Returns a string with only alphanumeric characters only #
###########################################################
function alphaNumeric($string) {
  return preg_replace("/[^a-zA-Z0-9]+/", "", $string);
}
##################################################################
# mobile browser detection from http://detectmobilebrowsers.com/ #
##################################################################
function isMobile() {
  global $caSettings;

  $useragent=$_SERVER['HTTP_USER_AGENT'];
  return (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)));
}
################################################
# Returns the author from the Repository entry #
################################################
function getAuthor($template) {
  if ( isset($template['PluginURL']) ) return $template['PluginAuthor'];

  if ( isset($template['Author']) ) return strip_tags($template['Author']);
  $template['Repository'] = str_replace(["lscr.io/","ghcr.io/","registry.hub.docker.com/","library/"],"",$template['Repository']);
  $repoEntry = explode("/",$template['Repository']);
  if (count($repoEntry) < 2)
    $repoEntry[] = "";

  return strip_tags(explode(":",$repoEntry[count($repoEntry)-2])[0]);
}
############################
# Trims the category lists #
############################
function categoryList($cat,$popUp = false) {
  $cat = str_replace([":,",": "," "],",",$cat);
  $cat = rtrim($cat,": ");
  $all_cat = explode(",",$cat);
  foreach ($all_cat as $trcat)
    $all_categories[] = tr($trcat);

  $categoryList = $popUp ? $all_categories : array_slice($all_categories,0,2);

  if ( count($all_categories) > count($categoryList) ) {
    $excess = count($all_categories) - count($categoryList);
    $categoryList[] = " ".sprintf(tr("and %s more"),$excess);
  }
  return rtrim(implode(", ",$categoryList),", ");
}
##################################
# Trims the language author list #
##################################
function languageAuthorList($authors) {
  $newAuthor = "";
  $allAuthors = explode(",",$authors);
  if ( count($allAuthors) > 3 ) {
    $newAuthors = array_slice($allAuthors,0,2);
    foreach ($newAuthors as $author) {
      $newAuthor .= trim($author).", ";
    }
    $excess = count($allAuthors) -2;
    $authors = rtrim($newAuthor,", ")." ".sprintf(tr("and %s more"),$excess);
  }
  return $authors;
}
#####################################
# Gets a rounded off download count #
#####################################
function getDownloads($downloads,$lowFlag=false) {
  $downloadCount = ["10000000000","5000000000","1000000000","500000000","100000000","50000000","25000000","10000000","5000000","2500000","1000000","500000","250000","100000","50000","25000","10000","5000","1000","500","100"];
  foreach ($downloadCount as $downloadtmp) {
    if ($downloads > $downloadtmp) {
      return sprintf(tr("More than %s"),number_format($downloadtmp));
    }
  }
  return ($lowFlag) ? $downloads : "";
}
#####################
# Stops a container #
#####################
function myStopContainer($id) {
  global $DockerClient;

  $DockerClient->stopContainer($id);
}
#####################################
# Fix Descriptions on previous apps #
#####################################
function fixDescription($Description) {
  if ( is_string($Description) ) {
    $Description = preg_replace("#\[br\s*\]#i", "{}", $Description);
    $Description = preg_replace("#\[b[\\\]*\s*\]#i", "||", $Description);
    $Description = preg_replace('#\[([^\]]*)\]#', '<$1>', $Description);
    $Description = preg_replace("#<span.*#si", "", $Description);
    $Description = preg_replace("#<[^>]*>#i", '', $Description);
    $Description = preg_replace("#"."{}"."#i", '<br>', $Description);
    $Description = preg_replace("#"."\|\|"."#i", '<b>', $Description);
    $Description = str_replace("&lt;","<",$Description);
    $Description = str_replace("&gt;",">",$Description);
    $Description = strip_tags($Description);
    $Description = trim($Description);
  }
  return is_string($Description) ? $Description : "";
}
############################
# displays the branch tags #
############################
function formatTags($leadTemplate,$rename="false") {
  global $caPaths;

  $type = $rename == "true" ? "second" : "default";

  $file = &$GLOBALS['templates'];

  $template = $file[$leadTemplate];
  $childTemplates = $file[$leadTemplate]['BranchID'];
  if ( ! is_array($childTemplates) )
    $o =  tr("Something really went wrong here");
  else {
    $defaultTag = $template['BranchDefault'] ? $template['BranchDefault'] : "latest";

    $o = "<table>";
    $o .= "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a class='appIconsPopUp xmlInstall ca_normal' data-type='$type' data-xml='{$template['Path']}'>Default</a></td><td class='appIconsPopUp xmlInstall ca_normal' data-type='default' data-xml='{$template['Path']}'>".tr("Install Using The Template's Default Tag")." (<span class='ca_bold'>:$defaultTag</span>)</td></tr>";
    foreach ($childTemplates as $child) {
      $o .= "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a class='appIconsPopUp xmlInstall ca_normal' data-type='$type' data-xml='{$file[$child]['Path']}'>{$file[$child]['BranchName']}</a></td><td class='appIconsPopUp xmlInstall ca_normal' data-type='default' data-xml='{$file[$child]['Path']}'>{$file[$child]['BranchDescription']}</td></tr>";
    }
    $o .= "</table>";
  }
  return $o;
}
###########################
# handles the POST return #
###########################
function postReturn($retArray) {
  global $caSettings, $caPaths;

  if (is_array($retArray)) {
    if ( isset($GLOBALS['script']) )
      $retArray['globalScript'] = $GLOBALS['script'];

    echo json_encode($retArray);
  }	else
    echo $retArray;
  flush();
  debug("POST RETURN ({$_POST['action']})\n".var_dump_ret($retArray));
  debug("POST RETURN Memory Usage:".round(memory_get_usage()/1048576,2)." MB");
}
####################################
# Translation backwards compatible #
####################################
if ( ! function_exists("tr") ) {
  function tr($string,$options=-1) {
    $translated = _($string,$options);
    if ( ! trim($translated) )
      $translated = $string;

    if ( startsWith($translated,"&#34;") && endsWith($translated,"&#34;") )
      $translated = first_str_replace(last_str_replace($translated,"&#34;",""),"&#34;","");

    $translated =  str_replace(['"',"'"],["&#34;","&#39;"],$translated);

    return $translated;
  }
}
#############################
# Check for language update #
#############################
function languageCheck($template) {
  global $caPaths;

  if ( ! $template['LanguageURL'] ) return false;

  $countryCode = $template['LanguagePack'];
  $installedLanguage = "{$caPaths['installedLanguages']}/lang-$countryCode.xml";
  $dynamixUpdate = "{$caPaths['dynamixUpdates']}/lang-$countryCode.xml";
  if ( ! is_file($installedLanguage) )
    return false;

  $OSupdates = readXmlFile($dynamixUpdate,true);   // Because the OS might check for an update before the feed
  if ( ! $OSupdates )
    $OSupdates['Version'] = "1900.01.01";

  $xmlFile = readXmlFile($installedLanguage,true);

  if ( !$xmlFile['Version'] ) return false;
  return (strcmp($template['Version'],$xmlFile['Version']) > 0) || (strcmp($OSupdates['Version'],$xmlFile['Version']) > 0);
}
######################
# Writes an ini file #
######################
function write_ini_file($file,$array) {
  $res = [];
  foreach($array as $key => $val) {
    if(is_array($val)) {
      $res[] = "[$key]";
      foreach($val as $skey => $sval)
        $res[] = $skey.'="'.$sval.'"';
    }
    else
      $res[] = $key.'="'.$val.'"';
  }
  ca_file_put_contents($file,implode("\r\n", $res),LOCK_EX);
}
###################################################
# Gets all the information about what's installed #
###################################################
function getAllInfo($force=false) {
  global $caSettings, $DockerTemplates, $DockerClient, $caPaths;

  $containers = readJsonFile($caPaths['info']);

  if ( $force || ! $containers || empty($containers) ) {
    if ( $caSettings['dockerRunning'] ?? false ) {
      $info = $DockerTemplates->getAllInfo(false,true,true);
      $containers = $DockerClient->getDockerContainers();
      foreach ($containers as &$container) {
        $container['running'] = $info[$container['Name']]['running'] ?? null;
        $container['url'] = $info[$container['Name']]['url'] ?? null;
        $container['template'] = $info[$container['Name']]['template'] ?? null;
      }
    }
    debug("Forced info update");
    writeJsonFile($caPaths['info'],$containers);
  } else {
    debug("Cached info update");
  }
  return $containers;
}
#######################
# Logs the debug info #
#######################
function debug($str) {
  global $caSettings, $caPaths;

  if ( $caSettings['debugging'] == "yes" ) {
    if ( ! is_file($caPaths['logging']) ) {
      touch($caPaths['logging']);
      $caVersion = plugin("version","/var/log/plugins/community.applications.plg");

      debug("Community Applications Version: $caVersion");
      debug("Unraid version: {$caSettings['unRaidVersion']}");
      debug("MD5's: \n".shell_exec("cd /usr/local/emhttp/plugins/community.applications && md5sum -c ca.md5"));
      $lingo = $_SESSION['locale'] ?? "en_US";
      debug("Language: $lingo");
      debug("Settings:\n".print_r($caSettings,true));
    }
    @file_put_contents($caPaths['logging'],date('Y-m-d H:i:s')."  $str\n",FILE_APPEND); //don't run through CA wrapper as this is non-critical
  }
}
########################################
# Gets the default ports in a template #
########################################
function portsUsed($template) {
  if ( ($template['Network'] ?? "whatever") !== "bridge")
    return;
  $portsUsed = [];
  if ( isset($template['Config']['@attributes']) )
    $template['Config'] = ['@attributes'=>$template['Config']];
  if ( is_array($template['Config']) ) {
    foreach ($template['Config'] as $config) {
      if ( $config['@attributes']['Type'] !== "Port" )
        continue;
      $portsUsed[] = $config['value'] ?: $config['@attributes']['Default'];
    }
  }
  return json_encode($portsUsed);
}

########################
# Get the ports in use #
########################
function getPortsInUse() {
  global $var, $caPaths;

  $addr = null;
  if ( !$var )
    $var = parse_ini_file($caPaths['unRaidVars']);

  $portsInUse = [];
  exec("lsof -Pni|awk '/LISTEN/ && \$9!~/127.0.0.1/ && \$9!~/\\[::1\\]/{print \$9}'|sort -u", $output);

  $bind = $var['BIND_MGT']=='yes';
  $list = is_array($addr) ? array_merge(['*'],$addr) : ['*',$addr];

  foreach ($output as $line) {
    [$ip, $port] = ca_explode(':', $line);
    if (!in_array($port,$portsInUse) && (!$bind || in_array(plain($ip),$list)))
      if ( is_numeric($port) )
        $portsInUse[] = $port;
  }

  return $portsInUse;
}

function ca_explode($split,$text,$count=2) {
  return array_pad(explode($split,$text,$count),$count,'');
}
function plain($ip) {
  return str_replace(['[',']'],'',$ip);
}
###########################################
# Checks server date against CA's version #
###########################################
# only a quick check if date on server is 30 days before CA's version.  Not 100% accurate to determine if date & time on server is incorrect

function checkServerDate() {
  $currentDate = strtotime(date("Y-m-d"));
  $caVersion = preg_replace("/[^0-9.]/","",plugin("version","/var/log/plugins/community.applications.plg"));
  if ( ! $caVersion )
    return true;
  $caVersion = str_replace(".","-",$caVersion);
  $caVersion = strtotime($caVersion);

  if ( ($caVersion - $currentDate) > 2592000 ) # 30 Days
    return false;
  else
    return true;
}

##################################################################################
# Adds in all the various missing entries from the templates for PHP8 compliance #
##################################################################################
function addMissingVars($o) {
  if ( ! is_array($o) )
    return $o;
  $vars = [
    'Category',
    'CategoryList',
    'CABlacklist',
    'Blacklist',
    'MinVer',
    'MaxVer',
    'UpdateMinVer',
    'Plugin',
    'PluginURL',
    'Date',
    'DonateText',
    'DonateLink',
    'Branch',
    'OriginalOverview',
    'DateInstalled',
    'Config',
    'trending',
    'CAComment',
    'ModeratorComment',
    'DeprecatedMaxVer',
    'downloads',
    'FirstSeen',
    'OriginalDescription',
    'Deprecated',
    'RecommendedRaw',
    'Language',
    'RequiresFile',
    'Requires',
    'trends',
    'Description',
    'OriginalDescription',
    'Overview',
    'Repository',
    'Tag',
    'Plugin',
    'CaComment',
    'IncompatibleVersion',
    'Private',
    'BranchName',
    'display',
    'RepositoryTemplate',
    'bio',
    'NoInstall',
    'Twitter',
    'Discord',
    'Reddit',
    'Facebook',
    'ReadMe',
    'display_dockerName',
    'actionCentre',
    'SupportLanguage',
    'DockerHub',
    'Official',
    'Removable',
    'IconFA',
    'imageNoClick',
    'RecommendedDate',
    'UpdateAvailable',
    'Installed',
    'Uninstall',
    'caTemplateExists',
    'Support',
    'Beta',
    'Project',
    'Trusted',
    'InstallPath',
    'LanguagePack',
    'trendDelta',
    'RepoTemplate',
    'ExtraSearchTerms',
    'Icon',
    'LanguageDefault',
    'translatedCategories',
    'RepoShort',
    'LanguageLocal',
    'ExtraPriority',
    'Registry',
    'caTemplateURL',
    'Changes',
    'ChangeLogPresent',
    'Photo',
    'Screenshot',
    'Video',
    'RecommendedReason',
    'stars',
    'LanguageURL',
    'LastUpdate',
    'RecommendedWho',
    'RepoName',
    'SortName',
    'ca_fav',
    'Pinned'


    ];

  foreach ($vars as $var) {
    $o[$var] = $o[$var] ?? null;
  }
  return $o;

}

/**
 * @copyright Copyright 2006-2012, Miles Johnson - http://milesj.me
 * @license   http://opensource.org/licenses/mit-license.php - Licensed under the MIT License
 * @link    http://milesj.me/code/php/type-converter
 */

/**
 * A class that handles the detection and conversion of certain resource formats / content types into other formats.
 * The current formats are supported: XML, JSON, Array, Object, Serialized
 *
 * @version 2.0.0
 * @package mjohnson.utility
 */
class TypeConverter {

  /**
   * Disregard XML attributes and only return the value.
   */
  const XML_NONE = 0;

  /**
   * Merge attributes and the value into a single dimension; the values key will be "value".
   */
  const XML_MERGE = 1;

  /**
   * Group the attributes into a key "attributes" and the value into a key of "value".
   */
  const XML_GROUP = 2;

  /**
   * Attributes will only be returned.
   */
  const XML_OVERWRITE = 3;

  /**
   * Returns a string for the detected type.
   *
   * @access public
   * @param mixed $data
   * @return string
   * @static
   */
  public static function is($data) {
    if (self::isArray($data)) {
      return 'array';

    } else if (self::isObject($data)) {
      return 'object';

    } else if (self::isJson($data)) {
      return 'json';

    } else if (self::isSerialized($data)) {
      return 'serialized';

    } else if (self::isXml($data)) {
      return 'xml';
    }

    return 'other';
  }

  /**
   * Check to see if data passed is an array.
   *
   * @access public
   * @param mixed $data
   * @return boolean
   * @static
   */
  public static function isArray($data) {
    return is_array($data);
  }

  /**
   * Check to see if data passed is a JSON object.
   *
   * @access public
   * @param mixed $data
   * @return boolean
   * @static
   */
  public static function isJson($data) {
    return (@json_decode($data) !== null);
  }

  /**
   * Check to see if data passed is an object.
   *
   * @access public
   * @param mixed $data
   * @return boolean
   * @static
   */
  public static function isObject($data) {
    return is_object($data);
  }

  /**
   * Check to see if data passed has been serialized.
   *
   * @access public
   * @param mixed $data
   * @return boolean
   * @static
   */
  public static function isSerialized($data) {
    $ser = @unserialize($data);

    return ($ser !== false) ? $ser : false;
  }

  /**
   * Check to see if data passed is an XML document.
   *
   * @access public
   * @param mixed $data
   * @return boolean
   * @static
   */
  public static function isXml($data) {
    $xml = @simplexml_load_string($data);

    return ($xml instanceof SimpleXmlElement) ? $xml : false;
  }

  /**
   * Transforms a resource into an array.
   *
   * @access public
   * @param mixed $resource
   * @return array
   * @static
   */
  public static function toArray($resource) {
    if (self::isArray($resource)) {
      return $resource;

    } else if (self::isObject($resource)) {
      return self::buildArray($resource);

    } else if (self::isJson($resource)) {
      return json_decode($resource, true);

    } else if ($ser = self::isSerialized($resource)) {
      return self::toArray($ser);

    } else if ($xml = self::isXml($resource)) {
      return self::xmlToArray($xml);
    }

    return $resource;
  }

  /**
   * Transforms a resource into a JSON object.
   *
   * @access public
   * @param mixed $resource
   * @return string (json)
   * @static
   */
  public static function toJson($resource) {
    if (self::isJson($resource)) {
      return $resource;
    }

    if ($xml = self::isXml($resource)) {
      $resource = self::xmlToArray($xml);

    } else if ($ser = self::isSerialized($resource)) {
      $resource = $ser;
    }

    return json_encode($resource);
  }

  /**
   * Transforms a resource into an object.
   *
   * @access public
   * @param mixed $resource
   * @return object
   * @static
   */
  public static function toObject($resource) {
    if (self::isObject($resource)) {
      return $resource;

    } else if (self::isArray($resource)) {
      return self::buildObject($resource);

    } else if (self::isJson($resource)) {
      return json_decode($resource);

    } else if ($ser = self::isSerialized($resource)) {
      return self::toObject($ser);

    } else if ($xml = self::isXml($resource)) {
      return $xml;
    }

    return $resource;
  }

  /**
   * Transforms a resource into a serialized form.
   *
   * @access public
   * @param mixed $resource
   * @return string
   * @static
   */
  public static function toSerialize($resource) {
    if (!self::isArray($resource)) {
      $resource = self::toArray($resource);
    }

    return serialize($resource);
  }

  /**
   * Transforms a resource into an XML document.
   *
   * @access public
   * @param mixed $resource
   * @param string $root
   * @return string (xml)
   * @static
   */
  public static function toXml($resource, $root = 'root') {
    if (self::isXml($resource)) {
      return $resource;
    }

    $array = self::toArray($resource);

    if (!empty($array)) {
      $xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><'. $root .'></'. $root .'>');
      $response = self::buildXml($xml, $array);

      return $response->asXML();
    }

    return $resource;
  }

  /**
   * Turn an object into an array. Alternative to array_map magic.
   *
   * @access public
   * @param object $object
   * @return array
   */
  public static function buildArray($object) {
    $array = array();

    foreach ($object as $key => $value) {
      if (is_object($value)) {
        $array[$key] = self::buildArray($value);
      } else {
        $array[$key] = $value;
      }
    }

    return $array;
  }

  /**
   * Turn an array into an object. Alternative to array_map magic.
   *
   * @access public
   * @param array $array
   * @return object
   */
  public static function buildObject($array) {
    $obj = new \stdClass();

    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $obj->{$key} = self::buildObject($value);
      } else {
        $obj->{$key} = $value;
      }
    }

    return $obj;
  }

  /**
   * Turn an array into an XML document. Alternative to array_map magic.
   *
   * @access public
   * @param object $xml
   * @param array $array
   * @return object
   */
  public static function buildXml(&$xml, $array) {
    if (is_array($array)) {
      foreach ($array as $key => $value) {
        // XML_NONE
        if (!is_array($value)) {
          $xml->addChild($key, $value);
          continue;
        }

        // Multiple nodes of the same name
        if (isset($value[0])) {
          foreach ($value as $kValue) {
            if (is_array($kValue)) {
              self::buildXml($xml, array($key => $kValue));
            } else {
              $xml->addChild($key, $kValue);
            }
          }

        // XML_GROUP
        } else if (isset($value['@attributes'])) {
          if (is_array($value['value'])) {
            $node = $xml->addChild($key);
            self::buildXml($node, $value['value']);
          } else {
            $node = $xml->addChild($key, $value['value']);
          }

          if (!empty($value['@attributes'])) {
            foreach ($value['@attributes'] as $aKey => $aValue) {
              $node->addAttribute($aKey, $aValue);
            }
          }

        // XML_MERGE
        } else if (isset($value['value'])) {
          $node = $xml->addChild($key, $value['value']);
          unset($value['value']);

          if (!empty($value)) {
            foreach ($value as $aKey => $aValue) {
              if (is_array($aValue)) {
                self::buildXml($node, array($aKey => $aValue));
              } else {
                $node->addAttribute($aKey, $aValue);
              }
            }
          }

        // XML_OVERWRITE
        } else {
          $node = $xml->addChild($key);

          if (!empty($value)) {
            foreach ($value as $aKey => $aValue) {
              if (is_array($aValue)) {
                self::buildXml($node, array($aKey => $aValue));
              } else {
                $node->addChild($aKey, $aValue);
              }
            }
          }
        }
      }
    }

    return $xml;
  }

  /**
   * Convert a SimpleXML object into an array.
   *
   * @access public
   * @param object $xml
   * @param int $format
   * @return array
   */
  public static function xmlToArray($xml, $format = self::XML_GROUP) {
    if (is_string($xml)) {
      $xml = @simplexml_load_string($xml);
    }
    if ( ! $xml ) { return false; }
    if (count($xml->children()) <= 0) {
      return (string)$xml;
    }

    $array = array();

    foreach ($xml->children() as $element => $node) {
      $data = array();

      if (!isset($array[$element])) {
#       $array[$element] = "";
        $array[$element] = [];
      }

      if (!$node->attributes() || $format === self::XML_NONE) {
        $data = self::xmlToArray($node, $format);

      } else {
        switch ($format) {
          case self::XML_GROUP:
            $data = array(
              '@attributes' => array(),
              'value' => (string)$node
            );

            if (count($node->children()) > 0) {
              $data['value'] = self::xmlToArray($node, $format);
            }

            foreach ($node->attributes() as $attr => $value) {
              $data['@attributes'][$attr] = (string)$value;
            }
          break;

          case self::XML_MERGE:
          case self::XML_OVERWRITE:
            if ($format === self::XML_MERGE) {
              if (count($node->children()) > 0) {
                $data = $data + self::xmlToArray($node, $format);
              } else {
                $data['value'] = (string)$node;
              }
            }

            foreach ($node->attributes() as $attr => $value) {
              $data[$attr] = (string)$value;
            }
          break;
        }
      }

      if (count($xml->{$element}) > 1) {
        $array[$element][] = $data;
      } else {
        $array[$element] = $data;
      }
    }

    return $array;
  }

  /**
   * Encode a resource object for UTF-8.
   *
   * @access public
   * @param mixed $data
   * @return array|string
   * @static
   */
  public static function utf8Encode($data) {
    if (is_string($data)) {
      return mb_convert_encoding($data,'UTF-8','ISO-8859-1');

    } else if (is_array($data)) {
      foreach ($data as $key => $value) {
        $data[mb_convert_encoding($key,'UTF-8','ISO-8859-1')] = self::utf8Encode($value);
      }

    } else if (is_object($data)) {
      foreach ($data as $key => $value) {
        $data->{$key} = self::utf8Encode($value);
      }
    }

    return $data;
  }

  /**
   * Decode a resource object for UTF-8.
   *
   * @access public
   * @param mixed $data 
   * @return array|string
   * @static
   */
  public static function utf8Decode($data) {
    if (is_string($data)) {
      return mb_convert_encoding($data,'UTF-8','ISO-8859-1');

    } else if (is_array($data)) {
      foreach ($data as $key => $value) {
        $data[mb_convert_encoding($key,'UTF-8','ISO-8859-1')] = self::utf8Decode($value);
      }

    } else if (is_object($data)) {
      foreach ($data as $key => $value) {
        $data->{$key} = self::utf8Decode($value);
      }
    }

    return $data;
  }

}

 /**
 * Array2XML: A class to convert array in PHP to XML
 * It also takes into account attributes names unlike SimpleXML in PHP
 * It returns the XML in form of DOMDocument class for further manipulation.
 * It throws exception if the tag name or attribute name has illegal chars.
 *
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (10 July 2011)
 * Version: 0.2 (16 August 2011)
 *          - replaced htmlentities() with htmlspecialchars() (Thanks to Liel Dulev)
 *          - fixed a edge case where root node has a false/null/0 value. (Thanks to Liel Dulev)
 * Version: 0.3 (22 August 2011)
 *          - fixed tag sanitize regex which didn't allow tagnames with single character.
 * Version: 0.4 (18 September 2011)
 *          - Added support for CDATA section using @cdata instead of @value.
 * Version: 0.5 (07 December 2011)
 *          - Changed logic to check numeric array indices not starting from 0.
 * Version: 0.6 (04 March 2012)
 *          - Code now doesn't @cdata to be placed in an empty array
 * Version: 0.7 (24 March 2012)
 *          - Reverted to version 0.5
 * Version: 0.8 (02 May 2012)
 *          - Removed htmlspecialchars() before adding to text node or attributes.
 *
 * Usage:
 *       $xml = Array2XML::createXML('root_node_name', $php_array);
 *       echo $xml->saveXML();
 */
class Array2XML {
    private static $xml = null;
  private static $encoding = 'UTF-8';
    /**
     * Initialize the root XML node [optional]
     * @param $version
     * @param $encoding
     * @param $format_output
     */
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true) {
        self::$xml = new DomDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
    self::$encoding = $encoding;
    }
    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DomDocument
     */
    public static function &createXML($node_name, $arr=array()) {
        $xml = self::getXMLRoot();
        $xml->appendChild(self::convert($node_name, $arr));
        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $xml;
    }
    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DOMNode
     */
    private static function &convert($node_name, $arr=array()) {
        //print_arr($node_name);
        $xml = self::getXMLRoot();
        $node = $xml->createElement($node_name);
        if(is_array($arr)){
            // get the attributes first.;
            if(isset($arr['@attributes'])) {
                foreach($arr['@attributes'] as $key => $value) {
                    if(!self::isValidTagName($key)) {
                        throw new Exception('[Array2XML] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr['@attributes']); //remove the key from the array once done.
            }
            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if(isset($arr['@value'])) {
                $node->appendChild($xml->createTextNode(self::bool2str($arr['@value'])));
                unset($arr['@value']);    //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            } else if(isset($arr['@cdata'])) {
                $node->appendChild($xml->createCDATASection(self::bool2str($arr['@cdata'])));
                unset($arr['@cdata']);    //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            }
        }
        //create subnodes using recursion
        if(is_array($arr)){
            // recurse to get the node for that key
            foreach($arr as $key=>$value){
                if(!self::isValidTagName($key)) {
                    throw new Exception('[Array2XML] Illegal character in tag name. tag: '.$key.' in node: '.$node_name);
                }
                if(is_array($value) && is_numeric(key($value))) {
                    // MORE THAN ONE NODE OF ITS KIND;
                    // if the new array is numeric index, means it is array of nodes of the same kind
                    // it should follow the parent key name
                    foreach($value as $k=>$v){
                        $node->appendChild(self::convert($key, $v));
                    }
                } else {
                    // ONLY ONE NODE OF ITS KIND
                    $node->appendChild(self::convert($key, $value));
                }
                unset($arr[$key]); //remove the key from the array once done.
            }
        }
        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if(!is_array($arr)) {
            $node->appendChild($xml->createTextNode(self::bool2str($arr ?? "")));
        }
        return $node;
    }
    /*
     * Get the root XML node, if there isn't one, create it.
     */
    private static function getXMLRoot(){
        if(empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }
    /*
     * Get string representation of boolean value
     */
    private static function bool2str($v){
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;
        return $v;
    }
    /*
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     */
    private static function isValidTagName($tag){
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}

/**
 * XML2Array: A class to convert XML to array in PHP
 * It returns the array which can be converted back to XML using the Array2XML script
 * It takes an XML string or a DOMDocument object as an input.
 *
 * See Array2XML: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 *
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/convert-xml-to-array-in-php-xml2array
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (07 Dec 2011)
 * Version: 0.2 (04 Mar 2012)
 *      Fixed typo 'DomDocument' to 'DOMDocument'
 *
 * Usage:
 *       $array = XML2Array::createArray($xml);
 */

class XML2Array {

    private static $xml = null;
  private static $encoding = 'UTF-8';

    /**
     * Initialize the root XML node [optional]
     * @param $version
     * @param $encoding
     * @param $format_output
     */
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true) {
        self::$xml = new DOMDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
    self::$encoding = $encoding;
    }

    /**
     * Convert an XML to Array
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DOMDocument
     */
    public static function &createArray($input_xml) {
        $xml = self::getXMLRoot();
    if(is_string($input_xml)) {
      $parsed = $xml->loadXML($input_xml);
      if(!$parsed) {
        throw new Exception('[XML2Array] Error parsing the XML string.');
      }
    } else {
      if(get_class($input_xml) != 'DOMDocument') {
        throw new Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
      }
      $xml = self::$xml = $input_xml;
    }
    $array[$xml->documentElement->tagName] = self::convert($xml->documentElement);
        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $array;
    }

    /**
     * Convert an Array to XML
     * @param mixed $node - XML as a string or as an object of DOMDocument
     * @return mixed
     */
    private static function &convert($node) {
    $output = array();

    switch ($node->nodeType) {
      case XML_CDATA_SECTION_NODE:
        $output['@cdata'] = trim($node->textContent);
        break;

      case XML_TEXT_NODE:
        $output = trim($node->textContent);
        break;

      case XML_ELEMENT_NODE:

        // for each child node, call the covert function recursively
        for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
          $child = $node->childNodes->item($i);
          $v = self::convert($child);
          if(isset($child->tagName)) {
            $t = $child->tagName;

            // assume more nodes of same kind are coming
            if(!isset($output[$t])) {
              $output[$t] = array();
            }
            $output[$t][] = $v;
          } else {
            //check if it is not an empty text node
            if($v !== '') {
              $output = $v;
            }
          }
        }

        if(is_array($output)) {
          // if only one node of its kind, assign it directly instead if array($value);
          foreach ($output as $t => $v) {
            if(is_array($v) && count($v)==1) {
              $output[$t] = $v[0];
            }
          }
          if(empty($output)) {
            //for empty nodes
            $output = '';
          }
        }

        // loop through the attributes and collect them
        if($node->attributes->length) {
          $a = array();
          foreach($node->attributes as $attrName => $attrNode) {
            $a[$attrName] = (string) $attrNode->value;
          }
          // if its an leaf node, store the value in @value instead of directly storing it.
          if(!is_array($output)) {
            $output = array('@value' => $output);
          }
          $output['@attributes'] = $a;
        }
        break;
    }
    return $output;
    }

    /*
     * Get the root XML node, if there isn't one, create it.
     */
    private static function getXMLRoot(){
        if(empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }
}
?>