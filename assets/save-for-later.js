/**
 * WooCommerce: Save For Later
 */

wcsfl_banner = jQuery('#wcsfl_banner');
wcsfl_header = wcsfl_banner.find('.header');
wcsfl_products = wcsfl_banner.find('.products');
wcsfl_storage = jQuery.localStorage( 'woocommerce_wishlist' );

// wait for dom to be ready
jQuery(document).ready(function($){

	if( wcsfl_settings.user_status ) {
		if( typeof wcsfl_storage == 'undefined' ) {
			$.post( wcsfl_settings.ajaxurl, { action: "woocommerce_sfl_get_wishlist" }).done( function( response ) {
				data = jQuery.parseJSON( response );
				wishlist = '';
				$.each( data.products, function( i, product ){
					wishlist += wcsfl_settings.template.product.wcsfl_format( product.permalink, product.thumbnail, product.ID );
				});
				wcsfl_products.find('.banner-items').html( wishlist );
			});	
		}
	}

// alert( 'user_status' + wcsfl_settings.user_status );
	// alert( $.localStorage( 'foo', {data:'bar'} ) );
	

	// animate the header showing
	wcsfl_header.on( 'wcsfl_scripts_header', wcsfl_header, wcsfl_delay_slide );

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
		wcsfl_banner.trigger('wcsfl_scripts_css');
	}

	// enable the wishlist banner open events
	wcsfl_header.on( "click", function( event ){
		event.preventDefault();
		if (wcsfl_products.is( ":visible" )){
			cssClass = 'closed';
			icon = '&#x2010;';
			text = wcsfl_settings.header_show;
			wcsfl_products.slideUp();
		} else {
			icon = '/';
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
			$(this).find('img.wp-post-image').css('opacity', 1);
			$(this).find('span.remove').fadeIn();
			// $(this).find('div,span').fadeIn();
		},
		mouseleave : function(){
			$(this).find('img.wp-post-image').css('opacity', .5);
			$(this).find('span.remove').fadeOut();
			// $(this).find('div,span').fadeOut();
		}
	}, '#wcsfl_banner .banner-items .product' );

	$(document).on({
		mouseenter : function() {
			$(this).find('.product_image_overlay.save_for_later').fadeIn();
		},
		mouseleave : function() {
			$(this).find('.product_image_overlay.save_for_later').fadeOut();
		}
	},'.product > a');

	// remove from wishlist
	$(document).on( "click", '#wcsfl_banner .banner-items .product > span.remove', function( event ){
		event.preventDefault();

		var data = {
			action: 'woocommerce_sfl_remove_from_wishlist',
			product_id: $(this).attr('data-id'), // wishlist params
		};

		wcsfl_products.find('.banner-items').load( wcsfl_settings.ajaxurl, data );

	});

	// add product to wishlist
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

	// foo = $.localStorage.getItem( 'foo' );
	// alert(foo.data);

	wcsfl_banner.trigger('wcsfl_scripts');
	wcsfl_header.trigger('wcsfl_scripts_header');
	wcsfl_products.trigger('wcsfl_scripts_products');

});

function wcsfl_delay_slide(){
	jQuery(this).delay(1000).slideDown();
}

String.prototype.wcsfl_format = function() {
    var formatted = this;
    for (var i = 0; i < arguments.length; i++) {
        var regexp = new RegExp('\\{'+i+'\\}', 'gi');
        formatted = formatted.replace(regexp, arguments[i]);
    }
    return formatted;
};
