<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Helpers', false ) ) :

	class WC_Billingo_Plus_Helpers {

		//Get supported languages
		public static function get_supported_languages() {
			return apply_filters('wc_billingo_plus_supported_languages', array(
				'hu' => __( 'Hungarian', 'wc-billingo-plus' ),
				'de' => __( 'German', 'wc-billingo-plus' ),
				'en' => __( 'English', 'wc-billingo-plus' ),
				'it' => __( 'Italian', 'wc-billingo-plus' ),
				'fr' => __( 'French', 'wc-billingo-plus' ),
				'hr' => __( 'Croatian', 'wc-billingo-plus' ),
				'ro' => __( 'Romanian', 'wc-billingo-plus' ),
				'sk' => __( 'Slovak', 'wc-billingo-plus' )
			));
		}

		//Get document type ids and labels
		public static function get_document_types() {
			return apply_filters('wc_billingo_plus_document_types', array(
				'proform' => esc_html__('Proforma invoice','wc-billingo-plus'),
				'invoice' => esc_html__('Invoice','wc-billingo-plus'),
				'void' => esc_html__('Reverse invoice','wc-billingo-plus'),
				'draft' => esc_html__('Draft','wc-billingo-plus'),
				'deposit' => esc_html__('Deposit invoice','wc-billingo-plus'),
				'receipt' => esc_html__('Receipt','wc-billingo-plus'),
				//'waybill' => esc_html__('Waybill','wc-billingo-plus'),
				//'offer' => esc_html__('Offer','wc-billingo-plus'),
			));
		}

		//Duplicate wc_display_item_meta for small customizations(mainly to hide the backordered meta info)
		public static function get_item_meta($item, $args) {
			$strings = array();
			$html = '';
			$args = wp_parse_args(
				$args,
				array(
					'before' => '<ul class="wc-item-meta"><li>',
					'after' => '</li></ul>',
					'separator' => '</li><li>',
					'echo' => true,
					'autop' => false,
					'label_before' => '<strong class="wc-item-meta-label">',
					'label_after' => ':</strong> ',
				)
			);

			foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
				if(__( 'Backordered', 'woocommerce' ) == $meta->key) continue;
				$value = wp_kses_post( make_clickable( trim( $meta->display_value ) ) );
				$strings[] = wp_kses_post( $meta->display_key ) . $args['label_after'] . $value;
			}

			if ( $strings ) {
				$html = $args['before'] . implode( $args['separator'], $strings );
			}

			return apply_filters( 'woocommerce_display_item_meta', $html, $item, $args );
		}

		public static function get_billingo_payment_methods() {
			$payment_methods = apply_filters('wc_billingo_plus_payment_methods', array(
				'wire_transfer' => __('Wire transfer', 'wc-billingo-plus'),
				'aruhitel' => __('Loan', 'wc-billingo-plus'),
				'bankcard' => __('Credit card', 'wc-billingo-plus'),
				'barion' => __('Barion', 'wc-billingo-plus'),
				'barter' => __('Barter', 'wc-billingo-plus'),
				'ep_kartya' => __('Health insurance card', 'wc-billingo-plus'),
				'elore_utalas' => __('Advance payment', 'wc-billingo-plus'),
				'kompenzacio' => __('Compensation', 'wc-billingo-plus'),
				'coupon' => __('Coupon', 'wc-billingo-plus'),
				'cash' => __('Cash', 'wc-billingo-plus'),
				'levonas' => __('Deduction', 'wc-billingo-plus'),
				'online_bankcard' => __('Online credit card', 'wc-billingo-plus'),
				'paypal' => __('PayPal', 'wc-billingo-plus'),
				'paypal_utolag' => __('PayPal post-paid', 'wc-billingo-plus'),
				'payu' => __('PayU', 'wc-billingo-plus'),
				'paylike' => __('Paylike', 'wc-billingo-plus'),
				'payoneer' => __('Payoneer', 'wc-billingo-plus'),
				'pick_pack_pont' => __('Pick Pack Pont', 'wc-billingo-plus'),
				'postai_csekk' => __('Post office cheque', 'wc-billingo-plus'),
				'postautalvany' => __('Postal voucher', 'wc-billingo-plus'),
				'szep_card' => __('SZÉP card', 'wc-billingo-plus'),
				'skrill' => __('Skrill', 'wc-billingo-plus'),
				'transferwise' => __('Transferwise', 'wc-billingo-plus'),
				'upwork' => __('Upwork', 'wc-billingo-plus'),
				'utalvany' => __('Voucher', 'wc-billingo-plus'),
				'cash_on_delivery' => __('Cash on delivery', 'wc-billingo-plus'),
				'valto' => __('Bill of exchange', 'wc-billingo-plus'),
			));

			return $payment_methods;
		}

		public static function get_billingo_payment_method_label($id) {
			$payment_methods = self::get_billingo_payment_methods();
			if($payment_methods[$id]) {
				return $payment_methods[$id];
			} else {
				return false;
			}
		}

		//Get the language of the invoice
		public static function get_order_language($order) {
			$lang_code = WC_Billingo_Plus()->get_option('language', 'hu');
			return apply_filters('wc_billingo_plus_get_order_language', $lang_code, $order);
		}

		//Query for the VAT ids
		public static function get_billingo_vat_ids($skip_numeric = false) {
			$vat_ids = apply_filters('wc_billingo_plus_vat_ids', array(
				'0%' => '0%',
				'1%' => '1%',
				'10%' => '10%',
				'11%' => '11%',
				'12%' => '12%',
				'13%' => '13%',
				'14%' => '14%',
				'15%' => '15%',
				'16%' => '16%',
				'17%' => '17%',
				'18%' => '18%',
				'19%' => '19%',
				'2%' => '2%',
				'20%' => '20%',
				'21%' => '21%',
				'22%' => '22%',
				'23%' => '23%',
				'24%' => '24%',
				'25%' => '25%',
				'26%' => '26%',
				'27%' => '27%',
				'3%' => '3%',
				'4%' => '4%',
				'5%' => '5%',
				'6%' => '6%',
				'7%' => '7%',
				'8%' => '8%',
				'9%' => '9%',
				'AAM' => 'AAM',
				'AM' => 'AM',
				'EU' => 'EU',
				'EUK' => 'EUK',
				'F.AFA' => 'F.AFA',
				'FAD' => 'FAD',
				'K.AFA' => 'K.AFA',
				'MAA' => 'MAA',
				'TAM' => 'TAM',
				'ÁKK' => 'ÁKK',
				'ÁTHK' => 'ÁTHK',
			));

			if ($skip_numeric) {
				foreach ($vat_ids as $k => $v) {
					if (strpos($v, '%') !== false) {
						unset($vat_ids[$k]);
					}
				}
			}
			return $vat_ids;
		}

		//Query for the VAT ids
		public static function get_billingo_entitlements() {
			$entitlements = apply_filters('wc_billingo_plus_entitlements', array(
				'AAM' => 'Alanyi adómentesség',
				'ANTIQUES' => 'Különbözet szerinti szabályozás: gyűjteménydarabok és régiségek',
				'ARTWORK' => 'Különbözet szerinti szabályozás: műalkotások',
				'ATK' => 'Áfa tv. tárgyi hatályán kívüli ügylet',
				'EAM' => 'Áfamentes termékexport, azzal egy tekintet alá eső értékesítések, nemzetközi közlekedéshez kapcsolódó áfamentes ügyletek (Áfa tv. 98-109. §)',
				'EUE' => 'EU más tagállamában áfaköteles (áfa fizetésére az értékesítő köteles)',
				'EUFAD37' => 'Áfa tv. 37. § (1) bekezdése alapján a szolgáltatás teljesítése helye az EU más tagállama (áfa fizetésére a vevő köteles)',
				'EUFADE' => 'Áfa tv. szerint egyéb rendelkezése szerint a teljesítés helye EU más tagállama (áfa fizetésére a vevő köteles)',
				'HO' => 'Áfa tv. szerint EU-n kívül teljesített ügylet',
				'KBAET' => 'Más tagállamba irányuló áfamentes termékértékesítés (Áfa tv. 89. §)',
				'NAM_1' => 'Áfamentes közvetítői tevékenység (Áfa tv. 110. §)',
				'NAM_2' => 'Termékek nemzetközi forgalmához kapcsolódó áfamentes ügylet (Áfa tv. 111-118. §)',
				'SECOND_HAND' => 'Különbözet szerinti szabályozás - használt cikkek -',
				'TAM' => 'Tevékenység közérdekű jellegére vagy egyéb sajátos jellegére tekintettel áfamentes (Áfa tv. 85-87.§)',
				'TRAVEL_AGENCY' => 'Különbözet szerinti szabályozás - utazási irodák -'
			));
			return $entitlements;
		}

		public static function check_legacy_invoice_id($invoice_id, $order_id) {
			$invoice_types = WC_Billingo_Plus_Helpers::get_document_types();
			$has_new_meta = false;
			$order = wc_get_order($order_id);

			//If the URL meta exists, its a new invoice id
			if($order->get_meta( '_wc_billingo_plus_invoice_url' ) || $order->get_meta( '_wc_billingo_plus_invoice_id_v3' )) {
				$has_new_meta = true;
			}

			//If it doesn't, get a new id
			if(!$has_new_meta) {

				//Load billingo API
				$billingo = WC_Billingo_Plus()->get_billingo_api($order);

				//Get blocks
				$new_invoice_id = $billingo->get('utils/convert-legacy-id/'.$invoice_id);
				if ( is_wp_error( $new_invoice_id ) ) {
					WC_Billingo_Plus()->log_error_messages($new_invoice_id, 'get_billingo_convert_legacy_id');
				} else {

					if(isset($new_invoice_id['id']) && !empty($new_invoice_id['id'])) {
						$invoice_id = $new_invoice_id['id'];
						$order->update_meta_data('_wc_billingo_plus_invoice_id', $invoice_id);
						$order->update_meta_data('_wc_billingo_plus_invoice_id_v3', true);
						$order->save();
					}
				}

			}

			return $invoice_id;
		}

		//Get file path for pdf files
		public static function get_pdf_file_path($type, $order_id, $invoice = false) {
			$upload_dir = wp_upload_dir( null, false );
			$location = $upload_dir['basedir'] . '/wc-billingo-plus/';
			$random_file_name = substr(md5(rand()),5);
			$pdf_file_name = implode( '-', array( $type, $order_id, $random_file_name ) ).'.pdf';
			$pdf_file_name = apply_filters('wc_billingo_plus_pdf_file_name', $pdf_file_name, $type, $order_id, $invoice);
			$pdf_file_path = $location.'/'.$pdf_file_name;

			//Group by year and month if needed
			if (get_option('uploads_use_yearmonth_folders') ) {
				$time = current_time( 'mysql' );
				$y = substr( $time, 0, 4 );
				$m = substr( $time, 5, 2 );
				$subdir = "/$y/$m";
				$pdf_file_name = $y.'/'.$m.'/'.$pdf_file_name;
				$file_dir = $location.$y.'/'.$m.'/';
			} else {
				$file_dir = $location;
			}

			return apply_filters('wc_billingo_plus_pdf_file_path', array('name' => $pdf_file_name, 'path' => $location.$pdf_file_name, 'file_dir' => $file_dir), $type, $order_id, $invoice);
		}

		//Replace placeholders in invoice note
		public static function replace_note_placeholders($note, $order) {

			//Setup replacements
			$note_replacements = apply_filters('wc_billingo_plus_get_order_note_placeholders', array(
				'{customer_email}' => $order->get_billing_email(),
				'{customer_phone}' => $order->get_billing_phone(),
				'{order_number}' => $order->get_order_number(),
				'{transaction_id}' => $order->get_transaction_id(),
				'{shipping_address}' => preg_replace('/\<br(\s*)?\/?\>/i', "\n", $order->get_formatted_shipping_address()),
				'{customer_notes}' => $order->get_customer_note()
			), $order);

			//Replace stuff:
			$note = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $note);

			//Replace shortcodes
			$note = do_shortcode($note);

			//Return fixed note
			return $note;
		}

		public static function get_payment_methods() {
			$available_gateways = WC()->payment_gateways->payment_gateways();
			$payment_methods = array();
			foreach ($available_gateways as $available_gateway) {
				if($available_gateway->enabled == 'yes') {
					$payment_methods[$available_gateway->id] = $available_gateway->title;
				}
			}
			return $payment_methods;
		}

		public static function get_shipping_methods() {
			$active_methods = array();
			$custom_zones = WC_Shipping_Zones::get_zones();
			$worldwide_zone = new WC_Shipping_Zone( 0 );
			$worldwide_methods = $worldwide_zone->get_shipping_methods();

			foreach ( $custom_zones as $zone ) {
				$shipping_methods = $zone['shipping_methods'];
				foreach ($shipping_methods as $shipping_method) {
					if ( isset( $shipping_method->enabled ) && 'yes' === $shipping_method->enabled ) {
						$method_title = $shipping_method->method_title;
						$active_methods[$shipping_method->id.':'.$shipping_method->instance_id] = $method_title.' ('.$zone['zone_name'].')';
					}
				}
			}

			foreach ($worldwide_methods as $shipping_method_id => $shipping_method) {
				if ( isset( $shipping_method->enabled ) && 'yes' === $shipping_method->enabled ) {
					$method_title = $shipping_method->method_title;
					$active_methods[$shipping_method->id.':'.$shipping_method->instance_id] = $method_title.' (Worldwide)';
				}
			}

			return $active_methods;
		}

		//Helper function to check vat override settings
		public static function check_vat_override($product_item, $line_item_type, $auto_vat, $order, $order_item = false) {

			//Set new vat
			$vat = $auto_vat;
			$entitlement = false;

			//Check for overrides
			$vat_overrides = get_option('wc_billingo_plus_vat_overrides');

			//Skip if theres no override setup
			if((WC_Billingo_Plus()->get_option('vat_overrides_custom', 'no') == 'no') || !$vat_overrides) {
				return $product_item;
			}

			//Get order type
			$order_details = WC_Billingo_Plus_Conditions::get_order_details($order, 'vat_overrides');

			//Check for product specific stuff
			if($line_item_type == 'product' && $order_item && $order_item->get_product()) {
				$product = $order_item->get_product();
				$categories = $product->get_category_ids();
				$order_details['product_categories'] = $categories;
			}

			//We will return the matched automations at the end
			$final_automations = array();

			//Loop through each automation
			foreach ($vat_overrides as $automation_id => $automation) {

				//Check if trigger is a match. If not, just skip
				if($automation['line_item'] != $line_item_type) {
					continue;
				}

				if($automation['conditional']) {

					//Compare conditions with order details and see if we have a match
					$automation_is_a_match = WC_Billingo_Plus_Conditions::match_conditions($vat_overrides, $automation_id, $order_details);

					//If its not a match, continue to next not
					if(!$automation_is_a_match) continue;

					//If its a match, add to found automations
					$final_automations[] = $automation;

				} else {

					$final_automations[] = $automation;

				}

			}

			//If we found some automations, try to set it
			if(count($final_automations) > 0) {
				foreach ($final_automations as $final_automation) {
					$vat = $final_automation['vat_type'];

					if(isset($final_automation['entitlement']) && $final_automation['entitlement'] && $final_automation['entitlement'] != '') {
						$entitlement = $final_automation['entitlement'];
					}
				}
			}

			//Set new vat id
			$product_item['vat'] = $vat;

			//Set entitlement if needed
			if($entitlement) {
				$product_item['entitlement'] = $entitlement;
			}

			//Return line item
			return $product_item;
		}

		//Helper function to get stored partner id from customer
		//Compares the address, so if it was changed since last time, a new customer will be created instead
		public static function get_billingo_partner_id_from_order($order, $partner_data, $block_id) {
			$partner_address_hash = md5(json_encode($partner_data));
			$billingo_partner_id = false;

			if($customer_id = $order->get_customer_id()) {

				//Get saved meta, which is blockID|partnerDataHash|billingoPartnerID
				$wc_billingo_partner_data = get_user_meta($customer_id, '_wc_billingo_plus_partner_data', true);

				//If we have a saved billingo partner info
				if(! empty( $wc_billingo_partner_data )) {

					//If we have a record with the same block id
					if(isset($wc_billingo_partner_data[$block_id])) {
						$wc_billingo_partner_info = explode('|', $wc_billingo_partner_data[$block_id]);
						$address_hash = $wc_billingo_partner_info[0];

						//If saved and equal to the current one, use the saved partner id, else, we will create a new partner
						if($partner_address_hash == $address_hash) {
							$billingo_partner_id = $wc_billingo_partner_info[1];
						}

					}

				}
			}

			return $billingo_partner_id;
		}

		public static function save_billingo_partner_id($block_id, $partner_data, $partner_id, $customer_id) {
			$partner_data_hash = md5(json_encode($partner_data));
			$wc_billingo_partner_data = get_user_meta($customer_id, '_wc_billingo_plus_partner_data', true);
			if(empty( $wc_billingo_partner_data )) {
				$wc_billingo_partner_data = array();
			}
			$wc_billingo_partner_data[$block_id] = $partner_data_hash.'|'.$partner_id;
			update_user_meta($customer_id, '_wc_billingo_plus_partner_data', $wc_billingo_partner_data);
		}

		public static function get_default_complete_date($order) {
			$default = WC_Billingo_Plus()->get_option('default_complete_date', 'now');
			$timestamp = current_time('timestamp');

			//Get dates related to the order
			if($default == 'order_created') {
				$timestamp = $order->get_date_created()->getTimestamp();
			}

			if($default == 'payment_complete' && $order->get_date_paid()) {
				$timestamp = $order->get_date_paid()->getTimestamp();
			}

			//Calculate document dates
			$deadline_delay = 0;
			$document_complete_date = date_i18n('Y-m-d', strtotime('+'.$deadline_delay.' days', $timestamp));

			return $document_complete_date;
		}

		public static function replace_coupon_placeholders($note, $order) {

			//Get coupon code details
			$coupons = $order->get_coupon_codes();
			$descriptions = array();
			$amounts = array();
			if( $coupons ) {
				foreach( $coupons as $coupon ) {
					$coupon_obj = new WC_Coupon($coupon);
					$coupon_desc = $coupon_obj->get_description();
					if( !in_array( $coupon_desc, $descriptions ) ) array_push( $descriptions, $coupon_desc );
					if ( $coupon_obj->get_discount_type() == 'percent' ){
						$amounts[] = $coupon_obj->get_amount().'%';
					}

					if( $coupon_obj->get_discount_type() == 'fixed_cart' || $coupon_obj->get_discount_type() == 'fixed_product' ){
						$amounts[] = wc_price($coupon_obj->get_amount());
					}
				}
			}

			//Setup replacements
			$note_replacements = array(
				'{coupon_description}' => implode(', ', $descriptions),
				'{coupon_code}' => implode(', ', $coupons),
				'{coupon_amount}' => implode(', ', $amounts),
			);

			//Replace stuff:
			$note = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $note);

			//Replace shortcodes
			$note = do_shortcode($note);

			//Return fixed note
			return $note;
		}

	}

endif;
