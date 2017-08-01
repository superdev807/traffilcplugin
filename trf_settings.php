<?php
function trf_settings() {
    global $wpdb;

    if(isset($_POST['trfAuth'])) {
        $facebook_id = trim($_POST['apikey']);
        $facebook_secret =  trim($_POST['apisecret']);

        $reddit_app_id = trim($_POST['reddit_app_id']);
        $reddit_app_secret = trim($_POST['reddit_app_secret']);

        $twitter_apikey = trim($_POST['twitter_apikey']);
        $twitter_apisecret = trim($_POST['twitter_apisecret']);
        $twitter_token = trim($_POST['twitter_token']);
        $twitter_tokensecret = trim($_POST['twitter_tokensecret']);
        $twitter_ownerid = trim($_POST['twitter_ownerid']);

        $selected_facebook_page = trim($_POST['selected_facebook_page']);
        $selected_reddit_subpage = trim($_POST['selected_reddit_subpage']);

        $trftimezone =  trim($_POST['trftimezone']);

        update_post_meta('111111113', 'trfFbID', $facebook_id);
        update_post_meta('111111113', 'trfFbSecret', $facebook_secret);

        update_post_meta('111111113', 'trfRdID', $reddit_app_id);
        update_post_meta('111111113', 'trfRdSecret', $reddit_app_secret);

        update_post_meta('111111113', 'trfTwKey', $twitter_apikey);
        update_post_meta('111111113', 'trfTwSecret', $twitter_apisecret);
        update_post_meta('111111113', 'trfTwToken', $twitter_token);
        update_post_meta('111111113', 'trfTwTokenSecret', $twitter_tokensecret);
        update_post_meta('111111113', 'trfTwOwnerID', $twitter_ownerid);

        update_post_meta('111111113', 'trfFbPage', $selected_facebook_page);
        update_post_meta('111111113', 'trfRdPage', $selected_reddit_subpage);

        update_post_meta('111111113', 'trftimezone', $trftimezone );

        $successmessage = " <strong>Settings Saved</strong>";
    }

    $facebook_id = get_post_meta(111111113, 'trfFbID', TRUE);
    $facebook_secret = get_post_meta(111111113, 'trfFbSecret', TRUE);

    $reddit_app_id = get_post_meta(111111113, 'trfRdID', TRUE);
    $reddit_app_secret = get_post_meta(111111113, 'trfRdSecret', TRUE);

    $twitter_apikey = get_post_meta(111111113, 'trfTwKey', TRUE);
    $twitter_apisecret = get_post_meta(111111113, 'trfTwSecret', TRUE);
    $twitter_token = get_post_meta(111111113, 'trfTwToken', TRUE);
    $twitter_tokensecret = get_post_meta(111111113, 'trfTwTokenSecret', TRUE);
    $twitter_ownerid = get_post_meta(111111113, 'trfTwOwnerID', TRUE);

    $selected_facebook_page = get_post_meta(111111113, 'trfFbPage', true);
    $selected_reddit_subpage = get_post_meta(111111113, 'trfRdPage', true);

    if (!empty($facebook_id)) {
        $trftimezone = get_post_meta(111111113, 'trftimezone', TRUE);

        $fb = new Facebook\Facebook([
            'app_id' => $facebook_id,
            'app_secret' =>$facebook_secret,
        ]);

        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['publish_actions,manage_pages,publish_pages']; // optional
        $loginUrl = $helper->getLoginUrl(plugin_dir_url(__FILE__ ) . 'facebookcallback.php?fbid=' . $facebook_id . '&secret=' . $facebook_secret . '' , $permissions);
    }

    echo '<div class = "wrap">
        <div class = "fbvahead">' . TRAFFIC_PLUGIN_LOGO . ' </div>
        <h1>Settings</h1>
        <hr />';

    echo "<form method='post' action=''>
        <table>
            <tr><td>Facebook App ID: </td><td ><input type='text' name='apikey' value='" . $facebook_id . "'></td></tr>
            <tr><td>Facebook App Secret: </td><td ><input type='text' name='apisecret' value='" . $facebook_secret . "'></td></tr>
            <tr class='table-gap'><td>Reddit App ID: </td><td ><input type='text' name='reddit_app_id' value='" . $reddit_app_id . "'></td></tr>
            <tr><td>Reddit App Secret: </td><td ><input type='text' name='reddit_app_secret' value='" . $reddit_app_secret . "'></td></tr>
            <tr class='table-gap'><td>Twitter Consumer Key: </td><td ><input type='text' name='twitter_apikey' value='".$twitter_apikey."' size = '40'></td></tr>
            <tr><td>Twitter Consumer Secret: </td><td ><input type='text' name='twitter_apisecret' value='".$twitter_apisecret."'  size = '40'>
            <tr><td>Twitter Access Token: </td><td ><input type='text' name='twitter_token' value='".$twitter_token."' size = '40'></td></tr>
            <tr><td>Twitter Access Token Secret: </td><td ><input type='text' name='twitter_tokensecret' value='".$twitter_tokensecret."' size = '40'></td></tr>
            <tr><td>Twitter Owner ID: </td><td ><input type='text' name='twitter_ownerid' value='".$twitter_ownerid."' size = '40'></td></tr>";

    echo '<tr class="table-gap"><td>Select Your Timezone: </td><td>
        <select name="trftimezone" >';

    for ($i= 12; $i>0; $i--) {
        if ($trftimezone == "-".$i) {
            echo '<option value= -'.$i.' selected = "selected">UTC -'.$i.':00</option>';
        }
        else {
            echo '<option value= -'.$i.' >UTC -'.$i.':00</option>';
        }
    }
    for ($i= 0; $i<13; $i++) {
        if ($trftimezone == "+".$i) {
            echo '<option value= +'.$i.' selected = "selected">UTC +'.$i.':00</option>';
        }
        else {
            echo '<option value= +'.$i.' >UTC +'.$i.':00</option>';
        }
    }

    if (!empty(get_post_meta(111111113, 'trfFbAccessToken', true))) {
        echo '<tr class="table-gap"><td>Select Facebook Page</td><td><select name = "selected_facebook_page"><option></option>';
        $response = wptrfFacebookQuery("me/accounts", "");

        foreach ($response ['data'] as $page) {
            $pagename = $page['name'];
            $id = trim($page['id']);
            $token = $page['access_token'];

            if ($id == $selected_facebook_page) {
                echo "<option value = '".$id."'  selected = 'selected'>".$pagename."</option>";
            } else {
                echo "<option value = '".$id."'>".$pagename."</option>";
            }
        }
        echo '</select></td></tr>';
    }

    if ( !empty(get_post_meta(111111113, 'trfRdRefreshToken', TRUE)) ) {
        $subreddits = wptrfMySubreddits();

        if (isset($subreddits)) {
            echo $selected_reddit_subpage;

            echo '<tr class="table-gap"><td>Select Reddit Page</td><td><select name = "selected_reddit_subpage"><option></option>';

            foreach ($subreddits as $page) {
                // $url = $page['data']['url'];
                $name = trim($page['data']['display_name']);

                if ($name == $selected_reddit_subpage) {
                    echo "<option value = '".$name."' selected = 'selected'>".$name."</option>";
                } else {
                    echo "<option value = '".$name."'>".$name."</option>";
                }
            }
            echo '</select></td></tr>';
        } else {
            echo '<tr class="table-gap"><td>Reddit Token Expired</td></tr>';
        }
    }

    echo "<tr><td></td><td><input name='trfAuth' type='submit' value='Save' class = 'button button-primary'></td>";

    if (!empty($loginUrl)) {
        echo "<td><a href = '".$loginUrl."'>Auth Facebook</a></td>";
    }
    if (!empty($reddit_app_id)) {
        echo "<td><a href = '" . plugin_dir_url(__FILE__ ) . 'reddit-oauth.php' . "' target='_blank'>Auth Reddit</a></td>";
    }
    echo "</tr>";
    echo '<tr><td></td><td>' . $successmessage . '</td></tr>';

    echo '</table>
        </form>
    <hr />';

    echo "</div>";
}
