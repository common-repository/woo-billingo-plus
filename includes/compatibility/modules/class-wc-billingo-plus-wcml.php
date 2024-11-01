<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WPML WooCommerce Compatibility
class WC_Billingo_Plus_WCML_Compatibility {

	public static function init() {
		add_filter('wc_billingo_plus_get_order_language', array( __CLASS__, 'get_language'), 10, 2);
		add_filter('wc_billingo_plus_notes_conditions_values', array( __CLASS__, 'order_details'), 10, 2);
		add_filter('wc_billingo_plus_vat_overrides_conditions_values', array( __CLASS__, 'order_details'), 10, 2);
		add_filter('wc_billingo_plus_automations_conditions_values', array( __CLASS__, 'order_details'), 10, 2);
		add_filter('wc_billingo_plus_advanced_options_conditions_values', array( __CLASS__, 'order_details'), 10, 2);
		add_action('wc_billingo_plus_before_generate_invoice', array( __CLASS__, 'set_language_temporarily'), 10, 2);
	}

	public static function get_language($lang_code, $order) {
		$wpml_lang_code = $order->get_meta('wpml_language');
		$supported_locales = WC_Billingo_Plus_Helpers::get_supported_languages();
		$supported_locales = array_keys($supported_locales);
		if(!$wpml_lang_code && function_exists('pll_get_post_language')){
			$wpml_lang_code = pll_get_post_language($orderId, 'slug');
		}
		if($wpml_lang_code && in_array($wpml_lang_code, $supported_locales)) {
			$lang_code = $wpml_lang_code;
		}
		return $lang_code;
	}

	public static function order_details($details, $order) {
		$translated_product_categories = array();
		$wpml_default_language = apply_filters('wpml_default_language', NULL );
		foreach ($details['product_categories'] as $category_id) {
			$original_category_id = apply_filters( 'wpml_object_id', $category_id, 'product_cat', TRUE, $wpml_default_language );
			if($original_category_id) {
				$details['product_categories'][] = $original_category_id;
			}
		}
		return $details;
	}

	public static function set_language_temporarily($order_id) {
		$order = wc_get_order($order_id);
		if($order->get_meta('wpml_language')) {
			$locale = $order->get_meta('wpml_language');
			do_action( 'wpml_switch_language', $locale );
		}
	}

}

WC_Billingo_Plus_WCML_Compatibility::init();
