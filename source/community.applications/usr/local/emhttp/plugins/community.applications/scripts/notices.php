#!/usr/bin/php
<?PHP
require_once "/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php";
require_once "/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php";


$paths['notices'] = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/CA_notices.json";
$debugging = true;
$DockerClient = new DockerClient();

function debug($message) {
	global $debugging;
	
	if ($debugging) echo $message;
}
	

function readJsonFile($filename) {
	$json = json_decode(@file_get_contents($filename),true);
	return is_array($json) ? $json : array();
}
function writeJsonFile($filename,$jsonArray) {
	file_put_contents($filename,json_encode($jsonArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}
function download_url($url, $path = "", $bg = false, $timeout = 45) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_FRESH_CONNECT,true);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,15);
	curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
	curl_setopt($ch,CURLOPT_ENCODING,"");
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$out = curl_exec($ch);
	curl_close($ch);
	if ( $path )
		file_put_contents($path,$out);

	return $out ?: false;
}
function download_json($url,$path="") {
	return json_decode(download_url($url,$path),true);
}

function startsWith($haystack, $needle) {
	if ( !is_string($haystack) || ! is_string($needle) ) return false;
	return $needle === "" || strripos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

function conditionsMet($value) {
	global $conditionsMet;
	
	if ($value) {
		debug("  Passed\n");
	} else {
		$conditionsMet = false;
		debug("  Failed\n");
	}
}
		

############## MAIN ##############3

$notices = download_json($paths['notices']);
if ( ! is_array($notices) ) $notices = array();

debug(print_r($notices,true));

foreach ( $notices as $app => $notice ) {
	$found = false;

	if ( startsWith($app,"https://") || strtolower(pathinfo($app,PATHINFO_EXTENSION)) == "plg")  {
		$plugin = true;
	} else {
		$plugin = false;
	}
	if ( ! $plugin ) {
		$info = $DockerClient->getDockerContainers();
		$search = explode(":",$app);
		if ( ! $search[1] ) {
			$app .= ":latest";
		}
		foreach($info as $container) {
			if ( $search[1] == "*" ) {
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
			} else {
				$found = true;
			}
		}
	}
	if ( $found ) {
		debug("Found $app   Looking for conditions\n");
		$conditionsMet = true;
		if ( $notice['Conditions']['unraid'] ) {
			$unraid = parse_ini_file("/etc/unraid-version");
			$unraidVersion = $unraid['version'];
			foreach ($notice['Conditions']['unraid'] as $condition) {
				if ( ! $conditionsMet ) break;
				debug("Testing unraid version $unraidVersion {$condition[0]} {$condition[1]}");
				conditionsMet(version_compare($unraidVersion,$condition[1],$condition[0]));
			}

		}
	}
	if ( $plugin && $notice['Conditions']['plugin'] ) {
		$pluginVersion = @plugin("version","/var/log/plugins/".basename($app));
		if ( ! $pluginVersion ) {
			debug("Unable to determine plugin version.  Carrying on");
			continue;
		}
		foreach ($notice['Conditions']['plugin'] as $condition) {
			if ( ! $conditionsMet ) break;
			debug("Testing plugin version $pluginVersion {$condition[0]} {$condition[1]}");
			$cmp = strcmp($pluginVersion,$condition[1]);
// do some operator substitutions
			switch($condition[0]) {
				case "=":
					$condition[0] = "==";
					break;
				case "=<":
					$condition[0] = "<=";
					break;
				case "le":
					$condition[0] = "<=";
					break;
				case ">=":
					$condition[0] = ">=";
					break;
				case "gt":
					$condition[0] = ">=";
					break;
				case "ne":
					$condition[0] = "!";
					break;
				case "<>":
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

	if ( $notice['Conditions']['code'] && $conditionsMet) {
		debug("Executing {$notice['Conditions']['code']}");
		conditionsMet(eval($notice['Conditions']['code']));
	}

	if ($conditionsMet) {
		debug("Conditions Met.  Send the notification!\n");
	} else {
		debug("Conditions not met.  Do nothing!\n");
	}
	debug("\n");
	

		
	
	
	
}

?>