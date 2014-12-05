<?php


function miz_admin_menu() {
  add_menu_page('Roadtrip Plugin','Roadtrip', 'manage_options', 'miz-menu', 'miz_menu');
  add_submenu_page('miz-menu', 'Map', 'Map', 'manage_options', 'miz-menu-map', 'miz_menu_map');
  add_submenu_page('miz-menu', 'Registered users', 'Users', 'manage_options', 'miz-menu-users', 'miz_menu_users');
  add_submenu_page('miz-menu', 'Options', 'Options', 'manage_options', 'miz-menu-optionen', 'miz_menu_optionen');
}

function miz_menu_user_initializeSettings() {
  add_settings_section('miz_users_section', '', 'miz_users_section_callback', 'miz-menu-users');
  add_settings_field('miz-users-field', '', 'miz_users_field_callback', 'miz-menu-users', 'miz_users_section');
  register_setting('miz-menu-users', 'miz-menu-users');

  add_settings_section('miz_options_section', '', 'miz_users_section_callback', 'miz-menu-optionen');
  add_Settings_field('miz-options-field', '', 'miz_options_field_callback', 'miz-menu-optionsn', 'miz_options_section');
  register_setting('miz-menu-optionen', 'miz-menu-optionen');
}

function miz_users_section_callback() {}
function miz_users_field_callback() {}


function miz_menu_map() {
  $media = new WP_Query(array(
    'post_type' => 'miz',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_key' => 'location',
    'meta_value' => "",
    'meta_compare' => '!='));
  ?>

  <div class="wrap">
    <h2>Map</h2>
    <div id="map-canvas"></div>
  </div>

  <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBFheFeAX46nENc1-Ru8c3iDpYLqY9D8BE"></script>
  <script type="text/javascript">
    function initialize() {
      var mapOptions = {
        center: new google.maps.LatLng(48.901178184,2.343941506),
        zoom: 5
      };
      var map = new google.maps.Map(document.getElementById("map-canvas"),
        mapOptions);

      <?php if($media->have_posts()) : while($media->have_posts()) : $media->the_post();
      $content = htmlentities(strip_tags(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', get_the_content())), ENT_QUOTES);
       if(has_post_thumbnail()) : $content = get_the_post_thumbnail(the_ID(), 'thumbnail') . $content; endif;
       ?>

      var contentString = '<div class="marker-window"><?php echo $content; ?></div>';
      var infoWindow<?php the_ID(); ?> = new google.maps.InfoWindow({
        content: contentString,
        maxWidth: 500
      });

      var marker<?php the_ID(); ?> = new google.maps.Marker({
        position: new google.maps.LatLng(<?php echo get_post_meta(get_the_ID(), 'location', true); ?>),
        title: '<?php the_title(); ?>'
      });
      marker<?php the_ID(); ?>.setMap(map);
      google.maps.event.addListener(marker<?php the_ID(); ?>, 'click', function() {
         infoWindow<?php the_ID(); ?>.open(map, marker<?php the_ID(); ?>);
      });
      <?php endwhile; endif; ?>
    }
    google.maps.event.addDomListener(window, 'load', initialize);
  </script>
<?php }

function miz_menu() {
  $media = new WP_Query('post_type=miz&post_status=publish&posts_per_page=-1');
  $users = get_users();
  if($media->have_posts()) : while($media->have_posts()) : $media->the_post();
    $date = get_the_date('dmy');
    $dates[$date] = get_the_date('d.m.Y');
  endwhile; endif;
  ?>
  <div class="wrap">
    <h2>Roadtrip Plugin Content</h2>
    <div class="miz-filter">
      <select data-group="user" name="user">
        <option value="">all users</option>
        <?php foreach($users as $user) : ?>
        <option value=".user-<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>
        <?php endforeach; ?>
      </select>
      <select data-group="type" name="type">
        <option value="">all channels</option>
        <option value=".type-twitter">Twitter</option>
        <option value=".type-instagram">Instagram</option>
      </select>
      <select data-group="date" name="date">
        <option value="">any time</option>
        <?php foreach($dates as $timestamp => $date) : ?>
        <option value=".date-<?php echo $timestamp; ?>"><?php echo $date; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <ul class="miz-grid">
      <?php if($media->have_posts()) : while($media->have_posts()) : $media->the_post();
        $user = get_post_meta(get_the_ID(), 'user', true);
        $type = strtolower(get_post_meta(get_the_ID(), 'type', true));
        ?>
        <li class="user-<?php the_author_meta('ID'); ?> type-<?php echo $type; ?> date-<?php echo get_the_date('dmy'); ?>">
          <?php if(has_post_thumbnail()) the_post_thumbnail('thumbnail'); ?>
          <?php the_excerpt(); ?>
          <div class="meta">
            <?php the_author(); ?> via <?php echo get_post_meta(get_the_ID(), 'type', true); ?> (<a href="<?php echo get_post_meta(get_the_ID(), 'link', true); ?>">Link</a>)
          </div>
        </li>
      <?php endwhile; endif; ?>
    </ul>
  </div>

  <script type="text/javascript">
    var filters = {};

    jQuery('.miz-filter select').change(function() {
      var $this = jQuery(this);
      var filterGroup = $this.attr('data-group');
      filters[ filterGroup ] = jQuery(this).val();
      console.log(filters);


      var filterValue = '';
      for(var prop in filters) {
        filterValue += filters[ prop ];
      }

      jQuery('.miz-grid li').hide();
      jQuery('.miz-grid li' + filterValue).show();
    });

  </script>
<?php

}

function miz_menu_users() {
  $blogusers = get_users();
  $mizusers = get_option('miz-menu-users');
  ?>

  <div class="wrap">
    <h2>Registered Users</h2>
    <form method="post" action="options.php">
      <?php settings_fields('miz-menu-users'); ?>

      <table class="wp-list-table widefat fixed">
        <thead>
        <tr>
          <th>User ID</th>
          <th>Username</th>
          <th>Twitter</th>
          <th>Instagram</th>
          <th>Last Update</th>
          <!--<th></th>-->
        </tr>
        </thead>
        <tbody id="miz-users">
        <?php foreach($blogusers as $user) {
          $updated_at = $mizusers[$user->ID]['updated_at'];
          if($updated_at == '') $updated_at = 0; ?>
          <tr>
            <td><?php echo $user->ID; $userID = $user->ID; ?></td>
            <td><?php echo $user->user_nicename; ?></td>

            <?php if(get_user_meta($user->ID, 'twitter', true) != '') : ?>
            <td class="user-sm" <?php if($mizusers[$user->ID]['twitter'] == 'on') echo 'style="background-color: #0F0"'; ?>>
              <input name="miz-menu-users[<?php echo $user->ID; ?>][twitter]" type="checkbox" <?php echo checked('on', $mizusers[$user->ID]['twitter']); ?>> @<?php echo get_user_meta($user->ID, 'twitter', true); ?> </td>
              <?php else: ?>
              <td></td>
            <?php endif; ?>
            <?php if(get_user_meta($user->ID, 'instagram', true) != '') : ?>
            <td class="user-sm" <?php if($mizusers[$user->ID]['instagram'] == 'on') echo 'style="background-color: #0F0"'; ?>>
              <input  name="miz-menu-users[<?php echo $user->ID; ?>][instagram]" type="checkbox" <?php echo checked('on', $mizusers[$user->ID]['instagram']); ?>> <?php echo get_user_meta($user->ID, 'instagram', true); ?></td>
            <?php else: ?>
            <td></td>
            <?php endif; ?>
            <td><?php echo $updated_at !== "0" ? date('d.m.y H:i', $updated_at) : "none so far"; ?> <input type="hidden" name="miz-menu-users[<?php echo $user->ID; ?>][updated_at]" value="<?php echo $updated_at; ?>">
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>

      <?php submit_button(); ?>
    </form>

  </div>

  <script type="text/javascript">
    jQuery('#miz-users input').change(function() {
      if(jQuery(this).is(":checked")) {
        jQuery(this).parent().css('background-color', '#0F0');
      } else {
        jQuery(this).parent().css('background-color', '');
      }
    });
  </script>
<?php

}

function miz_menu_optionen() {
  $mizsettings = get_option('miz-menu-optionen'); ?>
  <div class="wrap">
    <h2>Options</h2>
    <form method="post" action="options.php">
      <?php settings_fields('miz-menu-optionen'); ?>

    <h3 class="title">Instagram App Credentials</h3>
      <table class="form-table">
        <tbody><tr>
          <th scope="row"><label>Update Interval</label></th>
          <td>Every <input name="miz-menu-optionen[updateinterval]" type="number" step="1" min="5" id="posts_per_page" value="<?php echo $mizsettings['updateinterval']; ?>" class="small-text"> minutes
          <p class="description">One user will be processed at each update.</p></td>
        </tr>
        </tbody></table>


    <h3 class="title">Instagram App Credentials</h3>
    <p>You need to register an app for collecting and importing Instagrams at <a href="http://instagram.com/developer/">instagram.com/developer/</a>. After registering, enter the credentials.</p>
    <table class="form-table">
      <tbody><tr>
        <th scope="row"><label>Access Token</label></th>
        <td><input name="miz-menu-optionen[instagram][accesstoken]" type="text" value="<?php echo $mizsettings['instagram']['accesstoken']; ?>" class="large-text code"></td>
      </tr>
      </tbody></table>

      <h3 class="title">Twitter App Credentials</h3>
      <p>You need to register an app for collecting and importing Tweets at <a href="https://dev.twitter.com/">dev.twitter.com/</a>. After registering, enter the credentials.</p>
      <table class="form-table">
        <tbody><tr>
          <th scope="row"><label>Access Token</label></th>
          <td><input name="miz-menu-optionen[twitter][accesstoken]" type="text" value="<?php echo $mizsettings['twitter']['accesstoken']; ?>" class="large-text code"></td>
        </tr><tr>
          <th scope="row"><label>Access Token Secret</label></th>
          <td><input name="miz-menu-optionen[twitter][accesstokensecret]" type="text" value="<?php echo $mizsettings['twitter']['accesstokensecret']; ?>" class="large-text code"></td>
        </tr><tr>
          <th scope="row"><label>Consumer Key</label></th>
          <td><input name="miz-menu-optionen[twitter][consumerkey]" type="text" value="<?php echo $mizsettings['twitter']['consumerkey']; ?>" class="large-text code"></td>
        </tr><tr>
          <th scope="row"><label>Consumer Secret</label></th>
          <td><input name="miz-menu-optionen[twitter][consumersecret]" type="text" value="<?php echo $mizsettings['twitter']['consumersecret']; ?>" class="large-text code"></td>
        </tr>
        </tbody></table>

      <?php submit_button(); ?>
    </form>
  </div>

<?php }

function miz_enqueue_styles() {
  wp_enqueue_style('miz');
}


function miz_init_post() {

  $labels = array(
    'name' => 'MIZ',
    'singular_name' => 'MIZ',
    'menu_name' => 'MIZ',
    'name_admin_bar' => 'MIZ',
    'add_new' => 'Add new',
    'new_item' => 'New item',
    'edit_item' => 'Edit item',
    'view_item' => 'View item',
    'all_items' => 'All items',
    'search_items' => 'Search items',
    'parent_item_colon' => 'Parent MIZ',
    'not_found' => 'Not found',
    'not_found_in_trash' => 'Not found in trash'
  );

  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_menu'       => false,
    'query_var'          => true,
    'rewrite'            => array( 'slug' => 'miz' ),
    'capability_type'    => 'post',
    'has_archive'        => false,
    'hierarchical'       => false,
    'menu_position'      => null,
    'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' )
  );

  register_post_type('miz', $args);


}


function miz_add_meta_boxes() {
  add_meta_box('miz-meta', 'Roadtrip Plugin Meta Information', 'miz_meta', 'miz');

}


function miz_meta($post) {

  $postMeta = get_metadata('post', $post->ID);


  echo '<strong>Unique ID, provided through service:</strong> ' .$postMeta['uID'][0] . '<br>';
  echo '<strong>Type of Social Media content:</strong> ' .$postMeta['type'][0] . '<br>';
  echo '<strong>Author of Social Media content:</strong> ' .$postMeta['user'][0] . '<br>';
  echo '<strong>Location while publishing:</strong> ' .$postMeta['location'][0] . '<br>';

}


function miz_column_head($defaults) {
  $defaults['miz_type'] = "Social Media Type";
  $defaults['miz_location'] = "Latitude, Longitude";
  return $defaults;
}

function miz_column_content($column_name, $post_ID) {
  if($column_name == 'miz_type') {
    $post_type = get_post_meta($post_ID, 'type', true);
    echo $post_type;
  }
  if($column_name == 'miz_location') {
    $post_location = get_post_meta($post_ID, 'location', true);
    echo $post_location;
  }
}

function miz_register_styles() {
  wp_register_style('miz', plugins_url('../assets/admin.css', __FILE__));
}

function miz_excerpt_length( $length ) {
  return 150;
}

function miz_cron_schedules( $schedules ) {
  $schedules['fiveminute'] = array(
    'interval' => 1800,
    'display' => __( 'Every minute' )
  );
  $schedules['minute'] = array(
    'interval' => 60,
    'display' => __( 'Every minute' )
  );
  return $schedules;
}

add_action( 'wp', 'prefix_setup_schedule' );
/**
 * On an early action hook, check if the hook is scheduled - if not, schedule it.
 */
function prefix_setup_schedule() {
  if ( ! wp_next_scheduled( 'miz_oldest_update_event' ) ) {
    wp_schedule_event( time(), 'minute', 'miz_oldest_update_event');
  }
}

function miz_show_user_profile( $user ) { ?>

<h3>Roadtrip Plugin Contact Information</h3>

<table class="form-table">
  <tr>
    <th><label for="twitter">Twitter Username</label></th>

    <td><input type="text" name="twitter" value="<?php echo get_user_meta($user->ID, 'twitter', true); ?>" class="regular-text"/>
      <span class="description">(without leading @)</span></td>
  </tr>
  <tr>
    <th><label for="instagram">Instagram Username</label></th>

    <td><input type="text" name="instagram" value="<?php echo get_user_meta($user->ID, 'instagram', true); ?>" class="regular-text"/>
  </tr>
  <tr>
    <th><label for="instagram">Facebook Login</label></th>

    <td><a href="#">Login</a>
  </tr>
</table>

<?php }

function miz_personal_options_update( $user_id ) {
   if(!current_user_can('edit_user', $user_id )) return false;
 
  update_usermeta( $user_id, 'twitter', $_POST['twitter'] );
  update_usermeta( $user_id, 'instagram', $_POST['instagram'] );
}



add_action( 'show_user_profile', 'miz_show_user_profile' );
add_action( 'edit_user_profile', 'miz_show_user_profile' );
add_action( 'personal_options_update', 'miz_personal_options_update' );
add_action( 'edit_user_profile_update', 'miz_personal_options_update' );
 

add_filter('the_excerpt_length', 'miz_excerpt_length', 999);
add_filter('cron_schedules', 'miz_cron_schedules' );

add_action('add_meta_boxes', 'miz_add_meta_boxes');
add_action('init', 'miz_init_post');
add_action('admin_init', 'miz_register_styles');
add_action('admin_init', 'miz_menu_user_initializeSettings');
add_action('admin_print_styles', 'miz_enqueue_styles' );
add_action('admin_menu', 'miz_admin_menu');
add_action('manage_miz_posts_columns','miz_column_head');
add_action('manage_miz_posts_custom_column','miz_column_content', 10, 2);
add_action('miz_oldest_update_event', 'miz_get_oldest_update');


?>