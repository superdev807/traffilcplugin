<?php
require('../../../wp-blog-header.php');

set_time_limit(200);
function return_300( $seconds ) {
  // change the default feed cache recreation period to 2 hours
  return 300;
}

add_filter( 'wp_feed_cache_transient_lifetime' , 'return_300' );

$post_id = trfSelectPostId();
trfFollow($post_id);

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

function trfFollow($post_id) {
	$keyword1		= get_post_meta($post_id, 'trf_keyword1', true);
	$keyword2		= get_post_meta($post_id, 'trf_keyword2', true);
	$keyword3		= get_post_meta($post_id, 'trf_keyword3', true);

	$id 			= get_post_meta(111111113, 'trfTwKey', true);
	$secret			= get_post_meta(111111113, 'trfTwSecret', true);
	$token			= get_post_meta(111111113, 'trfTwToken', true);
	$tokensecret	= get_post_meta(111111113, 'trfTwTokenSecret', true);
	$userid			= get_post_meta(111111113, 'trfTwOwnerID', true);

	$settings 		= array(
		'oauth_access_token'		=> $token,
		'oauth_access_token_secret' => $tokensecret,
		'consumer_key'				=> $id,
		'consumer_secret'			=> $secret
	);

	// echo "settings... \n";
	// var_dump($settings);

	$list_id = trfGetList($settings, $userid);
	$recentTweets = trfGetRecentTweets($settings, $keyword1, $keyword2, $keyword3);

	foreach($recentTweets as $tweet) {
		$id 		= $tweet['user']['id'];
		$tweetid	= $tweet['id_str'];
		$name		= $tweet['user']['screen_name'];

		if (!trfCheckIfFollow($settings, $userid, $id)) {
			$url = 'https://api.twitter.com/1.1/favorites/create.json';
			$requestMethod	= 'POST';
			$postfields = array (
				'id' => $tweetid
			);

			$twitter = new TwitterAPIExchange($settings);
			$res = $twitter->buildOauth($url, $requestMethod)
							->setPostfields($postfields)
							->performRequest();

			$res = json_decode($res, true);
			if ($res['errors']) {
				echo $res['errors'][0]['message'] . "\n";
				// trfInsertHistory('twitter_like_error', json_encode($res), "https://twitter.com/$name/status/$tweetid", $name, $post_id);
			}
			else {
				trfInsertHistory('twitter_like', json_encode($res), "https://twitter.com/$name/status/$tweetid", $name, $post_id);
			}

			if (!empty($list_id)) {
				$url = 'https://api.twitter.com/1.1/lists/members/create.json';
				$requestMethod = 'POST';
				$postfields = array (
					'list_id'		=> $list_id,
					'user_id'		=> $userid,
					'screen_name'	=> $name
				);

				$twitter = new TwitterAPIExchange($settings);
				$res = $twitter->buildOauth($url, $requestMethod)
								->setPostfields($postfields)
								->performRequest();

				$res = json_decode($res, true);

				if ($res['errors']) {
					echo $res['errors'][0]['message'] . "\n";
					trfInsertHistory('twitter_member_error', json_encode($res), "https://twitter.com/$name/status/$tweetid", $name, $post_id);
				} else {
					trfInsertHistory('twitter_member', json_encode($res), "https://twitter.com/$name/status/$tweetid", $name, $post_id);
				}
			}

			$url = 'https://api.twitter.com/1.1/friendships/create.json';
			$requestMethod = 'POST';
			$postfields = array (
				'user_id'		=> $id,
				'follow'		=> 'true'
			);

			$twitter = new TwitterAPIExchange($settings);
			$res = $twitter->buildOauth($url, $requestMethod)
							->setPostfields($postfields)
							->performRequest();

			$res = json_decode($res, true);
			if ($res['errors']) {
				echo $res['errors'][0]['message'] . "\n";
				trfInsertHistory('twitter_follow_error', json_encode($res), "https://twitter.com/$name/status/$tweetid", $name, $post_id);
			} else {
				// var_dump($res);
				trfInsertHistory('twitter_follow', json_encode($res), "https://twitter.com/$name/status/$tweetid", $name, $post_id);
			}
		} else {
			echo "skipping because it's already followed";
		}

		usleep(mt_rand(2000000,3000000));
	}
}

function trfGetList($settings, $userid) {
	$url = 'https://api.twitter.com/1.1/lists/list.json';

	$getfield = '?user_id=' . $userid . '&reverse=true';
	$requestMethod = 'GET';

	$twitter = new TwitterAPIExchange($settings);
	$response = $twitter->setGetfield($getfield)
						->buildOauth($url, $requestMethod)
					    ->performRequest();

	$response = json_decode($response, TRUE);

	$name = $response[0]['name'];
	$id = $response[0]['id'];

	// echo "Get List... \n";
	// var_dump($response);
	return $id;
}

function trfCheckIfFollow($settings, $userid, $id) {
	$url =  'https://api.twitter.com/1.1/followers/list.json';
	$getfield = '?user_id=' . $userid . '&count=200';
	$requestMethod = 'GET';

	$twitter = new TwitterAPIExchange($settings);
	$response = $twitter->setGetfield($getfield)
						->buildOauth($url, $requestMethod)
						->performRequest();

	$response = json_decode($response, TRUE);
	$followers = $response['users'];

	foreach ($followers as $follower) {
		$followerid = $follower['id'];

		if ($id == $followerid) {
			return 1;
		}
	}

	return 0;
}

function trfGetRecentTweets($settings, $keyword1, $keyword2, $keyword3) {
	$url = 'https://api.twitter.com/1.1/search/tweets.json';

	$getfield = "?q=#{$keyword1}&result_type=recent&count=10";

	$requestMethod = 'GET';

	$twitter = new TwitterAPIExchange($settings);
	$response1 = $twitter->setGetfield($getfield)
						 ->buildOauth($url, $requestMethod)
						 ->performRequest();
	$response1 = json_decode($response1, TRUE);

	$getfield = "?q=#{$keyword2}&result_type=recent&count=10";
	$response2 = $twitter->setGetfield($getfield)
						 ->buildOauth($url, $requestMethod)
						 ->performRequest();
	$response2 = json_decode($response2, TRUE);

	$getfield = "?q=#{$keyword3}&result_type=recent&count=10";
	$response3 = $twitter->setGetfield($getfield)
						 ->buildOauth($url, $requestMethod)
						 ->performRequest();
	$response3 = json_decode($response3, TRUE);

	$response = array_merge(
		empty($response1['statuses']) ? [] : $response1['statuses'],
		empty($response2['statuses']) ? [] : $response2['statuses'],
		empty($response3['statuses']) ? [] : $response2['statuses']
	);
	$response = array_slice($response, 0, mt_rand(1, 3));

	// echo "getting recent tweets...";
	// var_dump($response);
	return $response;
}
