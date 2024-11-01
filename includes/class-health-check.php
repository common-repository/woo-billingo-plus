<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Health_Check', false ) ) :

	class WC_Billingo_Plus_Health_Check {

		//Init notices
		public static function init() {

			add_filter( 'debug_information', array( __CLASS__, 'debug_info' ) );
			add_filter( 'site_status_tests', array(__CLASS__, 'status_tests') );
			add_action( 'wp_ajax_health-check-wc-billingo_plus_test', array(__CLASS__, 'status_tests_ajax') );
			add_action( 'woocommerce_system_status_report', array( __CLASS__, 'add_status_page_box' ) );

		}

		public static function debug_info($debug) {
			$billingo = array(
				'wc_billingo_plus' => array(
					'label' => esc_html__( 'Woo Billingo Plus', 'wc-billingo-plus' ),
					'description' => sprintf(
						esc_html__(
							'Diagnostic information related to the Woo Billingo Plus extension. If you have some questions or something is not working correctly, please forward these details too: <a href="%1$s" target="_blank" rel="noopener noreferrer">Support</a>',
							'wc-billingo-plus'
						),
						esc_html( 'https://visztpeter.me/' )
					),
					'fields' => self::debug_info_data(),
				),
			);
			$debug = array_merge($debug, $billingo);
			return $debug;
		}

		public static function status_tests($core_tests) {

			$core_tests['direct']['wc_billingo_plus_test'] = array(
				'label' => esc_html__( 'Woo Billingo Plus requirements', 'wc-billingo-plus' ),
				'test'	=> function() {
					$settings = get_option( 'woocommerce_wc_billingo_plus_settings', null );

					$result = array(
						'label'			 => esc_html__( 'Woo Billingo Plus requirements', 'wc-billingo-plus' ),
						'status'			=> 'good',
						'badge'			 => array(
							'label' => esc_html__( 'Woo Billingo Plus', 'wc-billingo-plus' ),
							'color' => 'blue',
						),
						'description' => esc_html__( 'The website and hosting meet all the requirements for a successful invoice generation with the Woo Billingo Plus extension.', 'wc-billingo-plus' ),
					);

					//Username/password
					if($settings['api_key']) {
						//all good
					} else {
						$result['status'] = 'critical';
						$result['badge']['color'] = 'red';
						$result['description'] = __('You need to enter your API V3 Key in the settings to use the <strong>Woo Billingo Plus</strong> extension.', 'wc-billingo-plus');
						$result['actions'] = sprintf(
							'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s <span aria-hidden="true" class="dashicons dashicons-admin-generic"></span></a></p>',
							esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_billingo_plus' ) ),
							esc_html__( 'Settings', 'wc-billingo-plus' )
						);
					}

					if(!$settings['block_uid'] || $settings['block_uid'] == '') {
						$result['status'] = 'critical';
						$result['badge']['color'] = 'red';
						$result['description'] = __('To use <strong>Woo Billingo Plus</strong>, you need to select a document block in the settings.', 'wc-billingo-plus');
						$result['actions'] = sprintf(
							'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s <span aria-hidden="true" class="dashicons dashicons-admin-generic"></span></a></p>',
							esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_billingo_plus' ) ),
							esc_html__( 'Settings', 'wc-billingo-plus' )
						);
					}

					return $result;
				}
			);

			//Debug mode is turned on
			$core_tests['direct']['wc_billingo_plus_debug'] = array(
				'label' => esc_html__( 'Woo Billingo Plus debug mode', 'wc-billingo-plus' ),
				'test'	=> function() {
					$settings = get_option( 'woocommerce_wc_billingo_plus_settings', null );

					$result = array(
						'label'			 => esc_html__('Billingo Plus debug mode is turned off', 'wc-billingo-plus'),
						'status'			=> 'good',
						'badge'			 => array(
							'label' => esc_html__( 'Woo Billingo Plus', 'wc-billingo-plus' ),
							'color' => 'blue',
						),
						'description' => esc_html__( 'A WooCommerce Billingo Plus debug mode is turned off.', 'wc-billingo-plus' ),
						'test'				=> 'wc_billingo_plus_check_debug_mode',
					);

					//If debug mode is turned on
					if($settings['debug'] && $settings['debug'] == 'yes') {
						$result['label'] = esc_html__('Billingo Plus debug mode is turned on', 'wc-billingo-plus');
						$result['status'] = 'critical';
						$result['badge']['color'] = 'red';
						$result['description'] = __("The <strong>Woo Billingo Plus</strong> extension's debug mode is turned on. Make sure you turned this off if you are using it in a live environment.", 'wc-billingo-plus');
						$result['actions'] = sprintf(
							'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s <span aria-hidden="true" class="dashicons dashicons-admin-generic"></span></a></p>',
							esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_billingo_plus' ) ),
							esc_html__( 'Settings', 'wc-billingo-plus' )
						);
					}

					return $result;
				}
			);

			$core_tests['async']['wc_billingo_plus_generate'] = array(
				'label' => esc_html__( 'Woo Billingo Plus invoice generation', 'wc-billingo-plus' ),
				'test'	=> 'wc_billingo_plus_test',
			);

			return $core_tests;
		}

		public static function status_tests_ajax() {
			check_ajax_referer( 'health-check-site-status' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}

			$result = array(
				'label'			 => esc_html__( 'Billingo is connected.', 'wc-billingo-plus' ),
				'status'			=> 'good',
				'badge'			 => array(
					'label' => esc_html__( 'Woo Billingo Plus', 'wc-billingo-plus' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( "The Woo Billingo Plus extension was able to communicate with Billingo with the API key. Looks like everything is working correctly.", 'wc-billingo-plus' )
				),
				'actions'		 => '',
				'test'				=> 'wc_billingo_plus_test_generate_invoice',
			);

			global $wc_billingo_plus;
			$billingo = $wc_billingo_plus->get_billingo_api(false);
			try {
				$billingo_vat_ids = $billingo->get('vat');
			} catch (Exception $e) {
				$error_message = $e->getMessage();

				$result['label'] = __('Billingo is not connected', 'wc-billingo-plus');
				$result['status'] = 'critical';
				$result['badge']['color'] = 'red';
				$result['description'] = sprintf(
					'<p>%s</p><p>%s</p>',
					__( "The Woo Billingo Plus extension was unable to connect with Billingo. This is the original error message:", 'wc-billingo-plus' ),
					esc_html($e->getMessage())
				);

				if (strpos($error_message, 'User not found!') !== false) {

					$result['description'] = sprintf(
						'<p>%s</p><p>%s</p>',
						__( "The Woo Billingo Plus extension was unable to connect with Billingo. The issue is most likely an invalid API key. This is the original error message:", 'wc-billingo-plus' ),
						esc_html($e->getMessage())
					);
				}

				if (strpos($error_message, 'Cannot handle token') !== false) {

					$result['description'] = sprintf(
						'<p>%s</p><p>%s</p>',
						esc_html__( "The Woo Billingo Plus extension was unable to connect with Billingo. Make sure the exact time on your server is the same as the time on Billingo's server. This is the original error message:", 'wc-billingo-plus' ),
						esc_html($e->getMessage())
					);
				}

				if (strpos($error_message, 'User needs to be premium') !== false) {

					$result['description'] = sprintf(
						'<p>%s</p><p>%s</p>',
						__( "The Woo Billingo Plus extension was unable to connect with Billingo because you don't have permission to use the API service. Make sure you have a valid subscription. This is the original error message:", 'wc-billingo-plus' ),
						esc_html($e->getMessage())
					);
				}
			}

			wp_send_json_success($result);
		}

		public static function debug_info_data() {
			$debug_info = array();

			//PRO verzió
			$debug_info['wc_billingo_plus_pro_version'] = array(
				'label'	 => esc_html__('PRO version', 'wc-billingo-plus'),
				'value'	 => intval(WC_Billingo_Plus_Pro::is_pro_enabled())
			);

			//Invoice path
			$UploadDir = wp_upload_dir();
			$UploadURL = $UploadDir['basedir'];
			$location	= realpath($UploadURL . "/wc_billingo_plus/");
			$debug_info['wc_billingo_plus_path'] = array(
				'label'	 => esc_html__('Path', 'wc-billingo-plus'),
				'value'	 => $location
			);

			//IPN URL
			$settings_api = new WC_Billingo_Plus_Settings();

			//Payment options
			$payment_options = get_option('wc_billingo_plus_payment_method_options');
			$debug_info['wc_billingo_plus_payment_options'] = array(
				'label'	 => esc_html__('Payment methods', 'wc-billingo-plus'),
				'value'	 => print_r($payment_options, true)
			);

			//Rounding options
			$rounding_options = get_option('wc_billingo_plus_rounding_options');
			$debug_info['wc_billingo_plus_rounding_options'] = array(
				'label'	 => esc_html__('Rounding', 'wc-billingo-plus'),
				'value'	 => print_r($rounding_options, true)
			);

			//Display saved settings
			$settings = get_option( 'woocommerce_wc_billingo_plus_settings', null );
			$options = $settings_api->form_fields;
			unset($options['private_key']);
			unset($options['api_key']);

			foreach ($options as $option_id => $option) {
				if(!in_array($option['type'], array('pro', 'title', 'payment_methods', 'rounding'))) {
					$debug_info[$option_id] = array(
						'label'	 => esc_html($option['title']),
						'value'	 => esc_html($settings[$option_id])
					);
				}
			}

			return $debug_info;
		}

		public static function add_status_page_box() {
			$debug_info = self::debug_info_data();
			include( dirname( __FILE__ ) . '/views/html-status-report.php' );
		}

	}

	WC_Billingo_Plus_Health_Check::init();

endif;
