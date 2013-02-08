<?php

if ( !class_exists( 'SFL_Wishlist_Install' ) ) {
	class SFL_Wishlist_Install {

		function __construct() {

		}
    
		function activate() {
			update_option( 'woocommerce_wishlist_installed', 1 );
			self::do_install();
		}

		function install_or_upgrade() {
			self::do_install();
		}

		function do_install() {
			SFL_Wishlist_Meta::create_tables();
			self::init_user_roles();
			// Update version
			update_option( 'woocommerce_wishlist_db_version', WooCommerce_SaveForLater::instance()->version );
			update_option( 'woocommerce_wishlist_unique_url_length', 6 );
			// create a page for user registration
			// woocommerce_create_page( esc_sql( _x('change-password', 'page_slug', 'woocommerce') ), 'woocommerce_change_password_page_id', __('Change Password', 'woocommerce'), '[woocommerce_change_password]', woocommerce_get_page_id('myaccount') );
			flush_rewrite_rules();
		}

		function init_user_roles() {
			global $wp_roles;

			if ( class_exists( 'WP_Roles' ) ) if ( ! isset( $wp_roles ) ) $wp_roles = new WP_Roles();

				if ( is_object( $wp_roles ) ) {
					// capabilities for admin
					$wp_roles->add_cap( 'administrator', 'woocommerce_wishlist_manage' );
				}
		}
	}
}
