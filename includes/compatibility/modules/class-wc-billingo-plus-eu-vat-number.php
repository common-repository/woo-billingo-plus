<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WooCommerce EU VAT Number Compatibility
class WC_Billingo_Plus_EU_Vat_Number_Compatibility {

	public static function init() {
		add_filter( 'wc_billingo_plus_taxcode', array( __CLASS__, 'add_eu_vat_number' ), 10, 2 );
	}

	public static function add_eu_vat_number( $clientDataTaxcode, $order ) {

		//EU VAT Assistant
		if(function_exists('wc_eu_vat_get_vat_from_order')) {
			$adoszam_eu = wc_eu_vat_get_vat_from_order($order);
		} else {
			$adoszam_eu = $order->get_meta( '_vat_number' );
		}

		return $clientDataTaxcode;
	}

}

WC_Billingo_Plus_EU_Vat_Number_Compatibility::init();
