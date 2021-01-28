<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2021, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################

$CA = "community.applications";

$caPaths['tempFiles']                           = "/tmp/$CA/tempFiles";                            /* path to temporary files */
$caPaths['flashDrive']                          = "/boot/config/plugins/$CA";
$caPaths['templates-community']                 = $caPaths['tempFiles']."/templates-community-apps";           /* templates and temporary files stored here.  Deleted every update of applications */
$caPaths['community-templates-url']             = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Repositories.json";
$caPaths['PublicServiceAnnouncement']           = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/PublicServiceAnnouncement.txt";
$caPaths['community-templates-info']            = $caPaths['tempFiles']."/templates.json";                     /* json file containing all of the templates */
$caPaths['community-templates-displayed']       = $caPaths['tempFiles']."/displayed.json";                     /* json file containing all of the templates currently displayed */
$caPaths['community-templates-allSearchResults']= $caPaths['tempFiles']."/allSearchResults.json";
$caPaths['community-templates-catSearchResults']= $caPaths['tempFiles']."/catSearchResults.json";
$caPaths['startupDisplayed']                    = $caPaths['tempFiles']."/startupDisplayed";
$caPaths['repositoriesDisplayed']               = $caPaths['tempFiles']."/repositoriesDisplayed.json";
$caPaths['application-feed']                    = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/applicationFeed.json";
$caPaths['application-feed-last-updated']       = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/applicationFeed-lastUpdated.json";
$caPaths['application-feedBackup']              = "https://s3.amazonaws.com/dnld.lime-technology.com/appfeed/master/applicationFeed.json";
$caPaths['application-feed-last-updatedBackup'] = "https://s3.amazonaws.com/dnld.lime-technology.com/appfeed/master/applicationFeed-lastUpdated.json";
$caPaths['appFeedDownloadError']                = $caPaths['tempFiles']."/downloaderror.txt";
$caPaths['categoryList']                        = $caPaths['tempFiles']."/categoryList.json";
$caPaths['repositoryList']                      = $caPaths['tempFiles']."/repositoryList.json";
$caPaths['currentServer']                       = $caPaths['tempFiles']."/currentServer.txt";
$caPaths['lastUpdated']                         = $caPaths['tempFiles']."/lastUpdated.json";
$caPaths['lastUpdated-old']                     = $caPaths['tempFiles']."/lastUpdated-old.json";
$caPaths['addConverted']                        = $caPaths['tempFiles']."/TrippingTheRift";                    /* flag to indicate a rescan needed since a dockerHub container was added */
$caPaths['convertedTemplates']                  = "{$caPaths['flashDrive']}/private/";                        /* path to private repositories on flash drive */
$caPaths['dockerSearchResults']                 = $caPaths['tempFiles']."/docker_search.json";                 /* The displayed docker search results */
$caPaths['dockerfilePage']                      = $caPaths['tempFiles']."/dockerfilePage";                     /* the downloaded webpage to scrape the dockerfile from */
$caPaths['Dockerfile']                          = $caPaths['tempFiles']."/Dockerfile";
$caPaths['moderationURL']                       = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Moderation.json";
$caPaths['moderation']                          = $caPaths['tempFiles']."/moderation.json";                    /* json file that has all of the moderation */
$caPaths['unRaidVersion']                       = "/etc/unraid-version";
$caPaths['logos']                               = $caPaths['tempFiles']."/logos.json";
$caPaths['unRaidVars']                          = "/var/local/emhttp/var.ini";
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
?>