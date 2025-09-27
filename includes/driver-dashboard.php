THIS SHOULD BE A LINTER ERROR<?php
/**
 * Driver Dashboard: shortcode and REST endpoints
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register shortcode and assets
 */
add_action( 'init', function () {
	add_shortcode( 'wom_driver_dashboard', 'wom_render_driver_dashboard_shortcode' );
} );

/**
 * Enqueue front-end scripts/styles for the driver dashboard when shortcode is present
 */
function wom_maybe_enqueue_driver_assets() {
	if ( is_admin() ) {
		return;
	}

	global $post;
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( has_shortcode( $post->post_content, 'wom_driver_dashboard' ) ) {
		$handle = 'wom-driver-dashboard';
		$src    = WOM_PLUGIN_URL . 'assets/driver-dashboard.js';
		$ver    = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : WOM_PLUGIN_VERSION;
		wp_register_script( $handle, $src, array( 'wp-api-fetch' ), $ver, true );

		$rest_namespace = 'wom/v1';
		$settings       = array(
			'root'        => esc_url_raw( rest_url( $rest_namespace ) ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'userId'      => get_current_user_id(),
			'orderStatus' => array( 'assigned', 'en_route', 'delivered', 'failed' ),
		);
		wp_localize_script( $handle, 'WOM_Driver', $settings );
		wp_enqueue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'wom_maybe_enqueue_driver_assets' );

/**
 * Shortcode renderer
 */
function wom_render_driver_dashboard_shortcode( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<div class="wom-notice">' . esc_html__( 'Please log in to view your deliveries.', 'woocommerce-orders-map' ) . '</div>';
	}

	$container_id = 'wom-driver-dashboard-root';
	ob_start();
	echo '<div id="' . esc_attr( $container_id ) . '"></div>';
	return ob_get_clean();
}

/**
 * REST API routes
 */
add_action( 'rest_api_init', function () {
	$namespace = 'wom/v1';

	register_rest_route( $namespace, '/driver/orders', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'wom_rest_get_driver_orders',
		'permission_callback' => 'wom_rest_require_logged_in_driver',
		'args'                => array(
			'status' => array(
				'required' => false,
				'type'     => 'string',
			),
		),
	) );

	register_rest_route( $namespace, '/driver/orders/(?P<order_id>\d+)/status', array(
		'methods'             => WP_REST_Server::EDITABLE,
		'callback'            => 'wom_rest_update_driver_order_status',
		'permission_callback' => 'wom_rest_require_logged_in_driver_for_order',
		'args'                => array(
			'status' => array(
				'required' => true,
				'type'     => 'string',
			),
		),
	) );
} );

/**
 * Permissions: must be logged in and have assigned orders capability (or be customer/driver)
 */
function wom_rest_require_logged_in_driver() {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'wom_rest_forbidden', __( 'Authentication required', 'woocommerce-orders-map' ), array( 'status' => 401 ) );
	}
	// Optionally check role/capability here
	return true;
}

/**
 * Permissions: user must be assigned to this order
 */
function wom_rest_require_logged_in_driver_for_order( WP_REST_Request $request ) {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'wom_rest_forbidden', __( 'Authentication required', 'woocommerce-orders-map' ), array( 'status' => 401 ) );
	}
	$order_id  = absint( $request->get_param( 'order_id' ) );
	$user_id   = get_current_user_id();
	$assigned  = get_post_meta( $order_id, WOM_META_ASSIGNED_DRIVER, true );
	if ( (string) $assigned !== (string) $user_id ) {
		return new WP_Error( 'wom_rest_forbidden', __( 'You are not assigned to this order', 'woocommerce-orders-map' ), array( 'status' => 403 ) );
	}
	return true;
}

/**
 * GET /driver/orders
 */
function wom_rest_get_driver_orders( WP_REST_Request $request ) {
	if ( ! class_exists( 'WC_Order_Query' ) ) {
		return new WP_REST_Response( array(), 200 );
	}
	$user_id = get_current_user_id();
	$status  = sanitize_text_field( (string) $request->get_param( 'status' ) );

	$query_args = array(
		'limit'        => 50,
		'orderby'      => 'date',
		'order'        => 'DESC',
		'meta_query'   => array(
			array(
				'key'   => WOM_META_ASSIGNED_DRIVER,
				'value' => (string) $user_id,
			),
		),
		'return'       => 'ids',
	);

	$ids = wc_get_orders( $query_args );
	$data = array();
	foreach ( $ids as $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			continue;
		}
		$current_status = get_post_meta( $order_id, WOM_META_DRIVER_STATUS, true );
		if ( $status && $status !== $current_status ) {
			continue;
		}
		$data[] = array(
			'id'           => $order_id,
			'number'       => $order->get_order_number(),
			'status'       => $order->get_status(),
			'driverStatus' => $current_status ?: 'assigned',
			'customer'     => array(
				'name'    => $order->get_formatted_billing_full_name(),
				'phone'   => $order->get_billing_phone(),
			),
			'shipping'     => array(
				'address1' => $order->get_shipping_address_1(),
				'address2' => $order->get_shipping_address_2(),
				'city'     => $order->get_shipping_city(),
				'postcode' => $order->get_shipping_postcode(),
				'country'  => $order->get_shipping_country(),
			),
		);
	}

	return new WP_REST_Response( $data, 200 );
}

/**
 * POST /driver/orders/{order_id}/status
 */
function wom_rest_update_driver_order_status( WP_REST_Request $request ) {
	$order_id   = absint( $request->get_param( 'order_id' ) );
	$new_status = sanitize_text_field( (string) $request->get_param( 'status' ) );

	$allowed = array( 'assigned', 'en_route', 'delivered', 'failed' );
	if ( ! in_array( $new_status, $allowed, true ) ) {
		return new WP_Error( 'wom_bad_status', __( 'Invalid status', 'woocommerce-orders-map' ), array( 'status' => 400 ) );
	}

	update_post_meta( $order_id, WOM_META_DRIVER_STATUS, $new_status );

	// Optionally: update WC status on final states
	if ( 'delivered' === $new_status ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			// Do not force-complete; sites differ. Hookable filter:
			$should_complete = apply_filters( 'wom_complete_wc_order_on_delivered', false, $order_id );
			if ( $should_complete ) {
				$order->update_status( 'completed', __( 'Marked delivered by driver', 'woocommerce-orders-map' ) );
			}
		}
	}

	return new WP_REST_Response( array( 'ok' => true, 'status' => $new_status ), 200 );
}

<content of includes/driver-dashboard.php>