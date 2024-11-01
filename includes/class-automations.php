<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Automations', false ) ) :

	class WC_Billingo_Plus_Automations {

		//Setup triggers
		public static function init() {

			//When order created
			add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_order_created' ), 10, 3 );

			//On successful payment
			add_action( 'woocommerce_payment_complete', array( __CLASS__, 'on_payment_complete' ) );

			//On status change
			add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_status_change' ), 10, 3 );

			//On status change
			$statuses = self::get_order_statuses();
			foreach ($statuses as $status => $label) {
				$status = str_replace( 'wc-', '', $status );
				add_action( 'woocommerce_order_status_'.$status, function($order_id) use ($status) {
					self::on_status_change($order_id, $status);
				});
			}

		}

		//Get order statues
		public static function get_order_statuses() {
			if(function_exists('wc_order_status_manager_get_order_status_posts')) {
				$filtered_statuses = array();
				$custom_statuses = wc_order_status_manager_get_order_status_posts();
				foreach ($custom_statuses as $status ) {
					$filtered_statuses[ 'wc-' . $status->post_name ] = $status->post_title;
				}
				return $filtered_statuses;
			} else {
				$statues = wc_get_order_statuses();
				if(WC_Billingo_Plus()->get_option('custom_order_statues', '') != '') {
					$custom_statuses = WC_Billingo_Plus()->get_option('custom_order_statues', '');
					$custom_statuses = explode(',', $custom_statuses); //Split at commas
					$custom_statuses = array_map('trim', $custom_statuses); //Remove whitespace

					foreach ($custom_statuses as $custom_status) {
						if(!isset($statues[$custom_status])) {
							$statues[$custom_status] = $custom_status;
						}
					}
				}
				return apply_filters('wc_billingo_plus_get_order_statuses', $statues);
			}
		}

		public static function on_order_created($order_id, $posted_data, $order) {
			$automations = self::find_automations($order_id, 'order_created');
		}

		public static function on_payment_complete( $order_id ) {
			$automations = self::find_automations($order_id, 'payment_complete');
		}

		public static function on_status_change( $order_id, $new_status ) {
			$automations = self::find_automations($order_id, $new_status);
		}

		public static function find_automations($order_id, $trigger) {

			//Get main data
			$order = wc_get_order($order_id);
			$automations = get_option('wc_billingo_plus_automations');
			$order_details = WC_Billingo_Plus_Conditions::get_order_details($order, 'automations');

			//We will return the matched automations at the end
			$final_automations = array();

			//Loop through each automation
			foreach ($automations as $automation_id => $automation) {

				//Check if trigger is a match. If not, just skip
				if(str_replace( 'wc-', '', $automation['trigger'] ) != str_replace( 'wc-', '', $trigger )) {
					continue;
				}

				//If this is based on a condition
				if($automation['conditional']) {

					//Compare conditions with order details and see if we have a match
					$automation_is_a_match = WC_Billingo_Plus_Conditions::match_conditions($automations, $automation_id, $order_details);

					//If its not a match, continue to next not
					if(!$automation_is_a_match) continue;

					//If its a match, add to found automations
					$final_automations[] = $automation;

				} else {
					$final_automations[] = $automation;
				}

			}

			//If we found some automations, try to generate documents
			if(count($final_automations) > 0) {

				//First sort by document types, so proform and deposit runs before invoice
				$ordered_automations = array();
				$document_order = array('draft', 'proform', 'deposit', 'invoice', 'receipt', 'void', 'offer', 'waybill', 'paid');
				foreach($document_order as $value) {
					foreach ($final_automations as $final_automation) {
						if($final_automation['document'] == $value) {
							$ordered_automations[] = $final_automation;
						}
					}
				}

				//Loop through documents(usually it will be only one, but who knows)
				self::run_automations($order_id, $ordered_automations);

			}

			return $final_automations;
		}

		public static function run_automations($order_id, $automations) {

			//Get data
			$order = wc_get_order($order_id);
			$deferred = (WC_Billingo_Plus()->get_option('defer', 'no') == 'yes');
			$order_total = $order->get_total();

			//Don't create deferred if we are in an admin page and only mark one order completed
			if(is_admin() && isset( $_GET['action']) && $_GET['action'] == 'woocommerce_mark_order_status') {
				$deferred = false;
			}

			//Don't defer if we are just changing one or two order status using bulk actions
			if(is_admin() && isset($_GET['_wp_http_referer']) && isset($_GET['post']) && count($_GET['post']) < 3) {
				$deferred = false;
			}

			//Don't create for free orders
			$is_order_free = false;
			if($order_total == 0 && (WC_Billingo_Plus()->get_option('disable_free_order', 'yes') == 'yes')) {
				$is_order_free = true;
			}

			//Check payment method settings
			$should_generate_auto_invoice = true;
			$payment_method = $order->get_payment_method();
			if(WC_Billingo_Plus()->check_payment_method_options($order->get_payment_method(), 'auto_disabled')) {
				$should_generate_auto_invoice = false;
			}

			//Check for product option
			$order_items = $order->get_items();
			foreach( $order_items as $order_item ) {
				if($order_item->get_product() && $order_item->get_product()->get_meta('wc_billingo_plus_disable_auto_invoice') && $order_item->get_product()->get_meta('wc_billingo_plus_disable_auto_invoice') == 'yes') {
					$should_generate_auto_invoice = false;
				}
			}

			//Allow customization with filters
			$should_generate_auto_invoice = apply_filters('wc_billingo_plus__should_generate_auto_invoice', $should_generate_auto_invoice, $order_id);

			//Loop through automations
			foreach ($automations as $automation) {

				//Check if it was already generated or already marked as paid
				$generated = false;
				if($automation['document'] == 'paid') {
					$generated = ($order->get_meta('_wc_billingo_plus_completed'));
				} else {
					$generated = WC_Billingo_Plus()->is_invoice_generated($order_id, $automation['document']);
				}

				//Skip, if already generated or not needed for free orders
				if($generated || $is_order_free) continue;

				//If its an invoice, check for the should auto generate option
				if($automation['document'] == 'invoice' && !$should_generate_auto_invoice) continue;

				//Check for receipts, if not selected during checkout, skip
				if($automation['document'] == 'receipt' && !$order->get_meta('_wc_billingo_plus_type_receipt')) continue;

				//If we should create an invoice, but the order needs a receipt instead, skip
				if($automation['document'] == 'invoice' && $order->get_meta('_wc_billingo_plus_type_receipt')) continue;

				//If we are still here, we can generate the actual document
				//First, get custom options, like complete date and deadline
				$complete_date = $automation['complete'];
				$complete_date_delay = intval($automation['complete_delay']);
				$deadline = intval($automation['deadline']);
				$paid = $automation['paid'];
				$automation_id = $automation['id'];
				$timestamp = current_time('timestamp');

				//Get dates related to the order
				if($complete_date == 'order_created') {
					$timestamp = $order->get_date_created()->getTimestamp();
				}

				if($complete_date == 'payment_complete' && $order->get_date_paid()) {
					$timestamp = $order->get_date_paid()->getTimestamp();
				}

				//Calculate document dates
				$deadline_delay = $complete_date_delay+$deadline;
				$document_complete_date = date_i18n('Y-m-d', strtotime('+'.$complete_date_delay.' days', $timestamp));
				$document_deadline_date = date_i18n('Y-m-d', strtotime('+'.$deadline_delay.' days', $timestamp));

				//Setup options
				$options = array(
					'deadline' => $deadline_delay,
					'completed' => $document_complete_date,
					'paid' => $paid,
					'automation_id' => $automation_id
				);

				//Two type of automations to run, one is to actually generate the documents, the other is to just mark an invoice as paid
				if($automation['document'] == 'paid') {

					$return_info = WC_Billingo_Plus()->generate_invoice_complete($order_id, $document_complete_date);

				} else {

					if($deferred) {
						WC()->queue()->add( 'wc_billingo_plus_generate_document_async', array( 'invoice_type' => $automation['document'], 'order_id' => $order_id ), 'wc-billingo-plus' );
					} else {
	
						//If PDF download is enabled, this will defer the WooCommerce e-mails, in case it is attached as PDF
						if(WC_Billingo_Plus()->get_option('download_invoice', 'no') == 'yes') {
							$order->update_meta_data('_wc_billingo_plus_auto_gen_pending', true);
							$order->save();
						}
	
						$return_info = WC_Billingo_Plus()->generate_invoice($order_id, $automation['document'], $options);
					}

				}
				
			}
		}
	}

	WC_Billingo_Plus_Automations::init();

endif;
