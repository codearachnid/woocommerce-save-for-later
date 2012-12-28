<?php

if ( !class_exists( 'SFL_Wishlist_Meta' ) ) {
	class SFL_Wishlist_Meta {

		const TABLE_META = 'woocommerce_wishlistmeta';
		function __construct() {

		}

		function create_tables() {
			global $wpdb;

			require_once ABSPATH . 'wp-admin/includes/upgrade.php'; // for adding custom table

			$table = $wpdb->prefix . self::TABLE_META;

			$collate = '';
			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty( $wpdb->charset ) ) $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
				if ( ! empty( $wpdb->collate ) ) $collate .= " COLLATE $wpdb->collate";
			}

			// WooWishList meta table
			$sql = "CREATE TABLE {$table} (
	        meta_id bigint(20) NOT NULL AUTO_INCREMENT,
	        wishlist_post_id bigint(20) NOT NULL,
	        product_id bigint(20) NOT NULL,
	        meta_key varchar(255) NULL,
	        meta_value longtext NULL,
	        PRIMARY KEY  (meta_id)
	      ) {$collate};
	      ";
			dbDelta( $sql );
		}
	}
}
