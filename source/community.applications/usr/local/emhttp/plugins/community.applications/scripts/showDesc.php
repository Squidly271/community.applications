<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2019, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################
?>
<?PHP
require_once("/usr/local/emhttp/plugins/dynamix/include/Helpers.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");

$unRaidVars = parse_ini_file("/var/local/emhttp/var.ini");
$communitySettings = parse_plugin_cfg("community.applications");
$csrf_token = $unRaidVars['csrf_token'];
$appNumber =  urldecode($_GET['appPath']);
$appName = urldecode($_GET['appName']);
$appName = str_replace("'","",$appName);
?>
<script src='<?autov("/plugins/dynamix/javascript/dynamix.js")?>'></script>
<script src='<?autov("/plugins/community.applications/javascript/libraries.js")?>'></script>

<link type="text/css" rel="stylesheet" href='<?autov("/webGui/styles/font-awesome.css")?>'>
<link type="text/css" rel="stylesheet" href='<?autov("/plugins/community.applications/skins/Narrow/css.php")?>'>
<link type="text/css" rel="stylesheet" href='<?autov("/webGui/styles/default-fonts.css")?>'>
<!-- Specific styling for the popup -->
<style>
p {margin-left:2rem;margin-right:2rem;}
body {margin-left:1.5rem;margin-right:1.5rem;margin-top:1.5rem;font-family:clear-sans;font-size:0.9rem;}
hr { margin-top:1rem;margin-bottom:1rem; }
div.spinner{margin:48px auto;text-align:center;}
div.spinner.fixed{position:fixed;top:50%;left:50%;margin-top:-16px;margin-left:-64px}
div.spinner .unraid_mark{height:64px}
div.spinner .unraid_mark_2,div .unraid_mark_4{animation:mark_2 1.5s ease infinite}
div.spinner .unraid_mark_3{animation:mark_3 1.5s ease infinite}
div.spinner .unraid_mark_6,div .unraid_mark_8{animation:mark_6 1.5s ease infinite}
div.spinner .unraid_mark_7{animation:mark_7 1.5s ease infinite}
@keyframes mark_2{50% {transform:translateY(-40px)} 100% {transform:translateY(0px)}}
@keyframes mark_3{50% {transform:translateY(-62px)} 100% {transform:translateY(0px)}}
@keyframes mark_6{50% {transform:translateY(40px)} 100% {transform:translateY(0px)}}
@keyframes mark_7{50% {transform:translateY(62px)} 100% {transform: translateY(0px)}}
</style>
<script>
$(function() {
	$.removeCookie("installPluginURL",{path:"/"});

	setTimeout(function() {
		$(".spinner").show();
	},250);
	$.post("/plugins/community.applications/scripts/getPopupDescription.php",{appName:'<?=$appName?>',appPath:'<?=$appNumber?>',csrf_token:'<?=$csrf_token?>'},function(data) {
		if (data) {
			var descData = JSON.parse(data);
			$("#popUpContent").hide();

			$("#popUpContent").html(descData.description);
			$('img').each(function() { // This handles any http images embedded in changelogs
				if ( $(this).hasClass('displayIcon') ) { // ie: don't change any images on the main display
					return;
				}
				var origSource = $(this).attr("src");
				if ( origSource.startsWith("http://") ) {
					var newSource = origSource.replace("http://","https://");
					$(this).attr("src",newSource);
				}
			});
			$('img').on("error",function() {
				var origSource = $(this).attr('src');
				var newSource = origSource.replace("https://","http://");
				if ( document.referrer.startsWith("https") && "<?=$communitySettings['secureImage']?>" == "secure" ) {
					$(this).attr('src',"https://github.com/Squidly271/community.applications/raw/master/webImages/no_https.png");
				} else {
					$(this).attr('src',newSource);
					$(this).on("error",function() {
						$(this).attr('src',"/plugins/dynamix.docker.manager/images/question.png");
					});
				}
			});

			if ( ! cookiesEnabled() ) {
				$(".pluginInstall").hide();
			}
			$("#popUpContent").show();
			if ( $("#trendChart").length ) {
				var ctx = document.getElementById("trendChart").getContext('2d');
				let chart = new Chart(ctx, {
					type: 'line',
					data: {
						datasets: [{
							label: "Trend Per Month",
							data: descData.trendData,
							backgroundColor: '#c0c0c0',
							borderColor: '#FF8C2F'
						}],
						labels: descData.trendLabel
					},
					options: {
						events: ["mousemove","mouseout"]
					}
				});
			}
			if ( $("#downloadChart").length ) {
				var ctx = document.getElementById("downloadChart").getContext('2d');
				let chart = new Chart(ctx, {
					type: 'line',
					data: {
						datasets: [{
							label: "Downloads Per Month",
							data: descData.downloadtrend,
							backgroundColor: '#c0c0c0',
							borderColor: '#FF8C2F'
						}],
						labels: descData.downloadLabel
					},
					options: {
						events: ["mousemove","mouseout"]
					}
				});
			}
		}
	});
});

function installPlugin(pluginURL) {
	$.cookie("installPluginURL",pluginURL,{path:"/"});
	window.parent.Shadowbox.close();
}

function cookiesEnabled() {
	return evaluateBoolean(navigator.cookieEnabled);
}

function evaluateBoolean(str) {
	regex=/^\s*(true|1|on)\s*$/i
	return regex.test(str);
}
function makePlural(string,count) {
	return ( (count > 1) || (count == 0) ) ? string + "s" : string;
}
</script>
<html>
<body>
<span id='popUpContent'><div class='spinner fixed' style='display:none;'><?readfile("/usr/local/emhttp/plugins/dynamix/images/animated-logo.svg")?></div></span>
</body>
</html>