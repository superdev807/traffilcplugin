<?php
function trf_install() {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	global $wpdb;
	$wpdb->show_errors();

	$table_name = $wpdb->prefix ."trfpages";

	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			node_id varchar(30),
			title varchar(1024),
			talking_about int(11),
			likes int(11),
			keyword varchar(255) NOT NULL,
			UNIQUE KEY id (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix ."trftweets";

	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			node_id varchar(30),
			tweet varchar(1024),
			keyword varchar(255) NOT NULL,
			UNIQUE KEY id (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix . "trfhistory";

	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE $table_name (
			id INTEGER(100) UNSIGNED AUTO_INCREMENT,
			post_id int(11),
			created int(11),
			source varchar(20),
			url varchar(1024),
			title varchar(255),
			raw_result text,
			UNIQUE KEY id (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix . "trfcommenthistory";

	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE $table_name (
			id INTEGER(100) UNSIGNED AUTO_INCREMENT,
			post_id int(11),
			history_date varchar(20),
			fb_post varchar(50),
			timesent int(11),
			UNIQUE KEY id (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix . "trfdailypost";

	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE $table_name (
			id INTEGER(100) UNSIGNED AUTO_INCREMENT,
			postdate varchar(20),
			postid int(11),
			UNIQUE KEY id (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix ."trfcomments";
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE $table_name (
			id INTEGER(100) UNSIGNED AUTO_INCREMENT,
			comment text,
			campaign text,
			UNIQUE KEY id (id)
		)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;";

	   	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);

		$wpdb->insert( $table_name,  array(
            'comment' =>'{Nice |Great}{|!|post|info|} {|thanks|thanks a lot} {but|} {read|check out| check out this} {[LINK] | my page}',
            'campaign' => 'ALL'
    	) );

    	$wpdb->insert( $table_name,  array(
            'comment' =>'{Interesting|Cool } {post|info|} {|thanks|so much} {this is|} {really great|really good} {but look | take a look at } {this |this too | my fanpage } {[LINK] | } ',
            'campaign' => 'ALL'
	    ) );
    	$wpdb->insert( $table_name,  array(
                'comment' =>'{Awesome|Tremendous|Amazing} {| post}  {but |} {read|check out| check out this} {[LINK] | our page} ',
                'campaign' => 'ALL'
	    ) );
	    $wpdb->insert( $table_name,  array(
            'comment' =>'Thanks {|!| for the post| for the info|} {big fan here|} {but |} {you need to see |you need to read} {this|} {[LINK] | page}',
            'campaign' => 'ALL'
	    ) );
    	$wpdb->insert( $table_name,  array(
            'comment' =>'Anyone else {seen [LINK] | thinks this [LINK] is cool | seen our page | thinks my page is cool } {?|}',
            'campaign' => 'ALL'
	    ) );
    	$wpdb->insert( $table_name,  array(
            'comment' =>'{who else|who else really} {has seen|has read} {this|} {[LINK] | our fanpage | page } {?|}',
            'campaign' => 'ALL'
	    ) );
    	$wpdb->insert( $table_name,  array(
            'comment' =>'{Check this out |Take a look at | Read this} { too | as well} {guys |everyone} [LINK]',
            'campaign' => 'ALL'
	    ) );
    	$wpdb->insert( $table_name,  array(
            'comment' =>'{Reminds me of this | Similiar ?} {[LINK]} {who agrees?|}',
            'campaign' => 'ALL'
	    ) );
	    $wpdb->insert( $table_name,  array(
            'comment' =>'anyone {|else} {love| like } {|this | this post} { [LINK] | on my page } as much as {me|i do}  ',
            'campaign' => 'ALL'
	    ) );
	    $wpdb->insert( $table_name,  array(
            'comment' =>'{loving the| love the| such a great}  {post | page | fanpage} {but look | take a look at }{this |this too} {[LINK] | my page | our page too}',
            'campaign' => 'ALL'
	    ) );
	    $wpdb->insert( $table_name,  array(
            'comment' =>'{Valuable Post | Important Post| Important Info} {on my page| [LINK]} {!|} ',
            'campaign' => 'ALL'
	    ) );
	    $wpdb->insert( $table_name,  array(
            'comment' =>'Anything { about | related to} this is {so|very|really|} {you need to see |you need to read} {{this|} [LINK] | {our page | my page | my fanpage}  ',
            'campaign' => 'ALL'
	    ) );
	    $wpdb->insert( $table_name,  array(
            'comment' =>'More {info|posts|stuff} {like this | such as this [LINK] } {please|ok?} {like if you agree|like = agree|who agrees?|}  ',
            'campaign' => 'ALL'
    	) );
	    $wpdb->insert( $table_name,  array(
            'comment' =>'{Hey everyone|Hey | Guys |  You need to }{read|check out| check out this} { [LINK] |us too } ',
            'campaign' => 'ALL'
    	) );
	    $wpdb->insert( $table_name,  array(
            'comment' =>'{Stuff |posts} {like this} [LINK] are why {I love | I like | everyone loves | everyone likes} {social media | facebook }{agree ?|? |} ',
            'campaign' => 'ALL'
    	) );
	    $wpdb->insert( $table_name,  array(
            'comment' =>'{<3| I <3 | Who else? <3| Who else? <3} [LINK] {?|} ',
            'campaign' => 'ALL'
    	) );
	}

	update_post_meta(111111113, 'trf_show_posts', true );
	update_post_meta(111111113, 'trf_show_pages', true );
}
