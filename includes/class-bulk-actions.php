<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Bulk_Actions', false ) ) :

	class WC_Billingo_Plus_Bulk_Actions {

		public static function init() {
			add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'add_bulk_options'), 20, 1);
			add_filter( 'handle_bulk_actions-edit-shop_order', array( __CLASS__, 'handle_bulk_actions'), 10, 3 );
			add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'add_bulk_options'), 20, 1 );
			add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'handle_bulk_actions'), 10, 3 );
			add_action( 'admin_notices', array( __CLASS__, 'bulk_actions_results') );
			add_action( 'admin_footer', array( __CLASS__, 'generator_modal' ) );
			add_action( 'wp_ajax_wc_billingo_plus_bulk_generator', array( __CLASS__, 'bulk_generator_ajax' ) );

			add_filter( 'woocommerce_admin_order_preview_get_order_details', array( __CLASS__, 'add_invoices_in_preview_modal'), 20, 2 );
			add_action( 'woocommerce_admin_order_preview_start', array( __CLASS__, 'show_invoices_in_preview_modal') );
		}

		public static function add_bulk_options( $actions ) {
			$actions['wc_billingo_plus_bulk_generate'] = __( 'Create Billingo invoices', 'wc-billingo-plus' );
			$actions['wc_billingo_plus_bulk_void'] = __( 'Create Billingo reverse invoices', 'wc-billingo-plus' );
			$actions['wc_billingo_plus_bulk_print'] = __( 'Print Billingo invoices', 'wc-billingo-plus' );
			$actions['wc_billingo_plus_bulk_download'] = __( 'Download Billingo invoices', 'wc-billingo-plus' );

			if(WC_Billingo_Plus_Pro::is_pro_enabled()) {
				$actions['wc_billingo_plus_bulk_generator'] = __( 'Create Billingo documents', 'wc-billingo-plus' );
			}

			return $actions;
		}

		public static function handle_bulk_actions( $redirect_to, $action, $post_ids ) {

			if ( in_array($action, array('wc_billingo_plus_bulk_print', 'wc_billingo_plus_bulk_download'))) {
				global $wc_billingo_plus;
				$bulk_pdf_file = WC_Billingo_Plus_Helpers::get_pdf_file_path('bulk', 0);

				if($wc_billingo_plus->get_option('bulk_download_zip', 'no') == 'yes' && $action == 'wc_billingo_plus_bulk_download' && class_exists('ZipArchive')) {

					//Create an object from the ZipArchive class.
					$zipArchive = new ZipArchive();

					//The full path to where we want to save the zip file.
					$bulk_pdf_file['path'] = str_replace('.pdf', '.zip', $bulk_pdf_file['path']);
					$bulk_pdf_file['name'] = str_replace('.pdf', '.zip', $bulk_pdf_file['name']);

					//Call the open function.
					$status = $zipArchive->open($bulk_pdf_file['path'], ZipArchive::CREATE | ZipArchive::OVERWRITE);

					//An array of files that we want to add to our zip archive.
					foreach ( $post_ids as $order_id ) {
						$order = wc_get_order($order_id);
						$pdf_file = $wc_billingo_plus->generate_download_link($order, false, true);
						if($pdf_file && strpos($pdf_file, '.pdf') !== false) {
							$new_filename = substr($pdf_file, strrpos($pdf_file,'/') + 1);
							$zipArchive->addFile($pdf_file, $new_filename);
							$processed[] = $order_id;
						}
					}

					//Finally, close the active archive.
					$zipArchive->close();

				} else {

					$pdf = new \Clegginabox\PDFMerger\PDFMerger;
					$processed = array();

					//Process selected posts
					foreach ( $post_ids as $order_id ) {
						$order = wc_get_order($order_id);
						$pdf_file = $wc_billingo_plus->generate_download_link($order, 'invoice', true);
						if($pdf_file && strpos($pdf_file, '.pdf') !== false) {
							$pdf->addPDF($pdf_file, 'all');
							$processed[] = $order_id;
						}
					}

					//Create bulk pdf file
					$pdf->merge('file', $bulk_pdf_file['path']);

				}

				//Remove exusting params from url
				$redirect_to = remove_query_arg(array('wc_billingo_plus_bulk_print', 'wc_billingo_plus_bulk_download', 'wc_billingo_plus_bulk_pdf', 'wc_billingo_plus_bulk_generate', 'wc_billingo_plus_bulk_void'), $redirect_to);

				//Set redirect url that will show the download message notice
				$redirect_to = add_query_arg( array($action => count( $post_ids ), 'wc_billingo_plus_bulk_pdf' => urlencode($bulk_pdf_file['name'])), $redirect_to );
				return $redirect_to;

			} else if ($action == 'wc_billingo_plus_bulk_generate' || $action == 'wc_billingo_plus_bulk_void') {

				//Remove existing params from url
				$redirect_to = remove_query_arg(array('wc_billingo_plus_bulk_print', 'wc_billingo_plus_bulk_download', 'wc_billingo_plus_bulk_pdf', 'wc_billingo_plus_bulk_generate', 'wc_billingo_plus_bulk_void'), $redirect_to);

				//What type of invoice?
				$type = 'invoice';
				if($action == 'wc_billingo_plus_bulk_void') $type = 'void';

				//Processed orders
				$processed = array();

				//Check if we need to defer
				$defer_limit = apply_filters('wc_billingo_plus_bulk_generate_defer_limit', 2);

				if(count($post_ids) > $defer_limit) {
					foreach ( $post_ids as $order_id ) {
						WC()->queue()->add( 'wc_billingo_plus_generate_document_async', array( 'invoice_type' => $type, 'order_id' => $order_id ), 'wc-billingo-plus' );
						$processed[] = $order_id;
					}
				} else {
					foreach ( $post_ids as $order_id ) {
						if(!WC_Billingo_Plus()->is_invoice_generated($order_id, $type)) {
							if($type == 'invoice') {
								$test = WC_Billingo_Plus()->generate_invoice($order_id);
							} else {
								WC_Billingo_Plus()->generate_void_invoice($order_id);
							}
							$processed[] = $order_id;
						}
					}
				}

				//Set redirect url that will show the download message notice
				$redirect_to = add_query_arg( array($action => implode('|', $processed)), $redirect_to );
				return $redirect_to;

			} else {
				return $redirect_to;
			}

		}

		public static function bulk_actions_results() {
			if ( !empty( $_REQUEST['wc_billingo_plus_bulk_print'] ) || !empty( $_REQUEST['wc_billingo_plus_bulk_download'] ) ) {
				if(!empty( $_REQUEST['wc_billingo_plus_bulk_print'] )) {
					$print_count = intval( $_REQUEST['wc_billingo_plus_bulk_print'] );
					$type = 'print';
				} else {
					$print_count = intval( $_REQUEST['wc_billingo_plus_bulk_download'] );
					$type = 'download';
				}
				$pdf_file_name = esc_attr( $_REQUEST['wc_billingo_plus_bulk_pdf'] );
				$UploadDir = wp_upload_dir();
				$UploadURL = $UploadDir['baseurl'];
				$location = $UploadURL . '/wc-billingo-plus/';
				$pdf_file_url = $location.'/'.$pdf_file_name;

				include( dirname( __FILE__ ) . '/views/html-notice-bulk.php' );
			}

			if ( !empty( $_REQUEST['wc_billingo_plus_bulk_generate'] ) || !empty( $_REQUEST['wc_billingo_plus_bulk_void'] ) ) {
				$type = 'invoice';
				$invoices = array();

				if(!empty( $_REQUEST['wc_billingo_plus_bulk_void'] )) {
					$type = 'void';
					$invoices = explode('|', $_REQUEST['wc_billingo_plus_bulk_void']);
				} else {
					$invoices = explode('|', $_REQUEST['wc_billingo_plus_bulk_generate']);
				}

				include( dirname( __FILE__ ) . '/views/html-notice-bulk.php' );
			}
		}

		public static function generator_modal() {
			global $typenow;
			if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) ) {
				include( dirname( __FILE__ ) . '/views/html-modal-generator.php' );
			}
		}

		public static function bulk_generator_ajax() {
			check_ajax_referer( 'wc_billingo_plus_bulk_generator', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}

			//Create response
			$response = array();
			$response['error'] = false;
			$response['messages'] = array();

			//Get the selected order ids
			$orders = sanitize_text_field($_POST['orders']);
			$document_type = sanitize_text_field($_POST['options']['document_type']);
			$options = array();
			$option_names = array('account', 'lang', 'note', 'deadline', 'completed', 'doc_type');
			foreach ($option_names as $option_name) {
				$options[$option_name] = sanitize_text_field($_POST['options'][$option_name]);
			}

			//Convert submitted order ids to int array
			$order_ids = array_map('intval', explode(',', $orders));

			//Create an array of order numbers
			$order_numbers = array();
			foreach ($order_ids as $order_id) {
				$order = wc_get_order($order_id);
				$order_numbers[] = $order->get_order_number();
			}

			//Get document label
			$document_types = WC_Billingo_Plus_Helpers::get_document_types();
			$document_label = $document_types[$document_type];

			//Check if we need to defer
			$defer_limit = apply_filters('wc_billingo_plus_bulk_generate_defer_limit', 2);
			if(count($order_ids) > $defer_limit) {
				foreach ( $order_ids as $order_id ) {
					WC()->queue()->add( 'wc_billingo_plus_generate_document_async', array( 'invoice_type' => $document_type, 'order_id' => $order_id, 'options' => $options ), 'wc-billingo-plus' );
					$processed[] = $order_id;
				}
				$response['messages'][] = sprintf( esc_html__( '%1$s order(s) has been selected to create the following documents: %2$s. Documents are being created. Reload this page and you will see a status indicator top right, next to your username.', 'wc-billingo-plus' ), count($processed), $document_label);
			} else {
				$response['generated'] = array();
				foreach ( $order_ids as $order_id ) {
					$order = wc_get_order($order_id);
					if(!WC_Billingo_Plus()->is_invoice_generated($order_id, $document_type)) {
						$geneartor_response = WC_Billingo_Plus()->generate_invoice($order_id, $document_type, $options);
						$geneartor_response['order_number'] = $order->get_order_number();
						$response['generated'][] = $geneartor_response;
						$processed[] = $order_id;
					} else {
						$msg = sprintf(__('%1$s already exists for this order', 'wc-billingo-plus'), $document_label);
						$response['generated'][] = array(
							'order_number' => $order->get_order_number(),
							'error' => true,
							'messages' => array($msg)
						);
					}
				}
				$response['messages'][] = sprintf( esc_html__( '%1$s order(s) has been selected to create the following documents: %2$s.', 'wc-billingo-plus' ), count($processed), $document_label);
			}

			wp_send_json_success($response);
		}


		public static function add_invoices_in_preview_modal( $fields, $order ) {
			$invoice_types = WC_Billingo_Plus_Helpers::get_document_types();
			$invoices = array();

			foreach ($invoice_types as $invoice_type => $invoice_label) {
				if(WC_Billingo_Plus()->is_invoice_generated($order->get_id(), $invoice_type) && !$order->get_meta('_wc_billingo_plus_own')) {
					$invoices[] = [
						'label' => $invoice_label,
						'name' => $order->get_meta('_wc_billingo_plus_'.$invoice_type.'_name'),
						'link' => WC_Billingo_Plus()->generate_download_link($order, $invoice_type)
					];
				}
			}

			if($invoices) {
				$fields['wc_billingo_plus'] = $invoices;
			}

			return $fields;
		}

		public static function show_invoices_in_preview_modal() {
			?>
			<# if ( data.wc_billingo_plus ) { #>
			<div class="wc-order-preview-addresses">
				<div class="wc-order-preview-address">
					<h2><?php esc_html_e( 'Billingo', 'wc-billingo-plus' ); ?></h2>
					<# _.each( data.wc_billingo_plus, function(res, index) { #>
						<strong>{{res.label}}</strong>
						<a href="{{ res.link }}" target="_blank">{{ res.name }}</a>
					<# }) #>
				</div>
			</div>
			<# } #>
			<?php
		}

	}

	WC_Billingo_Plus_Bulk_Actions::init();

endif;
