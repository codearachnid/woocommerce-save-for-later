<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

if ( ! class_exists( 'WooCommerce_SaveForLater' ) ) {
	class WooCommerce_SaveForLater {

		protected static $instance;

		public $path;
		public $dir;
		public $url;

		const PLUGIN_NAME = 'WooCommerce: Save For Later';
		const DOMAIN = 'wcsvl';
		const MIN_WC_VERSION = '1.6.5';
		const MIN_WP_VERSION = '3.4';
		const MIN_PHP_VERSION = '5.3';

		function __construct() {

			// register lazy autoloading
			spl_autoload_register( 'self::lazy_loader' );

			// set core vars
			$this->path = self::get_plugin_path();
			$this->dir = trailingslashit( basename( $this->path ) );
			$this->url = plugins_url() . '/' . $this->dir;

			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'));
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'loop_image_overlay'), 20);
      add_action('woocommerce_after_shop_loop_item', array($this, 'save_for_later'), 20); // link on product collections page
      add_action('woocommerce_after_add_to_cart_button', array($this, 'save_for_later'), 20); // link on product single page
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
    
    public static function save_for_later($type = 'link'){
      global $product;
      switch($type){
        case 'link':
          ?>
          <a class="save_for_later button product_type_<?Php echo $product->product_type ?>" data-product_id="<?php echo $product->id ?>" rel="nofollow" href=""><?php echo __('Save for Later') ?></a>
          <?php
          break;
        case 'button':
          // we do not need button, make it a link always
          ?>
          <button type="submit" class="button alt"><?php echo __('Save for Later') ?></button>
          <?php
          break;
        default:
          break;
      }
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