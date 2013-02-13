<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

if ( !class_exists( 'woocommerce_Wishlist_Template' ) ) {
	class WC_Wishlist_Template {

		public static function checkout_notice() {
			$html = sprintf( '<div id="wc_wishlist_notice">%s <span class="wc_wishlist_open_dock">%s</span></div>',
				__( 'Would you like to add items from your wishlist today?', 'woocommerce_wishlist' ),
				__( 'Show Wishlist', 'woocommerce_wishlist' )
			);
			echo apply_filters( 'woocommerce_wishlist_notice', $html );
		}

		public static function my_account_dashboard() {
			$html = sprintf( '<div id="wc_wishlist_myaccount"><h2>%s</h2><p>%s</p><div class="products">',
				__( 'Wishlist', 'woocommerce_wishlist' ),
				__( 'These are items you have decided to save for later for easy access to add to your cart.', 'woocommerce_wishlist' )
			);

			// $wishlist = WC_wishlist_get_active_wishlist_by_user();

			// foreach( WC_wishlist_get_wishlist_meta( $wishlist, null, 'quantity' ) as $item ){
			//  $html .= apply_filters( 'woocommerce_wishlist_my_account_dashboard_product', sprintf( '<a class="product" href="%s">%s<span class="add_to_cart" data-icon="i" data-id="%s"></span><span class="remove" data-icon="x" data-id="%s"></span></a>',
			//   get_permalink( $item->product_id ),
			//   get_the_post_thumbnail( $item->product_id, 'shop_thumbnail' ),
			//   $item->product_id,
			//   $item->product_id
			//   ), $item );
			// }

			$html .= '</div></div>';

			echo apply_filters( 'woocommerce_wishlist_my_account_dashboard', $html );
		}

		public static function dock_product_template() {
			$html = '<a class="product" href="{0}" data-id="{1}">{2}<span class="add_to_cart" data-icon="i"></span><span class="remove" data-icon="x"></span></a>';
			return apply_filters( 'woocommerce_wishlist_dock_product_template', $html );
		}

		public static function product_image_overlay() {
			$overlay_image = sprintf( '<i data-icon="o" data-product_id="%s" class="product_image_overlay save_for_later">%s</i>',
				get_the_ID(),
				__( 'Save For Later', 'woocommerce_wishlist' )
			);
			echo apply_filters( 'woocommerce_wishlist_template_product_image_overlay', $overlay_image );
		}

		public static function product_button() {
			global $product;

			$button = sprintf( '<a class="save_for_later button product_type_%s" data-product_id="%s" rel="nofollow">%s</a>',
				$product->product_type,
				$product->id,
				__( 'Save for Later', 'woocommerce_wishlist' )
			);

			echo apply_filters( 'woocommerce_wishlist_template_product_button', $button, $product );
		}

		public static function dock_title() {
			$dock = sprintf( '<h3 data-icon="j">%s</h3>',
				WC_Wishlist_Settings::get_option( 'frontend_label' )
			);

			if ( is_user_logged_in() ) {
				$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
				$account_class = 'my_account';
				$account_icon = '(';
				$account_title = __( 'My Account', 'woocommerce_wishlist' );
			} else {
				$myaccount_page_id = ( get_option( 'woocommerce_enable_myaccount_registration' )=='yes' ) ? get_option( 'woocommerce_myaccount_page_id' ) : get_option( 'woocommerce_createaccount_page_id' );
				$account_permalink = '#';
				$account_class = 'my_account create';
				$account_icon = 'o';
				$account_title = __( 'Create an Account', 'woocommerce_wishlist' );
			}

			$account_permalink = ( $myaccount_page_id ) ? get_permalink( $myaccount_page_id ) : '';

			$dock .= apply_filters( 'woocommerce_wishlist_template_dock_account',
				!empty( $account_permalink ) ? sprintf( '<a href="%s" class="%s" data-icon="%s">%s</a>',
					$account_permalink,
					$account_class,
					$account_icon,
					$account_title
				) : '', is_user_logged_in(), $account_permalink, $account_class, $account_icon, $account_title );

			$cart_permalink = get_permalink( woocommerce_get_page_id( 'cart' ) );
			$cart_icon = 'i';
			$cart_text = __( 'View Cart', 'woocommerce_wishlist' );
			$dock .= apply_filters( 'woocommerce_wishlist_template_dock_cart', sprintf( '<a href="%s" class="view_cart" data-icon="%s">%s</a>',
					$cart_permalink,
					$cart_icon,
					$cart_text
				), $cart_permalink, $cart_icon, $cart_text );

			echo apply_filters( 'woocommerce_wishlist_template_dock_title', $dock );
		}

		public static function dock() {
			include apply_filters( 'woocommerce_wishlist_template_dock_file', WC_Wishlist::instance()->path . 'views/wishlist-dock.php' );
		}

		public static function register_form() {
			global $WC;
			include apply_filters( 'woocommerce_wishlist_template_register_form_file', WC_Wishlist::instance()->path . 'views/register-form.php' );
		}

		public static function not_found() {
			$message = sprintf( '<span>%s <a href="%s">%s</a></span',
				__( 'It seems you haven\'t added any items into your wishlist.', 'woocommerce_wishlist' ),
				get_permalink( woocommerce_get_page_id( 'shop' ) ),
				__( 'Add some now.', 'woocommerce_wishlist' )
			);
			return apply_filters( 'woocommerce_wishlist_template_not_found', $message );
		}
	}
}
