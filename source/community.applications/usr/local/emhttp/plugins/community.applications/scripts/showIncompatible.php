<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
$moderation = @file_get_contents($communityPaths['totalIncompatible_txt']);
echo "<body bgcolor='white'>";
if ( ! $moderation ) {
  echo "<br><br><center><b>No incompatible apps found</b></center>";
  return;
}
echo "<b>While highly not recommended to do</b>, incompatible applications can be installed by enabling Display Incompatible Applications within CA's General Settings<br><br><tt>$moderation";
?>

