<?php
###############################################################
#                                                             #
# Community Applications copyright 2015-2019, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

header("Content-type: text/css; charset: UTF-8");

$dynamix = @parse_ini_file("/boot/config/plugins/dynamix/dynamix.cfg",true);
if ( ! $dynamix['display']['theme'] )
	$dynamix = @parse_ini_file("/usr/local/emhttp/plugins/dynamix/default.cfg",true);

$theme = $dynamix['display']['theme'] ?: "black";

$unRaidSettings = parse_ini_file("/etc/unraid-version");
$unRaid66 = version_compare($unRaidSettings['version'],"6.5.3",">");
$unRaid67 = version_compare($unRaidSettings['version'],"6.7.0-rc4",">");
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
		break;
	case 'white':
		$donateBackground = "#1c1b1b";
		$donateText = "#f2f2f2";
		$templateBackground = "#f5f5f5";
		$hrColor = "lightgrey";
		$borderColor = "lightgrey";
		break;
	case 'azure':
		$donateBackground = "#606e7f";
		$donateText = "#e4e2e4";
		$templateBackground = "#e2e0e2";
		$hrColor = "#606e7f";
		$border = "#606e7f";
		break;
	case 'gray':
		$donateBackground = "#606e7f";
		$donateText = "#1b1d1b";
		$templateBackground = "#1b1d1b";
		$hrColor = "#606e7f";
		$border = "#606e7f";
		break;
	default:
		$donateBackground = "#f2f2f2";
		$donateText = "#1c1b1b";
		$templateBackground = "#191818";
		$hrColor = "#2b2b2b";
		$borderColor = "#2b2b2b";
		break;
}

$multiplier = 1;
?>
body.stop-scrolling{height:100%;overflow:auto}  // disable SweetAlert killing the scroll bar ( stops the wiggle )
.sweet-alert {background-color:transparent;}
.sweet-overlay{background-color:rgba(0, 0, 0, 0);} // don't dim if spinner is displayed
.ca_iconArea {width:100%;height:<?=(8*$multiplier)?>rem;margin:<?=(1*$multiplier)?>rem;}
.ca_icon {width:<?=(8*$multiplier)?>rem;height:<?=(9*$multiplier)?>rem;display:inline-block;padding-top:<?=(0.5*$multiplier)?>rem;padding-left:<?=(1*$multiplier)?>rem;}
.ca_infoArea {height:<?=(10*$multiplier)?>rem;margin:<?=(1*$multiplier)?>rem;display:inline-block;position:absolute;width:auto;}
.ca_applicationInfo {display:inline-block;position:absolute;width:<?=(25*$multiplier)?>rem;}
.ca_categories {font-size:<?=(1*$multiplier)?>rem;font-style:italic;}
a.ca_categories {text-decoration:none;color:inherit;}
.ca_applicationName {font-size:<?=(1.5*$multiplier)?>rem;}
a.ca_applicationName {text-decoration:none;color:inherit;}
.ca_author {cursor:pointer;font-size:<?=(1*$multiplier)?>rem;font-style:italic;}
a.ca_author {text-decoration:none;color:inherit;}
.ca_categoryLink {color:<?=$linkColor?>;font-weight:normal;}
a.ca_categoryLink {text-decoration:none;color:inherit;}
.ca_descriptionArea {margin:<?=(1*$multiplier)?>rem;width:auto;height:<?=(4*$multiplier)?>rem;position:relative;margin-top:<?=(-11*$multiplier)?>rem;}
.ca_holder {background-color:<?=$templateBackground?>;display:inline-block;float:left;height:<?=(24*$multiplier)?>rem;min-width:<?=(37*$multiplier)?>rem;max-width:<?=(50*$multiplier)?>rem;flex-grow:1;flex-basis:<?=(37*$multiplier)?>rem;overflow:hidden;padding:0px;margin-left:0px;margin-top:0px;margin-bottom:<?=(1*$multiplier)?>rem;margin-right:<?=(1*$multiplier)?>rem;font-size:<?=(1.2*$multiplier)?>rem;border:1px solid;border-color:<?=$borderColor?>;border-radius:10px 10px 10px 10px;}
.ca_topRightArea {display:block;position:relative;margin-top:<?=(.5*$multiplier)?>rem;margin-right:<?=(3*$multiplier)?>rem;z-index:9999;float:right;}
img.displayIcon {height:<?=(6.4*$multiplier)?>rem;width:<?=(6.4*$multiplier)?>rem;}
i.displayIcon {font-size:<?=(5.5*$multiplier)?>rem;color:#626868;padding-top:<?=(0.25*$multiplier)?>rem;}
.ca_bottomLine {display:block;position:relative;padding-top:<?=(9.5*$multiplier)?>rem;margin-left:<?=(1.5*$multiplier)?>rem;}
.ca_bottomRight {float:right;margin-right:<?=(2*$multiplier)?>rem;padding-top:<?=(0.5*$multiplier)?>rem;}
.ca_hr {margin-left:10px;margin-right:10px;border:1px; border-color:<?=$hrColor?>; border-top-style:solid;border-right-style:none;border-bottom-style:none;border-left-style:none;}
.categoryLine {margin-left:10px;font-size:<?=(1*$multiplier)?>rem;font-weight:normal;width:20%;display:inline-block;}
.searchArea {float:right;z-index:2;width:auto;position:static;}
.sortIcons {font-size:<?=(1.8*$multiplier)?>rem;margin-right:20px;cursor:pointer;}
ul.caMenu {list-style-type: none;margin:0px 0px 20px 0px;padding: 0;cursor:pointer;font-size:<?=(1.5*$multiplier)?>rem;}
ul.nonselectMenu {list-style-type: none;margin:0px 0px 20px 0px;padding: 0;font-size:<?=(1.5*$multiplier)?>rem;}
li.caMenuItem {padding:0px 0px 5px 0px;}
ul.subCategory {list-style-type:none;margin-left:<?=(2*$multiplier)?>rem;padding:0px;cursor:pointer;display:none;}
.menuHeader { font-size:<?=(2*$multiplier)?>rem; margin-bottom:<?=(1*$multiplier)?>rem;margin-top:<?=(1*$multiplier)?>rem;}
.selectedMenu {color:<?=$unRaid66color?>;font-weight:bold;cursor:default;}
.hoverMenu {color:<?=$unRaid66color?>;}
table {background-color:transparent;}
table tbody td {line-height:<?=(1.4*$multiplier)?>rem;}
.startup-icon {color:lightblue;font-size:<?=(1.5*$multiplier)?>rem;cursor:pointer;}
.ca_serverWarning {color:#cecc31}
.ca_template_icon {color:#606E7F;width:<?=(37*$multiplier)?>rem;float:left;display:inline-block;background-color: #C7C5CB;margin:0px 0px 0px 0px;height:<?=(15*$multiplier)?>rem;padding-top:<?=(1*$multiplier)?>rem;}
.ca_template {color:#606E7F;border-radius:0px 0px <?=(2*$multiplier)?>rem <?=(2*$multiplier)?>rem;display:inline-block;text-align:left;overflow:auto;height:<?=(27*$multiplier)?>rem;width:<?=(36*$multiplier)?>rem;padding-left:<?=(.5*$multiplier)?>rem;padding-right:<?=(.5*$multiplier)?>rem; background-color:#DDDADF;}
.ca_icon_wide {display:inline-block;float:left;width:<?=(9.5*$multiplier)?>rem;margin-left:<?=(2.5*$multiplier)?>rem;}
.ca_wide_info {display: inline-block;float:left;text-align:left;margin-left:<?=(1*$multiplier)?>rem;margin-top:<?=(1.5*$multiplier)?>rem;width:<?=(20*$multiplier)?>rem;}
.ca_repository {color:black;}
.ca_highlight {color:#0e5d08;font-weight:bold;}
.ca_description {color:#505E6F;}
a.ca_appPopup {text-decoration:none;cursor:pointer;}
input[type=checkbox] {width:<?=(2*$multiplier)?>rem;height:<?=(2*$multiplier)?>rem;}
.enabledIcon {cursor:pointer;color:<?=$unRaid66color?>;}
.disabledIcon {color:#040404;font-size:<?=(2.5*$multiplier)?>rem;}
.pinned {font-size:<?=(2*$multiplier)?>rem;cursor:pointer;padding-left:<?=(.5*$multiplier)?>rem;padding-right:<?=(.5*$multiplier)?>rem;cursor:pointer;color:<?=$unRaid66color?>;padding:<?=(.3*$multiplier)?>rem;}
.unpinned {font-size:<?=(2*$multiplier)?>rem;cursor:pointer;padding-left:<?=(.5*$multiplier)?>rem;padding-right:<?=(.5*$multiplier)?>rem;cursor:pointer;padding:<?=(.3*$multiplier)?>rem;}
.pinned::after {content:"\f08d";font-family:fontAwesome;}
.unpinned::after {content:"\f08d";font-family:fontAwesome;display:inline-block;-webkit-transform: rotate(20deg);-moz-transform: rotate(20deg);-ms-transform: rotate(20deg); -o-transform: rotate(20deg);  transform: rotate(20deg);}
.appIcons {font-size:<?=(2.3*$multiplier)?>rem;color:inherit;cursor:pointer;padding-left:<?=(.5*$multiplier)?>rem;padding-right:<?=(.5*$multiplier)?>rem;}
.appIcons:hover {text-decoration:none;}
a.appIcons {text-decoration:none;}
.appIconsPopUp {font-size:<?=(1.1*$multiplier)?>rem;cursor:pointer;padding-left:<?=(.5*$multiplier)?>rem;padding-right:<?=(.5*$multiplier)?>rem;}
.appIconsPopUp:hover {text-decoration:none;}
.myReadmore {text-align:center;}
.myReadmoreButton {color:blue;}
.supportLink {color:inherit;padding-left:<?=(.5*$multiplier)?>rem;padding-right:<?=(.5*$multiplier)?>rem;}
.projectLink {font-weight:bold;color:<?=$linkColor?>;padding-left:<?=(.5*$multiplier)?>rem;padding-right:<?=(.5*$multiplier)?>rem;}
.projectLink::after {content:"Project Home Page"}
.webLink {font-weight:bold;color:<?=$linkColor?>;padding-left:<?=(.5*$multiplier)?>rem;padding-right:<?=(.5*$multiplier)?>rem;}
.webLink::after {content:"Web Page"}
.donateLink {font-size:<?=(1.2*$multiplier)?>rem;}
.dockerHubStar {font-size:<?=(1*$multiplier)?>rem;}
.dockerDisabled {display:none;}
.separateOfficial {text-align:center;width:auto;font-size:<?=(2.5*$multiplier)?>rem;}
.displayBeta {margin-left:<?=(2*$multiplier)?>rem;cursor:pointer;}
.newApp {color:red;font-size:<?=(1.5*$multiplier)?>rem;cursor:pointer;}
.ca_fa-support::before {content:"\f059";font-family:fontAwesome;}
<?if ($unRaid67):?>
.ca_fa-delete {color:#882626;font-size:<?=(1.5*$multiplier)?>rem;position:relative;cursor:pointer;}
.ca_fa-delete::before {content:"\e92f";font-family:Unraid;}
.ca_fa-project::before {content:"\e953";font-family:Unraid;}
.dockerHubStar::before{content:"\e95a";font-family:UnRaid;}
<?else:?>
.ca_fa-delete {color:#882626;font-size:<?=(2*$multiplier)?>rem;position:relative;cursor:pointer;}
.ca_fa-delete::before {content:"\f00d";font-family:fontAwesome;}
.ca_fa-project::before {content:"\f08e";font-family:fontAwesome;}
.dockerHubStar:before {content:"\f005";font-family:fontAwesome;}
<?endif;?>
a.ca_fa-delete{text-decoration:none;}
.ca_fa-install::before {content:"\f019";font-family:fontAwesome;}
.ca_fa-edit::before {content:"\f044";font-family:fontAwesome;}
.ca_fa-globe::before {content:"\f0ac";font-family:fontAwesome;}
.ca_fa-update::before {content:"\f021";font-family:fontAwesome;}
.ca_fa-info::before {content:"\f05a";font-family:fontAwesome;}
.ca_fa-warning::before {content:"\f071";font-family:fontAwesome;}
.trendingUp::before {content:"\f062";font-family:fontAwesome;}
.trendingDown::before {content:"\f063";font-family:fontAwesome;}
.ca_private::after {content:"\f069";font-family:fontAwesome;}
.ca_private{color:#882626;}
.warning-red {color:#882626;}
.warning-yellow {color:#FF8C2F;}
.ca_fa-pluginSettings::before {content:"\f013";font-family:fontAwesome;}
.ca_donate {position:relative;margin-left:<?=(18*$multiplier)?>rem;}
.ca_multiselect {cursor:pointer;}
.pageNumber{margin-left:<?=(1*$multiplier)?>rem;margin-right:<?=(1*$multiplier)?>rem;cursor:pointer;}
.pageDots{color:grey;cursor:default;}
.pageDots::after {content:"...";}
.pageNavigation {font-size:<?=(1.5*$multiplier)?>rem;}
.pageNavNoClick {font-size:<?=(1.5*$multiplier)?>rem;color:grey;cursor:default;}
.pageSelected {cursor:default;}
.pageRight::after {content:"\f138";font-family:fontAwesome;font-weight:bold;}
.pageLeft::after {content:"\f137";font-family:fontAwesome;font-weight:bold;}
.specialCategory {font-size:<?=(1.5*$multiplier)?>rem;}
.ca_table { padding:<?=(.5*$multiplier)?>rem <?=(2*$multiplier)?>rem <?=(.5*$multiplier)?>rem 0; font-size:<?=(1.5*$multiplier)?>rem;}
.ca_stat {color:coral; font-size:<?=(1.5*$multiplier)?>rem;}
.ca_credit { padding:<?=(.5*$multiplier)?>rem 0 <?=(1*$multiplier)?>rem 0; font-size:<?=(1.5*$multiplier)?>rem;line-height:<?=(2*$multiplier)?>rem;}
.ca_dateUpdated {font-weight:bold;text-align:center;}
.ca_dateUpdated::before {content:"Date Updated: ";}
.ca_dateAdded {font-weight:bold;text-align:center;}
.ca_dateAdded::before {content:"Dated Added: ";}
.ca_dateUpdatedDate {font-weight:normal;}
#cookieWarning {display:none;}
.notice.shift {margin-top:0px;}
#searchBox{top:<?=(-0.6*$multiplier)?>rem;padding:<?=(0.6*$multiplier)?>rem;}
.searchSubmit{height:<?=(3.4*$multiplier)?>rem;}
.startupMessage{font-size:<?=(2.5*$multiplier)?>rem;}
.startupMessage2{font-size:<?=(1*$multiplier)?>rem;}
.donate {background: <?=$donateBackground?>;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),              linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 200% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;  text-shadow: 1px 1px 5px #666;}
a.donate {text-decoration:none;font-style:italic;color:<?=$donateText?>;}
.popup-donate {background:black;background: -webkit-linear-gradient(top, transparent 0%, rgba(0,0,0,0.4) 100%),-webkit-linear-gradient(left, lighten(<?=$donateBackground?>, 15%) 0%, <?=$donateBackground?> 50%, lighten(<?=$donateBackground?>, 15%) 100%);  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%),              linear-gradient(to right, lighten(#E68321, 15%) 0%, #E68321 50%, lighten(#E68321, 15%) 100%);  background-position: 0 0;  background-size: 200% 100%;  border-radius: 15px;  color: #fff;  padding: 1px 10px 1px 10px;  text-shadow: 1px 1px 5px #666;}
a.popup-donate {text-decoration:none;font-style:italic;color:white;}
<?if ( $theme == "azure" ):?>
.searchSubmit{font-family:'FontAwesome';width:<?=(2.9*$multiplier)?>rem;height:<?=(2.9*$multiplier)?>rem;border:<?=(.1*$multiplier)?>rem solid #dadada;border-radius:4px 4px 4px 4px;font-size:<?=(1.1*$multiplier)?>rem;position:relative; top:<?=(-.7*$multiplier)?>rem;padding:0px <?=(.2*$multiplier)?>rem;background:transparent;border:none;cursor:pointer;}
#searchBox{margin-left:<?=(1*$multiplier)?>rem;margin-right:0;position:relative;top:<?=(-.6*$multiplier)?>rem;border:none;}
<?endif;?>
<?if ( $theme == "black" ):?>
.searchSubmit{font-family:'FontAwesome';width:<?=(2.9*$multiplier)?>rem;height:<?=(2*$multiplier)?>rem;border:1px solid #dadada;border-radius:4px 4px 4px 4px;font-size:<?=(1.1*$multiplier)?>rem;position:relative; top:-6px;padding:0px 2px;background:transparent;border:none;cursor:pointer;}
#searchBox{margin-left:<?=(1*$multiplier)?>rem;margin-right:0;position:relative;top:<?=(-.5*$multiplier)?>rem;border:none;}
<?endif;?>
<?if ( $theme == "gray" ):?>
.searchSubmit{font-family:'FontAwesome';width:<?=(2.9*$multiplier)?>rem;height:<?=(2.9*$multiplier)?>rem;border:<?=(.1*$multiplier)?>rem solid #dadada;border-radius:4px 4px 4px 4px;font-size:<?=(1.1*$multiplier)?>rem;position:relative; top:<?=(-.7*$multiplier)?>rem;padding:0px <?=(.2*$multiplier)?>rem;background:transparent;border:none;cursor:pointer;}
#searchBox{margin-left:<?=(1*$multiplier)?>rem;margin-right:0;position:relative;top:<?=(-.6*$multiplier)?>rem;border:none;}
<?endif;?>
<?if ( $theme == "white" ):?>
.searchSubmit{font-family:'FontAwesome';width:<?=(2.9*$multiplier)?>rem;height:<?=(2.6*$multiplier)?>rem;border:1px; solid #dadada;border-radius:4px 4px 4px 4px;font-size:<?=(1.1*$multiplier)?>rem;position:relative; top:-6px;padding:0px 2px;background:transparent;border:none;cursor:pointer;}
#searchBox{margin-left:<?=(1*$multiplier)?>rem;margin-right:0;position:relative;top:<?=(-.5*$multiplier)?>rem;border:none;}
<?endif;?>
<?if ($unRaid66 && ( $theme == "black" || $theme == "white") ):?>
#searchBox{top:<?=(-0.6*$multiplier)?>rem;padding:<?=(0.6*$multiplier)?>rem;}
.searchSubmit{height:<?=(3.4*$multiplier)?>rem;}
<?endif;?>
.popUpLink {cursor:pointer;}
a.popUpLink {text-decoration:none;}
.popUpDeprecated {color:#FF8C2F;}
i.popupIcon {color:#626868;font-size:<?=(3.5*$multiplier)?>rem;padding-left:<?=(1*$multiplier)?>rem;width:<?=(4.8*$multiplier)?>rem}
img.popupIcon {width:<?=(4.8*$multiplier)?>rem;height:<?=(4.8*$multiplier)?>rem;padding:<?=(0.3*$multiplier)?>rem;border-radius:<?=(1*$multiplier)?>rem <?=(1*$multiplier)?>rem <?=(1*$multiplier)?>rem <?=(1*$multiplier)?>rem;}
.display_beta {color:#FF8C2F;}
a.appIconsPopUp { text-decoration:none;color:inherit;}
.ca_italic {font-style:italic;}
.ca_bold {font-weight:bold;}
.ca_center {margin:auto;text-align:center;}
.ca_NoAppsFound {font-size:<?=(3*$multiplier)?>rem;margin:auto;text-align:center;}
.ca_NoAppsFound::after{content:"No Matching Applications Found"}
.ca_NoDockerAppsFound {font-size:<?=(3*$multiplier)?>rem;margin:auto;text-align:center;}
.ca_NoDockerAppsFound::after{content:"No Matching Applications Found On Docker Hub"}
.ca_templatesDisplay {display:flex;flex-wrap:wrap;justify-content:center;overflow-x:hidden;}
#warningNotAccepted {display:none;}
.menuItems {position:absolute; left:0px;width:<?=(14*$multiplier)?>rem;height:auto;}
.mainArea {position:absolute;left:<?=(18.5*$multiplier)?>rem;right:0px; display:block;overflow-x:hidden;}
.multi_installDiv {width:100%; display:none;padding-bottom:20px;}
.ca_toolsView {font-size:<?=(2.3*$multiplier)?>rem; position:relative;top:<?=(-0.2*$multiplier)?>rem;}
#templates_content {overflow-x:hidden;}
.graphLink {cursor:pointer;text-decoration:none;}
.caChart {display:none;border:1px solid #c2c8c8;border-radius:4px 4px 4px 4px;}
.caHighlight {color:#FF0000;font-weight:bold;}
.caChangeLog {cursor:pointer;}
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