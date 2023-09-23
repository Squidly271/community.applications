<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2023, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";
require_once "$docroot/plugins/dynamix/include/Wrappers.php";

$CA = "community.applications";

if ( ! isset($dockerManPaths) ) {
  $dockerManPaths = [
    'autostart-file' => "/var/lib/docker/unraid-autostart",
    'update-status'  => "/var/lib/docker/unraid-update-status.json",
    'template-repos' => "/boot/config/plugins/dockerMan/template-repos",
    'templates-user' => "/boot/config/plugins/dockerMan/templates-user",
    'templates-usb'  => "/boot/config/plugins/dockerMan/templates",
    'images'         => "/var/lib/docker/unraid/images",
    'user-prefs'     => "/boot/config/plugins/dockerMan/userprefs.cfg",
    'plugin'         => "$docroot/plugins/dynamix.docker.manager",
    'images-ram'     => "$docroot/state/plugins/dynamix.docker.manager/images",
    'webui-info'     => "$docroot/state/plugins/dynamix.docker.manager/docker.json"
  ];
}

$caPaths['tempFiles']                           = "/tmp/$CA/tempFiles";                            /* path to temporary files */
$caPaths['flashDrive']                          = "/boot/config/plugins/$CA";
$caPaths['templates-community']                 = $caPaths['tempFiles']."/templates-community-apps";           /* templates and temporary files stored here.  Deleted every update of applications */
$caPaths['community-templates-url']             = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Repositories.json";
$caPaths['PublicServiceAnnouncement']           = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/PublicServiceAnnouncement.txt";
$caPaths['community-templates-info']            = $caPaths['tempFiles']."/templates.json";                     /* json file containing all of the templates */
$caPaths['haveTemplates']												= $caPaths['tempFiles']."/haveTemplates";
$caPaths['community-templates-displayed']       = $caPaths['tempFiles']."/displayed.json";                     /* json file containing all of the templates currently displayed */
$caPaths['community-templates-allSearchResults']= $caPaths['tempFiles']."/allSearchResults.json";
$caPaths['community-templates-catSearchResults']= $caPaths['tempFiles']."/catSearchResults.json";
$caPaths['startupDisplayed']                    = $caPaths['tempFiles']."/startupDisplayed";
$caPaths['repositoriesDisplayed']               = $caPaths['tempFiles']."/repositoriesDisplayed.json";
$caPaths['application-feed']                    = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/applicationFeed.json";
$caPaths['application-feed-last-updated']       = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/applicationFeed-lastUpdated.json";
$caPaths['application-feedBackup']              = "https://dnld.lime-technology.com/appfeed/master/applicationFeed.json";
$caPaths['application-feed-last-updatedBackup'] = "https://dnld.lime-technology.com/appfeed/master/applicationFeed-lastUpdated.json";
$caPaths['appFeedDownloadError']                = $caPaths['tempFiles']."/downloaderror.txt";
$caPaths['categoryList']                        = $caPaths['tempFiles']."/categoryList.json";
$caPaths['repositoryList']                      = $caPaths['tempFiles']."/repositoryList.json";
$caPaths['extraBlacklist']                      = $caPaths['tempFiles']."/extraBlacklist.json";
$caPaths['extraDeprecated']                     = $caPaths['tempFiles']."/extraDeprecated.json";
$caPaths['sortOrder']                           = $caPaths['tempFiles']."/sortOrder.json";
$caPaths['currentServer']                       = $caPaths['tempFiles']."/currentServer.txt";
$caPaths['lastUpdated']                         = $caPaths['tempFiles']."/lastUpdated.json";
$caPaths['lastUpdated-old']                     = $caPaths['tempFiles']."/lastUpdated-old.json";
$caPaths['addConverted']                        = $caPaths['tempFiles']."/TrippingTheRift";                    /* flag to indicate a rescan needed since a dockerHub container was added */
$caPaths['convertedTemplates']                  = "{$caPaths['flashDrive']}/private/";                        /* path to private repositories on flash drive */
$caPaths['moderationURL']                       = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Moderation.json";
$caPaths['moderation']                          = $caPaths['tempFiles']."/moderation.json";                    /* json file that has all of the moderation */
$caPaths['unRaidVersion']                       = "/etc/unraid-version";
$caPaths['unRaidVars']                          = "/var/local/emhttp/var.ini";
$caPaths['network_ini']                         = "/var/local/emhttp/network.ini";
$caPaths['docker_cfg']                          = "/boot/config/docker.cfg";
$caPaths['dockerUpdateStatus']                  = "/var/lib/docker/unraid-update-status.json";
$caPaths['pinnedV2']                            = "{$caPaths['flashDrive']}/pinned_appsV2.json";
$caPaths['appOfTheDay']                         = $caPaths['tempFiles']."/appOfTheDay.json";
$caPaths['statistics']                          = $caPaths['tempFiles']."/statistics.json";
$caPaths['statisticsURL']                       = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/statistics.json";
$caPaths['pluginSettings']                      = "{$caPaths['flashDrive']}/community.applications.cfg";
$caPaths['fixedTemplates_txt']                  = $caPaths['tempFiles']."/caFixed.txt";
$caPaths['invalidXML_txt']                      = $caPaths['tempFiles']."/invalidxml.txt";
$caPaths['warningAccepted']                     = "{$caPaths['flashDrive']}/accepted";
$caPaths['pluginWarning']                       = "{$caPaths['flashDrive']}/plugins_accepted";
$caPaths['pluginDupes']                         = $caPaths['tempFiles']."/pluginDupes.json";
$caPaths['pluginTempDownload']                  = $caPaths['tempFiles']."/pluginTempFile.plg";
$caPaths['dockerManTemplates']                  = $dockerManPaths['templates-user'];
$caPaths['iconHTTPSbase']                       = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/https-images/";
$caPaths['disksINI']                            = "/var/local/emhttp/disks.ini";
$caPaths['dynamixSettings']                     = "/boot/config/plugins/dynamix/dynamix.cfg";
$caPaths['installedLanguages']                  = "/boot/config/plugins";
$caPaths['dynamixUpdates']                      = "/tmp/plugins";
$caPaths['LanguageErrors']                      = "https://squidly271.github.io/languageErrors.html";
$caPaths['CA_languageBase']                     = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/languages/";
$caPaths['CA_logs']                             = "/tmp/CA_logs";
$caPaths['logging']                             = "{$caPaths['CA_logs']}/ca_log.txt";
$caPaths['languageInstalled']                   = "/usr/local/emhttp/languages/";
$caPaths['updateTime']                          = "/tmp/$CA/checkForUpdatesTime"; # can't be in /tmp/community.applications/tempFiles because new feed downloads erases everything there
$caPaths['updateRunning']                       = "/tmp/$CA/updateRunning";
$caPaths['dockerWriteTest']                     = "/var/lib/docker/communityApplicationsWriteTest";
$caPaths['info']                                = $caPaths['tempFiles']."/info.json";
$caPaths['dockerSearchResults']                 = $caPaths['tempFiles']."/dockerSearch.json";
$caPaths['dockerSearchInstall']                 = $caPaths['tempFiles']."/dockerConvert.xml";
$caPaths['dockerSearchActive']                  = $caPaths['tempFiles']."/dockerSearchActive";
$caPaths['dockerConvertFlash']                  = $dockerManPaths['templates-user']."/my-CA_TEST_CONTAINER_DOCKERHUB.xml";
$caPaths['pluginPending']                       = "/tmp/plugins/pluginPending/";

$dynamixSettings = parse_plugin_cfg("dynamix");
$caPaths['SpotlightIcon']					 							= "https://github.com/Squidly271/community.applications/raw/master/webImages/spotlight_{$dynamixSettings['theme']}.png";
$caPaths['SpotlightIcon-backup']                = "https://s3.amazonaws.com/dnld.lime-technology.com/community-apps/assets/spotlight/spotlight_{$dynamixSettings['theme']}.png";
?>