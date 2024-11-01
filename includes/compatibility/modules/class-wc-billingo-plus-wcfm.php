<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WCFM Compatibility
class WC_Billingo_Plus_WCFM_Compatibility {

	public static function init() {
		add_filter('wc_billingo_plus_account_conditions', array(__CLASS__, 'create_vendor_conditions'));
		add_filter('wc_billingo_plus_account_conditions_values', array(__CLASS__, 'change_to_vendor_account'), 10, 2);
		add_filter('wc_billingo_plus_advanced_options_conditions', array(__CLASS__, 'create_vendor_conditions'));
		add_filter('wc_billingo_plus_advanced_options_conditions_values', array(__CLASS__, 'change_to_vendor_account'), 10, 2);
		add_filter('wc_billingo_plus_vat_overrides_conditions', array(__CLASS__, 'create_vendor_conditions'));
		add_filter('wc_billingo_plus_vat_overrides_conditions_values', array(__CLASS__, 'change_to_vendor_account'), 10, 2);
	}

	public static function create_vendor_conditions($conditions) {
		$vendors = self::get_wcfm_marketplace_vendors();
		$conditions['wcfm_vendors'] = array(
			"label" => apply_filters( 'wcfm_sold_by_label', '', __( 'Store', 'wc-multivendor-marketplace' ) ),
			'options' => $vendors
		);
		return $conditions;
	}

	public static function change_to_vendor_account($conditions, $order) {
		$order_vendor_id = self::get_wcfm_vendor_from_order($order);
		if($order_vendor_id) {
			$conditions['wcfm_vendor'] = $order_vendor_id;
		}
		return $conditions;
	}

	public static function get_wcfm_vendor_from_order($order) {
		global $wpdb;
		$items = $order->get_items( 'line_item' );
		$order_vendor_id = false;
		$vendor = false;
		if( !empty( $items ) ) {
			foreach( $items as $order_item_id => $item ) {
				$line_item = new WC_Order_Item_Product( $item );
				$product  = $line_item->get_product();
				$product_id = $line_item->get_product_id();
				$vendor_id  = wcfm_get_vendor_id_by_post( $product_id );

				if( !$vendor_id ) {
					continue;
				} else {
					$order_vendor_id = $vendor_id;
					break;
				}
			}
		}

		return $order_vendor_id;
	}

	public static function get_wcfm_marketplace_vendors() {
		$vendors = array();
		if (!isset($GLOBALS['WCFMmp']) ) {
			return $vendors;
		}

		global $WCFMmp;
		$stores = $WCFMmp->wcfmmp_vendor->wcfmmp_search_vendor_list(true);

		foreach ($stores as $store_id => $store_slug) {
			$store_user = wcfmmp_get_store( $store_id );
			$store_info = $store_user->get_shop_info();
			$vendors[$store_id] = $store_info['store_name'];
		}

		return $vendors;
	}

}

WC_Billingo_Plus_WCFM_Compatibility::init();
