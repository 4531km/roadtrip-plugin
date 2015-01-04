<?php
/*
Plugin Name: Roadtrip-Plugin
Plugin URI: http://4531km.eu
Description:
Author: 
Version: 1.0
Author URI:
*/

require_once('admin/init.php');

require_once('output/map.php');
require_once('output/timeline.php');

function miz_get_oldest_update() {
  $mizusers = get_option('miz-menu-users');
  uasort($mizusers, 'miz_determine_oldest_update');

  $userID = key($mizusers);
  $services = $mizusers[$userID];

  foreach($services as $service => $status) {
    if($service == 'updated_at') continue;
    $call = 'miz_' . $service . '_init';
    require_once('modules/' . $service . '.php');
    call_user_func($call, get_user_meta($userID, $service, true), $userID);
  }

  $services['updated_at'] = time();
  $mizusers[$userID] = $services;
  update_option('miz-menu-users', $mizusers);
}

function miz_determine_oldest_update($a, $b) {
  if($a['updated_at'] == $b['updated_at']) return 0;

  return ($a['updated_at'] < $b['updated_at']) ? -1 : 1;
}