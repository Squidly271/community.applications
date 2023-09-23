<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2023, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

$login_locale = true;

require_once "$docroot/plugins/dynamix.docker.manager/include/DockerClient.php";
require_once "$docroot/plugins/dynamix.plugin.manager/include/PluginHelpers.php";
require_once "$docroot/plugins/dynamix/include/Wrappers.php";
require_once "$docroot/plugins/community.applications/include/helpers.php";
require_once "$docroot/plugins/community.applications/include/paths.php";

$paths['notices_remote'] = "https://raw.githubusercontent.com/Squidly271/CA_notifications/master/CA_notices.json";
$paths['CA_root']        = "/tmp/ca_notices";
$paths['notices']        = "{$paths['CA_root']}/CA_notices.json";
$paths['bannerNotices']  = "{$paths['CA_root']}/notices";
$paths['local']          = "/tmp/GitHub/CA_notifications/CA_notices.json";  // only used when run from the command line for debugging
$paths['dismiss']        = "/boot/config/plugins/community.applications/notifications_dismissed.json";

$caSettings = $cfg = parse_plugin_cfg("community.applications");
@mkdir($caPaths['CA_logs'],0777,true);
if ( $cfg['notifications'] == "no" ) {
  echo json_encode([]);
  exit();
}

exec("mkdir -p {$paths['CA_root']}");

$local = false;  // ONLY SET TO TRUE FOR DEBUGGING.  MUST BE FALSE FOR RELEASES

$action = $_POST['action'] ?? null;
// check if started from command prompt or gui
if ( ! $action ){
  $debugging = true;
  $sendNotification = true;
  $action = 'scan';
}

if ( is_file("/var/run/dockerd.pid") && is_dir("/proc/".@file_get_contents("/var/run/dockerd.pid")) ) {
  $dockerRunning = true;
  $DockerClient = new DockerClient();
} else
  $dockerRunning = false;

function debug1($message) {
  global $debugging;

  if ($debugging) echo $message;
}

function conditionsMet($value) {
  global $conditionsMet;

  if ($value)
    debug1("  Passed\n");
  else {
    $conditionsMet = false;
    debug1("  Failed\n");
  }
}

############## MAIN ##############
switch ($action) {
  case 'scan':
    if ( $local ) {
      $notices = readJsonFile($paths['local']);
      copy($paths['local'],$paths['notices']);
    } else {
      if ( is_file($paths['notices']) && ( time() - filemtime($paths['notices']) < 604800 ) ) {
        // Only send one notification per week
        $notices = readJsonFile($paths['notices']);
        $sendNotification = false;
      } else {
//				exec("logger downloading new notifications");
        $notices = download_json($paths['notices_remote'],$paths['notices']);
      }
    }

    if ( $local && ! is_array($notices) ) {
      debug1("Not a valid local json file");
      return;
    }

    if ( ! is_array($notices) ) $notices = array();
    $dismissed = readJsonFile($paths['dismiss']);
    foreach ( $notices as $app => $notice ) {
      if ( in_array($notice['ID'],$dismissed) )
        continue;

      debug1("Searching for $app");
      $found = false;

      $plugin = ( startsWith($app,"https://") || strtolower(pathinfo($app,PATHINFO_EXTENSION)) == "plg");

      if ( ! $plugin && $dockerRunning) {
        $info = $DockerClient->getDockerContainers();
        $search = explode(":",$app);
        if ( ! ($search[1] ?? false) )
          $app .= ":latest";
        
        foreach($info as $container) {
          if ( ($search[1] ?? "") == "*" ) {
            if ( explode(":",$container['Image'])[0] == $search[0])
              $found = true;
              break;
          }
          if ($container['Image'] == $app) {
            $found = true;
            break;
          }
        }
      } else {
        if ( is_file("/var/log/plugins/".basename($app)) ) {
          if ( startswith($app,"https:") ) {
            if ( plugin("pluginURL","/var/log/plugins/".basename($app)) == $app) {
              $found = true;
            }
          } else
            $found = true;
        }
      }
      if ( $found ) {
        debug1("   Found  Looking for conditions\n");
        $conditionsMet = true;
        if ( $notice['Conditions']['unraid'] ?? false ) {
          $unraid = parse_ini_file("/etc/unraid-version");
          $unraidVersion = $unraid['version'];
          foreach ($notice['Conditions']['unraid'] as $condition) {
            if ( ! $conditionsMet ) break;
            debug1("Testing unraid version $unraidVersion {$condition[0]} {$condition[1]}");
            conditionsMet(version_compare($unraidVersion,$condition[1],$condition[0]));
          }
        }
      } else {
        debug1("  Not Found");
        continue;
      }

      if ( $plugin && ($notice['Conditions']['plugin'] ?? false) ) {
        $pluginVersion = @plugin("version","/var/log/plugins/".basename($app));
        if ( ! $pluginVersion ) {
          debug1("Unable to determine plugin version.  Carrying on");
          continue;
        }
        foreach ($notice['Conditions']['plugin'] as $condition) {
          if ( ! $conditionsMet ) break;
          debug1("Testing plugin version $pluginVersion {$condition[0]} {$condition[1]}");
          $cmp = strcmp($pluginVersion,$condition[1]);
    // do some operator substitutions
          switch($condition[0]) {
            case "=":
              $condition[0] = "==";
              break;
            case "eq":
              $condition[0] = "==";
              break;
            case "=<":
              $condition[0] = "<=";
              break;
            case "le":
              $condition[0] = "<=";
              break;
            case "lt":
              $condition[0] = "<";
              break;
            case ">=":
              $condition[0] = ">=";
              break;
            case "ge":
              $condition[0] = ">=";
              break;
            case "gt":
              $condition[0] = ">";
            case "ne":
              $condition[0] = "!";
              break;
            case "<>":
              $condition[0] = "!";
              break;
            case "!=":
              $condition[0] = "!";
              break;
          }

          switch ($condition[0]) {
            case "<":
              conditionsMet($cmp < 0);
              break;
            case ">":
              conditionsMet($cmp > 0);
              break;
            case "==":
              conditionsMet($cmp == 0);
              break;
            case "<=":
              conditionsMet($cmp < 1);
              break;
            case "=>":
              conditionsMet($cmp > -1);
              break;
            case "!":
              conditionsMet($cmp != 0);
              break;
          }
        }
      }

      if ( ($notice['Conditions']['code'] ?? false) && $conditionsMet) {
        debug1("Executing {$notice['Conditions']['code']}");
        conditionsMet(eval($notice['Conditions']['code']));
      }
  
      $unRaidNotifications = [];
      if ($conditionsMet) {
        debug1("Conditions Met.  Send the notification!\n");
        if ( $sendNotification ) {
          $command = "/usr/local/emhttp/plugins/dynamix/scripts/notify -b -e 'Community Applications Background Scanning' -s 'Attention Required' -d ".escapeshellarg($notice['email']."  Login to your server for more detail.  To not receive this notification again, dismiss the banner when logged into your server")." -i 'warning'";
          exec($command);
        }
        $notice['App'] = $app;
        $unRaidNotifications[] = $notice;
      } else {
        debug1("Conditions not met.  Do nothing!\n");
      }
      debug1("\n");
    }
    echo json_encode($unRaidNotifications ?: [],JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    break;
  case 'dismiss':
    $notifications = readJsonFile($paths['dismiss']);
    $notifications[] = $_POST['ID'];
    writeJsonFile($paths['dismiss'],$notifications);
    break;
}
?>