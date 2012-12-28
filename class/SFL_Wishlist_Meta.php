<?php

if ( !class_exists( 'SFL_Wishlist_Meta' ) ) {
	class SFL_Wishlist_Meta {

		const TABLE_META = 'woocommerce_wishlistmeta';

		function get() {

		}
    
    function if_exists() {
      
		}
    
    function exists( $type, $user_ID ){
      switch($type){
        case 'anon':
          break;
        default:
          // @TODO: for now, its one user, one wishlist - later interface will be built to handle multiple wishlists
          $wishlist_count = wcsvl_count_user_posts_by_type( $user_ID );
          break;
      }
      return $wishlist_count;
    }
    
    
    function manager( $dataset, $form = array() ){
      global $user_ID;
      
      if ( !$product_id = absint($dataset['product_id']) ) return false;
      
      $anon = ( is_user_logged_in() ) ? '' : 'anon';
      
      $wishlist_count = SFL_Wishlist_Meta::exists($anon, $user_ID);
      
      if ( !$wishlist_count ){
        // create a wishlist for current user | anon
        $wishlist_post_id = wcsvl_add_wishlist_post();
      } else{
        // get the wishlists
        $wishlists_post_ids = wcsvl_get_wishlists_by_user( $user_ID );
        if(count ($wishlists_post_ids) ){
          $wishlist_post_id = $wishlists_post_ids[0]; // right now dealing only in one
        }
      }
      
      // now we have wishlist post id, now we need to add meta information related to current product
      if( count($form) ){
        foreach($form as $key => $value){
          // @TODO: need to add validation that there is only one entry per key|value pair per wishlist|product pair
          $mids[] = wcsvl_add_wishlist_meta($wishlist_post_id, $product_id, $key, $value);
        }
      }
            
      return $wishlist_post_id;
      
      return $product_id; // @TODO: change it to the desired value. its meta 
    }

		function add( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = false ) {
			if ( !$meta_key || 
				 !$wishlist_post_id = absint( $wishlist_post_id ) || 
				 !$product_id = absint( $product_id ) )
				return false;

			global $wpdb;

			$meta_db_table = $wpdb->prefix . self::TABLE_META;

				// expected_slashed ($meta_key)
			$meta_key = stripslashes( $meta_key );
			$meta_value = stripslashes_deep( $meta_value );
			$meta_value = sanitize_meta( $meta_key, $meta_value, WooCommerce_SaveForLater::POST_TYPE );

			$check = apply_filters( WooCommerce_SaveForLater::DOMAIN . '_add_meta', null, $wishlist_post_id, $product_id, $meta_key, $meta_value );

			if ( null !== $check )
				return $check;

			// TODO: Look into setting uniques
			// if ( $unique && $wpdb->get_var( $wpdb->prepare(
			//  "SELECT COUNT(*) FROM $meta_db_table WHERE meta_key = %s AND $column = %d",
			//  $meta_key, $object_id ) ) )
			//  return false;

			$_meta_value = $meta_value;
			$meta_value = maybe_serialize( $meta_value );

			do_action( WooCommerce_SaveForLater::DOMAIN . '_add_meta', $wishlist_post_id, $product_id, $meta_key, $_meta_value );

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

			do_action( WooCommerce_SaveForLater::DOMAIN . '_added_meta', $wishlist_post_id, $product_id, $meta_key, $_meta_value );

			// returns false if the row could not be inserted
			return $mid;
		}

		function update() {

		}

		function delete() {

		}

		function create_tables() {
			global $wpdb;

			$table_name = $wpdb->prefix . self::TABLE_META;
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
