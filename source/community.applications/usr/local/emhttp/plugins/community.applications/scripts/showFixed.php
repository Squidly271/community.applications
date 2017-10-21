<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
$moderation = @file_get_contents($communityPaths['fixedTemplates_txt']);
echo "<body bgcolor='white'>";
		
if ( ! $moderation ) {
  echo "<br><br><center><b>No templates were automatically fixed</b></center>";
} else {
	$moderation = str_replace(" ","&nbsp;",$moderation);
  $moderation = str_replace("\n","<br>",$moderation);
	echo "All of these errors found have been fixed automatically.  These errors only affect the operation of Community Applications.  <b>The template <em>may</em> have other errors present</b><br><br><tt>$moderation";
}

$dupeList = json_decode(@file_get_contents($communityPaths['pluginDupes']),true);
if ($dupeList) {
	$templates = json_decode(file_get_contents($communityPaths['community-templates-info']),true);
	echo "<br><br><b></tt>The following plugins have duplicated filenames and are not able to be installed simultaneously:</b><br><br>";
	foreach (array_keys($dupeList) as $dupe) {
		echo "<b>$dupe</b><br>";
		foreach ($templates as $template) {
			if ( basename($template['PluginURL']) == $dupe ) {
				echo "<tt>{$template['Author']} - {$template['Name']}<br></tt>";
			}
		}
		echo "<br>";
	}
	echo "<br><br>";
}
?>