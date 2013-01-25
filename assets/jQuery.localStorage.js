; (function ( $, document, undefined ) {

	var supported;

	// if local storage optiosn are disabled in privacy settings on Firefox then trap
	try {
		supported = typeof window.localStorage == 'undefined' || typeof window.JSON == 'undefined' ? false : true;
	} catch( error ){}

	$.localStorage = function( key, value, options ){
		options = jQuery.extend({}, options);
		return $.localStorage.plugin.init(key, value);
	}

	$.localStorage.setItem = function( key, value ){
		return $.localStorage.plugin.setItem( key, value );
	}

	$.localStorage.getItem = function( key ){
		return $.localStorage.plugin.getItem( key );
	}

	$.localStorage.removeItem = function( key ){
		return $.localStorage.plugin.removeItem(key);
	}

	$.localStorage.plugin = {
		init: function( key, value ){
			if ( typeof value != 'undefined' ) {
				return this.setItem(key, value);	
			} else {
				return this.setItem(key);
			}
		},
		setItem: function( key, value ){
			var value = JSON.stringify( value );
			if ( !supported ){
				try {
					$.localStorage.cookie( key, value );
				} catch( e ){
					console.log('Local Storage not supported by this browser. Install the cookie plugin on your site to take advantage of the same functionality. You can get it at https://github.com/carhartl/jquery-cookie');
				}
			}
			window.localStorage.setItem( key, value );
			return this.result( value );
		},
		getItem: function( key ){
			if ( !supported ){
				try {
					return this.result( $.localStorage.cookie( key ) );
				} catch(e){
					return null;
				}
 			}
			return this.result( window.localStorage.getItem( key ) );
		},
		removeItem: function( key ){
			if ( !supported ){
				try {
					$.localStorage.cookie( key, null );
					return true;
				} catch( e ){
					return false;
				}
 			}
			window.localStorage.removeItem( key );
			return true;
		},
		result: function( res ){
			var ret;
			try {
				ret = JSON.parse( res );
				if (ret == 'true'){
					ret = true;
				}
				if (ret == 'false'){
					ret = false;
				}
				if ( parseFloat( ret ) == ret && typeof ret != "object" ){
					ret = parseFloat( ret );
				}
			} catch( e ){}
			return ret;
		}
	}

	$.localStorage.cookie = function ( key, value, options ) {

		// key and value given, set cookie
		if ( arguments.length > 1 && ( value === null || typeof value !== "object" ) ) {
			if ( value === null ) {
				options.expires = -1;
			}

			if ( typeof options.expires === 'number' ) {
				var days = options.expires, t = options.expires = new Date();
				t.setDate( t.getDate() + days );
			}

			return (document.cookie = [
				encodeURIComponent(key), '=',
				options.raw ? String(value) : encodeURIComponent(String(value)),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path ? '; path=' + options.path : '',
				options.domain ? '; domain=' + options.domain : '',
				options.secure ? '; secure' : ''
			].join(''));
		}

		// key and possibly options given, get cookie...
		options = value || {};
		var result,
			decode = options.raw ? function (s) { return s; } : decodeURIComponent;

		return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
	}

})(jQuery, document);