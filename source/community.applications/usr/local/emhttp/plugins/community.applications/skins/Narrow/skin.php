<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2020, Andrew Zawadzki #
#          Licenced under the terms of GNU GPLv2              #
#                                                             #
###############################################################

############################################################
#                                                          #
# Routines that actually displays the template containers. #
#                                                          #
############################################################
function display_apps($pageNumber=1,$selectedApps=false,$startup=false) {
	global $caPaths, $caSettings;

	$file = readJsonFile($caPaths['community-templates-displayed']);
	$communityApplications = is_array($file['community']) ? $file['community'] : array();
	$totalApplications = count($communityApplications);

	$display = ( $totalApplications ) ? my_display_apps($communityApplications,$pageNumber,$selectedApps,$startup) : "<div class='ca_NoAppsFound'></div>";

	return $display;
}

# skin specific PHP
#my_display_apps(), getPageNavigation(), displaySearchResults() must accept all parameters
#note that many template entries in my_display_apps() are not actually used in the skin, but are present for future possible use.
function my_display_apps($file,$pageNumber=1,$selectedApps=false,$startup=false) {
	global $caPaths, $caSettings, $plugin, $displayDeprecated, $sortOrder;

	$viewMode = "detail";

	$info = getRunningContainers();

	if ( ! $selectedApps )
		$selectedApps = array();

	$dockerNotEnabled = (! $caSettings['dockerRunning'] && ! $caSettings['NoInstalls']) ? "true" : "false";
		$displayHeader = "<script>addDockerWarning($dockerNotEnabled);var dockerNotEnabled = $dockerNotEnabled;</script>";

	if ( is_file($caPaths['pinned']) )
		convertPinnedAppsToV2();

	$pinnedApps = readJsonFile($caPaths['pinnedV2']);

	$checkedOffApps = arrayEntriesToObject(@array_merge(@array_values($selectedApps['docker']),@array_values($selectedApps['plugin'])));
	if ( filter_var($startup,FILTER_VALIDATE_BOOLEAN) )
		$sortOrder['sortBy'] = "noSort";

	if ( $sortOrder['sortBy'] != "noSort" ) {
		if ( $sortOrder['sortBy'] == "Name" ) $sortOrder['sortBy'] = "SortName";
		usort($file,"mySort");
	}

	$displayHeader .= getPageNavigation($pageNumber,count($file),false)."<br>";

	$columnNumber = 0;
	$appCount = 0;
	$startingApp = ($pageNumber -1) * $caSettings['maxPerPage'] + 1;
	$startingAppCounter = 0;

	$displayedTemplates = array();
	foreach ($file as $template) {
		if ( $template['Blacklist'] && ! $template['NoInstall'] ) continue;

		$startingAppCounter++;
		if ( $startingAppCounter < $startingApp ) continue;
		$displayedTemplates[] = $template;
	}

	$ct .= "<div class='ca_templatesDisplay'>";

	$iconClass = "displayIcon";

	$currentServer = @file_get_contents($caPaths['currentServer']);

	# Create entries for skins.  Note that MANY entries are not used in the current skin
	foreach ($displayedTemplates as $template) {
		if ( $currentServer == "Primary Server" && $template['IconHTTPS'])
			$template['Icon'] = $template['IconHTTPS'];

		$name = $template['SortName'];
		$appName = str_replace(" ","",$template['SortName']);
		$ID = $template['ID'];
		$template['ModeratorComment'] .= $template['CAComment'];
		$selected = $info[$name]['template'];
		$tmpRepo = strpos($template['Repository'],":") ? $template['Repository'] : "{$template['Repository']}:latest";
		if ( ! strpos($tmpRepo,"/") )
			$tmpRepo = "library/$tmpRepo";
		
		$selected = $selected ? ($tmpRepo == $info[$name]['repository']) : false;
		$selected = $template['Uninstall'] ? true : $selected;

		$appType = $template['Plugin'] ? "plugin" : "docker";
		$previousAppName = $template['Plugin'] ? $template['PluginURL'] : $template['Name'];
		$checked = $checkedOffApps[$previousAppName] ? "checked" : "";

		$template['Category'] = categoryList($template['Category']);

		if ( ! $template['DonateText'] )
			$template['DonateText'] = tr("Donate To Author");

		$template['display_Private'] = ( $template['Private'] == "true" ) ? "<span class='ca_tooltip ca_private' title='".tr("Private (dockerHub Conversion)")."'></span>" : "";
		$template['display_DonateLink'] = $template['DonateLink'] ? "<a class='ca_tooltip donateLink' href='{$template['DonateLink']}' target='_blank' title='{$template['DonateText']}'></a>" : "";

		$template['display_faProject'] = $template['Project'] ? "<a class='ca_tooltip ca_fa-project appIcons' target='_blank' href='{$template['Project']}' title='".tr("Go to the project page")."'></a>" : "";
		$template['display_faSupport'] = $template['Support'] ? "<a class='ca_tooltip ca_fa-support appIcons' href='{$template['Support']}' target='_blank' title='".tr("Go to the support thread")."'></a>" : "";
		$template['display_faThumbsUp'] = $template['Recommended'] ? "<span class='ca_thumbsup appIcons'></span>" : "";
		if ( ! $template['Compatible'] ) unset($template['display_faThumbsUp']);

		$template['display_ModeratorComment'] .= $template['ModeratorComment'] ? "</span></strong><font color='purple'>{$template['ModeratorComment']}</font>" : "";

		if ( $pinnedApps["{$template['Repository']}&{$template['SortName']}"] ) {
			$pinned = "pinned";
			$pinnedTitle = tr("Click to unpin this application");
		} else {
			$pinned = "unpinned";
			$pinnedTitle = tr("Click to pin this application");
		}
		$template['display_pinButton'] = "<span class='ca_tooltip $pinned' title='$pinnedTitle' data-repository='{$template['Repository']}' data-name='{$template['SortName']}'></span>";
		if ($template['Blacklist'])
			unset($template['display_pinButton']);

		if ( $template['Uninstall'] && $template['Name'] != "Community Applications" ) {
			$template['display_Uninstall'] = "<a class='ca_tooltip ca_fa-delete' title='".tr("Uninstall Application")."' ";
			$template['display_Uninstall'] .= ( $template['Plugin'] ) ? "onclick='uninstallApp(&quot;{$template['InstallPath']}&quot;,&quot;{$template['Name']}&quot;);'>" : "onclick='uninstallDocker(&quot;{$info[$name]['template']}&quot;,&quot;{$template['Name']}&quot;);'>";
			$template['display_Uninstall'] .= "</a>";
		} else {
			if ( $template['Private'] == "true" )
				$template['display_Uninstall'] = "<a class='ca_tooltip  ca_fa-delete' title='".tr("Remove Private Application")."' onclick='deletePrivateApp(&quot;{$template['Path']}&quot;,&quot;{$template['SortName']}&quot;,&quot;{$template['SortAuthor']}&quot;);'></a>";
		}
		$template['display_removable'] = $template['Removable'] && ! $selected ? "<a class='ca_tooltip ca_fa-delete' title='".tr("Remove Application From List")."' onclick='removeApp(&quot;{$template['InstallPath']}&quot;,&quot;{$template['Name']}&quot;);'></a>" : "";
		if ( $template['display_Uninstall'] && $template['display_removable'] )
			unset($template['display_Uninstall']); # prevent previously installed private apps from having 2 x's in previous apps section

		$template['display_humanDate'] = date("F j, Y",$template['Date']);
		$template['display_multi_install'] = ($template['Removable']) ? "<input class='ca_multiselect ca_tooltip' title='".tr("Check off to select multiple reinstalls")."' type='checkbox' data-name='$previousAppName' data-humanName='{$template['Name']}' data-type='$appType' $checked>" : "";
		if (! $caSettings['dockerRunning'] && ! $template['Plugin'])
			unset($template['display_multi_install']);

		if ( $template['Plugin'] )
			$template['UpdateAvailable'] = checkPluginUpdate($template['PluginURL']);

		if ( ! $template['NoInstall'] && ! $caSettings['NoInstalls'] ){  # certain "special" categories (blacklist, deprecated, etc) don't allow the installation etc icons
			if ( $template['Plugin'] ) {
				$pluginName = basename($template['PluginURL']);
				if ( checkInstalledPlugin($template) ) {
					$pluginSettings = $pluginName == "community.applications.plg" ? "ca_settings" : plugin("launch","/var/log/plugins/$pluginName");
					$tmpVar = $pluginSettings ? "" : " disabled ";
					$template['display_pluginSettingsIcon'] = $pluginSettings ? "<a class='ca_tooltip ca_fa-pluginSettings appIcons' title='".tr("Go to the plugin settings")."' href='/Plugins/Apps/$pluginSettings'></a>" : "";
				} else {
					$template['display_pluginInstallIcon'] = "<a style='cursor:pointer' class='ca_tooltip ca_fa-install appIcons' title='".tr("Install plugin")."' onclick=installPlugin('{$template['PluginURL']}');></a>";
				}
			} else {
				if ( $caSettings['dockerRunning'] ) {
					if ( $selected ) {
						$template['InstallPath'] = $template['InstallPath'] ?: $template['Path'];
						$template['display_dockerDefaultIcon'] = "<a class='ca_tooltip ca_fa-install appIcons xmlInstall' title='".tr("Click to reinstall the application using default values")."' data-type='default' data-xml='".addslashes($template['Path'])."'></a>";
						$template['display_dockerDefaultIcon'] = $template['BranchID'] ? "<a class='ca_tooltip ca_fa-install appIcons' type='button' style='margin:0px' title='".tr("Click to reinstall the application using default values")."' onclick='displayTags(&quot;$ID&quot;);'></a>" : $template['display_dockerDefaultIcon'];
						$template['display_dockerEditIcon']    = "<a class='ca_tooltip appIcons ca_fa-edit xmlInstall' title='".tr("Click to edit the application values")."' data-type='edit' data-xml='".addslashes($info[$name]['template'])."'></a>";
						$template['display_dockerReinstallIcon'] = $caSettings['defaultReinstall'] == "true" ? "<a class='ca_tooltip ca_fa-install appIcons xmlInstall' title='".tr("Click to reinstall")."' data-type='default' data-xml='".addslashes($template['InstallPath'])."'></a>" : "";
						if ( $info[$name]['url'] && $info[$name]['running'] )
							$template['dockerWebIcon'] = "<a class='ca_tooltip appIcons ca_fa-globe' href='{$info[$name]['url']}' target='_blank' title='".tr("Click to go to the WebUI")."'></a>";
					} else {
						if ( $template['InstallPath'] )
							$template['display_dockerReinstallIcon'] = "<a class='ca_tooltip ca_fa-install appIcons xmlInstall' title='".tr("Click to reinstall")."' data-type='user' data-xml='".addslashes($template['InstallPath'])."'></a>";
						else {
							$template['display_dockerInstallIcon'] = "<a class='ca_tooltip ca_fa-install appIcons xmlInstall' title='".tr("Click to install")."' data-type='default' data-xml='".addslashes($template['Path'])."'></a>";
							$template['display_dockerInstallIcon'] = $template['BranchID'] ? "<a style='cursor:pointer' class='ca_tooltip ca_fa-install appIcons' title='".tr("Click to install")."' onclick='displayTags(&quot;$ID&quot;);'></a>" : $template['display_dockerInstallIcon'];
						}
					}
				}
			}
		} else
			$specialCategoryComment = $template['NoInstall'];

		$warningColor = "warning-white";
		if ( $template['Beta'] ) {
			$template['display_compatible'] .= tr("This application has been marked as being Beta")."<br>";
		}
		if ( $template['Deprecated'] ) {
			$template['display_compatible'] .= tr("This application template has been deprecated")."<br>";
			$warningColor = "warning-yellow";
		}
		if ( ! $template['Compatible'] && ! $template['UnknownCompatible'] ) {
			$template['display_compatible'] .= tr("This application is not compatible with your version of unRaid")."<br>";
			$warningColor = "warning-red";
		}
		if ( $template['Blacklist'] ) {
			$template['display_compatible'] .= tr("This application template has been blacklisted")."<br>";
			$warningColor = "warning-red";
		}

		if ( $template['ModeratorComment'] )
			$template['display_warning-text'] = $template['ModeratorComment'];
		if ( $template['Deprecated'] || ! $template['Compatible'] || $template['Blacklist'] || $template['Beta'])
			$template['display_warning-text'] .= $template['display_warning-text'] ? "<br>" : "";
			$template['display_warning-text'] .= "{$template['display_compatible']}";

		$template['display_faWarning'] = $template['display_warning-text'] ? "<span class='ca_tooltip-warning ca_fa-warning appIcons $warningColor' title='".htmlspecialchars($template['display_warning-text'],ENT_COMPAT | ENT_QUOTES)."'></span>" : "";

		$template['display_author'] = "<a class='ca_tooltip ca_author' onclick='doSearch(false,this.innerText);' title='".sprintf(tr("Search for more applications from %s"),$template['SortAuthor'])."'>".$template['Author']."</a>";
		$displayIcon = $template['Icon'];
		$displayIcon = $displayIcon ? $displayIcon : "/plugins/dynamix.docker.manager/images/question.png";
		$template['display_iconSmall'] = "<a onclick='showDesc({$template['ID']},&#39;{$name}&#39;);' style='cursor:pointer'><img class='ca_appPopup $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}' src='$displayIcon'></a>";
		$template['display_iconSelectable'] = "<img class='$iconClass' src='$displayIcon'>";
		$template['display_infoIcon'] = "<a class='ca_appPopup ca_tooltip appIcons ca_fa-info' title='".tr("Click for more information")."' data-appNumber='$ID' data-appPath='{$template['Path']}' data-appName='{$template['Name']}' style='cursor:pointer'></a>";
		if ( isset($ID) ) {
			$template['display_iconClickable'] = "<a class='ca_appPopup' data-appName='{$template['Name']}' data-appNumber='$ID' data-appPath='{$template['Path']}'>".$template['display_iconSelectable']."</a>";
			$template['display_iconSmall'] = "<a class='ca_appPopup' onclick='showDesc({$template['ID']},&#39;".$name."&#39;);'><img class='ca_appPopup $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}' src='".$displayIcon."'></a>";
			$template['display_iconOnly'] = "<img class='$iconClass' src='".$displayIcon."'></img>";
		} else {
			$template['display_iconClickable'] = $template['display_iconSelectable'];
			$template['display_iconSmall'] = "<img src='".$displayIcon."' class='$iconClass'>";
			$template['display_iconOnly'] = $template['display_iconSmall'];
		}
		if ( $template['IconFA'] ) {
			$displayIcon = $template['IconFA'] ?: $template['Icon'];
			$displayIconClass = startsWith($displayIcon,"icon-") ? $displayIcon : "fa fa-$displayIcon";
			$template['display_iconSmall'] = "<a class='ca_appPopup' onclick='showDesc({$template['ID']},&#39;{$name}&#39;);'><div class='ca_center'><i class='ca_appPopup $displayIconClass $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}'></i></div></a>";
			$template['display_iconSelectable'] = "<div class='ca_center'><i class='$displayIconClass $iconClass'></i></div>";
			if ( isset($ID) ) {
				$template['display_iconClickable'] = "<a class='ca_appPopup' data-appName='{$template['Name']}' data-appNumber='$ID' data-appPath='{$template['Path']}' style='cursor:pointer' >".$template['display_iconSelectable']."</a>";
				$template['display_iconSmall'] = "<a class='ca_appPopup' onclick='showDesc({$template['ID']},&#39;{$name}&#39;);'><div class='ca_center'><i class='fa fa-$displayIcon ca_appPopup $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}'></i></div></a>";
				$template['display_iconOnly'] = "<div class='ca_center'><i class='fa fa-$displayIcon $iconClass'></i></div>";
			} else {
				$template['display_iconClickable'] = $template['display_iconSelectable'];
				$template['display_iconSmall'] = "<div class='ca_center'><i class='$displayIconClass $iconClass'></i></div>";
				$template['display_iconOnly'] = $template['display_iconSmall'];
			}
		}
		
		$template['display_dockerName'] = "<span class='ca_applicationName'>";
		$template['display_dockerName'] .= $template['Name_highlighted'] ?: $template['Name'];
		$template['display_dockerName'] .= "</span>";
		
		$template['Category'] = ($template['Category'] == "UNCATEGORIZED") ? tr("Uncategorized") : $template['Category'];

# Entries created.  Now display it
		$ct .= displayCard($template);
		$count++;
		if ( $count == $caSettings['maxPerPage'] ) break;
	}
	$ct .= "</div>";

	$ct .= getPageNavigation($pageNumber,count($file),false,false)."<br><br><br>";

	if ( $specialCategoryComment ) {
		$displayHeader .= "<span class='specialCategory'><div class='ca_center'>".tr("This display is informational ONLY.")."</div><br>";
		$displayHeader .= "<div class='ca_center'>$specialCategoryComment</div></span>";
	}

	if ( ! $count )
		$displayHeader .= "<div class='ca_NoAppsFound'></div>";

	return "$displayHeader$ct";
}

function getPageNavigation($pageNumber,$totalApps,$dockerSearch,$displayCount = true) {
	global $caSettings;

	if ( $caSettings['maxPerPage'] < 0 ) return;
	$swipeScript = "<script>";
	$my_function = $dockerSearch ? "dockerSearch" : "changePage";
	if ( $dockerSearch )
		$caSettings['maxPerPage'] = 25;
	$totalPages = ceil($totalApps / $caSettings['maxPerPage']);

	if ($totalPages == 1) return;

	$startApp = ($pageNumber - 1) * $caSettings['maxPerPage'] + 1;
	$endApp = $pageNumber * $caSettings['maxPerPage'];
	if ( $endApp > $totalApps )
		$endApp = $totalApps;

	$o = "<div class='ca_center'>";
	if ( ! $dockerSearch && $displayCount)
		$o .= "<span class='pageNavigation'>".sprintf(tr("Displaying %s - %s (of %s)"),$startApp,$endApp,$totalApps)."</span><br>";

	$o .= "<div class='pageNavigation'>";
	$previousPage = $pageNumber - 1;
	$o .= ( $pageNumber == 1 ) ? "<span class='pageLeft pageNumber pageNavNoClick'></span>" : "<span class='pageLeft ca_tooltip pageNumber' onclick='{$my_function}(&quot;$previousPage&quot;)'></span>";
	$swipeScript .= "data.prevpage = $previousPage;";
	$startingPage = $pageNumber - 5;
	if ($startingPage < 3 )
		$startingPage = 1;
	else
		$o .= "<a class='ca_tooltip pageNumber' onclick='{$my_function}(&quot;1&quot;);'>1</a><span class='pageNumber pageDots'></span>";

	$endingPage = $pageNumber + 5;
	if ( $endingPage > $totalPages )
		$endingPage = $totalPages;

	for ($i = $startingPage; $i <= $endingPage; $i++)
		$o .= ( $i == $pageNumber ) ? "<span class='pageNumber pageSelected'>$i</span>" : "<a class='ca_tooltip pageNumber' onclick='{$my_function}(&quot;$i&quot;);'>$i</a>";

	if ( $endingPage != $totalPages) {
		if ( ($totalPages - $pageNumber ) > 6)
			$o .= "<span class='pageNumber pageDots'></span>";

		if ( ($totalPages - $pageNumber ) >5 )
			$o .= "<a class='ca_tooltip pageNumber' onclick='{$my_function}(&quot;$totalPages&quot;);'>$totalPages</a>";
	}
	$nextPage = $pageNumber + 1;
	$o .= ( $pageNumber < $totalPages ) ? "<span class='ca_tooltip pageNumber pageRight' onclick='{$my_function}(&quot;$nextPage&quot;);'></span>" : "<span class='pageRight pageNumber pageNavNoClick'></span>";
	$swipeScript .= ( $pageNumber < $totalPages ) ? "data.nextpage = $nextPage;" : "data.nextpage = 0;";
	$swipeScript .= ( $dockerSearch ) ? "dockerSearchFlag = true;" : "dockerSearchFlag = false";
	$swipeScript .= "</script>";
	$o .= "</div></div><script>data.currentpage = $pageNumber;</script>";
	return $o.$swipeScript;
}

########################################################################################
#                                                                                      #
# function used to display the navigation (page up/down buttons) for dockerHub results #
#                                                                                      #
########################################################################################
function dockerNavigate($num_pages, $pageNumber) {
	return getPageNavigation($pageNumber,$num_pages * 25, true);
}

##############################################################
#                                                            #
# function that actually displays the results from dockerHub #
#                                                            #
##############################################################
function displaySearchResults($pageNumber) {
	global $caPaths, $caSettings, $plugin;

	$tempFile = readJsonFile($caPaths['dockerSearchResults']);
	$num_pages = $tempFile['num_pages'];
	$file = $tempFile['results'];
	$templates = readJsonFile($caPaths['community-templates-info']);
	$viewMode = "detail";

	$ct = dockerNavigate($num_pages,$pageNumber)."<br>";
	$ct .= "<div class='ca_templatesDisplay'>";

	$columnNumber = 0;
	foreach ($file as $result) {
		$result['Icon'] = "/plugins/dynamix.docker.manager/images/question.png";
		$result['display_dockerName'] = "<a class='ca_tooltip ca_applicationName' style='cursor:pointer;' onclick='mySearch(this.innerText);' title='".tr("Search for similar containers")."'>{$result['Name']}</a>";
		$result['display_author'] = "<a class='ca_tooltip ca_author' onclick='mySearch(this.innerText);' title='".sprintf(tr("Search For Containers From %s"),$result['Author'])."'>{$result['Author']}</a>";
		$result['Category'] = "Docker Hub Search";
		$result['display_iconClickable'] = "<i class='displayIcon fa fa-docker'></i>";
		$result['Description'] = $result['Description'] ?: "No description present";
		$result['display_faProject'] = "<a class='ca_tooltip ca_fa-project appIcons' title='Go to dockerHub page' target='_blank' href='{$result['DockerHub']}'></a>";
		$result['display_dockerInstallIcon'] = $caSettings['NoInstalls'] ? "" : "<a class='ca_tooltip ca_fa-install appIcons' title='".tr("Click to install")."' onclick='dockerConvert(&#39;".$result['ID']."&#39;);'></a>";
		$ct .= displayCard($result);
		$count++;
	}
	$ct .= "</div>";

	return $ct.dockerNavigate($num_pages,$pageNumber);
}

######################################
# Generate the display for the popup #
######################################
function getPopupDescription($appNumber) {
	global $caSettings, $caPaths;

	require_once "webGui/include/Markdown.php";

	$unRaidVars = parse_ini_file($caPaths['unRaidVars']);
	$dockerVars = parse_ini_file($caPaths['docker_cfg']);
	$caSettings = parse_plugin_cfg("community.applications");
	$csrf_token = $unRaidVars['csrf_token'];
	$tabMode = '_parent';

	if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
		$caSettings['dockerRunning'] = "true";
		$DockerTemplates = new DockerTemplates();
		$DockerClient = new DockerClient();
		$info = $DockerTemplates->getAllInfo();
		$dockerRunning = $DockerClient->getDockerContainers();
	} else {
		unset($caSettings['dockerRunning']);
		$info = array();
		$dockerRunning = array();
	}
	if ( ! is_file($caPaths['warningAccepted']) )
		$caSettings['NoInstalls'] = true;

	# $appNumber is actually the path to the template.  It's pretty much always going to be the same even if the database is out of sync.
	$displayed = readJsonFile($caPaths['community-templates-displayed']);
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
		$file = readJsonFile($caPaths['community-templates-info']);
		$index = searchArray($file,"Path",$appNumber);

		if ( $index === false ) {
			echo json_encode(array("description"=>tr("Something really wrong happened.  Reloading the Apps tab will probably fix the problem")));
			return;
		}
		$template = $file[$index];
		$Displayed = false;
	}
	$currentServer = file_get_contents($caPaths['currentServer']);

	# Create entries for skins.  Note that MANY entries are not used in the current skins
	if ( $currentServer == "Primary Server" && $template['IconHTTPS'])
		$template['Icon'] = $template['IconHTTPS'];

	$ID = $template['ID'];

	$donatelink = $template['DonateLink'];
	if ( $donatelink ) {
		$donatetext = $template['DonateText'];
		if ( ! $donatetext )
			$donatetext = $template['Plugin'] ? tr("Donate To Author") : tr("Donate To Maintainer");
	}

	if ( ! $template['Plugin'] ) {
		if ( ! strpos($template['Repository'],"/") ) {
			$template['Repository'] = "library/{$template['Repository']}";
		}
		foreach ($dockerRunning as $testDocker) {
			$templateRepo = explode(":",$template['Repository']);
			$testRepo = explode(":",$testDocker['Image']);
			if ($templateRepo[0] == $testRepo[0]) {
				$selected = true;
				$name = $testDocker['Name'];
				break;
			}
		}
	} else
		$pluginName = basename($template['PluginURL']);

	if ( $template['trending'] ) {
		$allApps = readJsonFile($caPaths['community-templates-info']);

		$allTrends = array_unique(array_column($allApps,"trending"));
		rsort($allTrends);
		$trendRank = array_search($template['trending'],$allTrends) + 1;
	}

	$template['Category'] = categoryList($template['Category'],true);
	$template['Icon'] = $template['Icon'] ? $template['Icon'] : "/plugins/dynamix.docker.manager/images/question.png";
	$template['Description'] = trim($template['Description']);
	$template['ModeratorComment'] .= $template['CAComment'];

	if ( $template['Plugin'] ) {
		download_url($template['PluginURL'],$caPaths['pluginTempDownload']);
		$template['Changes'] = @plugin("changes",$caPaths['pluginTempDownload']);
		$template['pluginVersion'] = @plugin("version",$caPaths['pluginTempDownload']) ?: $template['pluginVersion'];
	} else {
		download_url($template['TemplateURL'],$caPaths['pluginTempDownload']);
		$xml = readXmlFile($caPaths['pluginTempDownload']);
		$template['Changes'] = $xml['Changes'];
	}

	$templateDescription .= "<div style='width:60px;height:60px;display:inline-block;position:absolute;'>";
	if ( $template['IconFA'] ) {
		$template['IconFA'] = $template['IconFA'] ?: $template['Icon'];
		$templateIcon = startsWith($template['IconFA'],"icon-") ? $template['IconFA'] : "fa fa-{$template['IconFA']}";
		$templateDescription .= "<i class='$templateIcon popupIcon ca_center' id='icon'></i>";
	} else
		$templateDescription .= "<img class='popupIcon' id='icon' src='{$template['Icon']}'>";

	if ( $template['Beta'] )
		$templateDescription .= "<br><br><img src='https://github.com/Squidly271/community.applications/raw/master/webImages/beta.png' style='height:2.5rem;z-index:-2;margin-left:1.25rem;'></img>";
	$templateDescription .= "</div><div style='display:inline-block;margin-left:105px;'>";
	$templateDescription .= $template['Plugin'] ? "<table class='popupTableAreaPlugin'>" : "<table class='popupTableAreaDocker'>";
	$author = $template['PluginURL'] ? $template['PluginAuthor'] : $template['SortAuthor'];
	$author .= $template['Recommended'] ? "&nbsp;&nbsp;<span class='ca_thumbsup' style='cursor:default;'></span>" : "";
	$templateDescription .= "<tr><td style='width:25%;'>".tr("Author:")."</td><td>$author</a></td></tr>";
	if ( ! $template['Plugin'] ) {
		$templateDescription .= "<tr><td>".tr("DockerHub:")."</td><td><a class='popUpLink' href='{$template['Registry']}' target='_blank'>{$template['Repository']}</a></td></tr>";
	}
	$templateDescription .= "<tr><td>".tr("Repository:")."</td><td>";
	$repoSearch = explode("'",$template['RepoName']);
	$templateDescription .= "{$template['RepoName']}</a>";
	if ( $template['Profile'] ) {
		$profileDescription = $template['Plugin'] ? tr("Author") : tr("Maintainer");
		$templateDescription .= "<span>&nbsp;&nbsp;<a class='popUpLink' href='{$template['Profile']}' target='_blank'>$profileDescription Profile</a></span>";
	}
	$templateDescription .= "</td></tr>";
	$templateDescription .= ($template['Private'] == "true") ? "<tr><td></td><td><font color=red>Private Repository</font></td></tr>" : "";
	$templateDescription .= ( $dockerVars['DOCKER_AUTHORING_MODE'] == "yes"  && $template['TemplateURL']) ? "<tr><td></td><td><a class='popUpLink' href='{$template['TemplateURL']}' target='_blank'>".tr("Application Template")."</a></td></tr>" : "";
	if ( $template['Category'] ) {
		$templateDescription .= "<tr><td>".tr("Categories:")."</td><td>".$template['Category'];
		$templateDescription .= "</td></tr>";
	}
	if ( filter_var($template['multiLanguage'],FILTER_VALIDATE_BOOLEAN) ) 
		$templateDescription .= "<tr><td>".tr("Multi Language Support")."</td><td>Yes</td></tr>";
	if ( ! $template['Plugin'] ) {
		if ( strtolower($template['Base']) == "unknown" || ! $template['Base'])
			$template['Base'] = $template['BaseImage'];

		if ( $template['Base'] )
			$templateDescription .= "<tr><td nowrap>".tr("Base OS:")."</td><td>".$template['Base']."</td></tr>";
	}
	$templateDescription .= $template['stars'] ? "<tr><td nowrap>".tr("DockerHub Stars:")."</td><td><span class='dockerHubStar'></span> ".$template['stars']."</td></tr>" : "";

	if ( $template['FirstSeen'] > 1 && $template['Name'] != "Community Applications" )
		$templateDescription .= "<tr><td>".tr("Added to CA:")."</td><td>".my_lang(date("F",$template['FirstSeen']),0).date(" j, Y",$template['FirstSeen'])."</td></tr>";

	# In this day and age with auto-updating apps, NO ONE keeps up to date with the date updated.  Remove from docker containers to avoid confusion
	if ( $template['Date'] && $template['Plugin'] ) {
		$niceDate = my_lang(date("F",$template['Date']),0).date(" j, Y",$template['Date']);
		$templateDescription .= "<tr><td nowrap>".tr("Date Updated:")."</td><td>$niceDate</td></tr>";
	}
	if ( $template['Plugin'] ) {
		$template['pluginVersion'] = $template['pluginVersion'] ?: tr("unknown");
		$templateDescription .= "<tr><td nowrap>".tr("Current Version:")."</td><td>{$template['pluginVersion']}</td></tr>";
	}
	$unraidVersion = parse_ini_file($caPaths['unRaidVersion']);
	$templateDescription .= ( $template['MinVer'] > "6.4.0" ) ? "<tr><td nowrap>".tr("Minimum OS:")."</td><td>unRaid v".$template['MinVer']."</td></tr>" : "";

	$template['MaxVer'] = $template['MaxVer'] ?: $template['DeprecatedMaxVer'];
	$templateDescription .= $template['MaxVer'] ? "<tr><td nowrap>".tr("Max OS:")."</td><td>unRaid v".$template['MaxVer']."</td></tr>" : "";
	$downloads = getDownloads($template['downloads']);
	if ($downloads)
		$templateDescription .= "<tr><td>".tr("Total Downloads:")."</td><td>$downloads</td></tr>";

	$templateDescription .= $template['Licence'] ? "<tr><td>".tr("Licence:")."</td><td>".$template['Licence']."</td></tr>" : "";
	if ( $template['trending'] ) {
		$templateDescription .= "<tr><td>".tr("30 Day Trend:")."</td><td>".sprintf(tr("Ranked #%s"),$trendRank);
		if (is_array($template['trends']) && (count($template['trends']) > 1) ){
			$templateDescription .= "  ".sprintf(tr("Trending %s"), (end($template['trends']) > $template['trends'][count($template['trends'])-2]) ? " <span class='trendingUp'></span>" : " <span class='trendingDown'></span>");
		}
		$templateDescription .= "<tr><td></td><td>".sprintf(tr("(As of %s)"),my_lang(date("F",$template['LastUpdateScan'])).date(" j, Y  g:i a",$template['LastUpdateScan']),0)."</td></tr>";
		$template['description'] .= "</td></tr>";
	}
	$templateDescription .= "</table></div>";

	$templateDescription .= "<div class='ca_center'><span class='popUpDeprecated'>";
	if ($template['Blacklist'])
		$templateDescription .= tr("This application / template has been blacklisted")."<br>";

	if ($template['Deprecated'])
		$templateDescription .= tr("This application / template has been deprecated")."<br>";

	if ( !$template['Compatible'] )
		$templateDescription .= tr("This application is not compatible with your version of unRaid")."<br>";

	$templateDescription .= "</span></div><hr>";

	if ( ! $Displayed )
		$templateDescription .= "<div><span class='ca_fa-warning warning-yellow'></span>&nbsp; <font size='1'>".tr("Another browser tab or device has updated the displayed templates.  Some actions are not available")."</font></div>";

	if ( $Displayed && ! $template['NoInstall'] && ! $caSettings['NoInstalls']) {
		if ( ! $template['Plugin'] ) {
			if ( $caSettings['dockerRunning'] ) {
				if ( $selected ) {
					$installLine .= $caSettings['defaultReinstall'] == "true" ? "<a class='appIconsPopUp ca_fa-install xmlInstall' onclick='xmlInstall(&quot;default&quot;,&quot;".addslashes($template['Path'])."&quot;);'>&nbsp;&nbsp;".tr("Reinstall (default)")."</a>" : "";
					$installLine .= "<a class='appIconsPopUp ca_fa-edit' onclick='xmlInstall(&quot;edit&quot;,&quot;".addslashes($info[$name]['template'])."&quot;);'>&nbsp;&nbsp;".tr("Edit")."</a>";
					if ( $info[$name]['url'] && $info[$name]['running'] ) {
						$installLine .= "<a class='appIconsPopUp ca_fa-globe' href='{$info[$name]['url']}' target='_blank'>&nbsp;&nbsp;".tr("WebUI")."</a>";
					}
				} else {
					if ( $template['InstallPath'] )
						$installLine .= "<a class='appIconsPopUp ca_fa-install' onclick='xmlInstall(&quot;user&quot;,&quot;".addslashes($template['InstallPath'])."&quot;);'>&nbsp;&nbsp;".tr("Reinstall")."</a>";
					else {
						$install = "<a class='appIconsPopUp ca_fa-install' onclick='xmlInstall(&quot;default&quot;,&quot;".addslashes($template['Path'])."&quot;);'>&nbsp;&nbsp;".tr("Install")."</a>";
						$installLine .= $template['BranchID'] ? "<a style='cursor:pointer' class='appIconsPopUp ca_fa-install' onclick='$(&quot;#branch&quot;).show(500);'>&nbsp;&nbsp;".tr("Install")."</a>" : $install;
					}
				}
			}
		} else {
			if ( file_exists("/var/log/plugins/$pluginName") ) {
				$pluginSettings = $pluginName == "community.applications.plg" ? "ca_settings" : plugin("launch","/var/log/plugins/$pluginName");
				if ( $pluginSettings )
					$installLine .= "<a class='appIconsPopUp ca_fa-pluginSettings' href='/Plugins/Apps/$pluginSettings' target='$tabMode'>&nbsp;&nbsp;".tr("Settings")."</a>";
			} else {
				$buttonTitle = $template['InstallPath'] ? tr("Reinstall") : tr("Install");
				$installLine .= "<a style='cursor:pointer' class='appIconsPopUp ca_fa-install pluginInstall' onclick=installPlugin('".$template['PluginURL']."');>&nbsp;&nbsp;$buttonTitle</a>";
			}
		}
	}
	if ( $template['Support'] || $template['Project'] ) {
		$installLine .= "<span style='float:right;'>";
		$installLine .= $template['Support'] ? "<a class='appIconsPopUp ca_fa-support' href='".$template['Support']."' target='_blank'>&nbsp;&nbsp;".tr("Support")."</strong></a>" : "";
		$installLine .= $template['Project'] ? "<a class='appIconsPopUp ca_fa-project' href='".$template['Project']."' target='_blank'>&nbsp;&nbsp;".tr("Project")."</strong></a>" : "";
		$installLine .= "</span>";
	}
	if ( $installLine ) {
		$templateDescription .= "$installLine<br>";
		if ($template['BranchID']) {
			$templateDescription .= "<span id='branch' style='display:none;'>";
			$templateDescription .= formatTags($template['ID'],"popup");
			$templateDescription .= "</span>";
		}
		$templateDescription .= "<hr>";
	}
	$templateDescription .= strip_tags($template['Description']);
	$templateDescription .= $template['ModeratorComment'] ? "<br><br><span class='ca_bold'><font color='red'>".tr("Moderator Comments:")."</font></span> ".$template['ModeratorComment'] : "";
	$templateDescription .= "</p><br><div class='ca_center'>";

	if ( $donatelink )
		$templateDescription .= "<span style='float:right;text-align:right;'><font size=0.75rem;>$donatetext</font>&nbsp;&nbsp;<a class='popup-donate donateLink' href='$donatelink' target='_blank'>".tr("Donate")."</a></span><br><br>";

	$templateDescription .= "</div>";
	if ($template['Plugin']) {
		$dupeList = readJsonFile($caPaths['pluginDupes']);
		if ( $dupeList[basename($template['Repository'])] == 1 ){
			$allTemplates = readJsonFile($caPaths['community-templates-info']);
			foreach ($allTemplates as $testTemplate) {
				if ($testTemplate['Repository'] == $template['Repository']) continue;

				if ($testTemplate['Plugin'] && (basename($testTemplate['Repository']) == basename($template['Repository'])))
					$duplicated .= $testTemplate['Author']." - ".$testTemplate['Name'];
			}
			$templateDescription .= "<br>".sprintf(tr("This plugin has a duplicated name from another plugin %s.  This will impact your ability to install both plugins simultaneously"),$duplicated)."<br>";
		}
	}
	if (is_array($template['trends']) && (count($template['trends']) > 1) ){
		if ( $template['downloadtrend'] ) {
			$templateDescription .= "<div><canvas id='trendChart' class='caChart' height=1 width=3></canvas></div>";
			$templateDescription .= "<div><canvas id='downloadChart' class='caChart' height=1 width=3></canvas></div>";
			$templateDescription .= "<div><canvas id='totalDownloadChart' class='caChart' height=1 width=3></canvas></div>";
		}
	}

	$changeLogMessage = "Note: not all ";
	$changeLogMessage .= $template['PluginURL'] ? "authors" : "maintainers";
	$changeLogMessage .= " keep up to date on change logs</font></div><br>";
	$changeLogMessage = "<div class='ca_center'><font size='0'>".tr($changeLogMessage)."</font></div><br>";
	if ( trim($template['Changes']) ) {
		if ( $appNumber != "ca" && $appNumber != "ca_update" )
			$templateDescription .= "</div>";

		if ( $template['Plugin'] ) {
			if ( file_exists("/var/log/plugins/$pluginName") ) {
				$appInformation = tr("Currently Installed Version:")." ".plugin("version","/var/log/plugins/$pluginName");
				if ( plugin("version","/var/log/plugins/$pluginName") != plugin("version",$caPaths['pluginTempDownload']) ) {
					copy($caPaths['pluginTempDownload'],"/tmp/plugins/$pluginName");
					$appInformation .= " - <span class='ca_bold'><a href='/Plugins/Apps/Plugins' target='_parent'>".tr("Install The Update")."</a></span>";
				} else
					$appInformation .= " - <font color='green'>".tr("Latest Version")."</font>";
			}
			$appInformation .= Markdown($template['Changes']);
		} else {
			$appInformation = $template['Changes'];
			$appInformation = str_replace("\n","<br>",$appInformation);
			$appInformation = str_replace("[","<",$appInformation);
			$appInformation = str_replace("]",">",$appInformation);
		}
		$templateDescription .= "<div class='ca_center'><br><font size='4'><span class='ca_bold'>".tr("Change Log")."</span></div></font><br>$changeLogMessage$appInformation";
	}

	if (is_array($template['trendsDate']) ) {
		array_walk($template['trendsDate'],function(&$entry) {
			$entry = my_lang(date("M",$entry),0).date(" j",$entry);
		});
	}

	if ( is_array($template['trends']) ) {
		if ( count($template['trends']) < count($template['downloadtrend']) )
			array_shift($template['downloadtrend']);

		$chartLabel = $template['trendsDate'];
		if ( is_array($template['downloadtrend']) ) {
			#get what the previous download value would have been based upon the trend
			$minDownload = intval(  ((100 - $template['trends'][0]) / 100)  * ($template['downloadtrend'][0]) );
			foreach ($template['downloadtrend'] as $download) {
				$totalDown[] = $download;
				$down[] = intval($download - $minDownload);
				$minDownload = $download;
			}
			$downloadLabel = $template['trendsDate'];
		}
		$down = is_array($down) ? $down : array();
	}

	@unlink($caPaths['pluginTempDownload']);
	return array("description"=>$templateDescription,"trendData"=>$template['trends'],"trendLabel"=>$chartLabel,"downloadtrend"=>$down,"downloadLabel"=>$downloadLabel,"totaldown"=>$totalDown,"totaldownLabel"=>$downloadLabel);
}

###########################
# Generate the app's card #
###########################
function displayCard($template) {
	global $ca_settings;

	$appName = str_replace("-"," ",$template['display_dockerName']);
	$dockerReinstall = $ca_Settings['defaultReinstall'] == "true" ? $template['display_dockerDefaultIcon'] : "";
	$holder = $template['Plugin'] ? "ca_holderPlugin" : "ca_holderDocker";

	$card = "
		<div class='$holder'>
			<div class='ca_iconArea'>
				<div class='ca_icon'>
					{$template['display_iconClickable']}
				</div>
				<div class='ca_infoArea'>
					<div class='ca_applicationInfo'>
						<span class='ca_applicationName'>
							$appName   {$template['display_faWarning']}
						</span>
						{$template['display_Private']}
						<br>
						<span class='ca_author'>
							{$template['display_author']}
						</span>
						<br>
						<span class='ca_categories'>
							{$template['Category']}
						</span>
					</div>
				</div>
			</div>
			<div class='ca_hr'></div>
			<div class='ca_bottomLine'>
				{$template['display_multi_install']}{$template['display_pluginInstallIcon']} {$template['display_dockerInstallIcon']} $dockerReinstall {$template['display_dockerReinstallIcon']} {$template['display_dockerEditIcon']} {$template['display_pluginSettingsIcon']}{$template['display_infoIcon']} {$template['dockerWebIcon']} {$template['display_faSupport']} {$template['display_faThumbsUp']} {$template['display_faProject']} {$template['display_pinButton']} &nbsp;&nbsp; {$template['display_removable']} {$template['display_Uninstall']}
				<span class='ca_bottomRight'>
					{$template['display_DonateImage']}
				</span>
			</div>
			<div class='ca_descriptionArea'>
				{$template['CardDescription']}
			</div>
		</div>
		";

	return $card;
}

?>