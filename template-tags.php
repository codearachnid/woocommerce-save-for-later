<?php

function wcsfl_get_active_wishlist_by_user(){

	if ( ! get_current_user_id() ) {
		// if the user isn't logged in we want to use local storage
		return false;
	} else {
		$args = array(
			'post_type' => WooCommerce_SaveForLater::POST_TYPE,
			'posts_per_page' => 1,
			'orderby' => 'menu_order',
			'author' => get_current_user_id()
			);

		$get_wishlist = new WP_Query( $args );

		$wishlist = !empty($get_wishlist->posts) ? $get_wishlist->posts[0] : array();
		
		return apply_filters( 'woocommerce_sfl_get_active_wishlist_by_user', $wishlist);
	}
}

function wcsfl_get_wishlist_meta( $wishlist_id, $product_id = null, $meta_key = null ) {
	return apply_filters('woocommerce_sfl_get_wishlist_meta', SFL_Wishlist_Meta::get( $wishlist_id, $product_id, $meta_key ) );
}

function wcsfl_is_wishlist( $wishlist_id = null ){
	$status = !is_null($wishlist_id) && get_post_type( $wishlist_id ) == WooCommerce_SaveForLater::POST_TYPE ? true : false;
	return apply_filters( 'woocommerce_sfl_is_wishlist', $status);
}

function wcsfl_add_wishlist_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {
	// make sure meta is added to the post, not a revision
	// if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
	// 	$wishlist_post_id = $the_post;

	return apply_filters('woocommerce_sfl_add_wishlist_meta', SFL_Wishlist_Meta::add( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique ) );
}

function wcsfl_delete_wishlist_meta( $wishlist_post_id, $product_id = null, $meta_key = null, $meta_value = null ) {
	return apply_filters('woocommerce_sfl_delete_wishlist_meta', SFL_Wishlist_Meta::delete( $wishlist_post_id, $product_id, $meta_key, $meta_value ) );
}

if ( !function_exists( 'wcsfl_update_wishlist_meta' ) ) {
	function wcsfl_update_wishlist_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {
		// make sure meta is added to the post, not a revision
		if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
			$wishlist_post_id = $the_post;

		return SFL_Wishlist_Meta::update( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique );
	}
}

if ( !function_exists( 'wcsfl_delete_wishlist_meta' ) ) {
	function wcsfl_delete_wishlist_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {
		// make sure meta is added to the post, not a revision
		if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
			$wishlist_post_id = $the_post;

		return SFL_Wishlist_Meta::delete( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique );
	}
}


function wcsfl_create_wishlist( ) {
	return WooCommerce_SaveForLater::create_wishlist();
}

if ( !function_exists( 'wcsfl_count_user_posts_by_type' ) ) {
  function wcsfl_count_user_posts_by_type($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE) {
    global $wpdb;
    $where = get_posts_by_author_sql($post_type, TRUE, $userid);
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );
    return apply_filters('woocommerce_sfl_get_usernumposts', $count, $userid);
  }
}

if ( !function_exists( 'wcsfl_count_anon_posts_by_type' ) ) {
  function wcsfl_count_anon_posts_by_type($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE) {
    global $wpdb;
    $where = get_posts_by_author_sql($post_type, TRUE, $userid);
    $result = $wpdb->get_results( "SELECT ID FROM $wpdb->posts $where" );
    
    $wishlist_post_ids = array();
    $wishlists_anons = wcsfl_get_wishlists_anon();
    
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

if ( !function_exists( 'wcsfl_count_wishlists_by_anon' ) ) {
  function wcsfl_count_wishlists_by_anon() {
    $wishlist_post_ids =  WooCommerce_SaveForLater::get_wishlists_anon();
    return count( $wishlist_post_ids );
  }
}

if ( !function_exists( 'wcsfl_get_wishlists_by_user' ) ) {
  function wcsfl_get_wishlists_by_user($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE, $limit = 1) {
    return WooCommerce_SaveForLater::get_wishlists($userid, $post_type, $limit);
  }
}

if ( !function_exists( 'wcsfl_get_wishlists_by_anon' ) ) {
  function wcsfl_get_wishlists_by_anon($userid, $post_type = WooCommerce_SaveForLater::POST_TYPE, $limit = 1) {
    $wishlist_post_ids = array();
    $wishlist_post_ids_anon =  WooCommerce_SaveForLater::get_wishlists($userid, $post_type, $limit);
    $wishlists_anons = wcsfl_get_wishlists_anon();
    
    foreach( $wishlist_post_ids_anon as $post_id ){
      if( in_array($post_id, $wishlists_anons) ){
        $wishlist_post_ids[] = $post_id;
      }
    }
    
    return $wishlist_post_ids;
  }
}

if ( !function_exists( 'wcsfl_get_wishlists_anon' ) ) {
  function wcsfl_get_wishlists_anon() {
    $wishlist_post_ids = array();
    $wishlist_post_ids =  WooCommerce_SaveForLater::get_wishlists_anon();
    
    return $wishlist_post_ids;
  }
}

function wcsfl_display_dock_items( $wishlist = null, $wishlist_items = array() ){
	do_action( 'woocommerce_sfl_wishlist_dock_items_before', $wishlist );

	if ( !empty( $wishlist_items ) ) {
		foreach($wishlist_items as $wishlist_product ){

			do_action( 'woocommerce_sfl_wishlist_dock_item_before', $wishlist_product->product_id, $wishlist_product );

			printf('<a class="%s" href="%s">%s<span class="add_to_cart" data-icon="i" data-id="%s"></span><span class="remove" data-icon="x" data-id="%s"></span><div class="quick_view">%s</div></a>',
				'product',
				get_permalink( $wishlist_product->product_id ),
				get_the_post_thumbnail( $wishlist_product->product_id, 'shop_thumbnail' ),
				$wishlist_product->product_id,
				$wishlist_product->product_id,
				__('Quick View', 'woocommerce_sfl')
				);

			do_action( 'woocommerce_sfl_wishlist_dock_item_after', $wishlist_product->product_id, $wishlist_product );

		}
	} else {
		do_action( 'woocommerce_sfl_wishlist_dock_not_found', $wishlist );
	}

	do_action( 'woocommerce_sfl_wishlist_dock_items_after', $wishlist );
}
