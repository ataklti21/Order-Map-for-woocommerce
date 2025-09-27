<?php
/**
 * Notifications (email/SMS hooks)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send POD confirmation link via selected method
 *
 * @param int    $order_id
 * @param string $method   'email' or 'sms'
 * @param string $confirm_url
 */
add_action( 'wom_send_pod_confirmation', function ( $order_id, $method, $confirm_url ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) { return; }

	if ( 'email' === $method ) {
		$to      = $order->get_billing_email();
		$subject = __( 'Confirm your delivery', 'woocommerce-orders-map' );
		$body    = sprintf( __( 'Please confirm you received order #%1$s by clicking: %2$s', 'woocommerce-orders-map' ), $order->get_order_number(), esc_url( $confirm_url ) );
		wp_mail( $to, $subject, $body );
	}

	if ( 'sms' === $method ) {
		// Stub: integrate with SMS gateway. For now, write an order note.
		$phone = $order->get_billing_phone();
		wc_create_order_note( $order_id, sprintf( __( 'SMS confirmation link to %1$s: %2$s', 'woocommerce-orders-map' ), esc_html( $phone ), esc_url( $confirm_url ) ) );
	}
}, 10, 3 );
