<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2017, Andrew Zawadzki #
#                                                             #
###############################################################

##################################################################################################################
# Convert Array("one","two","three") to be Array("one"=>$defaultFlag, "two"=>$defaultFlag, "three"=>$defaultFlag #
##################################################################################################################
function arrayEntriesToObject($sourceArray,$defaultFlag=true) {
	if ( ! is_array($sourceArray) ) {
		return array();
	}
	foreach ($sourceArray as $entry) {
		$newArray[$entry] = $defaultFlag;
	}
	return $newArray;
}

####################################################################################################
# 2 Functions because unRaid includes comments in .cfg files starting with # in violation of PHP 7 #
####################################################################################################
function my_parse_ini_file($file,$mode=false,$scanner_mode=INI_SCANNER_NORMAL) {
	return parse_ini_string(preg_replace('/^#.*\\n/m', "", @file_get_contents($file)),$mode,$scanner_mode);
}
function my_parse_ini_string($string, $mode=false,$scanner_mode=INI_SCANNER_NORMAL) {
	return parse_ini_string(preg_replace('/^#.*\\n/m', "", $string),$mode,$scanner_mode);
}

###########################################################################
# Helper function to determine if a plugin has an update available or not #
###########################################################################
function checkPluginUpdate($filename) {
	global $unRaidVersion;

	$filename = basename($filename);
	$installedVersion = plugin("version","/var/log/plugins/$filename");
	$upgradeVersion = (is_file("/tmp/plugins/$filename")) ? plugin("version","/tmp/plugins/$filename") : "0";

	if ( $installedVersion < $upgradeVersion ) {
		$unRaid = plugin("unRAID","/tmp/plugins/$filename");
		if ( $unRaid === false || version_compare($unRaidVersion,$unRaid,">=") ) {
			return true;
		} else {
			return false;
		}
	}
	return false;
}

#############################################################
# Helper function to return an array of directory contents. #
# Returns an empty array if the directory does not exist    #
#############################################################
function dirContents($path) {
	$dirContents = @scandir($path);
	if ( ! $dirContents ) { $dirContents = array(); }
	return array_diff($dirContents,array(".",".."));
}

###################################################################################
# returns a random file name (/tmp/community.applications/tempFiles/34234234.tmp) #
###################################################################################
function randomFile() {
	global $communityPaths;

	return tempnam($communityPaths['tempFiles'],"CA-Temp-");
}

##################################################################
# 7 Functions to avoid typing the same lines over and over again #
##################################################################
function readJsonFile($filename) {
	$json = json_decode(@file_get_contents($filename),true);
	if ( ! is_array($json) ) { $json = array(); }
	return $json;
}
function writeJsonFile($filename,$jsonArray) {
	file_put_contents($filename,json_encode($jsonArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
function download_url($url, $path = "", $bg = false){
	exec("curl -H 'Cache-Control: no-cache' --compressed --max-time 60 --silent --insecure --location --fail ".($path ? " -o '$path' " : "")." $url ".($bg ? ">/dev/null 2>&1 &" : "2>/dev/null"), $out, $exit_code );
	return ($exit_code === 0 ) ? implode("\n", $out) : false;
}
function download_json($url,$path) {
	download_url($url,$path);
	return readJsonFile($path);
}
function getPost($setting,$default) {
	return isset($_POST[$setting]) ? urldecode(($_POST[$setting])) : $default;
}
function getPostArray($setting) {
	return $_POST[$setting];
}
function getSortOrder($sortArray) {
	if ( ! is_array($sortArray) ) { print_r($_POST); }
	foreach ($sortArray as $sort) {
		$sortOrder[$sort[0]] = $sort[1];
	}
	return $sortOrder;
}

#################################################################
# Helper function to determine if $haystack begins with $needle #
#################################################################
function startsWith($haystack, $needle) {
	return $needle === "" || strripos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

#######################################################################################
# Helper function to further remove formatting from descriptions (suitable for popUps #
#######################################################################################
function fixPopUpDescription($PopUpDescription) {
	$PopUpDescription = str_replace("'","&#39;",$PopUpDescription);
	$PopUpDescription = str_replace('"','&quot;',$PopUpDescription);
	$PopUpDescription = strip_tags($PopUpDescription);
	$PopUpDescription = trim($PopUpDescription);
	return ($PopUpDescription);
}

###################################################################
# Helper function to remove any formatting, etc from descriptions #
###################################################################
function fixDescription($Description) {
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
	return $Description;
}

########################################################################
# Security function to remove any <script> tags from elements that are #
# displayed as is                                                      #
########################################################################

# pass a copy of the original template to relate security violations back to the template
function fixSecurity(&$template,&$originalTemplate) {
	foreach ($template as &$element) {
		if ( is_array($element) ) {
			fixSecurity($element,$originalTemplate);
		} else {
			$tempElement = htmlspecialchars_decode($element);
			if ( preg_match('#<script(.*?)>(.*?)</script>#is',$tempElement) || preg_match('#<iframe(.*?)>(.*?)</iframe>#is',$tempElement) ) {
				logger("VERY IMPORTANT IF YOU SEE THIS: Alert the maintainers of Community Applications with the following Information:".$originalTemplate['RepoName']." ".$originalTemplate['Name']." ".$originalTemplate['Repository']);
				$originalTemplate['Blacklist'] = true;
				return;
			}
		}
	}
}

#######################
# Custom sort routine #
#######################
function mySort($a, $b) {
	global $sortOrder;

	if ( $sortOrder['sortBy'] != "downloads" ) {
		$c = strtolower($a[$sortOrder['sortBy']]);
		$d = strtolower($b[$sortOrder['sortBy']]);
	} else {
		$c = $a[$sortOrder['sortBy']];
		$d = $b[$sortOrder['sortBy']];
	}
	$return1 = ($sortOrder['sortDir'] == "Down") ? -1 : 1;
	$return2 = ($sortOrder['sortDir'] == "Down") ? 1 : -1;

	if ($c > $d) { return $return1; }
	else if ($c < $d) { return $return2; }
	else { return 0; }
}

###############################################
# Search array for a particular key and value #
# returns the index number of the array       #
# return value === false if not found         #
###############################################
function searchArray($array,$key,$value) {
	$result = false;
	if (count($array) ) {
		for ($i = 0; $i <= max(array_keys($array)); $i++) {
			if ( $array[$i][$key] == $value ) {
				$result = $i;
				break;
			}
		}
	}
	return $result;
}

#############################
# Highlights search results #
#############################
function highlight($text, $search) {
	return preg_replace('#'. preg_quote($text,'#') .'#si', '<span style="background-color:#FFFF66; color:#FF0000;font-weight:bold;">\\0</span>', $search);
}

########################################################
# Fix common problems (maintainer errors) in templates #
########################################################
function fixTemplates($template) {
	global $statistics;

	$origStats = $statistics;
# this fix must always be the first test
	if ( is_array($template['Repository']) ) {                 # due to cmer
		$template['Repository'] = $template['Repository'][0];
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Fatal: Multiple Repositories Found - Removing application from lists";
    return false;
	}
	if ( (is_array($template['Support'])) && (count($template['Support'])) ) {
		unset($template['Support']);
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Multiple Support Tags Found";
	}
	if ( ! is_string($template['Name'])  ) {
		$template['Name']=" ";
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Name is not a string";
	}
	if ( ! is_string($template['Author']) ) {
		$template['Author']=" ";
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Author is not a string";
	}
	if ( is_array($template['Description']) ) {
		$template['Description']="";
		if ( count($template['Description']) > 1 ) {
			$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Fatal: Multiple Description tags present";
			$statistics['caFixed']++;
      return false;
		}
	}
	if ( is_array($template['Beta']) ) {
		$template['Beta'] = "false";
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Multiple Beta tags found";
	} else {
		$template['Beta'] = strtolower(stripslashes($template['Beta']));
	}
	$template['Date'] = ( $template['Date'] ) ? strtotime( $template['Date'] ) : 0;
	if ( $template['Date'] > strtotime("+2 day") ) {
		$template['Date'] = 0;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Invalid Date Updated (More than 2 days in the future) Format used probably not in http://php.net/manual/en/datetime.formats.date.php";
	}
	if ( ! $template['MinVer'] ) {
		$template['MinVer'] = $template['Plugin'] ? "6.1" : "6.0";
	}
	if ( is_array($template['Category']) ) {
		$template['Category'] = $template['Category'][0];        # due to lsio / CHBMB
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Multiple Category tags or Category present but empty";
	}
	$template['Category'] = $template['Category'] ?: "Uncategorized";
	if ( ! is_string($template['Category']) ) {
		$template['Category'] = "Uncategorized";
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Multiple Category tags or Category present but empty";
	}
	if ( !is_string($template['Overview']) ) {
		unset($template['Overview']);
	}
	if ( is_array($template['SortAuthor']) ) {                 # due to cmer
		$template['SortAuthor'] = $template['SortAuthor'][0];
		$template['Author'] = $template['SortAuthor'];
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Multiple Authors / Repositories Found";
	}
	if ( is_array($template['PluginURL']) ) {                  # due to coppit
		$template['PluginURL'] = $template['PluginURL'][1];
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Fatal: Multiple PluginURL's found";
    return false;
	}
	if ( $template['PluginURL'] ) {                            # due to bonienl
		$template['PluginURL'] = str_replace("raw.github.com","raw.githubusercontent.com",$template['PluginURL']);
		$template['Repository'] = $template['PluginURL'];
	}
	if ( strlen($template['Overview']) > 0 ) {
		$template['Description'] = $template['Overview'];
		$template['Description'] = preg_replace('#\[([^\]]*)\]#', '<$1>', $template['Description']);
		$template['Description'] = fixDescription($template['Description']);
		$template['Overview'] = $template['Description'];
	} else {
  	$template['Description'] = fixDescription($template['Description']);
	}
	if ( ( ! strlen(trim($template['Overview'])) ) && ( ! strlen(trim($template['Description'])) ) && ! $template['Private'] ){
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Fatal: No valid Overview Or Description present - Application dropped from CA automatically - Possibly far too many formatting tags present";
    return false;
	}
	if ( ! $template['Icon'] ) {
		$statistics['caFixed']++;
		$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "No Icon specified within the application template";
	}
	if ( ( stripos($template['RepoName'],' beta') > 0 )  ) {
		$template['Beta'] = "true";
	}
	$template['Support'] = validURL($template['Support']);
	$template['Project'] = validURL($template['Project']);
	$template['DonateLink'] = validURL($template['DonateLink']);
	$template['DonateImg'] = validURL($template['DonateImg']);
	$template['DonateText'] = str_replace("'","&#39;",$template['DonateText']);
	$template['DonateText'] = str_replace('"','&quot;',$template['DonateText']);
  $template['Date'] = $template['Date'] ?: $template['DateInstalled'];
	
	# support v6.2 redefining deprecating the <Beta> tag and moving it to a category
	if ( stripos($template['Category'],":Beta") ) {
		$template['Beta'] = "true";
	} else {
		if ( $template['Beta'] === "true" ) {
			$template['Category'] .= " Status:Beta";
		}
	}
	$template['PopUpDescription'] = fixPopUpDescription($template['Description']);
	if ( $template['Private'] ) {
		$statistics = $origStats;
	}
	return $template;
}

###############################################
# Function used to create XML's from appFeeds #
###############################################
function makeXML($template) {
	# ensure its a v2 template if the Config entries exist
	if ( $template['Config'] && ! $template['@attributes'] ) {
		$template['@attributes'] = array("version"=>2);
	}
	fixAttributes($template,"Network");
	fixAttributes($template,"Config");
	
	$Array2XML = new Array2XML();
	$xml = $Array2XML->createXML("Container",$template);
	return $xml->saveXML();
}

#################################################################################
# Function to fix differing schema in the appfeed vs what Array2XML class wants #
#################################################################################
function fixAttributes(&$template,$attribute) {
	if ( ! is_array($template[$attribute]) ) {
		return;
	}
	if ( $template[$attribute]['@attributes'] ) {
		$template[$attribute][0]['@attributes'] = $template[$attribute]['@attributes'];
		if ( $template[$attribute]['value']) {
			$template[$attribute][0]['value'] = $template[$attribute]['value'];
		}
		unset($template[$attribute]['@attributes']);
		unset($template[$attribute]['value']);
	}

	if ( $template[$attribute] ) {
		foreach ($template[$attribute] as $tempArray) {
			if ( $tempArray['value'] ) {
				$tempArray2[] = array('@attributes'=>$tempArray['@attributes'],'@value'=>$tempArray['value']);
			} else {
				$tempArray2[] = array('@attributes'=>$tempArray['@attributes']);
			}
		}
		$template[$attribute] = $tempArray2;
	}
}

#################################################################
# checks the Min/Max version of an app against unRaid's version #
# Returns: TRUE if it's valid to run, FALSE if not              #
#################################################################
function versionCheck($template) {
	global $unRaidVersion;

	if ( $template['MinVer'] && ( version_compare($template['MinVer'],$unRaidVersion) > 0 ) ) { return false; }
	if ( $template['MaxVer'] && ( version_compare($template['MaxVer'],$unRaidVersion) < 0 ) ) { return false; }
	return true;
}

###############################################
# Function to read a template XML to an array #
###############################################
function readXmlFile($xmlfile) {
	global $statistics;

	$xml = file_get_contents($xmlfile);
	$o = TypeConverter::xmlToArray($xml,TypeConverter::XML_GROUP);
	if ( ! $o ) { return false; }

	# Fix some errors in templates prior to continuing

	if ( is_array($o['SortAuthor']) ) {
		$o['SortAuthor'] = $o['SortAuthor'][0];  $statistics['caFixed']++;
	}
	if ( is_array($o['Repository']) ) {
		$o['Repository'] = $o['Repository'][0];  $statistics['caFixed']++;
	}
	$o['Path']          = $xmlfile;
	$o['Author']        = preg_replace("#/.*#", "", $o['Repository']);
	$o['DockerHubName'] = strtolower($o['Name']);
	$o['Base']          = $o['BaseImage'];
	$o['SortAuthor']    = $o['Author'];
	$o['SortName']      = $o['Name'];
	$o['Forum']         = $Repo['forum'];
# configure the config attributes to same format as appfeed
# handle the case where there is only a single <Config> entry

	if ( $o['Config']['@attributes'] ) {
		$o['Config'] = array('@attributes'=>$o['Config']['@attributes'],'value'=>$o['Config']['value']);
	}
	if ( $o['Plugin'] ) {
		$o['Author']     = $o['PluginAuthor'];
		$o['Repository'] = $o['PluginURL'];
		$o['Category']   .= " Plugins: ";
		$o['SortAuthor'] = $o['Author'];
		$o['SortName']   = $o['Name'];
		$statistics['plugin']++;
	} else {
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
	global $communityPaths;

	$templates = readJsonFile($communityPaths['community-templates-info']);
	$moderation = readJsonFile($communityPaths['moderation']);
	$repositories = readJsonFile($communityPaths['Repositories']);
	foreach ($repositories as $repo) {
		if ( is_array($repo['duplicated']) ) {
			$duplicatedTemplate[$repo['url']] = $repo;
		}
	}
	if ( ! $templates ) { return; }
	foreach ($templates as $template) {
		$templateTMP = $template;
		if ( is_array($moderation[$template['Repository']]) ) {
      $templateTMP = array_merge($template,$moderation[$template['Repository']]);
		}
		if ( $duplicatedTemplate[$templateTMP['RepoURL']]['duplicated'][$template['Repository']] ) {
			$templateTMP['Blacklist'] = true;
			$templateTMP['ModeratorComment'] = "Duplicated Template";
		}
		$templateTMP['Compatible'] = versionCheck($templateTMP);
		$o[] = $templateTMP;
	}
	writeJsonFile($communityPaths['community-templates-info'],$o);
}

############################################
# Function to write a string to the syslog #
############################################
function logger($string) {
	exec("logger ".escapeshellarg($string));
}

#######################################################
# Function to check for a valid URL                   #
#######################################################
function validURL($URL) {
	if ( function_exists("filter_var") ) {  # function only works on unRaid 6.1.8+
		return filter_var($URL, FILTER_VALIDATE_URL);
	} else {
		return $URL;
	}
}

####################################################################################
# Read the pinned apps from temp files.  If it fails, gets it from the flash drive #
####################################################################################
function getPinnedApps() {
	global $communityPaths;

	return readJsonFile($communityPaths['pinned']);
}

#################################################
# Sets the updateButton to the appropriate Mode #
#################################################
function caGetMode() {
	global $communityPaths, $communitySettings;

	$script = ( is_file($communityPaths['LegacyMode']) ) ? "$('#updateButton').html('appFeed Mode');" : "$('#updateButton').html('Legacy Mode');";
  $script .= ( is_file($communityPaths['LegacyMode'] ) || ($communitySettings['maintainer'] == "yes") ) ? "$('#updateButton').show();" : "$('#updateButton').hide();";
	return "<script>$script</script>";
}

################################################
# Returns the actual URL after any redirection #
################################################
# works, but very slow.  Switched to a simple string replace as all redirects are plugin and simply github.com/raw/ vs raw.github.usercontent/
function getRedirectedURL($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$a = curl_exec($ch);
	return curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
}

###########################################################
# Returns the maximum number of columns per display width #
###########################################################
function getMaxColumns($windowWidth) {
	global $communitySettings, $templateSkin, $unRaid64;

	if ( ! $unRaid64 ) {
		$communitySettings['maxDetailColumns'] = 2;
		$communitySettings['maxIconColumns'] = 5;
		return;
	}
	$communitySettings['windowWidth'] = $windowWidth;
	$communitySettings['maxDetailColumns'] = floor($windowWidth / $templateSkin['detail']['templateWidth']);
	$communitySettings['maxIconColumns'] = floor($windowWidth / $templateSkin['icon']['templateWidth']);
	if ( ! $communitySettings['maxDetailColumns'] ) $communitySettings['maxDetailColumns'] = 1;
	if ( ! $communitySettings['maxIconColumns'] ) $communitySettings['maxIconColumns'] = 1;
}

#######################
# Creates an ini file #
#######################
function create_ini_file($settings,$mode=false) {
	if ( $mode ) {
		$keys = array_keys($settings);

		foreach ($keys as $key) {
			$iniFile .= "[$key]\r\n";
			$entryKeys = array_keys($settings[$key]);
			foreach ($entryKeys as $entry) {
				$iniFile .= $entry.'="'.$settings[$key][$entry].'"'."\r\n";
			}
		}
	} else {
		$entryKeys = array_keys($settings);
		foreach ($entryKeys as $entry) {
			$iniFile .= $entry.'="'.$settings[$entry].'"'."\r\n";
		}
	}
	return $iniFile;
}

#######################################################
# Function used to determine if a search term matches #
#######################################################
function filterMatch($filter,$searchArray) {
	$filterwords = explode(" ",$filter);
	foreach ( $filterwords as $testfilter) {
		foreach ($searchArray as $search) {
	  	if ( preg_match("#$testfilter#i",str_replace(" ","",$search)) ) {
        $foundword++;
				break;
			}
		}	
	}
	return ($foundword == count($filterwords));
}

###################################################################
# Used to update the last time synced to keep browsers up to date #
###################################################################
function updateSyncTime($updateSyncFlag) {
	global $communityPaths;
	
	$updateTime = $updateSyncFlag ? time() : @file_get_contents($communityPaths['lastUpdated-sync']);
	if ( ! $updateTime ) {
		$updateTime = time();
	}
 	echo "<script>data_lastUpdated = $updateTime;</script>";
	file_put_contents($communityPaths['lastUpdated-sync'],$updateTime);
}

##########################################################
# Used to figure out which plugins have duplicated names #
##########################################################
function pluginDupe($templates) {
	global $communityPaths;
	
	foreach ($templates as $template) {
		if ( ! $template['Plugin'] ) {
			continue;
		}
		$pluginList[basename($template['Repository'])]++;
	}
	foreach (array_keys($pluginList) as $plugin) {
		if ( $pluginList[$plugin] > 1 ) {
			$dupeList[$plugin]++;
		}
	}
	writeJsonFile($communityPaths['pluginDupes'],$dupeList);
}

###################################
# Checks if a plugin is installed #
###################################
function checkInstalledPlugin($template) {
	global $communityPaths;
	
	$pluginName = basename($template['PluginURL']);
	if ( ! file_exists("/var/log/plugins/$pluginName") ) {
		return false;
	}
	$dupeList = readJsonFile($communityPaths['pluginDupes']);
	if ( ! $dupeList[$pluginName] ) {
		return true;
	}
	if ( strtolower(trim(plugin("pluginURL","/var/log/plugins/$pluginName"))) != strtolower(trim($template['PluginURL']))) {
		return false;
	} else {
		return true;
	}
}

####################################################################################################################################################################
# Locking of display is needed because of edge cases with multiple tabs open, and removing applications (which cause a rescan of feed, etc) the possibility exists #
# for the second tab to recreate displayed.json  Check_stale will not run if display is locked                                                                     #
####################################################################################################################################################################
function lockDisplay($lock = true) {
	global $communityPaths;
	
	if ($lock) {
		file_put_contents($communityPaths['displayLocked'],"No changes allowed to display.json");
	} else {
		@unlink($communityPaths['displayLocked']);
	}
}
function isdisplayLocked() {
	global $communityPaths;
	
	return is_file($communityPaths['displayLocked']);
}

function jsonError($error) {
	switch ( $error ) {
		case JSON_ERROR_NONE:
			return "No error occurred";
			break;
		case JSON_ERROR_DEPTH:
			return "The maximum stack depth has been exceeded";
			break;
		case JSON_ERROR_STATE_MISMATCH:
			return "Invalid or malformed JSON";
			break;
		case JSON_ERROR_CTRL_CHAR:
			return "Control character error, possibly incorrectly encoded";
			break;
		case JSON_ERROR_SYNTAX:
			return "Syntax error";
			break;
		case JSON_ERROR_UTF8:
			return "Malformed UTF-8 characters, possibly incorrectly encoded";
			break;
		case JSON_ERROR_RECURSION:
			return "One or more recursive references in the value to be encoded";
			break;
		case JSON_ERROR_INF_OR_NAN:
			return "One or more NAN or INF values in the value to be encoded";
			break;
		case JSON_ERROR_UNSUPPORTED_TYPE:
			return "A value of a type that cannot be encoded was given";
			break;
		default: 
			return "Unknown error";
			break;
	}
}

############################################################################
# Function to convert a template's associative tags to static numeric tags #
# (Because the associate tag order can change depending upon the template) #
############################################################################
function toNumericArray($template) {
	return array(
		$template['Repository'],              # 1
		$template['Author'],                  # 2
		$template['Name'],                    # 3
		$template['DockerHubName'],           # 4
		$template['Beta'],                    # 5
		$template['Changes'],                 # 6
		$template['Date'],                    # 7
		$template['RepoName'],                # 8
		$template['Project'],                 # 9
		$template['ID'],                      #10
		$template['Base'],                    #11
		$template['BaseImage'],               #12
		$template['SortAuthor'],              #13
		$template['SortName'],                #14
		$template['Licence'],                 #15
		$template['Plugin'],                  #16
		$template['PluginURL'],               #17
		$template['PluginAuthor'],            #18
		$template['MinVer'],                  #19
		$template['MaxVer'],                  #20
		$template['Category'],                #21
		$template['Description'],             #22
		$template['Overview'],                #23
		$template['Downloads'],               #24
		$template['Stars'],                   #25
		$template['Announcement'],            #26
		$template['Support'],                 #27
		$template['IconWeb'],                 #28
		$template['DonateText'],              #29
		$template['DonateImg'],               #30
		$template['DonateLink'],              #31
		$template['PopUpDescription'],        #32
		$template['ModeratorComment'],        #33
		$template['Compatible'],              #34
		$template['display_DonateLink'],      #35
		$template['display_Project'],         #36
		$template['display_Support'],         #37
		$template['display_UpdateAvailable'], #38
		$template['display_ModeratorComment'],#39
		$template['display_Announcement'],    #40
		$template['display_Stars'],           #41
		$template['display_Downloads'],       #42
		$template['display_pinButton'],       #43
		$template['display_Uninstall'],       #44
		$template['display_removable'],       #45
		$template['display_newIcon'],         #46
		$template['display_changes'],         #47
		$template['display_webPage'],         #48
		$template['display_humanDate'],       #49
		$template['display_pluginSettings'],  #50
		$template['display_pluginInstall'],   #51
		$template['display_dockerDefault'],   #52
		$template['display_dockerEdit'],      #53
		$template['display_dockerReinstall'], #54
		$template['display_dockerInstall'],   #55
		$template['display_dockerDisable'],   #56
		$template['display_compatible'],      #57
		$template['display_compatibleShort'], #58
		$template['display_author'],          #59
		$template['display_iconSmall'],       #60
		$template['display_iconSelectable'],  #61
		$template['display_popupDesc'],       #62
		$template['display_updateAvail'],     #63  *** NO LONGER USED - USE #38 instead
		$template['display_dateUpdated'],     #64
		$template['display_iconClickable'],   #65
		str_replace("-"," ",$template['display_dockerName']),      #66
		$template['Path'],                    #67
		$template['display_pluginInstallIcon'],#68
		$template['display_dockerDefaultIcon'],#69
		$template['display_dockerEditIcon'],  #70
		$template['display_dockerReinstallIcon'], #71
		$template['display_dockerInstallIcon'], #72
		$template['display_pluginSettingsIcon'], #73
		$template['dockerWebIcon'],            #74
		$template['display_multi_install']     #75
	);
}
?>