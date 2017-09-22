<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");

$repositories = json_decode(file_get_contents($communityPaths['Repositories']),true);
echo "<tt>";

foreach ($repositories as $repo) {
	$repos[$repo['name']] = $repo['url'];
}
ksort($repos,SORT_FLAG_CASE | SORT_NATURAL);
echo "<table>";
foreach (array_keys($repos) as $repo) {
  echo "<tr><td><b>$repo</td><td><a href='{$repos[$repo]}' target='_blank'>{$repos[$repo]}</a></td></tr>";
}
echo "</table>";
echo "</tt>";
?>