<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2020, Andrew Zawadzki #
#          Licenced under the terms of GNU GPLv2              #
#                                                             #
###############################################################
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

require_once "$docroot/plugins/dynamix/include/Wrappers.php";
require_once "$docroot/plugins/dynamix/include/Helpers.php";
require_once "$docroot/plugins/community.applications/include/paths.php";

$unRaidVars     = parse_ini_file("/var/local/emhttp/var.ini");
$caSettings     = parse_plugin_cfg("community.applications");
$csrf_token     = $unRaidVars['csrf_token'];
$appNumber      = urldecode($_GET['appPath']);
$appName        = urldecode($_GET['appName']);
$appName        = str_replace("'","",$appName);

$translations = is_file("$docroot/plugins/dynamix/include/Translations.php");

if ( $translations ) {
	$_SERVER['REQUEST_URI'] = "docker/apps";
	require_once("$docroot/plugins/dynamix/include/Translations.php");
}


function tr($string,$ret=false) {
	if ( function_exists("_") )
		$string =  str_replace('"',"&#34;",str_replace("'","&#39;",_($string)));

	if ( $ret )
		return $string;
	else
		echo $string;
}
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
var csrf_token = "<?=$csrf_token?>";
$(function() {
	$.removeCookie("ca_installPluginURL",{path:"/"});

	setTimeout(function() {
		$(".spinner").show();
	},250);
	$.post("/plugins/community.applications/include/exec.php",{action:'getPopupDescription',appName:'<?=$appName?>',appPath:'<?=$appNumber?>',csrf_token:csrf_token},function(result) {
		try {
			var descData = JSON.parse(result);
		} catch(e) {
			var descData = new Object();
			descData.description = result;
		}
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
			$(this).attr('src',"/plugins/dynamix.docker.manager/images/question.png");
		});

		if ( ! cookiesEnabled() ) {
			$(".pluginInstall").hide();
		}
		$("#popUpContent").show();
		if ( $("#trendChart").length ) {
			var fontSize = 14;

			if (descData.trendLabel.length > 3) {
				var fontSize = 12;
			}
			if (descData.trendLabel.length > 6) {
				var fontSize = 11;
			}
			if (descData.trendLabel.length > 8) {
				var fontSize = 8;
			}

			var ctx = document.getElementById("trendChart").getContext('2d');
			let chart = new Chart(ctx, {
				type: 'line',
				data: {
					datasets: [{
						data: descData.trendData,
						borderColor: '#FF8C2F',
						trendlineLinear: {
							style: "rgb(255 ,66 ,255)",
              lineStyle: "dotted",
              width: 2
            }
					}],
					labels: descData.trendLabel
				},
				options: {
					tooltips: {
						callbacks: {
							label: function(tooltipItem,data) {
								return tooltipItem.yLabel.toLocaleString()+"%";
							}
						}
					},
					title: {
						display: true,
						text: "<?tr("Trend Per Month");?>",
						fontSize: 16
					},
					legend: {
						display: false
					},
					events: ["mousemove","mouseout"],
					scales: {
						yAxes: [{
							ticks: {
								callback: function(label,index,labels) {
									return label + " %";
								},
								precision: 0
							}
						}],
						xAxes: [{
							ticks: {
								fontSize: fontSize
							}
						}]
					}
				}
			});
		}
		if ( $("#downloadChart").length ) {
			var ctx = document.getElementById("downloadChart").getContext('2d');
			let chart = new Chart(ctx, {
				type: 'line',
				data: {
					datasets: [{
						data: descData.downloadtrend,
						borderColor: '#FF8C2F',
						trendlineLinear: {
							style: "rgb(255 ,66 ,255)",
              lineStyle: "dotted",
              width: 2
            }
					}],
					labels: descData.downloadLabel
				},
				options: {
					tooltips: {
						callbacks: {
							label: function(tooltipItem,data) {
								return tooltipItem.yLabel.toLocaleString();
							}
						}
					},
					title: {
						display: true,
						text: "<?tr("Downloads Per Month")?>",
						fontSize: 16
					},
					legend: {
						display: false
					},
					events: ["mousemove","mouseout"],
					scales: {
						yAxes: [{
							ticks: {
								callback: function(label,index,labels) {
									return label.toLocaleString();
								}
							}
						}],
						xAxes: [{
							ticks: {
								fontSize: fontSize
							}
						}]
					}
				}
			});
		}
		if ( $("#totalDownloadChart").length ) {
			var ctx = document.getElementById("totalDownloadChart").getContext('2d');
			let chart = new Chart(ctx, {
				type: 'line',
				data: {
					datasets: [{
						data: descData.totaldown,
						backgroundColor: '#c0c0c0',
						borderColor: '#FF8C2F'
					}],
					labels: descData.totaldownLabel
				},
				options: {
					tooltips: {
						callbacks: {
							label: function(tooltipItem,data) {
								return tooltipItem.yLabel.toLocaleString();
							}
						}
					},
					title: {
						display: true,
						text: "<?tr("Total Downloads");?>",
						fontSize: 16
					},
					legend: {
						display: false
					},
					events: ["mousemove","mouseout"],
					scales: {
						yAxes: [{
							ticks: {
								callback: function(label,index,labels) {
									return label.toLocaleString();
								}
							}
						}],
						xAxes: [{
							ticks: {
								fontSize: fontSize
							}
						}]
					}
				}
			});
		}
	});
});


function installPlugin(pluginURL) {
	$.cookie("ca_installPluginURL",pluginURL,{path:"/"});
	window.parent.Shadowbox.close();
}

function cookiesEnabled() {
	return evaluateBoolean(navigator.cookieEnabled);
}

function evaluateBoolean(str) {
	regex=/^\s*(true|1|on)\s*$/i
	return regex.test(str);
}

function openNewModalWindow(newURL) {
	var popUp = window.open(newURL,"_parent");
	if ( !popUp || popUp.closed || typeof popUp == "undefined" ) {
		alert("<?tr("Popup Blocked CA requires popups to be enabled under certain circumstances.  You must white list your server within your browser to allow popups")?>");
	}
}

function xmlInstall(type,xml) {
	$.post("/plugins/community.applications/include/exec.php",{action:'createXML',xml:xml,csrf_token:csrf_token},function(data) {
		try {
			var result = JSON.parse(data);
		} catch(e) {
			var result = new Object();
			result = data;
		}
		console.log(result);
		if ( result.status == "ok" ) {
			openNewModalWindow("/Apps/AddContainer?xmlTemplate="+type+":"+xml);
		}
	});
}

function CAswitchLanguage(language) {
	$.cookie("ca_languageSwitch",language,{path:"/"});
	window.parent.Shadowbox.close();
}

</script>
<html>
<body>
<span id='popUpContent'><div class='spinner fixed' style='display:none;'><?readfile("$docroot/plugins/dynamix/images/animated-logo.svg")?></div></span>
</body>
</html>