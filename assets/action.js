// WooCommerce: Save For Later
jQuery(document).ready(function($){
	wcsfl_banner = $('#wcsfl_banner');
	wcsfl_header = wcsfl_banner.find('.header');
	wcsfl_products = wcsfl_banner.find('.products');

	// animate the header showing
	wcsfl_header.delay(1000).slideDown();

	// setup styling for wishlist banner
	if( wcsfl_settings.css_colors_enabled == 'yes' ) {
		wcsfl_header.css({
			'border-top-color' : wcsfl_settings.css_colors.border,
			'background' : wcsfl_settings.css_colors.header_bg,
			'color' : wcsfl_settings.css_colors.header_text
		});
		wcsfl_products.css({
			'background' : wcsfl_settings.css_colors.background,
			'color' : wcsfl_settings.css_colors.text
		});
	}

	// enable the wishlist banner open events
	wcsfl_header.click(function( event ){
		event.preventDefault();
		if (wcsfl_products.is( ":visible" )){
			cssClass = 'closed';
			icon = '&#xf148;';
			text = wcsfl_settings.header_show;
			wcsfl_products.slideUp();
		} else {
			icon = '&#xf149;';
			cssClass = 'open';
			text = wcsfl_settings.header_hide;
			wcsfl_products.slideDown();			 
		}
		// set the header display/data by visibility
		$(this).html( text ).attr({
			'data-icon' : $('<div/>').html( icon ).text(), // because jquery escapes ampersands
			'class' : 'header ' + cssClass
		});
	}).html( wcsfl_settings.header_show );

	$('.attachment-shop_catalog').hover(function(){
		$(this).next('img.wcsfl-save-for-later').css('visibility', 'visible');
	},function(){
		$(this).next('img.wcsfl-save-for-later').css('visibility', 'hidden');
	});
	$('img.wcsfl-save-for-later').click(function(e){
		e.preventDefault();
		alert('saving');
	});
  
 // call to wishlist genie
 $('.save_for_later').on('click', function(e){
   console.log("TEST:" + wcsfl_settings.test);
   // getting data- key/values for current element
   $dataset = this.dataset;
   // getting form current data
   $form = $(this).parent('form').serialize();
   // setting the data to be send as post
   var data = {
      action: 'into_wishlist',
      dataset: $dataset,
      form: $form
    };
    // calling the post
    $.post(wcsfl_settings.ajaxurl, data, function(response) {
      var is_json = true;
      try{
        response = $.parseJSON( response );
      }catch(err){
        is_json = false;
      }
      if(is_json){
        console.log(response.msg);
      }else{
        console.log("not json");
      }
    });
   return false;
 });

});