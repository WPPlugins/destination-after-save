<?php
/**
 * Plugin Name: Destination After Save
 * Plugin URI: http://slimbobwp.com/wordpress-plugins/destination-after-save/
 * Description: Allows user to choose destination to load after saving.
 * Version: 3.0
 * Author: SlimBob
 * Author URI: http://slimbobwp.com
 * Text Domain: destination
 * Network: true
 * License: GPL2
 */

class destination_after_save {

	var $plugin_slug;

	function __construct( $plugin_slug ) {

		$this->plugin_slug = $plugin_slug;

		register_activation_hook( __FILE__, array( 'destination_after_save', 'ACTIVATE' ) );

		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );

		add_action( 'save_post', array( $this, 'load_destination' ), PHP_INT_MAX );

		add_action( 'edit_user_profile', array( $this, 'add_user_option_default_behavior' ) );

		add_action( 'show_user_profile', array( $this, 'add_user_option_default_behavior' ) );

		add_action( 'personal_options_update', array( $this, 'save_default_user_behavior' ) );

		add_action( 'edit_user_profile_update', array( $this, 'save_default_user_behavior' ) );

		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta_links' ), 10, 2 );

		register_uninstall_hook( __FILE__, array( 'destination_after_save', 'UNINSTALL' ) );

	}

	function activate() {

		$all_users = get_users();

		foreach( $all_users as $user ) {

			update_usermeta( $user->ID, 'default_destination', 'edit' );

		}

		add_action( 'activated_plugin', array( 'destination_after_save', 'POST_ACTIVATION_REDIRECT' ) );

	}

	function post_activation_redirect() {

		wp_safe_redirect( site_url() . '/wp-admin/profile.php#destination-after-save' );

		exit;

	}

	function determine_screens() {

		$screens = array( 'page', 'post' );

		$custom_post_types = get_post_types( array( '_builtin' => false ) );

		if( ! empty( $custom_post_types ) ) {

			foreach( $custom_post_types as $custom_post_type ) {

				$screens[] = $custom_post_type;

			}

		}

		return $screens;

	}

	function register_meta_box() {

		$screens = $this->determine_screens();

		foreach ( $screens as $screen ) {

			add_meta_box( 'destination_page_meta_box', __( 'Destination After Save', 'destination_page_meta_box' ), array( $this, 'meta_box_content' ), $screen, 'side', 'core' );
		
		}

	}

	function meta_box_content( $post ) {

		$args = array(

			'sort_order' => 'ASC',

			'sort_column' => 'post_title',

			'hierarchical' => 0,

			'exclude' => $post->ID,

			'child_of' => 0,

			'parent' => -1,

			'offset' => 0,

			'post_type' => $post->post_type,
			
			'post_status' => 'publish,draft,future,private,pending',

		);

		if( 'page' == $post->post_type ) {

			$destinations = get_pages( $args );

		} else {

			$destinations = get_posts( $args );

		}

		$item_type = $post->post_type;

		if( 'post' == $item_type || 'page' == $item_type ) {

			$item_type = ucwords( $item_type );

		} else {

			$custom_post_type = get_post_type_object( $item_type );

			$item_type = $custom_post_type->labels->singular_name;

		}

		echo '<select style="margin-top: 10px; width: 100%;" name="destination-selection" id="destination-selection">';

			echo '<option value="' . $post->ID . '">Edit This ' . $item_type . '</option>';

			echo '<option value="view"' . selected( get_user_meta( get_current_user_id(), 'default_destination', true ), 'view', false ) . '>View This ' . $item_type . '</option>';

			echo '<option value="new">Add New ' . $item_type . '</option>';

			echo '<option value="list"' . selected( get_user_meta( get_current_user_id(), 'default_destination', true ), 'list', false ) . '>Return to List</option>';

			echo '<option value="separator" disabled>Edit Other ' . $item_type . '</option>';

			foreach( $destinations as $destination ) {

				echo '<option value="' . $destination->ID . '">' . $destination->post_title . '</option>';

			}

		echo '</select>';

	}

	function load_destination() {

		if( isset( $_POST['destination-selection'] ) && 'dopreview' != $_POST['wp-preview'] ) {

			global $post;

			if( 'view' == $_POST['destination-selection'] ) {

				wp_safe_redirect( site_url() . '/?p=' . $post->ID );

			} elseif( 'new' == $_POST['destination-selection'] ) {

				if( 'post' == $post->post_type ) {

					wp_safe_redirect( site_url() . '/wp-admin/post-new.php' );

				} else {

					wp_safe_redirect( site_url() . '/wp-admin/post-new.php?post_type=' . $post->post_type );

				}

			} elseif( 'list' == $_POST['destination-selection'] ) {

				if( 'post' == $post->post_type ) {

					wp_safe_redirect( site_url() . '/wp-admin/edit.php' );

				} else {

					wp_safe_redirect( site_url() . '/wp-admin/edit.php?post_type=' . $post->post_type );

				}

			} else {

				wp_safe_redirect( site_url() . '/wp-admin/post.php?post=' . $_POST['destination-selection'] . '&action=edit' );

			}

			exit;

		}

	}

	function add_user_option_default_behavior( $user ) {

		echo '<a name="destination-after-save"></a>';

		echo '<h3>Destination After Save</h3>';

		echo '<table class="form-table">';

			echo '<tr>';

				echo '<th><label for="default_destination">Default Destination</label></th>';

				echo '<td>';

					echo '<select name="default_destination" id="default_destination">';
							
						echo '<option value="edit"' . selected( get_user_meta( $user->ID, 'default_destination', true ), 'edit', false ) . '>Edit</option>';

						echo '<option value="view"' . selected( get_user_meta( $user->ID, 'default_destination', true ), 'view', false ) . '>View</option>';

						echo '<option value="list"' . selected( get_user_meta( $user->ID, 'default_destination', true ), 'list', false ) . '>Return to List</option>';

					echo '</select>';

					echo '<p class="description">Default WordPress behavior is Edit, and will reload the Edit UI.<br>View will redirect to the content on the front end.<br>Return to List will redirect to the list of all posts, pages, or custom post type.</p>';

				echo '</td>';

			echo '</tr>';

		echo '</table>';

	}

	function save_default_user_behavior( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {

			return false;

		}

		update_user_meta( $user_id, 'default_destination', $_POST['default_destination'] );

	}

	function plugin_row_meta_links( $links, $file ) {

		if ( strpos( $file, $this->plugin_slug . '.php' ) !== false ) {

			$new_links = array(

				'<a href="' . admin_url( 'profile.php#' . $this->plugin_slug ) . '">Settings</a>',

				'<a href="http://slimbobwp.com/wordpress-plugins/' . $this->plugin_slug . '/#comment" target="_blank">Request a feature</a>',

				'<a href="https://wordpress.org/support/plugin/' . $this->plugin_slug . '" target="_blank">Request support</a>',

				'<a href="https://wordpress.org/support/plugin/' . $this->plugin_slug . '" target="_blank">Report a bug</a>',

				'<a href="https://wordpress.org/support/view/plugin-reviews/' . $this->plugin_slug . '" target="_blank">Leave a review</a>',

				'<a href="http://slimbobwp.com/donate" target="_blank">Make a donation</a>',

			);
			
			$links = array_merge( $links, $new_links );

		}
		
		return $links;
		
	}

	function uninstall() {

		$all_users = get_users();

		foreach( $all_users as $user ) {

			delete_user_meta( $user->ID, 'default_destination' );

		}

	}

}

$plugin_slug = 'destination-after-save';

$destination_after_save = new destination_after_save( $plugin_slug );

?>