<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WooCommerce Advanced Quantity Compatibility
class WC_Billingo_Plus_EU_Vat_Assistant_Compatibility {

	public static function init() {
		add_filter( 'wc_billingo_plus_taxcode', array( __CLASS__, 'add_eu_vat_number' ), 10, 2 );
	}

	public static function add_eu_vat_number( $clientDataTaxcode, $order ) {

		//EU VAT Assistant
		if($order->get_meta( 'vat_number' ) && $order->get_meta('_vat_number_validated') && $order->get_meta('_vat_number_validated') == 'valid') {
			$clientDataTaxcode = $order->get_meta( 'vat_number' );
		}

		return $clientDataTaxcode;
	}

}

WC_Billingo_Plus_EU_Vat_Assistant_Compatibility::init();
