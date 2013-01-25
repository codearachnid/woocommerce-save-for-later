/**
 * WooCommerce: Save For Later
 */

var wcsfl_banner = jQuery('#wcsfl_banner');
var wcsfl_header = wcsfl_banner.find('.header');
var wcsfl_products = wcsfl_banner.find('.products');

// wait for dom to be ready
jQuery(document).ready(function($){

	if( wcsfl_settings.user_status ) {
		storage = $.localStorage.getItem( 'woocommerce_wishlist' );
		if( typeof storage == 'undefined' || storage == null || !storage ) {
			wcsfl_get_wishlist( { action: "woocommerce_sfl_get_wishlist" } );
		} else {
			wcsfl_product_template();
		}
	}

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
		},
		mouseleave : function(){
			$(this).find('img.wp-post-image').css('opacity', .5);
			$(this).find('span.remove').fadeOut();
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

		wcsfl_get_wishlist( data );

	});

	// add product to wishlist
	$('.save_for_later').on( "click", function( event ){
		event.preventDefault();

		var data = {
			action: 'woocommerce_sfl_add_to_wishlist',
			wishlist: this.dataset, // wishlist params
			form: $(this).parent('form').serialize() // get form current data
		};

		wcsfl_get_wishlist( data, true );

	});

	wcsfl_banner.trigger('wcsfl_scripts');
	wcsfl_header.trigger('wcsfl_scripts_header');
	wcsfl_products.trigger('wcsfl_scripts_products');

});

function wcsfl_delay_slide(){
	jQuery(this).delay(1000).slideDown();
}

function wcsfl_get_wishlist( data, is_open ){
	is_open = typeof is_open !== 'undefined' ? is_open : false;
	jQuery.post( wcsfl_settings.ajaxurl, data ).done( function( response ) {
		data = jQuery.parseJSON( response );
		storage = { products: data.products, wishlist: data.wishlist, user_status: wcsfl_settings.user_status };
		jQuery.localStorage( 'woocommerce_wishlist', storage );
		wcsfl_product_template();
		if (is_open == true && data.status == 'success' && ! wcsfl_products.is( ":visible" ) ){
			wcsfl_header.trigger('click');
		}
		wcsfl_banner.trigger('wcsfl_get_wishlist');
	});
}

function wcsfl_product_template(){
	storage = jQuery.localStorage.getItem( 'woocommerce_wishlist' );
	if( wcsfl_settings.user_status == storage.user_status ) {
		wishlist = '';
		jQuery.each( storage.products, function( i, product ){
			wishlist += wcsfl_settings.template.product.wcsfl_format( product.permalink, product.thumbnail, product.ID );
		});
		wishlist = wishlist.length == 0 ? wcsfl_settings.template.not_found : wishlist;
	} else {
		jQuery.localStorage.removeItem( 'woocommerce_wishlist' );
		wishlist = wcsfl_settings.template.not_found;
	}
	wcsfl_products.find('.banner-items').html( wishlist );
	wcsfl_products.trigger('wcsfl_product_template');
}

String.prototype.wcsfl_format = function() {
    var formatted = this;
    for (var i = 0; i < arguments.length; i++) {
        var regexp = new RegExp('\\{'+i+'\\}', 'gi');
        formatted = formatted.replace(regexp, arguments[i]);
    }
    return formatted;
};
