<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2023, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################

function display_apps($pageNumber=1,$selectedApps=false,$startup=false) {
  global $caPaths, $caSettings, $sortOrder;

  if ( is_file($caPaths['repositoriesDisplayed']) ) {
    $file = readJsonFile($caPaths['repositoriesDisplayed']);
    //$startup = true;
  } else {
    if ( is_file($caPaths['community-templates-catSearchResults']) )
      $file = readJsonFile($caPaths['community-templates-catSearchResults']);
    else
      $file = readJsonFile($caPaths['community-templates-displayed']);
  }
  $communityApplications = is_array($file['community']) ? $file['community'] : [];
  $totalApplications = count($communityApplications);

  $display = ( $totalApplications ) ? my_display_apps($communityApplications,$pageNumber,$selectedApps,$startup) : "<div class='ca_NoAppsFound'>".tr("No Matching Applications Found")."</div><script>$('.multi_installDiv').hide();hideSortIcons();</script>";

  return $display;
}

function my_display_apps($file,$pageNumber=1,$selectedApps=false,$startup=false) {
  global $caPaths, $caSettings, $plugin, $displayDeprecated, $sortOrder, $DockerTemplates, $DockerClient;

  $dockerUpdateStatus = readJsonFile($caPaths['dockerUpdateStatus']);
  $repositories = readJsonFile($caPaths['repositoryList']);
  $extraBlacklist = readJsonFile($caPaths['extraBlacklist']);
  $extraDeprecated = readJsonFile($caPaths['extraDeprecated']);
  $ct = "";
  $count = 0;

  if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
    $caSettings['dockerRunning'] = "true";
    $info = getAllInfo();
    $dockerUpdateStatus = readJsonFile($caPaths['dockerUpdateStatus']);
  } else {
    $caSettings['dockerRunning'] = false;
    $info = [];
    $dockerUpdateStatus = [];
  }

  if ( ! $selectedApps )
    $selectedApps = [];

  $dockerWarningFlag = $dockerNotEnabled = (! $caSettings['dockerRunning'] && ! ($caSettings['NoInstalls'] ?? false) ) ? "true" : "false";

  if ( $dockerNotEnabled == "true" ) {
    $unRaidVars = parse_ini_file($caPaths['unRaidVars']);
    $dockerVars = parse_ini_file($caPaths['docker_cfg']);

    if ( $unRaidVars['mdState'] == "STARTED" && $dockerVars['DOCKER_ENABLED'] !== "yes" )
      $dockerNotEnabled = 1; // Array started, docker not enabled
    if ( $unRaidVars['mdState'] == "STARTED" && $dockerVars['DOCKER_ENABLED'] == "yes" )
      $dockerNotEnabled = 2; // Docker failed to start
    if ( $unRaidVars['mdState'] !== "STARTED" )
      $dockerNotEnabled = 3; // Array not started
  }
  $displayHeader = "<script>addDockerWarning($dockerNotEnabled);var dockerNotEnabled = $dockerWarningFlag;</script>";

  $pinnedApps = readJsonFile($caPaths['pinnedV2']);

  $selectedApps['docker'] = $selectedApps['docker'] ?? [];
  $selectedApps['plugin'] = $selectedApps['plugin'] ?? [];
  $checkedOffApps = arrayEntriesToObject(@array_merge(@array_values($selectedApps['docker']),@array_values($selectedApps['plugin'])));

  $columnNumber = 0;
  $appCount = 0;
  $startingApp = ($pageNumber -1) * $caSettings['maxPerPage'] + 1;
  $startingAppCounter = 0;

  $displayedTemplates = [];
  foreach ($file as $template) {
    $startingAppCounter++;
    if ( $startingAppCounter < $startingApp ) continue;
    $displayedTemplates[] = $template;
  }

  # Create entries for skins.
  foreach ($displayedTemplates as $template) {
    if ( ! $template['RepositoryTemplate'] ) {
      if ( ! $template['Blacklist'] ) {
        if ( isset($extraBlacklist[$template['Repository']]) ) {
          $template['Blacklist'] = true;
          $template['ModeratorComment'] = $extraBlacklist[$template['Repository']];
        }
      }
      if ( ! $template['Deprecated'] && isset($extraDeprecated[$template['Repository']]) ) {
        $template['Deprecated'] = true;
        $template['ModeratorComment'] = $extraDeprecated[$template['Repository']];
      }
    }
    $template['Icon'] = $template["Icon-{$caSettings['dynamixTheme']}"] ?? $template['Icon'];

    if ( $template['RepositoryTemplate'] ) {
      $template['Icon'] = $template['icon'] ?? "/plugins/dynamix.docker.manager/images/question.png";

      if ( ! isset($template['bio']) )
        $template['CardDescription'] = tr("No description present");
      else {
        $template['bio'] = strip_tags(markdown($template['bio']));
        $template['Description'] = $template['bio'];
      }
      $template['display_dockerName'] = $template['RepoName'];

      $favClass = ( $caSettings['favourite'] && ($caSettings['favourite'] == $template['RepoName']) ) ? "ca_favouriteRepo" : "ca_non_favouriteRepo";
      $template['ca_fav'] = $caSettings['favourite'] && ($caSettings['favourite'] == $template['RepoName']);
      $niceRepoName = str_replace("'s Repository","",$template['RepoName']);
      $niceRepoName = str_replace("' Repository","",$niceRepoName);
      $niceRepoName = str_replace(" Repository","",$niceRepoName);

      $ct .= displayCard($template);
      $count++;
      if ( $count == $caSettings['maxPerPage'] ) break;
    } else {
      $actionsContext = [];
      $selected = false;
      
      if ( $template['ModeratorComment'] ) {
        preg_match_all("/\/\/(.*?)&#92;/m",$template['ModeratorComment'],$searchMatches);
        if ( count($searchMatches[1]) ) {
          foreach ($searchMatches[1] as $searchResult) {
            $template['ModeratorComment'] = str_replace("//$searchResult&#92;","<a style=cursor:pointer; onclick=doSidebarSearch(&quot;$searchResult&quot;);>$searchResult</a>",$template['ModeratorComment']);
          }
        }     
      }
      if ( $template['CAComment'] ) {
        preg_match_all("/\/\/(.*?)&#92;/m",$template['CAComment'],$searchMatches);
        if ( count($searchMatches[1]) ) {
          foreach ($searchMatches[1] as $searchResult) {
            $template['CAComment'] = str_replace("//$searchResult&#92;","<a style=cursor:pointer; onclick=doSidebarSearch(&quot;$searchResult&quot;);>$searchResult</a>",$template['CAComment']);
          }
        }        
      }
      $installComment = $template['ModeratorComment'] ? "<span class=ca_bold>{$template['ModeratorComment']}</span>" : $template['CAComment'];

      

      if ( $template['Requires'] ) {
        $template['Requires'] = markdown(strip_tags(str_replace(["\r","\n","&#xD;","'"],["","<br>","","&#39;"],trim($template['Requires'])),"<br>"));
        preg_match_all("/\/\/(.*?)&#92;/m",$template['Requires'],$searchMatches);
        if ( count($searchMatches[1]) ) {
          foreach ($searchMatches[1] as $searchResult) {
            $template['Requires'] = str_replace("//$searchResult&#92;","<a style=cursor:pointer; onclick=doSidebarSearch(&quot;$searchResult&quot;);>$searchResult</a>",$template['Requires']);
          }
        }
        $installComment = tr("This application has additional requirements")."<br>{$template['Requires']}<br>$installComment";
      }

      $installComment = str_replace("\n","",$installComment ?: "");
      if ( ! $template['Language'] ) {
        if ( ! $template['NoInstall'] && ! ($caSettings['NoInstalls'] ?? false) ) {
          if ( ! $template['Plugin'] ) {
            if ( $caSettings['dockerRunning'] ) {
              foreach ($info as $testDocker) {
                $tmpRepo = strpos($template['Repository'],":") ? $template['Repository'] : $template['Repository'].":latest";
                $tmpRepo = strpos($tmpRepo,"/") ? $tmpRepo : "library/$tmpRepo";
                if ( ( ($tmpRepo == $testDocker['Image'] && $template['Name'] == $testDocker['Name']) || "{$tmpRepo}:latest" == $testDocker['Image']) && ($template['Name'] == $testDocker['Name']) ) {
                  $selected = true;
                  $name = $testDocker['Name'];
                  break;
                }
              }

              $template['Installed'] = $selected;
              if ( $selected ) {

                $ind = searchArray($info,"Name",$name);
                if ( $info[$ind]['url'] && $info[$ind]['running'] ) {
                  $actionsContext[] = ["icon"=>"ca_fa-globe","text"=>"WebUI","action"=>"openNewWindow('{$info[$ind]['url']}','_blank');"];
                }

                if ( $dockerUpdateStatus[$tmpRepo]['status'] == "false" ) {
                  $template['UpdateAvailable'] = true;
                  $actionsContext[] = ["icon"=>"ca_fa-update","text"=>tr("Update"),"action"=>"updateDocker('$name');"];
                } else {
                  $template['UpdateAvailable'] = false;
                }
                if ( $caSettings['defaultReinstall'] == "true" && ! $template['Blacklist']) {
                  if ( $template['ID'] !== false ) { # don't allow 2nd if there's not a "default" within CA
                    if ( $template['BranchID'] ?? false )
                      $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Install second instance"),"action"=>"displayTags('{$template['ID']}',true,'".str_replace(" ","&#32;",htmlspecialchars($installComment))."','".portsUsed($template)."');"];
                    else
                      $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Install second instance"),"action"=>"popupInstallXML('".addslashes($template['Path'])."','second','".str_replace(" ","&#32;",htmlspecialchars($installComment))."','".portsUsed($template)."');"];
                  }
                }
                if ( is_file($info[$ind]['template']) )
                  $actionsContext[] = ["icon"=>"ca_fa-edit","text"=>tr("Edit"),"action"=>"popupInstallXML('".addslashes($info[$ind]['template'])."','edit');"];

                $actionsContext[] = ["divider"=>true];
                if ($info[$ind]['template'])
                  $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>tr("Uninstall"),"action"=>"uninstallDocker('".addslashes($info[$ind]['template'])."','{$template['Name']}');"];
                if ( $template['DonateLink'] ) {
                  $actionsContext[] = ["divider"=>true];
                  $actionsContext[] = ["icon"=>"ca_fa-money","text"=>tr("Donate"),"action"=>"openNewWindow('".addslashes($template['DonateLink'])."','_blank');"];
                }
              } elseif ( ! $template['Blacklist'] || ! $template['Compatible']) {
                if ( $template['InstallPath'] ) {
                  $userTemplate = readXmlFile($template['InstallPath'],false,false);
                  if ( ! $template['Blacklist'] ) {
                    $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Reinstall"),"action"=>"popupInstallXML('".addslashes($template['InstallPath'])."','user','','".portsUsed($userTemplate)."');"];
                    $actionsContext[] = ["divider"=>true];
                  }
                  $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>tr("Remove from Previous Apps"),"alternate"=>tr("Remove"),"action"=>"removeApp('{$template['InstallPath']}','{$template['Name']}');"];
                }	else {
                  if ( ! ($template['BranchID'] ?? null) ) {
                    if ( is_file("{$caPaths['dockerManTemplates']}/my-{$template['Name']}.xml") ) {
                      $test = readXmlFile("{$caPaths['dockerManTemplates']}/my-{$template['Name']}.xml",true);
                      if ( $template['Repository'] == $test['Repository'] ) {
                        $userTemplate = readXmlFile($template['InstallPath'],false,false);
                        $actionsContext[] = ["icon"=>"ca_fa-install","text"=>"<span class='ca_red'>".tr("Reinstall From Previous Apps")."</span>","action"=>"popupInstallXML('".addslashes("{$caPaths['dockerManTemplates']}/my-{$template['Name']}").".xml','user','','".portsUsed($userTemplate)."');"];
                        $actionsContext[] = ["divider"=>true];
                      }
                    }
                    $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Install"),"action"=>"popupInstallXML('".addslashes($template['Path'])."','default','".str_replace(" ","&#32;",htmlspecialchars($installComment))."','".portsUsed($template)."');"];
                  } else {
                    $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Install"),"action"=>"displayTags('{$template['ID']}',false,'".str_replace(" ","&#32;",htmlspecialchars($installComment))."','".portsUsed($template)."');"];
                  }
                }
              }
            }
          } else {
            $pluginName = basename($template['PluginURL']);
            $template['Installed'] = file_exists("/var/log/plugins/$pluginName");
            if ( $template['Installed'] )  {
              $pluginInstalledVersion = plugin("version","/var/log/plugins/$pluginName");
              if ( file_exists("/tmp/plugins/$pluginName") ) {
                $tmpPluginVersion = plugin("version","/tmp/plugins/$pluginName");
                if (strcmp($template['pluginVersion'],$tmpPluginVersion) < 0)
                  $template['pluginVersion'] = $tmpPluginVersion;
              }
              $template['pluginVersion'] = plugin("version","/tmp/plugins/$pluginName");

              if ( ( strcmp($pluginInstalledVersion,$template['pluginVersion']) < 0 || $template['UpdateAvailable']) && $template['Name'] !== "Community Applications" && ( ! ($template['UninstallOnly'] ?? false) ) ) {
                @copy($caPaths['pluginTempDownload'],"/tmp/plugins/$pluginName");
                $template['UpdateAvailable'] = true;
                $actionsContext[] = ["icon"=>"ca_fa-update","text"=>tr("Update"),"action"=>"installPlugin('$pluginName',true,'','{$template['RequiresFile']}');"];
              } else {
                if ( ! $template['UpdateAvailable'] ) # this handles if the feed hasn't caught up to the update yet
                  $template['UpdateAvailable'] = false;
              }
              $pluginSettings = ($pluginName == "community.applications.plg") ? "ca_settings" : plugin("launch","/var/log/plugins/$pluginName");
              if ( $pluginSettings ) {
                $actionsContext[] = ["icon"=>"ca_fa-pluginSettings","text"=>tr("Settings"),"action"=>"openNewWindow('/Apps/$pluginSettings');"];
              }

              if ( $pluginName != "community.applications.plg" ) {
                if ( ! empty($actionsContext) )
                  $actionsContext[] = ["divider"=>true];

                $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>tr("Uninstall"),"action"=>"uninstallApp('/var/log/plugins/$pluginName','".str_replace(" ","&#32;",$template['Name'])."');"];
              }
              if ( $template['DonateLink'] ) {
                  $actionsContext[] = ["divider"=>true];
                  $actionsContext[] = ["icon"=>"ca_fa-money","text"=>tr("Donate"),"action"=>"openNewWindow('".addslashes($template['DonateLink'])."','_blank');"];
              }
            } elseif ( ! $template['Blacklist'] || ! $template['Compatible'] ) {
              $buttonTitle = $template['InstallPath'] ? tr("Reinstall") : tr("Install");
              if ( ! $template['InstallPath'] ) {
                $installComment = $template['CAComment'];
                if ( ! $installComment && $template['Requires'] ){
                  // Remove the flags to indicate a search taking place
                  preg_match_all("/\/\/(.*?)\\\\/m",$template['Requires'],$searchMatches);
                  if ( count($searchMatches[1]) ) {
                    foreach ($searchMatches[1] as $searchResult) {
                      $template['Requires'] = str_replace("//$searchResult\\\\",$searchResult,$template['Requires']);
                    }
                  }
                  $installComment = tr("This application has additional requirements")."<br>".markdown($template['Requires']);
                }
              }
              $isDeprecated = $template['Deprecated'] ? "&deprecated" : "";
              $isDeprecated = $template['Compatible'] ? "&incompatible" : "";

              $updateFlag = false;
              $requiresText = "";
              if ( $template['RequiresFile'] && ! is_file($template['RequiresFile']) ) {
                $requiresText = "AnythingHere";
                $updateFlag = true; // This forces the system to double check the requirements and abort the install
              } else {
                $installComment = $template['RequiresFile'] ? "" : $installComment;
              }
              if ( ! ($template['UninstallOnly'] ?? false) ) {
                if ( $template['Compatible'] )
                  $actionsContext[] = ["icon"=>"ca_fa-install","text"=>$buttonTitle,"action"=>"installPlugin('{$template['PluginURL']}$isDeprecated','$updateFlag','".str_replace([" ","\n"],["&#32;",""],htmlspecialchars($installComment ?? ""))."','$requiresText');"];
              }
              if ( $template['InstallPath'] ) {
                if ( ! empty($actionsContext) )
                  $actionsContext[] = ["divider"=>true];
                $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>tr("Remove from Previous Apps"),"action"=>"removeApp('{$template['InstallPath']}','$pluginName');"];
              }
            }
            if ( file_exists($caPaths['pluginPending'].$pluginName) ) {
              unset($actionsContext);
              $actionsContext[] = ["text"=>tr("Pending")];
            }
          }
        }
      }
      if ( $template['Language'] ) {
        $countryCode = $template['LanguageDefault'] ? "en_US" : $template['LanguagePack'];
        $dynamixSettings = @parse_ini_file($caPaths['dynamixSettings'],true);
        $currentLanguage = $dynamixSettings['display']['locale'] ?? "en_US";
        $installedLanguages = array_diff(scandir("/usr/local/emhttp/languages"),[".",".."]);
        $installedLanguages = array_filter($installedLanguages,function($v) {
          return is_dir("/usr/local/emhttp/languages/$v");
        });
        $installedLanguages[] = "en_US";
        $currentLanguage = (is_dir("/usr/local/emhttp/languages/$currentLanguage") ) ? $currentLanguage : "en_US";
        if ( in_array($countryCode,$installedLanguages) ) {
          if ( $currentLanguage != $countryCode ) {
            $actionsContext[] = ["icon"=>"ca_fa-switchto","text"=>$template['SwitchLanguage'],"action"=>"CAswitchLanguage('$countryCode');"];
          }
        } else {
          $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Install"),"action"=>"installLanguage('{$template['TemplateURL']}','$countryCode');"];
        }
        if ( file_exists("/var/log/plugins/lang-$countryCode.xml") ) {
          $template['Installed'] = true;
          if ( languageCheck($template) ) {
            $template['UpdateAvailable'] = true;
            $actionsContext[] = ["icon"=>"ca_fa-update","text"=>$template['UpdateLanguage'],"action"=>"updateLanguage('$countryCode');"];
          }
          if ( $currentLanguage != $countryCode ) {
            if ( ! empty($actionsContext) )
              $actionsContext[] = ["divider"=>true];
            $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>tr("Remove Language Pack"),"action"=>"removeLanguage('$countryCode');"];
          }
        }
        if ( file_exists($caPaths['pluginPending'].$template['LanguagePack']) || file_exists("{$caPaths['pluginPending']}lang-{$template['LanguagePack']}.xml") ) {
          unset($actionsContext);
          $actionsContext[] = ["text"=>tr("Pending")];
        }
      }

      $template['actionsContext'] = $actionsContext;

      $template['ca_fav'] = $caSettings['favourite'] && ($caSettings['favourite'] == $template['RepoName']);
      if ( strpos($template['Repository'],"/") === false )
        $template['Pinned'] = $pinnedApps["library/{$template['Repository']}&{$template['SortName']}"] ?? false;
      else
        $template['Pinned'] = $pinnedApps["{$template['Repository']}&{$template['SortName']}"] ?? false;
      if ( isset($template['Repo']) ) {
        $template['Twitter'] = $template['Twitter'] ?? ($repositories[$template['Repo']]['Twitter'] ?? null);
        $template['Reddit'] = $template['Reddit'] ?? ($repositories[$template['Repo']]['Reddit'] ?? null);
        $template['Facebook'] = $template['Facebook'] ?? ($repositories[$template['Repo']]['Facebook'] ?? null);
        $template['Discord'] = $template['Discord'] ?? ($repositories[$template['RepoName']]['Discord'] ?? null);
      } else {
        $template['Twitter'] = $template['Twitter'] ?? null;
        $template['Reddit'] = $template['Reddit'] ?? null;
        $template['Facebook'] = $template['Facebook'] ?? null;
        $template['Discord'] = $template['Discord'] ?? null;
      }

      $previousAppName = $template['Plugin'] ? $template['PluginURL'] : $template['Name'];
      if ( isset($checkedOffApps[$previousAppName]) )
        $template['checked'] = $checkedOffApps[$previousAppName] ? "checked" : "";

      if ( ! $template['Plugin'] ) {
        $tmpRepo = $template['Repository'];
        if ( ! strpos($tmpRepo,"/") ) {
          $tmpRepo = "library/$tmpRepo";
        }
        foreach ($info as $testDocker) {
          if ( ($tmpRepo == $testDocker['Image'] || "$tmpRepo:latest" == $testDocker['Image']) && ($template['Name'] == $testDocker['Name']) ) {
            $template['Installed'] = true;

            break;
          }
        }
      } else {
        $pluginName = basename($template['PluginURL']);
        $template['Installed'] = checkInstalledPlugin($template) ;

      }

      if ( $template['Language'] ) {
        $template['Installed'] = is_dir("{$caPaths['languageInstalled']}{$template['LanguagePack']}") && ! $template['Uninstall'];
      }

      if ( startsWith($template['Repository'],"library/") || startsWith($template['Repository'],"registry.hub.docker.com/library/") || strpos($template['Repository'],"/") === false)
        $template['Official'] = true;

  # Entries created.  Now display it
      $ct .= displayCard($template);
      $count++;
      if ( $count == $caSettings['maxPerPage'] ) break;
    }
  }

  $ct .= getPageNavigation($pageNumber,count($file),false,true);

  if ( ! $count )
    $displayHeader .= "<div class='ca_NoAppsFound'>".tr("No Matching Applications Found")."</div><script>hideSortIcons();</script>";

  if ( $count == 1 && ! isset($template['homeScreen']) ) {
    if ( $template['RepositoryTemplate'] ) {
      $displayHeader .= "<script>showRepoPopup('".htmlentities($template['RepoName'],ENT_QUOTES)."');</script>";
    } else {
      if ($template['InstallPath'])
        $template['Path'] = $template['InstallPath'];

      $displayHeader .= "<script>showSidebarApp('{$template['Path']}','{$template['Name']}');</script>";
    }
  }
  // Handle MaxPerPage changing on a different tab
  $displayHeader .= "<script>changeMax({$caSettings['maxPerPage']});</script>";

  return "$displayHeader$ct";
}

function getPageNavigation($pageNumber,$totalApps,$dockerSearch,$displayCount = true) {
  global $caSettings;

  $pageFunction = $dockerSearch ? "dockerSearch": "changePage";
  if ( $dockerSearch )
    $caSettings['maxPerPage'] = 25;

  if ( $caSettings['maxPerPage'] < 0 ) return;
  $swipeScript = "<script>";

  $totalPages = ceil($totalApps / $caSettings['maxPerPage']);

  if ($totalPages <= 1) return "<script>data.currentpage = 1;</script>";

  $startApp = ($pageNumber - 1) * $caSettings['maxPerPage'] + 1;
  $endApp = $pageNumber * $caSettings['maxPerPage'];
  if ( $endApp > $totalApps )
    $endApp = $totalApps;

  $o = "</div><div class='ca_center'>";
  if ($displayCount)
    $o .= "<span class='pageNavigation'>".sprintf(tr("Displaying %s - %s (of %s)"),$startApp,$endApp,$totalApps)."</span><br>";

  $o .= "<div class='pageNavigation'>";
  $previousPage = $pageNumber - 1;
  $o .= ( $pageNumber == 1 ) ? "<span class='pageLeft pageNumber pageNavNoClick'></span>" : "<span class='pageLeft ca_tooltip pageNumber' onclick='$pageFunction(&quot;$previousPage&quot;)'></span>";
  $swipeScript .= "data.prevpage = $previousPage;";
  $startingPage = $pageNumber - 5;
  if ($startingPage < 3 )
    $startingPage = 1;
  else
    $o .= "<a class='ca_tooltip pageNumber' onclick='$pageFunction(&quot;1&quot;);'>1</a><span class='pageDots'></span>";

  $endingPage = $pageNumber + 5;
  if ( $endingPage > $totalPages )
    $endingPage = $totalPages;

  for ($i = $startingPage; $i <= $endingPage; $i++)
    $o .= ( $i == $pageNumber ) ? "<span class='pageNumber pageSelected'>$i</span>" : "<a class='ca_tooltip pageNumber' onclick='$pageFunction(&quot;$i&quot;);'>$i</a>";

  if ( $endingPage != $totalPages) {
    if ( ($totalPages - $pageNumber ) > 6)
      $o .= "<span class='pageDots'></span>";

    if ( ($totalPages - $pageNumber ) >5 )
      $o .= "<a class='ca_tooltip pageNumber' onclick='$pageFunction(&quot;$totalPages&quot;);'>$totalPages</a>";
  }
  $nextPage = $pageNumber + 1;
  $o .= ( $pageNumber < $totalPages ) ? "<span class='ca_tooltip pageNumber pageRight' onclick='$pageFunction(&quot;$nextPage&quot;);'></span>" : "<span class='pageRight pageNumber pageNavNoClick'></span>";
  $swipeScript .= ( $pageNumber < $totalPages ) ? "data.nextpage = $nextPage;" : "data.nextpage = 0;";
  $swipeScript .= "</script>";
  $o .= "</div></div><script>data.currentpage = $pageNumber;</script>";
  return $o.$swipeScript;
}


######################################
# Generate the display for the popup #
######################################
function getPopupDescriptionSkin($appNumber) {
  global $caSettings, $caPaths, $language, $DockerTemplates, $DockerClient;

  $unRaidVars = parse_ini_file($caPaths['unRaidVars']);
  $dockerVars = parse_ini_file($caPaths['docker_cfg']);
  $csrf_token = $unRaidVars['csrf_token'];
  $tabMode = '_parent';

  $allRepositories = readJsonFile($caPaths['repositoryList']);
  $extraBlacklist = readJsonFile($caPaths['extraBlacklist']);
  $extraDeprecated = readJsonFile($caPaths['extraDeprecated']);
  $templateDescription = "";
  $selected = null;

  $pinnedApps = readJsonFile($caPaths['pinnedV2']);
  $info = [];

  if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
    $caSettings['dockerRunning'] = "true";
    $infoTmp = getAllInfo();
    foreach ($infoTmp as $container) {
      $info[$container['Name']] = $container;
    }
    $dockerRunning = $DockerClient->getDockerContainers();
    $dockerUpdateStatus = readJsonFile($caPaths['dockerUpdateStatus']);
  } else {
    $caSettings['dockerRunning'] = false;
    $dockerRunning = [];
    $dockerUpdateStatus = [];
  }
  if ( ! is_file($caPaths['warningAccepted']) )
    $caSettings['NoInstalls'] = true;

  # $appNumber is actually the path to the template.  It's pretty much always going to be the same even if the database is out of sync.
  if ( is_file($caPaths['community-templates-allSearchResults']) )
    $displayed = readJsonFile($caPaths['community-templates-allSearchResults']);
  else
    $displayed = readJsonFile($caPaths['community-templates-displayed']);

  $index = searchArray($displayed['community'],"InstallPath",$appNumber);
  if ( $index === false ) {
    $ind = $index;
    while ( true ) {
      if ( $ind !== false ) {
        if ( isset($displayed[$ind]) ) {
          $template = $displayed[$ind];
          if ( $template['Name'] == ($displayed['community'][$ind]['Name'] ?? "") ) {
            $index = $ind;
            break;
          }
        }
      }
      $ind = searchArray($displayed['community'],"Path",$appNumber,$ind+1);
      if ( $ind === false ) {
        unset($template);
        break;
      }
    }
  }

  if ( $index !== false ) {
    $template = $displayed['community'][$index];
  }

  # handle case where the app being asked to display isn't on the most recent displayed list (ie: multiple browser tabs open)
  if ( ! isset($template) ) {
    $file = &$GLOBALS['templates'];
    $index = searchArray($file,"Path",$appNumber);
    if ( $index === false ) {
      echo json_encode(["description"=>tr("Something really wrong happened.  Reloading the Apps tab will probably fix the problem")]);
      return;
    }
    $template = $file[$index];
  }

  if ( ! $template['Blacklist'] ) {
    if ( isset($extraBlacklist[$template['Repository']]) ) {
      $template['Blacklist'] = true;
      $template['ModeratorComment'] = $extraBlacklist[$template['Repository']];
    }
  }
  if ( ! $template['Deprecated'] && isset($extraDeprecated[$template['Repository']]) ) {
    $template['Deprecated'] = true;
    $template['ModeratorComment'] = isset($extraDeprecated[$template['Repository']]);
  }

  $ID = $template['ID'];

  $template['Profile'] = $allRepositories[$template['RepoName']]['profile'] ?? "";
  $template['ProfileIcon'] = $allRepositories[$template['RepoName']]['icon'] ?? "";

  // Hack the system so that language's popups always appear in the appropriate language
  if ( $template['Language'] ) {
    $countryCode = $template['LanguageDefault'] ? "en_US" : $template['LanguagePack'];
    if ( $countryCode !== "en_US" ) {
      if ( ! is_file("{$caPaths['tempFiles']}/CA_language-$countryCode") ) {
        download_url("{$caPaths['CA_languageBase']}$countryCode","{$caPaths['tempFiles']}/CA_language-$countryCode");
      }
      $language = is_file("{$caPaths['tempFiles']}/CA_language-$countryCode") ? @parse_lang_file("{$caPaths['tempFiles']}/CA_language-$countryCode") : [];
    } else {
      $language = [];
    }
  }

  $donatelink = $template['DonateLink'];
  if ( $donatelink ) {
    $donatetext = $template['DonateText'];
  }

  if ( ! $template['Plugin'] ) {
    if ( ! strpos($template['Repository'],"/") ) {
      $template['Repository'] = "library/{$template['Repository']}";
    }
    foreach ($dockerRunning as $testDocker) {
      if ( ($template['Repository'] == $testDocker['Image'] || "{$template['Repository']}:latest" == $testDocker['Image']) && ($template['Name'] == $testDocker['Name']) ) {
        $selected = true;
        $name = $testDocker['Name'];
        break;
      }
    }
  } else
    $pluginName = basename($template['PluginURL']);

  if ( $template['trending'] ) {
    $allApps = &$GLOBALS['templates'];

    $allTrends = array_unique(array_column($allApps,"trending"));
    rsort($allTrends);
    $trendRank = array_search($template['trending'],$allTrends) + 1;
  }
  $template['Category'] = categoryList($template['Category'],true);
  $template['Icon'] = $template['Icon'] ? $template['Icon'] : "/plugins/dynamix.docker.manager/images/question.png";
  if ( $template['Overview'] )
    $ovr = $template['OriginalOverview'] ?: $template['Overview'];
  if ( ! isset($ovr) )
    $ovr = $template['OriginalDescription'] ?: $template['Description'];
  $ovr = html_entity_decode($ovr);
  $ovr = str_replace(["[","]"],["<",">"],$ovr);
  $ovr = str_replace("\n","<br>",$ovr);
  $ovr = str_replace("    ","&nbsp;&nbsp;&nbsp;&nbsp;",$ovr);
  $ovr = markdown(strip_tags($ovr,"<br>"));
  $template['display_ovr'] = $ovr;

  if ( $template['Plugin'] ) {
    $templateURL = $template['PluginURL'];
    download_url($templateURL,$caPaths['pluginTempDownload'],"",5);
    $template['Changes'] = @plugin("changes",$caPaths['pluginTempDownload']) ?: $template['Changes'];

    $template['pluginVersion'] = @plugin("version",$caPaths['pluginTempDownload']) ?: $template['pluginVersion'];

  } else {
    if ( ! $template['Changes'] && $template['ChangeLogPresent']) {
      $templateURL = $template['caTemplateURL'] ?: $template['TemplateURL'];
      download_url($templateURL,$caPaths['pluginTempDownload'],"",5);
      $xml = readXmlFile($caPaths['pluginTempDownload']);
      $template['Changes'] = $xml['Changes'];
    }
  }
  $template['Changes'] = str_replace("    ","&nbsp;&nbsp;&nbsp;&nbsp;",$template['Changes'] ?: ""); // Prevent inadvertent code blocks
  $template['Changes'] = Markdown(strip_tags(str_replace(["[","]"],["<",">"],$template['Changes'] ?: ""),"<br>"));
  if ( trim($template['Changes']) )
    $template['display_changes'] = trim($template['Changes']);

  if ( $template['IconFA'] ) {
    $template['IconFA'] = $template['IconFA'] ?: $template['Icon'];
    $templateIcon = startsWith($template['IconFA'],"icon-") ? "{$template['IconFA']} unraidIcon" : "fa fa-{$template['IconFA']}";
    $template['display_icon'] = "<i class='$templateIcon popupIcon'></i>";
  } else {
    $template['Icon'] = $template["Icon-{$caSettings['dynamixTheme']}"] ?? $template['Icon'];
    $template['display_icon'] = "<img class='popupIcon screenshot' href='{$template['Icon']}' src='{$template['Icon']}' alt='Application Icon'>";
  }
  
  if ( $template['ModeratorComment'] ) {
    preg_match_all("/\/\/(.*?)&#92;/m",$template['ModeratorComment'],$searchMatches);
    if ( count($searchMatches[1]) ) {
      foreach ($searchMatches[1] as $searchResult) {
        $template['ModeratorComment'] = str_replace("//$searchResult&#92;","<a style=cursor:pointer; onclick=doSidebarSearch(&quot;$searchResult&quot;);>$searchResult</a>",$template['ModeratorComment']);
      }
    }     
  }
  if ( $template['CAComment'] ) {
    preg_match_all("/\/\/(.*?)&#92;/m",$template['CAComment'],$searchMatches);
    if ( count($searchMatches[1]) ) {
      foreach ($searchMatches[1] as $searchResult) {
        $template['CAComment'] = str_replace("//$searchResult&#92;","<a style=cursor:pointer; onclick=doSidebarSearch(&quot;$searchResult&quot;);>$searchResult</a>",$template['CAComment']);
      }
    }        
  }
  if ( $template['Requires'] ) {
    $template['Requires'] = Markdown(strip_tags(str_replace(["\r","\n","&#xD;"],["","<br>",""],trim($template['Requires'])),"<br>"));
    preg_match_all("/\/\/(.*?)&#92;/m",$template['Requires'],$searchMatches);
    if ( count($searchMatches[1]) ) {
      foreach ($searchMatches[1] as $searchResult) {
        $template['Requires'] = str_replace("//$searchResult&#92;","<a style='cursor:pointer;' onclick='doSidebarSearch(&quot;$searchResult&quot;);'>$searchResult</a>",$template['Requires']);
      }
    }
  }
  $actionsContext = [];
  if ( ! $template['Language'] ) {
    if ( ! $template['NoInstall'] && ! ($caSettings['NoInstalls'] ?? false) ) {
      if ( ! $template['Plugin'] ) {
        if ( $caSettings['dockerRunning'] ) {
          if ( $selected ) {
            if ( $info[$name]['url'] && $info[$name]['running'] ) {
              $actionsContext[] = ["icon"=>"ca_fa-globe","text"=>"WebUI","action"=>"openNewWindow('{$info[$name]['url']}','_blank');"];
            }
            $tmpRepo = strpos($template['Repository'],":") ? $template['Repository'] : $template['Repository'].":latest";
            $tmpRepo = strpos($tmpRepo,"/") ? $tmpRepo : "library/$tmpRepo";
            if ( $dockerUpdateStatus[$tmpRepo]['status'] == "false" ) {
              $template['UpdateAvailable'] = true;
              $actionsContext[] = ["icon"=>"ca_fa-update","text"=>tr("Update"),"action"=>"updateDocker('$name');"];
            } else {
              $template['UpdateAvailable'] = false;
            }
            if ( $caSettings['defaultReinstall'] == "true" && ! $template['Blacklist'] && $template['ID'] !== false) {
              if ( $template['BranchID'] ?? false )
                $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Install second instance"),"action"=>"displayTags('{$template['ID']}',true,'','".portsUsed($template)."');"];
              else
                $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Install second instance"),"action"=>"popupInstallXML('".addslashes($template['Path'])."','second','','".portsUsed($template)."');"];
            }
            if ( is_file($info[$name]['template']) )
              $actionsContext[] = ["icon"=>"ca_fa-edit","text"=>tr("Edit"),"action"=>"popupInstallXML('".addslashes($info[$name]['template'])."','edit');"];

            $actionsContext[] = ["divider"=>true];
            if ( $info[$name]['template'] ) {
              $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>"<span class='ca_red'>".tr("Uninstall")."</span>","action"=>"uninstallDocker('".addslashes($info[$name]['template'])."','{$template['Name']}');"];
              $template['Installed'] = true;
            }
          } elseif ( ! $template['Blacklist'] ) {
            if ( $template['InstallPath'] ) {
              $userTemplate = readXmlFile($template['InstallPath'],false,false);
              if ( ! $template['Blacklist'] ) {
                $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Reinstall"),"action"=>"popupInstallXML('".addslashes($template['InstallPath'])."','user','','".portsUsed($userTemplate)."');"];
                $actionsContext[] = ["divider"=>true];
              }
              $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>"<span class='ca_red'>".tr("Remove from Previous Apps")."</span>","action"=>"removeApp('{$template['InstallPath']}','{$template['Name']}');"];
            }	else {
              if ( ! $template['Blacklist'] ) {
                if ( ( $template['Compatible'] || $caSettings['hideIncompatible'] !== "true" )  ) {
                  if ( !$template['Deprecated'] || $caSettings['hideDeprecated'] !== "true" ) {
                    if ( ! isset($template['BranchID']) ) {
                      $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Install"),"action"=>"popupInstallXML('".addslashes($template['Path'])."','default','','".portsUsed($template)."');"];
                    } else {
                      $actionsContext[] = ["icon"=>"ca_fa-install","text"=>tr("Install"),"action"=>"displayTags('{$template['ID']}',false,'','".portsUsed($template)."');"];
                    }
                  }
                }
              }
            }
          }
        }
      } else {
        if ( file_exists("/var/log/plugins/$pluginName") ) {
          $template['Installed'] = true;
          $template['installedVersion'] = plugin("version","/var/log/plugins/$pluginName");
          if ( ($template['installedVersion'] != $template['pluginVersion'] || $template['installedVersion'] != plugin("version","/tmp/plugins/$pluginName") ) && $template['Name'] !== "Community Applications") {
            if (is_file($caPaths['pluginTempDownload'])) {
              @copy($caPaths['pluginTempDownload'],"/tmp/plugins/$pluginName");
              $template['UpdateAvailable'] = true;
              $actionsContext[] = ["icon"=>"ca_fa-update","text"=>tr("Update"),"action"=>"installPlugin('$pluginName',true);"];
            }
          } else {
            $template['UpdateAvailable'] = false;
          }
          $pluginSettings = ($pluginName == "community.applications.plg") ? "ca_settings" : plugin("launch","/var/log/plugins/$pluginName");
          if ( $pluginSettings ) {
            $actionsContext[] = ["icon"=>"ca_fa-pluginSettings","text"=>tr("Settings"),"action"=>"openNewWindow('/Apps/$pluginSettings');"];
          }
          if ( $pluginName != "community.applications.plg" ) {
            if ( ! empty($actionsContext) )
              $actionsContext[] = ["divider"=>true];

            $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>"<span class='ca_red'>".tr("Uninstall")."</span>","action"=>"uninstallApp('/var/log/plugins/$pluginName','".str_replace(" ","&nbsp;",$template['Name'])."');"];
          }
        } elseif ( ! $template['Blacklist']  ) {
          if ( ($template['Compatible'] || $caSettings['hideIncompatible'] !== "true") && !($template['UninstallOnly'] ?? false) ) {
            if ( !$template['Deprecated'] || $caSettings['hideDeprecated'] !== "true" || ($template['Deprecated'] && $template['InstallPath']) ) {
              if ( ($template['RequiresFile'] && is_file($template['RequiresFile']) ) || ! $template['RequiresFile'] ) {
                $buttonTitle = $template['InstallPath'] ? tr("Reinstall") : tr("Install");
                $isDeprecated = $template['Deprecated'] ? "&deprecated" : "";
                $isDeprecated = $template['Compatible'] ? "&incompatible" : "";
                $actionsContext[] = ["icon"=>"ca_fa-install","text"=>$buttonTitle,"action"=>"installPlugin('{$template['PluginURL']}$isDeprecated');"];
              }
            }
          }
          if ( $template['InstallPath'] ) {
            if ( ! empty($actionsContext) )
              $actionsContext[] = ["divider"=>true];
            $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>"<span class='ca_red'>".tr("Remove from Previous Apps")."</span>","action"=>"removeApp('{$template['InstallPath']}','$pluginName');"];
          }
        }
        if ( is_file($caPaths['pluginPending'].$pluginName) ) {
          unset($actionsContext);
          $actionsContext[] = ["text"=>tr("Pending")];
        }
      }
    }
  }
  if ( $template['Language'] ) {
    $dynamixSettings = @parse_ini_file($caPaths['dynamixSettings'],true);
    $currentLanguage = $dynamixSettings['display']['locale'] ?? "en_US";
    $installedLanguages = array_diff(scandir("/usr/local/emhttp/languages"),array(".",".."));
    $installedLanguages = array_filter($installedLanguages,function($v) {
      return is_dir("/usr/local/emhttp/languages/$v");
    });
    $installedLanguages[] = "en_US";
    $currentLanguage = (is_dir("/usr/local/emhttp/languages/$currentLanguage") ) ? $currentLanguage : "en_US";
    if ( in_array($countryCode,$installedLanguages) ) {
      if ( $currentLanguage != $countryCode ) {
        $actionsContext[] = ["icon"=>"ca_fa-switchto","text"=>$template['SwitchLanguage'],"action"=>"CAswitchLanguage('$countryCode');"];
      }
    } else {
      $actionsContext[] = ["icon"=>"ca_fa-install","text"=>$template['InstallLanguage'],"action"=>"installLanguage('{$template['TemplateURL']}','$countryCode');"];
    }
    if ( file_exists("/var/log/plugins/lang-$countryCode.xml") ) {
      if ( languageCheck($template) ) {
        $template['UpdateAvailable'] = true;
        $actionsContext[] = ["icon"=>"ca_fa-update","text"=>$template['UpdateLanguage'],"action"=>"updateLanguage('$countryCode');"];
      }
      if ( $currentLanguage != $countryCode ) {
        if ( ! empty($actionsContext) )
          $actionsContext[] = ["divider"=>true];
        $actionsContext[] = ["icon"=>"ca_fa-delete","text"=>"<span class='ca_red'>".tr("Remove Language Pack")."</span>","action"=>"removeLanguage('$countryCode');"];
      }
    }
    if ( $countryCode !== "en_US" ) {
      $template['Changes'] = "<center><a href='https://github.com/unraid/lang-$countryCode/commits/master' target='_blank'>".tr("Click here to view the language changelog")."</a></center>";
    } else {
      unset($template['Changes']);
    }
    if ( file_exists($caPaths['pluginPending'].$template['LanguagePack']) || file_exists("{$caPaths['pluginPending']}lang-{$template['LanguagePack']}.xml") ) {
      unset($actionsContext);
      $actionsContext[] = ["text"=>tr("Pending")];
    }
  }

  $supportContext = [];
  if ( $template['ReadMe'] )
    $supportContext[] = ["icon"=>"ca_fa-readme","link"=>$template['ReadMe'],"text"=>tr("Read Me First")];
  if ( $template['Project'] )
    $supportContext[] = ["icon"=>"ca_fa-project","link"=>$template['Project'],"text"=> tr("Project")];

  if ( $template['Discord'] )
    $supportContext[] = ["icon"=>"ca_discord","link"=>$template['Discord'],"text"=>tr("Discord")];
  elseif ( isset($allRepositories[$template['Repo']]['Discord']) )
    $supportContext[] = ["icon"=>"ca_discord","link"=>$allRepositories[$template['Repo']]['Discord'],"text"=>tr("Discord")];

  if ( $template['Facebook'] )
    $supportContext[] = ["icon"=>"ca_facebook","link"=>$template['Facebook'],"text"=>tr("Facebook")];
  if ( $template['Reddit'] )
    $supportContext[] = ["icon"=>"ca_reddit","link"=>$template['Reddit'],"text"=>tr("Reddit")];

  if ( $template['Support'] )
    $supportContext[] = ["icon"=>"ca_fa-support","link"=>$template['Support'],"text"=> $template['SupportLanguage'] ?: tr("Support Forum")];

  if ( $template['Registry'] )
    $supportContext[] = ["icon"=>"ca_fa-docker","link"=>$template['Registry'],"text"=> tr("Registry")];
  if ( $caSettings['dev'] == "yes" )
    $supportContext[] = ["icon"=>"ca_fa-template","link"=> $template['caTemplateURL'] ?: ($template['TemplateURL']??""),"text"=>tr("Application Template")];

  $author = $template['PluginURL'] ? $template['PluginAuthor'] : $template['SortAuthor'];

  if (is_array($template['trends']) && (count($template['trends']) > 1) ){
    if ( $template['downloadtrend'] ) {
      $templateDescription .= "<div><canvas id='trendChart{$template['ID']}' class='caChart' height=1 width=3></canvas></div>";
      $templateDescription .= "<div><canvas id='downloadChart{$template['ID']}' class='caChart' height=1 width=3></canvas></div>";
      $templateDescription .= "<div><canvas id='totalDownloadChart{$template['ID']}' class='caChart' height=1 width=3></canvas></div>";
    }
  }
  if ( ! isset($countryCode) ) {
    $changeLogMessage = "Note: not all ";
    $changeLogMessage .= $template['PluginURL'] || $template['Language'] ? "authors" : "maintainers";
    $changeLogMessage .= " keep up to date on change logs<br>";
    $template['display_changelogMessage'] = tr($changeLogMessage);
  }

  if ( isset($template['trendsDate']) ) {
    array_walk($template['trendsDate'],function(&$entry) {
      $entry = tr(date("M",$entry),0).date(" j",$entry);
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
    $down = is_array($down) ? $down : [];
  }

  if ( $pinnedApps["{$template['Repository']}&{$template['SortName']}"] ?? false ) {
    $template['pinned'] = tr("Unpin App");
    $template['pinnedTitle'] = tr("Click to unpin this application");
    $template['pinnedClass'] = "pinned";
  } else {
    $template['pinned'] = tr("Pin App");
    $template['pinnedTitle'] = tr("Click to pin this application");
    $template['pinnedClass'] = "unpinned";
  }
  $template['actionsContext'] = $actionsContext;
  $template['supportContext'] = $supportContext;
  @unlink($caPaths['pluginTempDownload']);

  return ["description"=>displayPopup($template),"trendData"=>$template['trends'],"trendLabel"=>$chartLabel ?? "","downloadtrend"=>$down ?? "","downloadLabel"=>$downloadLabel ?? "","totaldown"=>$totalDown ?? "","totaldownLabel"=>$downloadLabel ?? "","supportContext"=>$supportContext,"actionsContext"=>$actionsContext,"ID"=>$template['ID']];
}

#####################################
# Generate the display for the repo #
#####################################
function getRepoDescriptionSkin($repository) {
  global $caSettings, $caPaths, $language;

  $dockerVars = parse_ini_file($caPaths['docker_cfg']);
  $repositories = readJsonFile($caPaths['repositoryList']);
  $templates = &$GLOBALS['templates'];

  $repo = $repositories[$repository];
  $iconPrefix = $repo['icon'] ? "<a class='screenshot mfp-image' href='{$repo['icon']}'>" : "";
  $iconPostfix = $repo['icon'] ? "</a>" : "";

  $repo['icon'] = $repo['icon'] ?? "/plugins/dynamix.docker.manager/images/question.png";
  $repo['bio'] = isset($repo['bio']) ? markdown($repo['bio']) : "<br><center>".tr("No description present");
  $favRepoClass = ($caSettings['favourite'] == $repository) ? "fav" : "nonfav";

  $totalApps = $totalLanguage = $totalPlugins = $totalDocker = $totalDownloads = $downloadDockerCount = 0;
  foreach ($templates as $template) {
    if ( $template['RepoName'] !== $repository ) continue;
    if ( isset($template['BranchID']) ) continue;

    if ( $template['Blacklist'] ) continue;
    if ( $template['Deprecated'] && $caSettings['hideDeprecated'] !== "false" ) continue;
    if ( ! $template['Compatible'] && $caSettings['hideIncompatible'] !== "false" ) continue;

    if ( $template['Registry'] ) {
      $totalDocker++;
      if ( $template['downloads'] ) {
        $totalDownloads = $totalDownloads + $template['downloads'];
        $downloadDockerCount++;
      }
    }
    if ( $template['PluginURL'] ) {
      $totalPlugins++;
    }
    if ( $template['Language'] ) {
      $totalLanguage++;
    }

    $totalApps++;
  }

  $t = "
    <div class='popUpClose'>".tr("CLOSE")."</div>
    <div class='popUpBack'>".tr("BACK")."</div>
    <div class='ca_popupIconArea'>
      <div class='popupIcon'>
        $iconPrefix<img class='popupIcon' src='{$repo['icon']}'>$iconPostfix
      </div>
      <div class='popupInfo'>
        <div class='popupName'>$repository</div>
        <div class='ca_repoSearchPopUp popupProfile' data-repository='".htmlentities($repository,ENT_QUOTES)."'>".tr("See All Apps")."</div>
        <div class='ca_favouriteRepo $favRepoClass' data-repository='".htmlentities($repository,ENT_QUOTES)."'>".tr("Favourite")."</div>
      </div>
    </div>
    <div class='popupRepoDescription'><br>".strip_tags($repo['bio'])."</div>
  ";
  if ( isset($repo['DonateLink']) ) {
    $t .= "
      <div class='donateArea'>
        <div class='repoDonateText'>{$repo['DonateText']}</div>
        <a class='donate' href='{$repo['DonateLink']}' target='_blank'>".tr("Donate")."</a>
      </div>
    ";
  }
  if ( isset($repo['Photo']) || isset($repo['Video']) ) {
    $t .= "<div>";
    if ( isset($repo['Photo']) ) {
      $photos = is_array($repo['Photo']) ? $repo['Photo'] : [$repo['Photo']];
      $t .= "<div>";
      foreach ($photos as $shot) {
        $t .= "<a class='screenshot' href='".trim($shot)."'><img class='screen' src='".trim($shot)."' onerror='this.style.display=&quot;none&quot;'></img></a>";
      }
      $t .= "</div>";
    }
    if ( isset($repo['Video']) ) {
      if ( isset($repo['Photo']) )
        $t .= "<div><hr></div>";

      $videos = is_array($repo['Video']) ? $repo['Video'] : [$repo['Video']];
      $vidText = (count($videos) == 1) ? "Play Video" : "Play Video %s";
      $t .= "<div>";
      $count = 1;
      foreach ($videos as $vid) {
        $t .= "<a class='screenshot videoButton mfp-iframe' href='".trim($vid)."'><div class='ca_fa-film'> ".sprintf(tr($vidText),$count)."</div></a>";
        $count++;
      }
      $t .= "</div>";
    }

    $t .= "</div>";
  }
  $t .= "
    </div>
    <div class='repoLinks'>
  ";

  $t .= "<div class='repoLinkArea'>";

  if ( isset($repo['WebPage']) )
    $t .= "<a class='appIconsPopUp ca_webpage' href='{$repo['WebPage']}' target='_blank'> ".tr("Web Page")."</a>";
  if ( isset($repo['Forum']) )
    $t .= "<a class='appIconsPopUp ca_forum' href='{$repo['Forum']}' target='_blank'> ".tr("Forum")."</a>";
  if ( isset($repo['profile']) )
    $t .= "<a class='appIconsPopUp ca_profile' href='{$repo['profile']}' target='_blank'> ".tr("Forum Profile")."</a>";
  if ( isset($repo['Facebook']) )
    $t .= "<a class='appIconsPopUp ca_facebook' href='{$repo['Facebook']}' target='_blank'> ".tr("Facebook")."</a>";
  if ( isset($repo['Reddit']) )
    $t .= "<a class='appIconsPopUp ca_reddit' href='{$repo['Reddit']}' target='_blank'> ".tr("Reddit")."</a>";
  if ( isset($repo['Twitter']) )
    $t .= "<a class='appIconsPopUp ca_twitter' href='{$repo['Twitter']}' target='_blank'> ".tr("Twitter")."</a>";
  if ( isset($repo['Discord']) )
    $t .= "<a class='appIconsPopUp ca_discord_popup' target='_blank' href='{$repo['Discord']}' target='_blank'> ".tr("Discord")."</a>";

  $t .= "
    </div>
    <div class='repoStats'>Statistics</div>
      <table class='repoTable'>
  ";
  if ( $repo['FirstSeen'] > 1 )
    $t .= "<tr><td class='repoLeft'>".tr("Added to CA")."</td><td class='repoRight'>".date("F j, Y",$repo['FirstSeen'])."</td></tr>";

  $t .= "
        <tr><td class='repoLeft'>".tr("Total Docker Applications")."</td><td class='repoRight'>$totalDocker</td></tr>
        <tr><td class='repoLeft'>".tr("Total Plugin Applications")."</td><td class='repoRight'>$totalPlugins</td></tr>
    ";
    if ( isset($totalLanguage) )
      $t .= "
        <tr><td class='repoLeft''>".tr("Total Languages")."</td><td class='repoRight'>$totalLanguage</td></tr>
      ";
  if ( $caSettings['dev'] == "yes" && $repo['url'])
    $t .= "
        <tr><td class='repoLeft'><a class='popUpLink' href='{$repo['url']}' target='_blank'>".tr("Repository URL")."</a></td></tr>
    ";

  $t .= "
        <tr><td class='repoLeft'>".tr("Total Applications")."</td><td class='repoRight'>$totalApps</td></tr>
      ";

  if ( $downloadDockerCount && $totalDownloads ) {
    $avgDownloads = intval($totalDownloads / $downloadDockerCount);
    $t .= "<tr><td class='repoLeft'>".tr("Total Known Downloads")."</td><td class='repoRight'>".number_format($totalDownloads)."</td></tr>";
    $t .= "<tr><td class='repoLeft'>".tr("Average Downloads Per App")."</td><td class='repoRight'>".number_format($avgDownloads)."</td></tr>";
  }
  $t .= "</table>";
  $t .= "</div>";

  $t = "<div class='popup'>$t</div>";
  return ["description"=>$t];
}

########################################################################################
# function used to display the navigation (page up/down buttons) for dockerHub results #
########################################################################################
function dockerNavigate($num_pages, $pageNumber) {
  return getPageNavigation($pageNumber,$num_pages * 25, true);
}

##############################################################
# function that actually displays the results from dockerHub #
##############################################################
function displaySearchResults($pageNumber) {
  global $caPaths, $caSettings, $plugin;

  $tempFile = readJsonFile($caPaths['dockerSearchResults']);
  $num_pages = $tempFile['num_pages'];
  $file = $tempFile['results'];
  $templates = &$GLOBALS['templates'];
  $count = 0;
  $caSettings['NoInstalls'] = is_file($caPaths['warningAccepted']) ? false : true;

  $ct = "<div>".tr("NOTE You must visit the dockerHub page to gather the information required to install correctly")."<span class='templateSearch' style='float:right'>Show CA templates</span></div><br><br>";
  $ct .= "<div class='ca_templatesDisplay'>";

  $columnNumber = 0;
  foreach ($file as $result) {
    $result['Icon'] = "/plugins/dynamix.docker.manager/images/question.png";
    $result['display_dockerName'] = "<a class='ca_tooltip ca_applicationName' style='cursor:pointer;' onclick='mySearch(this.innerText);' title='".tr("Search for similar containers")."'>{$result['Name']}</a>";
    $result['Category'] = "Docker&nbsp;Hub&nbsp;Search";
    $result['Description'] = $result['Description'] ?: tr("No description present");
    $result['Compatible'] = true;
    if ( ! $caSettings['NoInstalls'] )
      $result['actionsContext'] = [["icon"=>"ca_fa-install","text"=>tr("Install"),"action"=>"dockerConvert({$result['ID']});"]];

    $templateSearch = searchArray($templates,"Repository",$result['Repository']);
    if ( $templateSearch === false )
      $templateSearch = searchArray($templates,"Repository","{$result['Repository']}.latest");

    if ( $templateSearch !== false && ! $templates[$templateSearch]['Deprecated'] && ! $templates[$templateSearch]['Blacklist']) {
      $result['caTemplateExists'] = true;
      $result['Icon'] = $templates[$templateSearch]['Icon'];
      $result['Description'] = $templates[$templateSearch]['Overview'] ?: $templates[$templateSearch]['Description'];
      unset($result['IconFA']);
      $result['ID'] = $templates[$templateSearch]['ID'];
      $result['actionsContext'] = [["icon"=>"ca_fa-template","text"=>tr("Show Template"),"action"=>"doSearch(false,'{$templates[$templateSearch]['Repository']}');"]];
    }
    $ct .= displayCard($result);
    $count++;
  }
  $ct .= "</div>";

  return $ct.dockerNavigate($num_pages,$pageNumber);
}
###########################
# Generate the app's card #
###########################
function displayCard($template) {
  global $caSettings, $caPaths;
  $appName = str_replace("-"," ",$template['display_dockerName'] ?? "");
  $holderClass = "";
  $card = "";

  if ( $template['RepositoryTemplate'] )
    $template['DockerHub'] = false;

  if ( $template['DockerHub'] )
    $popupType = null;
  else {
    $popupType = $template['RepositoryTemplate'] ? "ca_repoPopup" : "ca_appPopup";

    if (! $template['RepositoryTemplate'] && $template['Language']) {
      $language = "{$template['Language']}";
      $language .= $template['LanguageLocal'] ? " - {$template['LanguageLocal']}" : "";
      $template['Category'] = "";
    }
  }

  extract($template);

  $class = "spotlightHome";
  $RepoName = $RepoName ?? "";

  if ( $RepositoryTemplate )
    $appType = "appRepository";
  else {
    $appType = $Plugin ? "appPlugin" : "appDocker";
    $appType = $Language ? "appLanguage": $appType;
    $appType = (strpos($Category,"Drivers") !== false) && $Plugin ? "appDriver" : $appType;
  }
  switch ($appType) {
    case "appPlugin":
      $typeTitle = tr("This application is a plugin");
      break;
    case "appDocker":
      $typeTitle = tr("This application is a docker container");
      break;
    case "appLanguage":
      $typeTitle = tr("This is a language pack");
      break;
    case "appDriver":
      $typeTitle = tr("This application is a driver (plugin)");
      break;
    default:
      $typeTitle = "";
      break;
  }
  if ($InstallPath ?? false)
    $Path = $InstallPath;

  $Category = $Category ?? "";
  $Category = explode(" ",$Category)[0];
  $Category = explode(":",$Category)[0];

  if ( ! $DockerHub )
    $author = $RepoShort ?? $RepoName;
  else
    $author = $Author;

  $ID = $ID ?? "";

  $author = $author ?? "";
  if ( $author == $RepoName ) {
    if (strpos($author,"' Repository") )
      $author = sprintf(tr("%s's Repository"),str_replace("' Repository","",$author));
    elseif (strpos($author,"'s Repository"))
      $author = sprintf(tr("%s's Repository"),str_replace("'s Repository","",$author));
    elseif (strpos($author," Repository") )
      $author = sprintf(tr("%s Repository"),str_replace(" Repository","",$author));
  }

  if ( !$RepositoryTemplate ) {
    $cardClass = "ca_appPopup";
    $supportContext = [];
    if ( $ReadMe )
      $supportContext[] = ["icon"=>"ca_fa-readme","link"=>$ReadMe,"text"=>tr("Read Me First")];
    if ( $Project )
      $supportContext[] = ["icon"=>"ca_fa-project","link"=>$Project,"text"=> tr("Project")];
    if ( $Discord )
      $supportContext[] = ["icon"=>"ca_discord","link"=>$Discord,"text"=>tr("Discord")];
    if ( $Support )
      $supportContext[] = ["icon"=>"ca_fa-support","link"=>$Support,"text"=> $SupportLanguage ?: tr("Support Forum")];
    if ( $Registry ?? false)
      $supportContext[] = ["icon"=>"docker","link"=>$Registry,"text"=>tr("Registry")];
  } else {
    $holderClass='repositoryCard';
    $cardClass = "ca_repoinfo";
    $ID = str_replace(" ","",$RepoName);
    $supportContext = [];
    if ( $profile ?? false )
      $supportContext[] = ["icon"=>"ca_profile","link"=>$profile,"text"=>tr("Profile")];
    if ( $Forum ?? false)
      $supportContext[] = ["icon"=>"ca_forum","link"=>$Forum,"text"=>tr("Forum")];
    if ( $Twitter ?? false)
      $supportContext[] = ["icon"=>"ca_twitter","link"=>$Twitter,"text"=>tr("Twitter")];
    if ( $Reddit ?? false)
      $supportContext[] = ["icon"=>"ca_reddit","link"=>$Reddit,"text"=>tr("Reddit")];
    if ( $Facebook ?? false)
      $supportContext[] = ["icon"=>"ca_facebook","link"=>$Facebook,"text"=>tr("Facebook")];
    if ( $WebPage ?? false)
      $supportContext[] = ["icon"=>"ca_webpage","link"=>$WebPage,"text"=>tr("Web Page")];

    $Name = str_replace(["' Repository","'s Repository"," Repository"],"",html_entity_decode($author,ENT_QUOTES));

    $Name = str_replace(["&apos;s","'s"],"",$Name);
    $author = "";
    $Path = $Repository = $Plugin = $IconFA = $ModeratorComment = $RecommendedDate = $UpdateAvailable = $Blacklist = $Official = $Trusted = $Pinned = $actionsContext = $Deprecated = $Removable = $CAComment = $Installed = $Uninstalled = $Uninstall = $fav = $Beta = $Requires = $caTemplateExists = $actionCentre = $Overview = $imageNoClick = "";
  }

  $bottomClass = "ca_bottomLineSpotLight";
  if ( $DockerHub ) {
    $backgroundClickable = "dockerCardBackground";
    $card .= "
      <div class='dockerHubHolder $class $popupType'>
      <div class='ca_bottomLine $bottomClass'>
      <div class='infoButton_docker dockerPopup' data-dockerHub='$DockerHub'>".tr("Docker Hub")."</div>";
  } else {
    if ( $PluginURL ?? false) {
      $dataPluginURL = "data-pluginurl='$PluginURL'";
    } else {
      $dataPluginURL = "";
    }
    $backgroundClickable = "ca_backgroundClickable";
    $card .= "
      <div class='ca_holder $class $popupType $holderClass' data-apppath='$Path' data-appname='$Name' data-repository='".htmlentities($RepoName,ENT_QUOTES)."' $dataPluginURL>
      <div class='ca_bottomLine $bottomClass'>
      <div class='infoButton $cardClass'>".tr("Info")."</div>
    ";
  }
  if ( count($supportContext) == 1)
    $card .= "<div class='supportButton'><span class='ca_href' data-href='{$supportContext[0]['link']}' data-target='_blank'>{$supportContext[0]['text']}</span></div>";
  elseif (!empty($supportContext))
    $card .= "
      <div class='supportButton supportButtonCardContext' id='support".preg_replace("/[^a-zA-Z0-9]+/", "",$Name)."$ID' data-context='".json_encode($supportContext)."'>".tr("Support")."</div>
    ";

  if ( $actionsContext ) {
    if ( count($actionsContext) == 1) {
      $dispText = $actionsContext[0]['alternate'] ?? $actionsContext[0]['text'];
      $card .= "<div class='actionsButton' data-pluginURL='$PluginURL' data-languagePack='$LanguagePack' onclick={$actionsContext[0]['action']}>$dispText</div>";
    }
    else
      $card .= "<div class='actionsButton actionsButtonContext' data-pluginURL='$PluginURL' data-languagePack='$LanguagePack' id='actions".preg_replace("/[^a-zA-Z0-9]+/", "",$Name)."$ID' data-context='".json_encode($actionsContext,JSON_HEX_QUOT | JSON_HEX_APOS)."'>".tr("Actions")."</div>";
  }

  $card .= "<span class='$appType' title='".htmlentities($typeTitle)."'></span>";
  if ( $ca_fav ) {
    $favText = $RepositoryTemplate ? tr("This is your favourite repository") : tr("This application is from your favourite repository");
    $card .= "<span class='favCardBackground' data-repository='".str_replace("'","",$RepoName)."' title='".htmlentities($favText)."'></span>";
  }	else
    $card .= "<span class='favCardBackground' data-repository='".str_replace("'","",$RepoName)."' style='display:none;'></span>";

  $pinStyle = $Pinned ? "" : "display:none;";

  $pindata = (strpos($Repository,"/") !== false) ? $Repository : "library/$Repository";
  $card .= "<span class='pinnedCard' title='".htmlentities(tr("This application is pinned for later viewing"))."' data-pindata='$pindata$SortName' style='$pinStyle'></span>";

  $previousAppName = $Plugin ? $PluginURL : $Name;
  switch ($appType) {
    case 'appDocker':
      $type = "docker";
      break;
    case 'appPlugin':
      $type = "plugin";
      break;
    case 'appLanguage':
      $type = "language";
      break;
    case 'appDriver':
      $type = 'plugin';
      break;
  }
  $checked = $checked ?? "";
  if ($Removable && !($DockerInfo ?? false) && ! $Installed && ! $Blacklist) {
    $card .= "<input class='ca_multiselect ca_tooltip' title='".tr("Check off to select multiple reinstalls")."' type='checkbox' data-name='$previousAppName' data-humanName='$Name' data-type='$type' data-deletepath='$InstallPath' $checked>";
  } elseif ( $actionCentre && $UpdateAvailable ) {
    $card .= "<input class='ca_multiselect ca_tooltip' title='".tr("Check off to select multiple updates")."' type='checkbox' data-name='$previousAppName' data-humanName='$Name' data-type='$type' data-language='$LanguagePack' $checked>";
  }

  $card .= "</div>";
  $card .= "<div class='$cardClass $backgroundClickable'>";
  $card .= "<div class='ca_iconArea'>";
  if ( $DockerHub )
    $imageNoClick = "noClick";

  if ( ! $IconFA )
    $card .= "
      <img class='ca_displayIcon $imageNoClick' src='$Icon' alt='Application Icon'></img>
    ";
  else {
    $displayIcon = $template['IconFA'] ?: $template['Icon'];
    $displayIconClass = startsWith($displayIcon,"icon-") ? $displayIcon : "fa fa-$displayIcon";
    $card  .= "<i class='ca_appPopup $displayIconClass displayIcon $imageNoClick'></i>";
  }
  $card .= "</div>";


  $card .= "
    <div class='ca_applicationName'>$Name
  ";
  if ( $CAComment || $ModeratorComment || $Requires) {
    $commentIcon = "";
    $warning = "";
    if ( $CAComment || $ModeratorComment) {
      $commentIcon = "ca_fa-comment";
      $warning = tr("Click info to see the notes regarding this application");
    }
    if ( $Requires ) {
      if ( $RequiresFile && ! is_file($RequiresFile) ) {
        $commentIcon = "ca_fa-additional";
        $warning = tr("This application has additional requirements");
      }
    }

    $card .= "&nbsp;<span class='$commentIcon cardWarning' title='".htmlentities($warning,ENT_QUOTES)."'></span>";
  }
  $card .= "
        </div>
        <div class='ca_author'>$author</div>
        <div class='cardCategory'>$Category</div>
  ";

  $card .= "
    </div>
    ";

  $Overview = $Overview ?: ($Description ?? "");

  if ( ! $Overview )
    $Overview = tr("No description present");

  $ovr = html_entity_decode($Overview);
  $ovr = trim($ovr);
  $ovr = str_replace(["[","]"],["<",">"],$ovr);
  $ovr = str_replace("\n","<br>",$ovr);

//	$ovr = str_replace("    ","&nbsp;&nbsp;&nbsp;&nbsp;",$ovr);
  $ovr = markdown(strip_tags($ovr,"<br>"));

  $ovr = str_replace("\n","<br>",$ovr);
  $Overview = strip_tags(str_replace("<br>"," ",$ovr));

  if ( ($UninstallOnly ?? false) && $Featured && is_file("/var/log/plugins/".basename($PluginURL)) )
    $Overview = "<span class='featuredIncompatible'>".sprintf(tr("%s is incompatible with your OS version.  Either uninstall %s or update the OS"),$Name,$Name)."</span>&nbsp;&nbsp;$Overview";
  else
    if ( (! $Compatible || ($UninstallOnly ?? false) ) && $Featured )
      $Overview = "<span class='featuredIncompatible'>".sprintf(tr("%s is incompatible with your OS version.  Please update the OS to proceed"),$Name)."</span>&nbsp;&nbsp;$Overview";


  $descClass= $RepositoryTemplate ? "cardDescriptionRepo" : "cardDescription";
  $card .= "<div class='$descClass $backgroundClickable'><div class='cardDesc'>$Overview</div></div>";
  if ( $RecommendedDate ) {
    $card .= "
      <div class='homespotlightIconArea ca_center''>
        <div><img class='spotlightIcon' src='{$caPaths['SpotlightIcon']}' alt='Spotlight'></img></div>
        <div class='spotlightDate'>".tr(date("M Y",$RecommendedDate),0)."</div>
      </div>
    ";
  }
  $card .= "</div>";
  if ( $Installed || $Uninstall ) {
    $flagTextStart = tr("Installed")."<br>";
    $flagTextEnd = "";
  } else {
    $flagTextStart = "&nbsp;";
    $flagTextEnd = "&nbsp;";
  }
  if ( $UpdateAvailable ) {
    $card .= "
      <div class='betaCardBackground'>
        <div class='installedCardText ca_center'>".tr("UPDATED")."</div>
      </div>";
  } elseif ( ($Installed || $Uninstall) && !$actionCentre) {
     $card .= "
       <div class='installedCardBackground'>
         <div class='installedCardText ca_center'>&nbsp;&nbsp;".tr("INSTALLED")."&nbsp;&nbsp;</div>
      </div>";
  } elseif ( $Blacklist ) {
    $card .= "
      <div class='warningCardBackground'>
        <div class='installedCardText ca_center' title='".tr("This application template / has been blacklisted")."'>".tr("Blacklisted")."$flagTextEnd</div>
      </div>
    ";
  } elseif ( $caTemplateExists ) {
    $card .= "
      <div class='warningCardBackground'>
        <div class='installedCardText ca_center' title='".tr("Template already exists in Apps")."'>".tr("Template Exists")."</div>
      </div>
    ";
  } elseif ( isset($Compatible) && ! $Compatible ) {
    $verMsg = $VerMessage ?? tr("This application is not compatible with your version of Unraid");
    $card .= "
      <div class='warningCardBackground'>
        <div class='installedCardText ca_center' title='$verMsg'>$flagTextStart".tr("Incompatible")."$flagTextEnd</div>
      </div>
    ";
  } elseif ( $Deprecated ) {
    $card .= "
      <div class='warningCardBackground'>
        <div class='installedCardText ca_center' title='".tr("This application template has been deprecated")."'>".tr("Deprecated")."$flagTextEnd</div>
      </div>
    ";
  } elseif ( $Official ) {
    $card .= "
      <div class='officialCardBackground'>
        <div class='installedCardText ca_center' title='".tr('This is an official container')."'>".tr("OFFICIAL")."</div>
      </div>
    ";
  } elseif ( $LTOfficial ?? false ) {
    $card .= "
      <div class='LTOfficialCardBackground'>
        <div class='installedCardText ca_center' title='".tr("This is an offical plugin")."'>".tr("OFFICIAL")."</div>
      </div>
    ";
  } elseif ( $Beta ) {
    $card .= "
      <div class='betaCardBackground'>
        <div class='installedCardText ca_center'>".tr("BETA")."</div>
      </div>
    ";
  }/*  elseif ( $RecommendedDate ) {
    $card .= "
      <div class='spotlightCardBackground'>
        <div class='spotlightPopupText' title='".tr("This is a spotlight application")."'></div>
      </div>
    ";
  } */ elseif ( $Trusted ) {
    $card .= "
      <div class='spotlightCardBackground'>
        <div class='installedCardText ca_center' title='".tr("This container is digitally signed")."'>".tr("Digitally Signed")."</div>
      </div>
    ";
  }
  return str_replace(["\t","\n"],"",$card);
}

function displayPopup($template) {
  global $caSettings, $caPaths;

  extract($template);

  if ( !$Private) {
    $RepoName = str_replace("' Repository","",str_replace("'s Repository","",$Repo));
    $RepoName = str_replace("Repository","",$RepoName);
  } else {
    $RepoName = str_replace("' Repository","",str_replace("'s Repository","",$RepoName));
    $Repo = $RepoName;
  }
  if ( $RepoShort ) $RepoName = $RepoShort;

  $FirstSeen = ($FirstSeen < 1433649600 ) ? 1433000000 : $FirstSeen;
  $DateAdded = tr(date("M j, Y",$FirstSeen),0);
  $favRepoClass = ($caSettings['favourite'] == $Repo) ? "fav" : "nonfav";
  $card = "
    <div class='popup'>
    <div><span class='popUpClose'>".tr("CLOSE")."</span></div>
    <div class='ca_popupIconArea'>
      <div class='popupIcon'>$display_icon</div>
      <div class='popupInfo'>
        <div class='popupName'>$Name</div>
    ";
    if ( ! $Language )
      $card .= "<div class='popupAuthorMain'>$Author</div>";

    if ( $actionsContext ) {
      if ( count($actionsContext) == 1 ) {
        $card .= "<div class='actionsPopup'><span onclick={$actionsContext[0]['action']}>".str_replace("ca_red","",$actionsContext[0]['text'])."</span></div>";
      } else {
        $card .= "
          <div class='actionsPopup' id='actionsPopup'>".tr("Actions")."</div>
        ";
      }
    }

    if ( count($supportContext) == 1 )
      $card .= "<div class='supportPopup'><a href='{$supportContext[0]['link']}' target='_blank'><span class='{$supportContext[0]['icon']}'> {$supportContext[0]['text']}</span></a></div>";
    elseif ( count($supportContext) )
      $card.= "<div class='supportPopup' id='supportPopup'><span class='ca_fa-support'> ".tr("Support")."</div>";

    $NoPin = $NoPin ?? false;
    $card .= ($LanguagePack != "en_US" && ! $Blacklist && ! $NoPin) ? "<div class='pinPopup $pinnedClass' title='$pinnedTitle' data-repository='$Repository' data-name='$SortName'><span>$pinned</span></div>" : "";
    if ( ! $caSettings['dockerRunning'] && (! $Plugin && ! $Language) ) {
      $card .= "<div class='ca_red'>".tr("Docker Service Not Enabled - Only Plugins Available To Be Installed Or Managed")."</div>";
    }
    $card .= "
      </div>
    </div>
    <div class='popupDescription popup_readmore'>$display_ovr</div>
  ";
  if ( $Requires && ! is_file($RequiresFile ?? "") )
    $card .= "<div class='additionalRequirementsHeader'>".tr("Additional Requirements")."</div><div class='additionalRequirements'>{$template['Requires']}</div>";

  if ( $Deprecated )
    $ModeratorComment .= "<br>".tr("This application template has been deprecated");
  if ( ! $Compatible && ! ($UnknownCompatible ?? false) )
    $ModeratorComment .= $VerMessage ?? "<br>".tr("This application is not compatible with your version of Unraid.");
  if ( $Blacklist )
    $ModeratorComment .= "<br>".tr("This application template has been blacklisted.");

  if ( $CAComment )
    $ModeratorComment .= "  $CAComment";

  if ( $Language && $LanguagePack !== "en_US" ) {
    $ModeratorComment .= "<a href='$disclaimLineLink' target='_blank'>$disclaimLine1</a>";
  }
  if ( (!$Compatible || ($UninstallOnly ?? false)) && $Featured )
    $ModeratorComment = "<span class='featuredIncompatible'>".sprintf(tr("%s is incompatible with your OS version.  Please update the OS to proceed"),$Name)."</span>";

  if ( $ModeratorComment ) {
    $card .= "<div class='modComment'><div class='moderatorCommentHeader'> ".tr("Attention:")."</div><div class='moderatorComment'>$ModeratorComment</div></div>";
  }

  if ( $RecommendedReason) {
    $RecommendedLanguage = $_SESSION['locale'] ?: "en_US";
    if ( ! $RecommendedReason[$RecommendedLanguage] )
      $RecommendedLanguage = "en_US";

    preg_match_all("/\/\/(.*?)\\\\/m",$RecommendedReason[$RecommendedLanguage],$searchMatches);
    if ( count($searchMatches[1]) ) {
      foreach ($searchMatches[1] as $searchResult) {
        $RecommendedReason[$RecommendedLanguage] = str_replace("//$searchResult\\\\","<a style=cursor:pointer; onclick=doSidebarSearch(&quot;$searchResult&quot;);>$searchResult</a>",$RecommendedReason[$RecommendedLanguage]);
      }
    }

    if ( ! $RecommendedWho ) $RecommendedWho = tr("Unraid Staff");
    $card .= "
      <div class='spotlightPopup'>
        <div class='spotlightIconArea ca_center'>
          <div><img class='spotlightIcon' src='{$caPaths['SpotlightIcon']}' alt='Spotlight'></img></div>
          <div class='spotlightDate spotlightDateSidebar'>".tr(date("M Y",$RecommendedDate),0)."</div>
        </div>
        <div class='spotlightInfoArea'>
          <div class='spotlightHeader'></div>
          <div class='spotlightWhy'>".tr("Why we picked it")."</div>
          <div class='spotlightMessage'>{$RecommendedReason[$RecommendedLanguage]}</div>
          <div class='spotlightWho'>- $RecommendedWho</div>
        </div>
      </div>
    ";
  }
  if ( $Screenshot || $Photo || $Video) {
    if ( $Screenshot || $Photo ) {
      $pictures = $Screenshot ? $Screenshot : $Photo;
      if ( ! is_array($pictures) )
        $pictures = [$pictures];

      $card .= "<div>";
      foreach ($pictures as $shot) {
        $card .= "<a class='screenshot mfp-image' href='".trim($shot)."'><img class='screen' src='".trim($shot)."'></img></a>";
      }
      $card .= "</div>";
    }

    if ( $Video ) {
      if ( $Screenshot || $Photo ) {
        $card .= "<div><hr></div>";
      }
      if ( ! is_array($Video) )
        $Video = [$Video];

      $vidText = (count($Video) == 1) ? "Play Video" : "Play Video %s";
      $card .= "<div>";
      $count = 1;
      foreach ( $Video as $vid ) {
        $card .= "<a class='screenshot videoButton mfp-iframe' href='".trim($vid)."'><div class='ca_fa-film'> ".sprintf(tr($vidText),$count)."</div></a>";
        $count++;
      }
      $card .= "</div>";
    }
  }
  $appType = $Plugin ? tr("Plugin") : tr("Docker");
  $appType = $Language ? tr("Language") : $appType;

  $card .= "
    <div>
    <div class='popupInfoSection'>
      <div class='popupInfoLeft'>
      <div class='rightTitle'>".tr("Details")."</div>
      <table style='display:initial;'>
        <tr><td class='popupTableLeft'>".tr("Application Type")."</td><td class='popupTableRight'>$appType</td></tr>
        <tr><td class='popupTableLeft'>".tr("Categories")."</td><td class='popupTableRight'>$Category</td></tr>
        <tr><td class='popupTableLeft'>".tr("Added")."</td><td class='popupTableRight'>$DateAdded</td></tr>
  ";
  $downloadText = getDownloads($downloads);
  if ($downloadText)
    $card .= "<tr><td class='popupTableLeft'>".tr("Downloads")."</td><td class='popupTableRight'>$downloadText</td></tr>";
  elseif ( isset($topPlugin) )
    $card .= "<tr><td class='popupTableLeft'>".tr("Popularity")."</td><td class='popupTableRight'># $topPlugin</td></tr>";

  if (!$Plugin && !$LanguagePack)
    $card .= "<tr><td class='popupTableLeft'>".tr("Repository")."</td><td class='popupTableRight' style='white-space:nowrap;'>$Repository</td></tr>";
  if ($stars)
    $card .= "<tr><td class='popupTableLeft'>".tr("DockerHub Stars:")."</td><td class='popupTableRight'>$stars <span class='dockerHubStar'></span></td></tr>";
  if ( ! $Plugin && ! $Language ) {
    $tagExplode = explode(":",$Repository);
    $tag = $tagExplode[1] ?? "";
    if ( ! $tag || strtolower($tag) === "latest" ) {
      $lastUpdateMsg = $LastUpdate ? tr(date("M j, Y",$LastUpdate),0) : tr("Unknown");
      $card .= "<tr><td class='popupTableLeft'>".tr("Last Update:")."</td><td class='popupTableRight'><span id='template{$template['ID']}'>$lastUpdateMsg <span class='ca_note'><span class='ca_fa-asterisk'></span></span></span></td></tr>";
    }
  }
  if ( $Plugin && isset($installedVersion) ) {
    $card .= "<tr><td class='popupTableLeft'>".tr("Installed Version")."</td><td class='popupTableRight'>$installedVersion</td></tr>";
    if ( $installedVersion != $pluginVersion ) {
      $card .= "<tr><td class='popupTableLeft'>".tr("Upgrade Version")."</td><td class='popupTableRight'>$pluginVersion</td></tr>";
    }
  }
  if ( $Plugin && ! isset($installedVersion) ) {
    $card .= "<tr><td calss='popupTableLeft'>".tr("Current Version")."</td><td class='popupTableRight'>$pluginVersion</td></tr>";
  }

  if ( $Plugin || ! $Compatible) {
    if ( $MinVer )
      $card .= "<tr><td class='popupTableLeft'>".tr("Min OS")."</td><td class='popupTableRight'>$MinVer</td></tr>";
    if ( $MaxVer )
      $card .= "<tr><td class='popupTableLeft'>".tr("Max OS")."</td><td class='popupTableRight'>$MaxVer</td></tr>";
  }
  $Licence = $Licence ?? ($License ?? "");
  if ( $Licence ) {
    if ( validURL($Licence) )
      $Licence = "<img class='licence' src='$Licence' onerror='this.outerHTML=&quot;<a href=$Licence target=_blank>".tr("Click Here")."</a>&quot;;this.onerror=null;' ></img>";
    
    $card .= "<tr><td class='popupTableLeft'>".tr("Licence")."</td><td class='popupTableRight'>$Licence</td></tr>";
  }
  $card .= "</table>";
  if ( $Repo || $Private ) {
    $remoteIconPrefix = startsWith($ProfileIcon,"http") ? "<a class='screenshot mfp-image' href='$ProfileIcon'>" : "";
    $remoteIconPostfix = $remoteIconPrefix ? "</a>" : "";
    $card .= "
      </div>
      <div class='popupInfoRight'>
          <div class='popupAuthorTitle'>".tr("Maintainer")."</div>
          <div><div class='popupAuthor'>$RepoName</div>
          <div class='popupAuthorIcon'>$remoteIconPrefix<img class='popupAuthorIcon' src='$ProfileIcon' alt='Repository Icon'></img>$remoteIconPostfix</div>
          </div>
          <div class='ca_repoSearchPopUp popupProfile' data-repository='".htmlentities($Repo,ENT_QUOTES)."'>".tr("All Apps")."</div>
          <div class='repoPopup' data-repository='".htmlentities($Repo,ENT_QUOTES)."'>".tr("Profile")."</div>
          <div class='ca_favouriteRepo $favRepoClass' data-repository='".htmlentities($Repo,ENT_QUOTES)."'>".tr("Favourite")."</div>
    ";
  }

  if ( $DonateLink ) {
    $card .= "
      <div class='donateText'>$DonateText</div>
      <div class='donateDiv'><span class='donate'><a href='$DonateLink' target='_blank'>".tr("Donate")."</a></span></div>
    ";
  }

  $card .= "
      </div>
    </div>
    </div>
  ";

  if (is_array($trends) && (count($trends) > 1) ){
    if ( $downloadtrend ) {
      $card .= "
        <div class='charts chartTitle'>".tr("Trends")."</div>
        <div><span class='charts'>Show: <span class='chartMenu selectedMenu' data-chart='trendChart'>".tr("Trend Per Month")."</span><span class='chartMenu' data-chart='downloadChart'>".tr("Downloads Per Month")."</span><span class='chartMenu' data-chart='totalDownloadChart'>".tr("Total Downloads")."</span></div>
        <div>
        <div><canvas id='trendChart' class='caChart' height=1 width=3></canvas></div>
        <div><canvas id='downloadChart' class='caChart' style='display:none;' height=1 width=3</canvas></div>
        <div><canvas id='totalDownloadChart' class='caChart' style='display:none;' height=1 width=3></canvas></div>
        </div>
      ";
    }
  }

  if ( isset($display_changes) ) {
    $card .= "
      <div class='changelogTitle'>".tr("Change Log")."</div>
      <div class='changelogMessage'>$display_changelogMessage</div>
      <div class='changelog popup_readmore'>$display_changes</div>
    ";
  }
  $moderation = readJsonFile($caPaths['statistics']);
  if ( isset($moderation['fixedTemplates'][$Repo][str_replace("library/","",$Repository)]) ) {
    $card .= "<div class='templateErrors'>".tr("Template Errors")."</div>";
    foreach ($moderation['fixedTemplates'][$Repo][str_replace("library/","",$Repository)] as $error) {
      $card .= "<li class='templateErrorsList'>$error</li>";
    }
  }
  if ( ! $Plugin && ! $Language ){
    $card .= "<div><br><span class='ca_note ca_bold'><span class='ca_fa-asterisk'></span> ".tr("Note: All statistics are only gathered every 30 days")."</span></div>";
  }
  if ( $UpdateAvailable ) {
    $card .= "
      <div class='upgradePopupBackground'>
      <div class='upgradePopupText ca_center'>".tr("UPDATED")."</div></div>
    ";
  } elseif ( $Beta ) {
    $card .= "
      <div class='betaPopupBackground'>
      <div class='betaPopupText ca_center'>".tr("BETA")."</div></div>
    ";
  } elseif ( $Installed ) {
    $card .= "
      <div class='installedPopup'>
      <div class='installedPopupText ca_center'>".tr("INSTALLED")."</div></div>
    ";
  }


  return $card;
}
?>
