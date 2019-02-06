<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2019, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/community.applications/include/paths.php");

function startsWith($haystack, $needle) {
  return $needle === "" || strripos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

# Modify the system file to avoid a harmless error from being displayed under normal circumstances
# Not needed under unRaid 6.6.3+
$unRaidVersion = parse_ini_file($communityPaths['unRaidVersion']);

if ( version_compare($unRaidVersion['version'],"6.6.2",">=") ) {
  $exeFile = "/usr/local/emhttp/plugins/dynamix.docker.manager/include/CreateDocker.php";
} else {
  $exeFile = "/tmp/community.applications/tempFiles/newCreateDocker.php";
  $dockerInstall = file("/usr/local/emhttp/plugins/dynamix.docker.manager/include/CreateDocker.php",FILE_IGNORE_NEW_LINES);
  foreach ($dockerInstall as $line) {
    if ( startsWith(trim($line),"removeContainer(") ) {
      $line = "#$line";
    }
    $newInstall[] = $line;
  }
  file_put_contents($exeFile,implode("\n",$newInstall));
  chmod($exeFile,0777);
}
$javascript = file_get_contents("/usr/local/emhttp/plugins/dynamix/javascript/dynamix.js");
echo "<script>$javascript</script>";

if ( $_GET['docker'] ) {
  echo "<div id='output'>";
  $dockers = explode(",",$_GET['docker']);
  echo "Installing docker applications ".str_replace(",",", ",$_GET['docker'])."<br>";
  $_GET['updateContainer'] = true;
  $_GET['ct'] = $dockers;
  $_GET['communityApplications'] = true;
  include($exeFile);
  echo "</div>";
?>
<script>
$("input,#output").hide();

var cursor = "";
function addLog(logLine) {
  var scrollTop = (window.pageYOffset !== undefined) ? window.pageYOffset : (document.documentElement || document.body.parentNode).scrollTop;
  var clientHeight = (document.documentElement || document.body.parentNode).clientHeight;
  var scrollHeight = (document.documentElement || document.body.parentNode).scrollHeight;
  var isScrolledToBottom = scrollHeight - clientHeight <= scrollTop + 1;
  if (logLine.slice(-1) == "\n") {
    document.body.innerHTML = document.body.innerHTML.slice(0,cursor) + logLine.slice(0,-1) + "<br>";
    lastLine = document.body.innerHTML.length;
    cursor = lastLine;
  }
  else if (logLine.slice(-1) == "\r") {
    document.body.innerHTML = document.body.innerHTML.slice(0,cursor) + logLine.slice(0,-1);
    cursor = lastLine;
  }
  else if (logLine.slice(-1) == "\b") {
    if (logLine.length > 1)
      document.body.innerHTML = document.body.innerHTML.slice(0,cursor) + logLine.slice(0,-1);
    cursor += logLine.length-2;
  }
  else {
    document.body.innerHTML += logLine;
    cursor += logLine.length;
  }
  if (isScrolledToBottom) {
    window.scrollTo(0,document.body.scrollHeight);
  }
}
function addCloseButton() {
  addLog("<p class='centered'><button class='logLine' type='button' onclick='" + (top.Shadowbox ? "top.Shadowbox" : "window") + ".close()'>Done</button></p>");
}
</script>
<?
  foreach ($dockers as $docker) {
    echo "Starting <b>$docker</b><br>";
    unset($output);
    exec("docker start $docker 2>&1",$output,$retval);
    if ($retval) {
      $failFlag = true;
      echo "<b>$docker</b> failed to start.  You should install it by itself to fix the errors<br>";
      foreach ($output as $line) {
        echo "<tt>$line</tt><br>";
      }
      echo "<br>";
    }
  }
  echo "<br>Setting installed applications to autostart<br>";
  $autostartFile = @file("/var/lib/docker/unraid-autostart",FILE_IGNORE_NEW_LINES);
  if ( ! $autostartFile ) {
    $autostartFile = array();
  }
  foreach ($autostartFile as $line) {
    $autostart[$line] = true;
  }
  foreach ($dockers as $docker) {
    $autostart[$docker] = true;
  }
  $autostartFile = implode("\n",array_keys($autostart));
  file_put_contents("/var/lib/docker/unraid-autostart",$autostartFile);

  if ( $failFlag || !$_GET['plugin']) {
    echo "<br>Docker Application Installation finished.<br><script>addCloseButton();</script>";
  } else {
    echo "<script>top.Shadowbox.close();</script>";
  }
  @unlink("/tmp/community.applications/tempFiles/newCreateDocker.php");
}
?>