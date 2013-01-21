<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

if ( !class_exists( 'SFL_Wishlist_Template' ) ) {
	class SFL_Wishlist_Template {

		function banner_items() {
			
		}

		function banner() {
			global $user_ID;
			$instance = WooCommerce_SaveForLater::instance();

			$wishlist = wcsfl_get_active_wishlist_by_user( $user_ID );

			// get only the active products in a wishlist
			$wishlist_items = wcsfl_get_wishlist_meta( $wishlist, null, 'quantity' );

			// setup the wishlist not found message
			add_action( 'woocommerce_sfl_wishlist_banner_not_found', array( __CLASS__, 'not_found' ) );

			include apply_filters( 'woocommerce_sfl_wishlist_banner_file', $instance->path . 'views/wishlist-banner.php' );
		}

		function not_found() {
			printf( '<span>%s <a href="%s">%s</a></span',
				__( 'It seems you haven\'t added any items into your wishlist.', 'woocommerce_sfl' ),
				get_permalink( woocommerce_get_page_id( 'shop' ) ),
				__( 'Add some now.', 'woocommerce_sfl' )
			);
		}
	}
}
