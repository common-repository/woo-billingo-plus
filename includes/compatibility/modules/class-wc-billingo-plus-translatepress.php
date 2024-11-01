<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Translatepress Compatibility
class WC_Billingo_Plus_Translatepress_Compatibility {

	public static function init() {
		add_filter( 'wc_billingo_plus_invoice', array( __CLASS__, 'change_language' ), 10, 2 );
		add_filter( 'wc_billingo_plus_invoice_line_item', array( __CLASS__, 'change_item_language'), 10, 4);
		add_filter( 'wc_billingo_plus_get_order_language', array( __CLASS__, 'get_language'), 10, 2);
		add_action( 'wc_billingo_plus_before_generate_invoice', array( __CLASS__, 'set_language_temporarily'), 10, 2);
	}

	public static function change_language( $invoice, $order ) {
		$supported_locales = WC_Billingo_Plus_Helpers::get_supported_languages();
		$supported_locales = array_keys($supported_locales);

		if($locale = $order->get_meta('_translatepress_locale')) {
			$locale = substr($locale, 0, 2);
			if($locale && in_array($locale, $supported_locales)) {
				$invoice['language'] = $locale;
			}
		}

		return $invoice;
	}

	public static function change_item_language($tetel, $order_item, $order, $szamla) {
		if($order->get_meta('trp_language')) {
			$tetel['name'] = htmlspecialchars_decode(wp_strip_all_tags(trp_translate($tetel['name'])));
		}
		return $tetel;
	}

	public static function get_language($lang_code, $order) {
		$supported_locales = WC_Billingo_Plus_Helpers::get_supported_languages();
		$supported_locales = array_keys($supported_locales);

		if($locale = $order->get_meta('_translatepress_locale')) {
			$locale = substr($locale, 0, 2);
			if($locale && in_array($locale, $supported_locales)) {
				$lang_code = $locale;
			}
		}
		return $lang_code;
	}

	public static function set_language_temporarily($order_id) {
		$order = wc_get_order($order_id);
		if($order->get_meta('trp_language')) {
			$locale = $order->get_meta('trp_language');
			trp_switch_language($locale);
		}
	}

}

WC_Billingo_Plus_Translatepress_Compatibility::init();
