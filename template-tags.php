<?php

if ( !function_exists( 'woocommerce_sfl_add_wishlist_meta' ) ) {
	function woocommerce_sfl_add_wishlist_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {
		// make sure meta is added to the post, not a revision
		if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
			$wishlist_post_id = $the_post;
    
		return SFL_Wishlist_Meta::add( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique );
	}
}

if ( !function_exists( 'woocommerce_sfl_update_wishlist_meta' ) ) {
	function woocommerce_sfl_update_wishlist_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {
		// make sure meta is added to the post, not a revision
		if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
			$wishlist_post_id = $the_post;

		return SFL_Wishlist_Meta::update( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique );
	}
}

if ( !function_exists( 'woocommerce_sfl_delete_wishlist_meta' ) ) {
	function woocommerce_sfl_delete_wishlist_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {
		// make sure meta is added to the post, not a revision
		if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
			$wishlist_post_id = $the_post;

		return SFL_Wishlist_Meta::delete( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique );
	}
}

if ( !function_exists( 'woocommerce_sfl_add_wishlist_post' ) ) {
	function woocommerce_sfl_add_wishlist_post( ) {
		return WooCommerce_SaveForLater::create_wishlist();
	}
}

if ( !function_exists( 'woocommerce_sfl_count_user_posts_by_type' ) ) {
  function woocommerce_sfl_count_user_posts_by_type($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE) {
    global $wpdb;
    $where = get_posts_by_author_sql($post_type, TRUE, $userid);
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );
    return apply_filters('get_usernumposts', $count, $userid);
  }
}

if ( !function_exists( 'woocommerce_sfl_count_anon_posts_by_type' ) ) {
  function woocommerce_sfl_count_anon_posts_by_type($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE) {
    global $wpdb;
    $where = get_posts_by_author_sql($post_type, TRUE, $userid);
    $result = $wpdb->get_results( "SELECT ID FROM $wpdb->posts $where" );
    
    $wishlist_post_ids = array();
    $wishlists_anons = woocommerce_sfl_get_wishlists_anon();
    
//    echo '<pre>';
//      print_r($wishlists_anons);
//    echo '</pre>';
    
    foreach( $result as $post ){
      if( in_array($post->ID, $wishlists_anons) ){
        $wishlist_post_ids[] = $post->ID;
        break;
      }
    } 
    
    return count( $wishlist_post_ids );
  }
}

if ( !function_exists( 'woocommerce_sfl_count_wishlists_by_anon' ) ) {
  function woocommerce_sfl_count_wishlists_by_anon() {
    $wishlist_post_ids =  WooCommerce_SaveForLater::get_wishlists_anon();
    return count( $wishlist_post_ids );
  }
}

if ( !function_exists( 'woocommerce_sfl_get_wishlists_by_user' ) ) {
  function woocommerce_sfl_get_wishlists_by_user($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE, $limit = 1) {
    return WooCommerce_SaveForLater::get_wishlists($userid, $post_type, $limit);
  }
}

if ( !function_exists( 'woocommerce_sfl_get_wishlists_by_anon' ) ) {
  function woocommerce_sfl_get_wishlists_by_anon($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE, $limit = 1) {
    $wishlist_post_ids = array();
    $wishlist_post_ids_anon =  WooCommerce_SaveForLater::get_wishlists($userid, $post_type, $limit);
    $wishlists_anons = woocommerce_sfl_get_wishlists_anon();
    
    foreach( $wishlist_post_ids_anon as $post_id ){
      if( in_array($post_id, $wishlists_anons) ){
        $wishlist_post_ids[] = $post_id;
      }
    }
    
    return $wishlist_post_ids;
  }
}

if ( !function_exists( 'woocommerce_sfl_get_wishlists_anon' ) ) {
  function woocommerce_sfl_get_wishlists_anon() {
    $wishlist_post_ids = array();
    $wishlist_post_ids =  WooCommerce_SaveForLater::get_wishlists_anon();
    
    return $wishlist_post_ids;
  }
}
