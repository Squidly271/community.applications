<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
$moderation = @file_get_contents($communityPaths['blacklisted_txt']);
echo "<body bgcolor='white'>";
if ( ! $moderation ) {
  echo "<br><br><center><b>No blacklisted apps found</b></center>";
  return;
}
$moderation = str_replace(" ","&nbsp;",$moderation);
$moderation = str_replace("\n","<br>",$moderation);
echo "These applications are still found within the application feed.  CA will never allow you to install or reinstall these applications<br><br><tt>$moderation";
?>

