<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Billingo_Plus_Background_Generator', false ) ) :

	class WC_Billingo_Plus_Background_Generator {

		public static function init() {

			//Function to run for scheduled async jobs
			add_action('wc_billingo_plus_generate_document_async', array(__CLASS__, 'generate_document_async'), 10, 3);

			//Add loading indicator to admin bar for background generation
			add_action('admin_bar_menu', array( __CLASS__, 'background_generator_loading_indicator'), 55);
			add_action('wp_ajax_wc_billingo_plus_bg_generate_status', array( __CLASS__, 'background_generator_status' ) );
			add_action('wp_ajax_wc_billingo_plus_bg_generate_stop', array( __CLASS__, 'background_generator_stop' ) );

			//Action that runs for background pdf downloads
			add_action('wc_billingo_plus_download_invoice', array( __CLASS__, 'download_pdf_file'), 10, 2);

			//Update PDF link via heartbeat
			add_filter( 'heartbeat_received', array( __CLASS__, 'add_pdf_to_heartbeat'), 10, 2 );

		}

		//Called by WC Queue to generate documents in the background
		public static function generate_document_async($document_type, $order_id, $options = array()) {
			if(!WC_Billingo_Plus()->is_invoice_generated($order_id, $document_type)) {
				WC_Billingo_Plus()->generate_invoice($order_id, $document_type, $options);
			}
		}

		//Check background generation status with ajax
		public static function background_generator_status() {
			check_ajax_referer( 'wc-billingo-plus-bg-generator', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}
			$response = array();
			if(self::is_async_generate_running()) {
				$response['finished'] = false;
			} else {
				$response['finished'] = true;
			}
			wp_send_json_success($response);
			wp_die();
		}

		//Stop background generation with ajax
		public static function background_generator_stop() {
			check_ajax_referer( 'wc-billingo-plus-bg-generator', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}
			WC()->queue()->cancel_all('wc_billingo_plus_generate_document_async');
			wp_send_json_success();
			wp_die();
		}

		//Get bg generator status
		public static function is_async_generate_running() {
			$documents_pending = WC()->queue()->search(
				array(
					'status' => 'pending',
					'hook' => 'wc_billingo_plus_generate_document_async',
					'per_page' => 1,
				)
			);
			return (bool) count( $documents_pending );
		}

		//Add loading indicator to menu bar
		public static function background_generator_loading_indicator($wp_admin_bar) {
			if(self::is_async_generate_running()) {
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'top-secondary',
						'id' => 'woo-billingo-plus-bg-generate-loading',
						'title' => '<div class="loading"><em></em><strong>'.__('Generating invoices...', 'wc-billingo-plus').'</strong></div><div class="finished"><em></em><strong>'.__('Invoice generation was successful', 'wc-billingo-plus').'</strong></div>',
						'href' => '',
					)
				);

				$text = __('Billingo is generating invoices in the background', 'wc-billingo-plus');
				$text2 = __('Invoices generated successfully. Reload the page to see the invoices.', 'wc-billingo-plus');
				$text_stop = __('Stop', 'wc-billingo-plus');
				$text_refresh = __('Refresh', 'wc-billingo-plus');
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'woo-billingo-plus-bg-generate-loading',
						'id' => 'woo-billingo-plus-bg-generate-loading-msg',
						'title' => '<div class="loading"><span>'.$text.'</span> <a href="#" id="woo-billingo-plus-bg-generate-stop" data-nonce="'.wp_create_nonce( 'wc-billingo-plus-bg-generator' ).'">'.$text_stop.'</a></div><div class="finished"><span>'.$text2.'</span> <a href="#" id="woo-billingo-plus-bg-generate-refresh">'.$text_refresh.'</a></div>',
						'href' => '',
					)
				);
			}
		}

		public static function download_pdf_file($order_id, $document_type) {

			//Create file name
			$pdf_file = WC_Billingo_Plus_Helpers::get_pdf_file_path($document_type, $order_id);
			$pdf_file_name = $pdf_file['name'];

			//Get order
			$order = wc_get_order($order_id);

			//Skip if the order is deleted since for example
			if(!$order) return;

			//Skip if already downloaded
			$current_pdf_value = $order->get_meta('_wc_billingo_plus_'.$document_type.'_pdf');
			if($current_pdf_value && $current_pdf_value != 'pending') return;

			//Get document id
			$document_id = $order->get_meta('_wc_billingo_plus_'.$document_type.'_id');

			//Load billingo API
			$billingo = WC_Billingo_Plus()->get_billingo_api($order);

			//Get pdf as a string
			$download_file = $billingo->download($document_id, $pdf_file);

			//Check for errors
			if(is_wp_error($download_file)) {
				WC_Billingo_Plus()->log_error_messages($download_file, 'download_invoice-'.$order_id);
				return;
			}

			//Since we have the pdf file ready,
			$order->update_meta_data( '_wc_billingo_plus_'.$document_type.'_pdf', $pdf_file_name );

			//Save the order
			$order->save();

			//For plugins to hook in
			do_action('wc_billingo_plus_pdf_downloaded', $pdf_file_name, $order_id, $document_type);

			//And check if we need to send out any WC emails now, since we have the pdf ready to be attached to it
			$pending_emails = $order->get_meta('_wc_billingo_plus_pending_emails');
			if($pending_emails && !empty($pending_emails)) {

				//Send out emails
				$mailer = WC()->mailer();
				$mails = $mailer->get_emails();
				if ( ! empty( $mails ) ) {
					foreach ( $mails as $mail ) {
						if ( in_array($mail->id, $pending_emails) ) {
							$mail->trigger( $order_id, $order );
						}
					}
				}

				//Reset pending emails
				$order->delete_meta_data('_wc_billingo_plus_pending_emails');
				$order->save();

			}

			//And finish
			return;

		}

		public static function add_pdf_to_heartbeat($response, $data) {

			//If we didn't receive our data, don't send any back.
			if ( empty( $data['wc_billingo_plus_pdf_download'] )) {
				return $response;
			}

			//Only on the order page though
			$document_type = sanitize_text_field($data['wc_billingo_plus_pdf_download']);
			$order_id = sanitize_text_field($data['wc_billingo_plus_order_id']);
			$order = wc_get_order($order_id);

			if($order) {
				$pdf_link = WC_Billingo_Plus()->generate_download_link($order, $document_type);
				$response['wc_billingo_plus_pdf_download_link_'.$document_type] = $pdf_link;
			}

			return $response;
		}

	}

	WC_Billingo_Plus_Background_Generator::init();

endif;
