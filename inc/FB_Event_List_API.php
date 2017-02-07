<?php

namespace svid\Facebook_Connect;

class FB_Event_List_API
{

	private $optios;

	public function __construct()
	{
		add_action( 'wp_ajax_nopriv_event_list', array($this, 'event_list') );
		add_action( 'wp_ajax_event_list', array($this, 'event_list') );
	}

	public function event_list()
	{
		global $fb;

		$this->options = get_option('svid_facebook_connect_tokens');

		$page = $this->options['page_token'];
		$page = explode(';', $page);
		$page_token = $page[0];
		$page_id = $page[2];

		$request = $fb->request(
			'GET',
			'/' . $page_id . '/events',
			[],
			$page_token
		);

		try {
			$response = $fb->getClient()->sendRequest($request);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
			// When Graph returns an error
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}

		$graphEdge = $response->getGraphEdge();
		$event_list = array();

		foreach ($graphEdge as $graphNode) {
			$event_list[] = $graphNode->asArray();
		}

		$api_response = array(
			'success' => true,
			'events' => $event_list
		);

		wp_send_json($api_response);
		echo 'a';
    wp_die();
	}
}

new FB_Event_List_API();
