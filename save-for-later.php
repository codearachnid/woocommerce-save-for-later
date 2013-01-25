<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

if ( ! class_exists( 'WooCommerce_SaveForLater' ) ) {
	class WooCommerce_SaveForLater {

		protected static $instance;

		private $default_ajax_response = array(
			'msg' => null,
			'status' => false,
			'code' => null,
			'wishlist' => array(),
			'products' => array()
			);

		public $path;
		public $dir;
		public $url;
		public $version = '1.0';

		const PLUGIN_NAME = 'WooCommerce: Save For Later';
		const MIN_WC_VERSION = '1.6.5';
		const MIN_WP_VERSION = '3.4';
		const MIN_PHP_VERSION = '5.3';
		const POST_TYPE = 'woocommerce_wishlist';

		function __construct() {

			// register lazy autoloading
			spl_autoload_register( 'self::lazy_loader' );

			$this->check_install();

			// enable the settings
			if ( is_admin() )
				new SFL_Wishlist_Settings();

			// set core vars
			$this->path = self::get_plugin_path();
			$this->dir = trailingslashit( basename( $this->path ) );
			$this->url = plugins_url() . '/' . $this->dir;
			$this->base_slug = apply_filters( 'woocommerce_sfl_base_slug', 'wishlist' );

			// core plugin items
			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );

			// ajax handlers
			add_action( 'wp_ajax_woocommerce_sfl_get_wishlist', array( $this, 'ajax_get_wishlist' ) ); // authenticated users
			add_action( 'wp_ajax_nopriv_woocommerce_sfl_get_wishlist', array( $this, 'ajax_get_wishlist' ) ); // authenticated users
			add_action( 'wp_ajax_woocommerce_sfl_add_to_wishlist', array( $this, 'ajax_add_to_wishlist' ) ); // authenticated users
			add_action( 'wp_ajax_nopriv_woocommerce_sfl_add_to_wishlist', array( $this, 'ajax_add_to_wishlist' ) ); // anon users
			add_action( 'wp_ajax_woocommerce_sfl_remove_from_wishlist', array( $this, 'ajax_remove_from_wishlist' ) ); // authenticated users
			add_action( 'wp_ajax_nopriv_woocommerce_sfl_remove_from_wishlist', array( $this, 'ajax_remove_from_wishlist' ) ); // anon users

			// templating
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 100 );
			add_action( 'wp_footer', array( $this, 'wp_footer' ) );
			add_action( 'woocommerce_sfl_banner_meta', array( 'SFL_Wishlist_Template', 'banner_title'));

			// hook into woocommerce for templating
			add_action( 'woocommerce_before_shop_loop_item_title', array( 'SFL_Wishlist_Template', 'product_image_overlay' ), 20 );
			add_action( 'woocommerce_after_shop_loop_item', array( 'SFL_Wishlist_Template', 'product_button' ), 20 ); // link on product collections page
			add_action( 'woocommerce_after_add_to_cart_button', array( 'SFL_Wishlist_Template', 'product_button' ), 20 ); // link on product single page
		}

		function check_install() {
			register_activation_hook( __FILE__, array( 'SFL_Wishlist_Install', 'activate' ) );
			register_activation_hook( __FILE__, array( 'SFL_Wishlist_Install', 'flush_rewrite_rules' ) );
			if ( is_admin() && get_option( 'woocommerce_sfl_db_version' ) != $this->version )
				add_action( 'init', array( 'SFL_Wishlist_Install', 'install_or_upgrade' ), 1 );
		}

		function ajax_get_wishlist() {

			$wishlist = wcsfl_get_active_wishlist_by_user();

			// get only the active products in a wishlist
			$wishlist_items = array();
			foreach( wcsfl_get_wishlist_meta( $wishlist, null, 'quantity' ) as $item ){
				$wishlist_items[] = $this->get_wishlist_product( $item->product_id );
			}

			// wcsfl_display_banner_items( $wishlist, $wishlist_items );
			$response = array(
				'status' => 'success',
				'code' => '100',
				'wishlist' => $wishlist,
				'products' => $wishlist_items
				);

			$response = wp_parse_args( $response, $this->default_ajax_response );
			echo json_encode( $response );

			die();
		}

		function ajax_remove_from_wishlist(){

			// will return anon wishlists if userid isn't known
			// if the wishlist is provided and not a legit wishlist 
			// then we try to get the active wishlist
			$wishlist_id = ! empty($wishlist['wishlist_id']) && wcsfl_is_wishlist( $wishlist_id ) ? $wishlist['wishlist_id'] : wcsfl_get_active_wishlist_by_user();

			// forward request to meta manager with the data and wait for its response
			if ( !empty( $_REQUEST ) && !empty( $wishlist_id ) ) {
				extract( $_REQUEST );

				if( wcsfl_delete_wishlist_meta( $wishlist_id, $product_id ) ){

					$this->ajax_get_wishlist();

				} else {
					$response = array(
						'status'=>'error',
						'msg' => __('The request to remove the product from the wishlist failed.' )
						);
				}

			} else {
				$response = array(
					'status'=>'error',
					'msg' => __('The AJAX request is improperly formatted.')
					);
			}

			$response = wp_parse_args( $response, $this->default_ajax_response );
			echo json_encode( $response );

			die();
		}

		// callback for wishlist ajax
		function ajax_add_to_wishlist() {
			
			if ( !empty( $_REQUEST ) ) {
				extract( $_REQUEST );

				if ( !empty( $form ) )
					parse_str( $form, $form );

				if( !empty($wishlist) && $this->add_to_wishlist( $wishlist, $form ) ){

					$this->ajax_get_wishlist();

				} else {
					$response = array(
						'status'=>'error',
						'msg' => __('The request to add the product to your wishlist failed.' )
						);
				}

			} else {
				$response = array(
					'status'=>'error',
					'msg' => __('The AJAX request is improperly formatted.')
					);
			}

			$response = wp_parse_args( $response, $this->default_ajax_response );
			echo json_encode( $response );

			die();
		}

		function get_wishlist_product( $ids = array() ){
			if( is_array( $ids )) {
				$products = array();
				foreach( $ids as $id ) {
					$products[] = self::get_wishlist_product( $id );
				}
				return $products;
			} else {
				$product_id = $ids;
				$product = (object) array(
					'ID' => $product_id,
					'title' => get_the_title( $product_id ),
					'permalink' => get_permalink( $product_id ),
					'thumbnail' => get_the_post_thumbnail( $product_id, 'shop_thumbnail' )
					);
				return $product;
			}
		}


		function add_to_wishlist( $wishlist, $attributes = array() ) {

			$defaults = array(
				'quantity' => 1
				);

			if ( ! $product_id = absint( $wishlist['product_id'] ) ) 
				return false;

			// will return anon wishlists if userid isn't known
			// if the wishlist is provided and not a legit wishlist 
			// then we try to get the active wishlist
			$wishlist_id = ! empty($wishlist['wishlist_id']) && wcsfl_is_wishlist( $wishlist_id ) ? $wishlist['wishlist_id'] : wcsfl_get_active_wishlist_by_user();

			// if no wishlists are returned then let's protect
			if ( empty( $wishlist_id ) ) {
				
				// create a wishlist for current user | anon
				$wishlist_id = wcsfl_create_wishlist();

			}

			// set wishlist meta and defaults
			$attributes = wp_parse_args( $attributes, $defaults );			
			foreach( $attributes as $key => $attribute ) {
				wcsfl_add_wishlist_meta( $wishlist_id, $product_id, $key, $attribute );	
			}
			
			return true;
		}

		// anon is simple a wishlist by author id 0 whose temporary id is the set_anon_cookie key and value is current wishlist post id to co-relate the two
		function has_wishlist( $user_ID, $type = 'anon' ) {
			if ( $type == 'anon' ) {
				// @TODO: for now, its one user, one wishlist - later interface will be built to handle multiple wishlists
				$wishlist_count = wcsfl_count_anon_posts_by_type( $user_ID );
			} else {
				// @TODO: for now, its one user, one wishlist - later interface will be built to handle multiple wishlists
				$wishlist_count = wcsfl_count_user_posts_by_type( $user_ID );
			}
			return $wishlist_count;
		}

		function get_user_capability(){
			return 'woocommerce_sfl_manage';
		}

		function register_post_type() {
			if ( post_type_exists( self::POST_TYPE ) ) return;

			$capability = self::get_user_capability();

			$post_type_args = apply_filters( 'woocommerce_sfl_post_type_args', array(
					'labels' => array(
						'name'      => __( 'WooWishLists', 'woocommerce_sfl' ),
						'singular_name'   => __( 'WooWishList', 'woocommerce_sfl' ),
						'menu_name'    => _x( 'WooWishLists', 'Admin menu name', 'woocommerce_sfl' ),
						'add_new'     => __( 'Add WooWishList', 'woocommerce_sfl' ),
						'add_new_item'    => __( 'Add New WooWishList', 'woocommerce_sfl' ),
						'edit'      => __( 'Edit', 'woocommerce_sfl' ),
						'edit_item'    => __( 'Edit WooWishList', 'woocommerce_sfl' ),
						'new_item'     => __( 'New WooWishList', 'woocommerce_sfl' ),
						'view'      => __( 'View WooWishList', 'woocommerce_sfl' ),
						'view_item'    => __( 'View WooWishList', 'woocommerce_sfl' ),
						'search_items'    => __( 'Search WooWishLists', 'woocommerce_sfl' ),
						'not_found'    => __( 'No WooWishLists found', 'woocommerce_sfl' ),
						'not_found_in_trash'  => __( 'No WooWishLists found in trash', 'woocommerce_sfl' ),
						'parent'     => __( 'Parent WooWishList', 'woocommerce_sfl' )
					),
					'description'    => __( 'This is where you can add new woo-wish-lists to your store.', 'woocommerce_sfl' ),
					'public'     => true,
					'show_ui'     => true,
					'capability_type'   => 'post',
					'capabilities' => array(
						'publish_posts'   => $capability,
						'edit_posts'    => $capability,
						'edit_others_posts'  => $capability,
						'delete_posts'    => $capability,
						'delete_others_posts' => $capability,
						'read_private_posts' => $capability,
						'edit_post'    => $capability,
						'delete_post'    => $capability,
						'read_post'    => $capability
					),
					'publicly_queryable'  => false,
					'exclude_from_search'  => true,
					'hierarchical'    => false,
					'rewrite'     => array(
						'slug' => $this->base_slug,
						'with_front' => true ),
					'query_var'    => true,
					'supports'     => array( 'title', 'custom-fields', 'author' ),
					'has_archive'    => false,
					'show_in_menu'  => false,
					'show_in_nav_menus'  => false
				) );
			register_post_type( self::POST_TYPE, $post_type_args );
		}

		function create_wishlist() {
			global $user_ID, $current_user;

			get_currentuserinfo();

			if ( is_user_logged_in() ) {
				$title = $current_user->display_name . "'s Wishlist";
			}else {
				$title = "Wishlist created ". date( 'F-d-Y:H-i-s', time() );
			}

			$post = array(
				'post_author'    => $user_ID,
				'post_category'  => array( 0 ),
				'post_status'    => 'publish',
				'post_title'     => $title,
				'post_type'      => WooCommerce_SaveForLater::POST_TYPE
			);

			return wp_insert_post( $post );
		}

		function get_wishlists( $userid, $post_type = WooCommerce_SaveForLater::POST_TYPE, $limit = 1 ) {
			$wishlists_post_ids = array();

			$wishlists = new WP_Query( array(
					'post_type' => $post_type,
					'posts_per_page' => $limit,
					'author' => $userid
				) );

			while ( $wishlists->have_posts() ) {
				$wishlists_post_ids[] = $wishlists->post->ID;
				break; // @TODO: issues with loop, memory space exaust error so breaking it for now
			}

			wp_reset_query();
			wp_reset_postdata();

			return $wishlists_post_ids;
		}

		function get_wishlists_anon() {
			$wishlists_post_ids = array();

			// find is any plugin cookies are set

			//      echo '<pre>';
			//        print_r($_COOKIE);
			//      echo '</pre>';

			if ( !empty( $_COOKIE ) ) {
				foreach ( $_COOKIE as $key => $value ) {
					if ( strpos( $key, 'woocommerce_sfl' ) !== false ) {
						$wishlists_post_ids[$key] = $value; // return post id
					}
				}
			}

			return $wishlists_post_ids;
		}

		function set_cookie_anon( $post_id, $post, $remember = false ) {
			//verify post is not a revision & is a wishlist & user is not logged in
			if ( $post->post_status != 'auto-draft' && ( WooCommerce_SaveForLater::POST_TYPE == $_REQUEST['post_type'] || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'into_wishlist' ) ) && ! wp_is_post_revision( $post_id ) && !is_user_logged_in() ) {
				// hook into only 'publish' events
				if ( isset( $_REQUEST['publish'] ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'into_wishlist' ) ) {
					// anon_wishlist operations
					if ( $remember ) {
						$expiration = $expire = time() + 1209600; // @TODO: add filter to specify the time period of anon_cookie
					}else {
						$expiration = time() + 1209600; // @TODO: add filter to specify the time period of anon_cookie
						$expire = 0;
					}

					$name = 'woocommerce_sfl_' . $post->post_name;
					setcookie( $name, $post_id, $expire, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN );
				}
			}
		}

		function save_post_anon( $post_id, $post ) {
			//verify post is not a revision & is a wishlist & user is not logged in
			if ( $post->post_status != 'auto-draft' && ( WooCommerce_SaveForLater::POST_TYPE == $_REQUEST['post_type'] || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'into_wishlist' ) ) && ! wp_is_post_revision( $post_id ) && !is_user_logged_in() ) {
				// hook into only 'publish' events
				if ( isset( $_REQUEST['publish'] ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'into_wishlist' ) ) {
					// anon_post operations
					$meta_key = 'woocommerce_sfl_' . $post->post_name;
					$meta_value = $post_id;
					update_post_meta( $post_id, $meta_key, $meta_value ); // right now only one wishlist per user
					WooCommerce_SaveForLater::set_cookie_anon( $post_id, $post );
				}
			}
		}

		function save_post( $post_id, $post ) {
			//verify post is not a revision & is a wishlist
			if ( ! wp_is_post_revision( $post_id ) && !empty($_REQUEST['post_type']) && self::POST_TYPE == $_REQUEST['post_type'] && $post->post_status != 'auto-draft' ) {
				// unhook this function so it doesn't loop infinitely
				remove_action( 'save_post', array( $this, 'save_post' ) );

				// hook into only 'publish' events
				if ( isset( $_REQUEST['publish'] ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'into_wishlist' ) ) {
					// update the post and change the post_name/slug to the post_title
					wp_update_post( array( 'ID' => $post_id, 'post_name' => self::generate_unique_slug() ) );
					// anon_post operations
					WooCommerce_SaveForLater::save_post_anon( $post_id, $post, true );
				}

				//re-hook this function
				add_action( 'save_post', array( $this, 'save_post' ) );
			}
		}

		function generate_unique_slug() {
			global $wpdb;
			
			$allowed_chars = apply_filters( 'woocommerce_sfl_unique_slug_allowed_chars', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );
			$url_length = SFL_Wishlist_Settings::get_option( 'unique_url_length' );
			$unique_slug = '';
			
			for ( $i=0; $i<$url_length; $i++ ) {
				$unique_slug .= substr( $allowed_chars, rand( 0, strlen( $allowed_chars ) ), 1 );
			
			}
			// check to see if this unique slug has been used before?
			if ( $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = '%s';", $unique_slug ) ) != null ) {
				
				// try generating again
				$unique_slug = self::generate_unique_slug();
			}

			return apply_filters('woocommerce_sfl_generate_unique_slug', $unique_slug);
		}

		function enqueue_assets() {
			if( !is_admin() ) {
				wp_enqueue_style( 'woocommerce_sfl_style', $this->url . 'assets/save-for-later.css', array( 'woocommerce_frontend_styles' ), 1.0, 'screen' );
				wp_enqueue_script( 'woocommerce_sfl_localstorage', $this->url . 'assets/jQuery.localStorage.js', array( 'jquery' ), 1.0, true );
				wp_enqueue_script( 'woocommerce_sfl_script', $this->url . 'assets/save-for-later.js', array( 'woocommerce_sfl_localstorage' ), 1.0, true );

				$localize_script = apply_filters('woocommerce_sfl_localize_script', array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'user_status' => is_user_logged_in(),
					'css_colors_enabled' => SFL_Wishlist_Settings::get_option('css_colors_enabled'),
					'css_colors' => SFL_Wishlist_Settings::get_option('css_colors'),
					'header_show' => sprintf( '%s %s',
						__('Show'),
						SFL_Wishlist_Settings::get_option('frontend_label') ),
					'header_hide' => sprintf( '%s %s',
						__('Hide'),
						SFL_Wishlist_Settings::get_option('frontend_label') ),
					'template' => array(
						'product' => SFL_Wishlist_Template::banner_product_template(),
						'not_found' => SFL_Wishlist_Template::not_found()
						)
					) );

				// using localized js namespace
				wp_localize_script( 'woocommerce_sfl_script', 'wcsfl_settings' , $localize_script);
			}
		}

		function wp_footer(){
			if( SFL_Wishlist_Settings::get_option('wp_footer_enabled') == 'yes' &&
				( SFL_Wishlist_Settings::get_option( 'store_only' ) == 'no' || 
				( SFL_Wishlist_Settings::get_option( 'store_only' ) == 'yes' && ( is_woocommerce() || is_cart() ) ) )
			){
				// display the wishlist banner
				SFL_Wishlist_Template::banner();
			}
		}

		public static function lazy_loader( $class_name ) {

			$file = apply_filters( 'woocommerce_sfl_lazy_loader', self::get_plugin_path() . 'classes/' . $class_name . '.php', $class_name );

			if ( file_exists( $file ) )
				require_once $file;
		}

		public static function get_plugin_path() {
			return trailingslashit( dirname( __FILE__ ) );
		}

		/**
		 * Check the minimum PHP & WP versions
		 *
		 * @static
		 * @return bool Whether the test passed
		 */
		public static function prerequisites() {;
			$pass = TRUE;
			// $pass = $pass && defined( WOOCOMMERCE_VERSION ) && version_compare( WOOCOMMERCE_VERSION, self::MIN_WC_VERSION, '>=' );
			$pass = $pass && version_compare( phpversion(), self::MIN_PHP_VERSION, '>=' );
			$pass = $pass && version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '>=' );
			return $pass;
		}

		public static function min_version_fail_notice() {
			echo '<div class="error"><p>';
			_e( sprintf( '%s requires the minimum versions of PHP v%s, WordPress v%s, and WooCommerce v%s in order to run properly.',
					self::PLUGIN_NAME,
					self::MIN_PHP_VERSION,
					self::MIN_WP_VERSION,
					self::MIN_WC_VERSION
				), 'woocommerce_sfl' );
			echo '</p></div>';
		}

		public static function woocommerce_fail_notice() {
			echo '<div class="error"><p>';
			_e( sprintf( '%s requires that WooCommerce be active in order to be succesfully activated.',
					self::PLUGIN_NAME
				), 'woocommerce_sfl' );
			echo '</p></div>';
		}

		/* Static Singleton Factory Method */
		public static function instance() {
			if ( !isset( self::$instance ) ) {
				$class_name = __CLASS__;
				self::$instance = new $class_name;
			}
			return self::$instance;
		}
	}
}
