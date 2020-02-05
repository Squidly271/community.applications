<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2020, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

require_once "/usr/local/emhttp/plugins/community.applications/include/paths.php";
require_once "/usr/local/emhttp/plugins/community.applications/include/helpers.php";
require_once "/usr/local/emhttp/plugins/dynamix/include/Helpers.php";
?>
<body bgcolor='white'>
<link type="text/css" rel="stylesheet" href='<?autov("/plugins/community.applications/skins/Narrow/css.php")?>'>
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
<div class='spinner fixed' id='spinner'><?readfile("/usr/local/emhttp/plugins/dynamix/images/animated-logo.svg")?></div>
<?
$repositories = download_json($caPaths['community-templates-url'],$caPaths['Repositories']);

switch ($_GET['arg1']) {
	case 'Repository':
		foreach ($repositories as $repo) {
			$repos[$repo['name']] = $repo['url'];
		}
		ksort($repos,SORT_FLAG_CASE | SORT_NATURAL);
		echo "<tt><table>";
		foreach (array_keys($repos) as $repo) {
			echo "<tr><td><span class='ca_bold'>$repo</td><td><a href='{$repos[$repo]}' rel='noreferrer' target='_blank'>{$repos[$repo]}</a></td></tr>";
		}
		echo "</table></tt>";
		break;
	case 'Invalid':
		$moderation = @file_get_contents($caPaths['invalidXML_txt']);
		if ( ! $moderation ) {
			echo "<br><br><div class='ca_center'><span class='ca_bold'>No invalid templates found</span></div>";
			return;
		}
		$moderation = str_replace(" ","&nbsp;",$moderation);
		$moderation = str_replace("\n","<br>",$moderation);
		echo "<tt>These templates are invalid and the application they are referring to is unknown<br><br>$moderation";
		break;
	case 'Fixed':
		$moderation = @file_get_contents($caPaths['fixedTemplates_txt']);
 		$json = json_decode($moderation,true);
		ksort($json,SORT_NATURAL | SORT_FLAG_CASE);
		if ( ! $moderation ) {
			echo "<br><br><div class='ca_center'><span class='ca_bold'>No templates were automatically fixed</span></div>";
		} else {
			echo "All of these errors found have been fixed automatically.<br><br>Note that many of these errors can be avoided by following the directions <a href='https://forums.unraid.net/topic/57181-real-docker-faq/#comment-566084' rel='noreferrer' target='_blank'>HERE</a><br><br>";
			foreach (array_keys($json) as $repository) {
				echo "<br><b><span style='font-size:20px;'>$repository</span></b><br>";
				foreach (array_keys($json[$repository]) as $repo) {
					echo "<code>&nbsp;&nbsp;&nbsp;&nbsp;<b><span style='font-size:16px;'>$repo:</span></b><br>";
					foreach ($json[$repository][$repo] as $error) {
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".str_replace(" ","&nbsp;",$error)."<br>";
					}
					echo "</code>";
				}
			}
		}	

		$dupeList = readJsonFile($caPaths['pluginDupes']);
		if ($dupeList) {
			$templates = readJsonFile($caPaths['community-templates-info']);
			echo "<br><br><span class='ca_bold'></tt>The following plugins have duplicated filenames and are not able to be installed simultaneously:</span><br><br>";
			foreach (array_keys($dupeList) as $dupe) {
				echo "<span class='ca_bold'>$dupe</span><br>";
				foreach ($templates as $template) {
					if ( basename($template['PluginURL']) == $dupe ) {
						echo "<tt>{$template['Author']} - {$template['Name']}<br></tt>";
					}
				}
				echo "<br>";
			}
		}
		$templates = readJsonFile($caPaths['community-templates-info']);
		foreach ($templates as $template) {
			$count = 0;
			foreach ($templates as $searchTemplates) {
				if ( ($template['Repository'] == $searchTemplates['Repository'])  ) {
					if ( $searchTemplates['BranchName'] || $searchTemplates['Blacklist'] || $searchTemplates['Deprecated']) {
						continue;
					}
					$count++;
				}
			}
			if ($count > 1) {
				$dupeRepos .= "Duplicated Template: {$template['RepoName']} - {$template['Repository']} - {$template['Name']}<br>";
			}
		}
		if ( $dupeRepos ) {
			echo "<br><span class='ca_bold'></tt>The following docker applications refer to the same docker repository, but may have subtle changes in the template to warrant this</span><br><br><tt>$dupeRepos";
		}

		break;
	case 'Moderation':
		echo "<br><div class='ca_center'><strong>If any of these entries are incorrect, then contact the author / moderators of CA to discuss</strong></div><br><br>";
		$moderation = file_get_contents($caPaths['moderation']);
		foreach ($repositories as $repo) {
			if ($repo['RepoComment']) {
				$repoComment .= "<tr><td>{$repo['name']}</td><td>{$repo['RepoComment']}</td></tr>";
			}
		}
		if ( $repoComment ) {
			echo "<br><div class='ca_center'><strong>Global Repository Comments:</strong><br>(Applied to all applications)</div><br><br><tt><table>$repoComment</table><br><br>";
		}
		if ( ! $moderation ) {
			echo "<br><br><div class='ca_center'><span class='ca_bold'>No moderation entries found</span></div>";
		}
		echo "</tt><div class='ca_center'><strong>Individual Application Moderation</strong></div><br><br>";
		$moderation = str_replace(" ","&nbsp;",$moderation);
		$moderation = str_replace("\n","<br>",$moderation);
		echo "<tt>$moderation";
		break;
}
?>
<script>
	document.getElementById("spinner").style.display = "none";
</script>	