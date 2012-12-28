<?php

if ( !function_exists( 'wcsvl_add_wishlist_meta' ) ) {
	function wcsvl_add_wishlist_meta( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique = false ) {
		// make sure meta is added to the post, not a revision
		if ( $the_post = wp_is_post_revision( $wishlist_post_id ) )
			$wishlist_post_id = $the_post;

		return SFL_Wishlist_Meta::add( $wishlist_post_id, $product_id, $meta_key, $meta_value, $unique );
	}
}
