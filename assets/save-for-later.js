/**
 * WooCommerce: Save For Later
 */

var wc_wishlist_myaccount = jQuery('#wc_wishlist_myaccount');
var wc_wishlist_dock = jQuery('#wc_wishlist_dock');
var wc_wishlist_header = wc_wishlist_dock.find('.header');
var wc_wishlist_products = wc_wishlist_dock.find('.products');

// wait for dom to be ready
jQuery( document ).ready( function ($){

	if( wc_wishlist_settings.user_status ) {
		storage = $.localStorage.getItem( 'woocommerce_wishlist' );
		if( typeof storage == 'undefined' || storage == null || !storage ) {
			$.wc_wishlist( { do_action: "get" } );
		} else {
			$.wc_wishlist.template();
		}
	} else {
		$.wc_wishlist.template();
	}

	// animate the header showing
	wc_wishlist_header.on( 'woocommerce_wishlist_scripts_header', $.wc_wishlist.slide );
	wc_wishlist_header.on('woocommerce_wishlist_response', function(){alert(this)} );

	// setup styling for wishlist dock
	if( wc_wishlist_settings.css_colors_enabled == 'yes' ) {
		wc_wishlist_header.css({
			'border-top-color' : wc_wishlist_settings.css_colors.border,
			'background' : wc_wishlist_settings.css_colors.header_bg,
			'color' : wc_wishlist_settings.css_colors.header_text
		});
		wc_wishlist_products.css({
			'background' : wc_wishlist_settings.css_colors.background,
			'color' : wc_wishlist_settings.css_colors.text
		});
		wc_wishlist_dock.trigger('woocommerce_wishlist_scripts_css');
	}

	// enable the wishlist dock open events
	wc_wishlist_header.on( "click", function ( event ){
		event.preventDefault();
		if (wc_wishlist_products.is( ":visible" )){
			cssClass = 'closed';
			icon = '&#x2010;';
			text = wc_wishlist_settings.header_show;
			wc_wishlist_products.slideUp();
		} else {
			icon = '/';
			cssClass = 'open';
			text = wc_wishlist_settings.header_hide;
			wc_wishlist_products.slideDown();			 
		}
		// set the header display/data by visibility
		$(this).html( text ).attr({
			'data-icon' : $('<div/>').html( icon ).text(), // because jquery escapes ampersands
			'class' : 'header ' + cssClass
		});
	}).html( wc_wishlist_settings.header_show );

	// enable the wishlist dock products hover events
	$(document).on({ 
		mouseenter : function(){
			$(this).find('img.wp-post-image').css('opacity', 1);
			$(this).find('span.remove,span.add_to_cart').fadeIn();
		},
		mouseleave : function(){
			$(this).find('img.wp-post-image').css('opacity', .5);
			$(this).find('span.remove,span.add_to_cart').fadeOut();
		}
	}, '#wc_wishlist_dock .dock-items .product, #wc_wishlist_myaccount .products .product' );

	$(document).on({
		mouseenter : function() {
			$(this).find('.product_image_overlay.save_for_later').fadeIn();
		},
		mouseleave : function() {
			$(this).find('.product_image_overlay.save_for_later').fadeOut();
		}
	},'.product > a');

	// remove from wishlist
	$(document).on( "click", '#wc_wishlist_dock .dock-items .product > span.remove, #wc_wishlist_myaccount .products .product > span.remove', function ( event ){
		event.preventDefault();

		var data = {
			do_action: 'remove',
			product_id: $(this).parent().attr('data-id'), // wishlist params
		};

		$(this).parents('.product').fadeOut( 'slow' );

		$.wc_wishlist( data );

	});

	// open dock via external click events
	$('.wc_wishlist_open_dock').on( "click", function (event){
		event.preventDefault();
		wc_wishlist_header.trigger( "click" );
	});

	// add product to wishlist
	$('.save_for_later').on( "click", function ( event ){
		event.preventDefault();

		var data = {
			do_action: 'add',
			product_id: this.dataset.product_id, // product id
			form: $(this).parent('form').serialize() // get form current data
		};

		$.wc_wishlist( data, true );

	});

	/**
	 * AJAX add to cart request
	 * 
	 * @see .add_to_cart_button
	 */
	wc_wishlist_products.find('.add_to_cart').on( "click", function ( event ) {
		event.preventDefault();

		var _this = $(this);
		var _parent = _this.parent();
			
		if (!_parent.attr('data-id')) return true;
		
		_parent.removeClass('added');
		_parent.addClass('loading');
		
		var data = {
			action: 		'woocommerce_add_to_cart',
			product_id: 	_parent.attr('data-id'),
			security: 		woocommerce_params.add_to_cart_nonce
		};
		
		// Trigger event
		$('body').trigger('adding_to_cart');
		
		// Ajax action
		$.post( woocommerce_params.ajax_url, data, function(response) {
			
			var this_page = window.location.toString();
			
			this_page = this_page.replace( 'add-to-cart', 'added-to-cart' );
			
			_parent.removeClass('loading');

			// Get response
			data = $.parseJSON( response );
			
			if (data.error && data.product_url) {
				window.location = data.product_url;
				return;
			}
			
			fragments = data;

			// Block fragments class
			if (fragments) {
				$.each(fragments, function(key, value) {
					$(key).addClass('updating');
				});
			}
			
			// Block widgets and fragments
			$('.widget_shopping_cart, .shop_table.cart, .updating, .cart_totals').fadeTo('400', '0.6').block({message: null, overlayCSS: {background: 'transparent url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', opacity: 0.6 } } );
			
			// Changes button classes
			_parent.addClass('added');

			// Cart widget load
			if ($('.widget_shopping_cart').size()>0) {
				$('.widget_shopping_cart:eq(0)').load( this_page + ' .widget_shopping_cart:eq(0) > *', function() {

					// Replace fragments
					if (fragments) {
						$.each(fragments, function(key, value) {
							$(key).replaceWith(value);
						});
					}
					
					// Unblock
					$('.widget_shopping_cart, .updating').stop(true).css('opacity', '1').unblock();
					
					$('body').trigger('cart_widget_refreshed');
				} );
			} else {
				// Replace fragments
				if (fragments) {
					$.each(fragments, function(key, value) {
						$(key).replaceWith(value);
					});
				}
				
				// Unblock
				$('.widget_shopping_cart, .updating').stop(true).css('opacity', '1').unblock();
			}
			
			// Cart page elements
			$('.shop_table.cart').load( this_page + ' .shop_table.cart:eq(0) > *', function() {
				
				$("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" id="add1" class="plus" />').prepend('<input type="button" value="-" id="minus1" class="minus" />');
				
				$('.shop_table.cart').stop(true).css('opacity', '1').unblock();
				
				$('body').trigger('cart_page_refreshed');
			});
			
			$('.cart_totals').load( this_page + ' .cart_totals:eq(0) > *', function() {
				$('.cart_totals').stop(true).css('opacity', '1').unblock();
			});
			
			// Trigger event so themes can refresh other areas
			$('body').trigger('added_to_cart');

			// clean up localStorage
			if( $.wc_wishlist.plugin.product_remove( _parent.attr('data-id') ) ) {
				$.wc_wishlist.template();
			}


		});
	});

	// look into merging when a user creates a new account/login
	// @link https://github.com/codearachnid/wc-save-for-later/issues/1
	// $('.my_account').on( 'click', function( event ){
	// 	if( $(this).hasClass('create') ) {
	// 		$.wc_wishlist.storage.add( 'do_merge', '1' );
	// 	} else {
	// 		$.wc_wishlist.storage.remove( 'do_merge' );
	// 	}
	// });

	wc_wishlist_dock.trigger('woocommerce_wishlist_scripts');
	wc_wishlist_header.trigger('woocommerce_wishlist_scripts_header');
	wc_wishlist_products.trigger('woocommerce_wishlist_scripts_products');

});

; (function ( $, document, undefined ) {
	
	var s_default = {
			user_status : false,
			products : [],
			wishlist : null
		},
		s_key = 'woocommerce_wishlist',
		settings = wc_wishlist_settings,
		storage = null;

	$.wc_wishlist = function( data, is_open ){
		return $.wc_wishlist.plugin.manage( data, is_open );
	}
	
	$.wc_wishlist.slide = function(){
		jQuery(this).delay(1000).slideDown();
	}

	$.wc_wishlist.template = function(){
		return $.wc_wishlist.plugin.template();
	}

	$.wc_wishlist.storage = function(){
		return $.wc_wishlist.plugin.get();
	}

	$.wc_wishlist.storage.add = function( key, data ){
		$.wc_wishlist.plugin.add( key, data );
	}

	$.wc_wishlist.storage.remove = function( key ){
		$.wc_wishlist.plugin.remove( key );
	}

	$.wc_wishlist.storage.exists = function( key ){
		return $.wc_wishlist.plugin.exists( key );
	}

	$.wc_wishlist.plugin = {
		manage: function( data, is_open ){
			_this = this;
			_this.get();
			is_open = typeof is_open !== 'undefined' ? is_open : false;
			data = jQuery.extend( { do_ajax: false, do_action: 'get' }, data );
			$(_this).on( 'woocommerce_wishlist_response', _this.template );
			// user logged in or force do_ajax
			if( settings.user_status || data.do_ajax ) {
				data.action = 'woocommerce_wishlist'; //_' + data.do_action;
				jQuery.post( settings.ajaxurl, data ).done( function( response ) {
					data = jQuery.parseJSON( response );
					// @link https://github.com/codearachnid/wc-save-for-later/issues/1
					// if( _this.exists( storage.do_merge) ) {
					// 	storage.products = jQuery.extend( storage.products, data.products );
					// 	storage.wishlist = jQuery.extend( storage.wishlist, data.wishlist );
					// 	storage.user_status = jQuery.extend( storage.user_status, settings.user_status );
					// } else {
						storage = { products: data.products, wishlist: data.wishlist, user_status: settings.user_status };
					// }
					// _this.remove( 'do_merge' );
					_this.save();
					if (is_open == true && data.status == 'success' && ! wc_wishlist_products.is( ":visible" ) ){
						wc_wishlist_header.trigger('click');
					}
					$(_this).trigger('woocommerce_wishlist_response');
				});
			} else {
				// no user information == local store only
				switch( data.do_action ) {
					case 'remove':
						product_status = _this.product_remove( data.product_id );
						if( product_status !== false ){
							$(_this).trigger( 'woocommerce_wishlist_response' );
						}
						break;
					case 'add':
						if( _this.product_exists( data.product_id ) === false ) {
							jQuery.post( settings.ajaxurl, { 
								action: 'woocommerce_wishlist', 
								do_action: 'lookup',
								product_id: data.product_id
							}).done( function( response ) {
								data = jQuery.parseJSON( response );
								if( data.status == 'success' && data.product.ID != '' ){
									storage.products.unshift( data.product );
									_this.save();
								}
								if (is_open == true && data.status == 'success' && ! wc_wishlist_products.is( ":visible" ) ){
									wc_wishlist_header.trigger('click');
								}
								$(_this).trigger('woocommerce_wishlist_response');
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
		product_remove: function ( product_id ){
			product_id = this.product_exists( product_id );
			if( product_id !== false ){
				storage.products.splice( product_id, 1 );
				this.save();
				return true;
			} else {
				return false;
			}
		},
		template: function(){
			_this = this;
			_this.get();
			no_products = true;
			if( settings.user_status == storage.user_status ) {
				wishlist = '';
				jQuery.each( storage.products, function( i, product ){
					wishlist += _this.format( settings.template.product, product.permalink, product.ID, product.thumbnail );
					no_products = false;
				});
				wishlist = wishlist.length == 0 ? settings.template.not_found : wishlist;
			} else {
				jQuery.localStorage.removeItem( s_key );
				wishlist = settings.template.not_found;
			}
			if( wc_wishlist_myaccount.length > 0 ) {
				wc_wishlist_myaccount.find('.products').html( wishlist );
			}
			wc_wishlist_products.trigger('woocommerce_wishlist_product_template').find('.dock-items').html( wishlist );
			if ( wc_wishlist_products.not( ":visible" ) && no_products === false ){
				$('#wc_wishlist_notice').show();
			} else if( no_products ) {
				$('#wc_wishlist_notice').hide();
			}
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
