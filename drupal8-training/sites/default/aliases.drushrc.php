<?php

$sitename = 'd8training.amazeelabs.com';

// - DO NOT make changes below this Comment

// Basic error handling
if($sitename == 'CHANGEME')
  die("[ERROR] - Luke, you should change the Sitename in aliases.drushrc.php!\n");

// Fetch our Distfile for magically know what servers we need to know within drush.
$amazeelabs_configuration = json_decode(file_get_contents('https://raw.github.com/AmazeeLabs/devops/master/drush-deployment/servers.json'), TRUE);
// Try to fetch the site related configs, if it fails we expect it not to be presetnt - quick and dirty
$customer_configuration = json_decode(@file_get_contents('https://raw.github.com/AmazeeLabs/devops/master/drush-deployment/customers/'.$sitename.'.json'), TRUE);

// Generate Site specifc variables like userid and siteurl
$site = array();
$site['userid'] = $sitename;
$site['siteurl'] = str_replace('_', '.', $site['userid']);

// Generate live sites aliases
foreach ($amazeelabs_configuration['live'] as $key => $value) {
    $aliases[$key] = array(
    'remote-host' => $value,
    'remote-user' => $site['userid'],
    'path-aliases' => array(
      '%drush-script' => '/home/'. $site['userid'].'/bin/drush',
      '%dump-dir' => '/home/'. $site['userid'] .'/',
    ),
    'command-specific' => array (
    'sql-sync' => array (
      'no-cache' => TRUE,
      'no-ordered-dump' => TRUE
    ),
  ),
  'root' => '/home/'. $site['userid'].'/public_html',
  'uri' => 'http://'. $site['siteurl'].'.'. $value .'/',

      );
}

// Generate DEV sites aliases
foreach ($amazeelabs_configuration['dev'] as $key => $value) {
    $aliases[$key] = array(
    'remote-host' => $value,
    'remote-user' => 'www-data',
    'path-aliases' => array(
      '%drush-script' => '/home/www-data/bin/drush',
      '%dump-dir' => '/home/www-data/',
    ),
    'command-specific' => array (
    'sql-sync' => array (
      'no-cache' => TRUE,
      'no-ordered-dump' => TRUE
    ),
  ),
  'root' => '/home/www-data/'. $site['siteurl'].'/drupal8-training',
  'uri' => 'http://'. $site['siteurl'].'.'. $value .'/',
      );
}

// if we have customer configuration, we overwrite every autogenerated configuration item
// with the customer configuration
if ($customer_configuration == true) {
  foreach ($customer_configuration as $key => $value) {
    $aliases[$key] = $value;
  }
}

// Generate Server Groups
$aliases['live'] = array(
  'site-list' => array_map(function($k){ return '@'.$k;},array_keys($amazeelabs_configuration['live']))
);

