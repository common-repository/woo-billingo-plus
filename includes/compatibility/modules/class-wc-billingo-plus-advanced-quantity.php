<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//WooCommerce Advanced Quantity Compatibility
class WC_Billingo_Plus_Advanced_Quantity_Compatibility {

	public static function init() {
		add_filter( 'wc_billingo_plus_invoice_line_item', array( __CLASS__, 'change_invoice_item_qty_unit' ), 10, 4 );
	}

	public static function change_invoice_item_qty_unit( $product_item, $order_item, $order, $invoiceData ) {

		if(method_exists($order_item, 'get_product')) {
			$product = $order_item->get_product();
			if(!$product) return $product_item;

			//Skip if a custom unit is set by Billingo
			if($product->get_meta('wc_billingo_plus_mennyisegi_egyseg') && $product->get_meta('wc_billingo_plus_mennyisegi_egyseg') != 'Array') return $product_item;

			//Get custom qty unit
			$quantity_suffix = '';
			if(class_exists('Morningtrain\WooAdvancedQTY\Plugin\Controllers\SettingsController')) {
				$quantity_suffix = Morningtrain\WooAdvancedQTY\Plugin\Controllers\SettingsController::getAppliedSettingForProduct($order_item->get_product_id(), 'quantity-suffix');
			} else {
				$quantity_suffix = self::get_option($order_item->get_product_id(), 'quantity-suffix', '');
			}

			//Skip if empty
			if(!$quantity_suffix || $quantity_suffix == '') return $product_item;

			//Set new qty unit
			$product_item['unit'] = $quantity_suffix;
		}

		return $product_item;
	}

	//Need to replicate this function found in Woo_Advanced_QTY_Public because its not public
	public static function get_option($product_id, $identifier, $default = null) {
		// If is applied on the product - then use it
		$post_setting = \get_post_meta($product_id, '_advanced-qty-' . $identifier, true);
		if(!empty($post_setting)) {
			if($post_setting != 'global-input') {
				return $post_setting;
			}
		}

		// If setting is applied on the category - then use it
		$terms = \get_the_terms($product_id, 'product_cat');

		$term_setting = '';
		if(!empty($terms)) {
			foreach($terms as $term) {
				$term_option = \get_option('product-category-advanced-qty-' . $identifier . '-' . $term->term_id);

				if(!empty($term_option) && $term_option != 'global-input') {
					$term_setting = $term_option;
				}
			}

			if(!empty($term_setting)) {
				return $term_setting;
			}
		}

		// If setting is applied on the store - then use it
		$shop_setting = \get_option('woo-advanced-qty-' . $identifier);
		if(!empty($shop_setting)) {
			return $shop_setting;
		}

		return $default;
	}

}

WC_Billingo_Plus_Advanced_Quantity_Compatibility::init();
