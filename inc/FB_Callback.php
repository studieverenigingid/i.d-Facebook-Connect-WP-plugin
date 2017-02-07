<?php

namespace svid\Facebook_Connect;

class FB_Callback
{

  public function __construct()
  {
      add_action('admin_post_svid_facebook_connect_callback', array($this, 'svid_facebook_connect_callback'));
  }



  public function svid_facebook_connect_callback()
  {

    session_start();

    date_default_timezone_set('Europe/Amsterdam');

    echo 'in the callback... now';

    global $fb;

    $helper = $fb->getRedirectLoginHelper();

    $options = get_option('svid_facebook_connect_tokens');

    try {
      $accessToken = $helper->getAccessToken();
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      // When Graph returns an error
      echo 'Graph returned an error: ' . $e->getMessage();
      exit;
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      exit;
    }

    if (! isset($accessToken)) {
      if ($helper->getError()) {
        header('HTTP/1.0 401 Unauthorized');
        echo "Error: " . $helper->getError() . "\n";
        echo "Error Code: " . $helper->getErrorCode() . "\n";
        echo "Error Reason: " . $helper->getErrorReason() . "\n";
        echo "Error Description: " . $helper->getErrorDescription() . "\n";
      } else {
        header('HTTP/1.0 400 Bad Request');
        echo 'Bad request';
      }
      exit;
    }

    // Logged in
    echo '<h3>Access Token</h3>';
    var_dump($accessToken->getValue());

    // The OAuth 2.0 client handler helps us manage access tokens
    $oAuth2Client = $fb->getOAuth2Client();

    // Get the access token metadata from /debug_token
    $tokenMetadata = $oAuth2Client->debugToken($accessToken);
    echo '<h3>Metadata</h3>';
    var_dump($tokenMetadata);

    try {
      // Validation (these will throw FacebookSDKException's when they fail)
      $app_id = (isset($options['fb_app_id'])) ? $options['fb_app_id'] : SVID_FB_APP_ID;
      $appIdValidation = $tokenMetadata->validateAppId($app_id); // Replace {app-id} with your app id
    } catch (\Facebook\Exceptions\FacebookSDKException $e) {
      echo "<p>Error with appId validation: " . $e->getMessage() . "</p>\n\n";
      exit;
    }

    // If you know the user ID this access token belongs to, you can validate it here
    //$tokenMetadata->validateUserId('123');
    $tokenMetadata->validateExpiration();

    if (! $accessToken->isLongLived()) {
      // Exchanges a short-lived access token for a long-lived one
      try {
        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
      } catch (\Facebook\Exceptions\FacebookSDKException $e) {
        echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
        exit;
      }

      echo '<h3>Long-lived</h3>';
      var_dump($accessToken->getValue());
      echo 'now redirecting';
    } else {
      echo '<h3>Short-lived</h3>';
    }

    $_SESSION['fb_access_token'] = (string) $accessToken;

    // User is logged in with a long-lived access token.
    // You can redirect them to a members-only page.
    // header('Location: ' . menu_page_url( 'svid-facebook-connect', false ));
    wp_redirect(admin_url( 'options-general.php?page=svid-facebook-connect' ));
    exit;
  }



}

if (is_admin()) {
		new FB_Callback();
}
