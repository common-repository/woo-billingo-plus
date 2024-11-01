<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Product_Options', false ) ) :

	class WC_Billingo_Plus_Product_Options {

		//Init notices
		public static function init() {
			add_action('woocommerce_product_options_advanced', array( __CLASS__, 'product_options_fields'));
			add_action('woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_options_fields'), 10, 2);

			add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'variable_options_fields'), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variable_options_fields'), 10, 2 );

			add_filter('woocommerce_shipping_instance_form_fields_flat_rate', array( __CLASS__, 'shipping_options_fields'));
			add_filter('woocommerce_shipping_instance_form_fields_free_shipping', array( __CLASS__, 'shipping_options_fields'));
			add_filter('woocommerce_shipping_instance_form_fields_local_pickup', array( __CLASS__, 'shipping_options_fields'));
		}

		public static function variable_options_fields($loop, $variation_data, $variation) {
			include( dirname( __FILE__ ) . '/views/html-variable-options.php' );
		}

		public static function product_options_fields() {
			global $post;
			include( dirname( __FILE__ ) . '/views/html-product-options.php' );
		}

		public static function shipping_options_fields($fields){
			$fields['wc_billingo_plus_tetel_nev'] = [
				'title' => esc_html__('Line item name', 'wc-billingo-plus'),
				'type' => 'text',
				'description' => esc_html__('Enter a custom name that will appear on the invoice. Default is the name of the shipping method.', 'wc-billingo-plus'),
				'default' => '',
				'desc_tip' => true,
			];
			$fields['wc_billingo_plus_megjegyzes'] = [
				'title' => esc_html__('Note', 'wc-billingo-plus'),
				'type' => 'text',
				'description' => esc_html__('This note will be visible on the invoice line item.', 'wc-billingo-plus'),
				'default' => '',
				'desc_tip' => true,
			];
			$fields['wc_billingo_plus_mennyisegi_egyseg'] = [
				'title' => esc_html__('Unit type', 'wc-billingo-plus'),
				'type' => 'text',
				'description' => esc_html__('This is the unit type for the line item on the invoice. The default value is set in the plugin settings.', 'wc-billingo-plus'),
				'default' => '',
				'desc_tip' => true,
			];
			return $fields;
		}

		public static function save_product_options_fields($product) {
			$fields = ['mennyisegi_egyseg', 'megjegyzes', 'tetel_nev', 'disable_auto_invoice', 'hide_item', 'custom_cost'];
			foreach ($fields as $field) {
				$posted_data = ! empty( $_REQUEST['wc_billingo_plus_'.$field] )
					? esc_attr( $_REQUEST['wc_billingo_plus_'.$field] )
					: '';
				$product->update_meta_data( 'wc_billingo_plus_'.$field, $posted_data );
			}
			$product->save_meta_data();
		}

		public static function save_variable_options_fields($variation_id, $i) {
			$fields = ['mennyisegi_egyseg', 'megjegyzes', 'tetel_nev', 'disable_auto_invoice', 'hide_item', 'custom_cost'];
			foreach ($fields as $field) {
				$custom_field = $_POST['wc_billingo_plus_'.$field][$i];
				if ( ! empty( $custom_field ) ) {
						update_post_meta( $variation_id, 'wc_billingo_plus_'.$field, esc_attr( $custom_field ) );
				} else delete_post_meta( $variation_id, 'wc_billingo_plus_'.$field );
			}
		}

	}

	WC_Billingo_Plus_Product_Options::init();

endif;
