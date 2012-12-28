<?php

if ( !class_exists( 'SFL_Wishlist' ) ) {
	class SFL_Wishlist {
		function __construct() {
		}

		function save_post( $post_id, $post ){
			//verify post is not a revision & is a wishlist
			if ( $post->post_status != 'auto-draft' && WooCommerce_SaveForLater::POST_TYPE == $_REQUEST['post_type'] && ! wp_is_post_revision( $post_id ) ) {
				// unhook this function so it doesn't loop infinitely
				remove_action('save_post', array( 'SFL_Wishlist', 'save_post' ) );

				// hook into only 'publish' events
				if( isset($_REQUEST['publish']) ) {
					// update the post and change the post_name/slug to the post_title
					wp_update_post(array('ID' => $post_id, 'post_name' => self::generate_unique_slug() ));
				}

				//re-hook this function
				add_action('save_post', array( 'SFL_Wishlist', 'save_post' ) );
			}
		}

		function generate_unique_slug() {
			global $wpdb;
			$allowed_chars = apply_filters( WooCommerce_SaveForLater::DOMAIN . '_unique_slug_allowed_chars', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
			$url_length = get_option( WooCommerce_SaveForLater::DOMAIN . '_unique_url_length', 6 );
		    $unique_slug = '';
			for ($i=0; $i<$url_length; $i++) {
				$unique_slug .= substr($allowed_chars, rand(0, strlen($allowed_chars)), 1);
			}
			// check to see if this unique slug has been used before?
		    $unique_slug_check = $wpdb->get_row("SELECT ID FROM $wpdb->posts WHERE post_name = '$unique_slug';");
		    if ($unique_slug_check != null) {
				// try again
				return self::generate_unique_slug();
			} else {
				return $unique_slug;
			}
		}
	}
}