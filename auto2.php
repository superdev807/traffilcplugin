<?php
require('../../../wp-blog-header.php');

set_time_limit(200);
function return_300( $seconds ) {
  // change the default feed cache recreation period to 2 hours
  return 300;
}

add_filter( 'wp_feed_cache_transient_lifetime' , 'return_300' );

$post_id = trfSelectPostId();

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
