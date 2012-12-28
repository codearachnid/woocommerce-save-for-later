<?php

if ( !defined( 'ABSPATH' ) )
  die( '-1' );

if ( ! class_exists( 'WooCommerce_SaveForLater' ) ) {
  class WooCommerce_SaveForLater {

    protected static $instance;

    public $path;
    public $dir;
    public $url;
    public $version = '1.0';

    const PLUGIN_NAME = 'WooCommerce: Save For Later';
    const DOMAIN = 'wcsvl';
    const MIN_WC_VERSION = '1.6.5';
    const MIN_WP_VERSION = '3.4';
    const MIN_PHP_VERSION = '5.3';
    const POST_TYPE = 'wcsvl_wishlist';

    function __construct() {

      // register lazy autoloading
      spl_autoload_register( 'self::lazy_loader' );

      $this->check_install();

      // set core vars
      $this->path = self::get_plugin_path();
      $this->dir = trailingslashit( basename( $this->path ) );
      $this->url = plugins_url() . '/' . $this->dir;
      $this->base_slug = apply_filters( self::DOMAIN . '_base_slug', 'wishlist');

      add_action( 'init', array( $this, 'register_post_type' ) );
      add_action( 'admin_menu', array( $this, 'admin_menu' ) );
      add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );

      add_action('save_post', array($this, 'save_post'), 10, 2 );

      add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'loop_image_overlay' ), 20 );
      add_action( 'woocommerce_after_shop_loop_item', array( $this, 'save_for_later' ), 20 ); // link on product collections page
      add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'save_for_later' ), 20 ); // link on product single page
    }

    function check_install() {
      register_activation_hook( __FILE__, array( 'SFL_Wishlist_Install', 'activate' ) );
      register_activation_hook( __FILE__, array( 'SFL_Wishlist_Install', 'flush_rewrite_rules' ) );
      if ( is_admin() && get_option( self::DOMAIN . '_db_version' ) != $this->version )
        add_action( 'init', array( 'SFL_Wishlist_Install', 'install_or_upgrade' ), 1 );
    }

    function register_post_type() {
      if ( post_type_exists( self::POST_TYPE ) ) return;

      $cap = self::DOMAIN . '_manage';

      $post_type_args = apply_filters( self::DOMAIN . '_post_type_args', array(
          'labels' => array(
            'name'      => __( 'WooWishLists', 'wcsvl' ),
            'singular_name'   => __( 'WooWishList', 'wcsvl' ),
            'menu_name'    => _x( 'WooWishLists', 'Admin menu name', 'wcsvl' ),
            'add_new'     => __( 'Add WooWishList', 'wcsvl' ),
            'add_new_item'    => __( 'Add New WooWishList', 'wcsvl' ),
            'edit'      => __( 'Edit', 'wcsvl' ),
            'edit_item'    => __( 'Edit WooWishList', 'wcsvl' ),
            'new_item'     => __( 'New WooWishList', 'wcsvl' ),
            'view'      => __( 'View WooWishList', 'wcsvl' ),
            'view_item'    => __( 'View WooWishList', 'wcsvl' ),
            'search_items'    => __( 'Search WooWishLists', 'wcsvl' ),
            'not_found'    => __( 'No WooWishLists found', 'wcsvl' ),
            'not_found_in_trash'  => __( 'No WooWishLists found in trash', 'wcsvl' ),
            'parent'     => __( 'Parent WooWishList', 'wcsvl' )
          ),
          'description'    => __( 'This is where you can add new woo-wish-lists to your store.', 'wcsvl' ),
          'public'     => true,
          'show_ui'     => true,
          'capability_type'   => 'post',
          'capabilities' => array(
            'publish_posts'   => $cap,
            'edit_posts'    => $cap,
            'edit_others_posts'  => $cap,
            'delete_posts'    => $cap,
            'delete_others_posts' => $cap,
            'read_private_posts' => $cap,
            'edit_post'    => $cap,
            'delete_post'    => $cap,
            'read_post'    => $cap
          ),
          'publicly_queryable'  => true,
          'exclude_from_search'  => false,
          'hierarchical'    => false,
          'rewrite'     => array( 
            'slug' => $this->base_slug,
            'with_front' => true ),
          'query_var'    => true,
          'supports'     => array( 'title', 'custom-fields', 'author' ),
          'has_archive'    => false,
          'show_in_menu'  => true,
          'show_in_nav_menus'  => false
        ));
      register_post_type( self::POST_TYPE, $post_type_args );
    }

    function save_post( $post_id, $post ){
      //verify post is not a revision & is a wishlist
      if ( $post->post_status != 'auto-draft' && WooCommerce_SaveForLater::POST_TYPE == $_REQUEST['post_type'] && ! wp_is_post_revision( $post_id ) ) {
        // unhook this function so it doesn't loop infinitely
        remove_action('save_post', array( $this, 'save_post' ) );

        // hook into only 'publish' events
        if( isset($_REQUEST['publish']) ) {
          // update the post and change the post_name/slug to the post_title
          wp_update_post(array('ID' => $post_id, 'post_name' => self::generate_unique_slug() ));
        }

        //re-hook this function
        add_action('save_post', array( $this, 'save_post' ) );
      }
    }

    function generate_unique_slug() {
      global $wpdb;
      $allowed_chars = apply_filters( self::DOMAIN . '_unique_slug_allowed_chars', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
      $url_length = get_option( self::DOMAIN . '_unique_url_length', 6 );
        $unique_slug = '';
      for ($i=0; $i<$url_length; $i++) {
        $unique_slug .= substr($allowed_chars, rand(0, strlen($allowed_chars)), 1);
      }
      // check to see if this unique slug has been used before?
      if ($wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = '%s';", $unique_slug )) != null) {
        // try generating again
        return self::generate_unique_slug();
      } else {
        return $unique_slug;
      }
    }

    function maybe_enqueue_assets() {
      wp_enqueue_style( 'wcsvl-style', $this->url . 'assets/style.css', array( 'woocommerce_frontend_styles' ), 1.0, 'screen' );
      wp_enqueue_script( 'wcsvl-script', $this->url . 'assets/action.js', array( 'jquery' ), 1.0, true );
    }
    function loop_image_overlay() {
      $overlay_image = sprintf( '<img src="%s" class="%s" alt="%s" />',
        apply_filters( 'woocommerce_save_for_later_loop_thumb_img', $this->url . 'assets/icons/folder_add.png' ),
        apply_filters( 'woocommerce_save_for_later_loop_thumb_class', self::DOMAIN . '-save-for-later' ),
        __( 'Save For Later', 'wcsvl' )
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

    public static function woocommerce_fail_notice() {
      echo '<div class="error"><p>';
      _e( sprintf( '%s requires that WooCommerce be active in order to be succesfully activated.',
          self::PLUGIN_NAME
        ), 'wcsvl' );
      echo '</p></div>';
    }

    public static function save_for_later() {
      global $product;
?>
      <a class="save_for_later button product_type_<?php echo $product->product_type ?>" data-product_id="<?php echo $product->id ?>" rel="nofollow" href=""><?php echo __( 'Save for Later' ) ?></a>
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
