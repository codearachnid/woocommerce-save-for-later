<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

if ( !class_exists( 'SFL_Wishlist_Template' ) ) {
	class SFL_Wishlist_Template {

		public static function dock_product_template(){
			$html = sprintf( '<a class="product" href="{0}">{1}<span class="add_to_cart" data-icon="i" data-id="{2}"></span><span class="remove" data-icon="x" data-id="{2}"></span><div class="quick_view">%s</div></a>',
				__('Quick View', 'woocommerce_sfl')
				);
			return apply_filters( 'woocommerce_sfl_dock_product_template', $html );
		}

		public static function product_image_overlay() {
			$overlay_image = sprintf( '<i data-icon="o" data-product_id="%s" class="product_image_overlay save_for_later">%s</i>',
				get_the_ID(),
				__( 'Save For Later', 'woocommerce_sfl' )
			);
			echo apply_filters( 'woocommerce_sfl_template_product_image_overlay', $overlay_image );
		}

		public static function product_button() {
			global $product;

			$button = sprintf( '<a class="save_for_later button product_type_%s" data-product_id="%s" rel="nofollow">%s</a>',
				$product->product_type,
				$product->id,
				__( 'Save for Later', 'woocommerce_sfl' )
				);

			echo apply_filters( 'woocommerce_sfl_template_product_button', $button, $product );
		}

		public static function dock_title(){
			$dock = sprintf('<h3 data-icon="j">%s</h3>',
				SFL_Wishlist_Settings::get_option( 'frontend_label' )
				);

			if( is_user_logged_in() ) {
				$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
				if ( $myaccount_page_id ) {
					$dock .= sprintf('<a class="settings" href="%s" data-icon="(">%s</a>',
						get_permalink( $myaccount_page_id ),
						__('My Account', 'woocommerce_sfl')
						);
				}
				
			} else {
				$dock .= sprintf('<a class="create_account" href="%s" data-icon="o">%s</a>',
					'#',
					__('Create an Account', 'woocommerce_sfl')
					);	
			}

			echo apply_filters( 'woocommerce_sfl_template_dock_title', $dock );
		}

		public static function dock() {

			// $instance = WooCommerce_SaveForLater::instance();

			// $wishlist = wcsfl_get_active_wishlist_by_user();

			// get only the active products in a wishlist
			// $wishlist_items = wcsfl_get_wishlist_meta( $wishlist, null, 'quantity' );

			include apply_filters( 'woocommerce_sfl_template_dock_file', WooCommerce_SaveForLater::instance()->path . 'views/wishlist-dock.php' );
		}

		public static function not_found() {
			$message = sprintf( '<span>%s <a href="%s">%s</a></span',
				__( 'It seems you haven\'t added any items into your wishlist.', 'woocommerce_sfl' ),
				get_permalink( woocommerce_get_page_id( 'shop' ) ),
				__( 'Add some now.', 'woocommerce_sfl' )
				);
			return apply_filters( 'woocommerce_sfl_template_not_found', $message );
		}
	}
}
