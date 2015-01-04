<?php

function rtp_map() {
  $counter = 0;
  $first = true;

  $media = new WP_Query(array(
    'post_type' => 'miz',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
      array(
        'key' => 'location',
        'value' => '',
        'compare' => '!='
      )
    )
  ));

  if($media->have_posts()) :

    $output = '<div id="map"><div id="map-canvas"></div><div id="map-media" style="display: none;"><div id="map-media-thumbnail"></div><div id="map-media-content"></div>
<div id="map-media-meta"><span class="author"></span> via <span class="service"></span> (<span class="link"></span>)</div></div></div>';

    $output .= '<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBFheFeAX46nENc1-Ru8c3iDpYLqY9D8BE"></script>';

    $output .= '<script type="text/javascript">
    function initialize() {
        var mapOptions = {
          center: new google.maps.LatLng(48.901178184,2.343941506),
          zoom: 5
        };
        var map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
        var media = {}';

    while($media->have_posts()) : $media->the_post();
      $content = htmlentities(strip_tags(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', get_the_content())), ENT_QUOTES);
      if(has_post_thumbnail()) : $thumbnail = get_the_post_thumbnail(the_ID(), 'full'); else : unset($thumbnail); endif;
      if(get_post_meta(get_the_ID(),'location',true) == ',') { continue; }

      $output .= 'media["' . get_the_ID() . '"] = {}';
      $output .= 'media["' . get_the_ID() . '"]["content"] = "' . $content . '";';
      $output .= 'media["' . get_the_ID() . '"]["author"] = "' . get_the_author() . '";';
      $output .= 'media["' . get_the_ID() . '"]["service"] = "' . get_post_meta(get_the_ID(), 'type', true) . '";';
      $output .= 'media["' . get_the_ID() . '"]["link"] = "' . get_post_meta(get_the_ID(), 'link', true) . '";';
      $output .= 'media["' . get_the_ID() . '"]["thumbnail"] = "' . $thumbnail . '";';

      $output .= 'var marker' . get_the_ID() . ' = new google.maps.Marker({
        position: new google.maps.LatLng(<?php echo get_post_meta(get_the_ID(), "location", true); ?>),
        title: "' . get_the_title() . '",
        icon: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
      });';
      $output .= 'marker' . get_the_ID() . '.setMap(map);';

      $output .= "google.maps.event.addListener(marker" . get_the_ID() .", 'click', function() {
      jQuery('#map-media-thumbnail').html(media['" . get_the_ID() ."']['thumbnail']);
      jQuery('#map-media-content').html(media['" . get_the_ID() ."']['content']);
      jQuery('#map-media-meta .author').html(media['" . get_the_ID() ."']['author']);
      jQuery('#map-media-meta .service').html(media['" . get_the_ID() ."']['service']);
      jQuery('#map-media-meta .link').html('<a href=\"' + media[" . get_the_ID() . "']['link'] + '\" target=\"_blank\">Link</a>');
      jQuery('#map-media').show();
      marker" . get_the_ID() .".setIcon('http://maps.google.com/mapfiles/ms/icons/green-dot.png');
      });";

      if($first) :
        $first = false;
        $output .= '    new google.maps.event.trigger( marker'. get_the_ID() . ', "click");';
      endif;

    endwhile;

    $output .= '}
    google.maps.event.addDomListener(window, "load", initialize);</script>';

    echo $output;
  endif;
}

?>