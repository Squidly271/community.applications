<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2018, Andrew Zawadzki #
#                                                             #
###############################################################

$plugin = "community.applications";

$communityPaths['tempFiles']                     = "/tmp/$plugin/tempFiles";                            /* path to temporary files */
$communityPaths['persistentDataStore']           = $communityPaths['tempFiles']."/community.applications.datastore";   /* anything in this folder is NOT deleted upon an update of templates */
$communityPaths['templates-community']           = $communityPaths['tempFiles']."/templates-community-apps";           /* templates and temporary files stored here.  Deleted every update of applications */
$communityPaths['community-templates-url']       = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Repositories.json";
$communityPaths['PublicServiceAnnouncement']     = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/PublicServiceAnnouncement.txt";
$communityPaths['Repositories']                  = $communityPaths['tempFiles']."/Repositories.json";
$communityPaths['community-templates-info']      = $communityPaths['tempFiles']."/templates.json";                     /* json file containing all of the templates */
$communityPaths['community-templates-displayed'] = $communityPaths['tempFiles']."/displayed.json";                     /* json file containing all of the templates currently displayed */
$communityPaths['application-feed']              = "https://tools.linuxserver.io/unraid-docker-templates.json";        /* path to the application feed */
$communityPaths['application-feed-last-updated'] = "https://tools.linuxserver.io/unraid-docker-templates.json?last_updated=1";
$communityPaths['lastUpdated']                   = $communityPaths['tempFiles']."/lastUpdated.json";
$communityPaths['lastUpdated-old']               = $communityPaths['tempFiles']."/lastUpdated-old.json";
$communityPaths['appFeedOverride']               = $communityPaths['tempFiles']."/WhatWouldChodeDo";                   /* flag to override the app feed temporarily */
$communityPaths['addConverted']                  = $communityPaths['tempFiles']."/TrippingTheRift";                    /* flag to indicate a rescan needed since a dockerHub container was added */
$communityPaths['convertedTemplates']            = "/boot/config/plugins/$plugin/private/";                        /* path to private repositories on flash drive */
$communityPaths['dockerSearchResults']           = $communityPaths['tempFiles']."/docker_search.json";                 /* The displayed docker search results */
$communityPaths['dockerfilePage']                = $communityPaths['tempFiles']."/dockerfilePage";                     /* the downloaded webpage to scrape the dockerfile from */
$communityPaths['Dockerfile']                    = $communityPaths['tempFiles']."/Dockerfile";
$communityPaths['moderationURL']                 = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Moderation.json";
$communityPaths['moderation']                    = $communityPaths['tempFiles']."/moderation.json";                    /* json file that has all of the moderation */
$communityPaths['unRaidVersion']                 = "/etc/unraid-version";
$communityPaths['logos']                         = $communityPaths['tempFiles']."/logos.json";
$communityPaths['unRaidVars']                    = "/var/local/emhttp/var.ini";
$communityPaths['updateErrors']                  = $communityPaths['tempFiles']."/updateErrors.txt";
$communityPaths['dockerUpdateStatus']            = "/var/lib/docker/unraid-update-status.json";
$communityPaths['pinned']                        = "/boot/config/plugins/$plugin/pinned_apps.json"; # stored on flash instead of docker.img so it will work without docker running
$communityPaths['appOfTheDay']                   = $communityPaths['tempFiles']."/appOfTheDay.json";
$communityPaths['LegacyMode']                    = $communityPaths['templates-community']."/legacyModeActive";
$communityPaths['statistics']                    = $communityPaths['tempFiles']."/statistics.json";
$communityPaths['pluginSettings']                = "/boot/config/plugins/$plugin/$plugin.cfg";
$communityPaths['fixedTemplates_txt']            = $communityPaths['tempFiles']."/caFixed.txt";
$communityPaths['invalidXML_txt']                = $communityPaths['tempFiles']."/invalidxml.txt";
$communityPaths['PluginInstallPending']          = $communityPaths['tempFiles']."/plugininstallpending.txt";
$communityPaths['warningAccepted']               = "/boot/config/plugins/$plugin/accepted";
$communityPaths['pluginDupes']                   = $communityPaths['tempFiles']."/pluginDupes";
$communityPaths['newFlag']                       = $communityPaths['tempFiles']."/newFlag";  # flag file to indicate that the "New" Category is being displayed
$communityPaths['dontAllowInstalls']             = $communityPaths['tempFiles']."/dontAllowInstalls"; # when file exists, the icons for install/edit/etc will not appear
$communityPaths['appFeedDownloadError']          = $communityPaths['tempFiles']."/appfeedTemporaryFileForAnalysis";
$communityPaths['legacySkin']                    = "/usr/local/emhttp/plugins/$plugin/skins/legacy.skin";
$communityPaths['defaultSkin']                   = "/usr/local/emhttp/plugins/$plugin/skins/default.skin";
$communityPaths['defaultSkinPHP']                = $communityPaths['defaultSkin'].".php";
$communityPaths['legacyTemplatesTmp']            = $communityPaths['tempFiles']."/pathsToTemplates.json";

$infoFile                                        = $communityPaths['community-templates-info'];

?>