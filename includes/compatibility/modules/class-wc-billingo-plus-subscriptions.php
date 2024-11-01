<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WooCommerce Subscriptions Compatibility
class WC_Billingo_Plus_Subscriptions_Compatibility {

	public static function init() {
		add_filter( 'wcs_renewal_order_meta_query', array( __CLASS__, 'remove_billing_fields_from_cloned_order' ), 10, 3 );
	}

	public static function remove_billing_fields_from_cloned_order( $order_meta_query, $to_order, $from_order ) {
		$order_meta_query .= " AND `meta_key` NOT IN ('_wc_billingo_plus_own', '_wc_billingo_plus_void_id', '_wc_billingo_plus_void_name', '_wc_billingo_plus_void_pdf', '_wc_billingo_plus_invoice_id', '_wc_billingo_plus_invoice_name', '_wc_billingo_plus_invoice_pdf', '_wc_billingo_plus_proform_id', '_wc_billingo_plus_proform_name', '_wc_billingo_plus_proform_pdf', '_wc_billingp_plus_completed')";
		return $order_meta_query;
	}

}

WC_Billingo_Plus_Subscriptions_Compatibility::init();