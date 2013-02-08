/**
 * WooCommerce: Save For Later
 */

var woocommerce_wishlist_myaccount = jQuery('#woocommerce_wishlist_myaccount');
var woocommerce_wishlist_dock = jQuery('#woocommerce_wishlist_dock');
var woocommerce_wishlist_header = woocommerce_wishlist_dock.find('.header');
var woocommerce_wishlist_products = woocommerce_wishlist_dock.find('.products');

// wait for dom to be ready
jQuery(document).ready(function($){

	if( woocommerce_wishlist_settings.user_status ) {
		storage = $.localStorage.getItem( 'woocommerce_wishlist' );
		if( typeof storage == 'undefined' || storage == null || !storage ) {
			$.woocommerce_wishlist( { do_action: "get" } );
		} else {
			$.woocommerce_wishlist.template();
		}
	} else {
		$.woocommerce_wishlist.template();
	}

	// animate the header showing
	woocommerce_wishlist_header.on( 'woocommerce_wishlist_scripts_header', $.woocommerce_wishlist.slide );

	// setup styling for wishlist dock
	if( woocommerce_wishlist_settings.css_colors_enabled == 'yes' ) {
		woocommerce_wishlist_header.css({
			'border-top-color' : woocommerce_wishlist_settings.css_colors.border,
			'background' : woocommerce_wishlist_settings.css_colors.header_bg,
			'color' : woocommerce_wishlist_settings.css_colors.header_text
		});
		woocommerce_wishlist_products.css({
			'background' : woocommerce_wishlist_settings.css_colors.background,
			'color' : woocommerce_wishlist_settings.css_colors.text
		});
		woocommerce_wishlist_dock.trigger('woocommerce_wishlist_scripts_css');
	}

	// enable the wishlist dock open events
	woocommerce_wishlist_header.on( "click", function( event ){
		event.preventDefault();
		if (woocommerce_wishlist_products.is( ":visible" )){
			cssClass = 'closed';
			icon = '&#x2010;';
			text = woocommerce_wishlist_settings.header_show;
			woocommerce_wishlist_products.slideUp();
		} else {
			icon = '/';
			cssClass = 'open';
			text = woocommerce_wishlist_settings.header_hide;
			woocommerce_wishlist_products.slideDown();			 
		}
		// set the header display/data by visibility
		$(this).html( text ).attr({
			'data-icon' : $('<div/>').html( icon ).text(), // because jquery escapes ampersands
			'class' : 'header ' + cssClass
		});
	}).html( woocommerce_wishlist_settings.header_show );

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
	}, '#woocommerce_wishlist_dock .dock-items .product, #woocommerce_wishlist_myaccount .products .product' );

	$(document).on({
		mouseenter : function() {
			$(this).find('.product_image_overlay.save_for_later').fadeIn();
		},
		mouseleave : function() {
			$(this).find('.product_image_overlay.save_for_later').fadeOut();
		}
	},'.product > a');

	// remove from wishlist
	$(document).on( "click", '#woocommerce_wishlist_dock .dock-items .product > span.remove, #woocommerce_wishlist_myaccount .products .product > span.remove', function( event ){
		event.preventDefault();

		var data = {
			do_action: 'remove',
			product_id: $(this).attr('data-id'), // wishlist params
		};

		$(this).parents('.product').fadeOut( 'slow' );

		$.woocommerce_wishlist( data );

	});

	// add product to wishlist
	$('.save_for_later').on( "click", function( event ){
		event.preventDefault();

		var data = {
			do_action: 'add',
			wishlist: this.dataset, // wishlist params
			form: $(this).parent('form').serialize() // get form current data
		};

		$.woocommerce_wishlist( data, true );

	});

	// look into merging when a user creates a new account/login
	// @link https://github.com/codearachnid/woocommerce-save-for-later/issues/1
	// $('.my_account').on( 'click', function( event ){
	// 	if( $(this).hasClass('create') ) {
	// 		$.woocommerce_wishlist.storage.add( 'do_merge', '1' );
	// 	} else {
	// 		$.woocommerce_wishlist.storage.remove( 'do_merge' );
	// 	}
	// });

	woocommerce_wishlist_dock.trigger('woocommerce_wishlist_scripts');
	woocommerce_wishlist_header.trigger('woocommerce_wishlist_scripts_header');
	woocommerce_wishlist_products.trigger('woocommerce_wishlist_scripts_products');

});

; (function ( $, document, undefined ) {
	
	var s_default = {
			user_status : false,
			products : [],
			wishlist : null
		},
		s_key = 'woocommerce_wishlist',
		settings = woocommerce_wishlist_settings,
		storage = null;

	$.woocommerce_wishlist = function( data, is_open ){
		return $.woocommerce_wishlist.plugin.manage( data, is_open );
	}
	
	$.woocommerce_wishlist.slide = function(){
		jQuery(this).delay(1000).slideDown();
	}

	$.woocommerce_wishlist.template = function(){
		return $.woocommerce_wishlist.plugin.template();
	}

	$.woocommerce_wishlist.storage = function(){
		return $.woocommerce_wishlist.plugin.get();
	}

	$.woocommerce_wishlist.storage.add = function( key, data ){
		$.woocommerce_wishlist.plugin.add( key, data );
	}

	$.woocommerce_wishlist.storage.remove = function( key ){
		$.woocommerce_wishlist.plugin.remove( key );
	}

	$.woocommerce_wishlist.storage.exists = function( key ){
		return $.woocommerce_wishlist.plugin.exists( key );
	}

	$.woocommerce_wishlist.plugin = {
		manage: function( data, is_open ){
			_this = this;
			_this.get();
			is_open = typeof is_open !== 'undefined' ? is_open : false;
			data = jQuery.extend( { do_ajax: false, do_action: 'get' }, data );
			woocommerce_wishlist_dock.on( 'woocommerce_wishlist_response', jQuery.woocommerce_wishlist.template );
			// user logged in or force do_ajax
			if( settings.user_status || data.do_ajax ) {
				data.action = 'woocommerce_wishlist_' + data.do_action;
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
					if (is_open == true && data.status == 'success' && ! woocommerce_wishlist_products.is( ":visible" ) ){
						woocommerce_wishlist_header.trigger('click');
					}
					woocommerce_wishlist_dock.trigger('woocommerce_wishlist_response');
				});
			} else {
				// no user information == local store only
				switch( data.do_action ) {
					case 'remove':
						product_id = _this.product_exists( data.product_id );
						if( product_id !== false ){
							storage.products.splice( product_id, 1 );
							_this.save();
							woocommerce_wishlist_dock.trigger( 'woocommerce_wishlist_response' );
						}
						break;
					case 'add':
						if( _this.product_exists( data.wishlist.product_id ) === false ) {
							jQuery.post( settings.ajaxurl, { 
								action: 'woocommerce_wishlist_lookup', 
								wishlist: data.wishlist 
							}).done( function( response ) {
								data = jQuery.parseJSON( response );
								if( data.status == 'success' && data.product.ID != '' ){
									storage.products.unshift( data.product );
									_this.save();
								}
								if (is_open == true && data.status == 'success' && ! woocommerce_wishlist_products.is( ":visible" ) ){
									woocommerce_wishlist_header.trigger('click');
								}
								woocommerce_wishlist_dock.trigger('woocommerce_wishlist_response');
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
			if( woocommerce_wishlist_myaccount.length > 0 ) {
				woocommerce_wishlist_myaccount.find('.products').html( wishlist );
			}
			woocommerce_wishlist_products.find('.dock-items').html( wishlist );
			woocommerce_wishlist_products.trigger('woocommerce_wishlist_product_template');
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
