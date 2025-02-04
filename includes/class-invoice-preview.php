<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Invoice_Preview', false ) ) :

	class WC_Billingo_Plus_Invoice_Preview {

		//Constructor
		public static function init() {

			// Not using Jetpack\Constants here as it can run before 'plugin_loaded' is done.
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX
				|| defined( 'DOING_CRON' ) && DOING_CRON
				|| ! is_admin() ) {
				return;
			}

			//Load template based on get parameter
			add_action( 'admin_init', array( __CLASS__, 'load_preview_template') );

		}

		public static function load_preview_template() {
			if(!isset( $_GET['wc_billingo_plus_preview'] )) {
				return;
			}

			//Get order info
			$order_id = sanitize_text_field($_GET['wc_billingo_plus_preview']);
			$options = array('preview' => true);

			if(isset($_GET['deadline']) && isset($_GET['completed'])) {
				$options['deadline'] = intval($_GET['deadline']);
				$options['completed'] = sanitize_text_field($_GET['completed']);
			}

			if(isset($_GET['note']) && !empty($_GET['note'])) {
				$options['note'] = sanitize_textarea_field($_GET['note']);
			}

			if(isset($_GET['account'])) {
				$options['account'] = sanitize_text_field($_GET['account']);
			}

			//Get invoice XML as json
			$invoice_data = WC_Billingo_Plus()->generate_invoice($order_id, 'invoice', $options);
			$partner = $invoice_data['partner'];
			$invoice = $invoice_data['invoice'];

			//If order and invoice found, show template
			if($invoice) {
				include( dirname( __FILE__ ) . '/views/html-invoice-preview.php' );
				exit();
			} else {
				return;
			}
		}

	}

	WC_Billingo_Plus_Invoice_Preview::init();

endif;
