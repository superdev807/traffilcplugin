<?php
require('../../../wp-blog-header.php');

require_once __DIR__ . '/oauth2/Client.php';
require_once __DIR__ . "/oauth2/GrantType/IGrantType.php";
require_once __DIR__ . "/oauth2/GrantType/AuthorizationCode.php";

if (isset($_GET["error"])) {
    echo("<pre>OAuth Error: " . $_GET["error"]."\n");
    echo('<a href="index.php">Retry</a></pre>');
    die;
}

$authorizeUrl = 'https://ssl.reddit.com/api/v1/authorize';
$accessTokenUrl = 'https://ssl.reddit.com/api/v1/access_token';
$clientId = get_post_meta(111111113, 'trfRdID', TRUE);
$clientSecret = get_post_meta(111111113, 'trfRdSecret', TRUE);
$userAgent = 'TrafficPlugin/0.1 by DanGreen';

$redirectUrl = plugin_dir_url(__FILE__ ) . 'reddit-oauth.php';

$client = new OAuth2\Client($clientId, $clientSecret, OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
$client->setCurlOption(CURLOPT_USERAGENT,$userAgent);

if (!isset($_GET["code"]))
{
    $authUrl = $client->getAuthenticationUrl($authorizeUrl, $redirectUrl, array("scope" => "identity, edit, flair, history, modconfig, modflair, modlog, modposts, modwiki, mysubreddits, privatemessages, read, report, save, submit, subscribe, vote, wikiedit, wikiread", "state" => "SomeUnguessableValue", "duration"=>"permanent"));
    header("Location: ".$authUrl);
    die("Redirect");
}
else {
    $params = array("code" => $_GET["code"], "redirect_uri" => $redirectUrl);
    $response = $client->getAccessToken($accessTokenUrl, "authorization_code", $params);
    $accessTokenResult = $response["result"];

    update_post_meta('111111113', 'trfRdCode', $_GET["code"]);
    update_post_meta('111111113', 'trfRdAccessToken', $accessTokenResult["access_token"]);
    update_post_meta('111111113', 'trfRdRefreshToken', $accessTokenResult["refresh_token"]);

    echo '
    <script type="text/javascript">
        window.close();
    </script>';
}

save_or_update_meta($post_id, 'trf-keyword1', 'trf_keyword1');