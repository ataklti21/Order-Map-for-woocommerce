<?php
/**
 * Admin Dashboard Map Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'wom_orders_map_widget', __( 'Orders Map', 'woocommerce-orders-map' ), 'wom_render_orders_map_widget' );
} );

function wom_render_orders_map_widget() {
	$map_id = 'wom-admin-map';
	$handle = 'wom-admin-map';
	$ver    = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : WOM_PLUGIN_VERSION;

	// Leaflet CSS/JS (CDN). In production consider bundling locally or using WordPress packages.
	wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
	wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

	wp_register_script( $handle, WOM_PLUGIN_URL . 'assets/admin-map.js', array( 'leaflet' ), $ver, true );
	$settings = array(
		'root'  => esc_url_raw( rest_url( 'wom/v1' ) ),
		'nonce' => wp_create_nonce( 'wp_rest' ),
	);
	wp_localize_script( $handle, 'WOM_AdminMap', $settings );
	wp_enqueue_script( $handle );

	// Container
	echo '<div id="' . esc_attr( $map_id ) . '" style="height:400px"></div>';
}

// Simple REST endpoint to fetch recent orders with basic location info
add_action( 'rest_api_init', function () {
	register_rest_route( 'wom/v1', '/admin/orders-for-map', array(
		'methods'             => WP_REST_Server::READABLE,
		'permission_callback' => function () { return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ); },
		'callback'            => function () {
			$orders = wc_get_orders( array(
				'limit'   => 50,
				'orderby' => 'date',
				'order'   => 'DESC',
				'return'  => 'ids',
			) );
			$data = array();
			foreach ( $orders as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) { continue; }
				$lat = get_post_meta( $order_id, '_wom_lat', true );
				$lng = get_post_meta( $order_id, '_wom_lng', true );
				// Only include orders with coords (assumes geocoding done elsewhere)
				if ( '' === $lat || '' === $lng ) { continue; }
				$data[] = array(
					'id'      => $order_id,
					'number'  => $order->get_order_number(),
					'lat'     => (float) $lat,
					'lng'     => (float) $lng,
					'address' => wc_format_address( $order->get_address( 'shipping' ) ),
					'status'  => $order->get_status(),
				);
			}
			return new WP_REST_Response( $data, 200 );
		},
	) );
} );

<content of includes/map-dashboard.php>