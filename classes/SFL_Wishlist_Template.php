<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

if ( !class_exists( 'SFL_Wishlist_Template' ) ) {
	class SFL_Wishlist_Template {

		function banner_title(){
			printf('<h3 data-icon="j">%s</h3>',
				SFL_Wishlist_Settings::get_option( 'frontend_label' )
				);

			if( is_user_logged_in() ) {
				$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
				if ( $myaccount_page_id ) {
					printf('<a class="settings" href="%s" data-icon="(">%s</a>',
						get_permalink( $myaccount_page_id ),
						__('My Account', 'woocommerce_sfl')
						);
				}
				
			} else {
				printf('<a class="create_account" href="%s" data-icon="o">%s</a>',
					'#',
					__('Create an Account', 'woocommerce_sfl')
					);	
			}
		}

		function banner() {
			global $user_ID;
			$instance = WooCommerce_SaveForLater::instance();

			$wishlist = wcsfl_get_active_wishlist_by_user( $user_ID );

			// get only the active products in a wishlist
			$wishlist_items = wcsfl_get_wishlist_meta( $wishlist, null, 'quantity' );

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
