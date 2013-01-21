<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

?>
<div id="wcsfl_banner" style="bottom: 0px;">
	<div class="header" class="closed" data-icon="&#x2010;">
	</div>
	<div class="products">
		<div class="wrapper">
			<div class="banner-meta">
				<?php do_action( 'woocommerce_sfl_banner_meta' ); ?>
			</div>
			<div class="banner-items">
			<?php

			wcsfl_display_banner_items( $wishlist, $wishlist_items );

			?>
			</div>
		</div>
	</div>
</div>
