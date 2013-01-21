<?php

if ( !class_exists( 'SFL_Wishlist_Meta' ) ) {
	class SFL_Wishlist_Meta {

		const META_TABLE = 'woocommerce_sfl_meta';
		const JOIN_KEY = 'woocommerce_sfl_product_id';

		function get( $wishlist_post_id, $product_id = null, $meta_key = null ) {

			$wishlist_post_id = !empty( $wishlist_post_id->ID ) ? $wishlist_post_id->ID : absint( $wishlist_post_id );

			global $wpdb;

			$meta_db_table = $wpdb->prefix . self::META_TABLE;

			$meta_key = stripslashes( $meta_key );

			$AND_product_id = self::prepare_and_key_value( 'product_id', $product_id, '%d' );
			$AND_meta_key = self::prepare_and_key_value( 'meta_key', $meta_key );

			$meta = $wpdb->get_results( $wpdb->prepare(
						"SELECT * FROM $meta_db_table 
						WHERE wishlist_post_id = %d 
						$AND_product_id 
						$AND_meta_key;",
						$wishlist_post_id ) );

			return $meta;

		}

		function if_exists( $wishlist_post_id, $product_id = null, $meta_key = null, $meta_value = null ) {

			$wishlist_post_id = !empty( $wishlist_post_id->ID ) ? absint( $wishlist_post_id->ID ) : absint( $wishlist_post_id );

			global $wpdb;

			$meta_db_table = $wpdb->prefix . self::META_TABLE;

			// expected_slashed ($meta_key)
			$meta_key = stripslashes( $meta_key );
			$meta_value = stripslashes_deep( $meta_value );
			$meta_value = sanitize_meta( $meta_key, $meta_value, WooCommerce_SaveForLater::POST_TYPE );

			$AND_product_id = self::prepare_and_key_value( 'product_id', $product_id, '%d' );
			$AND_meta_key = self::prepare_and_key_value( 'meta_key', $meta_key );
			$AND_meta_value = self::prepare_and_key_value( 'meta_value', $meta_value );

			if ( $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM $meta_db_table 
						WHERE wishlist_post_id = %d 
						$AND_product_id 
						$AND_meta_key
						$AND_meta_value;",
						$wishlist_post_id ) ) )
				return true;

			return false;
		}

		function add( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {

			$wishlist_post_id = !empty( $wishlist_post_id->ID ) ? $wishlist_post_id->ID : absint( $wishlist_post_id );

			if ( !$meta_key ||
				!$wishlist_post_id ||
				!$product_id = absint( $product_id ) )
				return false;

			global $wpdb;

			$meta_db_table = $wpdb->prefix . self::META_TABLE;

			// expected_slashed ($meta_key)
			$meta_key = stripslashes( $meta_key );
			$meta_value = stripslashes_deep( $meta_value );
			$meta_value = sanitize_meta( $meta_key, $meta_value, WooCommerce_SaveForLater::POST_TYPE );

			$check = apply_filters( 'woocommerce_sfl_add_meta', null, $wishlist_post_id, $product_id, $meta_key, $meta_value );

			if ( null !== $check )
				return $check;

			// TODO: Look into setting uniques
			// if ( $unique && $wpdb->get_var( $wpdb->prepare(
			//  "SELECT COUNT(*) FROM $meta_db_table WHERE meta_key = %s AND $column = %d",
			//  $meta_key, $object_id ) ) )
			//  return false;

			if ( self::if_exists( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique ) ) {
				return false;
			}

			$_meta_value = $meta_value;
			$meta_value = maybe_serialize( $meta_value );

			do_action( 'woocommerce_sfl_add_meta', $wishlist_post_id, $product_id, $meta_key, $meta_value );

			$result = $wpdb->insert(
				$meta_db_table,
				array(
					'wishlist_post_id' => $wishlist_post_id,
					'product_id' => $product_id,
					'meta_key' => $meta_key,
					'meta_value' => $meta_value
				),
				array(
					'%d',
					'%d',
					'%s',
					'%s'
				)
			);

			if ( ! $result )
				return false;

			$mid = (int) $wpdb->insert_id;

			// TODO: Look into caching performance
			// wp_cache_delete($object_id, WooCommerce_SaveForLater::POST_TYPE . '_meta');

			do_action( 'woocommerce_sfl_added_meta', $wishlist_post_id, $product_id, $meta_key, $_meta_value );

			// returns false if the row could not be inserted
			return $mid;
		}

		function update( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {

			$wishlist_post_id = absint( $wishlist_post_id );

			if ( !$meta_key ||
				!$wishlist_post_id ||
				!$product_id = absint( $product_id ) )
				return false;

			global $wpdb;

			$meta_db_table = $wpdb->prefix . self::META_TABLE;

			$meta_key = stripslashes( $meta_key );
			$meta_value = stripslashes_deep( $meta_value );
			$meta_value = sanitize_meta( $meta_key, $meta_value, WooCommerce_SaveForLater::POST_TYPE );

			// is there any thing to update
			if ( !SFL_Wishlist_Meta::if_exists( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique ) ) {
				return false;
			}

			$result = $wpdb->update(
				$meta_db_table,
				array(
					'meta_value' => $meta_value
				),
				array(
					'wishlist_post_id' => $wishlist_post_id,
					'product_id' => $product_id,
					'meta_key' => $meta_key,
				),
				array(
					'%s'
				),
				array(
					'%d',
					'%d',
					'%s',
				)
			);

			if ( ! $result )
				return false;

			return $result;
		}

		function delete( $wishlist_post_id, $product_id = null, $meta_key = null, $meta_value = null ) {

			$wishlist_post_id = !empty( $wishlist_post_id->ID ) ? $wishlist_post_id->ID : absint( $wishlist_post_id );

			// if ( !$meta_key ||
			// 	!$wishlist_post_id ||
			// 	!$product_id = absint( $product_id ) )
			// 	return false;

			global $wpdb;

			$meta_db_table = $wpdb->prefix . self::META_TABLE;

			$meta_key = stripslashes( $meta_key );
			$meta_value = stripslashes_deep( $meta_value );
			$meta_value = sanitize_meta( $meta_key, $meta_value, WooCommerce_SaveForLater::POST_TYPE );

			// is there any meta to delete
			if ( !SFL_Wishlist_Meta::if_exists( $wishlist_post_id, $product_id, $meta_key, $meta_value ) ) {
				return false;
			}

			$AND_product_id = self::prepare_and_key_value( 'product_id', $product_id, '%d' );
			$AND_meta_key = self::prepare_and_key_value( 'meta_key', $meta_key );
			$AND_meta_value = self::prepare_and_key_value( 'meta_value', $meta_value );

			// delete the record
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $meta_db_table
					WHERE wishlist_post_id = %d
					$AND_product_id
					$AND_meta_key
					$AND_meta_value;",
					$wishlist_post_id
				)
			);

			if ( !$result )
				return false;

			return $result;
		}

		function prepare_and_key_value( $key, $value = null, $type = '%s' ){
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

			// wp_woocommerce_wishlistmeta table sql
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
