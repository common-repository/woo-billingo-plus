<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'WC_Billingo_Plus_Receipt', false ) ) :
	class WC_Billingo_Plus_Receipt {

		//Init
		public static function init() {
			add_filter( 'woocommerce_checkout_fields' , array( __CLASS__, 'add_receipt_field_to_checkout' ), 20 );
			add_action( 'wp_ajax_wc_billingo_plus_receipt_check', array( __CLASS__, 'receipt_check_with_ajax' ) );
			add_action( 'wp_ajax_nopriv_wc_billingo_plus_receipt_check', array( __CLASS__, 'receipt_check_with_ajax' ) );
			add_filter( 'woocommerce_checkout_get_value' , array( __CLASS__, 'receipt_get_checkout_value' ), 10, 2 );
			add_action( 'woocommerce_cart_updated', array( __CLASS__, 'store_receipt_session_data') );
			add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save_receipt_session_data') );
			add_action( 'wp_ajax_wc_billingo_plus_reverse_receipt', array( __CLASS__, 'reverse_receipt_with_ajax' ) );
		}

		public static function store_receipt_session_data() {
			//Set session data, if not set previously
			if(!WC()->session->wc_billingo_plus_receipt) {
				WC()->session->set( 'wc_billingo_plus_receipt', 'receipt' );
			}
		}

		public static function receipt_get_checkout_value($value, $input) {
			if($input == 'wc_billingo_plus_receipt') {
				$receipt = WC()->session->wc_billingo_plus_receipt;
				if($receipt == 'invoice') {
					$value = true;
				} else {
					$value = false;
				}
			}
			return $value;
		}

		//Add E-Nyugta checkbox to checkout
		public static function add_receipt_field_to_checkout($fields) {

			$fields['billing']['wc_billingo_plus_receipt'] = array(
				'type'	=> 'checkbox',
				'label'	=> esc_html__('I need an invoice instead of a receipt', 'wc-billingo-plus'),
				'priority' => 0
			);

			//Hide billing fields if we don't need an invoice, just a receipt
			if(WC()->session->wc_billingo_plus_receipt && WC()->session->wc_billingo_plus_receipt == 'receipt') {
				if(WC_Billingo_Plus()->get_option('receipt_hidden_fields')) {
					foreach (WC_Billingo_Plus()->get_option('receipt_hidden_fields') as $field_to_hide) {
						foreach ($fields as $fields_group => $fields_in_group) {
							foreach ($fields_in_group as $field_id => $field_options) {
								if($field_to_hide == $field_id) {
									unset($fields[$fields_group][$field_id]);
								}
							}
						}
					}
				} else {
					unset($fields['billing']['billing_company']);
					unset($fields['billing']['billing_address_1']);
					unset($fields['billing']['billing_address_2']);
					unset($fields['billing']['billing_city']);
					unset($fields['billing']['billing_postcode']);
					unset($fields['billing']['billing_country']);
					unset($fields['billing']['billing_state']);
					unset($fields['billing']['billing_phone']);
					unset($fields['billing']['billing_address_2']);
					unset($fields['billing']['wc_billingo_plus_adoszam']);
					unset($fields['order']['order_comments']);
				}
			}

			//Load temporary values for name and email, which was saved when receipt or invoice was switched on checkout
			$tempCustomer = WC()->session->wc_billingo_plus_temp_customer;
			if($tempCustomer) {
				$storedFields = array('billing_first_name', 'billing_last_name', 'billing_email');
				foreach ($storedFields as $storedField) {
					if(isset(WC()->session->wc_billingo_plus_temp_customer[$storedField]) && isset($fields['billing'][$storedField])) {
						$fields['billing'][$storedField]['default'] = WC()->session->wc_billingo_plus_temp_customer[$storedField];
					}
				}
			}

			//Move email field below names
			$fields['billing']['billing_email']['priority'] = 21;

			return $fields;
		}

		public static function receipt_check_with_ajax() {
			check_ajax_referer( 'update-order-review', 'nonce' );

			if($_POST['checked'] == 'invoice') {
				WC()->session->set( 'wc_billingo_plus_receipt', 'invoice' );
			} else {
				WC()->session->set( 'wc_billingo_plus_receipt', 'receipt' );
			}

			//Update name and email, so if it was filled already, it will stay filled after a reload
			$customer = array();

			if ( ! empty( $_POST['billing_first_name'] ) ) {
				$customer['billing_first_name'] = $_POST['billing_first_name'];
			}

			if ( ! empty( $_POST['billing_last_name'] ) ) {
				$customer['billing_last_name'] = $_POST['billing_last_name'];
			}

			if ( ! empty( $_POST['billing_email'] ) ) {
				$customer['billing_email'] = $_POST['billing_email'];
			}

			WC()->session->set( 'wc_billingo_plus_temp_customer', $customer);

			wp_send_json_success();
		}

		public static function save_receipt_session_data( $order_id ) {
			if ( ! empty( WC()->session->wc_billingo_plus_receipt ) ) {

				//Save order type as receipt, if receipt is selected
				if(WC()->session->wc_billingo_plus_receipt == 'receipt') {
					$order = wc_get_order($order_id);
					$order->update_meta_data( '_wc_billingo_plus_type_receipt', true );
					$order->save();
				}

			}
		}

		//If the invoice is already generated without the plugin
		public static function reverse_receipt_with_ajax() {
			check_ajax_referer( 'wc_billingo_generate_invoice', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.', 'wc-billingo-plus' ) );
			}
			$orderid = intval($_POST['order']);
			$order = wc_get_order($orderid);
			$order->delete_meta_data('_wc_billingo_plus_type_receipt');
			$order->save();
			wp_send_json_success();
		}

	}

	WC_Billingo_Plus_Receipt::init();

endif;
