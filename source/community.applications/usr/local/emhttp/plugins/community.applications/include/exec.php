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
require_once("/usr/local/emhttp/plugins/community.applications/include/xmlHelpers.php");
require_once($communityPaths['defaultSkinPHP']);

$plugin = "community.applications";
$DockerTemplates = new DockerTemplates();

$unRaidSettings = my_parse_ini_file($communityPaths['unRaidVersion']);
$unRaidVersion = $unRaidSettings['version'];
if ($unRaidVersion == "6.2") $unRaidVersion = "6.2.0";
$unRaid64 = (version_compare($unRaidVersion,"6.4.0-rc0",">="));
$unRaid635 = (version_compare($unRaidVersion,"6.3.5",">="));

if ( ! $unRaid64 ) {
	$communityPaths['defaultSkin'] = $communityPaths['legacySkin'];
}
$templateSkin = readJsonFile($communityPaths['defaultSkin']);   # Global Var used in helpers ( getMaxColumns() )

################################################################################
#                                                                              #
# Set up any default settings (when not explicitely set by the settings module #
#                                                                              #
################################################################################

$communitySettings = parse_plugin_cfg("$plugin");
$communitySettings['appFeed']       = "true"; # set default for deprecated setting
$communitySettings['maxPerPage']    = getPost("maxPerPage",$communitySettings['maxPerPage']);  # Global POST.  Used damn near everywhere
$communitySettings['iconSize']      = 96;
$communitySettings['maxColumn']     = 5; # Pointless on 6.3  Gets overridden on 6.4 anyways

if ( $communitySettings['favourite'] != "None" ) {
	$officialRepo = str_replace("*","'",$communitySettings['favourite']);
	$separateOfficial = true;
}
$dockerDaemon = $unRaid64 ? "/var/run/dockerd.pid" : "/var/run/docker.pid";

if ( is_file($dockerDaemon) && is_dir("/proc/".@file_get_contents($dockerDaemon)) ) {
	$communitySettings['dockerRunning'] = "true";
} else {
	$communitySettings['dockerSearch'] = "no";
	unset($communitySettings['dockerRunning']);
}

if ( $communitySettings['dockerRunning'] ) {
	$info = $DockerTemplates->getAllInfo();
	$DockerClient = new DockerClient();
	$dockerRunning = $DockerClient->getDockerContainers();
} else {
	$info = array();
	$dockerRunning = array();
}

exec("mkdir -p ".$communityPaths['tempFiles']);
exec("mkdir -p ".$communityPaths['persistentDataStore']);

if ( !is_dir($communityPaths['templates-community']) ) {
	exec("mkdir -p ".$communityPaths['templates-community']);
	@unlink($infoFile);
}

$selectCategoryMessage = "Select a Section <i id='sectionIcon' class='fa fa-bars enabledIcon' aria-hidden='true' style='font-size:30px;cursor:auto;'></i> or Category <i id='categoryIcon' class='fa fa-folder enabledIcon' aria-hidden='true' style='font-size:30px;cursor:auto;'></i> above";

#################################################################
#                                                               #
# Functions used to download the templates from various sources #
#                                                               #
#################################################################
function DownloadCommunityTemplates() {
	global $communityPaths, $infoFile, $plugin, $communitySettings, $statistics;

	$moderation = readJsonFile($communityPaths['moderation']);

	$DockerTemplates = new DockerTemplates();
	$tmpFileName = randomFile();
	$Repos = download_json($communityPaths['community-templates-url'],$tmpFileName);
	@unlink($tmpFileName);

	if ( ! is_array($Repos) ) { return false; }
	$statistics['repository'] = count($Repos);

	$appCount = 0;
	$myTemplates = array();

	exec("rm -rf '{$communityPaths['templates-community']}'");
	@unlink($communityPaths['updateErrors']);

	$templates = array();
	foreach ($Repos as $downloadRepo) {
		$downloadURL = randomFile();
		file_put_contents($downloadURL, $downloadRepo['url']);
		$friendlyName = str_replace(" ","",$downloadRepo['name']);
		$friendlyName = str_replace("'","",$friendlyName);
		$friendlyName = str_replace('"',"",$friendlyName);
		$friendlyName = str_replace('\\',"",$friendlyName);
		$friendlyName = str_replace("/","",$friendlyName);

		if ( ! $downloaded = $DockerTemplates->downloadTemplates($communityPaths['templates-community']."/templates/$friendlyName", $downloadURL) ){
			file_put_contents($communityPaths['updateErrors'],"Failed to download <font color='purple'>".$downloadRepo['name']."</font> ".$downloadRepo['url']."<br>",FILE_APPEND);
			@unlink($downloadURL);
		} else {
			$templates = array_merge($templates,$downloaded);
			unlink($downloadURL);
		}
	}

	@unlink($downloadURL);
	$i = $appCount;
	foreach ($Repos as $Repo) {
		if ( ! is_array($templates[$Repo['url']]) ) {
			continue;
		}
		foreach ($templates[$Repo['url']] as $file) {
			if (is_file($file)){
				$o = readXmlFile($file);
				if ( ! $o ) {
					file_put_contents($communityPaths['updateErrors'],"Failed to parse <font color='purple'>$file</font> (errors in XML file?)<br>",FILE_APPEND);
				}
				if ( (! $o['Repository']) && (! $o['Plugin']) ) {
					$statistics['invalidXML']++;
					$invalidXML[] = $o;
					continue;
				}
				$o['Forum'] = $Repo['forum'];
				$o['RepoName'] = $Repo['name'];
				$o['ID'] = $i;
				$o['Displayable'] = true;
				$o['Support'] = $o['Support'] ? $o['Support'] : $o['Forum'];
				$o['DonateText'] = $o['DonateText'] ? $o['DonateText'] : $Repo['donatetext'];  # Some people can't read the specs correctly
				$o['DonateLink'] = $o['DonateLink'] ? $o['DonateLink'] : $Repo['donatelink'];
				$o['DonateImg'] = $o['DonateImg'] ? $o['DonateImg'] : $Repo['donateimg'];
				$o['RepoURL'] = $Repo['url'];
			  $o['ModeratorComment'] = $Repo['RepoComment'];
				$o['WebPageURL'] = $Repo['web'];
				$o['Logo'] = $Repo['logo'];
				$o['Profile'] = $Repo['profile'];
				fixSecurity($o,$o);
				$o = fixTemplates($o);
        if ( ! $o ) {
          continue;
        }

				# Overwrite any template values with the moderated values
				if ( is_array($moderation[$o['Repository']]) ) {
					$o = array_merge($o, $moderation[$o['Repository']]);
				}
				$o['Compatible'] = versionCheck($o);

				$statistics['totalApplications']++;

				$o['Category'] = str_replace("Status:Beta","",$o['Category']);    # undo changes LT made to my xml schema for no good reason
				$o['Category'] = str_replace("Status:Stable","",$o['Category']);
				$myTemplates[$o['ID']] = $o;
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
					unset($o['Branch']);
					$o['Path'] = $communityPaths['templates-community']."/".$o['ID'].".xml";
					file_put_contents($o['Path'],makeXML($o));
					$myTemplates[$o['ID']] = $o;
				}
				$i = ++$i;
			}
		}
	}
	if ( $invalidXML ) {
		writeJsonFile($communityPaths['invalidXML_txt'],$invalidXML);
	} else {
		@unlink($communityPaths['invalidXML_txt']);
	}
	writeJsonFile($communityPaths['statistics'],$statistics);
	writeJsonFile($communityPaths['community-templates-info'],$myTemplates);

	file_put_contents($communityPaths['LegacyMode'],"active");
	return true;
}

#  DownloadApplicationFeed MUST BE CALLED prior to DownloadCommunityTemplates in order for private repositories to be merged correctly.

function DownloadApplicationFeed() {
	global $communityPaths, $infoFile, $plugin, $communitySettings, $statistics;

	exec("rm -rf '{$communityPaths['templates-community']}'");
	exec("mkdir -p '{$communityPaths['templates-community']}'");

	$moderation = readJsonFile($communityPaths['moderation']);
	$statistics['moderation'] = count($moderation);
	$Repositories = readJsonFile($communityPaths['Repositories']);

	$statistics['repository'] = count($Repositories);
	$downloadURL = randomFile();
  $ApplicationFeed = download_json($communityPaths['application-feed'],$downloadURL);
	
	if ( ! is_array($ApplicationFeed['applist']) ) {
		file_put_contents($communityPaths['appFeedDownloadError'],$downloadURL);
		return false;
	}

	unlink($downloadURL);
	$i = 0;
	$statistics['totalApplications'] = count($ApplicationFeed['applist']);

	$myTemplates = array();

	foreach ($ApplicationFeed['applist'] as $file) {
		if ( (! $file['Repository']) && (! $file['Plugin']) ){
			$statistics['invalidXML']++;
			$invalidXML[] = $file;
			continue;
		}
		# Move the appropriate stuff over into a CA data file
		$o = $file;
		$o['ID']            = $i;
		$o['Displayable']   = true;
		$o['Author']        = preg_replace("#/.*#", "", $o['Repository']);
		$o['DockerHubName'] = strtolower($file['Name']);
		$o['RepoName']      = $file['Repo'];
		$o['SortAuthor']    = $o['Author'];
		$o['SortName']      = $o['Name'];
		$o['Licence']       = $file['License']; # Support Both Spellings
		$o['Licence']       = $file['Licence'];
		$o['Path']          = $communityPaths['templates-community']."/".$i.".xml";
		if ( $o['Plugin'] ) {
			$o['Author']        = $o['PluginAuthor'];
			$o['Repository']    = $o['PluginURL'];
			$o['Category']      .= " Plugins: ";
			$o['SortAuthor']    = $o['Author'];
			$o['SortName']      = $o['Name'];
		}
		$RepoIndex = searchArray($Repositories,"name",$o['RepoName']);
		if ( $RepoIndex != false ) {
			$o['DonateText']       = $Repositories[$RepoIndex]['donatetext'];
			$o['DonateImg']        = $Repositories[$RepoIndex]['donateimg'];
			$o['DonateLink']       = $Repositories[$RepoIndex]['donatelink'];
			$o['WebPageURL']       = $Repositories[$RepoIndex]['web'];
			$o['Logo']             = $Repositories[$RepoIndex]['logo'];
			$o['Profile']          = $Repositories[$RepoIndex]['profile'];
			$o['RepoURL']          = $Repositories[$RepoIndex]['url'];
			$o['ModeratorComment'] = $Repositories[$RepoIndex]['RepoComment'];
		}
		$o['DonateText'] = $file['DonateText'] ? $file['DonateText'] : $o['DonateText'];
		$o['DonateLink'] = $file['DonateLink'] ? $file['DonateLink'] : $o['DonateLink'];

		if ( ($file['DonateImg']) || ($file['DonateImage']) ) {  #because Sparklyballs can't read the tag documentation
			$o['DonateImg'] = $file['DonateImage'] ? $file['DonateImage'] : $file['DonateImg'];
		}

		fixSecurity($o,$o); # Apply various fixes to the templates for CA use
		$o = fixTemplates($o);
    if ( ! $o ) {
      continue;
    }

# Overwrite any template values with the moderated values

		if ( is_array($moderation[$o['Repository']]) ) {
			$repositoryTmp = $o['Repository']; # in case moderation changes the repository entry
			$o = array_merge($o, $moderation[$repositoryTmp]);
			$file = array_merge($file, $moderation[$repositoryTmp]);
		}

		if ( $o['Plugin'] ) {
			$statistics['plugin']++;
		} else {
			$statistics['docker']++;
		}

		$o['Compatible'] = versionCheck($o);

		# Update the settings for the template

		$file['Compatible'] = $o['Compatible'];
		$file['Beta'] = $o['Beta'];
		$file['MinVer'] = $o['MinVer'];
		$file['MaxVer'] = $o['MaxVer'];
		$file['Category'] = $o['Category'];
		$o['Category'] = str_replace("Status:Beta","",$o['Category']);    # undo changes LT made to my xml schema for no good reason
		$o['Category'] = str_replace("Status:Stable","",$o['Category']);
		$myTemplates[$i] = $o;

		if ( is_array($file['Branch']) ) {
			if ( ! $file['Branch'][0] ) {
				$tmp = $file['Branch'];
				unset($file['Branch']);
				$file['Branch'][] = $tmp;
			}
			foreach($file['Branch'] as $branch) {
				$i = ++$i;
				$subBranch = $file;
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
		unset($file['Branch']);
		$myTemplates[$o['ID']] = $o;
		$i = ++$i;
		$templateXML = makeXML($file);
		file_put_contents($o['Path'],$templateXML);
	}
	writeJsonFile($communityPaths['statistics'],$statistics);
	if ( $invalidXML ) {
		writeJsonFile($communityPaths['invalidXML_txt'],$invalidXML);
	} else {
		@unlink($communityPaths['invalidXML_txt']);
	}
	writeJsonFile($communityPaths['community-templates-info'],$myTemplates);

	@unlink($communityPaths['LegacyMode']);
	return true;
}

function getConvertedTemplates() {
	global $communityPaths, $infoFile, $plugin, $communitySettings, $statistics;

# Start by removing any pre-existing private (converted templates)
	$templates = readJsonFile($communityPaths['community-templates-info']);
	$statistics = readJsonFile($communityPaths['statistics']);

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
		$o['Forum']        = "";
		$o['Compatible']   = versionCheck($o);
		$o = fixTemplates($o);
		fixSecurity($o,$o);
		$myTemplates[$i]  = $o;
		$i = ++$i;
	}

	writeJsonFile($communityPaths['community-templates-info'],$myTemplates);
	writeJsonFile($communityPaths['statistics'],$statistics);
	return true;
}

############################################################
#                                                          #
# Routines that actually displays the template containers. #
#                                                          #
############################################################
function display_apps($viewMode,$pageNumber=1,$selectedApps=false) {
	global $communityPaths, $separateOfficial, $officialRepo, $communitySettings;

	$file = readJsonFile($communityPaths['community-templates-displayed']);
	$officialApplications = $file['official'];
	$communityApplications = $file['community'];
	$totalApplications = count($officialApplications) + count($communityApplications);
	$navigate = array();

	if ( $separateOfficial ) {
		if ( count($officialApplications) ) {
			$navigate[] = "doesn't matter what's here -> first element gets deleted anyways";
			$display = "<center><b>";

			$logos = readJsonFile($communityPaths['logos']);
			$display .= $logos[$officialRepo] ? "<img src='".$logos[$officialRepo]."' style='width:48px'>&nbsp;&nbsp;" : "";
			$display .= "<font size='4' color='purple' id='OFFICIAL'>$officialRepo</font></b></center><br>";
			$display .= my_display_apps($viewMode,$officialApplications,1,true,$selectedApps);
		}
	}

	if ( count($communityApplications) ) {
		if ( $separateOfficial ) {
			$navigate[] = "<a href='#COMMUNITY'>Community Supported Applications</a>";
			$display .= "<center><b><font size='4' color='purple' id='COMMUNITY'>Community Supported Applications</font></b></center><br>";
		}
		$display .= my_display_apps($viewMode,$communityApplications,$pageNumber,false,$selectedApps);
	}
	unset($navigate[0]);

	if ( count($navigate) ) {
		$bookmark = "Jump To: ".implode("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$navigate);
	}
	$display .= ( $totalApplications == 0 ) ? "<center><font size='3'>No Matching Content Found</font></center>" : "";

	$totalApps = "$totalApplications";

	$display .= "<script>$('#Total').html('$totalApps');</script>";
	echo $bookmark;
	echo $display;
}

#############################
#                           #
# Selects an app of the day #
#                           #
#############################
function appOfDay($file) {
	global $communityPaths, $info;

	$oldAppDay = @filemtime($communityPaths['appOfTheDay']);
	$oldAppDay = $oldAppDay ? $oldAppDay : 1;
	$oldAppDay = intval($oldAppDay / 86400);
	$currentDay = intval(time() / 86400);
	if ( $oldAppDay == $currentDay ) {
		$app = readJsonFile($communityPaths['appOfTheDay']);
		if ( is_array($app) ) {  # test to see if existing apps of day have been moderated / blacklisted, etc.
			$flag = false;
			foreach ($app as $testApp) {
				if ( ! checkRandomApp($testApp,$file) ) {
					$flag = true;
					break;
				}
			}
			if ( $flag ) {
				$app = array();
			}
		}	
	}
	if ( ! $app ) {
		for ( $ii=0; $ii<10; $ii++ ) {
			$flag = false;
			if ( $app[$ii] ) {
				$flag = checkRandomApp($app[$ii],$file);
			}
			if ( ! $flag ) {
				for ( $jj = 0; $jj<20; $jj++) { # only give it 20 shots to find an app of the day
					$randomApp = mt_rand(0,count($file) -1);
					$flag = checkRandomApp($randomApp,$file);
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
	if (! $app) { $app = array(); } # for the extremely unlikely situation where it can't find any valid apps of the day
	$app = array_values(array_unique($app));
	writeJsonFile($communityPaths['appOfTheDay'],$app);
	return $app;
}

#####################################################
# Checks selected app for eligibility as app of day #
#####################################################
function checkRandomApp($randomApp,$file) {
	if ( ! $file[$randomApp]['Displayable'] )    return false;
	if ( ! $file[$randomApp]['Compatible'] )     return false;
	if ( $file[$randomApp]['Blacklist'] )        return false;
	if ( $file[$randomApp]['ModeratorComment'] ) return false;
	if ( $file[$randomApp]['Deprecated'] )       return false;
	if ( $file[$randomApp]['Beta'] == "true" )   return false;
	if ( $file[$randomApp]['PluginURL'] == "https://raw.githubusercontent.com/Squidly271/community.applications/master/plugins/community.applications.plg" ) return false;
	return true;
}

##########################################################################
#                                                                        #
# function that comes up with alternate search suggestions for dockerHub #
#                                                                        #
##########################################################################
function suggestSearch($filter,$displayFlag) {
	$dockerFilter = str_replace("_","-",$filter);
	$dockerFilter = str_replace("%20","",$dockerFilter);
	$dockerFilter = str_replace("/","-",$dockerFilter);
	$otherSearch = explode("-",$dockerFilter);

	if ( count($otherSearch) > 1 ) {
		$returnSearch .= "Suggested Searches: ";

		foreach ( $otherSearch as $suggestedSearch) {
			$returnSearch .= "<a style='cursor:pointer' onclick='mySearch(this.innerHTML);' title='Search For $suggestedSearch'><font color='blue'>$suggestedSearch</font></a>&nbsp;&nbsp;&nbsp;&nbsp;";
		}
	} else {
		$otherSearch = preg_split('/(?=[A-Z])/',$dockerFilter);

		if ( count($otherSearch) > 1 ) {
			$returnSearch .= "Suggested Searches: ";

			foreach ( $otherSearch as $suggestedSearch) {
				if ( strlen($suggestedSearch) > 1 ) {
					$returnSearch .= "<a style='cursor:pointer' onclick='mySearch(this.innerHTML);' title='Search For $suggestedSearch'><font color='blue'>$suggestedSearch</font></a>&nbsp;&nbsp;&nbsp;&nbsp;";
				}
			}
		} else {
			if ( $displayFlag ) {
				$returnSearch .= "Suggested Searches: Unknown";
			}
		}
	}
	return $returnSearch;
}

############################################
############################################
##                                        ##
## BEGIN MAIN ROUTINES CALLED BY THE HTML ##
##                                        ##
############################################
############################################

switch ($_POST['action']) {

######################################################################################
#                                                                                    #
# get_content - get the results from templates according to categories, filters, etc #
#                                                                                    #
######################################################################################
case 'get_content':
	$filter      = getPost("filter",false);
	$category    = "/".getPost("category",false)."/i";
	$newApp      = getPost("newApp",false);
	$sortOrder   = getSortOrder(getPostArray("sortOrder"));
	$windowWidth = getPost("windowWidth",false);
	getMaxColumns($windowWidth);
	@unlink($communityPaths['dontAllowInstalls']);

	if ( $category == "/PRIVATE/i" ) {
		$category = false;
		$displayPrivates = true;
	}
	if ( $category == "/DEPRECATED/i") {
		$category = false;
		$displayDeprecated = true;
		file_put_contents($communityPaths['dontAllowInstalls'],"Deprecated Applications are able to still be installed if you have previously had them installed. New installations of these applications are blocked unless you enable Display Deprecated Applications within CA's General Settings<br><br>");
	}
	if ( $category == "/BLACKLIST/i") {
		$category = false;
		$displayBlacklisted = true;
		file_put_contents($communityPaths['dontAllowInstalls'],"The following applications are blacklisted.  CA will never allow you to install or reinstall these applications<br><br>");
	}
	if ( $category == "/INCOMPATIBLE/i") {
		$displayIncompatible = true;
		file_put_contents($communityPaths['dontAllowInstalls'],"<b>While highly not recommended to do</b>, incompatible applications can be installed by enabling Display Incompatible Applications within CA's General Settings<br><br>");
	}
	if ( $category == "/NOSUPPORT/i") {
		$category = false;
		$displayNoSupport = true;
		file_put_contents($communityPaths['dontAllowInstalls'],"The following applications do not have any support thread for them (other applications that are also blacklisted / deprecated in addition to having no support thread will not appear here)<br><br>");
	}
	$newAppTime = strtotime($communitySettings['timeNew']);

	if ( file_exists($communityPaths['addConverted']) ) {
		@unlink($infoFile);
		@unlink($communityPaths['addConverted']);
	}

	if ( file_exists($communityPaths['appFeedOverride']) ) {
	 $communitySettings['appFeed'] = "false";
	 @unlink($communityPaths['appFeedOverride']);
	}

	if (!file_exists($infoFile)) {
		$updatedSyncFlag = true;
		if ( $communitySettings['appFeed'] == "true" ) {
			DownloadApplicationFeed();
			if (!file_exists($infoFile)) {
				@unlink($communityPaths['LegacyMode']);
				updateSyncTime(true);
				echo "<center><font size='3'><strong>Download of appfeed failed.</strong></font><br><br>Community Applications <em><b>requires</b></em> your server to have internet access.  The most common cause of this failure is a failure to resolve DNS addresses.  You can try and reset your modem and router to fix this issue, or set static DNS addresses (Settings - Network Settings) of <b>8.8.8.8 and 8.8.4.4</b> and try again.<br><br>Alternatively, there is also a chance that the server handling the application feed is temporarily down.  Switching CA to operate in <em>Legacy Mode</em> might temporarily allow you to still utilize CA.<br>";
				$tempFile = @file_get_contents($communityPaths['appFeedDownloadError']);
				$downloaded = @file_get_contents($tempFile);
				if (strlen($downloaded) > 100) {
					echo "<font size='2' color='red'><br><br>It *appears* that a partial download of the application feed happened (or is malformed), therefore it is probable that the application feed is temporarily down.  Switch to legacy mode (top right), or try again later)</font>";
  			}
				echo "<center>Last JSON error Recorded: ";
				$jsonDecode = json_decode($downloaded,true);
				echo "JSON Error: ".jsonError(json_last_error());
				echo "</center>";
				@unlink($communityPaths['appFeedDownloadError']);
				echo caGetMode();
				echo "<script>$('#updateButton').show();</script>";
				if ( $communitySettings['maintainer'] == "yes"  ) {
					exec("curl --compressed --max-time 60 --insecure --location -o ".$communityPaths['tempFiles']."/failedOutput ".$communityPaths['application-feed'],$out);
					echo "<br><br>Developer Mode Enabled:<br></center>";
					foreach ($out as $line) {
						echo "<tt>$line<br>";
					}
					echo "<br><br><font size='4'>Appfeed Contents:<br></font>";
					echo "<div style='height:300px; overflow:auto;'>";
					$out = @file_get_contents($communityPaths['tempFiles']."/failedOutput");
					$out = str_replace("\n","<br>",$out);
					$out = str_replace(" ","&nbsp;",$out);
					echo "<tt>$out</tt>";
					echo "</div>";
				} else {
					echo "<br><br>Note: Developer Mode Not Enabled<br><br>";
				}
				@unlink($infoFile);
			}
		}

		if ($communitySettings['appFeed'] == "false" ) {
			if (!DownloadCommunityTemplates()) {
				echo "<center><font size='3'><strong>Download of appfeed failed.</strong></font><br><br>Community Applications <em><b>requires</b></em> your server to have internet access.  The most common cause of this failure is a failure to resolve DNS addresses.  You can try and reset your modem and router to fix this issue, or set static DNS addresses (Settings - Network Settings) of <b>8.8.8.8 and 8.8.4.4</b> and try again.<br><br>Alternatively, there is also a chance that the server handling templates (GitHub.com) is temporarily down.";

				break;
			} else {
				$lastUpdated['last_updated_timestamp'] = time();
				writeJsonFile($communityPaths['lastUpdated-old'],$lastUpdated);
				updateSyncTime(true);

				if (is_file($communityPaths['updateErrors'])) {
					echo "<table><td><td colspan='5'><br><center>The following errors occurred:<br><br>";
					echo "<strong>".file_get_contents($communityPaths['updateErrors'])."</strong></center></td></tr></table>";
					echo "<script>$('#templateSortButtons,#total1').hide();$('#sortButtons').hide();</script>";
					echo caGetMode();
					break;
				}
			}
		}
	}
	getConvertedTemplates();
	updateSyncTime($updatedSyncFlag);
	moderateTemplates();

	$file = readJsonFile($communityPaths['community-templates-info']);
	if ( empty($file)) break;

	if ( $category === "/NONE/i" ) {
		echo "<center><font size=4>$selectCategoryMessage</font></center>";
		if ( $communitySettings['appOfTheDay'] == "yes" ) {
			$displayApplications = array();
			if ( count($file) > 200) {
				$appsOfDay = appOfDay($file);
				$displayApplications['community'] = array();
				for ($i=0;$i<$communitySettings['maxDetailColumns'];$i++) {
					if ( ! $appsOfDay[$i]) {
						continue;
					}
					$displayApplications['community'][] = $file[$appsOfDay[$i]];
				}
				if ( $displayApplications['community'] ) {
					writeJsonFile($communityPaths['community-templates-displayed'],$displayApplications);
					echo "<script>$('#templateSortButtons,#sortButtons').hide();enableIcon('#sortIcon',false);</script>";
					echo "<br><center><font size='4' color='purple'><b>Random Apps Of The Day</b></font><br><br>";
					echo my_display_apps("detail",$displayApplications['community'],"1",$runningDockers,$imagesDocker);
					break;
				} else {
					echo "<script>$('#templateSortButtons,#sortButtons').hide();enableIcon('#sortIcon',false);</script>";
					echo "<br><center><font size='4' color='purple'><b>An error occurred.  Could not find any Random Apps of the day</b></font><br><br>";				
					break;
				}
			}
		} else {
			break;
		}
	}

	$display             = array();
	$official            = array();

	if ( $newApp == "true" ) {
		file_put_contents($communityPaths['newFlag'],"new category is being displayed");
	} else {
		@unlink($communityPaths['newFlag']);
	}
	
	foreach ($file as $template) {
		if ( ($template['Blacklist'] && ! $displayBlacklisted) || (! $template['Blacklist'] && $displayBlacklisted) ) {
			continue;
		}
		if ( ($communitySettings['hideDeprecated'] == "true") && ($template['Deprecated'] && ! $displayDeprecated) ) {
			continue;                          # ie: only show deprecated apps within previous apps section
		}
		if ( $displayDeprecated && ! $template['Deprecated'] ) {
			continue;
		}
		if ( ! $template['Displayable'] ) {
			continue;
		}
		if ( $communitySettings['hideIncompatible'] == "true" && ! $template['Compatible'] && ! $displayIncompatible) {
			continue;
		}
		if ( ! $template['Compatible'] && $displayIncompatible ) {
			$display[] = $template;
			continue;
		}
		if ( $template['Support'] && $displayNoSupport ) {
			continue;
		}
		
		$name = $template['Name'];

# Skip over installed containers

		if ( $newApp != "true" && $filter == "" && $communitySettings['separateInstalled'] == "true" && ! $displayPrivates) {
			if ( $template['Plugin'] ) {
				$pluginName = basename($template['PluginURL']);

				if ( file_exists("/var/log/plugins/$pluginName") ) {
					continue;
				}
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
				if ( $selected ) {
					continue;
				}
			}
		}
		if ( $template['Plugin'] && file_exists("/var/log/plugins/".basename($template['PluginURL'])) ) {
			$template['UpdateAvailable'] = checkPluginUpdate($template['PluginURL']);
			$template['MyPath'] = $template['PluginURL'];
		}

		if ( ($newApp == "true") && ($template['Date'] < $newAppTime) )  { continue; }
		if ( $category && ! preg_match($category,$template['Category'])) { continue; }
    if ( $displayPrivates && ! $template['Private'] ) { continue; }
		
		if ($filter) {
			if ( filterMatch($filter,array($template['Name'],$template['Author'],$template['Description'],$template['RepoName'])) ) {
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

	$displayApplications['official']  = $official;
	$displayApplications['community'] = $display;

	writeJsonFile($communityPaths['community-templates-displayed'],$displayApplications);
	display_apps($sortOrder['viewMode']);
	break;

########################################################
#                                                      #
# force_update -> forces an update of the applications #
#                                                      #
########################################################
case 'force_update':
	download_url($communityPaths['moderationURL'],$communityPaths['moderation']);
	$Repositories = download_json($communityPaths['community-templates-url'],$communityPaths['Repositories']);

	$repositoriesLogo = $Repositories;
	if ( ! is_array($repositoriesLogo) ) {
		$repositoriesLogo = array();
	}

	foreach ($repositoriesLogo as $repositories) {
		if ( $repositories['logo'] ) {
			$repoLogo[$repositories['name']] = $repositories['logo'];
		}
	}
	writeJsonFile($communityPaths['logos'],$repoLogo);

	if ( ! file_exists($infoFile) ) {
		if ( ! file_exists($communityPaths['lastUpdated-old']) ) {
			$latestUpdate['last_updated_timestamp'] = time();
			writeJsonFile($communityPaths['lastUpdated-old'],$latestUpdate);
		}
		echo "ok";
		break;
	}

	if ( file_exists($communityPaths['lastUpdated-old']) ) {
		$lastUpdatedOld = readJsonFile($communityPaths['lastUpdated-old']);
	} else {
		$lastUpdatedOld['last_updated_timestamp'] = 0;
	}
	@unlink($communityPaths['lastUpdated']);
	$latestUpdate = download_json($communityPaths['application-feed-last-updated'],$communityPaths['lastUpdated']);

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
			unlink($infoFile);
		}
	} else {
		moderateTemplates();
	}
	echo "ok";
	break;

####################################################################################################
#                                                                                                  #
# force_update_button - forces the system temporarily to override the appFeed and forces an update #
#                                                                                                  #
####################################################################################################
case 'force_update_button':
	if ( ! is_file($communityPaths['LegacyMode']) ) {
		file_put_contents($communityPaths['appFeedOverride'],"dunno");
	}
	@unlink($infoFile);
	echo "ok";
	break;

####################################################################################
#                                                                                  #
# display_content - displays the templates according to view mode, sort order, etc #
#                                                                                  #
####################################################################################
case 'display_content':
	lockDisplay();
	$sortOrder = getSortOrder(getPostArray('sortOrder'));
	$windowWidth = getPost("windowWidth",false);
	$pageNumber = getPost("pageNumber","1");
	$selectedApps = json_decode(getPost("selected",false),true);
	getMaxColumns($windowWidth);

	if ( file_exists($communityPaths['community-templates-displayed']) ) {
		display_apps($sortOrder['viewMode'],$pageNumber,$selectedApps);
	} else {
		echo "<center><font size='4'>$selectCategoryMessage</font></center>";
	}
	lockDisplay(false);
	break;

########################################################################
#                                                                      #
# change_docker_view - called when the view mode for dockerHub changes #
#                                                                      #
########################################################################
case 'change_docker_view':
	$sortOrder = getSortOrder(getPostArray('sortOrder'));
	if ( ! file_exists($communityPaths['dockerSearchResults']) ) {
		break;
	}

	$file = readJsonFile($communityPaths['dockerSearchResults']);
	$pageNumber = $file['page_number'];
	displaySearchResults($pageNumber,$sortOrder['viewMode']);
	break;

#######################################################################
#                                                                     #
# convert_docker - called when system adds a container from dockerHub #
#                                                                     #
#######################################################################
case 'convert_docker':
	$dockerID = getPost("ID","");

	$file = readJsonFile($communityPaths['dockerSearchResults']);
	$docker = $file['results'][$dockerID];
	$docker['Description'] = str_replace("&", "&amp;", $docker['Description']);

	if ( ! $docker['Official'] ) {
		$dockerURL = $docker['DockerHub']."~/dockerfile/";
		download_url($dockerURL,$communityPaths['dockerfilePage']);

		$mystring = file_get_contents($communityPaths['dockerfilePage']);

		@unlink($communityPaths['dockerfilePage']);

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
    $teststring = str_replace("\\"."\n"," ",$teststring);
		$dockerFile = explode("\n",$teststring);

		$volumes = array();
		$ports = array();

		foreach ( $dockerFile as $dockerLine ) {
			$dockerCompare = trim(strtoupper($dockerLine));

			$dockerCmp = strpos($dockerCompare, "VOLUME");
			if ( $dockerCmp === 0 ) {
				$dockerLine = str_replace("'", " ", $dockerLine);
				$dockerLine = str_replace("[", " ", $dockerLine);
				$dockerLine = str_replace("]", " ", $dockerLine);
				$dockerLine = str_replace(",", " ", $dockerLine);
				$dockerLine = str_replace('"', " ", $dockerLine);

				$volumes[] = $dockerLine;
			}

			$dockerCmp = strpos($dockerCompare, "EXPOSE");
			if ( $dockerCmp === 0 ) {
				$dockerLine = str_replace("'", " ", $dockerLine);
				$dockerLine = str_replace("[", " ", $dockerLine);
				$dockerLine = str_replace("]", " ", $dockerLine);
				$dockerLine = str_replace(",", " ", $dockerLine);
				$dockerLine = str_replace('"', " ", $dockerLine);
				$ports[] = $dockerLine;
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
				$allPorts[] = $myPort;
			}
		}

		$dockerfile['Name'] = $docker['Name'];
		$dockerfile['Support'] = $docker['DockerHub'];
		$dockerfile['Description'] = $docker['Description']."\n\n[b]Converted By Community Applications[/b]";
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
		$dockerfile['Icon'] = "https://github.com/Squidly271/community.applications/raw/master/source/community.applications/usr/local/emhttp/plugins/community.applications/images/question.png";

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
	} else {
# Container is Official.  Add it as such
		$dockerURL = $docker['DockerHub'];
		$dockerfile['Name'] = $docker['Name'];
		$dockerfile['Support'] = $docker['DockerHub'];
		$dockerfile['Overview'] = $docker['Description']."\n[b]Converted By Community Applications[/b]";
		$dockerfile['Description'] = $dockerfile['Overview'];
		$dockerfile['Registry'] = $dockerURL;
		$dockerfile['Repository'] = $docker['Repository'];
		$dockerfile['BindTime'] = "true";
		$dockerfile['Privileged'] = "false";
		$dockerfile['Networking']['Mode'] = "bridge";
		$dockerfile['Icon'] = "https://github.com/Squidly271/community.applications/raw/master/source/community.applications/usr/local/emhttp/plugins/community.applications/images/question.png";
	}
	$dockerXML = makeXML($dockerfile);

	$xmlFile = $communityPaths['convertedTemplates']."DockerHub/";
	if ( ! is_dir($xmlFile) ) {
		exec("mkdir -p ".$xmlFile);
	}
	$xmlFile .= str_replace("/","-",$docker['Repository']).".xml";
	file_put_contents($xmlFile,$dockerXML);
	file_put_contents($communityPaths['addConverted'],"Dante");
	echo $xmlFile;
	break;

#########################################################
#                                                       #
# search_dockerhub - returns the results from dockerHub #
#                                                       #
#########################################################
case 'search_dockerhub':
	$filter     = getPost("filter","");
	$pageNumber = getPost("page","1");
	$sortOrder  = getSortOrder(getPostArray('sortOrder'));
	@unlink($communityPaths['dontAllowInstalls']);

	$communityTemplates = readJsonFile($communityPaths['community-templates-info']);
	$filter = str_replace(" ","%20",$filter);
	$jsonPage = shell_exec("curl -s -X GET 'https://registry.hub.docker.com/v1/search?q=$filter\&page=$pageNumber'");
	$pageresults = json_decode($jsonPage,true);
	$num_pages = $pageresults['num_pages'];

	echo "<script>$('#Total').html(".$pageresults['num_results'].");</script>";

	if ($pageresults['num_results'] == 0) {
		echo "<center>No matching content found on dockerhub</center>";
		echo suggestSearch($filter,true);
		echo "<script>$('#dockerSearch').hide();$('#Total').html('0');</script>";
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
	echo suggestSearch($filter,false);
	displaySearchResults($pageNumber, $sortOrder['viewMode']);
	break;

#####################################################################
#                                                                   #
# dismiss_warning - dismisses the warning from appearing at startup #
#                                                                   #
#####################################################################
case 'dismiss_warning':
	file_put_contents($communityPaths['warningAccepted'],"warning dismissed");
	break;

###############################################################
#                                                             #
# Displays the list of installed or previously installed apps #
#                                                             #
###############################################################
case 'previous_apps':
	lockDisplay();
	@unlink($communityPaths['dontAllowInstalls']);

	$installed = getPost("installed","");
	$dockerUpdateStatus = readJsonFile($communityPaths['dockerUpdateStatus']);
	$moderation = readJsonFile($communityPaths['moderation']);
	$DockerClient = new DockerClient();
	$info = $DockerClient->getDockerContainers();
	$file = readJsonFile($communityPaths['community-templates-info']);

# $info contains all installed containers
# now correlate that to a template;
# this section handles containers that have not been renamed from the appfeed
	$all_files = glob("/boot/config/plugins/dockerMan/templates-user/*.xml");
	if ( ! $all_files ) {
		$all_files = array();
	}
	if ( $installed == "true" ) {
		foreach ($info as $installedDocker) {
			$installedImage = $installedDocker['Image'];
			$installedName = $installedDocker['Name'];

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
			$o = readXmlFile("$xmlfile",$moderation);
			$o['MyPath'] = "$xmlfile";
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
					$displayed[] = $o;
				}
			}
		}
	} else {
# now get the old not installed docker apps
		foreach ($all_files as $xmlfile) {
			$o = readXmlFile("$xmlfile");
			$o['MyPath'] = "$xmlfile";
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
				$displayed[] = $o;
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
				$template['UpdateAvailable'] = checkPluginUpdate($filename);

				$displayed[] = $template;
			}
		}
	} else {
		$all_plugs = dirContents("/boot/config/plugins-removed/");

		foreach ($all_plugs as $oldplug) {
			foreach ($file as $template) {
				if ( $oldplug == pathinfo($template['Repository'],PATHINFO_BASENAME) ) {
					if ( ! file_exists("/boot/config/plugins/$oldplug") ) {
						if ( $template['Blacklist'] ) {
							continue;
						}
            if ( strtolower(trim($template['PluginURL'])) != strtolower(trim(plugin("pluginURL","/boot/config/plugins-removed/$oldplug"))) ) {
							continue;
						}
						$template['Removable'] = true;
						$template['MyPath'] = "/boot/config/plugins-removed/$oldplug";

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
#                                                                                  #
# Removes an app from the previously installed list (ie: deletes the user template #
#                                                                                  #
####################################################################################
case 'remove_application':
	lockDisplay();
	$application = getPost("application","");
	if ( pathinfo($application,PATHINFO_EXTENSION) == "xml" || pathinfo($application,PATHINFO_EXTENSION) == "plg" ) {
		@unlink($application);
	}
	echo "ok";
	break;

#######################
#                     #
# Uninstalls a plugin #
#                     #
#######################
case 'uninstall_application':
	lockDisplay();
	$application = getPost("application","");

	$filename = pathinfo($application,PATHINFO_BASENAME);
	shell_exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin remove '$filename'");
	echo "ok";
	break;

###################################################################################
#                                                                                 #
# Checks for an update still available (to update display) after update installed #
#                                                                                 #
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
#                     #
# Uninstalls a docker #
#                     #
#######################
case 'uninstall_docker':
	lockDisplay();
	$application = getPost("application","");

# get the name of the container / image
	$doc = new DOMDocument();
	$doc->load($application);
	$containerName  = stripslashes($doc->getElementsByTagName( "Name" )->item(0)->nodeValue);

	$DockerClient = new DockerClient();
	$dockerInfo = $DockerClient->getDockerContainers();
	$container = searchArray($dockerInfo,"Name",$containerName);

# stop the container

	shell_exec("docker stop $containerName");
	shell_exec("docker rm  $containerName");
	shell_exec("docker rmi ".$dockerInfo[$container]['ImageId']);

	echo "Uninstalled";
	break;

##################################################
#                                                #
# Pins / Unpins an application for later viewing #
#                                                #
##################################################
case "pinApp":
	$repository = getPost("repository","oops");
	$pinnedApps = readJsonFile($communityPaths['pinned']);
	$pinnedApps[$repository] = $pinnedApps[$repository] ? false : $repository;
	writeJsonFile($communityPaths['pinned'],$pinnedApps);
	break;

####################################
#                                  #
# Displays the pinned applications #
#                                  #
####################################
case "pinnedApps":
	@unlink($communityPaths['dontAllowInstalls']);
	
	$pinnedApps = getPinnedApps();
	$file = readJsonFile($communityPaths['community-templates-info']);

	foreach ($pinnedApps as $pinned) {
		$index = searchArray($file,"Repository",$pinned);
		if ( $index !== false ) {
			$displayed[] = $file[$index];
		}
	}
	$displayedApplications['community'] = $displayed;
	$displayedApplications['pinnedFlag']  = true;
	writeJsonFile($communityPaths['community-templates-displayed'],$displayedApplications);
	echo "fini!";
	break;

################################################
#                                              #
# Displays the possible branch tags for an app #
#                                              #
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
#                                         #
# Displays The Statistics For The Appfeed #
#                                         #
###########################################
case 'statistics':
	$statistics = readJsonFile($communityPaths['statistics']);
	$statistics['totalModeration'] = count(readJsonFile($communityPaths['moderation']));

	$templates = readJsonFile($communityPaths['community-templates-info']);
	pluginDupe($templates);
	unset($statistics['Private']);
	if ( is_array($templates) ) {
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
			if ( ! $template['Support'] && ! $template['Blacklist'] && ! $template['Deprecated'] ) {
				$statistics['NoSupport']++;
			}
			if ( $template['Blacklist'] ) {
				$statistics['blacklist']++;
			}
			if ( $template['Private'] && ! $template['Blacklist']) {
				$statistics['private']++;
			}
		}
	}

	if ( $statistics['fixedTemplates'] ) {
		writeJsonFile($communityPaths['fixedTemplates_txt'],$statistics['fixedTemplates']);
	} else {
		@unlink($communityPaths['fixedTemplates_txt']);
	}
	if ( is_file($communityPaths['lastUpdated-old']) ) {
		$appFeedTime = readJsonFile($communityPaths['lastUpdated-old']);
	} else {
		$appFeedTime['last_updated_timestamp'] = filemtime($communityPaths['community-templates-info']);
	}
	$updateTime = date("F d Y H:i",$appFeedTime['last_updated_timestamp']);
	$updateTime = ( is_file($communityPaths['LegacyMode']) ) ? "N/A - Legacy Mode Active" : $updateTime;
	$defaultArray = Array('caFixed' => 0,'totalApplications' => 0, 'repository' => 0, 'docker' => 0, 'plugin' => 0, 'invalidXML' => 0, 'blacklist' => 0, 'totalIncompatible' =>0, 'totalDeprecated' => 0, 'totalModeration' => 0, 'private' => 0, 'NoSupport' => 0);
	$statistics = array_merge($defaultArray,$statistics);

	foreach ($statistics as &$stat) {
		if ( ! $stat ) {
			$stat = "0";
		}
	}

	$color = "<font color='coral'>";
	echo "<div style='overflow:scroll; max-height:550px; height:600px; overflow-x:hidden; overflow-y:hidden;'><center><img height='24px' src='/plugins/community.applications/images/CA.png'><br><font size='3' color='white'>Community Applications</font><br>";
	echo "<center><font size='2'>Application Feed Statistics</font></center><br><br>";
	echo "<table>";
	echo "<tr><td><b>{$color}Last Change To Application Feed</b></td><td>$color$updateTime</td></tr>";
	echo "<tr><td><b>{$color}Total Number Of Templates</b></td><td>$color{$statistics['totalApplications']}</td></tr>";
	echo "<tr><td><b>{$color}<a onclick='showModeration(&quot;Repository&quot;,&quot;Repository List&quot;);' style='cursor:pointer;'>Total Number Of Repositories</a></b></td><td>$color{$statistics['repository']}</td></tr>";
	echo "<tr><td><b>{$color}Total Number Of Docker Applications</b></td><td>$color{$statistics['docker']}</td></tr>";
	echo "<tr><td><b>{$color}Total Number Of Plugins</b></td><td>$color{$statistics['plugin']}</td></tr>";
	echo "<tr><td><b>{$color}<a id='PRIVATE' onclick='showSpecialCategory(this);' style='cursor:pointer;'><b>Total Number Of Private Docker Applications</b></a></td><td>$color{$statistics['private']}</td></tr>";
	echo "<tr><td><b>{$color}<a onclick='showModeration(&quot;Invalid&quot;,&quot;All Invalid Templates Found&quot;);' style='cursor:pointer'>Total Number Of Invalid Templates Found</a></b></td><td>$color{$statistics['invalidXML']}</td></tr>";
	echo "<tr><td><b>{$color}<a onclick='showModeration(&quot;Fixed&quot;,&quot;Template Errors&quot;);' style='cursor:pointer'>Total Number Of Template Errors</a></b></td><td>$color{$statistics['caFixed']}+</td></tr>";
	echo "<tr><td><b>{$color}<a id='BLACKLIST' onclick='showSpecialCategory(this);' style='cursor:pointer'>Total Number Of Blacklisted Apps Found In Appfeed</a></b></td><td>$color{$statistics['blacklist']}</td></tr>";
	echo "<tr><td><b>{$color}<a id='INCOMPATIBLE' onclick='showSpecialCategory(this);' style='cursor:pointer'>Total Number Of Incompatible Applications</a></b></td><td>$color{$statistics['totalIncompatible']}</td></tr>";
	echo "<tr><td><b>{$color}<a id='DEPRECATED' onclick='showSpecialCategory(this);' style='cursor:pointer'>Total Number Of Deprecated Applications</a></b></td><td>$color{$statistics['totalDeprecated']}</td></tr>";
	echo "<tr><td><b>{$color}<a onclick='showModeration(&quot;Moderation&quot;,&quot;All Moderation Entries&quot;);' style='cursor:pointer'>Total Number Of Moderation Entries</a></b></td><td>$color{$statistics['totalModeration']}+</td></tr>";
	echo "<tr><td><b>{$color}<a id='NOSUPPORT' onclick='showSpecialCategory(this);' style='cursor:pointer'>Applications without any support thread:</a></b></td><td>$color{$statistics['NoSupport']}</td></tr>";
	$totalCA = exec("du -h -s /usr/local/emhttp/plugins/community.applications/");
	$totalTmp = exec("du -h -s /tmp/community.applications/");
	$memCA = explode("\t",$totalCA);
	$memTmp = explode("\t",$totalTmp);
	echo "<tr><td><b>{$color}<b>Memory Usage (CA / DataFiles)</b></td><td>{$memCA[0]} / {$memTmp[0]}</td></tr>";
	
	echo "</table>";
	echo "<center><a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7M7CBCVU732XG' target='_blank'><img height='25px' src='https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif'></a></center>";
	echo "<center>Ensuring only safe applications are present is a full time job</center><br>";
	break;

#####################################################################################
#                                                                                   #
# Updates The maxPerPage setting (maxPerPage is already grabbed globally from POST) #
#                                                                                   #
#####################################################################################
case 'changeSettings':
	file_put_contents($communityPaths['pluginSettings'],create_ini_file($communitySettings,false));
	echo "settings updated";
	break;

##########################################
#                                        #
# Updates the viewMode for next instance #
#                                        #
##########################################
case 'changeViewModeSettings':
	$communitySettings['viewMode'] = getPost("view",$communitySettings['viewMode']);
	file_put_contents($communityPaths['pluginSettings'],create_ini_file($communitySettings,false));
	echo "ok";
	break;

#############################
#                           #
# Checks for stale database #
#                           #
#############################
case 'checkStale':
  if (isdisplayLocked() ) {
		echo "false";
	}
  $webTime = getPost("webTime",false);
	if ( ! $webTime ) {
		echo "false";
		return;
	}
	$lastUpdate = @file_get_contents($communityPaths['lastUpdated-sync']);
	if ( ! $lastUpdate ) {
		echo "false";
		return;
	}
	if ( $lastUpdate != $webTime ) {
		echo "true";
	} else {
		echo "false";
	}
	break;

#######################################
#                                     #
# Removes a private app from the list #
#                                     #
#######################################
case 'removePrivateApp':
	lockDisplay();
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
}
?>