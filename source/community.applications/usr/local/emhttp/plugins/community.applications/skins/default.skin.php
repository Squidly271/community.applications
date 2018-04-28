<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2018, Andrew Zawadzki #
#                                                             #
###############################################################

############################################################
#                                                          #
# Routines that actually displays the template containers. #
#                                                          #
############################################################
function display_apps($viewMode,$pageNumber=1,$selectedApps=false) {
	global $communityPaths, $separateOfficial, $officialRepo, $communitySettings;

	$file = readJsonFile($communityPaths['community-templates-displayed']);
	$officialApplications = is_array($file['official']) ? $file['official'] : array();
	$communityApplications = is_array($file['community']) ? $file['community'] : array();
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

# skin specific PHP
#my_display_apps(), getPageNavigation(), displaySearchResults() must accept all parameters
#note that many template entries in my_display_apps() are not actually used in the skin, but are present for future possible use.
function my_display_apps($viewMode,$file,$pageNumber=1,$officialFlag=false,$selectedApps=false) {
	global $communityPaths, $communitySettings, $plugin, $displayDeprecated;
	
	$fontAwesomeInstall = "<i class='appIcons fa fa-download' aria-hidden='true'></i>";
	$fontAwesomeEdit = "<i class='appIcons fa fa-edit' aria-hidden='true'></i>";
	$fontAwesomeGUI = "<i class='appIcons fa fa-globe' aria-hidden='true'></i>";
	$fontAwesomeUpdate = "<i class='appIcons fa fa-refresh' aria-hidden='true'></i>";
	$fontAwesomeDelete = "<i class='fa fa-window-close' aria-hidden='true' style='color:maroon; font-size:20px;cursor:pointer;'></i>";
	
	if ( $communitySettings['dockerRunning'] ) {
		$DockerTemplates = new DockerTemplates();
		$info = $DockerTemplates->getAllInfo();
	} else {
		$info = array();
	}
	$skin = readJsonFile($communityPaths['defaultSkin']);

	if ( ! $selectedApps ) {
		$selectedApps = array();
	}


	$pinnedApps = getPinnedApps();
	$iconSize = $communitySettings['iconSize'];
	$checkedOffApps = arrayEntriesToObject(@array_merge(@array_values($selectedApps['docker']),@array_values($selectedApps['plugin'])));
	usort($file,"mySort");


	if ( ! $officialFlag ) {
		$ct = "<br>".getPageNavigation($pageNumber,count($file),false)."<br>";
	}
	$specialCategoryComment = @file_get_contents($communityPaths['dontAllowInstalls']);
	if ( $specialCategoryComment ) {
		$ct .= "<center><font size='2' color='green'>This display is informational <em>ONLY</em>. Installations, edits, etc are not possible on this screen, and you must navigate to the appropriate settings and section / category</font></center><br>";
		$ct .= "<center><font size='2' color='green'>$specialCategoryComment</font></center>";
	}
	$columnNumber = 0;
	$appCount = 0;
	$startingApp = $officialFlag ? 1 : ($pageNumber -1) * $communitySettings['maxPerPage'] + 1;
	$startingAppCounter = 0;
	
	foreach ($file as $template) {
		if ( $template['Blacklist'] && ! is_file($communityPaths['dontAllowInstalls']) ) {
			continue;
		}
		$startingAppCounter++;
		if ( $startingAppCounter < $startingApp ) {
			continue;
		}
		$displayedTemplates[] = $template;
	}
	$maxColumnDisplayed = count($displayedTemplates) >= $communitySettings['maxDetailColumns'] ? $communitySettings['maxDetailColumns'] : count($displayedTemplates);
  $leftMargin = ($communitySettings['windowWidth'] - $maxColumnDisplayed*$skin[$viewMode]['templateWidth']) / 2;
	$leftMargin = $leftMargin < 0 ? 0 : intval($leftMargin); # safety precaution if something messes up
	$leftMargin = $communitySettings['windowWidth'] <= 1080 ? 0 : $leftMargin; # minimum window with supported by Dynamix

	$templateFormatArray = array(1 => $communitySettings['windowWidth'],2=>$leftMargin);      # this array is only used on header, sol, eol, footer
	$ct .= vsprintf($skin[$viewMode]['header'],$templateFormatArray);
	$displayTemplate = $skin[$viewMode]['template'];
	$iconClass = ( $viewMode == "detail" ) ? "displayIcon" : "displayIconTable";

# Create entries for skins.  Note that MANY entries are not used in the current skins
	foreach ($displayedTemplates as $template) {
		if ( $columnNumber == 0 ) {
			$ct .= vsprintf($skin[$viewMode]['sol'],$templateFormatArray);
		}
		
		$name = $template['SortName'];
		$appName = str_replace(" ","",$template['SortName']);
		$t = "";
		$ID = $template['ID'];
		$selected = $info[$name]['template'] && stripos($info[$name]['icon'], $template['SortAuthor']) !== false;
		$selected = $template['Uninstall'] ? true : $selected;

		$appType = $template['Plugin'] ? "plugin" : "docker";
		$previousAppName = $template['Plugin'] ? $template['PluginURL'] : $template['Name'];
		$checked = $checkedOffApps[$previousAppName] ? "checked" : "";

		$template['Category'] = rtrim(str_replace(":,",",",implode(", ",explode(" ",$template['Category']))),": ,");
		$RepoName = ( $template['Private'] == "true" ) ? $template['RepoName']."<font color=red> (Private)</font>" : $template['RepoName'];
		if ( ! $template['DonateText'] ) {
			$template['DonateText'] = "Donate To Author";
		}
		$template['display_DonateLink'] = $template['DonateLink'] ? "<font size='0'><a class='ca_tooltip' href='".$template['DonateLink']."' target='_blank' title='".$template['DonateText']."'>Donate To Author</a></font>" : "";
		$template['display_Project'] = $template['Project'] ? "<a class='ca_tooltip' target='_blank' title='Click to go the the Project Home Page' href='".$template['Project']."'><font color=red>Project Home Page</font></a>" : "";
		$template['display_Support'] = $template['Support'] ? "<a class='ca_tooltip' href='".$template['Support']."' target='_blank' title='Click to go to the support thread'><font color=red>Support Thread</font></a>" : "";
		$template['display_webPage'] = $template['WebPageURL'] ? "<a class='ca_tooltip' title='Click to go to {$template['SortAuthor']}&#39;s web page' href='".$template['WebPageURL']."' target='_blank'><font color='red'>Web Page</font></a></font>" : "";

		if ( $template['display_Support'] && $template['display_Project'] ) {
			$template['display_Project'] = "&nbsp;&nbsp;&nbsp".$template['display_Project'];
		}
		if ( $template['display_webPage'] && ( $template['display_Project'] || $template['display_Support'] ) ) {
			$template['display_webPage'] = "&nbsp;&nbsp;&nbsp;".$template['display_webPage'];
		}
		if ( $template['UpdateAvailable'] ) {
			$template['display_UpdateAvailable'] = $template['Plugin'] ? "<br><center><font color='red'><b>Update Available.  Click <a onclick='installPLGupdate(&quot;".basename($template['MyPath'])."&quot;,&quot;".$template['Name']."&quot;);' style='cursor:pointer'>Here</a> to Install</b> <i class='ca_infoPopup fa fa-info-circle' data-app='".basename($template['MyPath'])."' data-name='{$template['Name']}' style='cursor:pointer;font-size:15px;color:#486DBA;'></i></center></font>" : "<br><center><font color='red'><b>Update Available.  Click <a href='Docker'>Here</a> to install</b></font></center>";
		}
		if ( $template['Deprecated'] ) {
			$template['ModeratorComment'] .= "<br>This application has been deprecated.";
		}
		$template['display_ModeratorComment'] .= $template['ModeratorComment'] ? "</b></strong><font color='red'><b>Moderator Comments:</b></font> <font color='purple'>".$template['ModeratorComment']."</font>" : "";
		$tempLogo = $template['Logo'] ? "<img src='".$template['Logo']."' height=20px>" : "";
		$template['display_Repository'] = "$RepoName $tempLogo";
		$template['display_Stars'] = $template['stars'] ? "<i class='fa fa-star' style='font-size:15px; color:magenta;' aria-hidden='true'></i> <strong>".$template['stars']."</strong>" : "";
		$template['display_Downloads'] = $template['downloads'] ? "<center>".number_format($template['downloads'])."</center>" : "<center>Not Available</center>";

		if ( $pinnedApps[$template['Repository']] ) {
			$pinned = "pinned";
			$pinnedTitle = "Click to unpin this application";
		} else {
			$pinned = "unpinned";
			$pinnedTitle = "Click to pin this application";
		}
		$template['display_pinButton'] = "<i class='ca_tooltip fa fa-thumb-tack $pinned' title='$pinnedTitle' onclick='pinApp(this,&quot;".$template['Repository']."&quot;);' aria-hidden='true'></i>";
		if ( $template['Uninstall'] ) {
			$template['display_Uninstall'] = "<a class='ca_tooltip' title='Uninstall Application' ";
			$template['display_Uninstall'] .= ( $template['Plugin'] ) ? "onclick='uninstallApp(&quot;".$template['MyPath']."&quot;,&quot;".$template['Name']."&quot;);'>" :	"onclick='uninstallDocker(&quot;".$template['MyPath']."&quot;,&quot;".$template['Name']."&quot;);'>";
			$template['display_Uninstall'] .= "$fontAwesomeDelete</a>";
		} else {
			if ( $template['Private'] == "true" ) {
				$template['display_Uninstall'] = "<a class='ca_tooltip' title='Remove Private Application' onclick='deletePrivateApp(&quot;{$template['Path']}&quot;,&quot;{$template['SortName']}&quot;,&quot;{$template['SortAuthor']}&quot;);'>$fontAwesomeDelete</a>";
			}
		}
		$template['display_removable'] = $template['Removable'] ? "<a class='ca_tooltip' title='Remove Application From List' onclick='removeApp(&quot;".$template['MyPath']."&quot;,&quot;".$template['Name']."&quot;);'>$fontAwesomeDelete</a>" : "";
		if ( $template['display_Uninstall'] && $template['display_removable'] ) {
			unset($template['display_Uninstall']); # prevent previously installed private apps from having 2 x's in previous apps section
		}
		if ( $template['Date'] > strtotime($communitySettings['timeNew'] ) ) {
			$template['display_newIcon'] = "<i class='fa fa-star ca_tooltip' style='font-size:15px;color:yellow;' title='New / Updated - ".date("F d Y",$template['Date'])."'></i>&nbsp;";
		}
		$template['display_humanDate'] = date("F j, Y",$template['Date']);
		$template['display_dateUpdated'] = ($template['Date'] && is_file($communityPaths['newFlag']) ) ? "</b></strong><center><strong>Date Updated: </strong>".$template['display_humanDate']."</center>" : "";
		$template['display_multi_install'] = ($template['Removable']) ? "<input class='ca_multiselect ca_tooltip' title='Check-off to select multiple reinstalls' type='checkbox' data-name='$previousAppName' data-type='$appType' $checked>" : "";
		if (! $communitySettings['dockerRunning'] && ! $template['Plugin']) {
			unset($template['display_multi_install']);
		}
		if ( ! is_file($communityPaths['dontAllowInstalls']) ){  # certain "special" categories (blacklist, deprecated, etc) don't allow the installation etc icons
			if ( $template['Plugin'] ) {
				$pluginName = basename($template['PluginURL']);
				if ( checkInstalledPlugin($template) ) {
					$pluginSettings = plugin("launch","/var/log/plugins/$pluginName");
					$tmpVar = $pluginSettings ? "" : " disabled ";
					$template['display_pluginSettings'] = "<input class='ca_tooltip' title='Click to go to the plugin settings' type='submit' $tmpVar style='margin:0px' value='Settings' formtarget='_self' formaction='$pluginSettings' formmethod='post'>";
					$template['display_pluginSettingsIcon'] = $pluginSettings ? "<a class='ca_tooltip' title='Click to go to the plugin settings' href='$pluginSettings'>$fontAwesomeGUI</a>&nbsp;" : "";
				} else {
					$buttonTitle = $template['MyPath'] ? "Reinstall Plugin" : "Install Plugin";
					$template['display_pluginInstall'] = "<input class='ca_tooltip' type='button' value='$buttonTitle' style='margin:0px' title='Click to install this plugin' onclick=installPlugin('".$template['PluginURL']."');>";
					$template['display_pluginInstallIcon'] = "<a style='cursor:pointer' class='ca_tooltip' title='Click to install this plugin' onclick=installPlugin('".$template['PluginURL']."');>$fontAwesomeInstall</a>&nbsp;";
				}
			} else {
				if ( $communitySettings['dockerRunning'] ) {
					if ( $selected ) {
						$template['display_dockerDefault']     = "<input class='ca_tooltip' type='submit' value='Default' style='margin:1px' title='Click to reinstall the application using default values' formtarget='_self' formmethod='post' formaction='Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."'>";
						$template['display_dockerEdit']        = "<input class='ca_tooltip' type='submit' value='Edit' style='margin:1px' title='Click to edit the application values' formtarget='_self' formmethod='post' formaction='Apps/UpdateContainer?xmlTemplate=edit:".addslashes($info[$name]['template'])."'>";
						$template['display_dockerDefault']     = $template['BranchID'] ? "<input class='ca_tooltip' type='button' style='margin:0px' title='Click to reinstall the application using default values' value='Add' onclick='displayTags(&quot;$ID&quot;);'>" : $template['display_dockerDefault'];
						$template['display_dockerDefaultIcon'] = "<a class='ca_tooltip' title='Click to reinstall the application using default values' href='Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='_self'>$fontAwesomeInstall</a>&nbsp;";
						$template['display_dockerDefaultIcon'] = $template['BranchID'] ? "<a class='ca_tooltip' type='button' style='margin:0px' title='Click to reinstall the application using default values' onclick='displayTags(&quot;$ID&quot;);'>$fontAwesomeInstall</a>&nbsp;" : $template['display_dockerDefaultIcon'];
						$template['display_dockerEditIcon']    = "<a class='ca_tooltip' title='Click to edit the application values' href='Apps/UpdateContainer?xmlTemplate=edit:".addslashes($info[$name]['template'])."' target='_self'>$fontAwesomeEdit</a>&nbsp;";
						if ( $info[$name]['url'] && $info[$name]['running'] ) {
							$template['dockerWebIcon'] = "<a class='ca_tooltip' href='{$info[$name]['url']}' target='_blank' title='Click To Go To The App&#39;s UI'>$fontAwesomeGUI</a>&nbsp;&nbsp;";
						}
					} else {
						if ( $template['MyPath'] ) {
							$template['display_dockerReinstall'] = "<input class='ca_tooltip' type='submit' style='margin:0px' title='Click to reinstall the application' value='Reinstall' formtarget='_self' formmethod='post' formaction='Apps/AddContainer?xmlTemplate=user:".addslashes($template['MyPath'])."'>";
							$template['display_dockerReinstallIcon'] = "<a class='ca_tooltip' title='Click to reinstall' href='Apps/UpdateContainer?xmlTemplate=user:".addslashes($template['MyPath'])."' target='_self'>$fontAwesomeInstall</a>&nbsp;";
							} else {
							$template['display_dockerInstall']   = "<input class='ca_tooltip' type='submit' style='margin:0px' title='Click to install the application' value='Add' formtarget='_self' formmethod='post' formaction='Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."'>";
							$template['display_dockerInstall']   = $template['BranchID'] ? "<input class='ca_tooltip' type='button' style='margin:0px' title='Click to install the application' value='Add' onclick='displayTags(&quot;$ID&quot;);'>" : $template['display_dockerInstall'];
							$template['display_dockerInstallIcon'] = "<a class='ca_tooltip' title='Click to install' href='Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='_self'>$fontAwesomeInstall</a>&nbsp;";
							$template['display_dockerInstallIcon'] = $template['BranchID'] ? "<a style='cursor:pointer' class='ca_tooltip' title='Click to install the application' onclick='displayTags(&quot;$ID&quot;);'>$fontAwesomeInstall</a>&nbsp;" : $template['display_dockerInstallIcon'];
						}
					}
				} else {
					$template['display_dockerDisable'] = "<font color='red'>Docker Not Enabled</font>";
				}
			}
		}
		if ( ! $template['Compatible'] && ! $template['UnknownCompatible'] ) {
			$template['display_compatible'] = "NOTE: This application is listed as being NOT compatible with your version of unRaid<br>";
			$template['display_compatibleShort'] = "Incompatible";
		}
		$template['display_author'] = "<a class='ca_tooltip' style='cursor:pointer' onclick='authorSearch(this.innerHTML);' title='Search for more applications from {$template['SortAuthor']}'>".$template['Author']."</a>";
		$displayIcon = $template['Icon'];
		$displayIcon = $displayIcon ? $displayIcon : "/plugins/dynamix.docker.manager/images/question.png";
		$template['display_iconSmall'] = "<a onclick='showDesc(".$template['ID'].",&#39;".$name."&#39;);' style='cursor:pointer'><img class='ca_appPopup $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}' src='".$displayIcon."'></a>";
		$template['display_iconSelectable'] = "<img class='$iconClass' src='$displayIcon'>";
		if ( isset($ID) ) {
			$template['display_iconClickable'] = "<a class='ca_appPopup' data-appNumber='$ID' data-appPath='{$template['Path']}' style='cursor:pointer' >".$template['display_iconSelectable']."</a>";
			$template['display_iconSmall'] = "<a onclick='showDesc(".$template['ID'].",&#39;".$name."&#39;);' style='cursor:pointer'><img class='ca_appPopup $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}' src='".$displayIcon."'></a>";
		} else {
			$template['display_iconClickable'] = $template['display_iconSelectable'];
			$template['display_iconSmall'] = "<img src='".$displayIcon."' class='$iconClass'>";
		}
		$template['display_dockerName'] = ( $communitySettings['dockerSearch'] == "yes" && ! $template['Plugin'] ) ? "<a class='ca_tooltip' data-appNumber='$ID' style='cursor:pointer' onclick='mySearch(this.innerHTML);' title='Search dockerHub for similar containers'>".$template['Name']."</a>" : $template['Name'];
		$template['Category'] = ($template['Category'] == "UNCATEGORIZED") ? "Uncategorized" : $template['Category'];

		if ( ( $template['Beta'] == "true" ) ) {
			$template['display_dockerName'] .= "<span class='ca_tooltip' title='Beta Container &#13;See support forum for potential issues'><font size='3' color='red'><strong><br>BETA</strong></font></span>";
		}
# Entries created.  Now display it
		$t .= vsprintf($displayTemplate,toNumericArray($template));

		$columnNumber=++$columnNumber;

		if ( $viewMode == "detail" ) {
			if ( $columnNumber == $communitySettings['maxDetailColumns'] ) {
				$columnNumber = 0;
				$t .= vsprintf($skin[$viewMode]['eol'],$templateFormatArray);
			}
		} else {
			$columnNumber = 0;
			$t .= vsprintf($skin[$viewMode]['eol'],$templateFormatArray);
		}

		$ct .= $t;
		$count++;
		if ( ! $officialFlag && ($count == $communitySettings['maxPerPage']) ) {
			break;
		}
	}
	$ct .= vsprintf($skin[$viewMode]['footer'],$templateFormatArray);
	$ct .= caGetMode();
	if ( ! $officialFlag ) {
		$ct .= "<br>".getPageNavigation($pageNumber,count($file),false)."<br><br><br>";
	}
  if ( $communitySettings['dockerSearch'] != "yes" ) {
		$ct .= "<script>$('.dockerSearch').hide();</script>";
	}
	return $ct;
}

function getPageNavigation($pageNumber,$totalApps,$dockerSearch) {
	global $communitySettings;

	if ( $communitySettings['maxPerPage'] < 0 ) { return; }

	$my_function = $dockerSearch ? "dockerSearch" : "changePage";
	if ( $dockerSearch ) {
		$communitySettings['maxPerPage'] = 25;
	}
	$totalPages = ceil($totalApps / $communitySettings['maxPerPage']);

	if ($totalPages == 1) {
		return;
	}
	$startApp = ($pageNumber - 1) * $communitySettings['maxPerPage'] + 1;
	$endApp = $pageNumber * $communitySettings['maxPerPage'];
	if ( $endApp > $totalApps ) {
		$endApp = $totalApps;
	}
	$o = "<center><font color='purple'><b>";
	if ( ! $dockerSearch ) {
		$o .= "Displaying $startApp - $endApp (of $totalApps)<br>";
	}
	$o .= "Select Page:&nbsp;&nbsp&nbsp;";

	$previousPage = $pageNumber - 1;
	$o .= ( $pageNumber == 1 ) ? "<font size='3' color='grey'><i class='fa fa-arrow-circle-left' aria-hidden='true'></i></font>" : "<font size='3' color='green'><i class='fa fa-arrow-circle-left' aria-hidden='true' style='cursor:pointer' onclick='{$my_function}(&quot;$previousPage&quot;)' title='Go To Page $previousPage'></i></font>";
	$o .= "&nbsp;&nbsp;&nbsp;";
	$startingPage = $pageNumber - 5;
	if ($startingPage < 3 ) {
		$startingPage = 1;
	} else {
		$o .= "<b><a style='cursor:pointer' onclick='{$my_function}(&quot;1&quot;);' title='Go To Page 1'>1</a></b>&nbsp;&nbsp;&nbsp;...&nbsp;&nbsp;&nbsp;";
	}
	$endingPage = $pageNumber + 5;
	if ( $endingPage > $totalPages ) {
		$endingPage = $totalPages;
	}
	for ($i = $startingPage; $i <= $endingPage; $i++) {
		$o .= ( $i == $pageNumber ) ? $i : "<b><a style='cursor:pointer' onclick='{$my_function}(&quot;$i&quot;);' title='Go To Page $i'>$i</a></b>";
		$o .= "&nbsp;&nbsp;&nbsp";
	}
	if ( $endingPage != $totalPages) {
		if ( ($totalPages - $pageNumber ) > 6){
			$o .= "...&nbsp;&nbsp;&nbsp;";
		}
		if ( ($totalPages - $pageNumber ) >5 ) {
			$o .= "<b><a style='cursor:pointer' title='Go To Page $totalPages' onclick='{$my_function}(&quot;$totalPages&quot;);'>$totalPages</a></b>&nbsp;&nbsp;&nbsp;";
		}
	}
	$nextPage = $pageNumber + 1;
	$o .= ( $pageNumber < $totalPages ) ? "<font size='3' color='green'><i class='fa fa-arrow-circle-right' aria-hidden='true' style='cursor:pointer' title='Go To Page $nextPage' onclick='{$my_function}(&quot;$nextPage&quot;);'></i></font>" : "<font size='3' color='grey'><i class='fa fa-arrow-circle-right' aria-hidden='true'></i></font>";
	$o .= "</font></b></center><span id='currentPageNumber' hidden>$pageNumber</span>";

	return $o;
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
function displaySearchResults($pageNumber,$viewMode) {
	global $communityPaths, $communitySettings, $plugin;

	$tempFile = readJsonFile($communityPaths['dockerSearchResults']);
	$num_pages = $tempFile['num_pages'];
	$file = $tempFile['results'];
	$templates = readJsonFile($communityPaths['community-templates-info']);

	echo dockerNavigate($num_pages,$pageNumber);
	echo "<br><br>";

	$maxColumn = $communitySettings['maxColumn'];
  $viewMode = ($viewMode == "icon") ? "detail" : $viewMode;
	switch ($viewMode) {
		case "table":
			$t =  "<table class='tablesorter'><thead><th></th><th></th><th>Container</th><th>Author</th><th>Stars</th><th>Description</th></thead>";
			$iconSize = 48;
			break;
		case "detail":
			$t = "<table class='tablesorter'>";
			$viewMode = "icon";
			$maxColumn = 2;
			$iconSize = 96;
			break;
	}

	$column = 0;

	$t .= "<tr>";

	foreach ($file as $result) {
		$recommended = false;
		foreach ($templates as $template) {
			if ( $template['Repository'] == $result['Repository'] ) {
				$result['Description'] = $template['Description'];
				$result['Description'] = str_replace("'","&#39;",$result['Description']);
				$result['Description'] = str_replace('"',"&quot;",$result['Description']);
				$result['Icon'] = $template['IconWeb'];
			}
		}
		$result['display_stars'] = $result['Stars'] ? "<i class='fa fa-star' style='font-size:15px; color:magenta;' aria-hidden='true'></i> <strong>".$result['Stars']."</strong>" : "";
		$result['display_official'] =  $result['Official'] ? "<strong><font color=red>Official</font> ".$result['Name']." container.</strong><br><br>": "";
		$result['display_official_short'] = $result['Official'] ? "<font color='red'><strong>Official</strong></font>" : "";

		if ( $viewMode == "icon" ) {
			$t .= "<td>";
			$t .= "<center>".$result['display_official_short']."</center>";

			$t .= "<center>Author: </strong><font size='3'><a class='ca_tooltip' style='cursor:pointer' onclick='mySearch(this.innerHTML);' title='Search For Containers From {$result['Author']}'>{$result['Author']}</a></font></center>";
			$t .= "<center>".$result['display_stars']."</center>";

			$description = "Click to go to the dockerHub website for this container";
			if ( $result['Description'] ) {
				$description = $result['Description']."<br><br>$description";
			}
			$description =str_replace("'","&#39;",$description);
			$description = str_replace('"',"&#34;",$description);

			$t .= "<figure><center><a class='ca_tooltip' href='".$result['DockerHub']."' title='$description' target='_blank'>";
			$t .= "<img style='width:{$iconSize}px;height:{$iconSize}px;' src='".$result['Icon']."'></a>";
			$t .= "<figcaption><strong><center><font size='3'><a class='ca_tooltip' style='cursor:pointer' onclick='mySearch(this.innerHTML);' title='Search For Similar Containers'>".$result['Name']."</a></font></center></strong></figcaption></figure>";
			if ( $communitySettings['dockerRunning'] == "true" ) {
				$t .= "<center><input type='button' value='Add' onclick='dockerConvert(&#39;".$result['ID']."&#39;)' style='margin:0px'></center>";
			}
			$t .= "</td>";

			if ( $maxColumn == 2 ) {
				$t .= "<td style='display:inline-block;width:350px;text-align:left;'>";
				$t .= "<br><br><br>";
				$t .= $result['display_official'];

				if ( $result['Description'] ) {
					$t .= "<strong><span class='desc_readmore' style='display:block'>".$result['Description']."</span></strong><br><br>";
				} else {
					$t .= "<em>Container Overview not available.</em><br><br>";
				}
				$t .= "Click container's icon for full description<br><br>";
				$t .= "</td>";
			}
			$column = ++$column;
			if ( $column == $maxColumn ) {
				$column = 0;
				$t .= "</tr><tr>";
			}
		}
		if ( $viewMode == "table" ) {
			$t .= "<tr><td><a class='ca_tooltip' href='".$result['DockerHub']."' target='_blank' title='Click to go to the dockerHub website for this container'>";
			$t .= "<img src='".$result['Icon']."' style='width:{$iconSize}px;height:{$iconSize}px;'>";
			$t .= "</a></td>";
			$t .= "<td><input type='button' value='Add' onclick='dockerConvert(&#39;".$result['ID']."&#39;)';></td>";
			$t .= "<td><a class='ca_tooltip' style='cursor:pointer' onclick='mySearch(this.innerHTML);' title='Search Similar Containers'>".$result['Name']."</a></td>";
			$t .= "<td><a class='ca_tooltip' style='cursor:pointer' onclick='mySearch(this.innerHTML);' title='Search For More Containers From {$result['Author']}'>{$result['Author']}</a></td>";
			$t .= "<td>".$result['display_stars']."</td>";
			$t .= "<td>";
			$t .= $result['display_official'];
			$t .= "<strong><span class='desc_readmore' style='display:block'>".$result['Description']."</span></strong></td>";
			$t .= "</tr>";
		}
	}
	$t .= "</table>";
	echo $t;
	echo dockerNavigate($num_pages,$pageNumber);
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
		$template['display_Repository'],      #40
		$template['display_Stars'],           #41
		$template['display_Downloads'],       #42
		$template['display_pinButton'],       #43
		$template['display_Uninstall'],       #44
		$template['display_removable'],       #45
		$template['display_newIcon'],         #46
		$template['display_changes'],         #47 # Do not use -> no longer implemented
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
		$template['display_popupDesc'],       #62  # Do not use -> no longer implemented
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

