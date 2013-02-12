<?php

if ( !class_exists( 'WC_Wishlist_Query' ) ) {
	class WC_Wishlist_Query {

		const META_TABLE = 'woocommerce_wishlistmeta';
		const JOIN_KEY = 'woocommerce_wishlist_product_id';

		function get_wishlist_by_user( $user_id = null, $args = array() ) {

			$user_id = is_null( $user_id ) ? get_current_user_id() : $user_id;

			$defaults = array(
				'query_type' => 'woocommerce_wishlist',
				'query_action' => 'get_wishlist_by_user',
				'post_type' => WC_Wishlist::POST_TYPE,
				'posts_per_page' => 1,
				'orderby' => 'menu_order',
				'author' => $user_id
			);

			$args = wp_parse_args( $args, $defaults );

			$get_wishlist = new WP_Query( $args );

			$wishlist = array();
			foreach ( $get_wishlist->posts as $post ) {
				$wishlist[] = (object) apply_filters( 'woocommerce_wishlist_query_get_by_user_post', array(
						'ID' => $post->ID,
						'status' => $post->post_status,
						'order' => $post->menu_order
					), $post );
			}

			return $wishlist;
		}

		function create_wishlist() {
			global $user_ID, $current_user;

			get_currentuserinfo();

			$title = __( 'Wishlist created', 'woocommerce_wishlist' ) . ' ' . date( 'c' );
			$title = apply_filters( 'woocommerce_wishlist_query_create_wishlist_title', $title, $current_user );

			$post = array(
				'post_author'    => $user_ID,
				'post_category'  => array( 0 ),
				'post_status'    => 'publish',
				'post_title'     => $title,
				'post_type'      => WC_Wishlist::POST_TYPE,
				'post_name'   => self::unique_slug()
			);

			$post = apply_filters( 'woocommerce_wishlist_query_create_wishlist', $post, $current_user );

			return wp_insert_post( $post );
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

		function prepare_key_value( $key, $value = null, $type = '%s' ) {
			global $wpdb;
			return !empty( $value ) ? $wpdb->prepare( "AND $key = $type", $value ) : '';
		}

		/**
		 * Generate a unique slug verified by checking against the posts table for uniqueness
		 *
		 * @return string $unique_slug
		 */
		function unique_slug() {
			global $wpdb;

			$allowed_chars = apply_filters( 'woocommerce_wishlist_unique_slug_allowed_chars', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );
			$url_length = WC_Wishlist_Settings::get_option( 'unique_url_length' );
			$unique_slug = '';

			for ( $i=0; $i<$url_length; $i++ ) {
				$unique_slug .= substr( $allowed_chars, rand( 0, strlen( $allowed_chars ) ), 1 );

			}
			// check to see if this unique slug has been used before?
			if ( $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = '%s';", $unique_slug ) ) != null ) {

				// try generating again
				$unique_slug = self::generate_unique_slug();
			}

			return apply_filters( 'woocommerce_wishlist_generate_unique_slug', $unique_slug );
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
