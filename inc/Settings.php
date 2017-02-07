<?php

namespace svid\Facebook_Connect;

class Settings
{

		private $options;

		public function __construct()
		{
				add_action('admin_menu', array($this, 'add_plugin_page'));
				add_action('admin_init', array($this, 'page_init'));
		}

		public function add_plugin_page()
		{
				add_options_page(
						'i.d Facebook Connect',
						'i.d Facebook Connect',
						'manage_options',
						'svid-facebook-connect',
						array($this, 'create_admin_page')
				);
		}

		/**
		 * Options page callback
		 */
		public function create_admin_page()
		{
				// Set class property
				$this->options = get_option('svid_facebook_connect_tokens');
				session_start();
				global $fb;
				?>
				<div class="wrap">
						<h2>i.d Facebook Connect</h2>

						<form method="post" action="options.php">
								<?php
								// This prints out all hidden setting fields
								settings_fields('default_settings');
								do_settings_sections('svid-facebook-connect');
								submit_button();
								?>
						</form>
				</div>
				<?php

		}



		/**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'default_settings', // Option group
            'svid_facebook_connect_tokens', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'svid_facebook_connect_general', // ID
            'Instellingen', // Title
            array($this, 'print_token_section'), // Callback
            'svid-facebook-connect' // Page
        );

				add_settings_field(
            'fb_app_id', // ID
            'App id', // Title
            array($this, 'app_id_callback'), // Callback
            'svid-facebook-connect', // Page
            'svid_facebook_connect_general' // Section
        );

				add_settings_field(
            'fb_app_secret', // ID
            'App secret', // Title
            array($this, 'app_secret_callback'), // Callback
            'svid-facebook-connect', // Page
            'svid_facebook_connect_general' // Section
        );

				add_settings_field(
            'user_token', // ID
            'User Token', // Title
            array($this, 'user_token_callback'), // Callback
            'svid-facebook-connect', // Page
            'svid_facebook_connect_general' // Section
        );

        add_settings_field(
            'page_token', // ID
            'Page Token', // Title
            array($this, 'page_token_callback'), // Callback
            'svid-facebook-connect', // Page
            'svid_facebook_connect_general' // Section
        );
    }

		public function sanitize($input)
    {
        $new_input = array();

				if (isset($input['fb_app_id']))
            $new_input['fb_app_id'] = sanitize_text_field($input['fb_app_id']);

				if (isset($input['fb_app_secret']))
            $new_input['fb_app_secret'] = sanitize_text_field($input['fb_app_secret']);

				if (isset($input['user_token']))
            $new_input['user_token'] = sanitize_text_field($input['user_token']);

        if (isset($input['page_token']))
            $new_input['page_token'] = sanitize_text_field($input['page_token']);

        return $new_input;
    }

		public function print_token_section()
		{
			echo "<p>Use these settings to set up i.d Facebook Connect so you can create Wordpress events from existing Facebook events.</p>";
		}

		public function app_id_callback()
		{
			printf(
					'<input type="text" id="fb_app_id" name="svid_facebook_connect_tokens[fb_app_id]" value="%s" />',
					isset($this->options['fb_app_id']) ? esc_attr($this->options['fb_app_id']) : ''
			);

			if (DEFINED('SVID_FB_APP_ID')) {
					echo '<p class="description">App id set via WP_CONFIG to '. SVID_FB_APP_ID .'. You can override by using these settings.</p>';
			}
		}

		public function app_secret_callback()
		{
			printf(
					'<input type="text" id="fb_app_secret" name="svid_facebook_connect_tokens[fb_app_secret]" value="%s" />',
					isset($this->options['fb_app_secret']) ? esc_attr($this->options['fb_app_secret']) : ''
			);

			if (DEFINED('SVID_FB_APP_SECRET')) {
					echo '<p class="description">App secret set via WP_CONFIG to '. SVID_FB_APP_SECRET .'. You can override by using these settings.</p>';
			}
		}

		public function user_token_callback()
		{
			global $fb;
			if (!isset($fb)) {
				echo 'Set app id and secret first.';
				return false;
			}

			if (isset($this->options['user_token']) ||
					isset($_SESSION['fb_access_token'])) {
				$this->show_name($fb);
			} else {
				$this->login_link($fb);
			}
		}

		public function page_token_callback()
		{
			global $fb;
			if (!isset($fb)) {
				echo 'Set app id and secret first.';
				return false;
			}

			if (isset($this->options['user_token']) &&
					$this->options['user_token'] === $_SESSION['fb_access_token']) {
				$this->show_page($fb);
			} elseif (isset($_SESSION['fb_access_token'])) {
				echo 'Save these settings to display your Facebook pages';
			}
		}



		public function show_name($fb)
		{
			$user_token = (isset($_SESSION['fb_access_token'])) ? $_SESSION['fb_access_token'] : $this->options['user_token'] ;

			try {
				// Returns a `Facebook\FacebookResponse` object
				$response = $fb->get('/me?fields=id,name', $user_token);
			} catch(\Facebook\Exceptions\FacebookResponseException $e) {
				$this->show_exception('Graph returned an ', $fb, $e);
				return;
				exit;
			} catch(\Facebook\Exceptions\FacebookSDKException $e) {
				$this->show_exception('Facebook SDK returned an', $fb, $e);
				return;
				exit;
			}

			$user = $response->getGraphUser();

			echo 'You are logged in as ' . $user['name'];
			echo '<input type="hidden" name="svid_facebook_connect_tokens[user_token]" value="' . $user_token . '">';
		}

		public function show_exception($who_did, $fb, $e)
		{
			echo '<div class="error">';
			echo '<p>' . $who_did . 'error: ' . $e->getMessage() . '</p>';
			echo '</div>';
			$this->login_link($fb);
		}

		public function login_link($fb)
		{
			$helper = $fb->getRedirectLoginHelper();

			$get_parameters = array('action' => 'svid_facebook_connect_callback');
			$admin_post_url = admin_url( 'admin-post.php' );
			$callback_url = $admin_post_url  . '?' . http_build_query($get_parameters);

			$permissions = ['email', 'manage_pages', 'publish_pages', 'pages_show_list']; // Optional permissions
			$loginUrl = $helper->getLoginUrl($callback_url, $permissions);

			echo '<a class="button-primary" href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook</a>';

			unset($_SESSION['fb_access_token']);
		}



		public function show_page($fb)
		{
			$user_token = $this->options['user_token'];

			$request = $fb->request(
				'GET',
				'/me/accounts',
				[],
				$user_token
			);

			try {
				$response = $fb->getClient()->sendRequest($request);
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
				// When Graph returns an error
				echo 'Graph returned an error: ' . $e->getMessage();
				return;
				exit;
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
				// When validation fails or other local issues
				echo 'Facebook SDK returned an error: ' . $e->getMessage();
				return;
				exit;
			}

			$graphEdge = $response->getGraphEdge();

			$page = $this->options['page_token'];
			$page = explode(';', $page);
			$page_id = $page[2];

			foreach ($graphEdge as $graphNode) {
				$node_access_token = $graphNode->getField('access_token');
				$node_name = $graphNode->getField('name');
				$node_id = $graphNode->getField('id');
				$page_value = [$node_access_token, $node_name, $node_id];
				$page_value = implode(';', $page_value);

				echo '<p>';
				echo '<input
					name="svid_facebook_connect_tokens[page_token]"
					type="radio"
					value="' . $page_value . '"' .
					(($page_id === $node_id) ? 'checked' : '') .
					'>';
				echo $node_name;
				echo '</p>';
			}

		}
}

if (is_admin()) {
		new Settings();
}
