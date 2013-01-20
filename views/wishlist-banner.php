<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

?>
<div id="wcsfl_banner" style="bottom: 0px;">
	<div class="header" class="closed" data-icon="&#xf148;">
	</div>
	<div class="products">
		<div class="banner-meta">
			<h3><?php echo SFL_Wishlist_Settings::get_option('frontend_label'); ?></h3>
			<?php do_action( '' ); ?>
		</div>
	</div>
</div>