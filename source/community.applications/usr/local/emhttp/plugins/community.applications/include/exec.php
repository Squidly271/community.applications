<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2021, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################

$unRaidSettings = parse_ini_file("/etc/unraid-version");

### Translations section has to be first so that nothing else winds up caching the file(s)

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

$translationsAllowed = is_file("$docroot/plugins/dynamix/include/Translations.php");
if ( $translationsAllowed ) {
	$_SERVER['REQUEST_URI'] = "docker/apps";
	require_once("$docroot/plugins/dynamix/include/Translations.php");
}

require_once "$docroot/plugins/dynamix.docker.manager/include/DockerClient.php"; # must be first include due to paths defined
require_once "$docroot/plugins/community.applications/include/paths.php";
require_once "$docroot/plugins/community.applications/include/helpers.php";
require_once "$docroot/plugins/community.applications/skins/Narrow/skin.php";
require_once "$docroot/plugins/dynamix/include/Wrappers.php";
require_once "$docroot/plugins/dynamix.plugin.manager/include/PluginHelpers.php";
require_once "$docroot/webGui/include/Markdown.php";

################################################################################
# Set up any default settings (when not explicitely set by the settings module #
################################################################################

$caSettings = parse_plugin_cfg("community.applications");

$debugging = $caSettings['debugging'] == "yes";

$caSettings['maxPerPage']    = isMobile() ? 12 : 24;
$caSettings['unRaidVersion'] = $unRaidSettings['version'];
$caSettings['timeNew']       = "-10 years";
$caSettings['favourite'] = str_replace("*","'",$caSettings['favourite']);

if ( ! is_file($caPaths['warningAccepted']) )
	$caSettings['NoInstalls'] = true;

$DockerClient = new DockerClient();
$DockerTemplates = new DockerTemplates();

if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
	$caSettings['dockerRunning'] = true;
	$dockerRunning = $DockerClient->getDockerContainers();
} else {
	$caSettings['dockerSearch'] = "no";
	unset($caSettings['dockerRunning']);
	$dockerRunning = array();
}

@mkdir($caPaths['tempFiles'],0777,true);

if ( !is_dir($caPaths['templates-community']) ) {
	@mkdir($caPaths['templates-community'],0777,true);
	@unlink($caPaths['community-templates-info']);
}

if ($debugging) {
	file_put_contents($caPaths['logging'],"POST CALLED\n".print_r($_POST,true),FILE_APPEND);
}

############################################
##                                        ##
## BEGIN MAIN ROUTINES CALLED BY THE HTML ##
##                                        ##
############################################

switch ($_POST['action']) {

######################################################################################
# get_content - get the results from templates according to categories, filters, etc #
######################################################################################
case 'get_content':
	$filter      = getPost("filter",false);
	$category    = getPost("category",false);
	$newApp      = filter_var(getPost("newApp",false),FILTER_VALIDATE_BOOLEAN);
	$sortOrder   = getSortOrder(getPostArray("sortOrder"));
	$caSettings['startup'] = getPost("startupDisplay",false);
	@unlink($caPaths['repositoriesDisplayed']);
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

	$newAppTime = strtotime($caSettings['timeNew']);

	if ( file_exists($caPaths['addConverted']) ) {
		@unlink($caPaths['addConverted']);
		getConvertedTemplates();
	}
	if ( strpos($category,":") && $filter ) {
		$disp = readJsonFile($caPaths['community-templates-allSearchResults']);
		$file = $disp['community'];
	} else {
		$file = readJsonFile($caPaths['community-templates-info']);
	}
	if ( empty($file)) break;

	if ( $category === "/NONE/i" ) {
		file_put_contents($caPaths['startupDisplayed'],"startup");
		$displayApplications = array();
		if ( count($file) > 200) {
			$appsOfDay = appOfDay($file);

			$displayApplications['community'] = array();
			for ($i=0;$i<$caSettings['maxPerPage'];$i++) {
				if ( ! $appsOfDay[$i]) continue;
				$file[$appsOfDay[$i]]['NewApp'] = ($caSettings['startup'] != "random");
				$displayApplications['community'][] = $file[$appsOfDay[$i]];
			}
			if ( $displayApplications['community'] ) {
				writeJsonFile($caPaths['community-templates-displayed'],$displayApplications);
				@unlink($caPaths['community-templates-allSearchResults']);
				@unlink($caPaths['community-templates-catSearchResults']);
				$sortOrder['sortBy'] = "noSort";
				$o['display'] = my_display_apps($displayApplications['community'],"1");
				$o['script'] = "$('#templateSortButtons,#sortButtons').hide();enableIcon('#sortIcon',false);";
				postReturn($o);
				break;
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
				}


				$o['display'] =  "<br><div class='ca_center'><font size='4' color='purple'><span class='ca_bold'>".sprintf(tr("An error occurred.  Could not find any %s Apps"),$startupType)."</span></font><br><br>";
				$o['script'] = "$('#templateSortButtons,#sortButtons').hide();enableIcon('#sortIcon',false);";
				postReturn($o);
				break;
			}
		}
	} else {
		@unlink($caPaths['startupDisplayed']);
	}
	$display  = array();
	$official = array();

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
			if ( ! $template['BranchID'] )
				$display[] = $template;
			continue;
		}
		if ( ($caSettings['hideDeprecated'] == "true") && ($template['Deprecated'] && ! $displayDeprecated) ) continue;
		if ( $displayDeprecated && ! $template['Deprecated'] ) continue;
		if ( ! $template['Displayable'] ) continue;
		if ( $caSettings['hideIncompatible'] == "true" && ! $template['Compatible'] && ! $displayIncompatible) continue;
		if ( $template['Blacklist'] ) continue;

		$name = $template['Name'];

		if ( $template['Plugin'] && file_exists("/var/log/plugins/".basename($template['PluginURL'])) )
			$template['InstallPath'] = $template['PluginURL'];

		if ( ($newApp) && ($template['Date'] < $newAppTime) ) continue;
		$template['NewApp'] = $newApp;

		if ( $category && ! preg_match($category,$template['Category'])) continue;
		if ( $displayPrivates && ! $template['Private'] ) continue;

		if ($filter) {
			# Can't be done at appfeed download time because the translation may or may not exist if the user switches languages
			foreach (explode(" ",$template['Category']) as $trCat) {
				$template['translatedCategories'] .= tr($trCat)." ";
			}
			if ( endsWith($filter," Repository") && $template['RepoName'] !== $filter) {
				continue;
			}
			if ( filterMatch($filter,array($template['SortName'])) && $caSettings['favourite'] == $template['RepoName']) {
				$template['Name_highlighted'] = highlight($filter,$template['Name']);
				$searchResults['favNameHit'][] = $template;
				continue;
			}
			if ( filterMatch($filter,array($template['SortName'],$template['RepoName'],$template['Language'],$template['LanguageLocal'])) ) {
				$template['Name_highlighted'] = highlight($filter,$template['Name']);
				$template['Description'] = highlight($filter, $template['Description']);
				$template['Author'] = highlight($filter, $template['Author']);
				$template['CardDescription'] = highlight($filter,$template['CardDescription']);
				$template['RepoName_highlighted'] = highlight($filter,$template['RepoName']);
				if ($template['Language']) {
					$template['Language'] = highlight($filter,$template['Language']);
					$template['LanguageLocal'] = highlight($filter,$template['LanguageLocal']);
				}
				$searchResults['nameHit'][] = $template;
			} else if ( filterMatch($filter,array($template['Author'],$template['Description'],$template['translatedCategories'])) ) {
				$template['Description'] = highlight($filter, $template['Description']);
				$template['Author'] = highlight($filter, $template['Author']);
				$template['CardDescription'] = highlight($filter,$template['CardDescription']);
				if ( $template['RepoName'] == $caSettings['favourite'] ) {
					$searchResults['nameHit'][] = $template;
				} else {
					$searchResults['anyHit'][] = $template;
				}
			} else continue;
		}

		$display[] = $template;
	}
	if ( $filter ) {
		if ( is_array($searchResults['nameHit']) ) {
			usort($searchResults['nameHit'],"mySort");
			if ( ! strpos($filter," Repository") ) {
				if ( $caSettings['favourite'] && $caSettings['favourite'] !== "none" ) {
					usort($searchResults['nameHit'],"favouriteSort");
				}
			}
		}
		else
			$searchResults['nameHit'] = array();

		if ( is_array($searchResults['anyHit']) ) {
			usort($searchResults['anyHit'],"mySort");
		}
		else
			$searchResults['anyHit'] = array();
		if ( is_array($searchResults['favNameHit']) ) {
			usort($searchResults['favNameHit'],"mySort");
		} else
			$searchResults['favNameHit'] = array();

		$displayApplications['community'] = array_merge($searchResults['favNameHit'],$searchResults['nameHit'],$searchResults['anyHit']);
		$sortOrder['sortBy'] = "noSort";
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
	$o['display'] = display_apps();
	if ( count($displayApplications['community']) < 2 )
		$o['script'] = "disableSort();";

	postReturn($o);
	break;

########################################################
# force_update -> forces an update of the applications #
########################################################
case 'force_update':
	$lastUpdatedOld = readJsonFile($caPaths['lastUpdated-old']);

	@unlink($caPaths['lastUpdated']);
	$latestUpdate = download_json($caPaths['application-feed-last-updated'],$caPaths['lastUpdated']);
	if ( ! $latestUpdate['last_updated_timestamp'] )
		$latestUpdate = download_json($caPaths['application-feed-last-updatedBackup'],$caPaths['lastUpdated']);

	if ( ! $latestUpdate['last_updated_timestamp'] ) {
		$latestUpdate['last_updated_timestamp'] = INF;
		$badDownload = true;
		@unlink($caPaths['lastUpdated']);
	}

	if ( $latestUpdate['last_updated_timestamp'] > $lastUpdatedOld['last_updated_timestamp'] ) {
		if ( $latestUpdate['last_updated_timestamp'] != INF )
			copy($caPaths['lastUpdated'],$caPaths['lastUpdated-old']);

		if ( ! $badDownload )
			@unlink($caPaths['community-templates-info']);
	}

	if (!file_exists($caPaths['community-templates-info'])) {
		$updatedSyncFlag = true;
		if (! DownloadApplicationFeed() ) {
			$o['script'] = "$('.startupButton,.caMenu,.menuHeader').hide();$('.caRelated').show();";
			$o['data'] =  "<div class='ca_center'><font size='4'><span class='ca_bold'>".tr("Download of appfeed failed.")."</span></font><font size='3'><br><br>Community Applications requires your server to have internet access.  The most common cause of this failure is a failure to resolve DNS addresses.  You can try and reset your modem and router to fix this issue, or set static DNS addresses (Settings - Network Settings) of 208.67.222.222 and 208.67.220.220 and try again.<br><br>Alternatively, there is also a chance that the server handling the application feed is temporarily down.";
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
			postReturn($o);
			break;
		}
	}
	getConvertedTemplates();
	moderateTemplates();
	$currentServer = @file_get_contents($caPaths['currentServer']);
	postReturn(['status'=>"ok",'script'=>"feedWarning('$currentServer');"]);
	break;

####################################################################################
# display_content - displays the templates according to view mode, sort order, etc #
####################################################################################
case 'display_content':
	$sortOrder = getSortOrder(getPostArray('sortOrder'));
	$pageNumber = getPost("pageNumber","1");
	$startup = getPost("startup",false);
	$selectedApps = json_decode(getPost("selected",false),true);

	$o['display'] = file_exists($caPaths['community-templates-displayed']) ? display_apps($pageNumber,$selectedApps,$startup) : "";
	$displayedApps = readJsonFile($caPaths['community-templates-displayed']);
	if ( ! is_array($displayedApps['community']) || count($displayedApps['community']) < 1)
		$o['script'] = "disableSort();";
	$currentServer = @file_get_contents($caPaths['currentServer']);
	$o['script'] .= "feedWarning('$currentServer');";
	postReturn($o);
	break;

#######################################################################
# convert_docker - called when system adds a container from dockerHub #
#######################################################################
case 'convert_docker':
	$dockerID = getPost("ID","");

	$file = readJsonFile($caPaths['dockerSearchResults']);
	$docker = $file['results'][$dockerID];
	$docker['Description'] = str_replace("&", "&amp;", $docker['Description']);
	@unlink($caPaths['Dockerfile']);

	$dockerfile['Name'] = $docker['Name'];
	$dockerfile['Support'] = $docker['DockerHub'];
	$dockerfile['Description'] = $docker['Description']."   Converted By Community Applications   Always verify this template (and values) against the dockerhub support page for the container";
	$dockerfile['Overview'] = $dockerfile['Description'];
	$dockerfile['Registry'] = $docker['DockerHub'];
	$dockerfile['Repository'] = $docker['Repository'];
	$dockerfile['BindTime'] = "true";
	$dockerfile['Privileged'] = "false";
	$dockerfile['Networking']['Mode'] = "bridge";
	$dockerfile['Icon'] = "/plugins/dynamix.docker.manager/images/question.png";
	$dockerXML = makeXML($dockerfile);

	$xmlFile = $caPaths['convertedTemplates']."DockerHub/";
	@mkdir($xmlFile,0777,true);
	$xmlFile .= str_replace("/","-",$docker['Repository']).".xml";
	file_put_contents($xmlFile,$dockerXML);
	file_put_contents($caPaths['addConverted'],"Dante");
	postReturn(['xml'=>$xmlFile]);
	break;

#########################################################
# search_dockerhub - returns the results from dockerHub #
#########################################################
case 'search_dockerhub':
	$filter     = getPost("filter","");
	$pageNumber = getPost("page","1");
	$sortOrder  = getSortOrder(getPostArray('sortOrder'));

	$communityTemplates = readJsonFile($caPaths['community-templates-info']);
	$filter = str_replace(" ","%20",$filter);
	$jsonPage = shell_exec("curl -s -X GET 'https://registry.hub.docker.com/v1/search?q=$filter&page=$pageNumber'");
	$pageresults = json_decode($jsonPage,true);
	$num_pages = $pageresults['num_pages'];

	if ($pageresults['num_results'] == 0) {
		$o['display'] = "<div class='ca_NoDockerAppsFound'>".tr("No Matching Applications Found On Docker Hub")."</div>";
		$o['script'] = "$('#dockerSearch').hide();";
		postReturn($o);
		@unlink($caPaths['dockerSerchResults']);
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
		$o['CardDescription'] = (strlen($o['Description']) > 240) ? substr($o['Description'],0,240)." ..." : $o['Description'];
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

		$dockerResults[$i] = $o;
		$i=++$i;
	}
	$dockerFile['num_pages'] = $num_pages;
	$dockerFile['page_number'] = $pageNumber;
	$dockerFile['results'] = $dockerResults;

	writeJsonFile($caPaths['dockerSearchResults'],$dockerFile);
	postReturn(['display'=>displaySearchResults($pageNumber)]);
	break;

#####################################################################
# dismiss_warning - dismisses the warning from appearing at startup #
#####################################################################
case 'dismiss_warning':
	file_put_contents($caPaths['warningAccepted'],"warning dismissed");
	postReturn(['status'=>"warning dismissed"]);
	break;

case 'dismiss_plugin_warning':
	file_put_contents($caPaths['pluginWarning'],"disclaimer ok");
	postReturn(['status'=>"disclaimed"]);
	break;

###############################################################
# Displays the list of installed or previously installed apps #
###############################################################
case 'previous_apps':
	$installed = getPost("installed","");
	$dockerUpdateStatus = readJsonFile($caPaths['dockerUpdateStatus']);
	$info = $caSettings['dockerRunning'] ? $DockerClient->getDockerContainers() : array();

	@unlink($caPaths['community-templates-allSearchResults']);
	@unlink($caPaths['community-templates-catSearchResults']);
	@unlink($caPaths['repositoriesDisplayed']);
	@unlink($caPaths['startupDisplayed']);

	$file = readJsonFile($caPaths['community-templates-info']);

# $info contains all installed containers
# now correlate that to a template;
# this section handles containers that have not been renamed from the appfeed
if ( $caSettings['dockerRunning'] ) {
	$all_files = glob("{$caPaths['dockerManTemplates']}/*.xml");
	$all_files = $all_files ?: array();
	if ( $installed == "true" ) {
		foreach ($info as $installedDocker) {
			$installedName = $installedDocker['Name'];
			if ( startsWith($installedImage,"library/") ) # official images are in DockerClient as library/mysql eg but template just shows mysql
				$installedImage = str_replace("library/","",$installedImage);

			foreach ($file as $template) {
				if ( $installedName == $template['Name'] ) {
					$template['testrepo'] = $installedImage;
					if ( startsWith($installedImage,$template['Repository']) ) {
						$template['Uninstall'] = true;
						$template['InstallPath'] = $template['Path'];
						if ( $dockerUpdateStatus[$installedImage]['status'] == "false" || $dockerUpdateStatus[$template['Name']] == "false" ) {
							$template['UpdateAvailable'] = true;
						}
						if ($template['Blacklist'] ) continue;

						$displayed[] = $template;
						break;
					}
				}
			}
		}
# handle renamed containers
		foreach ($all_files as $xmlfile) {
			$o = readXmlFile($xmlfile);
			$o['Description'] = fixDescription($o['Description']);
			$o['Overview'] = fixDescription($o['Overview']);
			$o['InstallPath'] = $xmlfile;
			$o['UnknownCompatible'] = true;

			$flag = false;
			$containerID = false;
			foreach ($file as $templateDocker) {
# use startsWith to eliminate any version tags (:latest)
				if ( startsWith($templateDocker['Repository'], $testRepo) ) {
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
							if ( ! $searchResult ) {
								$searchResult = searchArray($file,'Repository',explode(":",$o['Repository'])[0]);
							}
							if ( $searchResult !== false ) {
								$tempPath = $o['InstallPath'];
								$containerID = $file[$searchResult]['ID'];
								$o = $file[$searchResult];
								$o['Name'] = $installedName;
								$o['InstallPath'] = $tempPath;
								$o['SortName'] = $installedName;
								if ( $dockerUpdateStatus[$installedImage]['status'] == "false" || $dockerUpdateStatus[$template['Name']] == "false" ) {
									$o['UpdateAvailable'] = true;
								}
							}
							break;
						}
					}
				}
				if ( $runningflag ) {
					$o['Uninstall'] = true;
					$o['ID'] = $containerID;
					if ( $o['Blacklist'] ) 	continue;

					# handle a PR from LT where it is possible for an identical template (xml) to be present twice, with different filenames.
					# Without this, an app could appear to be shown in installed apps twice
					$fat32Fix[$searchResult]++;
					if ($fat32Fix[$searchResult] > 1) continue;
					$displayed[] = $o;
				}
			}
		}
	} else {
# now get the old not installed docker apps
		foreach ($all_files as $xmlfile) {
			$o = readXmlFile($xmlfile);
			if ( ! $o ) continue;
			$o['Description'] = fixDescription($o['Description']);
			$o['Overview'] = fixDescription($o['Overview']);
			$o['InstallPath'] = $xmlfile;
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
				$testRepo = explode(":",$o['Repository'])[0];
# now associate the template back to a template in the appfeed
				foreach ($file as $appTemplate) {
					if (startsWith($appTemplate['Repository'],$testRepo)) {
						$tempPath = $o['InstallPath'];
						$tempName = $o['Name'];
						$o = $appTemplate;
						$o['Removable'] = true;
						$o['InstallPath'] = $tempPath;
						$o['Name'] = $tempName;
						$o['SortName'] = $o['Name'];
						break;
					}
				}

				if ( ! $o['Blacklist'] )
					$displayed[] = $o;
			}
		}
	}
}
# Now work on plugins
	if ( $installed == "true" ) {
		foreach ($file as $template) {
			if ( ! $template['Plugin'] ) continue;

			$filename = pathinfo($template['Repository'],PATHINFO_BASENAME);

			if ( checkInstalledPlugin($template) ) {
				if ( $template['Blacklist'] ) continue;

				$template['InstallPath'] = "/var/log/plugins/$filename";
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
						if ( $template['Blacklist'] || ( ($caSettings['hideIncompatible'] == "true") && (! $template['Compatible']) ) ) continue;
						$oldPlugURL = trim(plugin("pluginURL",$oldplug));
						if ( strtolower(trim($template['PluginURL'])) != strtolower(trim($oldPlugURL)) ) {
							continue;
						}
						$template['Removable'] = true;
						$template['InstallPath'] = $oldplug;

						$displayed[] = $template;
						break;
					}
				}
			}
		}
	}
	$displayedApplications['community'] = $displayed;
	writeJsonFile($caPaths['community-templates-displayed'],$displayedApplications);
	postReturn(['status'=>"ok"]);
	break;

####################################################################################
# Removes an app from the previously installed list (ie: deletes the user template #
####################################################################################
case 'remove_application':
	$application = getPost("application","");
	if ( pathinfo($application,PATHINFO_EXTENSION) == "xml" || pathinfo($application,PATHINFO_EXTENSION) == "plg" )
		@unlink($application);

	postReturn(['status'=>"ok"]);
	break;

###################################################################################
# Checks for an update still available (to update display) after update installed #
###################################################################################
case 'updatePLGstatus':
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

	if ( $dockerRunning[$container]['Running'] )
		myStopContainer($dockerRunning[$container]['Id']);

	$DockerClient->removeContainer($containerName,$dockerRunning[$container]['Id']);
	$DockerClient->removeImage($dockerRunning[$container]['ImageId']);

	postReturn(['status'=>"Uninstalled"]);
	break;

##################################################
# Pins / Unpins an application for later viewing #
##################################################
case "pinApp":
	$repository = getPost("repository","oops");
	$name = getPost("name","oops");
	$pinnedApps = readJsonFile($caPaths['pinnedV2']);
	$pinnedApps["$repository&$name"] = $pinnedApps["$repository&$name"] ? false : "$repository&$name";
	writeJsonFile($caPaths['pinnedV2'],$pinnedApps);
	break;

####################################
# Displays the pinned applications #
####################################
case "pinnedApps":
	$pinnedApps = readJsonFile($caPaths['pinnedV2']);
	$file = readJsonFile($caPaths['community-templates-info']);
	@unlink($caPaths['community-templates-allSearchResults']);
	@unlink($caPaths['community-templates-catSearchResults']);
	@unlink($caPaths['repositoriesDisplayed']);
	@unlink($caPaths['startupDisplayed']);
	
	foreach ($pinnedApps as $pinned) {
		$startIndex = 0;
		$search = explode("&",$pinned);
		for ($i=0;$i<10;$i++) {
			$index = searchArray($file,"Repository",$search[0],$startIndex);
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
	$displayedApplications['community'] = $displayed;
	$displayedApplications['pinnedFlag']  = true;
	writeJsonFile($caPaths['community-templates-displayed'],$displayedApplications);
	postReturn(["status"=>"ok"]);
	break;

################################################
# Displays the possible branch tags for an app #
################################################
case 'displayTags':
	$leadTemplate = getPost("leadTemplate","oops");
	postReturn(['tags'=>formatTags($leadTemplate)]);
	break;

###########################################
# Displays The Statistics For The Appfeed #
###########################################
case 'statistics':
	$statistics = download_json($caPaths['statisticsURL'],$caPaths['statistics']);
	download_json($caPaths['moderationURL'],$caPaths['moderation']);
	$statistics['totalModeration'] = count(readJsonFile($caPaths['moderation']));
	$repositories = readJsonFile($caPaths['repositoryList']);
	$templates = readJsonFile($caPaths['community-templates-info']);
	pluginDupe($templates);
	$invalidXML = readJsonFile($caPaths['invalidXML_txt']);

	foreach ($templates as $template) {
		if ( $template['Deprecated'] && ! $template['Blacklist'] && ! $template['BranchID']) $statistics['totalDeprecated']++;

		if ( ! $template['Compatible'] ) $statistics['totalIncompatible']++;

		if ( $template['Blacklist'] ) $statistics['blacklist']++;

		if ( $template['Private'] && ! $template['Blacklist']) {
			if ( ! ($caSettings['hideDeprecated'] == 'true' && $template['Deprecated']) )
				$statistics['private']++;
		}
		if ( ! $template['PluginURL'] && ! $template['Repository'] )
			$statistics['invalidXML']++;
		else {
			if ( $template['PluginURL'] )
				$statistics['plugin']++;
			else
				$statistics['docker']++;
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
	$defaultArray = Array('caFixed' => 0,'totalApplications' => 0, 'repository' => 0, 'docker' => 0, 'plugin' => 0, 'invalidXML' => 0, 'blacklist' => 0, 'totalIncompatible' =>0, 'totalDeprecated' => 0, 'totalModeration' => 0, 'private' => 0, 'NoSupport' => 0);
	$statistics = array_merge($defaultArray,$statistics);

	foreach ($statistics as &$stat) {
		if ( ! $stat ) $stat = "0";
	}

	$currentServer = @file_get_contents($caPaths['currentServer']);
	if ( $currentServer != "Primary Server" )
		$currentServer = "<i class='fa fa-exclamation-triangle ca_serverWarning' aria-hidden='true'></i> $currentServer";

	$statistics['invalidXML'] = @count($invalidXML) ?: tr("unknown");
	$statistics['repositories'] = @count($repositories) ?: tr("unknown");
	$o =  "<div style='height:auto;overflow:scroll; overflow-x:hidden; overflow-y:hidden;margin:auto;width:700px;'>";
	$o .= "<table style='margin-top:1rem;'>";
	$o .= "<tr style='height:6rem;'><td colspan='2'><div class='ca_center'><i class='fa fa-users' style='font-size:6rem;'></i></td></tr>";
	$o .= "<tr><td colspan='2'><div class='ca_center'><font size='5rem;'>Community Applications</font></div></td></tr>";
	$o .= "<tr><td class='ca_table'>".tr("Last Change To Application Feed")."</td><td class='ca_stat'>$updateTime<br>".tr($currentServer)."</td></tr>";
	$o .= "<tr><td class='ca_table'>".tr("Number Of Docker Applications")."</td><td class='ca_stat'>{$statistics['docker']}</td></tr>";
	$o .= "<tr><td class='ca_table'>".tr("Number Of Plugin Applications")."</td><td class='ca_stat'>{$statistics['plugin']}</td></tr>";
	$o .= "<tr><td class='ca_table'>".tr("Number Of Templates")."</td><td class='ca_stat'>{$statistics['totalApplications']}</td></tr>";
	$o .= "<tr><td class='ca_table'><a onclick='showModeration(&quot;Repository&quot;,&quot;".tr("Repository List")."&quot;);' style='cursor:pointer;'>".tr("Number Of Repositories")."</a></td><td class='ca_stat'>{$statistics['repositories']}</td></tr>";
	$o .= "<tr><td class='ca_table'><a data-category='PRIVATE' onclick='showSpecialCategory(this);' style='cursor:pointer;'>".tr("Number Of Private Docker Applications")."</a></td><td class='ca_stat'>{$statistics['private']}</td></tr>";
	$o .= "<tr><td class='ca_table'><a onclick='showModeration(&quot;Invalid&quot;,&quot;".tr("All Invalid Templates Found")."&quot;);' style='cursor:pointer'>".tr("Number Of Invalid Templates")."</a></td><td class='ca_stat'>{$statistics['invalidXML']}</td></tr>";
	$o .= "<tr><td class='ca_table'><a onclick='showModeration(&quot;Fixed&quot;,&quot;".tr("Template Errors")."&quot;);' style='cursor:pointer'>".tr("Number Of Template Errors")."</a></td><td class='ca_stat'>{$statistics['caFixed']}+</td></tr>";
	$o .= "<tr><td class='ca_table'><a data-category='BLACKLIST' onclick='showSpecialCategory(this);' style='cursor:pointer'>".tr("Number Of Blacklisted Apps")."</a></td><td class='ca_stat'>{$statistics['blacklist']}</td></tr>";
	$o .= "<tr><td class='ca_table'><a data-category='INCOMPATIBLE' onclick='showSpecialCategory(this);' style='cursor:pointer'>".tr("Number Of Incompatible Applications")."</a></td><td class='ca_stat'>{$statistics['totalIncompatible']}</td></tr>";
	$o .= "<tr><td class='ca_table'><a data-category='DEPRECATED' onclick='showSpecialCategory(this);' style='cursor:pointer'>".tr("Number Of Deprecated Applications")."</a></td><td class='ca_stat'>{$statistics['totalDeprecated']}</td></tr>";
	$o .= "<tr><td class='ca_table'><a onclick='showModeration(&quot;Moderation&quot;,&quot;".tr("All Moderation Entries")."&quot;);' style='cursor:pointer'>".tr("Number Of Moderation Entries")."</a></td><td class='ca_stat'>{$statistics['totalModeration']}+</td></tr>";
	$o .= "<tr><td class='ca_table'><a href='{$caPaths['application-feed']}' target='_blank'>".tr("Primary Server")."</a> / <a href='{$caPaths['application-feedBackup']}' target='_blank'> ".tr("Backup Server")."</a></td></tr>";
	$o .= "</table>";
	$o .= "<div class='ca_center'><a href='https://forums.unraid.net/topic/87144-ca-application-policies/' target='_blank'>".tr("Application Policy")."</a></div>";

	postReturn(['statistics'=>$o]);
	break;

#######################################
# Removes a private app from the list #
#######################################
case 'removePrivateApp':
	$path = getPost("path",false);

	if ( ! $path || pathinfo($path,PATHINFO_EXTENSION) != "xml") {
		postReturn(["error"=>"Something went wrong-> not an xml file: $path"]);
		break;
	}
	$templates = readJsonFile($caPaths['community-templates-info']);
	$displayed = readJsonFile($caPaths['community-templates-displayed']);
	foreach ( $displayed as &$displayType ) {
		foreach ( $displayType as &$display ) {
			if ( $display['Path'] == $path )
				$display['Blacklist'] = true;
		}
	}
	foreach ( $templates as &$template ) {
		if ( $template['Path'] == $path )
			$template['Blacklist'] = true;
	}
	writeJsonFile($caPaths['community-templates-info'],$templates);
	writeJsonFile($caPaths['community-templates-displayed'],$displayed);
	@unlink($path);
	postReturn(["status"=>"ok"]);
	break;

####################################################
# Creates the entries for autocomplete on searches #
####################################################
case 'populateAutoComplete':
	$templates = readJsonFile($caPaths['community-templates-info']);
	$autoComplete = array_map(function($x){return str_replace(":","",tr($x['Cat']));},readJsonFile($caPaths['categoryList']));
	foreach ($templates as $template) {
		if ( $template['RepoTemplate'] )
			continue;
		if ( ! $template['Blacklist'] && ! ($template['Deprecated'] && $caSettings['hideDeprecated'] == "true") && ($template['Compatible'] || $caSettings['hideIncompatible'] != "true") ) {
			if ( $template['Language'] && $template['LanguageLocal'] ) {
				$autoComplete[strtolower($template['Language'])] = $template['Language'];
				$autoComplete[strtolower($template['LanguageLocal'])] = $template['LanguageLocal'];
			} else {
				$autoComplete[$template['Repo']] = $template['Repo'];
			}
			$name = trim(strtolower($template['SortName']));
/* 			if ( $name !== "pihole template" ) {
				$name = str_ireplace(strtolower($template['Author'])."-","",$name);
				$name = str_ireplace(strtolower($template['Author'])." ","",$name);
			} */
			$autoComplete[$name] = $name;
			if ( startsWith($autoComplete[$name],"dynamix ") )
				$autoComplete[$name] = str_replace("dynamix ","",$autoComplete[$name]);
			if ( startsWith($autoComplete[$name],"ca ") )
				$autoComplete[$name] = str_replace("ca ","",$autoComplete[$name]);
			if ( startsWith($autoComplete[$name],"binhex ") )
				$autoComplete[$name] = str_replace("binhex ","",$autoComplete[$name]);
				
			if ( $template['Plugin'] )
				$autoComplete[strtolower($template['Author'])] = $template['Author'];
		}
	}
	if ( version_compare("6.9.0-beta1",$caSettings['unRaidVersion'],"<") )
		$autoComplete[tr("language")] = tr("Language");

	postReturn(['autocomplete'=>array_values(array_filter(array_unique($autoComplete)))]);
	break;

##########################
# Displays the changelog #
##########################
case 'caChangeLog':
	require_once("webGui/include/Markdown.php");
	$o = "<div style='margin:auto;width:500px;'>";
	$o .= "<div class='ca_center'><font size='4rem'>".tr("Community Applications Changelog")."</font></div><br><br>";
	postReturn(["changelog"=>$o.Markdown(plugin("changes","/var/log/plugins/community.applications.plg"))."<br><br>"]);
	break;

###############################
# Populates the category list #
###############################
case 'get_categories':
	$categories = readJsonFile($caPaths['categoryList']);
	if ( ! is_array($categories) || empty($categories) ) {
		$cat = "<span class='ca_fa-warning'></span> Category list N/A<br><br>";
		postReturn(['categories'=>$cat]);
		break;
	} else {
		if ($translationsAllowed) {
			$categories[] = array("Des"=>"Language","Cat"=>"Language:");
		}
		foreach ($categories as $category) {
			$category['Des'] = tr($category['Des']);
			if ( is_array($category['Sub']) ) {
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

		foreach ($newCat as $category) {
			$cat .= "<li class='categoryMenu caMenuItem' data-category='{$category['Cat']}'>".$category['Des']."</li>";
			if (is_array($category['Sub'])) {
				$cat .= "<ul class='subCategory'>";
				foreach($category['Sub'] as $subcategory) {
					$cat .= "<li class='categoryMenu caMenuItem' data-category='{$subcategory['Cat']}'>".$subcategory['Des']."</li>";
				}
				$cat .= "</ul>";
			}
		}
		$templates = readJsonFile($caPaths['community-templates-info']);
		foreach ($templates as $template) {
			if ($template['Private'] == true && ! $template['Blacklist']) {
				$cat .= "<li class='categoryMenu caMenuItem' data-category='PRIVATE'>".tr("Private Apps")."</li>";
				break;
			}
		}
	}
	postReturn(["categories"=>$cat]);
	break;

##############################
# Get the html for the popup #
##############################
case 'getPopupDescription':
	$appNumber = getPost("appPath","");
	postReturn(getPopupDescription($appNumber));
	break;
	
#################################
# Get the html for a repo popup #
#################################
case 'getRepoDescription':
	$repository = html_entity_decode(getPost("repository",""),ENT_QUOTES);
	postReturn(getRepoDescription($repository));
	break;

###########################################
# Creates the XML for a container install #
###########################################
case 'createXML':
	$xmlFile = getPost("xml","");
	if ( ! $xmlFile ) {
		postReturn(["error"=>"CreateXML: XML file was missing"]);
		break;
	}
	$templates = readJsonFile($caPaths['community-templates-info']);
	if ( ! $templates ) {
		postReturn(["error"=>"Create XML: templates file missing or empty"]);
		break;
	}
	if ( !startsWith($xmlFile,"/boot/") ) {
		$index = searchArray($templates,"Path",$xmlFile);
		if ( $index === false ) {
			postReturn(["error"=>"Create XML: couldn't find template with path of $xmlFile"]);
			break;
		}
		$template = $templates[$index];
		if ( $template['OriginalOverview'] )
			$template['Overview'] = $template['OriginalOverview'];
		if ( $template['OriginalDescription'] )
			$template['Description'] = $template['OriginalDescription'];

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
		$disksPresent = array_merge($cachePools,$disksPresent,array("disks"));

		// check to see if user shares enabled
		$unRaidVars = parse_ini_file($caPaths['unRaidVars']);
		if ( $unRaidVars['shareUser'] == "e" )
			$disksPresent[] = "user";
		if ( @is_array($template['Data']['Volume']) ) {
			$testarray = $template['Data']['Volume'];
			if ( ! is_array($testarray[0]) ) $testarray = array($testarray);
			foreach ($testarray as &$volume) {
				$diskReferenced = array_values(array_filter(explode("/",$volume['HostDir'])));
				if ( $diskReferenced[0] == "mnt" && $diskReferenced[1] && ! in_array($diskReferenced[1],$disksPresent) ) {
					$volume['HostDir'] = str_replace("/mnt/{$diskReferenced[1]}/","/mnt/{$disksPresent[0]}/",$volume['HostDir']);
				}
			}
			$template['Data']['Volume'] = $testarray;
		}

		if ( $template['Config'] ) {
			$testarray = $template['Config'] ?: array();
			if (!$testarray[0]) $testarray = array($testarray);

			foreach ($testarray as &$config) {
				if ( is_array($config['@attributes']) ) {
					if ( $config['@attributes']['Type'] == "Path" ) {
						$defaultReferenced = array_values(array_filter(explode("/",$config['@attributes']['Default'])));

						if ( $defaultReferenced[0] == "mnt" && $defaultReferenced[1] && ! in_array($defaultReferenced[1],$disksPresent) )
							$config['@attributes']['Default'] = str_replace("/mnt/{$defaultReferenced[1]}/","/mnt/{$disksPresent[0]}/",$config['@attributes']['Default']);

						$valueReferenced = array_values(array_filter(explode("/",$config['value'])));
						if ( $valueReferenced[0] == "mnt" && $valueReferenced[1] && ! in_array($valueReferenced[1],$disksPresent) )
							$config['value'] = str_replace("/mnt/{$valueReferenced[1]}/","/mnt/{$disksPresent[0]}/",$config['value']);

					}
				}
			}
			$template['Config'] = $testarray;
		}
		$template['Name'] = str_replace(" ","-",$template['Name']);
		$xml = makeXML($template);
		@mkdir(dirname($xmlFile));
		file_put_contents($xmlFile,$xml);
	}
	postReturn(["status"=>"ok","cache"=>$cacheVolume]);
	break;

########################
# Switch to a language #
########################
case 'switchLanguage':
	$language = getPost("language","");
	if ( $language == "en_US" )
		$language = "";

	if ( ! is_dir("/usr/local/emhttp/languages/$language") )  {
		postReturn(["error"=>"language $language is not installed"]);
		break;
	}
	$dynamixSettings = @parse_ini_file($caPaths['dynamixSettings'],true);
	$dynamixSettings['display']['locale'] = $language;
	write_ini_file($caPaths['dynamixSettings'],$dynamixSettings);
	postReturn(["status"=> "ok"]);
	break;

#######################################################
# Delete multiple checked off apps from previous apps #
#######################################################
case 'remove_multiApplications':
	$apps = getPostArray("apps");
	if ( ! count($apps) ) {
		postReturn(["error"=>"No apps were in post when trying to remove multiple applications"]);
		break;
	}
	foreach ($apps as $app) {
		if ( strpos($app,"/boot/config/") === false ) {
			$error = "Remove multiple apps: $app was not in /boot/config";
			break;
		}
		@unlink($app);
	}
	if ( $error )
		postReturn(["error"=>$error]);
	else
		postReturn(["status"=>"ok"]);
	break;

############################################
# Get's the categories present on a search #
############################################
case 'getCategoriesPresent':
	if ( is_file($caPaths['community-templates-allSearchResults']) )
		$displayed = readJsonFile($caPaths['community-templates-allSearchResults']);
	else
		$displayed = readJsonFile($caPaths['community-templates-displayed']);

	$categories = array();
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
	break;

##################################
# Set's the favourite repository #
##################################
case 'toggleFavourite':
	$repository = html_entity_decode(getPost("repository",""),ENT_QUOTES);

	$caSettings['favourite'] = $repository;
	write_ini_file($caPaths['pluginSettings'],$caSettings);
	postReturn(['status'=>"ok"]);
	break;

####################################
# Returns the favourite repository #
####################################
case 'getFavourite':
	postReturn(["favourite"=>$caSettings['favourite']]);
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
	global $caPaths, $caSettings, $statistics, $translationsAllowed;

	exec("rm -rf '{$caPaths['tempFiles']}'");
	@mkdir($caPaths['templates-community'],0777,true);

	$currentFeed = "Primary Server";
	$downloadURL = randomFile();
	$ApplicationFeed = download_json($caPaths['application-feed'],$downloadURL);
	if ( ! is_array($ApplicationFeed['applist']) ) {
		$currentFeed = "Backup Server";
		$ApplicationFeed = download_json($caPaths['application-feedBackup'],$downloadURL);
	}
	@unlink($downloadURL);
	if ( ! is_array($ApplicationFeed['applist']) ) {
		@unlink($caPaths['currentServer']);
		file_put_contents($caPaths['appFeedDownloadError'],$downloadURL);
		return false;
	}
	file_put_contents($caPaths['currentServer'],$currentFeed);
	$i = 0;
	$lastUpdated['last_updated_timestamp'] = $ApplicationFeed['last_updated_timestamp'];
	writeJsonFile($caPaths['lastUpdated-old'],$lastUpdated);
	$myTemplates = array();

	foreach ($ApplicationFeed['applist'] as $o) {
		if ( (! $o['Repository']) && (! $o['Plugin']) && (!$o['Language'])){
			$invalidXML[] = $o;
			continue;
		}
		if ( ! $translationsAllowed && $o['Language'] ) {
			$invalidXML[] = $o;
			continue;
		}
		unset($o['Category']);
		if ( $o['CategoryList'] ) {
			foreach ($o['CategoryList'] as $cat) {
				$cat = str_replace("-",":",$cat);
				if ( ! strpos($cat,":") ) 
					$cat .= ":";
				$o['Category'] .= "$cat ";
			}
		}
		$o['Category'] = trim($o['Category']);
		
		if ( $o['Language'] ) {
			$o['Category'] = "Language:";
			$o['Compatible'] = true;
			$o['Description'] = str_replace("\n","<br>",trim($o['Description']));
		}

		# Move the appropriate stuff over into a CA data file
		$o['ID']            = $i;
		$o['Displayable']   = true;
		$o['Author']        = getAuthor($o);
		$o['DockerHubName'] = strtolower($o['Name']);
		$o['RepoName']      = $o['Repo'];
		$o['SortAuthor']    = $o['Author'];
		$o['SortName']      = str_replace("-"," ",$o['Name']);
		$o['CardDescription'] = (strlen($o['Description']) > 240) ? substr($o['Description'],0,240)." ..." : $o['Description'];

		if ( $o['IconHTTPS'] )
			$o['IconHTTPS'] = $caPaths['iconHTTPSbase'] .$o['IconHTTPS'];

		if ( $o['PluginURL'] ) {
			$o['Author']        = $o['PluginAuthor'];
			$o['Repository']    = $o['PluginURL'];
		}

		$o['Blacklist'] = $o['CABlacklist'] ? true : $o['Blacklist'];
		$o['MinVer'] = max(array($o['MinVer'],$o['UpdateMinVer']));
		$tag = explode(":",$o['Repository']);
		if (! $tag[1])
			$tag[1] = "latest";
		$o['Path'] = $caPaths['templates-community']."/".alphaNumeric($o['RepoName'])."/".alphaNumeric($o['Author'])."-".alphaNumeric($o['Name'])."-{$tag[1]}";
		if ( file_exists($o['Path'].".xml") ) {
			$o['Path'] .= "(1)";
		}
		$o['Path'] .= ".xml";

		$o = fixTemplates($o);
		if ( ! $o ) continue;

		if ( is_array($o['trends']) && count($o['trends']) > 1 ) {
			$o['trendDelta'] = end($o['trends']) - $o['trends'][0];
			$o['trendAverage'] = array_sum($o['trends'])/count($o['trends']);
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
				$subBranch['Path'] = $caPaths['templates-community']."/".$i.".xml";
				$subBranch['Displayable'] = false;
				$subBranch['ID'] = $i;
				$subBranch['Overview'] = $o['OriginalOverview'] ?: $o['Overview'];
				$subBranch['Description'] = $o['OriginalDescription'] ?: $o['Description'];
				$replaceKeys = array_diff(array_keys($branch),array("Tag","TagDescription"));
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
	writeJsonFile($caPaths['categoryList'],$ApplicationFeed['categories']);
	writeJsonFile($caPaths['repositoryList'],$ApplicationFeed['repositories']);
	return true;
}

function getConvertedTemplates() {
	global $caPaths, $caSettings, $statistics;

# Start by removing any pre-existing private (converted templates)
	$templates = readJsonFile($caPaths['community-templates-info']);

	if ( empty($templates) ) return false;

	foreach ($templates as $template) {
		if ( ! $template['Private'] )
			$myTemplates[] = $template;
	}
	$appCount = count($myTemplates);
	$i = $appCount;
	unset($Repos);

	if ( ! is_dir($caPaths['convertedTemplates']) ) return;

	$privateTemplates = glob($caPaths['convertedTemplates']."*/*.xml");
	foreach ($privateTemplates as $template) {
		$o = readXmlFile($template);
		if ( ! $o['Repository'] ) continue;

		$o['Private']      = true;
		$o['RepoName']     = basename(pathinfo($template,PATHINFO_DIRNAME))." Repository";
		$o['ID']           = $i;
		$o['Displayable']  = true;
		$o['Date']         = ( $o['Date'] ) ? strtotime( $o['Date'] ) : 0;
		$o['SortAuthor']   = $o['Author'];
		$o['Compatible']   = versionCheck($o);
		$o['CardDescription'] = (strlen($o['Description']) > 240) ? substr($o['Description'],0,240)." ..." : $o['Description'];

		$o = fixTemplates($o);
		$myTemplates[$i]  = $o;
		$i = ++$i;
	}
	writeJsonFile($caPaths['community-templates-info'],$myTemplates);
	return true;
}

#############################
# Selects an app of the day #
#############################
function appOfDay($file) {
	global $caPaths,$caSettings,$sortOrder;

	$info = getRunningContainers();

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
					unset($app);
			}
			if ( ! $appOfDay ) {
				shuffle($file);
				foreach ($file as $template) {
					if ( ! checkRandomApp($template,$info,true) ) continue;
					$appOfDay[] = $template['ID'];
					if (count($appOfDay) == 25) break;
				}
			}
			writeJsonFile($caPaths['appOfTheDay'],$appOfDay);
			break;
		case "new":
			$sortOrder['sortBy'] = "Date";
			$sortOrder['sortDir'] = "Down";
			usort($file,"mySort");
			foreach ($file as $template) {
				if ( ! checkRandomApp($template) ) continue;
				$appOfDay[] = $template['ID'];
				if (count($appOfDay) == 25) break;
			}
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
						if ( count($appOfDay) == 25 ) break;
					}
				}
			}
			break;
		case "topperforming":
			$sortOrder['sortBy'] = "trending";
			$sortOrder['sortDir'] = "Down";
			usort($file,"mySort");
			foreach ($file as $template) {
				if ( ! is_array($template['trends']) ) continue;
				if ( count($template['trends']) < 6 ) continue;
				if ( startsWith($template['Repository'],"ich777/steamcmd") ) continue; // because a ton of apps all use the same repo
				if ( $template['trending'] && ($template['downloads'] > 100000) ) {
					if ( checkRandomApp($template) ) {
						$appOfDay[] = $template['ID'];
						if ( count($appOfDay) == 25 ) break;
					}
				}
			}
			break;
		case "trending":
			$sortOrder['sortBy'] = "trendDelta";
			$sortOrder['sortDir'] = "Down";
			usort($file,"mySort");
			foreach ($file as $template) {
				if ( count($template['trends'] ) < 3 ) continue;
				if ( startsWith($template['Repository'],"ich777/steamcmd") ) continue; // because a ton of apps all use the same repo`
				if ( $template['trending'] && ($template['downloads'] > 10000) ) {
					if ( checkRandomApp($template) ) {
						$appOfDay[] = $template['ID'];
						if ( count($appOfDay) == 25 ) break;
					}
				}
			}
			break;
	}
	return $appOfDay ?: array();
}

#####################################################
# Checks selected app for eligibility as app of day #
#####################################################
function checkRandomApp($test,$info=array(),$random=false) {
	global $caSettings;

	if ( $test['Name'] == "Community Applications" )  return false;
	if ( $test['BranchName'] )                        return false;
	if ( ! $test['Displayable'] )                     return false;
	if ( ! $test['Compatible'] && $caSettings['hideIncompatible'] == "true" ) return false;
	if ( $test['Blacklist'] )                         return false;
	if ( $test['Deprecated'] && ( $caSettings['hideDeprecated'] == "true" ) ) return false;
	if ( $random ) {
		$return = ! appInstalled($test,$info);
		if (! $return) {
			exec("logger {$test['Repository']}");
		}

		return ! appInstalled($test,$info);
	}
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
		$templates = $temp['community'];
	} else {
		$temp = readJsonFile($caPaths['community-templates-displayed']);
		$templates = $temp['community'];
	}
	if ( is_file($caPaths['startupDisplayed']) ) {
		$templates = readJsonFile($caPaths['community-templates-info']);
	}
	$templates = $templates ?: array();
	$allRepos = array();
	$bio = array();
	foreach ($templates as $template) {
		$repoName = $template['RepoName'];
		if ( ! $repoName ) continue;
		if ( $repoName == $caSettings['favourite'] ) {
			$fav = $repositories[$repoName];
			$fav['RepositoryTemplate'] = true;
			$fav['RepoName'] = $repoName;
			$fav['SortName'] = $repoName;
		} else {
			if ( $repositories[$repoName]['bio'] ) {
				$bio[$repoName] = $repositories[$repoName];
				$bio[$repoName] = $repositories[$repoName];
				$bio[$repoName]['RepositoryTemplate'] = true;
				$bio[$repoName]['RepoName'] = $repoName;
				$bio[$repoName]['SortName'] = $repoName;
			} else {
				$allRepos[$repoName] = $repositories[$repoName];
				$allRepos[$repoName]['RepositoryTemplate'] = true;
				$allRepos[$repoName]['RepoName'] = $repoName;
				$allRepos[$repoName]['SortName'] = $repoName;
			}
		}
	}
	usort($bio,"mySort");
	usort($allRepos,"mySort");
	$allRepos = array_merge($bio,$allRepos);
	if ( $fav )
		array_unshift($allRepos,$fav);
	$file['community'] = $allRepos;
	writeJsonFile($caPaths['repositoriesDisplayed'],$file);
}
?>