<?php

function woocommerce_wishlist_get_active_wishlist() {

	if ( ! get_current_user_id() ) {
		// if the user isn't logged in we want to use local storage
		$return = false;
	} else {
		$return = WC_Wishlist_Query::get_wishlist_by_user();
		$return = count( $return ) > 0 ? $return[0] : false;
	}
	return apply_filters( 'woocommerce_wishlist_get_active_wishlist', $return );
}

function woocommerce_wishlist_get_meta( $wishlist_id, $product_id = null, $meta_key = null ) {
	return apply_filters( 'woocommerce_wishlist_get_meta', WC_Wishlist_Query_Meta::get( $wishlist_id, $product_id, $meta_key ) );
}

function woocommerce_wishlist_is_wishlist( $wishlist_id = null ) {
	$wishlist_id = !is_null( $wishlist_id ) ? $wishlist_id : get_the_ID();
	$status = get_post_type( $wishlist_id ) == WC_Wishlist::POST_TYPE ? true : false;
	return apply_filters( 'woocommerce_wishlist_is_wishlist', $status );
}

function woocommerce_wishlist_add_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {
	// make sure meta is added to the post, not a revision
	// if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
	//  $wishlist_post_id = $the_post;

	return apply_filters( 'woocommerce_wishlist_add_meta', WC_Wishlist_Query_Meta::add( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique ) );
}

function woocommerce_wishlist_delete_meta( $wishlist_post_id, $product_id = null, $meta_key = null, $meta_value = null ) {
	return apply_filters( 'woocommerce_wishlist_delete_meta', WC_Wishlist_Query_Meta::delete( $wishlist_post_id, $product_id, $meta_key, $meta_value ) );
}

if ( !function_exists( 'woocommerce_wishlist_update_meta' ) ) {
	function woocommerce_wishlist_update_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = true ) {
		// make sure meta is added to the post, not a revision
		// if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
		//  $wishlist_post_id = $the_post;

		return WC_Wishlist_Query_Meta::update( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique );
	}
}
