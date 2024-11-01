<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'WC_Billingo_Plus_Vat_Number_Field', false ) ) :
	class WC_Billingo_Plus_Vat_Number_Field {

		//Init notices
		public static function init() {

			//Creates a new field at the checkout page
			add_filter( 'woocommerce_billing_fields' , array( __CLASS__, 'add_vat_number_checkout_field' ) );

			//Validate the vat number on checkout
			add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'vat_number_validate' ), 10, 2);

			//Saves the value to order meta(key _billing_wc_billingo_plus_adoszam)
			add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save_vat_number' ) );

			//Save the VAT number on the user's profile
			add_action( 'woocommerce_checkout_update_user_meta', array( __CLASS__, 'update_customer_meta' ) );

			//Dispaly the VAT number on the user's profile
			add_filter( 'woocommerce_customer_meta_fields', array( __CLASS__, 'customer_meta' ) );

			//On manual order creation, return the vat number too if customer selected
			add_filter( 'woocommerce_ajax_get_customer_details', array( __CLASS__, 'add_vat_to_customer_details'), 10, 3 );

			//Display the VAT number in the admin order page
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_vat_number' ) );
			add_action( 'woocommerce_admin_billing_fields', array( __CLASS__, 'display_vat_number_in_admin' ) );

			//Display the VAT number in the addresses(after the company name, (...))
			add_filter( 'woocommerce_my_account_my_address_formatted_address', array( __CLASS__, 'add_vat_number_to_my_formatted_address'), 10, 3);
			add_filter( 'woocommerce_localisation_address_formats', array( __CLASS__, 'add_vat_number_to_address'));
			add_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'replace_vat_number_in_address'), 10, 2);
			add_filter( 'woocommerce_order_formatted_billing_address', function( $address, $order ) {
				$taxnumber = self::get_order_vat_number($order);
				$address['wc_billingo_plus_adoszam'] = $taxnumber != '' ? $taxnumber : null;
				return $address;
			}, 10, 2 );

			//Just a helper to merge old meta key to the new format
			add_action( 'woocommerce_admin_order_data_after_order_details', function($order){
				if(!$order->get_meta('_billing_wc_billingo_plus_adoszam') && $order->get_meta('billingo_plus_adoszam')) {
					$order->update_meta_data('_billing_wc_billingo_plus_adoszam', $order->get_meta('billingo_plus_adoszam'));
					$order->save();
				}
			});

			//Ajax functions used on frontend
			add_action( 'wp_ajax_wc_billingo_plus_check_vat_number', array( __CLASS__, 'check_vat_number_with_ajax' ) );
			add_action( 'wp_ajax_nopriv_wc_billingo_plus_check_vat_number', array( __CLASS__, 'check_vat_number_with_ajax' ) );

		}

		//Helper function to get vat number(backward compatibility)
		public static function get_order_vat_number($order) {
			$vat_number = $order->get_meta('billingo_plus_adoszam');
			if($order->get_meta('_billing_wc_billingo_plus_adoszam')) {
				$vat_number = $order->get_meta('_billing_wc_billingo_plus_adoszam');
			}
			return $vat_number;
		}

		//Add vat number field to checkout page
		public static function add_vat_number_checkout_field($fields) {
			$fields['billingo_plus_adoszam'] = apply_filters('wc_billingo_plus_tax_number_field', array(
				'label' => esc_html__('VAT number', 'wc-billingo-plus'),
				 'placeholder' => _x('12345678-1-23', 'placeholder', 'wc-billingo-plus'),
				 'required' => false,
				 'class' => array('form-row-wide'),
				 'clear' => true,
				 'priority' => WC_Billingo_Plus()->get_option('vat_number_position', 35)
			));
			return $fields;
		}

		public static function save_vat_number( $order_id ) {
			if ( ! empty( $_POST['billingo_plus_adoszam'] ) ) {
				$order = wc_get_order( $order_id );
				$order->update_meta_data( '_billing_wc_billingo_plus_adoszam', sanitize_text_field( $_POST['billingo_plus_adoszam'] ) );
				$adoszam_data = self::get_vat_number_data(sanitize_text_field($_POST['billingo_plus_adoszam']));
				if($adoszam_data) {
					$order->update_meta_data( '_wc_billingo_plus_adoszam_data', $adoszam_data );
				}
				$order->save();
			}
		}

		public static function display_vat_number($order){
			if(!$order->get_meta('_billing_wc_billingo_plus_adoszam')) {
				if($adoszam = $order->get_meta('billingo_plus_adoszam')) {
					echo '<p><strong>'.__('VAT number', 'wc-billingo-plus').':</strong> ' . $adoszam . '</p>';
				}
			}
		}

		public static function display_vat_number_in_admin($billing_fields){
			$billing_fields['wc_billingo_plus_adoszam'] = array(
				'label' => __( 'VAT number', 'wc-billingo-plus' ),
				'show' => true,
			);
			return $billing_fields;
		}

		public static function vat_number_validate($fields, $errors) {
			if($fields['billingo_plus_adoszam'] && $fields['billing_country'] == 'HU') {

				//Validate general format
				if(preg_match('/^(\d{7})(\d)\-([1-5])\-(0[2-9]|[13][0-9]|2[02-9]|4[0-4]|51)$/', sanitize_text_field($fields['billingo_plus_adoszam']))) {

					//Check with the API too, but only if theres no more errors
					$error_codes = $errors->get_error_codes();
					if(empty( $error_codes )) {
						$adoszam_data = self::get_vat_number_data(sanitize_text_field($fields['billingo_plus_adoszam']));

						//Get ÁFA type
						$afa_type = explode('-', $fields['billingo_plus_adoszam'])[1];
						$afa_type_invalid = false;

						//Check for VAT type in NAV response
						if($adoszam_data && isset($adoszam_data['vat_code']) && $adoszam_data['vat_code'] != intval($afa_type)) {
							$afa_type_invalid = true;
						}

						if($adoszam_data && (!$adoszam_data['valid'] || $afa_type_invalid)) {
							$errors->add( 'validation', apply_filters('wc_billingo_plus_tax_validation_nav_message', esc_html__( 'The VAT number is not valid.', 'wc-billingo-plus'), $fields) );
						}
					}

				} else {
					$errors->add( 'validation', apply_filters('wc_billingo_plus_tax_validation_format_message', esc_html__( 'The VAT number format is not valid.', 'wc-billingo-plus'), $fields) );
				}

			}

			if($fields['billing_country'] == 'HU') {
				if($fields['billing_company'] && !$fields['billingo_plus_adoszam']) {
					$errors->add( 'validation', apply_filters('wc_billingo_plus_tax_validation_required_message', esc_html__( 'If you enter a company name, the VAT number field is required.', 'wc-billingo-plus'), $fields) );
				}

				if($fields['billingo_plus_adoszam'] && !$fields['billing_company'] && WC_Billingo_Plus()->get_option('vat_number_always_show', 'no') == 'yes') {
					$errors->add( 'validation', apply_filters('wc_billingo_plus_company_validation_required_message', esc_html__( 'If you enter a VAT number, the company name field is required.', 'wc-billingo-plus'), $fields) );
				}
			}
		}

		public static function update_customer_meta($customer_id) {
			$billing_tax_number = !empty( $_POST['billingo_plus_adoszam'] ) ? $_POST['billingo_plus_adoszam'] : '';
			update_user_meta( $customer_id, 'billingo_plus_adoszam', sanitize_text_field( $billing_tax_number ) );
		}

		public static function customer_meta($profileFieldArray) {
			$fieldData = array(
				'label' => __('VAT number', 'wc-billingo-plus'),
				'description' => ''
			);
			$profileFieldArray['billing']['fields']['billingo_plus_adoszam'] = $fieldData;
			return $profileFieldArray;
		}

		public static function add_vat_to_customer_details($data, $customer, $user_id) {
			$data['billing']['wc_billingo_plus_adoszam'] = get_user_meta( $user_id, 'billingo_plus_adoszam', true );
			return $data;
		}

		public static function add_vat_number_to_my_formatted_address( $args, $customer_id, $name ) {
			if($name == 'billing') {
				$args['wc_billingo_plus_adoszam'] = get_user_meta( $customer_id, 'billingo_plus_adoszam', true );
			}
			return $args;
		}

		public static function add_vat_number_to_address( $formats ) {
			$formats['HU'] = str_replace("\n{company}", "\n{company}{wc_billingo_plus_adoszam}", $formats['HU']);
			return $formats;
		}

		public static function replace_vat_number_in_address( $replacements, $args ) {
			$replacements['{wc_billingo_plus_adoszam}'] = '';
			if(isset($args['wc_billingo_plus_adoszam']) && !empty($args['wc_billingo_plus_adoszam'])) {
				$replacements['{wc_billingo_plus_adoszam}'] = ' ('.$args['wc_billingo_plus_adoszam'].')';
			}
			return $replacements;
		}

		public static function get_vat_number_data($vat_number) {

			//Create a reuqest id
			$request_id = uniqid('wc_billingo_plus_');

			//Get authnetication data
			$api_url = 'https://api.onlineszamla.nav.gov.hu/invoiceService/v3/queryTaxpayer';
			$config_user = WC_Billingo_Plus()->get_option('vat_number_nav_username', '');
			$config_pass = WC_Billingo_Plus()->get_option('vat_number_nav_password', '');
			$config_tax = WC_Billingo_Plus()->get_option('vat_number_nav_number', '');
			$config_signature = WC_Billingo_Plus()->get_option('vat_number_nav_signature', '');

			//Only proceed if every detail is set
			if(empty($config_user) || empty($config_pass) || empty($config_tax) || empty($config_signature)) return false;

			//Hash the password
			$config_pass = hash("sha512", $config_pass);

			//Get a timestamp in iso format(utc)
			$now = microtime(true);
			$milliseconds = round(($now - floor($now)) * 1000);
			$milliseconds = min($milliseconds, 999);
			$timestamp = gmdate("Y-m-d\TH:i:s", $now) . sprintf(".%03dZ", $milliseconds);

			//Create signature, which is requestID+timestamp(yyyyMMddHHmmss)+signature combined as sha512
			$requestSignature = $request_id;
			$requestSignature .= preg_replace("/\.\d{3}|\D+/", "", $timestamp);
			$requestSignature .= $config_signature;
			$requestSignature = hash('sha3-512' , $requestSignature);

			//Build Xml
			$szamla = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><QueryTaxpayerRequest xmlns:common="http://schemas.nav.gov.hu/NTCA/1.0/common" xmlns="http://schemas.nav.gov.hu/OSA/3.0/api"></QueryTaxpayerRequest>');
			$header = $szamla->addChild('common:header', '', 'http://schemas.nav.gov.hu/NTCA/1.0/common');
			$header->addChild('common:requestId', $request_id);
			$header->addChild('common:timestamp', $timestamp);
			$header->addChild('common:requestVersion', '3.0');
			$header->addChild('common:headerVersion', '1.0');
			$user = $szamla->addChild('common:user', '', 'http://schemas.nav.gov.hu/NTCA/1.0/common');
			$user->addChild('common:login', $config_user);
			$passwordHash = $user->addChild('common:passwordHash', strtoupper($config_pass));
			$passwordHash->addAttribute('cryptoType', 'SHA-512');
			$user->addChild('common:taxNumber', $config_tax);
			$requestSignature = $user->addChild('common:requestSignature', strtoupper($requestSignature));
			$requestSignature->addAttribute('cryptoType', 'SHA3-512');
			$software = $szamla->addChild('software');
			$software->addChild('softwareId', 'WOOCOMBILLINGOPLUS');
			$software->addChild('softwareName', 'Woo Billingo Plus');
			$software->addChild('softwareOperation', 'ONLINE_SERVICE');
			$software->addChild('softwareMainVersion', '1.0');
			$software->addChild('softwareDevName', 'Viszt Péter');
			$software->addChild('softwareDevContact', 'info@visztpeter.me');
			$software->addChild('softwareDevCountryCode', 'HU');

			//Add the tax number(only the first 8 digits)
			$szamla->addChild('taxNumber', substr($vat_number, 0, 8));

			//Build XML
			$xml = $szamla->asXML();

			//And submit
			$response = wp_remote_post( $api_url, array(
				'body' => $xml,
				'headers' => array(
					'Content-Type' => 'text/xml',
					'Accept' => 'text/xml'
				)
			));

			//Check for http errors
			if(is_wp_error($response)) {
				WC_Billingo_Plus()->log_debug_messages($response->get_error_message(), 'billingo-nav-tax-number-validation', true);
				return false;
			}

			//Get response body and status code
			$body = wp_remote_retrieve_body( $response );
			$status_code = wp_remote_retrieve_response_code( $response );

			//If the status code is not 200, it could be an autnetication error or just the API is down
			if($status_code != 200) {
				WC_Billingo_Plus()->log_debug_messages($body, 'billingo-nav-tax-number-validation', true);
				return false;
			}

			//Convert body to json
			$body = str_replace("ns2:","", $body); //This is so php can convert it to normal arrays
			$body = str_replace("ns3:","", $body); //This is so php can convert it to normal arrays
			$body_xml = simplexml_load_string($body);
			$body_xml_json = json_encode($body_xml);
			$body_xml_json = json_decode($body_xml_json,TRUE);

			//Create response object
			$nav_response = array(
				"valid" => 'unknown'
			);

			//Check for validity
			if (array_key_exists('taxpayerValidity', $body_xml_json)) {
				$is_valid = filter_var($body_xml_json['taxpayerValidity'], FILTER_VALIDATE_BOOLEAN);

				$nav_response = array(
					"valid" => $is_valid
				);

				if (array_key_exists('taxpayerData', $body_xml_json)) {
					$nav_response['name'] = $body_xml_json['taxpayerData']['taxpayerName'];

					if(isset($body_xml_json['taxpayerData']) && isset($body_xml_json['taxpayerData']['taxNumberDetail']) && isset($body_xml_json['taxpayerData']['taxNumberDetail']['vatCode'])) {
						$nav_response['vat_code'] = $body_xml_json['taxpayerData']['taxNumberDetail']['vatCode'];
					}

					if(
						isset($body_xml_json['taxpayerData']) &&
						isset($body_xml_json['taxpayerData']['taxpayerAddressList']) &&
						isset($body_xml_json['taxpayerData']['taxpayerAddressList']['taxpayerAddressItem']) &&
						isset($body_xml_json['taxpayerData']['taxpayerAddressList']['taxpayerAddressItem']['taxpayerAddress'])
					) {
						$address = $body_xml_json['taxpayerData']['taxpayerAddressList']['taxpayerAddressItem']['taxpayerAddress'];
						$available_fields = array('countryCode', 'postalCode', 'city', 'streetName', 'publicPlaceCategory', 'number', 'building', 'staircase', 'floor', 'door');
						$nav_response['address'] = array();
						foreach ($available_fields as $field) {
							if(isset($address[$field])) {
								$nav_response['address'][$field] = $address[$field];
							} else {
								$nav_response['address'][$field] = '';
							}
						}
					}
				}
			}

			return $nav_response;
		}

		//Create ajax function for vat number check
		public static function check_vat_number_with_ajax() {
			if($_POST['page'] == 'checkout') {
				check_ajax_referer( 'update-order-review', 'security' );
			} else {
				check_ajax_referer( 'woocommerce-edit_address', 'security' );
			}

			//Submitted vat number
			$vat_number = sanitize_text_field($_POST['vat_number']);
			$vat_number_data = false;

			//Try to validate using NAV api
			if(class_exists( 'WC_Billingo_Plus_Vat_Number_Field' )) {
				$vat_number_data = WC_Billingo_Plus_Vat_Number_Field::get_vat_number_data($vat_number);
			}

			wp_send_json($vat_number_data);
		}

	}

	WC_Billingo_Plus_Vat_Number_Field::init();

endif;
