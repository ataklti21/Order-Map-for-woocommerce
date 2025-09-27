<?php
/**
 * Plugin Name: WooCommerce Orders Map
 * Description: Map view of WooCommerce orders with driver assignment and a driver dashboard.
 * Version: 0.1.0
 * Author: Your Company
 * License: GPLv2 or later
 * Text Domain: woocommerce-orders-map
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Core plugin constants
if ( ! defined( 'WOM_PLUGIN_FILE' ) ) {
	define( 'WOM_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WOM_PLUGIN_VERSION' ) ) {
	define( 'WOM_PLUGIN_VERSION', '0.1.0' );
}
if ( ! defined( 'WOM_PLUGIN_PATH' ) ) {
	define( 'WOM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WOM_PLUGIN_URL' ) ) {
	define( 'WOM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Meta keys used by this plugin
if ( ! defined( 'WOM_META_ASSIGNED_DRIVER' ) ) {
	define( 'WOM_META_ASSIGNED_DRIVER', '_wom_assigned_driver' );
}
if ( ! defined( 'WOM_META_DRIVER_STATUS' ) ) {
	define( 'WOM_META_DRIVER_STATUS', '_wom_driver_status' );
}
if ( ! defined( 'WOM_META_POD_TOKEN' ) ) {
	define( 'WOM_META_POD_TOKEN', '_wom_pod_token' );
}
if ( ! defined( 'WOM_META_POD_EXPIRES' ) ) {
	define( 'WOM_META_POD_EXPIRES', '_wom_pod_expires' );
}
if ( ! defined( 'WOM_META_POD_CONFIRMED' ) ) {
	define( 'WOM_META_POD_CONFIRMED', '_wom_pod_confirmed' );
}
if ( ! defined( 'WOM_META_POD_METHOD' ) ) {
	define( 'WOM_META_POD_METHOD', '_wom_pod_method' );
}

// Bootstrap feature modules
require_once WOM_PLUGIN_PATH . 'includes/driver-dashboard.php';

// Optionally, future modules can be required here
require_once WOM_PLUGIN_PATH . 'includes/map-dashboard.php';
// require_once WOM_PLUGIN_PATH . 'includes/driver-frontend-reports.php';
require_once WOM_PLUGIN_PATH . 'includes/notifications.php';

// Ensure WooCommerce is active (soft check)
add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		// WooCommerce not active. We keep plugin loaded but features relying on WC should guard themselves.
	}
} );

// No closing PHP tag to avoid accidental output
<content of woocommerce-orders-map.php>