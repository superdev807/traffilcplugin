<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require('../../../wp-blog-header.php');

$fbid = $_GET['fbid'];
$secret = $_GET['secret'];

$fb = new Facebook\Facebook([
	'app_id' => $fbid,
	'app_secret' =>$secret,
]);

$helper = $fb->getRedirectLoginHelper();

try {
	$accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
	// When Graph returns an error
	echo 'Graph returned an error: ' . $e->getMessage();
	exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
	// When validation fails or other local issues
	echo 'Facebook SDK returned an error: ' . $e->getMessage();
	exit;
}

if (isset($accessToken)) {
	$token = $_SESSION['facebook_access_token'] = (string) $accessToken;
	update_post_meta('111111113', 'trfFbAccessToken', $token);
	$location = get_site_url()."/wp-admin/admin.php?page=trf";

	echo '
	<script>
		window.location.href = "'.$location.'";
	</script>';
}
