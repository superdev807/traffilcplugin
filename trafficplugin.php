<?php
/*
Plugin Name:  WP Traffic Plugin
Plugin URI: http://wp-traffic-plugin.com
Description: Traffic Plugin
Version: 1.0.0
Author: Dan Green
*/

error_reporting(E_ALL^E_NOTICE^E_DEPRECATED);
// error_reporting(0);

session_start();

require_once('trf_install.php');
require_once('trf_settings.php');
require_once('trf_log.php');
require_once('trf_promote.php');
require_once('TwitterAPIExchange.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');

require_once __DIR__ . '/oauth2/Client.php';
require_once __DIR__ . "/oauth2/GrantType/IGrantType.php";
require_once __DIR__ . "/oauth2/GrantType/AuthorizationCode.php";
require_once __DIR__ . "/oauth2/GrantType/RefreshToken.php";

require_once __DIR__ . '/facebook/src/Facebook/autoload.php';
require 'plugin_update_check.php';

$image = plugin_dir_url(__FILE__ ) . 'logo.png';
$favicon = plugin_dir_url(__FILE__ ) . 'favicon.png';

define("TRAFFIC_PLUGIN_LOGO", "<img src ='".$image."' />");
define('TRAFFIC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, 'trf_install' );

$MyUpdateChecker = new PluginUpdateChecker_2_0 (
    'https://kernl.us/api/v1/updates/5976eea11e97c267fc17f150/',
    __FILE__,
    'trf',
    1
);

add_action( 'wp_dashboard_setup', 'register_trf_dashboard_widget' );
function register_trf_dashboard_widget() {
    wp_add_dashboard_widget(
        'trf_dashboard_widget',
        'Recommended Software For You',
        'trf_promotion_widget_display'
    );

    global $wp_meta_boxes;
    $normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
    $trf_dashboard_widget = array(
        'trf_dashboard_widget' => $normal_dashboard['trf_dashboard_widget']
    );
    unset( $normal_dashboard['trf_dashboard_widget'] );
    $sorted_dashboard = array_merge( $trf_dashboard_widget, $normal_dashboard );
    $wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
}

function trf_promotion_widget_display() {
    echo file_get_contents('http://fmarketer.me/app-promotions.php');
}

add_action('admin_menu', 'trf_menu');
function trf_menu() {
    $hook = add_menu_page( 'Traffic Plugin', __( 'Traffic Plugin', 'trf' ), 'manage_options', 'trf', 'trf_admin', TRAFFIC_PLUGIN_URL . 'favicon.png' );
    $settings = add_submenu_page( 'trf', 'Settings', 'Settings', 'manage_options', 'trf', 'trf_settings' );
    $promote = add_submenu_page( 'trf', 'Promote', 'Promote', 'manage_options', 'trf_promote', 'trf_promote' );
    $promote = add_submenu_page( 'trf', 'Log', 'Log', 'manage_options', 'trf_log', 'trf_log' );
    $support = add_submenu_page( 'trf', 'Support', 'Support', 'manage_options', 'trf_support', 'trf_support' );
}

function trf_admin() {

}

function trf_support() {
    echo '
    <div class = "wrap">
        <div class = "fbvahead">' . TRAFFIC_PLUGIN_LOGO . '</div>
        <h1> Support</h1>
        <hr />
        <iframe src="http://wpfanmarketer2.com/support/fm2support.html" width = "100%" height = "3500px" scrolling = "no"></iframe>
    ';
}

add_action('admin_enqueue_scripts',  'trf_admin_scripts');
function trf_admin_scripts() {
    wp_enqueue_style('trf-admin-css', plugins_url( 'admin-style.css', __FILE__ ), false );
    wp_enqueue_style('trf-toastr-css', plugins_url( 'toastr/toastr.min.css', __FILE__ ), false );
    wp_enqueue_style('togglescss', plugins_url( 'tinytools/tinytools.toggleswitch.min.css', __FILE__ ), false );
    wp_enqueue_script('jquery');
    wp_enqueue_script('toggles',  plugins_url( 'tinytools/tinytools.toggleswitch.min.js', __FILE__ ) , false);
    wp_enqueue_script('trf-toastr-js',  plugins_url( 'toastr/toastr.min.js', __FILE__ ) , false);
}

function trf_humanTiming ($time) {
    $time = time() - $time; // to get the time since that moment
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }
}

function wptrfFacebookQuery($query, $params) {
    $post_url = 'https://graph.facebook.com/'.$query;
    $accesstoken = get_post_meta(111111113, 'trfFbAccessToken', TRUE);

    $post_url = $post_url . '?access_token=' . $accesstoken . $params;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $post_url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($result, TRUE);

    return $result;
}

function wptrfFacebookPost($query, $params, $pagetoken = false) {
    $post_url = 'https://graph.facebook.com/'.$query;

    if ($pagetoken) {
      $accesstoken = $pagetoken;
    } else {
      $accesstoken = get_post_meta(111111113, 'trfFbPageAccessToken', TRUE);
    }

    $params['access_token'] = $accesstoken;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $post_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $return = curl_exec($ch);
    curl_close($ch);

    $return = json_decode($return);

    return $return;
}

function wptrfMySubreddits() {
  $clientId = get_post_meta(111111113, 'trfRdID', TRUE);
  $clientSecret = get_post_meta(111111113, 'trfRdSecret', TRUE);
  $refreshtoken = get_post_meta(111111113, 'trfRdRefreshToken', TRUE);

  $userAgent = 'TrafficPlugin/0.1 by DanGreen';
  $client = new OAuth2\Client($clientId, $clientSecret, OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
  $client->setCurlOption(CURLOPT_USERAGENT, $userAgent);

  $response = $client->getAccessToken('https://ssl.reddit.com/api/v1/access_token', "refresh_token", array("refresh_token" => $refreshtoken));
  update_post_meta('111111113', 'trfRdAccessToken', $response["result"]["access_token"]);

  $accesstoken = $response["result"]["access_token"];

  $client->setAccessToken($accesstoken);
  $client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);

  $response = $client->fetch("https://oauth.reddit.com/subreddits/mine/subscriber");

  return $response["result"]["data"]["children"];
}

function createRedditPost($post_id) {
  $title = get_post_meta($post_id, "trf_reddit_title", true);
  if ( empty($title) ) $title  = get_the_title($post_id);
  $link   = get_permalink($post_id);

  $clientId = get_post_meta(111111113, 'trfRdID', TRUE);
  $clientSecret = get_post_meta(111111113, 'trfRdSecret', TRUE);
  $refreshtoken = get_post_meta(111111113, 'trfRdRefreshToken', TRUE);

  $userAgent = 'TrafficPlugin/0.1 by DanGreen';
  $client = new OAuth2\Client($clientId, $clientSecret, OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
  $client->setCurlOption(CURLOPT_USERAGENT, $userAgent);

  $response = $client->getAccessToken('https://ssl.reddit.com/api/v1/access_token', "refresh_token", array("refresh_token" => $refreshtoken));
  update_post_meta('111111113', 'trfRdAccessToken', $response["result"]["access_token"]);

  $accesstoken = $response["result"]["access_token"];

  $client->setAccessToken($accesstoken);
  $client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
  $subreddit = get_post_meta(111111113, 'trfRdPage', true);

  $data = array(
    'api_type'  => 'json',
    'kind'      => 'link',
    'url'       => $link,
    'title'     => $title,
    'text'      => $title,
    'sr'        => $subreddit
  );

  $response = $client->fetch("https://oauth.reddit.com/api/submit", $data, OAuth2\Client::HTTP_METHOD_POST);

  if (count($response['result']['json']['errors']) > 0) {
    return false;
  }
  else {
    trfInsertHistory('reddit', json_encode($response), $link, $title, $post_id);
    return true;
  }
}

add_action( 'load-post.php', 'trf_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'trf_post_meta_boxes_setup' );

function trf_post_meta_boxes_setup() {
  add_action( 'add_meta_boxes', 'trf_add_post_meta_boxes' );
  add_action( 'save_post', 'trf_save_traffic_meta', 10, 2 );
}

function trf_add_post_meta_boxes() {
  add_meta_box(
    'trf-keyword',      // Unique ID
    esc_html__( 'Get Traffic', 'example' ),    // Title
    'trf_traffic_meta_box',   // Callback function
    [ 'post', 'page' ],         // Admin page (or post type)
    'side',         // Context
    'default'         // Priority
  );
}

function trf_traffic_meta_box( $post ) { ?>
  <?php wp_nonce_field( basename( __FILE__ ), 'trf_traffic_nonce' ); ?>

  <p>
    <label for="trf-keyword"><?php _e( "Add 3 keywords.", 'trf' ); ?></label>
    <br /><br />

    Keyword1: <input class="widefat" type="text" name="trf-keyword1" id="trf-keyword1" value="<?php echo esc_attr( get_post_meta( $post->ID, 'trf_keyword1', true ) ); ?>" size="30" />
    Keyword2: <input class="widefat" type="text" name="trf-keyword2" id="trf-keyword2" value="<?php echo esc_attr( get_post_meta( $post->ID, 'trf_keyword2', true ) ); ?>" size="30" />
    Keyword3: <input class="widefat" type="text" name="trf-keyword3" id="trf-keyword3" value="<?php echo esc_attr( get_post_meta( $post->ID, 'trf_keyword3', true ) ); ?>" size="30" />

    <br /><br />
    <label>Get Traffic<br/>
      <input class="widefat" type="checkbox" name="trf-get-traffic" id="trf-get-traffic" value="1" <?php echo esc_attr( get_post_meta( $post->ID, 'trf_get_traffic', true ) ) === '1' ? 'checked' : ''; ?> /></label>
  </p>

  <script type = 'text/javascript'>
  jQuery(document).ready(function () {
    jQuery('#trf-get-traffic').toggleSwitch({ height: '30px' });
  });
  </script>
<?php }

function trf_save_traffic_meta( $post_id, $post ) {
  if ( !isset( $_POST['trf_traffic_nonce'] ) || !wp_verify_nonce( $_POST['trf_traffic_nonce'], basename( __FILE__ ) ) )
    return $post_id;

  $post_type = get_post_type_object( $post->post_type );

  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
    return $post_id;

  save_or_update_meta($post_id, 'trf-keyword1', 'trf_keyword1');
  save_or_update_meta($post_id, 'trf-keyword2', 'trf_keyword2');
  save_or_update_meta($post_id, 'trf-keyword3', 'trf_keyword3');
  save_or_update_meta($post_id, 'trf-get-traffic', 'trf_get_traffic');
}

function save_or_update_meta($post_id, $post_key, $meta_key) {
  $new_meta_value = ( isset( $_POST[$post_key] ) ? sanitize_html_class( $_POST[$post_key] ) : '' );
  $meta_value = get_post_meta( $post_id, $meta_key, true );

  if ( $new_meta_value && '' == $meta_value )
    add_post_meta( $post_id, $meta_key, $new_meta_value, true );
  elseif ( $new_meta_value && $new_meta_value != $meta_value )
    update_post_meta( $post_id, $meta_key, $new_meta_value );
  elseif ( '' == $new_meta_value && $meta_value )
    delete_post_meta( $post_id, $meta_key, $meta_value );
}

function createFacebookPost($post_id) {
  $title = get_post_meta($post_id, "trf_facebook_title", true);
  if ( empty($title) ) $title  = get_the_title($post_id);

  $link   = get_permalink($post_id);

  $fbpageid             = get_post_meta(111111113, 'trfFbPage', true);
  $data['link']         = $link;
  $data['message']      = html_entity_decode($title);

  $return = wptrfFacebookPost($fbpageid . '/feed', $data);
  trfInsertHistory('facebook', json_encode($return), $link, $title, $post_id);
}

function createTweet($post_id, $keyword1, $keyword2, $keyword3) {
  $title = get_post_meta($post_id, "trf_twitter_title", true);
  if ( empty($title) ) $title  = get_the_title($post_id);

  $link   = get_permalink($post_id);
  $tweet  = html_entity_decode($title);
  $tweet  = $tweet . " " . implode(array('#'.$keyword1, '#'.$keyword2, '#'.$keyword3), ' ');

  $id           = get_post_meta(111111113, 'trfTwKey', true);
  $secret       = get_post_meta(111111113, 'trfTwSecret', true);
  $token        = get_post_meta(111111113, 'trfTwToken', true);
  $tokensecret  = get_post_meta(111111113, 'trfTwTokenSecret', true);

  $settings = array(
    'oauth_access_token'        => $token,
    'oauth_access_token_secret' => $tokensecret,
    'consumer_key'              => $id,
    'consumer_secret'           => $secret
  );

  $url = 'https://api.twitter.com/1.1/statuses/update.json';
  $requestMethod = 'POST';

  if (strlen($tweet) >113){
    $tweet = substr($tweet, 0, 113);
    $tweet = $tweet . "...";
  }

  $postfields = array (
    'status' => $tweet . " " . $link,
    'media_ids' => ''
  );

  $twitter = new TwitterAPIExchange($settings);
  $response = $twitter->buildOauth($url, $requestMethod)
                      ->setPostfields($postfields)
                      ->performRequest();

  if ($response['errors'][0]['code'] == 187) { // if duplicated link
    return false;
  } else {
    trfInsertHistory('twitter', $response, $link, $title, $post_id);
    return true;
  }
}

function trfInsertHistory($source, $response_text, $link, $title, $post_id) {
  global $wpdb;
  $wpdb->show_errors();
  $table_name = $wpdb->prefix ."trfhistory";

  $result = $wpdb->insert( $table_name,  array(
    'source'      => $source,
    'raw_result'  => $response_text,
    'url'         => $link,
    'title'       => $title,
    'post_id'     => $post_id,
    'created'     => time()
  ), array('%s', '%s', '%s', '%s', '%d', '%d') );
}

function trfProcesskeyword($keyword) {
  global $wpdb;

  $tbl_kws_fb = $wpdb->prefix . "trfpages";

  // $row = $wpdb->get_row("SELECT count(id) as ct FROM $tbl_kws_fb WHERE keyword='$keyword1'");
  // if ($row->ct == 0) return;

  $per_page = 5000;
  $keyword = urlencode($keyword);
  $limit = $per_page;
  $offset = 0;
  $searchtype_3 = 'page';
  $query = "search";

  $params = '&q='.$keyword.'&type='.$searchtype_3.'&fields=name,id,updated_time,link,fan_count,talking_about_count&limit='.$limit.'&offset='.$offset;

  $raw = wptrfFacebookQuery($query, $params);

  $result_data = $raw["data"];
  $result_data = array_unique($result_data, SORT_REGULAR);

  foreach($result_data as $dt_3) {
    $nodeid = $dt_3['id'];
    $row = $wpdb->get_row("SELECT * FROM $tbl_kws_fb WHERE node_id = $nodeid");

    // if (intval($dt_3['talking_about_count']) < 100) continue;

    if (empty($row)) {
      $wpdb->insert(
        $tbl_kws_fb,
        array(
          'keyword'       => $keyword,
          'node_id'       => $nodeid,
          'title'         => $dt_3["name"],
          'talking_about' => $dt_3['talking_about_count'],
          'likes'         => $dt_3['fan_count']
        ),
        array(
          '%s',
          '%s',
          '%s',
          '%d',
          '%d'
        )
      );
    }
    else {
      $talkingabout = $dt_3['talking_about_count'];
      $likes = $dt_3['fan_count'];
      $wpdb->update(
        $tbl_kws_fb,
        array(
          'talking_about' => $talkingabout,
          'likes' => $likes
        ),
        array('node_id' => $nodeid)
      );
    }
  }
}

function trfSearchTweets($keyword) {
  $id           = get_post_meta(111111113, 'trfTwKey', true);
  $secret       = get_post_meta(111111113, 'trfTwSecret', true);
  $token        = get_post_meta(111111113, 'trfTwToken', true);
  $tokensecret  = get_post_meta(111111113, 'trfTwTokenSecret', true);

  $settings = array(
    'oauth_access_token'        => $token,
    'oauth_access_token_secret' => $tokensecret,
    'consumer_key'              => $id,
    'consumer_secret'           => $secret
  );

  $url = 'https://api.twitter.com/1.1/search/tweets.json';

  $getfield = "?q=#{$keyword}&result_type=recent&count=200";

  $requestMethod = 'GET';

  $twitter = new TwitterAPIExchange($settings);
  $response = $twitter->setGetfield($getfield)
                      ->buildOauth($url, $requestMethod)
                      ->performRequest();

  $response = json_decode($response, TRUE);

  foreach($response["statuses"] as $tw) {
    global $wpdb;
    $tbl_kws_fb = $wpdb->prefix . "trftweets";
    $nodeid = $tw['id_str'];

    $row = $wpdb->get_row("SELECT * FROM $tbl_kws_fb WHERE node_id = $nodeid");

    if (empty($row)) {
      $wpdb->insert(
        $tbl_kws_fb,
        array(
          'keyword'       => $keyword,
          'node_id'       => $nodeid,
          'tweet'         => $tw["text"],
        ),
        array(
          '%s',
          '%s',
          '%s'
        )
      );
    }
  }

  return $response["search_metadata"];
}

// Metas and Previewer
function trf_facebook_preview() {
  include( "trf_facebook_preview.php" );
}
function trf_twitter_preview() {
  include( "trf_twitter_preview.php" );
}
function trf_reddit_preview() {
  include( "trf_reddit_preview.php" );
}

function trf_preview( $post_type ) {
  if ( in_array( $post_type, array( 'post', 'page', 'product' ) ) ) {
    global $post;

    if (($post->post_type == 'post' ||
      $post->post_type == 'page' ||
      $post->post_type == 'product') && $post->post_status == 'publish') {
      add_meta_box(
        'reddit_preview_meta',
        'TrafficPlugin - Reddit Preview',
        'trf_reddit_preview',
        $post_type,
        'advanced', // change to something other then normal, advanced or side
        'high'
      );
      add_meta_box(
        'twitter_preview_meta',
        'TrafficPlugin - Twitter Preview',
        'trf_twitter_preview',
        $post_type,
        'advanced', // change to something other then normal, advanced or side
        'high'
      );
      add_meta_box(
        'facebook_preview_meta',
        'TrafficPlugin - Facebook Preview',
        'trf_facebook_preview',
        $post_type,
        'advanced', // change to something other then normal, advanced or side
        'high'
      );
    }
  }
}

function trf_add_preview() {
  global $post, $wp_meta_boxes;
  do_meta_boxes( get_current_screen(), 'facebook-preview', $post );
  do_meta_boxes( get_current_screen(), 'twitter-preview', $post );
  do_meta_boxes( get_current_screen(), 'reddit-preview', $post );
  unset($wp_meta_boxes['post']['facebook-preview']);
  unset($wp_meta_boxes['post']['twitter-preview']);
  unset($wp_meta_boxes['post']['reddit-preview']);
}

add_action('add_meta_boxes', 'trf_preview');
add_action('edit_form_after_title', 'trf_add_preview');
add_action('wp_head', 'trf_head', 1);
add_action('save_post','trf_save_facebook_custom');
add_action('save_post','trf_save_twitter_custom');
add_action('save_post','trf_save_reddit_custom');

function trf_save_facebook_custom($post_id) {
  if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
  if( !current_user_can( 'edit_post' ) ) return;

  $fbtitle = trim($_POST['fbtitle']);
  $fbimage = trim($_POST['fbimage']);
  $fbdescription = $_POST['fbdescription'];
  $fbmessage = $_POST['fbmessage'];

  update_post_meta($post_id, 'trf_facebook_title', $fbtitle );
  update_post_meta($post_id, 'trf_facebook_image', $fbimage );
  update_post_meta($post_id, 'trf_facebook_description', $fbdescription);
  update_post_meta($post_id, 'trf_facebook_message', $fbmessage);
}

function trf_save_twitter_custom($post_id) {
  if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
  if( !current_user_can( 'edit_post' ) ) return;

  $twtitle = trim($_POST['twtitle']);
  $twdescription = trim($_POST['twdescription']);
  $twimage = trim($_POST['twimage']);

  update_post_meta($post_id, 'trf_twitter_title', $twtitle);
  update_post_meta($post_id, 'trf_twitter_image', $twimage);
  update_post_meta($post_id, 'trf_twitter_description', $twdescription);
}

function trf_save_reddit_custom($post_id) {
  if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
  if( !current_user_can( 'edit_post' ) ) return;

  $rdtitle = trim($_POST['rdtitle']);
  $rdimage = trim($_POST['rdimage']);

  update_post_meta($post_id, 'trf_reddit_title', $rdtitle);
  update_post_meta($post_id, 'trf_reddit_image', $rdimage);
}

function trf_head() {
  global $post;
  $postId = $post->ID;

  $postTitle = get_post_meta($postId, "trf_facebook_title", true);
  if ( empty($postTitle) ) $postTitle = $post->post_title;
  $postDescription =  get_post_meta($postId, "trf_facebook_description", true);
  if ( empty($postDescription) ) {}

  $postImage = get_post_meta($postId, "trf_facebook_image", true);
  if ( empty($postImage) ) {
    $feat_image = wp_get_attachment_url( get_post_thumbnail_id($postId) );
    if ( empty($feat_image) ) {
      $match = array();
      preg_match( "/<img.+src=[\'\"](?P<src>.+?)[\'\"].*>/i", $post->post_content, $match );
      if ( sizeof($match) > 0 ) {
        $postImage = $match["src"];
      }
    }
    else {
      $postImage = $feat_image;
    }
  }

  echo '<meta property="og:title" content="'.$postTitle.'">' . "\n";
  echo '<meta property="og:description" content="'.$postDescription.'">' . "\n";
  echo '<meta property="og:url" content="'.get_the_permalink($postId).'">' . "\n";
  echo '<meta property="og:image" content="'.$postImage.'">' . "\n";

  $postTitle = get_post_meta($postId, "trf_twitter_title", true);
  if ( empty($postTitle) ) $postTitle = $post->post_title;
  $postDescription =  get_post_meta($postId, "trf_twitter_description", true);
  if ( empty($postDescription) ) {
    $postDescription = substr(strip_tags($post->post_content), 0, 100) . "...";
  }

  $postImage = get_post_meta($postId, "trf_twitter_image", true);
  if ( empty($postImage) ) {
    $feat_image = wp_get_attachment_url( get_post_thumbnail_id($postId) );
    if ( empty($feat_image) ) {
      $match = array();
      preg_match( "/<img.+src=[\'\"](?P<src>.+?)[\'\"].*>/i", $post->post_content, $match );
      if ( sizeof($match) > 0 ) {
        $postImage = $match["src"];
      }
    }
    else {
      $postImage = $feat_image;
    }
  }

  echo '<meta property="twitter:card" content="summary">' . "\n";
  echo '<meta property="twitter:title" content="' . $postTitle . '">' . "\n";
  echo '<meta property="twitter:description" content="' . $postDescription . '">' . "\n";
  echo '<meta property="twitter:image" content="' . $postImage . '">' . "\n";


  $postTitle = get_post_meta($postId, "trf_reddit_title", true);
  if ( empty($postTitle) ) $postTitle = $post->post_title;
  $postImage = get_post_meta($postId, "trf_reddit_image", true);
  if ( empty($postImage) ) {
    $feat_image = wp_get_attachment_url( get_post_thumbnail_id($postId) );
    if ( empty($feat_image) ) {
      $match = array();
      preg_match( "/<img.+src=[\'\"](?P<src>.+?)[\'\"].*>/i", $post->post_content, $match );
      if ( sizeof($match) > 0 ) {
        $postImage = $match["src"];
      }
    }
    else {
      $postImage = $feat_image;
    }
  }
  echo '<meta property="image" content="' . $postImage . '">' . "\n";
  echo '<meta property="title" content="' . $postTitle . '">' . "\n";
}

function trfGetPageAccessToken($pageid) {
    $response = wptrfFacebookQuery('me/accounts', '');

    foreach ($response ['data'] as $page){
        $pagename = $page['name'];
        $id = $page['id'];
        $id = trim($id);

        $token = $page['access_token'];

        if ($id == $pageid){
            $accesstoken = $token;
        }
    }

    return $accesstoken;
}