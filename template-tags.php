<?php

if ( !function_exists( 'wcsvl_add_wishlist_meta' ) ) {
	function wcsvl_add_wishlist_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = false ) {
		// make sure meta is added to the post, not a revision
		if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
			$wishlist_post_id = $the_post;

		return SFL_Wishlist_Meta::add( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique );
	}
}

if ( !function_exists( 'wcsvl_add_wishlist_post' ) ) {
	function wcsvl_add_wishlist_post( ) {
		return WooCommerce_SaveForLater::create_wishlist();
	}
}

if ( !function_exists( 'wcsvl_count_user_posts_by_type' ) ) {
  function wcsvl_count_user_posts_by_type($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE) {
    global $wpdb;
    $where = get_posts_by_author_sql($post_type, TRUE, $userid);
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );
    return apply_filters('get_usernumposts', $count, $userid);
  }
}

if ( !function_exists( 'wcsvl_get_wishlists_by_user' ) ) {
  function wcsvl_get_wishlists_by_user($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE, $limit = 1) {
    return WooCommerce_SaveForLater::get_wishlists($userid, $post_type, $limit);
  }
}
