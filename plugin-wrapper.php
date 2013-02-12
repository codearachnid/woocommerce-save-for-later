<?php

/**
 * Plugin Name: WooCommerce: Save For Later
 * Plugin URI:
 * Description: Allow your visitors/customers to add products to a personal wishlist that they may save to purchase later or share with their friends.
 * Version: 1.0
 * Author: Timothy Wood (@codearachnid)
 * Author URI: http://www.codearachnid.com
 * Author Email: tim@imaginesimplicity.com
 * Text Domain: woocommerce_wishlist
 * License: GPLv2 or later
 * 
 * Notes: THIS FILE IS FOR LOADING THE LIBS ONLY
 * 
 * License:
 * 
 * Copyright 2011 Imagine Simplicity (tim@imaginesimplicity.com)
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * @package WC_Wishlist
 * @category Extension
 * @author codearachnid
 */

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

/**
 *  Include required files to get this show on the road
 */
require_once 'save-for-later.php';
require_once 'template-tags.php';

/**
 * Add action 'plugins_loaded' to instantiate main class.
 *
 * @return void
 */
function WooCommerce_SaveForLater_Load() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'woocommerce_wishlist_active_plugins', get_option( 'active_plugins' ) ) ) ) {
		if ( apply_filters( 'woocommerce_wishlist_rating_pre_check', class_exists( 'WC_Wishlist' ) && WC_Wishlist::prerequisites() ) ) {
			add_action( 'init', array( 'WC_Wishlist', 'instance' ), -100, 0 );
		} else {
			// let the user know prerequisites weren't met: Wrong Versions
			add_action( 'admin_head', array( 'WC_Wishlist', 'min_version_fail_notice' ), 0, 0 );
		}
	} else {
		// let the user know prerequisites weren't met: WooCommerce isn't available
		add_action( 'admin_head', array( 'WC_Wishlist', 'fail_notice' ), 0, 0 );
	}
}
add_action( 'plugins_loaded', 'WooCommerce_SaveForLater_Load', 1 ); // high priority so that it's not too late for addon overrides
