<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

?>
<div id="woocommerce_wishlist_dock" style="bottom: 0px;">
	<div class="header" class="closed" data-icon="&#x2010;"></div>
	<div class="products">
		<div class="wrapper">
			<div class="dock-meta">
				<?php do_action( 'woocommerce_wishlist_dock_meta' ); ?>
			</div>
			<div class="dock-items"></div>
		</div>
	</div>
</div>