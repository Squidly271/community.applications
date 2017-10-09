<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
$moderation = @file_get_contents($communityPaths['moderation']);
$repositories = json_decode(file_get_contents($communityPaths['Repositories']),true);
echo "<body bgcolor='white'>";
foreach ($repositories as $repo) {
	if ($repo['RepoComment']) {
		$repoComment .= "<tr><td>{$repo['name']}</td><td>{$repo['RepoComment']}</td></tr>";
	}
}
if ( $repoComment ) {
	echo "<br><center><strong>Global Repository Comments:</strong></center><br><br><tt><table>$repoComment</table><br><br>";
}
if ( ! $moderation ) {
  echo "<br><br><center><b>No moderation entries found</b></center>";
}
echo "</tt><center><strong>Individual Application Moderation</strong></center><br><br>";
$moderation = str_replace(" ","&nbsp;",$moderation);
$moderation = str_replace("\n","<br>",$moderation);
echo "<tt>$moderation";
?>

