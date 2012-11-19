// WooCommerce: Save For Later
jQuery(document).ready(function($){
	$('.attachment-shop_catalog').hover(function(){
		$(this).next('img.wcsvl-save-for-later').css('visibility', 'visible');
	},function(){
		$(this).next('img.wcsvl-save-for-later').css('visibility', 'hidden');
	});
	$('img.wcsvl-save-for-later').click(function(e){
		e.preventDefault();
		alert('saving');
	});
});