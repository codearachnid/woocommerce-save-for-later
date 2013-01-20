<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

?>
<div id="wcsfl_banner" style="bottom: 0px;">
	<div class="header" class="closed" data-icon="&#xf148;">
	</div>
	<div class="products">
		<div class="wrapper">
			<div class="banner-meta">
				<h3><?php echo SFL_Wishlist_Settings::get_option( 'frontend_label' ); ?></h3>
				<?php do_action( 'woocommerce_sfl_banner_meta' ); ?>
			</div>
			<div class="banner-items">
			<?php

			if ( !empty( $wishlist_items ) ) {

			} else {
				printf( '<span>%s <a href="%s">%s</a></span',
					__( 'It seems you haven\'t added any items into your wishlist.', 'woocommerce_sfl' ),
					get_permalink( woocommerce_get_page_id( 'shop' ) ),
					__( 'Add some now.', 'woocommerce_sfl' )
				);
			}

			?>
			</div>
		</div>
	</div>
</div>
