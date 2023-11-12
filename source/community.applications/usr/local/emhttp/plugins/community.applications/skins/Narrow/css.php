<?php
###############################################################
#                                                             #
# Community Applications copyright 2015-2023, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

require_once "$docroot/plugins/dynamix/include/Wrappers.php";
require_once "$docroot/plugins/community.applications/include/helpers.php";

$dynamix = parse_plugin_cfg("dynamix");

$theme = $dynamix['theme'];

$unRaidSettings = parse_ini_file("/etc/unraid-version");

$unRaid66color = "#FF8C2F";
$linkColor = "#486dba";
$startupColor = "#FF8C2F";
switch ($theme) {
  case 'black':
    $donateBackground = "#ffffff";
    $donateText = "#000000";
    $templateBackground = "#191818";
    $templateHoverBackground = "#121212";
    $templateFavourite = "#333333";
    $hrColor = "#2b2b2b";
    $borderColor = "#2b2b2b";
    $watermarkColor = "rgba(43, 43, 43, 0.4)";
    $aColor = "#00b8d6";
    $sidebarBackground = "#000000";
    $sidebarText = "#f2f2f2";
    $betaPopupOffset = "0";
    $supportPopupText = "#000000";
    $supportPopupBackground = "#ffffff";
    $modCommentBorder = "#cf3131";
    $sidebarCloseBackground = "rgba(0,0,0,0.7)";
    break;
  case 'white':
    $donateBackground = "#1c1b1b";
    $donateText = "#f2f2f2";
    $templateBackground = "#f5f5f5";
    $templateHoverBackground = "#ffffff";
    $templateFavourite = "#d0d0d0";
    $hrColor = "lightgrey";
    $borderColor = "#e3e3e3";
    $watermarkColor = "rgba(211, 211, 211, 0.8)";
    $aColor = "#486dba";
    $sidebarBackground = "#ffffff";
    $sidebarText = "#000000";
    $betaPopupOffset = "0";
    $supportPopupText = "#f2f2f2";
    $supportPopupBackground = "#1c1b1b";
    $modCommentBorder = "#cf3131";
    $sidebarCloseBackground = "rgba(0,0,0,0.7)";
    break;
  case 'azure':
    $donateBackground = "#606e7f";
    $donateText = "#e4e2e4";
    $templateBackground = "transparent";
    $templateHoverBackground = "#edeaef";
    $templateFavourite = "#e0e0e0";
    $hrColor = "#606e7f";
    $border = "#9794a7";
    $watermarkColor = "rgba(96, 110, 127, 0.1)";
    $aColor = "#486dba";
    $sidebarBackground = "#edeaef";
    $sidebarText = "#606e7f";
    $betaPopupOffset = "1.5rem;";
    $supportPopupText = "#1b1d1b";
    $supportPopupBackground = "#ffffff";
    $modCommentBorder = "#cf3131";
    $sidebarCloseBackground = "rgba(0,0,0,0.7)";
    break;
  case 'gray':
    $donateBackground = "#606e7f";
    $donateText = "#1b1d1b";
    $templateBackground = "transparent";
    $templateHoverBackground = "#0c0f0b";
    $templateFavourite = "#2b2b2b";
    $hrColor = "#606e7f";
    $border = "#606e7f";
    $watermarkColor = "rgba(96, 110, 127, 0.1)";
    $aColor = "#00b8d6";
    $sidebarBackground = "#121510";
    $sidebarText = "#f2f2f2";
    $betaPopupOffset = "1.5rem;";
    $supportPopupText = "#1b1d1b";
    $supportPopupBackground = "#ffffff";
    $modCommentBorder = "#cf3131";
    $sidebarCloseBackground = "rgba(0,0,0,0.7)";
    break;
// Use settings for black as a fallback
  default:
    $donateBackground = "#f2f2f2";
    $donateText = "#1c1b1b";
    $templateBackground = "#0f0f0f";
    $templateFavourite = "#333333";
    $hrColor = "#2b2b2b";
    $borderColor = "#2b2b2b";
    $watermarkColor = "rgba(43, 43, 43, 0.4)";
    $aColor = "#00b8d6";
    $sidebarBackground = "#000000";
    $sidebarText = "#f2f2f2";
    $betaPopupOffset = "0";
    $supportPopupText = "#000000";
    $supportPopupBackground = "#ffffff";
    $modCommentBorder = "#cf3131";
    $sidebarCloseBackground = "rgba(0,0,0,.7)";
    break;
}
?>
a {color:<?=$aColor?>;}
.actionsPopup a{text-decoration:none;color:<?=$supportPopupText?>;cursor:pointer;}
.actionsPopup{margin-right:1rem;font-size:1.5rem;line-height:2rem;cursor:pointer;display:inline-block;color:<?=$supportPopupText?>!important;background: <?=$supportPopupBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;}
.actionsPopup:hover{background-color:<?=$unRaid66color?>;}
.actionsButton{margin-left:1rem;line-height:2rem;cursor:pointer;display:inline-block;color:<?=$donateText?>!important;background: <?=$donateBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;}
.actionsButton:hover{background:<?=$unRaid66color?>}
.additionalRequirements{margin-left:2rem;}
.additionalRequirementsHeader{font-size:1.5rem;color:#d67777;font-weight:bold;}
.appIconsPopUp{font-size:2rem !important;cursor:pointer;padding-left:.5rem;padding-right:.5rem;color:default;display:inline-block;margin-right:5rem;}
.appIconsPopUp:hover{text-decoration:none;color:<?=$unRaid66color?>;}
a.appIconsPopUp{text-decoration:none;color:inherit;}
.appDocker{float:right;font-size:2rem;opacity:0.7;margin-left:1rem;}
.appDocker::before{font-family:'Unraid';content:'\e90b';}
.appLanguage{float:right;font-size:2rem;opacity:0.7;margin-left:1rem;}
.appLanguage::before{font-family:'Unraid';content:'\e987';}
.appDriver{float:right;font-size:1.8rem;opacity:0.7;margin-left:1rem;}
.appDriver::before{content:"\f2db";font-family:fontAwesome;}
.appPlugin{float:right;font-size:2rem;opacity:0.7;margin-left:1rem;}
.appPlugin::before{font-family:'Unraid';content:'\e986';}
.appRepository{float:right;font-size:2rem;opacity:0.7;margin-left:1rem;}
.appRepository::before{font-family:'fontAwesome';content:'\f2be';}
.back_to_top_hide{z-index:0;}
.betaCardBackground{clip-path: polygon(0 0,100% 0, 100% 100%);background-color: #FF8C2F;top:0px;height:9rem;width:9rem;position: relative;left:-10rem;margin-right:-9rem;}
.betaPopupBackground{clip-path: polygon(0 0,100% 0, 100% 100%);background-color: #FF8C2F;top:<?=$betaPopupOffset?>;height:9rem;width:9rem;position: absolute;right: 0;}
.betaPopupText{position:absolute;transform:rotate(45deg);-webkit-transform:rotate(45deg);-moz-transform:rotate(45deg);-o-transform: rotate(45deg);color:white;font-size:2rem;position:absolute;top:2.25rem;right:-1rem;width:100%;overflow:hidden;height:2.5rem;}
body.stop-scrolling{height:70%;overflow:inherit;}  /* disable SweetAlert killing the scroll bar ( stops the wiggle ) */
body{scrollbar-gutter:stable;cursor:default !important;}
.body_sidebarScroll{overflow:hidden !important;}
.caChangeLog{cursor:pointer;}
.caChart{display:none;border:none;}
.caHomeSpotlight{height:29rem !important;}
.caMenuDisabled{cursor:default;opacity:0.5;}
.caMenuEnabled{cursor:pointer;opacity:1;}
.ca_applicationName{font-size:2rem;max-height:3rem;overflow:hidden;font-weight:bold;padding-top:1.5rem;margin-left:0.75rem;}
a.ca_appPopup{text-decoration:none;cursor:pointer;}
.ca_appPopup{cursor:pointer;}
div.ca_appPopup{cursor:pointer;}
.ca_author{font-size:1rem;margin-left:0.75rem;;margin-top:0.75rem;}
a.ca_author{text-decoration:none;color:inherit;}
.ca_backgroundClickable{height:18.5rem;cursor:pointer;}
.ca_bold{font-weight:bold;}
.ca_bottomLine{display:block;position:relative;top:18rem;}
.ca_bottomLineSpotLight{top:15rem !important;}
.ca_categories{font-size:1rem;font-style:italic;}
a.ca_categories{text-decoration:none;color:inherit;}
.ca_center{margin:auto;text-align:center;}
.ca_credit{ padding:.5rem 0 1rem 0; font-size:1.5rem;line-height:2rem; font-style:italic;}
.ca_creditheader{ font-size:2rem; padding-top:1rem;}
.ca_discord::before{content:"\e988";font-family:Unraid;}
.ca_discord_popup::before{content:"\e988";font-family:Unraid;font-size:2.2rem;vertical-align:middle;}
img.ca_displayIcon{height:8rem;width:8rem;}
.ca_fa-additional::before{content:"\f055";font-family:fontAwesome;}
.ca_fa-asterisk::before{content:"\f069";font-family:fontAwesome;}
.ca_fa-checkmark{color:#038503;}
.ca_fa-checkmark::before{content:"\f00c";font-family:fontAwesome;}
.ca_fa-comment::before{content:"\f075";font-family:fontAwesome;}
.ca_fa-delete{color:#882626;}
.ca_fa-delete::before{content:"\f00d";font-family:fontAwesome;}
a.ca_fa-delete{text-decoration:none;margin-left:1rem;font-size:2rem;margin-top:-0.25rem;cursor:pointer;float:right;}
.ca_fa-docker::before{font-family:'Unraid';content:'\e90b';}
.ca_fa-edit::before{content:"\f044";font-family:fontAwesome;}
.ca_fa-film::before{content:"\f04b";font-family:fontAwesome;}
.ca_fa-globe::before{content:"\f0ac";font-family:fontAwesome;}
.ca_fa-info::before{content:"\f05a";font-family:fontAwesome;}
.ca_fa-install::before{content:"\f019";font-family:fontAwesome;}
.ca_fa-money::before{content:"\f0d6";font-family:fontAwesome;}
.ca_fa-pluginSettings::before{content:"\f013";font-family:fontAwesome;}
.ca_fa-project::before{content:"\e953";font-family:Unraid;}
.ca_fa-readme::before{content:"\f02d";font-family:fontAwesome;}
.ca_fa-support::before{content:"\f059";font-family:fontAwesome;}
.ca_fa-switchto::before{content:"\e982";font-family:Unraid;}
.ca_fa-template::before{content:"\f08e";font-family:fontAwesome;}
.ca_fa-uninstall::before{content:"\e92f";font-family:Unraid;}
.ca_fa-update::before{content:"\f0ed";font-family:fontAwesome;}
.ca_fa-warning::before{content:"\f071";font-family:fontAwesome;}
.ca_fa-warning{animation:cycleRed 2s infinite;}
.ca_facebook::before{content:"\f09a";font-family:fontAwesome;}
.ca_favouriteRepo{margin-right:1rem;margin-bottom:1rem;font-size:1.5rem;line-height:2rem;cursor:pointer;display:inline-block;color:<?=$supportPopupText?>!important;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;}
.ca_favouriteRepo:hover{text-decoration:none;background-color:<?=$unRaid66color?>;}
.ca_forum::before{content:"\f1cd";font-family:fontAwesome;}
.ca_green{color:#038503;}
.ca_holder{background-color:<?=$templateBackground?>;display:inline-block;float:left;height:20rem;min-width:24rem;max-width:24rem;overflow:hidden;padding:20px;margin-top:0px;margin-bottom:1rem;margin-right:1rem;border:1px solid;border-color:<?=$borderColor?>;cursor:pointer;}
.ca_holder:hover{background-color:<?=$templateHoverBackground?>;box-shadow: 5px 5px 5px -3px rgba(255,140,47,.5);}
.dockerHubHolder {background-color:<?=$templateBackground?>;display:inline-block;float:left;height:20rem;min-width:24rem;max-width:24rem;overflow:hidden;padding:20px;margin-top:0px;margin-bottom:1rem;margin-right:1rem;border:1px solid;border-color:<?=$borderColor?>;cursor:pointer;}
.dockerHubHolder:hover{background-color:<?=$templateHoverBackground?>;}
.ca_holderFav{background-color:<?=$templateFavourite?> !important;}
.ca_homeTemplates{display:flex;flex-wrap:wrap;height:24.5rem;overflow:hidden;}
.ca_homeTemplatesHeader{font-size:2rem;margin-top:1rem;margin-bottom:0.5rem;}
.ca_homeTemplatesLine2{font-size:1.5rem;margin-bottom:1rem;}
.ca_href{cursor:pointer;}
.ca_icon{width:6.4rem;height:6.4rem;}
.ca_iconArea{width:100%;height:6.4rem;margin-top:-2rem;margin-left:1rem;}
.ca_italic{font-style:italic;}
ul.caMenu{list-style-type: none;margin:0px 0px 20px 0px;padding: 0;font-size:1.5rem;}
li.caMenuItem{padding:0px 0px 5px 0px; width:fit-content;}
.ca_multiselect{float:right;cursor:pointer;padding-right:1rem;}
.ca_NoAppsFound{font-size:3rem;margin:auto;text-align:center;}
.ca_NoDockerAppsFound{font-size:3rem;margin:auto;text-align:center;}
.ca_non_favouriteRepo{font-size:2rem;cursor:pointer;margin-left:2.5rem !important;padding-right:.5rem;cursor:pointer;padding:.3rem;}
.ca_non_favouriteRepo::before{content:"\f2be";font-family:fontAwesome;}
ul.nonselectMenu{list-style-type: none;margin:0px 0px 20px 0px;padding: 0;font-size:1.5rem;}
.ca_normal{font-size:1.4rem !important;}
.ca_note{color:#d67777;}
.ca_popupIconArea{height:14.4rem;}
.ca_private::after{content:"\f069";font-family:fontAwesome;}
.ca_private{color:#882626;}
.ca_profile::before{content:"\f2bb";font-family:fontAwesome;}
.ca_readmore{color:<?=$unRaid66color?>;font-size:1.5rem !important;cursor:pointer;padding-left:.5rem;padding-right:.5rem;padding-top:1rem;display:inline-block;margin-bottom:2rem;font-family:unset;}
.ca_readmore::before{font-family:fontAwesome;}
.ca_readmore:hover{text-decoration:none;color:#d67777;}
.ca_reddit::before{content:"\f281";font-family:fontAwesome;}
.ca_red{color:#882626;}
.ca_repoPopup{display:inline-block;text-decoration:none!important;cursor:pointer;color:inherit;}
a.ca_repoPopup:hover{color:<?=$unRaid66color?>;}
.ca_repoSearch{font-size:2rem;cursor:pointer;padding-left:.5rem;padding-right:.5rem;cursor:pointer;padding:.3rem;}
.ca_repoSearch::after{content:"\f002";font-family:fontAwesome;}
.ca_repoSearchPopup{font-size:2rem;cursor:pointer;padding-left:.5rem;padding-right:.5rem;cursor:pointer;padding:.3rem;}
.ca_serverWarning{color:#cecc31}
.ca_stat{color:coral; font-size:1.5rem;line-height:1.7rem;}
.ca_table{padding:.5rem 2rem .5rem 0; font-size:1.5rem;}
.ca_template{color:#606E7F;border-radius:0px 0px 2rem 2rem;display:inline-block;text-align:left;overflow:auto;height:27rem;width:36rem;padding-left:.5rem;padding-right:.5rem; background-color:#DDDADF;}
.ca_templatesDisplay{display:flex;flex-wrap:wrap;}
.ca_template_icon{color:#606E7F;width:37rem;float:left;display:inline-block;background-color: #C7C5CB;margin:0px 0px 0px 0px;height:15rem;padding-top:1rem;}
.ca_twitter::before{content:"\f099";font-family:fontAwesome;}
.ca_webpage::before{content:"\f0ac";font-family:fontAwesome;}
.changelogMessage{font-size:1rem;line-height:1rem;margin-top:1rem;}
.cardCategory{font-size:1rem;margin-left:0.75rem;}
.cardDescription{cursor: pointer;display: block;position: relative;top:0.5rem;max-height: 7rem;overflow: hidden;}
.cardDesc{display:inline-block;max-height:6rem;overflow:hidden;}
.card_readmore{color:<?=$unRaid66color?>;}
.cardWarning{font-size:1.5rem;color:#bdbd14;}
#Category{font-size:2rem;margin-bottom:0.5rem;}
.changelogTitle{font-size:2rem;line-height:2rem;margin-top:2rem;font-weight:normal;}
.changelog{font-size:1.2rem;line-height:1.4rem;margin-top:1.5rem;}
.chartMenu{padding-left:2rem;cursor:pointer;}
.chartMenu:hover{color:<?=$unRaid66color?>;}
.charts{font-size:1.5rem;}
.chartTitle{margin-top:1.5rem;font-size:2.5rem;}
.checkForUpdates{width:100%; display:none;padding-bottom:20px;}
ul.context{list-style-type:none;padding:0;margin:0;}
a.context{text-decoration:none;color:currentColor;margin:5px;}
li.context{margin-top:0.5rem;margin-bottom:0.5rem;font-size:1.5rem;}
li.context:hover{color:<?=$unRaid66color?>;}
.credits{overflow:scroll;overflow-x:hidden; overflow-y:auto;margin:auto;width:600px;padding-bottom:50px;background-image: linear-gradient(to bottom, royalblue 50%, #b5b52c 50%);-webkit-background-clip: text;-moz-background-clip: text;background-clip: text;color: transparent;}
li.debugging{cursor:pointer;}
.dimScreenShot{overflow:unset !important;}
.disabledIcon{color:#040404;font-size:2.5rem;}
i.displayIcon{font-size:5.5rem;color:#626868;padding-top:0.25rem;}
img.licence{max-height:2rem;max-width:10rem;}
img.displayIcon{height:6.4rem;width:6.4rem;border-radius:1rem 1rem 1rem 1rem;}
#cookieWarning{display:none;}
.docker::after{font-family:'Unraid';content:'\e90b';font-size:2.5rem;}
.dockerCardBackground{height:18.5rem;cursor:default !important;}
.dockerHubStar{font-size:1rem;}
.dockerHubStar::before{content:"\e95a";font-family:UnRaid;}
.dockerSearch{display:inline-block;float:right;}
.donate{color:<?=$supportPopupText?>!important;background: <?=$supportPopupBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 200% 100%;  border-radius: 15px;  color: #000000;  padding: 1px 10px 1px 10px;}
.donate:hover{text-decoration:none;background-color:<?=$unRaid66color?>;}
.donateDiv{margin-top:1rem;}
.donate a{text-decoration:none;color:<?=$supportPopupText?>}
.donateArea{margin-top:2rem;}
.donateLink{font-size:1.2rem;}
.donateText{margin-top:2rem;}
.enabledIcon{cursor:pointer;color:<?=$unRaid66color?> !important;}
.fav{background-color:#009900;}
.nonfav{background-color: <?=$supportPopupBackground?>;}
.favCardSpotlight{left:-40.25rem !important;}
.favCardBackground{float:right;color:#bb0000;padding-top:.25rem;margin-left:1rem;}
.favCardBackground::before{content:"\f004";font-family:fontAwesome;}
.featuredIncompatible{color:#FF8C2F;}
.homeMore{color:<?=$unRaid66color?>;cursor:pointer;}
.homeMore:hover{color:#d67777;}
.homespotlightIconArea{display: inline-block;position: relative;top: -25rem;left: 24rem;cursor:pointer;}
.hoverMenu{color:<?=$unRaid66color?> !important;}
.infoButton{line-height:2rem;cursor:pointer;display:inline-block;color:<?=$donateText?>!important;background: <?=$donateBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;}
.infoButton:hover{background:<?=$unRaid66color?>}
.infoButton_docker{line-height:2rem;cursor:pointer;display:inline-block;color:<?=$donateText?>!important;background: <?=$donateBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;}
.infoButton_docker:hover{background:<?=$unRaid66color?>}
.initDockerSearch{cursor:pointer;text-decoration:none;font-size:1.5rem;}
.initDockerSearch:hover{color:<?=$unRaid66color?>}
a.initDockerSearch{cursor:pointer;text-decoration:none;color:unset;}
.installedCardBackground{clip-path: polygon(0 0,100% 0, 100% 100%);background-color: #322fff;top:0px;height:9rem;width:9rem;position: relative;left:-10rem;margin-right:-9rem;}
.installedCardText{text-transform:uppercase;position:absolute;transform:rotate(45deg);-webkit-transform:rotate(45deg);-moz-transform:rotate(45deg);-o-transform: rotate(45deg);color:white;font-size:2rem;position:absolute;top:2rem;right:0rem;width:6rem;overflow:hidden;height:2.5rem; display:inline;}
input[type=checkbox]{width:2rem;height:2rem;margin-left:0rem;}
input[type=button]{background:none;font-size:1.5rem;}
input:hover[type=button]{color:<?=$unRaid66color?>;background:none !important;}
input:hover[type=button][disabled]{background:none !important;color:currentColor !important;font-size:1.5rem;}
input[type=button][disabled]{background:none;}
.installedPopup{clip-path: polygon(0 0,100% 0, 100% 100%);background-color: clip-path: polygon(0 0,100% 0, 100% 100%);background-color: #322fff;top:<?=$betaPopupOffset?>;height:9rem;width:9rem;position: absolute;right: 0;;top:<?=$betaPopupOffset?>;height:9rem;width:9rem;position: absolute;right: 0;}
.installedPopupText{position:absolute;transform:rotate(45deg);-webkit-transform:rotate(45deg);-moz-transform:rotate(45deg);-o-transform: rotate(45deg);color:white;font-size:2rem;position:absolute;top:2.5rem;right:-1rem;width:100%;overflow:hidden;height:2rem;}
.linkButton{color:<?=$supportPopupText?>!important;background: <?=$supportPopupBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 200% 100%;  border-radius: 15px;  color: #000000;  padding: 1px 10px 1px 10px;}
.linkButton:hover{text-decoration:none;background-color:<?=$unRaid66color?>;}
.linkButton a{text-decoration:none;color:<?=$supportPopupText?>}
.maxPerPage{float:right;line-height:2rem;cursor:pointer;display:inline-block;color:<?=$donateText?>!important;background: <?=$donateBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;}
.maxPerPage:hover{text-decoration:none;background-color:<?=$unRaid66color?>;}
.mainArea{position:absolute;left:18.5rem;right:0px;top:2rem;display:block;overflow-x:hidden;min-height:90vh;}
.menuHeader{ font-size:2rem; margin-bottom:1rem;margin-top:1rem;}
.menuItems{position:absolute;top:2rem;left:10px;width:14rem;height:auto;}
.mobileHolderFix{margin-bottom:2rem !important;}
.modComment{padding:2rem;border:1px solid;border-color:<?=$modCommentBorder?>;}
.moderatorCommentHeader{font-size:2rem;font-weight:normal;}
.moderatorCommentHeader:before{content:"\e97d";font-family:Unraid;color:<?=$modCommentBorder?>;}
.moderatorComment{font-size:1.2rem;font-style:italic;line-height:1.5rem;}
.moderationLink{color:<?=$linkColor?>;font-weight:normal;}
.multi_installDiv{width:100%; display:none;padding-bottom:20px;}
.myReadmoreButton{color:#6363ca;}
.noClick{cursor:default !important;}
.notice.shift{margin-top:0px;}
.officialCardBackground{clip-path: polygon(0 0,100% 0, 100% 100%);background-color: #854153;top:0px;height:9rem;width:9rem;position: relative;left:-10rem;margin-right:-9rem;}
.officialPopupText{position:absolute;transform:rotate(45deg);-webkit-transform:rotate(45deg);-moz-transform:rotate(45deg);-o-transform: rotate(45deg);color:white;font-size:2rem;position:absolute;top:2.25rem;right:-1rem;width:100%;overflow:hidden;height:2.5rem;}
.LTOfficialCardBackground{clip-path: polygon(0 0,100% 0, 100% 100%);background-color: #F15A2C;top:0px;height:9rem;width:9rem;position: relative;left:-10rem;margin-right:-9rem;}
.ltofficialPopupText{position:absolute;transform:rotate(45deg);-webkit-transform:rotate(45deg);-moz-transform:rotate(45deg);-o-transform: rotate(45deg);color:white;font-size:2rem;position:absolute;top:2.25rem;right:-1rem;width:100%;overflow:hidden;height:2.5rem;}
p {margin:auto;text-align:left;margin-bottom:10px;} /* override dynamix styling for popup */
.pageDots::after{content:"...";}
.pageDots{color:grey;cursor:default;}
.pageLeft::after{content:"\f137";font-family:fontAwesome;}
.pageNavigation{font-size:2.5rem;margin-bottom:3rem;}
.pageNavNoClick{font-size:2.5rem;cursor:default !important;opacity:0.3;}
.pageNavNoClick:hover{color:<?=$sidebarText?> !important;opacity:0.3;}
.pageNumber{margin-left:1rem;margin-right:1rem;cursor:pointer;text-decoration:none !important;}
.pageNumber:hover{color:<?=$unRaid66color?>;}
.pageRight::after{content:"\f138";font-family:fontAwesome;}
.pageSelected{cursor:default;color:<?=$unRaid66color?>;}
.pinnedCard{float:right;color:#1fa67a;font-size:1.75rem;}
.pinnedCard::before{content:"\f08d";font-family:fontAwesome;}
.pinPopup{margin-right:1rem;font-size:1.5rem;line-height:2rem;cursor:pointer;display:inline-block;color:<?=$supportPopupText?>!important;background: <?=$supportPopupBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;}
.pinPopup:hover{text-decoration:none;background-color:<?=$unRaid66color?>;}
.plugin::after{font-family:'Unraid';content:'\e986';font-size:2.5rem;}
.popup-donate{background:white;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),              linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 200% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;  text-shadow: 1px 1px 5px #666;}
.popupAuthor{font-size:1.7rem;line-height:2rem;;margin-bottom:0.5rem;margin-top:0.5rem;display:inline-block;}
.popupAuthorMain{font-size:1.7rem;line-height:2rem;margin-bottom:0.5rem;margin-top:0.5rem;}
.popupAuthorIcon{display:inline-block;float:right;}
img.popupAuthorIcon{height:7.2rem;width:7.2rem;border-radius:1rem 1rem 1rem 1rem;}
.popupAuthorTitle{font-size:2.5rem;margin-top:2rem;margin-bottom:2rem;}
.popupCategory{font-size:1rem;line-height:1rem;}
.popUpBack{font-size:1.5rem;color:#f34646;font-weight:bold;cursor:pointer;top:-2rem;display:inline-block;padding-left:3rem;}
.popUpClose{margin-top:2rem;font-size:1.5rem;color:#f34646;font-weight:bold;cursor:pointer;display:inline-block;}
.popUpClose:hover{color:<?=$unRaid66color?>;}
.popUpDeprecated{color:#FF8C2F;}
.popupDescriptionArea{display:block;font-size:1rem;color:<?=$sidebarText?>;}
.popupDescription{font-size:1.5rem;line-height:1.7rem !important;margin-top:1.5rem;margin-left:1rem;margin-right:1rem;margin-bottom:0px;}
.popupIcon{display:inline-block;}
i.popupIcon{color:#626868;font-size:10rem;padding-left:1rem;padding-top:2.2rem;}
img.popupIcon{width:10rem;height:10rem;padding:0.3rem;margin-top:2.2rem;border-radius:1rem 1rem 1rem 1rem;}
.popupInfo{position:absolute;top:10rem;left:15rem;}
.popupInfoLeft{min-width:45%;max-width:45%;width:50rem;float:left;display:inline-block;margin-right:10px;}
.popupInfoRight{min-width:45%;max-width:45%;float:left;display:inline-block;}
.popupInfoSection{line-height:2rem;font-size:1.5rem;display:inline-block;}
.popUpLink{cursor:pointer;color:<?$aColor?>;}
a.popUpLink{text-decoration:none;}
a.popUpLink:hover{color:<?=$unRaid66color?>;}
.popupName{display:block;font-size:3rem;line-height:4rem;font-weight:bold;max-height:4rem;overflow:hidden;}
.popupProfile{margin-right:1rem;margin-bottom:1rem;font-size:1.5rem;line-height:2rem;cursor:pointer;display:inline-block;color:<?=$supportPopupText?>!important;background: <?=$supportPopupBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;}
.popupProfile:hover{text-decoration:none;background-color:<?=$unRaid66color?>;}
.popupRepoDescription{font-size:1.5rem;margin-bottom:1rem;}
.popupTable td {width:30%;text-align:left;}
.popupTable{font-size:1.5rem;width:55rem;margin-top:0px;margin-left:auto;}
.popupTableLeft{vertical-align:top;padding-right:15px;}
.popupTableRight{max-width:20rem;overflow:hidden;}
.popupTitle{margin:auto;text-align:center;font-weight:bold;font-size:2rem;line-height}
.popup{margin:1.5rem;margin-top:-2rem;}
a.popup-donate{text-decoration:none;font-style:italic;color:black;font-size:1.5rem;}
a.popup-donate:hover{color:<?=$donateText?>;background-color:<?=$unRaid66color?>}
.readmore-js-collapsed{-webkit-mask-image: -webkit-gradient(linear, left top, left bottom, from(rgba(0,0,0,1)), to(rgba(0,0,0,0.1)));}
.repoDonateText{margin-bottom:0.5rem;}
.repoLinks{margin-top:3rem;margin-left:1.5rem;}
.repoPopup{margin-right:1rem;font-size:1.5rem;line-height:2rem;cursor:pointer;display:inline-block;color:<?=$supportPopupText?>!important;background: <?=$supportPopupBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;}
.repoPopup:hover{text-decoration:none;background-color:<?=$unRaid66color?>;}
.repoStats{font-size:2rem;margin-top:2rem;}
.repoTable{margin-top:15px;width:70%;}
.repoLeft{width:50%;}
.repoLinkArea{margin-top:1.5rem;}
.repoRight{text-align:right;}
.rightTitle{font-size:2.5rem;margin-top:2rem;margin-bottom:2rem;}
.screen{border:1px solid; border-radius: 5px 5px 5px 5px; border-color:<?=$unRaid66color?>; vertical-align:top; min-width:8rem;max-width:12rem;height:8rem;margin:10px;}
.screen:hover{opacity:50%}
.screenshot{cursor:pointer;}
.searchArea{z-index:2;width:auto;position:static;}
.searchSubmit{font-family:'FontAwesome';width:2rem;min-width:2rem;height:3.4rem;font-size:1.1rem;position:relative;padding-top:1.1rem;padding-bottom:1rem;padding-right:1rem;background:<?=$templateHoverBackground?>;border:none;cursor:pointer;background:<?=$templateHoverBackground?>;}
#searchBox{margin-left:0rem;margin-right:0;margin-bottom:1rem;top:-.6rem;border:none;padding:0.6rem;background:<?=$templateHoverBackground?>;padding-right:0.5rem;}
#searchButton:hover{color:<?=$unRaid66color?>;}
.sidebar{z-index:998;position:fixed;top:0;right:0;bottom:1.6rem;margin-bottom:10px;width:100%;height:100vh;display:none;background-color:<?=$sidebarCloseBackground?>;}
.sidebarClose{width: 100%;height: 100vh;position: fixed;top: 0;left: 0;}
.selectedMenu{color:<?=$unRaid66color?>;font-weight:bold;}
.sidenavHide{width:0px;}
.sidenavShow{width:70rem; }
.sidenav{position:fixed;top:0;right:0;bottom:1.6rem;margin-bottom:10px;background-color:<?=$sidebarBackground?>;color:<?=$sidebarText?>;overflow-x:hidden;transition:0.5s;padding-top:60px;overflow-y:scroll;}
#sortIconArea{padding-bottom:1rem;}
.sortIcons{font-size:1.2rem;margin-right:10px;margin-left:10px;cursor:pointer;text-decoration:none !important;color:<?=$sidebarText?>;}
.sortIcons:hover{color:<?=$unRaid66color?>;}
.specialCategory{font-size:1.5rem;}
.spinner{z-index:999999 !important;} /* ensure always ontop */
.spinnerBackground{position:fixed;top:0;left:0;width:100%;height:100vh;display:none;background:transparent;z-index:9999;}
.spotlightDate{font-size:1.5rem;margin-left:-2rem;}
.spotlightDateSidebar{margin-left:0.5rem !important;}
.spotlightCardBackground{clip-path: polygon(0 0,100% 0, 100% 100%);background-color: #009900;top:0px;height:9rem;width:9rem;position: relative;left:-10rem;margin-right:-9rem;}
.spotlightHome{min-width:36rem !important;max-width:36rem !important;height:24rem !important;margin-bottom:1rem;}
.spotlightHeader{font-size:2rem;}
.spotlightIconArea{display:inline-block;float:left;width:10rem;}
.spotlightIcon{height:15rem;margin-bottom:-2.5rem;margin-left:-2rem;margin-top:-3rem;}
.spotlightInfoArea{margin-left:2rem;padding-left:10rem;margin-top:2rem;}
.spotlightPopup{display:inline-block;margin-top:0.75rem;}
.spotlightWho{font-style:italic;}
.spotlightWhy{font-weight:bold;font-size:1.6rem;line-height:1.8rem;}
.spotlightMessage{margin-top:0.8rem;line-height:1.5rem;}
.spotlightMessage a{color:<?=$unRaid66color?>;text-decoration:none;}
.spotlightMessage a:hover{color:#d67777;}
ul.subCategory{list-style-type:none;margin-left:2rem;padding:0px;cursor:pointer;display:none;}
.supportButton{line-height:2rem;cursor:pointer;display:inline-block;background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px; ;  padding: 1px 10px 1px 10px;margin-left:1rem;border-style:solid;border-width:1px;}
.supportButton a{text-decoration:none;}
.supportButton:hover{background:<?=$unRaid66color?>;border-color:<?=$unRaid66color?>;}
.supportLink{color:inherit;padding-left:.5rem;padding-right:.5rem;}
.supportPopup a{text-decoration:none;color:inherit;cursor:pointer;}
.supportPopup{margin-right:1rem;font-size:1.5rem;line-height:2rem;cursor:pointer;display:inline-block;background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  padding: 1px 10px 1px 10px;border-style:solid;border-width:1px;}
.supportPopup:hover{background-color:<?=$unRaid66color?>;}
.sweet-alert table{margin-top:0px}
table tbody td {line-height:1.8rem;}
table {background-color:transparent;}
#templates_content{overflow-x:hidden;margin-bottom:3rem;}
.templateErrors{font-size:2.5rem;margin-top:2rem;}
.templateErrorsList{margin-left:3rem;}
.templateSearch{cursor:pointer;font-size:1.5rem;}
.templateSearch:hover{color:<?=$unRaid66color?>;}
.trendingDown::before{content:"\f063";font-family:fontAwesome;}
.trendingUp::before{content:"\f062";font-family:fontAwesome;}
.unraidIcon{margin-top:4rem;}
.upgradePopupBackground{clip-path: polygon(0 0,100% 0, 100% 100%);background-color: #FF8C2F;top:<?=$betaPopupOffset?>;height:9rem;width:9rem;position: absolute;right: 0;}
.upgradePopupText{position:absolute;transform:rotate(45deg);-webkit-transform:rotate(45deg);-moz-transform:rotate(45deg);-o-transform: rotate(45deg);color:white;font-size:2rem;position:absolute;top:2.25rem;right:-1rem;width:100%;overflow:hidden;height:2.5rem;}
.vid{display:inline-block;font-size:8rem;position:relative;top:-0.5rem;color:<?=$sidebarText?>;opacity:70%;padding-right:10px;}
.vid:hover{color:<?=$unRaid66color;?>}
.videoButton{margin-left:1rem;line-height:2rem;cursor:pointer;display:inline-block;color:<?=$donateText?>!important;background: <?=$donateBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 100% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;text-decoration:none !important;}
.videoButton:hover{background:<?=$unRaid66color?>}
.warningCardBackground{clip-path: polygon(0 0,100% 0, 100% 100%);background-color: #810000;top:0px;height:9rem;width:9rem;position: relative;left:-10rem;margin-right:-9rem;}
#warningNotAccepted{display:none;}

@keyframes cycleRed {
  0% {color: inherit;}
  50% {color: #882626;}
}
.awesomplete [hidden] {display: none;}
.awesomplete .visually-hidden {position: absolute;clip: rect(0, 0, 0, 0);}
.awesomplete {display: inline-block;position: relative;color: red;}
.awesomplete > input {display: block;}
.awesomplete > ul {position: absolute;left: 0;z-index: 1;min-width: 100%;box-sizing: border-box;list-style: none;padding: 0;margin: 0;background: #fff;}
.awesomplete > ul:empty {display: none;}
.awesomplete > ul {border-radius: .3em;margin: .2em 0 0;background: hsla(0,0%,100%);background: linear-gradient(to bottom right, white, hsla(0,0%,100%));border: 1px solid rgba(0,0,0,.3);box-shadow: .05em .2em .6em rgba(0,0,0,.2);text-shadow: none;}
@supports (transform: scale(0)) {.awesomplete > ul {transition: .3s cubic-bezier(.4,.2,.5,1.4);transform-origin: 1.43em -.43em;}
  .awesomplete > ul[hidden],.awesomplete > ul:empty {opacity: 0;transform: scale(0);display: block;transition-timing-function: ease;}
}
/* Pointer */
.awesomplete > ul:before {content: "";position: absolute;top: -.43em;left: 1em;width: 0; height: 0;padding: .4em;background: white;border: inherit;border-right: 0;border-bottom: 0;-webkit-transform: rotate(45deg);transform: rotate(45deg);}
.awesomplete > ul > li {position: relative;padding: .2em .5em;cursor: pointer;}
.awesomplete > ul > li:hover {background: hsl(200, 40%, 80%);color: black;}
.awesomplete > ul > li[aria-selected="true"] {background: hsl(205, 40%, 40%);color: white;}
.awesomplete mark {background: hsl(65, 100%, 50%);}
.awesomplete li:hover mark {background: hsl(68, 100%, 41%);}
.awesomplete li[aria-selected="true"] mark {background: hsl(86, 100%, 21%);color: inherit;}

/* Magnific Popup CSS */
.mfp-bg {top: 0;left: 0;width: 100%;height: 100%;z-index: 1042;overflow: hidden;position: fixed;background: #0b0b0b;opacity: 0.8; }
.mfp-wrap {top: 0;left: 0;width: 100%;height: 100%;z-index: 1043;position: fixed;outline: none !important;-webkit-backface-visibility: hidden; }
.mfp-container {text-align: center;position: absolute;width: 100%;height: 100%;left: 0;top: 0;padding: 0 8px;box-sizing: border-box; }
.mfp-container:before {content: '';display: inline-block;height: 100%;vertical-align: middle; }
.mfp-align-top .mfp-container:before {display: none; }
.mfp-content {position: relative;display: inline-block;vertical-align: middle;margin: 0 auto;text-align: left;z-index: 1045; }
.mfp-inline-holder .mfp-content,.mfp-ajax-holder .mfp-content {width: 100%;cursor: auto; }
.mfp-ajax-cur {cursor: progress; }
.mfp-zoom-out-cur, .mfp-zoom-out-cur .mfp-image-holder .mfp-close {cursor: -moz-zoom-out;cursor: -webkit-zoom-out;cursor: zoom-out; overflow:unset !important; }
.mfp-zoom {cursor: pointer;cursor: -webkit-zoom-in;cursor: -moz-zoom-in;cursor: zoom-in; }
.mfp-auto-cursor .mfp-content {cursor: auto; }
.mfp-close,.mfp-arrow,.mfp-preloader,.mfp-counter {-webkit-user-select: none;-moz-user-select: none;user-select: none; }
.mfp-loading.mfp-figure {display: none; }
.mfp-hide {display: none !important; }
.mfp-preloader {color: #CCC;position: absolute;top: 50%;width: auto;text-align: center;margin-top: -0.8em;left: 8px;right: 8px;z-index: 1044; }
.mfp-preloader a {color: #CCC; }
.mfp-preloader a:hover {color: #FFF; }
.mfp-s-ready .mfp-preloader {display: none; }
.mfp-s-error .mfp-content {display: none; }
button.mfp-close,button.mfp-arrow {overflow: visible;cursor: pointer;background: transparent;border: 0;-webkit-appearance: none;display: block;outline: none;padding: 0;z-index: 1046;box-shadow: none;touch-action: manipulation; }
button::-moz-focus-inner {padding: 0;border: 0; }
.mfp-close {cursor:pointer !important;width: fit-content !important;height: 44px;line-height: 44px;position: absolute;right: 0;top: 0;text-decoration: none;text-align: center;opacity: 0.65;padding: 0 0 18px 10px;color:#f34646;font-style: normal;font-size: 2rem;font-family: Arial, Baskerville, monospace; }
/* .mfp-close:hover,.mfp-close:focus {opacity: 1; } */
.mfp-close:hover {color:<?=$unRaid66color?> !important;}
.mfp-close:active {top: 1px; }
.mfp-close-btn-in .mfp-close {color: #333; }
.mfp-image-holder .mfp-close,.mfp-iframe-holder .mfp-close {color: #f34646;font-weight:bold;right: -6px;text-align: center;width: 100%; }
.mfp-counter {position: absolute;top: 0;right: 0;color: #CCC;font-size: 12px;line-height: 18px;white-space: nowrap; }
.mfp-arrow {position: absolute;opacity: 0.65;margin: 0;top: 50%;margin-top: -55px;padding: 0;width: 90px;height: 110px;-webkit-tap-highlight-color: transparent; }
.mfp-arrow:active {margin-top: -54px; }
.mfp-arrow:hover,.mfp-arrow:focus {opacity: 1; }
.mfp-arrow:before,.mfp-arrow:after {content: '';display: block;width: 0;height: 0;position: absolute;left: 0;top: 0;margin-top: 35px;margin-left: 35px;    border: medium inset transparent; }
.mfp-arrow:after {border-top-width: 13px;border-bottom-width: 13px;top: 8px; }
.mfp-arrow:before {border-top-width: 21px;border-bottom-width: 21px;opacity: 0.7; }
.mfp-arrow-left {left: 0; }
.mfp-arrow-left:after {border-right: 17px solid #FFF;margin-left: 31px; }
.mfp-arrow-left:before {margin-left: 25px;border-right: 27px solid #3F3F3F; }
.mfp-arrow-right {right: 0; }
.mfp-arrow-right:after {border-left: 17px solid #FFF;margin-left: 39px; }
.mfp-arrow-right:before {border-left: 27px solid #3F3F3F; }
.mfp-iframe-holder {padding-top: 40px;padding-bottom: 40px; }
.mfp-iframe-holder .mfp-content {line-height: 0;width: 100%;max-width: 900px; }
.mfp-iframe-holder .mfp-close {top: -40px; }
.mfp-iframe-scaler {width: 100%;height: 0;overflow: hidden;padding-top: 56.25%; }
.mfp-iframe-scaler iframe {position: absolute;display: block;top: 0;left: 0;width: 100%;height: 100%;box-shadow: 0 0 8px rgba(0, 0, 0, 0.6);  background: #000; }
/* Main image in popup */
img.mfp-img {width: auto;max-width: 100%;height: auto;display: block;line-height: 0;box-sizing: border-box;padding: 40px 0 40px;margin: 0 auto; }
/* The shadow behind the image */
.mfp-figure {line-height: 0; }
.mfp-figure:after { content: '';position: absolute;left: 0;top: 40px;bottom: 40px;display: block;right: 0;width: auto;height: auto;z-index: -1;  box-shadow: 0 0 8px rgba(0, 0, 0, 0.6);background: #444; }
.mfp-figure small {color: #BDBDBD;display: block;font-size: 12px;line-height: 14px; }
.mfp-figure figure {margin: 0; }
.mfp-bottom-bar {margin-top: -36px;position: absolute;top: 100%;left: 0;width: 100%;cursor: auto; }
.mfp-title {text-align: left;line-height: 18px;color: #F3F3F3;word-wrap: break-word;padding-right: 36px; }
.mfp-image-holder .mfp-content {max-width: 100%; }
.mfp-gallery .mfp-image-holder .mfp-figure {cursor: pointer; }
@media screen and (max-width: 800px) and (orientation: landscape), screen and (max-height: 300px) {
 /**
  * Remove all paddings around the image on small screen
 */
  .mfp-img-mobile .mfp-image-holder {padding-left: 0;padding-right: 0; }
  .mfp-img-mobile img.mfp-img {padding: 0; }
  .mfp-img-mobile .mfp-figure:after {top: 0;bottom: 0; }
  .mfp-img-mobile .mfp-figure small {display: inline;margin-left: 5px; }
  .mfp-img-mobile .mfp-bottom-bar {background: rgba(0, 0, 0, 0.6);bottom: 0;margin: 0;top: auto;padding: 3px 5px;position: fixed;box-sizing: border-box; }
    .mfp-img-mobile .mfp-bottom-bar:empty {padding: 0; }
  .mfp-img-mobile .mfp-counter {right: 5px;top: 3px; }
  .mfp-img-mobile .mfp-close {top: 0;right: 0;width: 35px;height: 35px;line-height: 35px;background: rgba(0, 0, 0, 0.6);position: fixed;text-align: center;padding: 0; }
}
@media all and (max-width: 900px) {
  .mfp-arrow {-webkit-transform: scale(0.75);transform: scale(0.75); }
  .mfp-arrow-left {-webkit-transform-origin: 0;transform-origin: 0; }
  .mfp-arrow-right {-webkit-transform-origin: 100%;transform-origin: 100%; }
  .mfp-container {padding-left: 6px;padding-right: 6px; }
}
/* overlay at start */
.mfp-fade.mfp-bg {opacity: 0;-webkit-transition: all 0.5s ease-out;-moz-transition: all 0.5s ease-out;transition: all 0.5s ease-out;}
/* overlay animate in */
.mfp-fade.mfp-bg.mfp-ready {opacity: 0.8; overflow:unset !important;}
/* overlay animate out */
.mfp-fade.mfp-bg.mfp-removing {opacity: 0;}
/* content at start */
.mfp-fade.mfp-wrap .mfp-content {opacity: 0;-webkit-transition: all 0.5s ease-out;-moz-transition: all 0.5s ease-out;transition: all 0.5s ease-out;}
/* content animate it */
.mfp-fade.mfp-wrap.mfp-ready .mfp-content {opacity: 1;}
/* content animate out */
.mfp-fade.mfp-wrap.mfp-removing .mfp-content {opacity: 0;}