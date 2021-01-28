<?php
###############################################################
#                                                             #
# Community Applications copyright 2015-2021, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################

header("Content-type: text/css; charset: UTF-8");

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

require_once "$docroot/plugins/dynamix/include/Wrappers.php";

$dynamix = parse_plugin_cfg("dynamix");

$theme = $dynamix['theme'];

$unRaidSettings = parse_ini_file("/etc/unraid-version");

$unRaid66 = version_compare($unRaidSettings['version'],"6.5.3",">");
$unRaid67 = version_compare($unRaidSettings['version'],"6.7.0-rc4",">");
$unRaid69 = version_compare($unRaidSettings['version'],"6.9.0-beta1",">");
$unRaid66color = "#FF8C2F";
$linkColor = "#486dba";
$startupColor = "#FF8C2F";
switch ($theme) {
	case 'black':
		$donateBackground = "#f2f2f2";
		$donateText = "#1c1b1b";
		$templateBackground = "#191818";
		$hrColor = "#2b2b2b";
		$borderColor = "#2b2b2b";
		$watermarkColor = "rgba(43, 43, 43, 0.4)";
		$tooltipsterBackground = "linear-gradient(90deg,#303030 0,#707070)";
		$tooltipsterContent = "#f2f2f2";
		break;
	case 'white':
		$donateBackground = "#1c1b1b";
		$donateText = "#f2f2f2";
		$templateBackground = "#f5f5f5";
		$hrColor = "lightgrey";
		$borderColor = "lightgrey";
		$watermarkColor = "rgba(211, 211, 211, 0.8)";
		$tooltipsterBackground = "linear-gradient(90deg,#d2d2d2 0,#f2f2f2)";
		$tooltipsterContent = "#101010";
		break;
	case 'azure':
		$donateBackground = "#606e7f";
		$donateText = "#e4e2e4";
		$templateBackground = "#e2e0e2";
		$hrColor = "#606e7f";
		$border = "#606e7f";
		$watermarkColor = "rgba(96, 110, 127, 0.1)";
		$tooltipsterBackground = "linear-gradient(90deg,#d2d2d2 0,#f2f2f2)";
		$tooltipsterContent = "#101010";
		break;
	case 'gray':
		$donateBackground = "#606e7f";
		$donateText = "#1b1d1b";
		$templateBackground = "#1b1d1b";
		$hrColor = "#606e7f";
		$border = "#606e7f";
		$watermarkColor = "rgba(96, 110, 127, 0.1)";
		$tooltipsterBackground = "linear-gradient(90deg,#303030 0,#707070)";
		$tooltipsterContent = "#f2f2f2";
		break;
// Use settings for black as a fallback
	default:
		$donateBackground = "#f2f2f2";
		$donateText = "#1c1b1b";
		$templateBackground = "#191818";
		$hrColor = "#2b2b2b";
		$borderColor = "#2b2b2b";
		$watermarkColor = "rgba(43, 43, 43, 0.4)";
		break;
}
?>
.tooltipster-box{background:<?=$tooltipsterBackground?>!important}
.tooltipster-content{color:<?=$tooltipsterContent?>!important}
body.stop-scrolling{height:100%;overflow:auto}  /* disable SweetAlert killing the scroll bar ( stops the wiggle ) */
.sweet-alert table{margin-top:0px}
.popupHolder,.tooltipster-box {max-height:460px;}
.sweet-overlay{background-color:rgba(0, 0, 0, 0) !important;} /* don't dim if spinner is displayed */
.popupTable{font-size:1.5rem;width:45rem;margin-top:0px;margin-left:auto;}
.popupTable td {width:30%;text-align:left;}
.ca_LanguageDisclaimer {cursor:pointer;font-size:.9rem;}
.ca_LanguageDisclaimer:hover {color:<?=$linkColor?>;}
a.ca_LanguageDisclaimer {text-decoration:none;}
.ca_display_beta {font-size:1rem;color:#FF8C2F;}
.ca_display_beta::after{content:"(BETA)"}
.ca_iconArea {width:100%;height:8rem;margin:1rem;}
.ca_icon {width:8rem;height:9rem;display:inline-block;padding-top:0.5rem;padding-left:1rem;}
.ca_infoArea {height:10rem;margin:1rem;display:inline-block;position:absolute;width:auto;}
.ca_applicationInfo {display:inline-block;position:absolute;width:25rem;}
.ca_categories {font-size:1rem;font-style:italic;}
a.ca_categories {text-decoration:none;color:inherit;}
.ca_applicationName {font-size:1.5rem;}
a.ca_applicationName {text-decoration:none;color:inherit;}
.ca_author {cursor:pointer;font-size:1rem;font-style:italic;}
a.ca_author {text-decoration:none;color:inherit;}
.ca_categoryLink {color:<?=$linkColor?>;font-weight:normal;}
a.ca_categoryLink {text-decoration:none;color:inherit;}
.ca_descriptionArea {margin:1rem;width:auto;height:4rem;position:relative;margin-top:-11rem;}
.ca_descriptionAreaRepository {margin:1rem;width:auto;height:4rem;position:relative;margin-top:-12rem;}
.ca_holderDocker {background-color:<?=$templateBackground?>;display:inline-block;float:left;height:24rem;min-width:37rem;max-width:50rem;flex-grow:1;flex-basis:37rem;overflow:hidden;padding:0px;margin-left:0px;margin-top:0px;margin-bottom:1rem;margin-right:1rem;font-size:1.2rem;border:1px solid;border-color:<?=$borderColor?>;border-radius:10px 10px 10px 10px;}
.ca_holderPlugin {background-color:<?=$templateBackground?>;display:inline-block;float:left;height:24rem;min-width:37rem;max-width:50rem;flex-grow:1;flex-basis:37rem;overflow:hidden;padding:0px;margin-left:0px;margin-top:0px;margin-bottom:1rem;margin-right:1rem;font-size:1.2rem;border:1px solid;border-color:<?=$borderColor?>;border-radius:10px 10px 10px 10px;}
.ca_holderLanguage {background-color:<?=$templateBackground?>;display:inline-block;float:left;height:24rem;min-width:37rem;max-width:50rem;flex-grow:1;flex-basis:37rem;overflow:hidden;padding:0px;margin-left:0px;margin-top:0px;margin-bottom:1rem;margin-right:1rem;font-size:1.2rem;border:1px solid;border-color:<?=$borderColor?>;border-radius:10px 10px 10px 10px;}
.ca_holderRepository {background-color:<?=$templateBackground?>;display:inline-block;float:left;height:24rem;min-width:37rem;max-width:50rem;flex-grow:1;flex-basis:37rem;overflow:hidden;padding:0px;margin-left:0px;margin-top:0px;margin-bottom:1rem;margin-right:1rem;font-size:1.2rem;border:1px solid;border-color:<?=$borderColor?>;border-radius:10px 10px 10px 10px;}
<?if (! $unRaid69 ):?>
.ca_holderPlugin::before{position:relative;float:right;margin-top:1rem;margin-right:3rem;font-family:'fontAwesome';content:'\f12e';font-size:8rem;color:<?=$watermarkColor?>;}
<?else:?>
.ca_holderPlugin::before{position:relative;float:right;margin-top:1rem;margin-right:3rem;font-family:'Unraid';content:'\e986';font-size:8rem;color:<?=$watermarkColor?>;}
<?endif;?>

<?if ( $unRaid67 ):?>
.ca_holderDocker::before{position:relative;float:right;margin-top:.5rem;margin-right:3rem;font-family:'Unraid';content:'\e90b';font-size:9rem;color:<?=$watermarkColor?>;}
<?endif;?>
.ca_holderLanguage::before{position:relative;float:right;margin-top:.5rem;margin-right:3rem;font-family:'Unraid';content:'\e987';font-size:9rem;color:<?=$watermarkColor?>;}
.ca_holderRepository::before{position:relative;float:right;margin-top:1.5rem;margin-right:3rem;margin-bottom:2rem;font-family:'fontAwesome';content:'\f2be';font-size:7rem;color:<?=$watermarkColor?>;}
.ca_topRightArea {display:block;position:relative;margin-top:.5rem;margin-right:2rem;z-index:9999;float:right;}
img.displayIcon {height:6.4rem;width:6.4rem;border-radius:1rem 1rem 1rem 1rem;}
i.displayIcon {font-size:5.5rem;color:#626868;padding-top:0.25rem;}
.ca_bottomLine {display:block;position:relative;padding-top:9.5rem;margin-left:1.5rem;}
.ca_bottomRight {float:right;margin-right:2rem;padding-top:0.5rem;}
.ca_hr {margin-left:10px;margin-right:10px;border:1px; border-color:<?=$hrColor?>; border-top-style:solid;border-right-style:none;border-bottom-style:none;border-left-style:none;}
.categoryLine {margin-left:10px;margin-top:-15px;font-size:1.5rem;font-weight:normal;color:<?=$unRaid66color?>;}
.searchArea {float:right;z-index:2;width:auto;position:static;}
.sortIcons {font-size:1.8rem;margin-right:20px;cursor:pointer;}
ul.caMenu {list-style-type: none;margin:0px 0px 20px 0px;padding: 0;font-size:1.5rem;}
.caMenuEnabled {cursor:pointer;opacity:1;}
.caMenuDisabled {cursor:default;opacity:0.5;}
ul.nonselectMenu {list-style-type: none;margin:0px 0px 20px 0px;padding: 0;font-size:1.5rem;}
li.caMenuItem {padding:0px 0px 5px 0px;}
ul.subCategory {list-style-type:none;margin-left:2rem;padding:0px;cursor:pointer;display:none;}
li.debugging {cursor:pointer;}
.menuHeader { font-size:2rem; margin-bottom:1rem;margin-top:1rem;}
.selectedMenu {color:<?=$unRaid66color?>;font-weight:bold;}
.hoverMenu {color:<?=$unRaid66color?>;}
table {background-color:transparent;}
table tbody td {line-height:1.8rem;}
.startup-icon {color:lightblue;font-size:1.5rem;cursor:pointer;}
.ca_serverWarning {color:#cecc31}
.ca_template_icon {color:#606E7F;width:37rem;float:left;display:inline-block;background-color: #C7C5CB;margin:0px 0px 0px 0px;height:15rem;padding-top:1rem;}
.ca_template {color:#606E7F;border-radius:0px 0px 2rem 2rem;display:inline-block;text-align:left;overflow:auto;height:27rem;width:36rem;padding-left:.5rem;padding-right:.5rem; background-color:#DDDADF;}
.ca_icon_wide {display:inline-block;float:left;width:9.5rem;margin-left:2.5rem;}
.ca_wide_info {display: inline-block;float:left;text-align:left;margin-left:1rem;margin-top:1.5rem;width:20rem;}
.ca_highlight {color:#0e5d08;font-weight:bold;}
.ca_description {color:#505E6F;}
a.ca_appPopup {text-decoration:none;cursor:pointer;}
.ca_repoPopup {text-decoration:none!important;cursor:pointer;color:inherit;}
a.ca_repoPopup:hover {color:<?=$unRaid66color?>;}
a.ca_reporeadmore {cursor:pointer;text-decoration:none;}
a.ca_appreadmore {cursor:pointer;text-decoration:none;}
a.ca_reporeadmore:hover {color:<?=$unRaid66color?>;}
a.ca_appreadmore:hover {color:<?=$unRaid66color?>;}
input[type=checkbox] {width:2rem;height:2rem;margin-right:1rem;margin-top:-.5rem;margin-left:0rem;}
.enabledIcon {cursor:pointer;color:<?=$unRaid66color?>;}
.disabledIcon {color:#040404;font-size:2.5rem;}
.pinned {font-size:2rem;cursor:pointer;padding-left:.5rem;padding-right:.5rem;cursor:pointer;color:#1fa67a;padding:.3rem;}
.unpinned {font-size:2rem;cursor:pointer;padding-left:.5rem;padding-right:.5rem;cursor:pointer;padding:.3rem;}
.pinned::after {content:"\f08d";font-family:fontAwesome;}
.unpinned::after {content:"\f08d";font-family:fontAwesome;display:inline-block;-webkit-transform: rotate(20deg);-moz-transform: rotate(20deg);-ms-transform: rotate(20deg); -o-transform: rotate(20deg);  transform: rotate(20deg);}
.ca_favouriteRepo {font-size:2rem;cursor:pointer;padding-left:.5rem;padding-right:.5rem;cursor:pointer;color:#1fa67a !important;padding:.3rem;}
.ca_favouriteRepo::before {content:"\f2be";font-family:fontAwesome;}
.ca_non_favouriteRepo {font-size:2rem;cursor:pointer;padding-left:.5rem;padding-right:.5rem;cursor:pointer;padding:.3rem;}
.ca_non_favouriteRepo::before {content:"\f2be";font-family:fontAwesome;}
.ca_repoSearch {font-size:2rem;cursor:pointer;padding-left:.5rem;padding-right:.5rem;cursor:pointer;padding:.3rem;}
.ca_repoSearchPopup {font-size:2rem;cursor:pointer;padding-left:.5rem;padding-right:.5rem;cursor:pointer;padding:.3rem;}
.ca_repoSearch::after {content:"\f002";font-family:fontAwesome;}
.appIcons {font-size:2.3rem;color:inherit;cursor:pointer;padding-left:.5rem;padding-right:.5rem;}
.appIcons:hover {text-decoration:none;color:<?=$unRaid66color?> ! important;}
.pinned:hover {text-decoration:none;color:<?=$unRaid66color?>;}
.unpinned:hover {text-decoration:none;color:<?=$unRaid66color?>;}
a.appIcons {text-decoration:none;}
.appIconsPopUp {font-size:2rem !important;cursor:pointer;padding-left:.5rem;padding-right:.5rem;color:default;}
.appIconsPopUp:hover {text-decoration:none;color:<?=$unRaid66color?>;}
.myReadmore {text-align:center;}
.myReadmoreButton {color:blue;}
.supportLink {color:inherit;padding-left:.5rem;padding-right:.5rem;}
.donateLink {font-size:1.2rem;}
.donate:hover {text-decoration:none;background-color:<?=$unRaid66color?>;}
.dockerHubStar {font-size:1rem;}
.dockerDisabled {display:none;}
.displayBeta {margin-left:2rem;cursor:pointer;}
.newApp {color:red;font-size:1.5rem;cursor:pointer;}
.ca_fa-support::before {content:"\f059";font-family:fontAwesome;}
<?if ($unRaid67):?>
.ca_fa-delete {color:#882626;font-size:1.5rem;cursor:pointer;}
.ca_fa-delete::before {content:"\e92f";font-family:Unraid;}
.ca_fa-delete:hover {color:<?=$unRaid66color?>;}
.ca_fa-project::before {content:"\e953";font-family:Unraid;}
.dockerHubStar::before{content:"\e95a";font-family:UnRaid;}
<?else:?>
.ca_fa-delete {color:#882626;font-size:2rem;cursor:pointer;}
.ca_fa-delete::before {content:"\f00d";font-family:fontAwesome;}
.ca_fa-project::before {content:"\f08e";font-family:fontAwesome;}
.dockerHubStar:before {content:"\f005";font-family:fontAwesome;}
<?endif;?>
a.ca_fa-delete{text-decoration:none;margin-left:1rem;}
.ca_fa-install::before {content:"\f019";font-family:fontAwesome;}
.ca_fa-edit::before {content:"\f044";font-family:fontAwesome;}
.ca_fa-globe::before {content:"\f0ac";font-family:fontAwesome;}
.ca_fa-update::before {content:"\f0ed";font-family:fontAwesome;}
.ca_fa-info::before {content:"\f05a";font-family:fontAwesome;}
.ca_repoinfo::before {content:"\f05a";font-family:fontAwesome;}
.ca_fa-warning::before {content:"\f071";font-family:fontAwesome;}
.ca_fa-switchto::before {content:"\e982";font-family:Unraid;}
.ca_favourite::before {content:"\f2be";font-family:fontAwesome;color:#1fa67a;}
.ca_favourite {cursor:default !important;}
.ca_twitter::before {content:"\f099";font-family:fontAwesome;}
.ca_reddit::before {content:"\f281";font-family:fontAwesome;}
.ca_facebook::before {content:"\f09a";font-family:fontAwesome;}
.ca_showRepo::before {content:"\f002";font-family:fontAwesome;}
.ca_repository::before {content:"\f2be";font-family:fontAwesome;}
<?if (version_compare($unRaidSettings['version'],"6.9.0-beta37",">")):?>
.ca_discord::before{content:"\e988";font-family:Unraid;font-size:2.8rem;vertical-align:bottom;}
.ca_discord_popup::before{content:"\e988";font-family:Unraid;font-size:2.2rem;vertical-align:middle;}
<?else:?>
.ca_discord {height:2.9rem; margin-top:-8px;cursor:pointer;}
<?endif;?>
.ca_forum::before {content:"\f1cd";font-family:fontAwesome;}
.ca_webpage::before {content:"\f0ac";font-family:fontAwesome;}
.ca_profile::before {content:"\f2bb";font-family:fontAwesome;}
.trendingUp::before {content:"\f062";font-family:fontAwesome;}
.trendingDown::before {content:"\f063";font-family:fontAwesome;}
.ca_private::after {content:"\f069";font-family:fontAwesome;}
.ca_private{color:#882626;}
.warning-red {color:#882626;}
.warning-yellow {color:#FF8C2F;}
.ca_fa-pluginSettings::before {content:"\f013";font-family:fontAwesome;}
.ca_donate {position:relative;margin-left:18rem;}
.ca_multiselect {cursor:pointer;padding-right:5rem;}
.pageNumber{margin-left:1rem;margin-right:1rem;cursor:pointer;text-decoration:none !important;}
.pageDots{color:grey;cursor:default;}
.pageDots::after {content:"...";}
.pageNavigation {font-size:1.5rem;}
.pageNavNoClick {font-size:1.5rem;color:grey;cursor:default;}
.pageSelected {cursor:default;}
.pageRight::after {content:"\f138";font-family:fontAwesome;font-weight:bold;}
.pageLeft::after {content:"\f137";font-family:fontAwesome;font-weight:bold;}
.specialCategory {font-size:1.5rem;}
.ca_table { padding:.5rem 2rem .5rem 0; font-size:1.5rem;}
.ca_stat {color:coral; font-size:1.5rem;line-height:1.7rem;}
.ca_credit { padding:.5rem 0 1rem 0; font-size:1.5rem;line-height:2rem; font-style:italic;}
.ca_creditheader { font-size:2rem; padding-top:1rem;}
.ca_dateUpdatedDate {font-weight:normal;}
#cookieWarning {display:none;}
.notice.shift {margin-top:0px;}
#searchBox{top:-0.6rem;padding:0.6rem;}
.searchSubmit{height:3.4rem;}
.startupMessage{font-size:2.5rem;}
.startupMessage2{font-size:1rem;}
.donate {background: <?=$donateBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 200% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;  text-shadow: 1px 1px 5px #666;}
a.donate {text-decoration:none;font-style:italic;color:<?=$donateText?>;}
.popup-donate {background:black;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),              linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 200% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;  text-shadow: 1px 1px 5px #666;}
a.popup-donate {text-decoration:none;font-style:italic;color:white;font-size:1.5rem;}
a.popup-donate:hover {color:<?=$donateText?>;background-color:<?=$unRaid66color?>}
<?if ( $theme == "azure" ):?>
.searchSubmit{font-family:'FontAwesome';width:2.9rem;height:2.9rem;border:.1rem solid #dadada;border-radius:4px 4px 4px 4px;font-size:1.1rem;position:relative; top:-.7rem;padding:0px .2rem;background:transparent;border:none;cursor:pointer;}
#searchBox{margin-left:1rem;margin-right:0;position:relative;top:-.6rem;border:none;}
<?endif;?>
<?if ( $theme == "black" ):?>
.searchSubmit{font-family:'FontAwesome';width:2.9rem;height:2rem;border:1px solid #dadada;border-radius:4px 4px 4px 4px;font-size:1.1rem;position:relative; top:-6px;padding:0px 2px;background:transparent;border:none;cursor:pointer;}
#searchBox{margin-left:1rem;margin-right:0;position:relative;top:-.5rem;border:none;}
<?endif;?>
<?if ( $theme == "gray" ):?>
.searchSubmit{font-family:'FontAwesome';width:2.9rem;height:2.9rem;border:.1rem solid #dadada;border-radius:4px 4px 4px 4px;font-size:1.1rem;position:relative; top:-.7rem;padding:0px .2rem;background:transparent;border:none;cursor:pointer;}
#searchBox{margin-left:1rem;margin-right:0;position:relative;top:-.6rem;border:none;}
<?endif;?>
<?if ( $theme == "white" ):?>
.searchSubmit{font-family:'FontAwesome';width:2.9rem;height:2.6rem;border:1px; solid #dadada;border-radius:4px 4px 4px 4px;font-size:1.1rem;position:relative; top:-6px;padding:0px 2px;background:transparent;border:none;cursor:pointer;}
#searchBox{margin-left:1rem;margin-right:0;position:relative;top:-.5rem;border:none;}
<?endif;?>
<?if ($unRaid66 && ( $theme == "black" || $theme == "white") ):?>
#searchBox{top:-0.6rem;padding:0.6rem;}
.searchSubmit{height:3.4rem;}
<?endif;?>
.popUpLink {cursor:pointer;}
a.popUpLink {text-decoration:none;}
a.popUpLink:hover {color:<?=$unRaid66color?>;}
.popUpDeprecated {color:#FF8C2F;}
i.popupIcon {color:#626868;font-size:14.4rem;padding-left:1rem;width:14.4rem}
img.popupIcon {width:14.4rem;height:14.4rem;padding:0.3rem;border-radius:1rem 1rem 1rem 1rem;}
.display_beta {color:#FF8C2F;}
a.appIconsPopUp { text-decoration:none;color:inherit;}
.ca_italic {font-style:italic;}
.ca_bold {font-weight:bold;}
.ca_center {margin:auto;text-align:center;}
.ca_NoAppsFound {font-size:3rem;margin:auto;text-align:center;}
.ca_NoDockerAppsFound {font-size:3rem;margin:auto;text-align:center;}
.ca_templatesDisplay {display:flex;flex-wrap:wrap;justify-content:center;overflow-x:hidden;}
#warningNotAccepted {display:none;}
.menuItems {position:absolute; left:0px;width:14rem;height:auto;}
.mainArea {position:absolute;left:18.5rem;right:0px; display:block;overflow-x:hidden;}
.multi_installDiv {width:100%; display:none;padding-bottom:20px;}
.ca_toolsView {font-size:2.3rem; position:relative;top:-0.2rem;}
#templates_content {overflow-x:hidden;}
.graphLink {cursor:pointer;text-decoration:none;}
.caChart {display:none;border:none;}
.caHighlight {color:#FF0000;font-weight:bold;}
.caChangeLog {cursor:pointer;}
.caInstallLinePopUp {display:flex;flex-wrap:wrap;justify-content:space-around;}
.caHelpIconSpacing {display:inline-block;width:7rem;height:3rem;}

.popupDescriptionArea{display:block;font-size:1.5rem;;}
.popupTitle{margin:auto;text-align:center;font-weight:bold;font-size:2rem;}

.awesomplete [hidden] {display: none;}
.awesomplete .visually-hidden {position: absolute;clip: rect(0, 0, 0, 0);}
.awesomplete {display: inline-block;position: relative;color: red;}
.awesomplete > input {display: block;}
.awesomplete > ul {position: absolute;left: 0;z-index: 1;min-width: 100%;box-sizing: border-box;list-style: none;padding: 0;margin: 0;background: #fff;}
.awesomplete > ul:empty {display: none;}
.awesomplete > ul {border-radius: .3em;margin: .2em 0 0;background: hsla(0,0%,100%,.9);background: linear-gradient(to bottom right, white, hsla(0,0%,100%,.8));border: 1px solid rgba(0,0,0,.3);box-shadow: .05em .2em .6em rgba(0,0,0,.2);text-shadow: none;}
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
