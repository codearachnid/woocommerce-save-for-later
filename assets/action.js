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
	wcsfl_header.on( "click", function( event ){
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

	// enable the wishlist banner products hover events
	$(document).on({ 
		mouseenter : function(){
			// mouseenter
			$(this).find('img.wp-post-image').css('opacity', 1);
			$(this).find('div,span').fadeIn();
		},
		mouseleave : function(){
			//mouseleave
			$(this).find('img.wp-post-image').css('opacity', .5);
			$(this).find('div,span').fadeOut();
		}
	}, '#wcsfl_banner .banner-items .product' );

	// remove from wishlist
	$(document).on( "click", '#wcsfl_banner .banner-items .product > span.remove', function( event ){
		event.preventDefault();

		var data = {
			action: 'woocommerce_sfl_remove_from_wishlist',
			product_id: $(this).attr('data-id'), // wishlist params
		};

		wcsfl_products.find('.banner-items').load( wcsfl_settings.ajaxurl, data );

	});

	$('.attachment-shop_catalog').hover(function(){
		$(this).next('img.wcsfl_prod_add').css('visibility', 'visible');
	},function(){
		$(this).next('img.wcsfl_prod_add').css('visibility', 'hidden');
	});
	$('img.wcsfl_prod_add').click(function( event ){
		e.preventDefault();
		alert('saving');
	});
  
	// call to wishlist genie
	$('.save_for_later').on( "click", function( event ){
		event.preventDefault();

		var data = {
			action: 'woocommerce_sfl_add_to_wishlist',
			wishlist: this.dataset, // wishlist params
			form: $(this).parent('form').serialize() // get form current data
		};

		wcsfl_products.find('.banner-items').load( wcsfl_settings.ajaxurl, data, function(response, status, xhr) {
			if (status == 'success' && ! wcsfl_products.is( ":visible" ) ){
				wcsfl_header.trigger('click');
			}
		});

	});

	});