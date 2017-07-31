<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");
$moderation = @file_get_contents($communityPaths['fixedTemplates_txt']);
echo "<body bgcolor='white'>";
if ( ! $moderation ) {
  echo "<br><br><center><b>No templates were automatically fixed</b></center>";
  return;
}
$moderation = str_replace(" ","&nbsp;",$moderation);
$moderation = str_replace("\n","<br>",$moderation);
echo "All of these errors found have been fixed automatically.  These errors only affect the operation of Community Applications.  <b>The template <em>may</em> have other errors present</b><br><br><tt>$moderation";
?>

