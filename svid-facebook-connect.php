<?php
/*
Plugin Name: i.d Facebook Connect
Description: Plugin to connect with Facebook and (e.g.) import Facebook events to Wordpress post type events.
Version: 0.1
Author: Floris Jansen
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Europe/Amsterdam');

require_once plugin_dir_path(__FILE__) . '/inc/src/Facebook/autoload.php';

$options = get_option('svid_facebook_connect_tokens');
define('FB_PLUGIN_NAME', plugin_basename( __FILE__ ));

if ( isset($options['fb_app_id']) &&
		 isset($options['fb_app_secret'])) {
	$fb = new Facebook\Facebook([
 		'app_id' => $options['fb_app_id'], // Replace {app-id} with your app id
 		'app_secret' => $options['fb_app_secret'],
 		'default_graph_version' => 'v2.8',
 		]);
} elseif ( DEFINED('SVID_FB_APP_ID') &&
					 DEFINED('SVID_FB_APP_SECRET') ) {
	$fb = new Facebook\Facebook([
		'app_id' => SVID_FB_APP_ID, // Replace {app-id} with your app id
		'app_secret' => SVID_FB_APP_SECRET,
		'default_graph_version' => 'v2.8',
		]);
}

/*
 * Options Page
 */
require_once plugin_dir_path(__FILE__) . '/inc/Settings.php';

/*
 * Callback from Facebook OAuth
 */
require_once plugin_dir_path(__FILE__) . '/inc/FB_Callback.php';

/*
 * Meta box at event post type edit page
 */
require_once plugin_dir_path(__FILE__) . '/inc/Event_Meta_Box.php';

/*
 * API to list events
 */
require_once plugin_dir_path(__FILE__) . '/inc/FB_Event_List_API.php';
