<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2018, Andrew Zawadzki #
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
if ( ! function_exists("my_parse_ini_file") ) {
	function my_parse_ini_file($file,$mode=false,$scanner_mode=INI_SCANNER_NORMAL) {
		return parse_ini_string(preg_replace('/^#.*\\n/m', "", @file_get_contents($file)),$mode,$scanner_mode);
	}
}
if ( ! function_exists("my_parse_ini_string") ) {
	function my_parse_ini_string($string, $mode=false,$scanner_mode=INI_SCANNER_NORMAL) {
		return parse_ini_string(preg_replace('/^#.*\\n/m', "", $string),$mode,$scanner_mode);
	}
}

###########################################################################
# Helper function to determine if a plugin has an update available or not #
###########################################################################
function checkPluginUpdate($filename) {
	global $unRaidVersion;

	$filename = basename($filename);
	$upgradeVersion = (is_file("/tmp/plugins/$filename")) ? plugin("version","/tmp/plugins/$filename") : "0";
	$installedVersion = $upgradeVersion ? plugin("version","/var/log/plugins/$filename") : 0;

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
function download_url($url, $path = "", $bg = false, $timeout=45){
	if ( ! strpos($url,"?") ) {
		$url .= "?".time(); # append time to always wind up requesting a non cached version
	}
	exec("curl --compressed --max-time $timeout --silent --insecure --location --fail ".($path ? " -o '$path' " : "")." $url ".($bg ? ">/dev/null 2>&1 &" : "2>/dev/null"), $out, $exit_code );
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

###################################################################
# Helper function to only replace the first occurance in a string #
###################################################################
function first_str_replace($haystack, $needle, $replace) {
	$pos = strpos($haystack, $needle);
	if ($pos !== false) {
		return substr_replace($haystack, $replace, $pos, strlen($needle));
	}
	return $haystack;
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
	} else {
		return "";
	}
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
	if ( is_array($template['PostArgs']) ) {
		if ( ! count($template['PostArgs']) ) {
			$template['PostArgs'] = "";
		} else {
			$template['PostArgs'] = $template['PostArgs'][0];
		}
	}
	$postArgs = explode(";",$template['PostArgs']);
	if ( $postArgs[1] ) {
		logger("VERY IMPORTANT IF YOU SEE THIS: Alert the maintainers of Community Applications with the following Information:".$originalTemplate['RepoName']." ".$originalTemplate['Name']." ".$originalTemplate['Repository']);
		$originalTemplate['Blacklist'] = true;
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
function searchArray($array,$key,$value,$startingIndex=0) {
	$result = false;
	if (count($array) ) {
		for ($i = $startingIndex; $i <= max(array_keys($array)); $i++) {
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
	return preg_replace('#'. preg_quote($text,'#') .'#si', '<span style="color:#FF0000;font-weight:bold;">\\0</span>', $search);
}

########################################################
# Fix common problems (maintainer errors) in templates #
########################################################
function fixTemplates($template) {
	global $statistics, $communitySettings;

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
		if ( count($template['Description']) > 1 ) {
			$template['Description']="";
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
	
	#Fix where authors make category entries themselves, and don't include the trailing colon (due to #rix1337)
	$categories = explode(" ",$template['Category']);
	unset($template['Category']);
	foreach ($categories as $category) {
		if ( strlen($category) && ! strpos($category,":") ) {
			$category .= ":";
			$statistics['caFixed']++;
			$statistics['fixedTemplates'][$template['Repo']][$template['Repository']][] = "Improperly formed category entry (a colon is always present)";
		}
		$template['Category'] .= "$category ";
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
	$template['Overview'] = is_string($template['Overview']) ? $template['Overview'] : "";
	$template['Description'] = is_string($template['Description']) ? $template['Description'] : "";
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
	if ( ! $template['Date'] ) {
		$template['Date'] = (is_numeric($template['DateInstalled'])) ? $template['DateInstalled'] : 0;
	}
	
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

	# fix where template author includes <Blacklist> or <Deprecated> entries in template (CA used booleans, but appfeed winds up saying "FALSE" which equates to be true
	$template['Deprecated'] = filter_var($template['Deprecated'],FILTER_VALIDATE_BOOLEAN);
	$template['Blacklist'] = filter_var($template['Blacklist'],FILTER_VALIDATE_BOOLEAN);

	if ( $template['DeprecatedMaxVer'] && version_compare($communitySettings['unRaidVersion'],$template['DeprecatedMaxVer'],">") ) {
		$template['Deprecated'] = true;
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
	global $communityPaths,$communitySettings;

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
			if ( $templateTMP['CAComment'] ) {
				$templateTMP['ModeratorComment'] = $templateTMP['CAComment'];
			}
		}
		if ( $duplicatedTemplate[$templateTMP['RepoURL']]['duplicated'][$template['Repository']] ) {
			$templateTMP['Blacklist'] = true;
			$templateTMP['ModeratorComment'] = "Duplicated Template";
		}
		$templateTMP['Compatible'] = versionCheck($templateTMP);
		if ( $templateTMP["DeprecatedMaxVer"] && version_compare($communitySettings['unRaidVersion'],$templateTMP["DeprecatedMaxVer"],">") ) {
			$templateTMP['Deprecated'] = true;
		}
		$o[] = $templateTMP;
	}
	writeJsonFile($communityPaths['community-templates-info'],$o);
	pluginDupe($o);
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
	return filter_var($URL, FILTER_VALIDATE_URL);
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
	global $communitySettings, $communityPaths;

	$templateSkin = readJsonFile($communityPaths['defaultSkin']);   # Global Var used in helpers ( getMaxColumns() )
	$communitySettings['windowWidth'] = $windowWidth;
	$communitySettings['maxDetailColumns'] = floor($windowWidth / $templateSkin['detail']['templateWidth']);
	if ( ! $communitySettings['maxDetailColumns'] ) $communitySettings['maxDetailColumns'] = 1;
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

##########################################################
# Used to figure out which plugins have duplicated names #
##########################################################
function pluginDupe($templates) {
	global $communityPaths;
	
	$pluginList = array();
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
	global $communitySettings;

	$useragent=$_SERVER['HTTP_USER_AGENT'];
	return (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)));
}

######################################
# Returns human readable JSON errors #
######################################
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
?>