<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Emails', false ) ) :

	class WC_Billingo_Plus_Emails {

		public static function init() {

			//Attach invoice to emails
			if(WC_Billingo_Plus()->get_option('email_attachment', 'no') == 'yes') {
				if(WC_Billingo_Plus()->get_option('email_attachment_position', 'beginning') == 'beginning') {
					add_action('woocommerce_email_before_order_table', array( __CLASS__, 'email_attachment'), 10, 4);
					add_action('woocommerce_subscriptions_email_order_details', array( __CLASS__, 'email_attachment'), 10, 4);
				} else {
					add_action('woocommerce_email_customer_details', array( __CLASS__, 'email_attachment'), 30, 4);
				}
			}

			//Attach invoice to emails
			if(WC_Billingo_Plus()->get_option('email_attachment_file', 'no') == 'yes') {
				add_filter( 'woocommerce_email_attachments', array( __CLASS__, 'email_attachment_file'), 100, 3 );

				$invoice_types = array('invoice', 'proform', 'void', 'deposit');
				$emails_to_temporarily_disable = array();
				foreach ($invoice_types as $invoice_type) {
					$invoice_email_ids = WC_Billingo_Plus()->get_option('email_attachment_'.$invoice_type, array());
					if($invoice_email_ids && !empty($invoice_email_ids)) {
						$emails_to_temporarily_disable = array_merge($emails_to_temporarily_disable, $invoice_email_ids);
					}
				}
				$emails_to_temporarily_disable = array_unique($emails_to_temporarily_disable);

				//Conditional email sending for attached invoices
				foreach ($emails_to_temporarily_disable as $email_to_temporarily_disable) {
					add_filter( 'woocommerce_email_recipient_'.$email_to_temporarily_disable, array( __CLASS__, 'temporarily_disable_email'), 10, 3 );
				}

			}

			//Forward emails if needed
			if(WC_Billingo_Plus()->get_option('invoice_forward')) {
				add_action( 'wc_billingo_plus_document_created', array( __CLASS__, 'forward_invoices' ) );
			}

		}

		//Email attachment
		public static function email_attachment($order, $sent_to_admin, $plain_text, $email){
			$order_id = $order->get_id();
			$order = wc_get_order($order_id); //The one on the parameter is an older version of $order
			$invoices = array();

			if(isset($email->id)) {
				$invoice_types = array('invoice', 'proform', 'void', 'deposit');
				foreach ($invoice_types as $invoice_type) {
					$invoice_email_ids = WC_Billingo_Plus()->get_option('email_attachment_'.$invoice_type, array());
					if($invoice_email_ids && !empty($invoice_email_ids)) {
						if(in_array($email->id,$invoice_email_ids)) {
							$invoice_name = $order->get_meta('_wc_billingo_plus_'.$invoice_type.'_name');
							if($invoice_name) {
								$invoices[$invoice_type] = array();
								$invoices[$invoice_type]['type'] = $invoice_type;
								$invoices[$invoice_type]['name'] = $order->get_meta('_wc_billingo_plus_'.$invoice_type.'_name');
								$invoices[$invoice_type]['link'] = WC_Billingo_Plus()->generate_download_link($order, $invoice_type);
							}
						}
					}
				}
			}

			if(!empty($invoices)) {
				if($plain_text) {
					wc_get_template( 'emails/plain/email-billingo-section.php', array( 'order' => $order, 'billingo_invoices' => $invoices ), '', plugin_dir_path( __FILE__ ) );
				} else {
					wc_get_template( 'emails/email-billingo-section.php', array( 'order' => $order, 'billingo_invoices' => $invoices ), '', plugin_dir_path( __FILE__ ) );
				}
			}
		}

		//Email attachment file
		public static function email_attachment_file($attachments, $email_id, $order){
			if(!is_a( $order, 'WC_Order' )) return $attachments;
			$order_id = $order->get_id();
			$order = wc_get_order($order_id); //The one on the parameter is an older version of $order

			$invoice_types = array('invoice', 'proform', 'void', 'deposit');
			foreach ($invoice_types as $invoice_type) {
				$invoice_email_ids = WC_Billingo_Plus()->get_option('email_attachment_'.$invoice_type, array());
				if($invoice_email_ids && !empty($invoice_email_ids)) {
					if(in_array($email_id,$invoice_email_ids)) {
						$pdf_name = $order->get_meta('_wc_billingo_plus_'.$invoice_type.'_pdf');
						if(strpos($pdf_name, '.pdf') !== false) {
							$attachments[] = WC_Billingo_Plus()->generate_download_link($order, $invoice_type, true);
						}
					}
				}
			}
			return $attachments;
		}

		public static function temporarily_disable_email($recipient, $order, $email = false) {
			if(!$email) return $recipient;
			$email_id = $email->id;
			$document_type = false;
			if(!$order) return $recipient;
			$order = wc_get_order($order->get_id());

			//If this is an email that we want to attach something
			$invoice_types = array('invoice', 'proform', 'void', 'deposit');
			foreach ($invoice_types as $invoice_type) {
				$invoice_email_ids = WC_Billingo_Plus()->get_option('email_attachment_'.$invoice_type, array());
				if($invoice_email_ids && !empty($invoice_email_ids)) {
					if(in_array($email_id, $invoice_email_ids)) {
						$document_type = $invoice_type;
					}
				}
			}

			//If theres an attachment, check if already exists
			if($document_type) {
				$pdf_link = $order->get_meta('_wc_billingo_plus_'.$document_type.'_pdf');
				$auto_gen_pending = $order->get_meta('_wc_billingo_plus_auto_gen_pending');

				//If the pdf file is still pending, we need to wait a little longer to send this email
				if($auto_gen_pending || ($pdf_link && $pdf_link == 'pending')) {
					$recipient = false;

					//Get currently pending emails
					$pending_emails = $order->get_meta('_wc_billingo_plus_pending_emails');
					if(!$pending_emails) $pending_emails = array();

					//Add current email as pending
					$pending_emails[] = $email_id;

					//Save meta
					$order->update_meta_data('_wc_billingo_plus_pending_emails', $pending_emails);
					$order->save();
				}
			}

			return $recipient;
		}

		//Send email on error
		public static function forward_invoices( $args ) {
			$order = wc_get_order($args['order_id']);
			$document_type = $args['document_type'];
			$document_types = WC_Billingo_Plus_Helpers::get_document_types();
			$document_label = $document_types[$document_type];

			$mailer = WC()->mailer();
			$content = wc_get_template_html( 'includes/emails/invoice-copy.php', array(
				'order' => $order,
				'email_heading' => sprintf(__('The following document was successfully created: %s', 'wc-billingo-plus'), $document_label),
				'plain_text' => false,
				'email' => $mailer,
				'sent_to_admin' => true,
				'document_type' => $document_label,
				'document_name' => $order->get_meta('_wc_billingo_plus_'.$document_type),
				'document_link' => WC_Billingo_Plus()->generate_download_link($order, $document_type)
			), '', plugin_dir_path( __FILE__ ) );
			$recipient = WC_Billingo_Plus()->get_option('invoice_forward');
			$subject = sprintf(__("Billingo document created - %s", 'wc-billingo-plus'), $document_label);
			$headers = "Content-Type: text/html\r\n";
			$attachments = array();
			$attachments[] = WC_Billingo_Plus()->generate_download_link($order, $document_type, true);
			$mailer->send( $recipient, $subject, $content, $headers, $attachments );
		}



	}

	WC_Billingo_Plus_Emails::init();

endif;
