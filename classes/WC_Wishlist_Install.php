<?php

if ( !class_exists( 'WC_Wishlist_Install' ) ) {
	class WC_Wishlist_Install {

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
			WC_Wishlist_Query::create_tables();
			foreach( WC_Wishlist_Settings::get_defaults() as $key => $default ){
				update_option( 'woocommerce_wishlist_' . $key, $default );	
			}
			self::init_user_roles();
			// Update version
			update_option( 'woocommerce_wishlist_db_version', WC_SaveForLater::instance()->version );
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
