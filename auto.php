<?php
require('../../../wp-blog-header.php');

set_time_limit(200);
function return_300( $seconds ) {
  // change the default feed cache recreation period to 2 hours
  return 300;
}

add_filter( 'wp_feed_cache_transient_lifetime' , 'return_300' );

$post_id = trfSelectPostId();
$fb_pages = trfSelectPagesFromTop150($post_id);

foreach($fb_pages as $fb_page) {
    $fb_recent_post = trfGetRecentPost($fb_page->node_id);
    if ($fb_recent_post) {
        if (trfHasCommentHistory($post_id, $fb_recent_post)) {
            echo "Breaking... $post_id - $fb_recent_post";
            break;
        } else {
            echo "Commenting... $post_id - $fb_recent_post";
            trfDoComment($post_id, $fb_recent_post);
            break;
        }
    }
}

function trfSelectPagesFromTop150($post_id) {
    global $wpdb;
    $wpdb->show_errors();

    $keyword1 = get_post_meta($post_id, 'trf_keyword1', true);
    $keyword2 = get_post_meta($post_id, 'trf_keyword2', true);
    $keyword3 = get_post_meta($post_id, 'trf_keyword3', true);

    $table_name = $wpdb->prefix . "trfpages";
    $query = "(select * from $table_name where keyword='$keyword1' order by talking_about desc limit 50) union (select * from $table_name where keyword='$keyword2' order by talking_about desc limit 50) union (select * from $table_name where keyword='$keyword3' order by talking_about desc limit 50)";

    global $wpdb;
    $wpdb->show_errors();

    $results = $wpdb->get_results($query);
    shuffle($results);

    return $results;
}

function trfGetRecentPost($fb_page) {
    $return = wptrfFacebookQuery($fb_page . '/feed', '&limit=1');
    return $return['data'][0]['id'];
}

function trfDoComment($post_id, $fb_post) {
    $data['message'] = trfGetRandomProcessedComment($post_id);

    $fbpage = get_post_meta(111111113, 'trfFbPage', TRUE);
    $pageAccessToken = get_post_meta(111111113, 'trfFbPageAccessToken', TRUE);

    if (empty($pageAccessToken)) $pageAccessToken = trfGetPageAccessToken($fbpage);

    $return = wptrfFacebookPost($fb_post . '/comments', $data, $pageAccessToken);

    if ($return->error) {
        trfInsertHistory('facebook_comment_error', json_encode($return), $link, $title, $post_id);
    }
    else {
        trfInsertCommentHistory($post_id, $fb_post);
    }
}

function trfGetRandomProcessedComment($post_id) {
    global $wpdb;
    $wpdb->show_errors();

    $table_name = $wpdb->prefix . "trfcomments";
    $query = "SELECT comment FROM $table_name order by RAND() limit 1";
    $res  = $wpdb->get_row($query);

    $spintax = new trfSpintax();
    $comment= $spintax->process($res->comment);
    $comment = str_replace("[LINK]", trfOneOfFourLinks($post_id), $comment);

    return $comment;
}

function trfOneOfFourLinks($post_id) {
    $links = array();
    array_push($links, "http://www.facebook.com/" . get_post_meta(111111113, 'trfFbPage', true));

    global $wpdb;
    $wpdb->show_errors();

    $table_name = $wpdb->prefix . "trfhistory";

    $query = "SELECT * FROM $table_name where post_id = '$post_id' and source='facebook' order by id desc";
    $row  = $wpdb->get_row($query);
    $jsonResponse = json_decode($row->raw_result, true);

    if ($jsonResponse["id"]) {
        array_push($links, "https://www.facebook.com/" . $jsonResponse["id"]);
    }

    $query = "SELECT * FROM $table_name where post_id = '$post_id' and source='twitter' order by id desc";
    $row  = $wpdb->get_row($query);
    $jsonResponse = json_decode($row->raw_result, true);

    if ($jsonResponse["id_str"]) {
        array_push($links, "https://twitter.com/" . $jsonResponse["user"]["id_str"] . "/status/" . $jsonResponse["id_str"]);
    }

    $query = "SELECT * FROM $table_name where post_id = '$post_id' and source='reddit' order by id desc";
    $row  = $wpdb->get_row($query);
    $jsonResponse = json_decode($row->raw_result, true);
    if ($jsonResponse["result"]["json"]["data"]["url"]) {
        array_push($links, $jsonResponse["result"]["json"]["data"]["url"]);
    }

    shuffle($links);
    return $links[mt_rand(0, count($links) - 1)];
}

function trfHasCommentHistory($post_id, $fb_post) {
    global $wpdb;
    $wpdb->show_errors();

    $table_name = $wpdb->prefix . "trfcommenthistory";
    $date = date('Y-m-d');
    $query = "SELECT * FROM $table_name WHERE post_id='$post_id' and history_date='$date' and fb_post='$fb_post'";
    $result  = $wpdb->get_row($query);

    return $result != null;
}

function trfInsertCommentHistory($post_id, $fb_post) {
    global $wpdb;
    $wpdb->show_errors();
    $table_name = $wpdb->prefix . "trfcommenthistory";

    $result = $wpdb->insert($table_name,  array(
        'post_id' => $post_id,
        'history_date' => date('Y-m-d'),
        'fb_post' => $fb_post,
        'timesent'=> time()
    ));
}

function trfSelectPostId() {
    $today = date('Y-m-d');
    $days_ago_50 = date('Y-m-d', strtotime('-50 days'));

    global $wpdb;
    $wpdb->show_errors();

    $table_name = $wpdb->prefix . "trfdailypost";

    $query = "SELECT * FROM $table_name WHERE postdate = '$today'";
    $row = $wpdb->get_row($query);

    // if record exists for today, returns it.
    if ($row) return $row->postid;

    // postIds for last 50 days
    $query = "SELECT * FROM $table_name WHERE postdate >= '$days_ago_50'";
    $results = $wpdb->get_results($query);
    $chosenIds = array();
    foreach ($results as $row) {
        array_push($chosenIds, $row->postid);
    }

    $posts_table_name = $wpdb->prefix . "posts";
    $query = "SELECT id FROM $posts_table_name WHERE post_status = 'publish' and post_type in ('post', 'page')";
    $results = $wpdb->get_results($query);

    $postIds = array();
    foreach ($results as $row) {
        if (!in_array($row->id, $chosenIds) && get_post_meta($row->id, 'trf_get_traffic', TRUE)) {
            array_push($postIds, $row->id);
        }
    }

    if (count($postIds) == 0) return false;

    $chosenId = $postIds[mt_rand(0, count($postIds) - 1)];
    $wpdb->insert($table_name,
        array('postdate' => $today, 'postid' => $chosenId),
        array('%s', '%d'));

    return $chosenId;
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

class trfSpintax {
    public function process($text) {
        return preg_replace_callback(
            '/\{(((?>[^\{\}]+)|(?R))*)\}/x',
            array($this, 'replace'),
            $text
        );
    }

    public function replace($text) {
        $text = $this->process($text[1]);
        $parts = explode('|', $text);
        return $parts[array_rand($parts)];
    }
}
