<?PHP
function download_url($url, $path = "", $bg = false){
  exec("curl --compressed --max-time 60 --silent --insecure --location --fail ".($path ? " -o '$path' " : "")." $url ".($bg ? ">/dev/null 2>&1 &" : "2>/dev/null"), $out, $exit_code );
  return ($exit_code === 0 ) ? implode("\n", $out) : false;
}

function randomFile() {
  return tempnam("/tmp","CA-Temp-");
}

$filename = randomFile();
download_url("https://raw.githubusercontent.com/Squidly271/ca.documentation/master/caDocumentation.html",$filename);
$manual = @file_get_contents($filename);
$manual = $manual ? $manual : "Unable to download manual.  Try again later";
@unlink($filename);
echo $manual;
?>