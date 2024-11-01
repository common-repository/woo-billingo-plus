<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WooCommerce Product Bundles Compatibility
class WC_Billingo_Plus_Product_Bundles_Compatibility {

	public static function init() {
		add_filter( 'wc_billingo_plus_invoice_line_item', array( __CLASS__, 'add_product_bundle_info' ), 10, 4 );
		add_filter( 'wc_billingo_plus_invoice', array( __CLASS__, 'remove_empty_line_items' ), 10, 2 );
	}

	public static function add_product_bundle_info( $product_item, $order_item, $order, $invoiceData ) {

		//Check if line item is a container
		if(wc_pb_is_bundle_container_order_item($order_item) && $product_item['unit_price'] != 0) {

			//Get bundled items
			$bundled_items = wc_pb_get_bundled_order_items($order_item, $order);
			foreach ($bundled_items as $bundled_order_item) {
				$product_item['comment'] .= '• '.$bundled_order_item->get_quantity().'× '.$bundled_order_item->get_name()."\n";
			}

		}

		if(is_object($order_item) && method_exists($order_item, 'get_product')) {
			if ( wc_pb_is_bundled_order_item($order_item, $order) && $product_item['unit_price'] == 0 ) {
				$product_item = false;
			} elseif ( wc_pb_is_bundle_container_order_item($order_item) && $product_item['unit_price'] == 0 ) {
				$product_item = false;
			}
		}

		return $product_item;
	}

	public static function remove_empty_line_items($invoiceData, $order) {

		//Remove empty line items
		foreach ($invoiceData['items'] as $key => $line_item) {
			if(!$line_item) {
				unset($invoiceData['items'][$key]);
			}
		}

		//Reindex array, so its still valid
		$invoiceData['items'] = array_values($invoiceData['items']);

		return $invoiceData;
	}

}

WC_Billingo_Plus_Product_Bundles_Compatibility::init();
