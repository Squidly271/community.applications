<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2019, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

$plugin = "community.applications";

$communityPaths['tempFiles']                           = "/tmp/$plugin/tempFiles";                            /* path to temporary files */
$communityPaths['templates-community']                 = $communityPaths['tempFiles']."/templates-community-apps";           /* templates and temporary files stored here.  Deleted every update of applications */
$communityPaths['community-templates-url']             = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Repositories.json";
$communityPaths['PublicServiceAnnouncement']           = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/PublicServiceAnnouncement.txt";
$communityPaths['Repositories']                        = $communityPaths['tempFiles']."/Repositories.json";
$communityPaths['community-templates-info']            = $communityPaths['tempFiles']."/templates.json";                     /* json file containing all of the templates */
$communityPaths['community-templates-displayed']       = $communityPaths['tempFiles']."/displayed.json";                     /* json file containing all of the templates currently displayed */
$communityPaths['application-feed']                    = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/applicationFeed.json";
$communityPaths['application-feed-last-updated']       = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/applicationFeed-lastUpdated.json";
$communityPaths['application-feedBackup']              = "https://s3.amazonaws.com/dnld.lime-technology.com/appfeed/master/applicationFeed.json";
$communityPaths['application-feed-last-updatedBackup'] = "https://s3.amazonaws.com/dnld.lime-technology.com/appfeed/master/applicationFeed-lastUpdated.json";
$communityPaths['appFeedBackupUSB']                    = "/boot/config/plugins/$plugin/applicationFeed.json";
$communityPaths['currentServer']                       = $communityPaths['tempFiles']."/currentServer.txt";
$communityPaths['onlineCAVersion']                     = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/caVersion";
$communityPaths['lastUpdated']                         = $communityPaths['tempFiles']."/lastUpdated.json";
$communityPaths['lastUpdated-old']                     = $communityPaths['tempFiles']."/lastUpdated-old.json";
$communityPaths['addConverted']                        = $communityPaths['tempFiles']."/TrippingTheRift";                    /* flag to indicate a rescan needed since a dockerHub container was added */
$communityPaths['convertedTemplates']                  = "/boot/config/plugins/$plugin/private/";                        /* path to private repositories on flash drive */
$communityPaths['dockerSearchResults']                 = $communityPaths['tempFiles']."/docker_search.json";                 /* The displayed docker search results */
$communityPaths['dockerfilePage']                      = $communityPaths['tempFiles']."/dockerfilePage";                     /* the downloaded webpage to scrape the dockerfile from */
$communityPaths['Dockerfile']                          = $communityPaths['tempFiles']."/Dockerfile";
$communityPaths['moderationURL']                       = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Moderation.json";
$communityPaths['moderation']                          = $communityPaths['tempFiles']."/moderation.json";                    /* json file that has all of the moderation */
$communityPaths['unRaidVersion']                       = "/etc/unraid-version";
$communityPaths['logos']                               = $communityPaths['tempFiles']."/logos.json";
$communityPaths['unRaidVars']                          = "/var/local/emhttp/var.ini";
$communityPaths['dockerUpdateStatus']                  = "/var/lib/docker/unraid-update-status.json";
$communityPaths['pinned']                              = "/boot/config/plugins/$plugin/pinned_apps.json"; # stored on flash instead of docker.img so it will work without docker running
$communityPaths['appOfTheDay']                         = $communityPaths['tempFiles']."/appOfTheDay.json";
$communityPaths['statistics']                          = $communityPaths['tempFiles']."/statistics.json";
$communityPaths['statisticsURL']                       = "https://raw.githubusercontent.com/Squidly271/AppFeed/master/statistics.json";
$communityPaths['pluginSettings']                      = "/boot/config/plugins/$plugin/$plugin.cfg";
$communityPaths['fixedTemplates_txt']                  = $communityPaths['tempFiles']."/caFixed.txt";
$communityPaths['invalidXML_txt']                      = $communityPaths['tempFiles']."/invalidxml.txt";
$communityPaths['warningAccepted']                     = "/boot/config/plugins/$plugin/accepted";
$communityPaths['pluginDupes']                         = $communityPaths['tempFiles']."/pluginDupes.json";
$communityPaths['appFeedDownloadError']                = $communityPaths['tempFiles']."/appfeedTemporaryFileForAnalysis";
$communityPaths['defaultSkin']                         = "/usr/local/emhttp/plugins/$plugin/skins/default.skin";
$communityPaths['defaultSkinPHP']                      = $communityPaths['defaultSkin'].".php";

?>