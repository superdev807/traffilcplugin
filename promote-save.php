<?php
    require('../../../wp-blog-header.php');
    header("HTTP/1.1 200 OK");

    $id = $_POST['id'];
    $post = get_post($id);

    save_traffic_meta($id, $post);

    function save_traffic_meta( $post_id, $post ) {
      if (!isset($_POST['trf_traffic_nonce']) || !wp_verify_nonce($_POST['trf_traffic_nonce'], 'trf_'.$post->ID))
        return $post_id;

      $post_type = get_post_type_object($post->post_type);

      if (!current_user_can($post_type->cap->edit_post, $post_id))
        return $post_id;

      if ($_POST['keyword'] === 'true') {
        save_or_update_meta($post_id, 'keyword1', 'trf_keyword1');
        save_or_update_meta($post_id, 'keyword2', 'trf_keyword2');
        save_or_update_meta($post_id, 'keyword3', 'trf_keyword3');
      } else {
        save_or_update_meta($post_id, 'get_traffic', 'trf_get_traffic');
      }
    }

    // -----------------------------

    if ($_POST['keyword'] === 'true') return;

    $get_traffic  = get_post_meta($id, 'trf_get_traffic', true);

    if ($get_traffic != '1') return;

    $facebookReady = !empty(get_post_meta(111111113, 'trfFbAccessToken', TRUE));
    $twitterReady  = !empty(get_post_meta(111111113, 'trfTwTokenSecret', TRUE));
    $redditReady   = !empty(get_post_meta(111111113, 'trfRdRefreshToken', TRUE));

    $keyword1     = get_post_meta($id, 'trf_keyword1', true);
    $keyword2     = get_post_meta($id, 'trf_keyword2', true);
    $keyword3     = get_post_meta($id, 'trf_keyword3', true);

    if ($get_traffic == 1) {
      if ($facebookReady) {
        trfProcesskeyword($keyword1);
        trfProcesskeyword($keyword2);
        trfProcesskeyword($keyword3);
      }

      if (pagesWithTalkingAbout($keyword1, $keyword2, $keyword3) < 150) {
        echo "Your keywords are too specific to generate traffic from Facebook, please try using more general keywords.<br/><br/>\n";
      }
      else {
        if ($twitterReady) {
          trfSearchTweets($keyword1);
          trfSearchTweets($keyword2);
          trfSearchTweets($keyword3);
        }

        if (tweetsCount($keyword1, $keyword2, $keyword3) < 200) {
          echo "Your keywords are too specific to generate traffic from Twitter, please try using more general keywords.<br/><br/>\n";
        }
        else {
          if ($facebookReady) {
            createFacebookPost($id);
            echo "Created your Facebook post and getting Facebook traffic for you now!<br/><br/>\n";
          }

          if ($twitterReady && createTweet($id, $keyword1, $keyword2, $keyword3)) {
            echo "Created your Twitter post and getting Twitter traffic for you now!<br/><br/>\n";
          }

          if ($redditReady && createRedditPost($id)) {
            echo "Created your Reddit post and getting Reddit traffic for you now!<br/><br/>\n";
          }
        }
      }
    }

    function pagesWithTalkingAbout($keyword1, $keyword2, $keyword3) {
      global $wpdb;

      $tbl_kws_fb = $wpdb->prefix . "trfpages";
      $row = $wpdb->get_row("SELECT count(id) as ct FROM $tbl_kws_fb WHERE talking_about >= 100 and keyword in ('$keyword1', '$keyword2', '$keyword3')");

      return intval($row->ct);
    }

    function tweetsCount($keyword1, $keyword2, $keyword3) {
      global $wpdb;

      $tbl_kws_fb = $wpdb->prefix . "trftweets";
      $row = $wpdb->get_row("SELECT count(id) as ct FROM $tbl_kws_fb WHERE keyword in ('$keyword1', '$keyword2', '$keyword3')");

      return intval($row->ct);
    }
