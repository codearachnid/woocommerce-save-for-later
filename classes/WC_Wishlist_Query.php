<?php

if ( !class_exists( 'WC_Wishlist_Query' ) ) {
	class WC_Wishlist_Query {

		const META_TABLE = 'woocommerce_wishlistmeta';
		const JOIN_KEY = 'woocommerce_wishlist_product_id';

		function get_wishlist_by_user( $user_id = null, $args = array() ){

			$user_id = is_null($user_id) ? get_current_user_id() : $user_id;

			$defaults = array(
				'query_type' => 'woocommerce_wishlist',
				'post_type' => WC_Wishlist::POST_TYPE,
				'posts_per_page' => 1,
				'orderby' => 'menu_order',
				'author' => $user_id
				);

			$args = wp_parse_args( $args, $defaults );

			$get_wishlist = new WP_Query( $args );

			$wishlist = array();
			foreach($get_wishlist->posts as $post){
				$wishlist[] = (object) apply_filters( 'woocommerce_wishlist_query_get_by_user_post', array(
					'ID' => $post->ID,
					'status' => $post->post_status,
					'order' => $post->menu_order
					), $post);
			}

			return $wishlist;
		}

		function get_products( $wishlist_post_id ) {
			global $wpdb;

			$meta_db_table = $wpdb->prefix . self::META_TABLE;

			$AND_meta_key = self::prepare_key_value( 'meta_key', 'added' );

			$product_ids = $wpdb->get_col( $wpdb->prepare(
						"SELECT product_id FROM $meta_db_table 
						WHERE wishlist_post_id = %d 
						$AND_meta_key
						ORDER BY meta_value DESC;",
						$wishlist_post_id ) );

			return apply_filters( 'woocommerce_wishlist_query_get_products', WC_Wishlist::get_wishlist_product( $product_ids ) );
		}

		function prepare_key_value( $key, $value = null, $type = '%s' ){
			global $wpdb;
			return !empty($value) ? $wpdb->prepare("AND $key = $type", $value ) : '';
		}

		function create_tables() {
			global $wpdb;

			$table_name = $wpdb->prefix . self::META_TABLE;
			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty( $wpdb->charset ) ) $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
				if ( ! empty( $wpdb->collate ) ) $collate .= " COLLATE $wpdb->collate";
			}

			// wp_WC_wishlistmeta table sql
			$sql = "CREATE TABLE {$table_name} (
				meta_id bigint(20) NOT NULL AUTO_INCREMENT,
				wishlist_post_id bigint(20) NOT NULL,
				product_id bigint(20) NOT NULL,
				meta_key varchar(255) NULL,
				meta_value longtext NULL,
				PRIMARY KEY  (meta_id)
				) {$collate}; ";

			// required for adding custom table
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}
}
