<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
$moderation = @file_get_contents($communityPaths['noSupport_txt']);
echo "<body bgcolor='white'>";
if ( ! $moderation ) {
  echo "<br><br><center><b>All applications have support threads</b></center>";
  return;
}
echo "These applications do not have any support thread specified by the template author.<br><br>$moderation";
?>

