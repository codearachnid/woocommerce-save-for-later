/**
 * WooCommerce: Save For Later
 */

var wcsfl_myaccount = jQuery('#wcsfl_myaccount');
var wcsfl_dock = jQuery('#wcsfl_dock');
var wcsfl_header = wcsfl_dock.find('.header');
var wcsfl_products = wcsfl_dock.find('.products');

// wait for dom to be ready
jQuery(document).ready(function($){

	if( wcsfl_settings.user_status ) {
		storage = $.localStorage.getItem( 'woocommerce_wishlist' );
		if( typeof storage == 'undefined' || storage == null || !storage ) {
			$.wcsfl_wishlist( { do_action: "get" } );
		} else {
			$.wcsfl_wishlist.template();
		}
	} else {
		$.wcsfl_wishlist.template();
	}

	// animate the header showing
	wcsfl_header.on( 'wcsfl_scripts_header', $.wcsfl_wishlist.slide );

	// setup styling for wishlist dock
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
		wcsfl_dock.trigger('wcsfl_scripts_css');
	}

	// enable the wishlist dock open events
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

	// enable the wishlist dock products hover events
	$(document).on({ 
		mouseenter : function(){
			$(this).find('img.wp-post-image').css('opacity', 1);
			$(this).find('span.remove').fadeIn();
		},
		mouseleave : function(){
			$(this).find('img.wp-post-image').css('opacity', .5);
			$(this).find('span.remove').fadeOut();
		}
	}, '#wcsfl_dock .dock-items .product, #wcsfl_myaccount .products .product' );

	$(document).on({
		mouseenter : function() {
			$(this).find('.product_image_overlay.save_for_later').fadeIn();
		},
		mouseleave : function() {
			$(this).find('.product_image_overlay.save_for_later').fadeOut();
		}
	},'.product > a');

	// remove from wishlist
	$(document).on( "click", '#wcsfl_dock .dock-items .product > span.remove, #wcsfl_myaccount .products .product > span.remove', function( event ){
		event.preventDefault();

		var data = {
			do_action: 'remove',
			product_id: $(this).attr('data-id'), // wishlist params
		};

		$(this).parents('.product').fadeOut( 'slow' );

		$.wcsfl_wishlist( data );

	});

	// add product to wishlist
	$('.save_for_later').on( "click", function( event ){
		event.preventDefault();

		var data = {
			do_action: 'add',
			wishlist: this.dataset, // wishlist params
			form: $(this).parent('form').serialize() // get form current data
		};

		$.wcsfl_wishlist( data, true );

	});

	// look into merging when a user creates a new account/login
	// @link https://github.com/codearachnid/woocommerce-save-for-later/issues/1
	// $('.my_account').on( 'click', function( event ){
	// 	if( $(this).hasClass('create') ) {
	// 		$.wcsfl_wishlist.storage.add( 'do_merge', '1' );
	// 	} else {
	// 		$.wcsfl_wishlist.storage.remove( 'do_merge' );
	// 	}
	// });

	wcsfl_dock.trigger('wcsfl_scripts');
	wcsfl_header.trigger('wcsfl_scripts_header');
	wcsfl_products.trigger('wcsfl_scripts_products');

});

; (function ( $, document, undefined ) {
	
	var s_default = {
			user_status : false,
			products : [],
			wishlist : null
		},
		s_key = 'woocommerce_wishlist',
		settings = wcsfl_settings,
		storage = null;

	$.wcsfl_wishlist = function( data, is_open ){
		return $.wcsfl_wishlist.plugin.manage( data, is_open );
	}
	
	$.wcsfl_wishlist.slide = function(){
		jQuery(this).delay(1000).slideDown();
	}

	$.wcsfl_wishlist.template = function(){
		return $.wcsfl_wishlist.plugin.template();
	}

	$.wcsfl_wishlist.storage = function(){
		return $.wcsfl_wishlist.plugin.get();
	}

	$.wcsfl_wishlist.storage.add = function( key, data ){
		$.wcsfl_wishlist.plugin.add( key, data );
	}

	$.wcsfl_wishlist.storage.remove = function( key ){
		$.wcsfl_wishlist.plugin.remove( key );
	}

	$.wcsfl_wishlist.storage.exists = function( key ){
		return $.wcsfl_wishlist.plugin.exists( key );
	}

	$.wcsfl_wishlist.plugin = {
		manage: function( data, is_open ){
			_this = this;
			_this.get();
			is_open = typeof is_open !== 'undefined' ? is_open : false;
			data = jQuery.extend( { do_ajax: false, do_action: 'get' }, data );
			wcsfl_dock.on( 'wcsfl_response', jQuery.wcsfl_wishlist.template );
			// user logged in or force do_ajax
			if( settings.user_status || data.do_ajax ) {
				data.action = 'wcsfl_' + data.do_action;
				jQuery.post( settings.ajaxurl, data ).done( function( response ) {
					data = jQuery.parseJSON( response );
					// @link https://github.com/codearachnid/woocommerce-save-for-later/issues/1
					// if( _this.exists( storage.do_merge) ) {
					// 	storage.products = jQuery.extend( storage.products, data.products );
					// 	storage.wishlist = jQuery.extend( storage.wishlist, data.wishlist );
					// 	storage.user_status = jQuery.extend( storage.user_status, settings.user_status );
					// } else {
						storage = { products: data.products, wishlist: data.wishlist, user_status: settings.user_status };
					// }
					// _this.remove( 'do_merge' );
					_this.save();
					if (is_open == true && data.status == 'success' && ! wcsfl_products.is( ":visible" ) ){
						wcsfl_header.trigger('click');
					}
					wcsfl_dock.trigger('wcsfl_response');
				});
			} else {
				// no user information == local store only
				switch( data.do_action ) {
					case 'remove':
						product_id = _this.product_exists( data.product_id );
						if( product_id !== false ){
							storage.products.splice( product_id, 1 );
							_this.save();
							wcsfl_dock.trigger( 'wcsfl_response' );
						}
						break;
					case 'add':
						if( _this.product_exists( data.wishlist.product_id ) === false ) {
							jQuery.post( settings.ajaxurl, { 
								action: 'wcsfl_lookup', 
								wishlist: data.wishlist 
							}).done( function( response ) {
								data = jQuery.parseJSON( response );
								if( data.status == 'success' && data.product.ID != '' ){
									storage.products.unshift( data.product );
									_this.save();
								}
								if (is_open == true && data.status == 'success' && ! wcsfl_products.is( ":visible" ) ){
									wcsfl_header.trigger('click');
								}
								wcsfl_dock.trigger('wcsfl_response');
							});
						}
						break;
					default: break;
				}
			}
		},
		get: function(){
			storage = jQuery.extend( s_default, jQuery.localStorage.getItem( s_key ) );
			return storage;
		},
		add: function( key, data ){
			storage = this.get();
			storage[ key ] = data;
			this.save();
		},
		remove: function( key ){
			storage = this.get();
			delete storage[ key ];
			this.save();
		},
		exists: function( key ){
			storage = this.get();
			return storage.hasOwnProperty( key );
		},
		save: function(){
			jQuery.localStorage( s_key, storage );
		},
		product_exists: function( product_id ){
			this.get();
			key = false;
			jQuery.each( storage.products, function( i, product ){
				if( product_id == product.ID ) {
					key = i;
				}
			});
			return key;
		},
		template: function(){
			_this = this;
			_this.get();
			if( settings.user_status == storage.user_status ) {
				wishlist = '';
				jQuery.each( storage.products, function( i, product ){
					wishlist += _this.format( settings.template.product, product.permalink, product.thumbnail, product.ID );
				});
				wishlist = wishlist.length == 0 ? settings.template.not_found : wishlist;
			} else {
				jQuery.localStorage.removeItem( s_key );
				wishlist = settings.template.not_found;
			}
			if( wcsfl_myaccount.length > 0 ) {
				wcsfl_myaccount.find('.products').html( wishlist );
			}
			wcsfl_products.find('.dock-items').html( wishlist );
			wcsfl_products.trigger('wcsfl_product_template');
		},
		format: function(){
			var formatted = arguments[0];
			for (var i = 1; i < arguments.length; i++) {
			    var regexp = new RegExp('\\{'+ ( i -1 ) +'\\}', 'gi');
			    formatted = formatted.replace(regexp, arguments[i]);
			}
			return formatted;
		}
	}

})(jQuery, document);
