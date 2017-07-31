<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
$moderation = @file_get_contents($communityPaths['invalidXML_txt']);
echo "<body bgcolor='white'>";
if ( ! $moderation ) {
  echo "<br><br><center><b>No invalid templates found</b></center>";
  return;
}
$moderation = str_replace(" ","&nbsp;",$moderation);
$moderation = str_replace("\n","<br>",$moderation);
echo "<tt>These templates are invalid and the application they are referring to is unknown<br><br>$moderation";
?>

