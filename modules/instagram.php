<?php

require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

function miz_instagram_init($user, $userID) {

  if(empty($user)) return;


  $uID = miz_instagram_getUID($user);
  if(!$uID) return;

  $mizsettings = get_option('miz-menu-optionen');

  $query = array(
    'access_token' => $mizsettings['instagram']['accesstoken'],
  );
  $url = 'https://api.instagram.com/v1/users/' . $uID . '/media/recent?'.http_build_query($query);

  try {
    $curl_connection = curl_init($url);
    curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);

    //Data are stored in $data
    $data = json_decode(curl_exec($curl_connection), true);
    curl_close($curl_connection);

    foreach($data['data'] as $image) {

      $existingPost = new WP_Query('post_type=miz&post_status=publish&meta_key=uID&meta_value=' . $image['id']);
      if($existingPost->post_count > 0 ) break;

      $post = array(
        'post_content' => strip_tags($image['caption']['text']),
        'post_title' => 'Instagram by ' . $user . ' on ' . date('d.m.Y H:i', $image['created_time']),
        'post_status' => 'publish',
        'post_type' => 'miz',
        'post_author' => $userID,
        'post_date' => date('Y-m-d H:i:s', $image['created_time'])
      );

      $iID = $image['id'];
      $imageURL = $image['images']['standard_resolution']['url'];
      $type = "Instagram";
      $link = $image['link'];
      if(isset($image['location']['latitude']) && $image['location']['latitude'] !== 0)
        $location = $image['location']['latitude'] . "," . $image['location']['longitude'];
      else $location = '';

      $pID = wp_insert_post($post);
      update_post_meta($pID, 'uID', $iID, true);
      update_post_meta($pID, 'type', $type, true);
      update_post_meta($pID, 'user', $user, true);
      update_post_meta($pID, 'link', $link, true);
      update_post_meta($pID, 'location', $location, true);
      media_sideload_image($imageURL, $pID);

      $attachments = get_posts( array(
        'post_type' => 'attachment',
        'number_posts' => 1,
        'post_status' => null,
        'post_parent' => $pID,
        'orderby' => 'post_date',
        'order' => 'DESC',
      ) );
      set_post_thumbnail($pID, $attachments[0]->ID);
    }

  } catch(Exception $e) {
    echo $e->getMessage();
  }

}

function miz_instagram_getUID($user) {
  $uID = false;

  $mizsettings = get_option('miz-menu-optionen');

  $query = array(
    'access_token' => $mizsettings['instagram']['accesstoken'],
    'q' => $user
  );
  $url = 'https://api.instagram.com/v1/users/search?' . http_build_query($query);

  try {
    $curl_connection = curl_init($url);
    curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);

    //Data are stored in $data
    $data = json_decode(curl_exec($curl_connection), true);
    curl_close($curl_connection);

    if(count($data['data']) == 0 ) return;

    foreach($data['data'] as $username) {
      if($username['username'] == $user) {
        $uID = $username['id'];
      }
    }

  } catch(Exception $e) {
    echo $e->getMessage();
  }

  return $uID;
}