<?php

function rtp_timeline() {
  $media = new WP_Query(array(
    'post_type' => array('miz','post'),
    'post_status' => 'publish',
    'posts_per_page' => -1
  ));

  $twitterusers = get_users(array(
    'meta_key' => 'twitter',
    'meta_value' => "",
    'meta_compare' => '!='));
  foreach($twitterusers as $twitteruser) {
    $twitterusernames[] = get_user_meta($twitteruser->ID, 'twitter', true);
  }
  $twitterusernames = array_filter($twitterusernames);

  if($media->have_posts()) :

    $output = '<ul class="grid-miz">';

    while($media->have_posts()) :

      $isRT = false;
      $class = '';
      $thumbnail = '';
        // Check for Retweets, we don't want to see them
      foreach($twitterusernames as $twitterusername) {
        if(strpos(get_the_content(), "RT @" . $twitterusername) !== FALSE) {
          $isRT = true;
        }
      }
      if($isRT) continue;

      if(has_post_thumbnail()) :
          $class = ' has-media';
          $thumbImg = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'medium' );
          $thumbURL = $thumbImg['0'];
          $thumbnail = '<div class="grid-miz-figure" style="background-image: url(' . $thumbURL . ')"><a href="' . get_post_meta(get_the_ID(), 'link', true) . '"></a></div>';
      endif;

      $output .= '<li' . $class . '>';
      $output .= '<div class="grid-miz-wrapper">';
      $output .= $thumbnail;

      $output .= '<div class="grid-miz-content">';

      if(get_post_type() == 'post' ) :
        $output .= '<a href="' . get_permalink(get_the_ID()) . '">' . showImgs(get_the_content()) . '<br>' . substr(get_the_title(), 0, 70);
      else:
        $output .= get_the_content();
      endif;

      $output .= '<div class="grid-miz-meta">' . get_the_author();
      if(get_post_type() == 'miz') :
        $output .= 'via ' . get_post_meta(get_the_ID(), 'type', true) . '(<a href="' . get_post_meta(get_the_ID(), 'link', true) . '" target="_blank">Link</a>)';
      endif;

      $output = '</li>';

    endwhile;
    $output .= '</ul>';

    echo $output;

  endif;
}

function keepNrImgs($nrimg, $str) {
  // gets an array with al <img> tags from $str
  if(preg_match_all('/(\<img[^\>]+\>)/i', $str, $mt)) {
    // gets array with the <img>s that must be stripped ($nrimg+), and removes them
    $remove_img = array_slice($mt[1], $nrimg);
    $str = str_ireplace($remove_img, '', $str);
  }
  return $str;
}

function showImgs($str) {
  // gets an array with al <img> tags from $str
  if(preg_match_all('/(\<img[^\>]+\>)/i', $str, $mt)) {
    return $mt[1][0];
  }
  return false;
}

?>
