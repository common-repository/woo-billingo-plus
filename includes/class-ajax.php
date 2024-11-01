<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Ajax', false ) ) :

	class WC_Billingo_Plus_Ajax {

		public static function init() {
			add_action( 'wp_ajax_wc_billingo_plus_generate_invoice', array( __CLASS__, 'generate_invoice_with_ajax' ) );
			add_action( 'wp_ajax_wc_billingo_plus_mark_completed', array( __CLASS__, 'mark_completed_with_ajax' ) );
			add_action( 'wp_ajax_wc_billingo_plus_resend_email', array( __CLASS__, 'resend_email_with_ajax' ) );
			add_action( 'wp_ajax_wc_billingo_plus_void_invoice', array( __CLASS__, 'void_invoice_with_ajax' ) );
			add_action( 'wp_ajax_wc_billingo_plus_toggle_invoice', array( __CLASS__, 'toggle_invoice' ) );
		}

		//Generate Invoice with Ajax
		public static function generate_invoice_with_ajax() {
			check_ajax_referer( 'wc_billingo_generate_invoice', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}
			$order_id = intval($_POST['order']);

			//Generate invoice(either final, proform or deposit, based on $_POST['type'])
			$type = sanitize_text_field($_POST['type']);

			$response = WC_Billingo_Plus()->generate_invoice($order_id, $type);
			wp_send_json_success($response);
		}

		//Mark completed with Ajax
		public static function mark_completed_with_ajax() {
			check_ajax_referer( 'wc_billingo_generate_invoice', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}
			$order_id = intval($_POST['order']);
			$date = false;
			if(isset($_POST['date'])) $date = sanitize_text_field($_POST['date']);

			$response = WC_Billingo_Plus()->generate_invoice_complete($order_id, $date);
			wp_send_json_success($response);
		}

		//Resend email notification with Ajax
		public static function resend_email_with_ajax() {
			check_ajax_referer( 'wc_billingo_generate_invoice', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}
			$order_id = intval($_POST['order']);
			$response = WC_Billingo_Plus()->resend_email($order_id);
			wp_send_json_success($response);
		}

		//Cancel Invoice with Ajax
		public static function void_invoice_with_ajax() {
			check_ajax_referer( 'wc_billingo_generate_invoice', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}
			$order_id = intval($_POST['order']);
			$response = WC_Billingo_Plus()->generate_void_invoice($order_id);
			wp_send_json_success($response);
		}

		//If the invoice is already generated without the plugin
		public static function toggle_invoice() {
			check_ajax_referer( 'wc_billingo_generate_invoice', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}
			$orderid = intval($_POST['order']);
			$order = wc_get_order($orderid);
			$note = sanitize_text_field($_POST['note']);
			$invoice_own = $order->get_meta('_wc_billingo_plus_own');
			$response = array();

			if($invoice_own) {
				$response['state'] = 'on';
				$order->delete_meta_data('_wc_billingo_plus_own');
				$response['messages'][] = esc_html__('Invoice generation turned on.','wc-billingo-plus');
			} else {
				$response['state'] = 'off';
				$order->update_meta_data( '_wc_billingo_plus_own', $note );
				$response['messages'][] = esc_html__('Invoice generation turned off.','wc-billingo-plus');
			}

			//Save the order
			$order->save();

			wp_send_json_success($response);
		}

	}

	WC_Billingo_Plus_Ajax::init();

endif;
