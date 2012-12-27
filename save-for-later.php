<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

if ( ! class_exists( 'WooCommerce_SaveForLater' ) ) {
	class WooCommerce_SaveForLater {

		protected static $instance;

		public $path;
		public $dir;
		public $url;
    
    // pluggin version number
    var $version = '1.0';
    
		const PLUGIN_NAME = 'WooCommerce: Save For Later';
		const DOMAIN = 'wcsvl';
		const MIN_WC_VERSION = '1.6.5';
		const MIN_WP_VERSION = '3.4';
		const MIN_PHP_VERSION = '5.3';
    

		function __construct() {

			// register lazy autoloading
			spl_autoload_register( 'self::lazy_loader' );
      
      if ( is_admin() && ! defined('DOING_AJAX') ) $this->install();
      
			// set core vars
			$this->path = self::get_plugin_path();
			$this->dir = trailingslashit( basename( $this->path ) );
			$this->url = plugins_url() . '/' . $this->dir;
      
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'));
      
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'loop_image_overlay'), 20);
      add_action('woocommerce_after_shop_loop_item', array( $this, 'save_for_later' ), 20); // link on product collections page
      add_action('woocommerce_after_add_to_cart_button', array( $this, 'save_for_later' ), 20); // link on product single page
		}
    
    function install(){
      register_activation_hook( __FILE__, array($this, 'activate_woowishlist') );
      register_activation_hook( __FILE__, array($this, 'flush_rewrite_rules') );
      if ( get_option('woowishlist_db_version') != $this->version )
        add_action( 'init', array( $this, 'install_woowishlist' ), 1 );
    }
    
    function activate_woowishlist(){
      update_option( 'woowishlist_installed', 1 );
      $this->do_woowishlist_install();
    }
    
    function install_woowishlist(){
      $this->do_woowishlist_install();
    }
    
    function do_woowishlist_install(){
      $this->init_custom_tables();
      $this->init_user_roles();
      $this->init_post_types();
      // Update version
      update_option( "woowishlist_db_version", $this->version );
      flush_rewrite_rules();
    }
    
    function init_custom_tables(){
      global $wpdb;
      
      $collate = '';
      if ( $wpdb->has_cap( 'collation' ) ) {
        if( ! empty($wpdb->charset ) ) $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
        if( ! empty($wpdb->collate ) ) $collate .= " COLLATE $wpdb->collate";
      }
      
      // WooWishList meta table
      $sql = "
      CREATE TABLE ". $wpdb->prefix . "woowishlist_meta (
        meta_id bigint(20) NOT NULL AUTO_INCREMENT,
        woowishlist_post_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        meta_key varchar(255) NULL,
        meta_value longtext NULL,
        PRIMARY KEY  (meta_id)
      ) $collate;
      ";
          dbDelta($sql);
    }
    
    function init_user_roles() {
      global $wp_roles;
      
      if ( class_exists('WP_Roles') ) if ( ! isset( $wp_roles ) ) $wp_roles = new WP_Roles();
      
      if ( is_object($wp_roles) ) {
        // capabilities for admin
        $wp_roles->add_cap( 'administrator', 'manage_woowishlists' );
      }
    }
    
    function init_post_types(){
      if ( post_type_exists('woowishlist') ) return;

      $base_slug = 'woowishlist';  
      $woo_wish_list_base = trailingslashit(_x('woowishlist', 'slug', 'woocommerce_svl'));
    
      /**
       * Post Type
       * 
       **/

      do_action( 'woowishlist_register_post_type' );

      register_post_type( "woowishlist",
        array(
          'labels' => array(
              'name' 					=> __( 'WooWishLists', 'woocommerce_svl' ),
              'singular_name' 		=> __( 'WooWishList', 'woocommerce_svl' ),
              'menu_name'				=> _x( 'WooWishLists', 'Admin menu name', 'woocommerce_svl' ),
              'add_new' 				=> __( 'Add WooWishList', 'woocommerce_svl' ),
              'add_new_item' 			=> __( 'Add New WooWishList', 'woocommerce_svl' ),
              'edit' 					=> __( 'Edit', 'woocommerce_svl' ),
              'edit_item' 			=> __( 'Edit WooWishList', 'woocommerce_svl' ),
              'new_item' 				=> __( 'New WooWishList', 'woocommerce_svl' ),
              'view' 					=> __( 'View WooWishList', 'woocommerce_svl' ),
              'view_item' 			=> __( 'View WooWishList', 'woocommerce_svl' ),
              'search_items' 			=> __( 'Search WooWishLists', 'woocommerce_svl' ),
              'not_found' 			=> __( 'No WooWishLists found', 'woocommerce_svl' ),
              'not_found_in_trash' 	=> __( 'No WooWishLists found in trash', 'woocommerce_svl' ),
              'parent' 				=> __( 'Parent WooWishList', 'woocommerce_svl' )
            ),
          'description' 			=> __( 'This is where you can add new woo-wish-lists to your store.', 'woocommerce_svl' ),
          'public' 				=> true,
          'show_ui' 				=> true,
          'capability_type' 		=> 'post',
          'capabilities' => array(
            'publish_posts' 		=> 'manage_woowishlists',
            'edit_posts' 			=> 'manage_woowishlists',
            'edit_others_posts' 	=> 'manage_woowishlists',
            'delete_posts' 			=> 'manage_woowishlists',
            'delete_others_posts'	=> 'manage_woowishlists',
            'read_private_posts'	=> 'manage_woowishlists',
            'edit_post' 			=> 'manage_woowishlists',
            'delete_post' 			=> 'manage_woowishlists',
            'read_post' 			=> 'manage_woowishlists'
          ),
          'publicly_queryable' 	=> true,
          'exclude_from_search' 	=> false,
          'hierarchical' 			=> false,
          'rewrite' 				=> array( 'slug' => $woo_wish_list_base, 'with_front' => false, 'feeds' => $base_slug ),
          'query_var' 			=> true,
          'supports' 				=> array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'custom-fields', 'page-attributes' ),
          'has_archive' 			=> $base_slug,
          'show_in_menu' 	=> true,
          'show_in_nav_menus' 	=> true
        )
      );
    }

		function maybe_enqueue_assets(){
			wp_enqueue_style('wcsvl-style', $this->url . 'assets/style.css', array('woocommerce_frontend_styles'), 1.0, 'screen');
			wp_enqueue_script('wcsvl-script', $this->url . 'assets/action.js', array('jquery'), 1.0, true);
		}
		function loop_image_overlay(){
			$overlay_image = sprintf( '<img src="%s" class="%s" alt="%s" />',
				apply_filters( 'woocommerce_save_for_later_loop_thumb_img', $this->url . 'assets/icons/folder_add.png' ),
				apply_filters( 'woocommerce_save_for_later_loop_thumb_class', self::DOMAIN . '-save-for-later' ),
				__('Save For Later', 'wcsvl')
				);
			echo apply_filters( 'woocommerce_save_for_later_loop_thumb', $overlay_image );
		}

		public static function lazy_loader( $class_name ) {

			$file = self::get_plugin_path() . 'class/' . $class_name . '.php';

			if ( file_exists( $file ) )
				require_once $file;
		}

		public static function get_plugin_path() {
			return trailingslashit( dirname( __FILE__ ) );
		}

		public function admin_menu() {
			// add_menu_page( self::PLUGIN_NAME, self::PLUGIN_NAME, 'manage_options', self::DOMAIN, array( 'DeployS3Hosted_Admin', 'dashboard' ), $this->url . 'assets/icon.png', 100 );
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
				), 'wcsvl' );
			echo '</p></div>';
		}

		public static function woocommerce_fail_notice(){
			echo '<div class="error"><p>';
			_e( sprintf('%s requires that WooCommerce be active in order to be succesfully activated.',
				self::PLUGIN_NAME
				), 'wcsvl' );
			echo '</p></div>';
		}
    
    public static function save_for_later(){
      global $product;
      ?>
      <a class="save_for_later button product_type_<?Php echo $product->product_type ?>" data-product_id="<?php echo $product->id ?>" rel="nofollow" href=""><?php echo __('Save for Later') ?></a>
      <?php
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