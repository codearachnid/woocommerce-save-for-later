<?php

if( !class_exists('WC_Wishlist_Query_Meta')) {
	class WC_Wishlist_Query_Meta extends WC_Wishlist_Query {

		function get( $wishlist_post_id, $product_id = null, $meta_key = null ) {

			$wishlist_post_id = !empty( $wishlist_post_id->ID ) ? $wishlist_post_id->ID : absint( $wishlist_post_id );

			global $wpdb;

			$meta_db_table = $wpdb->prefix . parent::META_TABLE;

			$meta_key = stripslashes( $meta_key );

			$AND_product_id = parent::prepare_key_value( 'product_id', $product_id, '%d' );
			$AND_meta_key = parent::prepare_key_value( 'meta_key', $meta_key );

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

			$meta_db_table = $wpdb->prefix . parent::META_TABLE;

			// expected_slashed ($meta_key)
			$meta_key = stripslashes( $meta_key );
			$meta_value = stripslashes_deep( $meta_value );
			$meta_value = sanitize_meta( $meta_key, $meta_value, WC_Wishlist::POST_TYPE );

			$AND_product_id = parent::prepare_key_value( 'product_id', $product_id, '%d' );
			$AND_meta_key = parent::prepare_key_value( 'meta_key', $meta_key );
			$AND_meta_value = parent::prepare_key_value( 'meta_value', $meta_value );

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

		function meta_add( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {

			$wishlist_post_id = !empty( $wishlist_post_id->ID ) ? $wishlist_post_id->ID : absint( $wishlist_post_id );

			if ( !$meta_key ||
				!$wishlist_post_id ||
				!$product_id = absint( $product_id ) )
				return false;

			global $wpdb;

			$meta_db_table = $wpdb->prefix . parent::META_TABLE;

			// expected_slashed ($meta_key)
			$meta_key = stripslashes( $meta_key );
			$meta_value = stripslashes_deep( $meta_value );
			$meta_value = sanitize_meta( $meta_key, $meta_value, WC_Wishlist::POST_TYPE );

			$check = apply_filters( 'woocommerce_wishlist_add_meta_check', null, $wishlist_post_id, $product_id, $meta_key, $meta_value );

			if ( null !== $check )
				return $check;

			// TODO: Look into setting uniques
			if ( $unique && $wpdb->get_var( $wpdb->prepare(
			 "SELECT COUNT(*) FROM $meta_db_table WHERE meta_key = %s AND $column = %d",
			 $meta_key, $object_id ) ) )
			 return false;

			if ( self::meta_if_exists( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique ) ) {
				return false;
			}

			$_meta_value = $meta_value;
			$meta_value = maybe_serialize( $meta_value );

			do_action( 'woocommerce_wishlist_add_meta', $wishlist_post_id, $product_id, $meta_key, $meta_value );

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
			// wp_cache_delete($object_id, WC_Wishlist::POST_TYPE . '_meta');

			do_action( 'woocommerce_wishlist_added_meta', $wishlist_post_id, $product_id, $meta_key, $_meta_value );

			// returns false if the row could not be inserted
			return $mid;
		}

		function meta_update( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {

			$wishlist_post_id = absint( $wishlist_post_id );

			if ( !$meta_key ||
				!$wishlist_post_id ||
				!$product_id = absint( $product_id ) )
				return false;

			global $wpdb;

			$meta_db_table = $wpdb->prefix . parent::META_TABLE;

			$meta_key = stripslashes( $meta_key );
			$meta_value = stripslashes_deep( $meta_value );
			$meta_value = sanitize_meta( $meta_key, $meta_value, WC_Wishlist::POST_TYPE );

			// is there any thing to update
			if ( !self::if_exists( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique ) ) {
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

		function meta_delete( $wishlist_post_id, $product_id = null, $meta_key = null, $meta_value = null ) {

			$wishlist_post_id = !empty( $wishlist_post_id->ID ) ? $wishlist_post_id->ID : absint( $wishlist_post_id );

			// if ( !$meta_key ||
			// 	!$wishlist_post_id ||
			// 	!$product_id = absint( $product_id ) )
			// 	return false;

			global $wpdb;

			$meta_db_table = $wpdb->prefix . parent::META_TABLE;

			$meta_key = stripslashes( $meta_key );
			$meta_value = stripslashes_deep( $meta_value );
			$meta_value = sanitize_meta( $meta_key, $meta_value, WC_Wishlist::POST_TYPE );

			// is there any meta to delete
			if ( !self::if_exists( $wishlist_post_id, $product_id, $meta_key, $meta_value ) ) {
				return false;
			}

			$AND_product_id = self::prepare_key_value( 'product_id', $product_id, '%d' );
			$AND_meta_key = self::prepare_key_value( 'meta_key', $meta_key );
			$AND_meta_value = self::prepare_key_value( 'meta_value', $meta_value );

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

	}
}