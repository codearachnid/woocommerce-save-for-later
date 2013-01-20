<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

/**
 * SFL_Wishlist_Settings class.
 *
 * @extends WC_Settings_API
 */
class SFL_Wishlist_Settings extends WC_Settings_API {

	private $defaults;
	protected static $instance;

	const PREFIX = 'woocommerce_wcsfl_';

	function __construct() {

		// color defaults
		$this->defaults[ 'css_colors'] = array(
			'border' => '#ad74a2',
			'header_bg' => '#f7f6f7',
			'header_text' => '#85ad74',
			'background' => '#ffffff',
			'text' => '#777777'
		);

		$this->default['store_only'] = 'no';

		$this->default['frontend_label'] = __('Saved Items');

		// limit the amount of products someone can add to their wishlist
		$this->defaults[ 'limit_add_amount' ] = apply_filters( WooCommerce_SaveForLater::DOMAIN . '_setting_default_limit_add_amount', 20 );

		add_action( 'woocommerce_general_settings', array( $this, 'add_general_fields' ) );
		add_action( 'woocommerce_admin_field_wcsfl_styles', array( $this, 'style_picker' ) );
		add_action( 'woocommerce_update_options_general', array( $this, 'update_options' ) );
	}

	function get_option( $key = null ) {
		if ( is_null( $key ) ) {
			return false;
		} else {
			$option = get_option( self::PREFIX . $key );
			if ( empty( $option ) ) {
				return self::instance()->default[ $key ];
			} else {
				return $option;
			}
		}
	}

	function update_options() {

		// Handle Colour Settings
		$new_colors = array(
			'border'  => ! empty( $_POST['woocommerce_wcsfl_css_border'] ) ? woocommerce_format_hex( $_POST['woocommerce_wcsfl_css_border'] ) : '',
			'header_bg' => ! empty( $_POST['woocommerce_wcsfl_css_header_bg'] ) ? woocommerce_format_hex( $_POST['woocommerce_wcsfl_css_header_bg'] ) : '',
			'header_text' => ! empty( $_POST['woocommerce_wcsfl_css_header_text'] ) ? woocommerce_format_hex( $_POST['woocommerce_wcsfl_css_header_text'] ) : '',
			'background' => ! empty( $_POST['woocommerce_wcsfl_css_bg'] ) ? woocommerce_format_hex( $_POST['woocommerce_wcsfl_css_bg'] ) : '',
			'text' => ! empty( $_POST['woocommerce_wcsfl_css_text'] ) ? woocommerce_format_hex( $_POST['woocommerce_wcsfl_css_text'] ) : ''
		);

		$old_colors = get_option( self::PREFIX . 'css_colors' );

		if ( $old_colors != $new_colors ) {
			update_option( self::PREFIX . 'css_colors', $new_colors );
			// woocommerce_compile_less_styles();
		}
	}

	function add_general_fields( $fields ) {
		$fields = array_merge( $fields, array(
				array( 'name' => __( 'Save For Later Options', 'wcsfl' ), 'type' => 'title', 'desc' => '', 'id' => 'wcsfl_options' ),
				array(
					'type' => 'wcsfl_styles'
				),
				array(
					'name' => __( "Enable 'Saved Items' in store only", 'wcsfl' ),
					'desc'   => __( "Show the 'Saved Items' list only on store pages, otherwise will show through whole site", 'wcsfl' ),
					'id'   => 'woocommerce_wcsfl_store_only',
					'std'   => $this->default['store_only'],
					'type'   => 'checkbox',
				),
				array( 'type' => 'sectionend', 'id' => 'wcsfl_options' ) ) );

		return $fields;
	}

	function style_picker() {

		// Get settings & defaults
		$colors = wp_parse_args( (array) get_option( 'woocommerce_wcsfl_css_colors' ), $this->defaults['css_colors'] );

		?><tr valign="top" class="woocommerce_css_colors">
		<th scope="row" class="titledesc">
			<label><?php _e( 'Styles', 'wcsfl' ); ?></label>
		</th>
	    <td class="forminp"><?php

		// Show inputs
		woocommerce_frontend_css_color_picker( __( 'Borders', 'wcsfl' ), 'woocommerce_wcsfl_css_border', $colors['border'], __( 'The border between the clickable header and the rest of the page, and border around product images', 'wcsfl' ) );
		woocommerce_frontend_css_color_picker( __( 'Header Bg', 'wcsfl' ), 'woocommerce_wcsfl_css_header_bg', $colors['header_bg'], __( 'Clickable header background', 'wcsfl' ) );
		woocommerce_frontend_css_color_picker( __( 'Header Text', 'wcsfl' ), 'woocommerce_wcsfl_css_header_text', $colors['header_text'], __( 'Clickable header text color', 'wcsfl' ) );
		woocommerce_frontend_css_color_picker( __( 'List Bg', 'wcsfl' ), 'woocommerce_wcsfl_css_bg', $colors['background'], __( 'Product list background', 'wcsfl' ) );
		woocommerce_frontend_css_color_picker( __( 'List Text', 'wcsfl' ), 'woocommerce_wcsfl_css_text', $colors['text'], __( 'Product list text color', 'wcsfl' ) );

		echo '</td></tr>';
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
