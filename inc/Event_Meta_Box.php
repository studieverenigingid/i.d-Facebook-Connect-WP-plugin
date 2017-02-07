<?php

namespace svid\Facebook_Connect;

class Event_Meta_Box
{

	public function setup()
	{
		add_action( 'add_meta_boxes', array($this, 'add_event_metaboxes') );
		add_action( 'save_post', array($this, 'save_event_post_meta'),
			10, // Priority
			2 // Amount of arguments callback can take
		);
	}



	public function add_event_metaboxes()
	{
		add_meta_box(
			'svid_facebook_connect_metabox',
			'Facebook event connection',
			array($this, 'render_event_metabox'),
			'event'
		);
	}

	public function render_event_metabox()
	{
		global $post;
		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		// Get the location data if its already been entered
		$fb_event_id = get_post_meta($post->ID, 'svid_facebook_connect_event_id', true);

		// Echo out the field
		//echo '<input type="text" name="svid_facebook_connect_event_id" value="' . $fb_event_id  . '" class="widefat">';

		echo '<ul class="js-event-list" data-event-picked="' . $fb_event_id . '"></ul>'; ?>

			<button type="button" name="update-event-info" class="button button-secondary js-update-event-info">
				Update event information with Facebook data
			</button>
		</p>

		<?php wp_register_script(
			'event_ajax', // Script id
			plugins_url('../js/event_ajax.js', __FILE__), // Url
			array('jquery'), // Deps
			false, // Version
			true // Load in footer
		);
    wp_enqueue_script('event_ajax');

	}



	public function save_event_post_meta($post_id, $post)
	{

	  /* Verify the nonce before proceeding. */
	  if ( !isset( $_POST['eventmeta_noncename'] ) ) {
			error_log('no eventmeta_noncename');
	    return $post_id;
		}

		if ( !wp_verify_nonce( $_POST['eventmeta_noncename'], plugin_basename( __FILE__ ) ) ) {
			error_log('eventmeta_noncename not valid: ' . $_POST['eventmeta_noncename']);
			return $post_id;
		}

	  /* Get the post type object. */
	  $post_type = get_post_type_object( $post->post_type );

	  /* Check if the current user has permission to edit the post. */
	  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			error_log('user can’t edit ' . $post_id);
	    return $post_id;
		}

	  /* Get the posted data and sanitize it for use as an HTML class. */
	  $new_meta_value = ( isset( $_POST['svid_facebook_connect_event_id'] ) ?
			sanitize_html_class( $_POST['svid_facebook_connect_event_id'] ) : '' );

	  /* Get the meta key. */
	  $meta_key = 'svid_facebook_connect_event_id';

	  /* Get the meta value of the custom field key. */
	  $meta_value = get_post_meta( $post_id, $meta_key, true );

	  /* If a new meta value was added and there was no previous value, add it. */
	  if ( $new_meta_value && '' == $meta_value )
	    add_post_meta( $post_id, $meta_key, $new_meta_value, true );

	  /* If the new meta value does not match the old value, update it. */
	  elseif ( $new_meta_value && $new_meta_value != $meta_value )
	    update_post_meta( $post_id, $meta_key, $new_meta_value );

	  /* If there is no new meta value but an old value exists, delete it. */
	  elseif ( '' == $new_meta_value && $meta_value )
	    delete_post_meta( $post_id, $meta_key, $meta_value );

	}


}

if (is_admin()) {
	$meta_box = new Event_Meta_Box();
	add_action( 'load-post.php', array($meta_box, 'setup') );
	add_action( 'load-post-new.php', array($meta_box, 'setup') );
}
