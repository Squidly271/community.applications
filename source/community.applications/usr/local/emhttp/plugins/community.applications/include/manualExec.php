<?PHP
require_once("/usr/local/emhttp/plugins/community.applications/include/helpers.php");
require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");

$filename = randomFile();
download_url("https://raw.githubusercontent.com/Squidly271/ca.documentation/master/caDocumentation.html",$filename);
$manual = @file_get_contents($filename);
$manual = $manual ?: "Unable to download manual.  Try again later";
@unlink($filename);
echo $manual;
?>