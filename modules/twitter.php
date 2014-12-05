<?php

require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-content/plugins/miz/classes/TwitterAPIExchange.php');


function miz_twitter_init($user, $userID) {

  if(empty($user)) return;

  $mizsettings = get_option('miz-menu-optionen');

  $settings = array(
    'oauth_access_token' => $mizsettings['twitter']['accesstoken'],
    'oauth_access_token_secret' => $mizsettings['twitter']['accesstokensecret'],
    'consumer_key' => $mizsettings['twitter']['consumerkey'],
    'consumer_secret' => $mizsettings['twitter']['consumersecret']
  );

  $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
  $requestMethod = 'GET';
  $getField = '?screen_name=' . $user . '&exclude_replies=true&count=20';

  $twitter = new TwitterAPIExchange($settings);
  $data = $twitter->setGetfield($getField)
    ->buildOauth($url, $requestMethod)
    ->performRequest();

  foreach(json_decode($data) as $tweet) {
    $existingPost = new WP_Query('post_type=miz&post_status=publish&meta_key=uID&meta_value=' . $tweet->id);
    if($existingPost->post_count > 0 ) break;

    $post = array(
      'post_content' => $tweet->text,
      'post_title' => 'Tweet by ' . $user . ' on ' . $tweet->created_at,
      'post_status' => 'publish',
      'post_type' => 'miz',
      'post_author' => $userID,
      'post_date' => date('Y-m-d H:i:s', strtotime($tweet->created_at))
    );
    $type = 'Twitter';
    $link = 'https://twitter.com/' . $user . '/status/' . $tweet->id;

    if(!is_null($tweet->coordinates)) {
      $location = implode(",", $tweet->coordinates->coordinates);
    } elseif(!is_null($tweet->geo)) {
      $location = implode($tweet->geo->coordinates);
    } elseif(is_object($tweet->place)) {
      $location = $tweet->place->full_name . ', ' . $tweet->place->country;
    }
    $pos = strpos($location,",");
    if($pos == 0) {
      $location = '';
    } else {
      $location = substr($location, $pos + 1) . "," . substr($location, 0, $pos);
    }

    $pID = wp_insert_post($post);
    update_post_meta($pID, 'uID', $tweet->id, true);
    update_post_meta($pID, 'type', $type, true);
    update_post_meta($pID, 'user', $user, true);
    update_post_meta($pID, 'link', $link, true);
    update_post_meta($pID, 'location', $location, true);

    foreach($tweet->entities->media as $media) {
      if($media->type == 'photo') {
        $mID = media_sideload_image($media->media_url, $pID);
      }
    }
    if(isset($mID)) {
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
  }
}