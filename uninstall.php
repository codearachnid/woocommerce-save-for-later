<?php
/**
 * WooWishList Uninstall
 *
 * Uninstalling WooWishList deletes user caps, custom post and custom tables
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Uninstaller
 * @version     1.6.4
 */
if( !defined('WP_UNINSTALL_PLUGIN') ) exit();

global $wpdb, $wp_roles;

// Capabilities
$wp_roles->remove_cap( 'administrator', 'manage_woocommerce_sfl' );

// Tables
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "woowishlist_meta" );

// Delete options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woowishlist_%';");