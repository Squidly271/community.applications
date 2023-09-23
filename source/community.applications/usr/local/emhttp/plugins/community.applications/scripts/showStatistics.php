<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2023, Andrew Zawadzki #
#                   Licenced under GPLv2                      #
#                                                             #
###############################################################

ini_set('memory_limit','256M');  // REQUIRED LINE

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";

require_once "$docroot/plugins/community.applications/include/paths.php";
require_once "$docroot/plugins/dynamix/include/Wrappers.php";
require_once "$docroot/plugins/dynamix/include/Helpers.php";

$_SERVER['REQUEST_URI'] = "docker/apps";
require_once "$docroot/plugins/dynamix/include/Translations.php";
require_once "$docroot/plugins/community.applications/include/helpers.php";

$caSettings = parse_plugin_cfg("community.applications");

function tr($string,$ret=true) {
  $string =  str_replace('"',"&#34;",str_replace("'","&#39;",_($string)));
  if ( $ret )
    return $string;
  else
    echo $string;
}

?>
<body bgcolor='white'>
<link type="text/css" rel="stylesheet" href='<?autov("/plugins/community.applications/skins/Narrow/css.php")?>'>
<style>
p {margin-left:2rem;margin-right:2rem;}
body {margin-left:1.5rem;margin-right:1.5rem;margin-top:1.5rem;font-family:clear-sans;font-size:0.9rem;}
hr { margin-top:1rem;margin-bottom:1rem; }
div.spinner{margin:48px auto;text-align:center;}
div.spinner.fixed{position:fixed;top:50%;left:50%;margin-top:-16px;margin-left:-64px}
div.spinner .unraid_mark{height:64px}
div.spinner .unraid_mark_2,div .unraid_mark_4{animation:mark_2 1.5s ease infinite}
div.spinner .unraid_mark_3{animation:mark_3 1.5s ease infinite}
div.spinner .unraid_mark_6,div .unraid_mark_8{animation:mark_6 1.5s ease infinite}
div.spinner .unraid_mark_7{animation:mark_7 1.5s ease infinite}
@keyframes mark_2{50% {transform:translateY(-40px)} 100% {transform:translateY(0px)}}
@keyframes mark_3{50% {transform:translateY(-62px)} 100% {transform:translateY(0px)}}
@keyframes mark_6{50% {transform:translateY(40px)} 100% {transform:translateY(0px)}}
@keyframes mark_7{50% {transform:translateY(62px)} 100% {transform: translateY(0px)}}
</style>
<div class='spinner fixed' id='spinner'><?readfile("/usr/local/emhttp/plugins/dynamix/images/animated-logo.svg")?></div>
<?
$repositories = readJsonFile($caPaths['repositoryList']);
switch ($_GET['arg1']) {
  case 'Repository':
    foreach ($repositories as $name => $repo) {
      $repos[$name] = $repo['url'];
    }
    ksort($repos,SORT_FLAG_CASE | SORT_NATURAL);
    echo "<tt><table>";
    foreach (array_keys($repos) as $repo) {
      echo "<tr><td><span class='ca_bold'>$repo</td><td><a class='popUpLink' href='{$repos[$repo]}' target='_blank'>{$repos[$repo]}</a></td></tr>";
    }
    echo "</table></tt>";
    break;
  case 'Invalid':
    $moderation = json_encode(json_decode(@file_get_contents($caPaths['invalidXML_txt'])),JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ( ! $moderation ) {
      echo "<br><br><div class='ca_center'><span class='ca_bold'>".tr("No invalid templates found")."</span></div>";
      return;
    }
    $moderation = str_replace(" ","&nbsp;",$moderation);
    $moderation = str_replace("\n","<br>",$moderation);
    echo "<tt>".tr("These templates are invalid and the application they are referring to is unknown")."<br><br>$moderation";
    break;
  case 'Fixed':
    $moderation = @file_get_contents($caPaths['fixedTemplates_txt']);
    if ( ! $moderation ) {
      echo "<br><br><div class='ca_center'><span class='ca_bold'>".tr("No templates were automatically fixed")."</span></div>";
    } else {
      $json = json_decode($moderation,true);
      ksort($json,SORT_NATURAL | SORT_FLAG_CASE);
      echo tr("All of these errors found have been fixed automatically")."<br><br>".tr("Note that many of these errors can be avoided by following the directions")." <a href='https://forums.unraid.net/topic/57181-real-docker-faq/#comment-566084' target='_blank'>".tr("HERE")."</a><br><br>";
      foreach (array_keys($json) as $repository) {
        echo "<br><b><span style='font-size:20px;'>$repository</span></b><br>";
        foreach (array_keys($json[$repository]) as $repo) {
          echo "<code>&nbsp;&nbsp;&nbsp;&nbsp;<b><span style='font-size:16px;'>$repo:</span></b><br>";
          foreach ($json[$repository][$repo] as $error) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".str_replace(" ","&nbsp;",$error)."<br>";
          }
          echo "</code>";
        }
      }
    }

    $dupeList = readJsonFile($caPaths['pluginDupes']);
    if ($dupeList) {
      $templates = readJsonFile($caPaths['community-templates-info']);
      echo "<br><br><span class='ca_bold'></tt>".tr("The following plugins have duplicated filenames and are not able to be installed simultaneously:")."</span><br><br>";
      foreach (array_keys($dupeList) as $dupe) {
        echo "<span class='ca_bold'>$dupe</span><br>";
        foreach ($templates as $template) {
          if ( basename($template['PluginURL']??"") == $dupe ) {
            echo "<tt>{$template['Author']} - {$template['Name']}<br></tt>";
          }
        }
        echo "<br>";
      }
    }
    $templates = readJsonFile($caPaths['community-templates-info']);
    $dupeRepos = "";
    foreach ($templates as $template) {
      $template['Repository'] = str_replace(":latest","",$template['Repository']);
      $count = 0;
      foreach ($templates as $searchTemplates) {
        if ( $template['Language'] ) continue;
        if ( (str_replace(["lscr.io/","ghcr.io/"],"",$template['Repository']) == str_replace(":latest","",str_replace(["lscr.io/","ghcr.io/"],"",$searchTemplates['Repository'])))  ) {
          if ( $searchTemplates['BranchName'] || $searchTemplates['Blacklist'] || $searchTemplates['Deprecated']) {
            continue;
          }
          $count++;
        }
      }
      if ($count > 1 ) {
        $dupeRepos .= "Duplicated Template: {$template['RepoName']} - {$template['Repository']} - {$template['Name']}<br>";
      }
    }
    if ( $dupeRepos ) {
      echo "<br><span class='ca_bold'></tt>".tr("The following docker applications refer to the same docker repository but may have subtle changes in the template to warrant this")."</span><br><br><tt>$dupeRepos";
    }

    break;
  case 'Moderation':
    echo "<br><div class='ca_center'><strong>".tr("If any of these entries are incorrect then contact the moderators of CA to discuss")."</strong></div><br><br>";
    $moderation = file_get_contents($caPaths['moderation']);
    $repoComment = "";
    foreach ($repositories as $repo) {
      if ($repo['RepoComment']??false) {
        $repoComment .= "<tr><td>{$repo['name']}</td><td>{$repo['RepoComment']}</td></tr>";
      }
    }
    if ( $repoComment ) {
      echo "<br><div class='ca_center'><strong>".tr("Global Repository Comments:")."</strong><br>".tr("(Applied to all applications)")."</div><br><br><tt><table>$repoComment</table><br><br>";
    }
    if ( ! $moderation ) {
      echo "<br><br><div class='ca_center'><span class='ca_bold'>No moderation entries found</span></div>";
    }
    echo "</tt><div class='ca_center'><strong>".tr("Individual Application Moderation")."</strong></div><br><br>";
    $moderation = str_replace(" ","&nbsp;",$moderation);
    $moderation = str_replace("\n","<br>",$moderation);
    echo "<tt>$moderation";
    break;
}
?>
<script>
  document.getElementById("spinner").style.display = "none";
</script>