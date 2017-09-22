<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2017, Andrew Zawadzki #
#                                                             #
###############################################################

$plugin = "community.applications";

$communityPaths['tempFiles']                     = "/tmp/community.applications/tempFiles";                            /* path to temporary files */
$communityPaths['persistentDataStore']           = $communityPaths['tempFiles']."/community.applications.datastore";   /* anything in this folder is NOT deleted upon an update of templates */
$communityPaths['templates-community']           = $communityPaths['tempFiles']."/templates-community-apps";           /* templates and temporary files stored here.  Deleted every update of applications */
$communityPaths['community-templates-url']       = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Repositories.json";
$communityPaths['Repositories']                  = $communityPaths['tempFiles']."/Repositories.json";
$communityPaths['community-templates-info']      = $communityPaths['tempFiles']."/templates.json";                     /* json file containing all of the templates */
$communityPaths['community-templates-displayed'] = $communityPaths['tempFiles']."/displayed.json";                     /* json file containing all of the templates currently displayed */
$communityPaths['application-feed']              = "https://tools.linuxserver.io/unraid-docker-templates.json";        /* path to the application feed */
$communityPaths['application-feed-last-updated'] = "https://tools.linuxserver.io/unraid-docker-templates.json?last_updated=1";
$communityPaths['lastUpdated']                   = $communityPaths['tempFiles']."/lastUpdated.json";
$communityPaths['lastUpdated-old']               = $communityPaths['tempFiles']."/lastUpdated-old.json";
$communityPaths['lastUpdated-sync']              = $communityPaths['tempFiles']."/lastUpdated-sync";
$communityPaths['appFeedOverride']               = $communityPaths['tempFiles']."/WhatWouldChodeDo";                   /* flag to override the app feed temporarily */
$communityPaths['addConverted']                  = $communityPaths['tempFiles']."/TrippingTheRift";                    /* flag to indicate a rescan needed since a dockerHub container was added */
$communityPaths['convertedTemplates']            = "/boot/config/plugins/".$plugin."/private/";                        /* path to private repositories on flash drive */
$communityPaths['dockerSearchResults']           = $communityPaths['tempFiles']."/docker_search.json";                 /* The displayed docker search results */
$communityPaths['dockerfilePage']                = $communityPaths['tempFiles']."/dockerfilePage";                     /* the downloaded webpage to scrape the dockerfile from */
$communityPaths['moderationURL']                 = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Moderation.json";
$communityPaths['moderation']                    = $communityPaths['tempFiles']."/moderation.json";                    /* json file that has all of the moderation */
$communityPaths['unRaidVersion']                 = "/etc/unraid-version";
$communityPaths['logos']                         = $communityPaths['tempFiles']."/logos.json";
$communityPaths['unRaidVars']                    = "/var/local/emhttp/var.ini";
$communityPaths['updateErrors']                  = $communityPaths['tempFiles']."/updateErrors.txt";
$communityPaths['dockerUpdateStatus']            = "/var/lib/docker/unraid-update-status.json";
$communityPaths['pinnedRam']                     = $communityPaths['tempFiles']."/pinned_apps.json"; # the ram copy of pinned apps for speed
$communityPaths['pinned']                        = "/boot/config/plugins/community.applications/pinned_apps.json"; # stored on flash instead of docker.img so it will work without docker running
$communityPaths['appOfTheDay']                   = $communityPaths['tmpeFiles']."/appOfTheDay.json";
$communityPaths['defaultSkin']                   = "/usr/local/emhttp/plugins/community.applications/skins/default.skin";
$communityPaths['legacySkin']                    = "/usr/local/emhttp/plugins/community.applications/skins/legacy.skin";
$communityPaths['LegacyMode']                    = $communityPaths['templates-community']."/legacyModeActive";
$communityPaths['statistics']                    = $communityPaths['tempFiles']."/statistics.json";
$communityPaths['pluginSettings']                = "/boot/config/plugins/$plugin/$plugin.cfg";
$communityPaths['totalDeprecated_txt']           = $communityPaths['tempFiles']."/deprecated.txt";
$communityPaths['totalIncompatible_txt']         = $communityPaths['tempFiles']."/incompatible.txt";
$communityPaths['blacklisted_txt']               = $communityPaths['tempFiles']."/blacklist.txt";
$communityPaths['fixedTemplates_txt']            = $communityPaths['tempFiles']."/caFixed.txt";
$communityPaths['invalidXML_txt']                = $communityPaths['tempFiles']."/invalidxml.txt";
$communityPaths['noSupport_txt']                 = $communityPaths['tempFiles']."/nosupport.txt";
$communityPaths['PluginInstallPending']          = $communityPaths['tempFiles']."/plugininstallpending.txt";
$communityPaths['warningAccepted']               = "/boot/config/plugins/community.applications/accepted";
$communityPaths['CAdeveloper']                   = "/boot/config/plugins/community.applications/developer";
$communityPaths['pollingDisabled']               = "/boot/config/plugins/community.applications/pollingDisabled";
$communityPaths['pluginDupes']                   = $communityPaths['tempFiles']."/pluginDupes";
$communityPaths['newFlag']                       = $communityPaths['tempFiles']."/newFlag";  # flag file to indicate that the "New" Category is being displayed

$infoFile                                        = $communityPaths['community-templates-info'];
?>