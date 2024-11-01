<?php
/*
Plugin Name: Woo Billingo Plus
Plugin URI: http://visztpeter.me
Description: Billingo integráció WooCommerce-hez rengeteg extra funkcióval
Author: Viszt Péter
Version: 4.7.5
Text Domain: wc-billingo-plus
Domain Path: /languages/
WC requires at least: 3.0.0
WC tested up to: 9.2.3
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! defined( 'WC_BILLINGO_PLUS_PLUGIN_FILE' ) ) {
	define( 'WC_BILLINGO_PLUS_PLUGIN_FILE', __FILE__ );
}

//HPOS compatibility
use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

//Generate stuff on plugin activation
function wc_billingo_plus_activate() {
	$upload_dir = wp_upload_dir();

	$files = array(
		array(
			'base' 		=> $upload_dir['basedir'] . '/wc-billingo-plus',
			'file' 		=> 'index.html',
			'content' 	=> ''
		)
	);

	foreach ( $files as $file ) {
		if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
			if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
				fwrite( $file_handle, $file['content'] );
				fclose( $file_handle );
			}
		}
	}

	//Save DB version for comparisons
	update_option('_wc_billingo_plus_db_version', 'v3');
}
register_activation_hook( __FILE__, 'wc_billingo_plus_activate' );

class WC_Billingo_Plus {

	public static $plugin_prefix;
	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_basename;
	public static $version;
	protected static $billingo_api = null;
	protected static $_instance = null;

	//Get main instance
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	//Construct
	public function __construct() {

		//Default variables
		self::$plugin_prefix = 'wc_billingo_plus_';
		self::$plugin_basename = plugin_basename(__FILE__);
		self::$plugin_url = plugin_dir_url(self::$plugin_basename);
		self::$plugin_path = trailingslashit(dirname(__FILE__));
		self::$version = '4.7.5';

		//Load billingo API
		if(apply_filters('wc_billingo_plus_load_fpdi', true)) {
			require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
		}

		//Helper functions
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-pro.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-helpers.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-conditions.php' );

		//Plugin loaded
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		//HPOS compatibility
		add_action( 'before_woocommerce_init', array( $this, 'woocommerce_hpos_compatible' ) );

		//Update notice, if needed
		add_action( 'in_plugin_update_message-woo-billingo-plus/index.php', array( $this, 'in_plugin_update_message' ), 10, 2 );

		//Include compatibility modules
		require_once( plugin_dir_path( __FILE__ ) . 'includes/compatibility/class-compatibility.php' );
		WC_Billingo_Plus_Compatibility::instance();

	}

	//Show upgrade notice for plugin updates in the future
	public function in_plugin_update_message( $data, $response ) {
		if( isset( $data['upgrade_notice'] ) ) {
			printf(
				'<div class="update-message">%s</div>',
				wpautop( $data['upgrade_notice'] )
			);
		}
	}

	//Load plugin stuff
	public function init() {

		//Load locale
		load_plugin_textdomain( 'wc-billingo-plus', false, basename( dirname( __FILE__ ) ) . '/languages/' );

		//Load custom api
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-api.php' );

		//Background invoice generator
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-background-generator.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-background-migrate.php' );

		//Functions related to emails and ajax
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-emails.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-ajax.php' );

		//Check if pro enabled
		$is_pro = WC_Billingo_Plus_Pro::is_pro_enabled();

		// Load includes
		if(is_admin()) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-settings.php' );
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-admin-notices.php' );
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-product-options.php' );
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-bulk-actions.php' );
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-grouped-invoice.php' );
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-invoice-preview.php' );
		}

		//Load custom webhooks
		if($is_pro) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-webhooks.php' );
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-ipn.php' );
		}

		//Load if new automations used
		if($is_pro && $this->get_option('auto_invoice_custom', 'no') == 'yes') {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-automations.php' );
		}

		//Health check for WP 5.2+
		global $wp_version;
		if ( version_compare( $wp_version, '5.2-alpha', 'ge' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-health-check.php' );
		}

		//Plugin links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		//Settings page
		if(is_admin()) {
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		}

		//Admin functions
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'add_meta_boxes', array( $this, 'metabox' ), 10, 2 );

		//Create a hook based on the status setup in settings to auto-generate invoice
		if($is_pro && $this->get_option('auto_invoice_custom', 'no') != 'yes') {

			//For proforms
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_order_processing' ) );

			//New option exists
			$auto_invoice_statuses = get_option('wc_billingo_plus_auto_invoice_status');
			$auto_void_statuses = get_option('wc_billingo_plus_auto_void_status');
			if($auto_invoice_statuses) {
				if(empty($auto_invoice_statuses)) $auto_invoice_statuses = array();
				if(empty($auto_void_statuses)) $auto_void_statuses = array();
			} else {

				//Check if old option exists
				if($this->get_option('auto_generate', 'no') == 'yes') {
					$auto_invoice_statuses = array($this->get_option('auto_invoice_status', 'no'));
					$auto_void_statuses = array($this->get_option('auto_void_status', 'no'));
				} else {
					$auto_invoice_statuses = array();
					$auto_void_statuses = array();
				}

			}

			//Auto invoice
			foreach ($auto_invoice_statuses as $auto_invoice_status) {
				$order_auto_invoice_status = str_replace( 'wc-', '', $auto_invoice_status );
				add_action( 'woocommerce_order_status_'.$order_auto_invoice_status, array( $this, 'on_order_complete' ) );
			}

			//Auto void
			foreach ($auto_void_statuses as $auto_void_status) {
				$order_auto_void_status = str_replace( 'wc-', '', $auto_void_status );
				if($order_auto_void_status != 'no') {
					add_action( 'woocommerce_order_status_'.$order_auto_void_status, array( $this, 'on_order_deleted' ) );
				}
			}

		}

		//Order list button
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_listing_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_listing_actions' ), 10, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_listing_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'add_listing_actions' ), 10, 2 );
		add_filter('woocommerce_my_account_my_orders_actions', array( $this, 'orders_download_button' ), 10,2);
		if($this->get_option('tools', 'no') == 'yes') add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_listing_actions_2' ) );

		//VAT form
		if($this->get_option('vat_number_form', 'no') == 'yes') {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-vat-number.php' );
		}

		//Frontend scripts & css
		if(($this->get_option('receipt') == 'yes' && $is_pro) || $this->get_option('vat_number_form', 'no') == 'yes') {
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_js' ));
		}

		//E-Nyugta
		if($this->get_option('receipt') == 'yes' && $is_pro) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-receipt.php' );
		}

		//Disable invoices on free orders
		add_action('woocommerce_checkout_order_processed', array( $this, 'disable_invoice_for_free_order' ), 10, 3);

	}

	//Declares WooCommerce HPOS compatibility.
	public function woocommerce_hpos_compatible() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	//Integration page
	public function add_integration( $integrations ) {
		$integrations[] = 'WC_Billingo_Plus_Settings';
		return $integrations;
	}

	//Add CSS & JS
	public function admin_init() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script( 'wc_billingo_plus_print_js', plugins_url( '/assets/js/print'.$suffix.'.js',__FILE__ ), array('jquery'), WC_Billingo_Plus::$version, TRUE );
		wp_enqueue_script( 'wc_billingo_plus_admin_js', plugins_url( '/assets/js/admin'.$suffix.'.js',__FILE__ ), array('jquery'), WC_Billingo_Plus::$version, TRUE );
		wp_enqueue_style( 'wc_billingo_plus_admin_css', plugins_url( '/assets/css/admin.css',__FILE__ ), array(), WC_Billingo_Plus::$version );

		$wc_billingo_plus_local = array(
			'loading' => plugins_url( '/assets/images/ajax-loader.gif',__FILE__ ),
			'settings_link' => esc_url(admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_billingo_plus' ))
		);
		wp_localize_script( 'wc_billingo_plus_admin_js', 'wc_billingo_plus_params', $wc_billingo_plus_local );

		//Store and check version number
		$version = get_option('wc_billingo_plus_version_number');

		//If plugin is updated, schedule imports(maybe a new provider was added for example)
		if(!$version || ($version != self::$version)) {
			update_option('wc_billingo_plus_version_number', self::$version);

			//And check if its an old pro version
			WC_Billingo_Plus_Pro::migrate_old_pro();
		}
	}

	//Frontend JS
	public function frontend_js() {
		if(is_checkout() || is_account_page()) {
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			wp_enqueue_style( 'wc_billingo_plus_frontend_css', plugins_url( '/assets/css/frontend.css',__FILE__ ), array(), WC_Billingo_Plus::$version );
			wp_enqueue_script( 'wc_billingo_plus_frontend_js', plugins_url( '/assets/js/frontend'.$suffix.'.js',__FILE__ ), array(), WC_Billingo_Plus::$version );

			$wc_billingo_plus_local = array(
				'always_show' => $this->get_option('vat_number_always_show', 'no'),
				'autofill' => $this->get_option('vat_number_autofill', 'no'),
				'ajax_url' => admin_url( 'admin-ajax.php' )
			);
			wp_localize_script( 'wc_billingo_plus_frontend_js', 'wc_billingo_plus_vat_number_params', $wc_billingo_plus_local );
		}
	}

	//Meta box on order page
	public function metabox( $post_type, $post_or_order_object ) {
		if ( class_exists( CustomOrdersTableController::class ) && function_exists( 'wc_get_container' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			$screen = wc_get_page_screen_id( 'shop-order' );
		} else {
			$screen = 'shop_order';
		}

		add_meta_box('wc_billingo_plus_metabox', __('Woo Billingo Plus', 'wc-billingo-plus'), array( $this, 'render_meta_box_content' ), $screen, 'side');
		add_meta_box('wc_billingo_plus_metabox', __('Woo Billingo Plus', 'wc-billingo-plus'), array( $this, 'render_meta_box_content' ), 'woocommerce_page_wc-orders--awcdp_payment', 'side');

		$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		if(is_a( $order, 'WC_Order' )) {
			$vat_number_data = $order->get_meta('_wc_billingo_plus_adoszam_data');
			if($vat_number_data) {
				add_meta_box('wc_billingo_plus_vat_number_metabox', __('VAT Number info', 'wc-billingo-plus'), array( $this, 'render_meta_box_content_vat_number' ), $screen, 'side');
			}
		}
	}

	//Render metabox content
	public function render_meta_box_content($post_or_order_object) {
		$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		include( dirname( __FILE__ ) . '/includes/views/html-metabox.php' );
	}

	//Vat number metabox content
	public function render_meta_box_content_vat_number($post_or_order_object) {
		$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		$vat_number_data = $order->get_meta('_wc_billingo_plus_adoszam_data');
		include( dirname( __FILE__ ) . '/includes/views/html-metabox-vat.php' );
	}

	//Generate XML for Szamla Agent
	public function generate_invoice($orderId, $type = 'invoice', $options = array()) {
		do_action('wc_billingo_plus_before_generate_invoice', $orderId);
		$type = apply_filters('wc_billingo_plus_generate_invoice_type', $type, $orderId);

		//If multiple orders passed
		if(is_array($orderId)) {

			//The main order is the first one
			$order = wc_get_order($orderId[0]);

			//Collect all order items into single array
			$order_items = array();
			foreach ($orderId as $order_id) {
				$temp_order = wc_get_order($order_id);
				$order_items = $order_items + $temp_order->get_items();
			}

			//Set the $orderId to the main order's(first one) id
			$orderId = $order->get_id();

		} else {
			$order = wc_get_order($orderId);
			$order_items = $order->get_items();
		}

		//If its a void invoice, we use a different function
		if($type == 'void') {
			return $this->generate_void_invoice($orderId, $options);
		}

		//If its a void invoice, we use a different function
		if($type == 'receipt' || $type == 'waybill' || $type == 'offer') {
			return $this->generate_receipt($orderId, $type, $options);
		}

		//Check for duplicates
		if((in_array($type, array('invoice', 'proform', 'deposit'))) && $this->is_invoice_generated($orderId, $type)) {
			$response = array();
			$response['error'] = true;
			$response['messages'][] = esc_html__('Billingo document already generated.', 'wc-billingo-plus');
			return $response;
		}

		//Response
		$response = array();
		$response['error'] = false;
		$response['type'] = $type;

		//Load billingo API
		$fixed_key = false;
		if(isset($options['account'])) $fixed_key = sanitize_text_field($options['account']);
		$billingo = $this->get_billingo_api($order, false, $fixed_key);

		//Create partner
		$partnerData = [
			'name' => '',
			'emails' => [$order->get_billing_email()],
			'phone' => $order->get_billing_phone(),
			'taxcode' => '',
			'address' => [
				'address' => $order->get_billing_address_1(),
				'city' => $order->get_billing_city(),
				'post_code' => $order->get_billing_postcode(),
				'country_code' => $order->get_billing_country() ? : '',
			]
		];

		//Add second billing address if exists
		if($order->get_billing_address_2()) {
			$partnerData['address']['address'] .= ' '.$order->get_billing_address_2();
		}

		//Set client name
		if($this->get_option('nev_csere') == 'yes') {
			$partnerData['name'] = $order->get_billing_last_name().' '.$order->get_billing_first_name();
		} else {
			$partnerData['name'] = $order->get_formatted_billing_full_name();
		}

		//Set company name
		if($order->get_billing_company() && $order->get_billing_company() != 'N/A') {
			if($this->get_option('company_name') == 'yes') {
				$partnerData['name'] = $order->get_billing_company().' - '.$partnerData['name'];
			} else {
				$partnerData['name'] = $order->get_billing_company();
			}
		}

		//TAX number
		if($taxcode = $order->get_meta( 'adoszam' )) {
			$partnerData['taxcode'] = $taxcode;
		}

		if($taxcode = $order->get_meta( 'billingo_plus_adoszam' )) {
			$partnerData['taxcode'] = $taxcode;
		}

		if($order->get_meta( '_billing_wc_billingo_plus_adoszam' )) {
			$partnerData['taxcode'] = $order->get_meta( '_billing_wc_billingo_plus_adoszam' );
		}

		//Change tax number with external plugins and with compatiblity modules
		$partnerData['taxcode'] = apply_filters('wc_billingo_plus_taxcode', $partnerData['taxcode'], $order);
		
		//Mark client type
		$partnerData['tax_type'] = 'FOREIGN';
		if(!$order->get_billing_country() || $order->get_billing_country() == 'HU') {
			$partnerData['tax_type'] = 'NO_TAX_NUMBER';
			if($partnerData['taxcode']) {
				$partnerData['tax_type'] = 'HAS_TAX_NUMBER';
			}
		}

		//Allow plugins to customize partner data
		$partnerData = apply_filters('wc_billingo_plus_partner', $partnerData, $order);

		//Save partner
		$billingo_partner_id = false;
		if(isset($options['preview'])) {
			$partner = apply_filters('wc_billingo_plus_partner', $partnerData, $order);
			$partner['id'] = 123;
		} else {

			//Check if its an existing partner
			$billingo_partner_id = WC_Billingo_Plus_Helpers::get_billingo_partner_id_from_order($order, $partnerData, $this->get_block_id($order));

			//If we have an existing partner ID, use that, if not, create a new partner
			if($billingo_partner_id) {

				//Try to get partner by id
				$this->log_debug_messages($partnerData, 'get-partner-'.$orderId);
				$partner = $billingo->get('partners/'.$billingo_partner_id);

				//If unable to get the partner, maybe it was deleted on Billingo, so try to create a new instead
				if(is_wp_error($partner) || !isset($partner['id'])) {
					$this->log_debug_messages($partnerData, 'create-partner-'.$orderId);
					$partner = $billingo->post('partners', $partnerData);

					//Set to false, so later on the updated partner id will be saved to user meta
					$billingo_partner_id = false;

				}

			} else {
				$this->log_debug_messages($partnerData, 'create-partner-'.$orderId);
				$partner = $billingo->post('partners', $partnerData);
			}
		}

		//Check for errors
		if(is_wp_error($partner)) {
			$response['error'] = true;
			$response['messages'][] = $partner->get_error_message();
			$this->log_error_messages($partner, 'create_partner-'.$orderId);
			$order->add_order_note(sprintf(esc_html__('Billingo invoice generation failed! Unable to create customer. Error code: %s', 'wc-billingo-plus'), $partner->get_error_message()));

			//Better error message for subscription issue
			if($partner->get_error_message())
			if (strpos($partner->get_error_message(), 'You do not have subscription for this operation') !== false) {
				$response['messages'][] = __('Tip: check if you have a subscription for API & Bulk Invoicing on Billingo.', 'wc-billingo-plus');
			}

			return $response;
		}

		//If it was successful, but still no partner created
		if(!$partner['id']) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Unable to create the customer.', 'wc-billingo-plus');
			return $response;
		}

		//Save partner id for logged in users
		if(!$billingo_partner_id && $order->get_customer_id()) {
			WC_Billingo_Plus_Helpers::save_billingo_partner_id($this->get_block_id($order), $partnerData, $partner['id'], $order->get_customer_id());
		}

		//If custom details submitted
		if(isset($_POST['deadline']) && isset($_POST['completed'])) {
			$deadline = intval($_POST['deadline']);
			$completed_date = sanitize_text_field($_POST['completed']);
		} elseif (isset($options['deadline']) && isset($options['completed'])) {
			$deadline = intval($options['deadline']);
			$completed_date = sanitize_text_field($options['completed']);
		} else {
			$deadline = $this->get_payment_method_deadline($order->get_payment_method());
			$completed_date = WC_Billingo_Plus_Helpers::get_default_complete_date($order);
		}

		//Get language
		$language = $this->get_option('language', 'hu');
		if(isset($_POST['lang'])) $language = sanitize_text_field($_POST['lang']);
		if(isset($options['lang'])) $language = sanitize_text_field($options['lang']);

		//Get order note
		$note = $this->get_invoice_note($order, $type, $language);
		if(isset($_POST['note']) && !empty($_POST['note'])) {
			$note_text = sanitize_textarea_field($_POST['note']);
			if($note_text[0] == '+') {
				$note .= "\n".substr($note_text, 1);
			} else {
				$note = $note_text;
			}
		} 

		if(isset($options['note']) && !empty($options['note'])) {
			$note_text = sanitize_textarea_field($options['note']);
			if($note_text[0] == '+') {
				$note .= "\n".substr($note_text, 1);
			} else {
				$note = $note_text;
			}
		} 

		//Replace customer email and phone number in note
		$note = WC_Billingo_Plus_Helpers::replace_note_placeholders($note, $order);

		//Create invoice data array
		$invoiceData = [
			'partner_id' => (int)$partner['id'],
			'block_id' => (int)$this->get_block_id($order),
			'bank_account_id' => (int)$this->get_bank_account_id($order),
			'type' => 'invoice',
			'fulfillment_date' => $completed_date,
			'due_date' => ($deadline) ? date_i18n('Y-m-d', strtotime('+'.$deadline.' days', current_time('timestamp'))) : date_i18n('Y-m-d'),
			'payment_method' => $this->check_payment_method_options($order->get_payment_method(), 'billingo_id'),
			'language' => $language,
			'currency' => $order->get_currency() ?: 'HUF',
			'electronic' => $this->get_invoice_type($order),
			'paid' => false,
			'comment' => $note,
			'settings' => array(
				'round' => $this->get_rounding_option($order)
			)
		];

		//If the base currency is not HUF, we should define currency rates
		if($invoiceData['currency'] != 'HUF') {
			$transient_name = 'wc_billingo_plus_currency_rate_'.strtolower($invoiceData['currency']);
			$exchange_rate = get_transient( $transient_name );
			if(!$exchange_rate) {
				$exchange_rate = 1;
				$get_conversion_rate = $billingo->get("currencies?to=HUF&from={$invoiceData['currency']}");
				if(is_wp_error($get_conversion_rate)) {
					$this->log_error_messages($get_conversion_rate, 'get_conversion_rate-'.$orderId);
				} else {
					$exchange_rate = $get_conversion_rate['conversation_rate'];
				}
				set_transient( $transient_name, $exchange_rate, 60*60*12 );
			}
			$invoiceData['conversion_rate'] = $exchange_rate;
		}

		//Override document type if value submitted
		$doc_type = '';
		if(isset($_POST['doc_type'])) $doc_type = sanitize_text_field($_POST['doc_type']);
		if(isset($options['doc_type'])) $doc_type = sanitize_text_field($options['doc_type']);
		if($doc_type == 'paper') $invoiceData['electronic'] = false;
		if($doc_type == 'electronic') $invoiceData['electronic'] = true;

		//Proform
		if($type == 'proform') {
			$invoiceData['type'] = 'proforma';
		}

		//If its a draft
		if($type == 'draft') {
			$invoiceData['type'] = 'draft';
		}

		//If its a deposit
		if($type == 'deposit') {
			$invoiceData['type'] = 'advance';
		}

		//Mark as paid if needed
		$is_invoice_already_paid = false;
		if($type == 'invoice') {
			if($this->check_payment_method_options($order->get_payment_method(), 'complete')) {
				$invoiceData['paid'] = true;
				$is_invoice_already_paid = true;
			} else {
				$invoiceData['paid'] = false;
			}

			//Based on the custom form field in metabox
			if(isset($_POST['paid'])) {
				if(rest_sanitize_boolean($_POST['paid'])) {
					$invoiceData['paid'] = true;
					$is_invoice_already_paid = true;
				} else {
					$invoiceData['paid'] = false;
					$is_invoice_already_paid = false;
				}
			}
		}

		//Mark as paid if needed with custom options
		if(isset($options['paid'])) {
			if($options['paid']) {
				$invoiceData['paid'] = true;
				$is_invoice_already_paid = true;
			} else {
				$invoiceData['paid'] = false;
				$is_invoice_already_paid = false;
			}
		}

		//This will show the Paid badge or already paid comment on the invoice automatically
		if($invoiceData['paid'] && $type == 'invoice') {
			$invoiceData['settings']['without_financial_fulfillment'] = true;
		}

		//Language based on WPML
		if($this->get_option('language_wpml') == 'yes') {
			$wpml_lang_code = $order->get_meta('wpml_language');
			if(!$wpml_lang_code && function_exists('pll_get_post_language')){
				$wpml_lang_code = pll_get_post_language($orderId, 'locale');
			}
			$supported_locales = WC_Billingo_Plus_Helpers::get_supported_languages();
			$supported_locales = array_keys($supported_locales);
			if($wpml_lang_code && in_array($wpml_lang_code, $supported_locales)) {
				$invoiceData['language'] = $wpml_lang_code;
			}
		}

		//If proform already generated and we now need an invoice, add proform number to invoice notes
		if($this->is_invoice_generated($orderId, 'proform') && $type == 'invoice') {
			if(apply_filters('wc_billingo_plus_deposit_proform_compat', false)) {
				$invoiceData['comment'] .= sprintf(__('Based on proform invoice: %s', 'wc-billingo-plus'), $order->get_meta('_wc_billingo_plus_proform_name'));
			}
		}

		//If deposit already generated and we now need an invoice, add deposit number to invoice notes
		if($this->is_invoice_generated($orderId, 'deposit') && $type == 'invoice') {
			if(apply_filters('wc_billingo_plus_deposit_proform_compat', false)) {
				$invoiceData['comment'] .= sprintf(__('Based on deposit invoice: %s', 'wc-billingo-plus'), $order->get_meta('_wc_billingo_plus_deposit_name'));
			} else {
				$invoiceData['advance_invoice'] = array((int)$order->get_meta('_wc_billingo_plus_deposit_id'));
			}
		}

		//Add products
		foreach( $order_items as $order_item ) {

			$product_item = array(
				'name' => $order_item->get_name(),
				'quantity' => $order_item->get_quantity(),
				'unit' => $this->get_option('unit_type', __('pcs', 'wc-billingo-plus')),
				'vat' => $this->get_order_item_tax_id($order, $order_item)
			);

			//Check for custom vat rate
			$product_item = WC_Billingo_Plus_Helpers::check_vat_override($product_item, 'product', $product_item['vat'], $order, $order_item);

			//Custom product name
			if($order_item->get_product() && $order_item->get_product()->get_meta('wc_billingo_plus_tetel_nev') && $order_item->get_product()->get_meta('wc_billingo_plus_tetel_nev') != '' && $order_item->get_product()->get_meta('wc_billingo_plus_tetel_nev') != 'Array') {
				$product_item['name'] = $order_item->get_product()->get_meta('wc_billingo_plus_tetel_nev');
			}

			//Custom unit type
			if($order_item->get_product() && $order_item->get_product()->get_meta('wc_billingo_plus_mennyisegi_egyseg') && $order_item->get_product()->get_meta('wc_billingo_plus_mennyisegi_egyseg') != '' && $order_item->get_product()->get_meta('wc_billingo_plus_mennyisegi_egyseg') != 'Array') {
				$product_item['unit'] = $order_item->get_product()->get_meta('wc_billingo_plus_mennyisegi_egyseg');
			}

			//Check if we need total or subtotal(total includes discount)
			$subtotal = $order_item->get_total();
			$subtotal_tax = $order_item->get_total_tax();
			if($this->get_option('separate_coupon') == 'yes') {
				$subtotal = $order_item->get_subtotal();
				$subtotal_tax = $order_item->get_subtotal_tax();
			}

			//Check if custom price is set
			if($order_item->get_product() && $order_item->get_product()->get_meta('wc_billingo_plus_custom_cost') && $order_item->get_product()->get_meta('wc_billingo_plus_custom_cost') != '' && $order_item->get_product()->get_meta('wc_billingo_plus_custom_cost') != 'Array') {
				$orig_net = $subtotal;
				$orig_tax = $subtotal_tax;
				$subtotal = intval($order_item->get_product()->get_meta('wc_billingo_plus_custom_cost')) * $order_item->get_quantity();
				$subtotal_tax = ($subtotal / $orig_net) * $orig_tax;
			}

			//Set item price
			$product_item = $this->calculate_item_prices(array(
				'net' => $subtotal,
				'tax' => $subtotal_tax,
				'product_item' => $product_item,
				'order' => $order
			));

			//Item note
			$note = '';

			//Show SKU if neede
			if($this->get_option('sku', 'no') == 'yes' && $order_item->get_product() && $order_item->get_product()->get_sku()) {
				$note = __('SKU: ', 'wc-billingo-plus').$order_item->get_product()->get_sku().' ';
			}

			//Show variation details if needed
			$product_name = $order_item->get_name();
			$note .= strip_tags(WC_Billingo_Plus_Helpers::get_item_meta( $order_item, array(
				'before' => "\n- ",
				'separator' => "\n- ",
				'after' => "",
				'echo' => false,
				'autop' => false,
				'label_before' => '',
				'label_after' => ': ',
			)));
			if($note != '') $note .= "\n";

			//Hide note if needed, but still allow custom notes set in settings
			if($this->get_option('hide_item_notes', 'no') == 'yes') $note = '';

			//Custom note
			if($order_item->get_product() && $order_item->get_product()->get_meta('wc_billingo_plus_megjegyzes') && $order_item->get_product()->get_meta('wc_billingo_plus_megjegyzes') != 'Array') {
				$note .= $order_item->get_product()->get_meta('wc_billingo_plus_megjegyzes');
			}

			//If we need to show sale price in the note
			if($this->get_option('discount_note') && $order_item->get_product() && $order_item->get_product()->is_on_sale()) {
				$sale_price = $order_item->get_product()->get_sale_price();
				$net_unit_price = $order_item->get_total()/$order_item->get_quantity();
				$gross_unit_price = ($order_item->get_total()+$order_item->get_total_tax())/$order_item->get_quantity();

				if(round($sale_price,2) == round($net_unit_price,2) || round($sale_price,2) == round($gross_unit_price,2)) {
					if(get_option( 'woocommerce_prices_include_tax') == 'no') {
						$afakulcs = 1+$order_item->get_total_tax()/$order_item->get_total();
					} else {
						$afakulcs = 1;
					}
					$regular_price = $order_item->get_product()->get_regular_price()*$afakulcs;
					$original_price = $regular_price*$order_item->get_quantity();
					$applied_sale = $original_price-($order_item->get_total()+$order_item->get_total_tax());
					$discounted_price = $order_item->get_total()+$order_item->get_total_tax();
					$discount_note = $this->get_option('discount_note');
					$discount_note_replacements = array('{eredeti_ar}' => wc_price($original_price), '{kedvezmeny_merteke}' => wc_price($applied_sale), '{kedvezmenyes_ar}' => wc_price($discounted_price));
					$discount_note = str_replace( array_keys( $discount_note_replacements ), array_values( $discount_note_replacements ), $discount_note);
					$discount_note = strip_tags($discount_note);
					$discount_note = html_entity_decode($discount_note);
					$note .= $discount_note;

					//Replace coupon placeholders
					$note = WC_Billingo_Plus_Helpers::replace_coupon_placeholders($note, $order);
				}
			}

			//Set note
			$product_item['comment'] = WC_Billingo_Plus_Helpers::replace_note_placeholders($note, $order);

			//Append to items
			if(!($order_item->get_product() && $order_item->get_product()->get_meta('wc_billingo_plus_hide_item') && $order_item->get_product()->get_meta('wc_billingo_plus_hide_item') == 'yes')) {
				$invoiceData['items'][] = apply_filters('wc_billingo_plus_invoice_line_item', $product_item, $order_item, $order, $invoiceData);
			}

		}

		//Shipping
		foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
			$order_shipping = $shipping_item_obj->get_total();
			$order_shipping_tax = $shipping_item_obj->get_total_tax();

			//Skip if free shipping is hidden
			if($this->get_option('hide_free_shipping') == 'yes' && $order_shipping == 0) {
				continue;
			}

			$product_item = array(
				'name' => $shipping_item_obj->get_method_title(),
				'quantity' => 1,
				'unit' => $this->get_option('unit_type', __('pcs', 'wc-billingo-plus')),
				'vat' => $this->get_order_shipping_tax_id($order, $shipping_item_obj),
			);

			//Check for custom vat rate
			$product_item = WC_Billingo_Plus_Helpers::check_vat_override($product_item, 'shipping', $product_item['vat'], $order);

			//Set item price
			$product_item = $this->calculate_item_prices(array(
				'net' => $order_shipping,
				'tax' => $order_shipping_tax,
				'product_item' => $product_item,
				'order' => $order
			));

			//Check if we have a custom name specified
			$shipping_method_options = get_option('woocommerce_'.$shipping_item_obj->get_method_id().'_'.$shipping_item_obj->get_instance_id().'_settings');
			if($shipping_method_options && isset($shipping_method_options['wc_billingo_plus_tetel_nev']) && !empty($shipping_method_options['wc_billingo_plus_tetel_nev'])) {
				$product_item['name'] = $shipping_method_options['wc_billingo_plus_tetel_nev'];
			}

			//Check if we have a custom unit type specified
			if($shipping_method_options && isset($shipping_method_options['wc_billingo_plus_mennyisegi_egyseg']) && !empty($shipping_method_options['wc_billingo_plus_mennyisegi_egyseg'])) {
				$product_item['unit'] = $shipping_method_options['wc_billingo_plus_mennyisegi_egyseg'];
			}

			//Check if we have a custom note specified
			if($shipping_method_options && isset($shipping_method_options['wc_billingo_plus_megjegyzes']) && !empty($shipping_method_options['wc_billingo_plus_megjegyzes'])) {
				$product_item['comment'] = $shipping_method_options['wc_billingo_plus_megjegyzes'];
			}

			//Append to items
			$invoiceData['items'][] = apply_filters('wc_billingo_plus_invoice_line_item', $product_item, $shipping_item_obj, $order, $invoiceData);

		}

		//Fees
		$fees = $order->get_fees();
		if(!empty($fees)) {
			foreach( $fees as $fee ) {
				$product_item = array(
					'name' => $fee['name'],
					'quantity' => 1,
					'unit' => $this->get_option('unit_type', __('pcs', 'wc-billingo-plus')),
					'vat' => $this->get_order_shipping_tax_id($order, $fee)
				);

				//Check for custom vat rate
				$product_item = WC_Billingo_Plus_Helpers::check_vat_override($product_item, 'fee', $product_item['vat'], $order);

				//Set item price
				$product_item = $this->calculate_item_prices(array(
					'net' => $fee->get_total(),
					'tax' => $fee->get_total_tax(),
					'product_item' => $product_item,
					'order' => $order
				));

				$invoiceData['items'][] = apply_filters('wc_billingo_plus_invoice_line_item', $product_item, $fee, $order, $invoiceData);

			}
		}

		//Discount
		if ( $order->get_discount_total() > 0 ) {
			$discout_details = $this->get_coupon_invoice_item_details($order);
			//If coupon is a separate item
			if($this->get_option('separate_coupon') == 'yes') {
				$product_item = array(
					'name' => $discout_details['name'],
					'comment' => $discout_details['comment'],
					'quantity' => 1,
					'unit' => $this->get_option('unit_type', __('pcs', 'wc-billingo-plus')),
					'vat' => $invoiceData['items'][0]['vat']
				);

				//Check for custom vat rate
				$product_item = WC_Billingo_Plus_Helpers::check_vat_override($product_item, 'discount', $product_item['vat'], $order);

				//Set item price
				$product_item = $this->calculate_item_prices(array(
					'net' => $order->get_total_discount()*-1,
					'tax' => $order->get_discount_tax()*-1,
					'product_item' => $product_item,
					'order' => $order
				));

				$invoiceData['items'][] = apply_filters('wc_billingo_plus_invoice_line_item', $product_item, $discout_details, $order, $invoiceData);
			} else {
				//Add space if theres already something in the comment
				if($invoiceData['comment']) {
					$invoiceData['comment'] .= ' ';
				}
				$invoiceData['comment'] .= $discout_details['comment'];
			}
		}

		//Refunds
		$order_refunds = $order->get_refunds();
		if(!empty($order_refunds)) {
			foreach ( $order_refunds as $refund ) {
				$product_item = array(
					'name' => __('Refund', 'wc-billingo-plus'),
					'quantity' => 1,
					'unit' => $this->get_option('unit_type', __('pcs', 'wc-billingo-plus')),
					'vat' => $this->get_order_shipping_tax_id($order, $refund)
				);

				//Check for custom vat rate
				$product_item = WC_Billingo_Plus_Helpers::check_vat_override($product_item, 'refund', $product_item['vat'], $order);

				//Set item price
				$product_item = $this->calculate_item_prices(array(
					'net' => $refund->get_total()-$refund->get_total_tax(),
					'tax' => $refund->get_total_tax(),
					'product_item' => $product_item,
					'order' => $order
				));

				$invoiceData['items'][] = apply_filters('wc_billingo_plus_invoice_line_item', $product_item, $refund, $order, $invoiceData);

			}
		}

		//If we are creating an invoice based on a deposit invoice, duplicate invoice line items as negative values
		if($this->is_invoice_generated($orderId, 'deposit') && $type == 'invoice' && apply_filters('wc_billingo_plus_deposit_proform_compat', false)) {
			foreach ($invoiceData['items'] as $invoice_line_item) {
				//Convert prices to negative
				$invoice_line_item['unit_price'] = $invoice_line_item['unit_price']*-1;
				$invoiceData['items'][] = $invoice_line_item;
			}
		}

		//Check for advanced options
		if($this->get_option('advanced_settings', 'no') == 'yes') {
			$invoiceData = WC_Billingo_Plus_Conditions::check_advanced_options($invoiceData, $order);
		}

		//Create invoice
		if(isset($options['preview'])) {
			$invoice = apply_filters('wc_billingo_plus_invoice', $invoiceData, $order);
			return array('partner' => $partner, 'invoice' => $invoice);
		} else {
			$this->log_debug_messages($invoiceData, 'generate-invoice-'.$orderId);

			//Check if custom stuff is sent or not
			$custom_details = false;
			if(isset($_POST['custom']) && $_POST['custom'] == 'true') $custom_details = true;

			//If theres a proform already existed, we are using a different api call
			if($type == 'invoice' && $order->get_meta('_wc_billingo_plus_proform_id') && !$this->is_invoice_generated($orderId, 'deposit')) {
				$invoiceData['paid'] = true;
				$from_proforma = array(
					'document_type' => 'invoice',
					'due_date' => $invoiceData['due_date'],
					'fulfillment_date' => $invoiceData['fulfillment_date'],
					'comment' => $invoiceData['comment']
				);
				$proform_id = $order->get_meta('_wc_billingo_plus_proform_id');
				$invoice = $billingo->post('documents/'.$proform_id.'/create-from-proforma', $from_proforma);
			} else if($type == 'deposit' && $this->is_invoice_generated($orderId, 'proform')) {
				$from_proforma = array(
					'document_type' => 'advance',
					'due_date' => $invoiceData['due_date'],
					'fulfillment_date' => $invoiceData['fulfillment_date'],
					'comment' => $invoiceData['comment']
				);
				$proform_id = $order->get_meta('_wc_billingo_plus_proform_id');
				$invoice = $billingo->post('documents/'.$proform_id.'/create-from-proforma', $from_proforma);
			} else {
				$invoice = $billingo->post('documents', apply_filters('wc_billingo_plus_invoice', $invoiceData, $order));
			}

		}

		//Check for errors
		if(is_wp_error($invoice)) {

			//Create response
			$response['error'] = true;
			$response['messages'][] = esc_html__('Invoice generation failed.', 'wc-billingo-plus');
			$response['messages'][] = $invoice->get_error_message();
			$order->add_order_note(sprintf(esc_html__('Billingo invoice generation failed! Error code: %s', 'wc-billingo-plus'), $invoice->get_error_message()));

			//Better error message for subscription issue
			if($invoice->get_error_message())
			if (strpos($invoice->get_error_message(), 'You do not have subscription for this operation') !== false) {
				$response['messages'][] = __('Tip: check if you have a subscription for API & Bulk Invoicing on Billingo.', 'wc-billingo-plus');
			}

			//Callbacks
			$this->log_error_messages($invoice, 'generate_invoice-'.$orderId);
			do_action('wc_billingo_plus_after_invoice_error', $order, $invoice);
			return $response;

		}

		//If successful, but still no invoice id
		if (!$invoice['id']) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Invoice generation failed for an unknown reason.', 'wc-billingo-plus');
			return $response;
		}

		//Send via email if needed
		$auto_email_sent = false;
		if($this->get_option('auto_email', 'yes') == 'yes' && $type != 'draft') {
			$send_email = $billingo->post("documents/{$invoice['id']}/send", array());

			if(is_wp_error($send_email)) {
				$this->log_error_messages($send_email, 'send_email-'.$orderId);
				$response['messages'][] = esc_html__('Failed to email invoice.', 'wc-billingo-plus');
				return false;
			} else {
				$auto_email_sent = true;
			}
		}

		//Save file if needed
		$pdf_file_name = '';
		if($this->get_option('download_invoice', 'no') == 'yes' && $type != 'draft') {

			//Since the PDF is not ready yet, schedule an action to download it later(at least 5 seconds in the future)
			WC()->queue()->schedule_single(
				time()+10,
				'wc_billingo_plus_download_invoice',
				array('order_id' => $orderId, 'document_type' => $response['type']),
				'woo_billingo_plus'
			);

			//For now, just store the filename as pending
			$pdf_file_name = 'pending';
			$response['pdf'] = 'pending';

		}

		//Create download link
		$billingo_url = '';
		$get_public_url = $billingo->get("documents/{$invoice['id']}/public-url");
		if(is_wp_error($get_public_url)) {
			$this->log_error_messages($get_public_url, 'create_download_link-'.$orderId);
			$response['messages'][] = esc_html__('Failed to create a download link for the invoice.', 'wc-billingo-plus');
		} else {
			$billingo_url = $get_public_url['public_url'];
		}

		//Get invoice data
		$invoice_name = $invoice['id'];
		if(isset($invoice['invoice_number'])) {
			$invoice_name = $invoice['invoice_number'];
		}
		$invoice_id = $invoice['id'];
		$invoice_pdf = $pdf_file_name;

		//Create response
		$response['name'] = $invoice_name;

		//Save order meta
		$order->update_meta_data( '_wc_billingo_plus_'.$type.'_id', $invoice_id );
		$order->update_meta_data( '_wc_billingo_plus_'.$type.'_name', $invoice_name );
		$order->update_meta_data( '_wc_billingo_plus_'.$type.'_pdf', $invoice_pdf );
		$order->update_meta_data( '_wc_billingo_plus_'.$type.'_url', $billingo_url );


		//Based on invoice type
		switch ($type) {

			//Proform invoice
			case 'proform':
				$response['messages'][] = ($auto_email_sent) ? esc_html__('Proforma invoice successfully generated and sent to the customer via e-mail.','wc-billingo-plus') : esc_html__('Proforma invoice successfully generated.','wc-billingo-plus');

				//Update order notes
				$order->add_order_note(sprintf(esc_html__('Billingo proforma invoice generated successfully. Invoice number: %s', 'wc-billingo-plus'), $invoice_name));

				//Return download links
				$response['link'] = $this->generate_download_link($order, 'proform');

				break;

			//Deposit invoice
			case 'deposit':
				$response['messages'][] = ($auto_email_sent) ? esc_html__('Deposit invoice successfully generated and sent to the customer via e-mail.','wc-billingo-plus') : esc_html__('Deposit invoice successfully generated.','wc-billingo-plus');

				//Update order notes
				$order->add_order_note(sprintf(esc_html__('Billingo deposit invoice generated successfully. Invoice number: %s', 'wc-billingo-plus'), $invoice_name));

				//Return download links
				$response['link'] = $this->generate_download_link($order, 'deposit');

				break;

			//Draft invoice
			case 'draft':
				$response['messages'][] =esc_html__('Draft successfully generated.','wc-billingo-plus');

				//Update order notes
				$order->add_order_note(esc_html__('Billingo draft successfully generated.', 'wc-billingo-plus'));

				//Return download links
				$response['link'] = $this->generate_download_link($order, 'draft');

				break;

			//Regular invoice
			case 'invoice':
				$response['messages'][] = ($auto_email_sent) ? esc_html__('Invoice successfully generated and sent to the customer via e-mail.','wc-billingo-plus') : esc_html__('Invoice successfully generated.','wc-billingo-plus');

				//Update order notes
				$order->add_order_note(sprintf(esc_html__('Billingo invoice generated successfully. Invoice number: %s', 'wc-billingo-plus'), $invoice_name));

				//Mark as paid if needed
				if($invoiceData['paid']) {
					$order->update_meta_data( '_wc_billingo_plus_completed', date_i18n('Y-m-d') );
					$response['paid'] = date_i18n('Y-m-d');
				}

				//If it was manually generated with a custom account
				if(isset( $_POST['action']) && $_POST['action'] == 'wc_billingo_plus_generate_invoice' && isset($_POST['account']) && $_POST['account'] != $this->get_option('api_key')) {
					$order->update_meta_data( '_wc_billingo_plus_account_id', substr(sanitize_text_field($_POST['account']), 0, 5) );
				}

				//Return download links
				$response['link'] = $this->generate_download_link($order);

				break;
		}

		//Delete void invoice if exists
		$order->delete_meta_data( '_wc_billingo_plus_void_id' );
		$order->delete_meta_data( '_wc_billingo_plus_void_name' );
		$order->delete_meta_data( '_wc_billingo_plus_void_pdf' );
		$order->delete_meta_data( '_wc_billingo_plus_void_url' );
		$order->delete_meta_data( '_wc_billingo_plus_auto_gen_pending' );

		//Save the order
		$order->save();

		//Run action on successful invoice creation
		do_action('wc_billingo_plus_after_invoice_success', $order, $response);

		//Action for webhooks
		do_action( 'wc_billingo_plus_document_created', array('order_id' => $order->get_id(), 'document_type' => $type) );

		return $response;
	}

	//Generate XML for Szamla Agent
	public function generate_receipt($orderId, $type = 'receipt', $options = array()) {

		//If multiple orders passed
		if(is_array($orderId)) {

			//The main order is the first one
			$order = wc_get_order($orderId[0]);

			//Collect all order items into single array
			$order_items = array();
			foreach ($orderId as $order_id) {
				$temp_order = wc_get_order($order_id);
				$order_items = $order_items + $temp_order->get_items();
			}

			//Set the $orderId to the main order's(first one) id
			$orderId = $order->get_id();

		} else {
			$order = wc_get_order($orderId);
			$order_items = $order->get_items();
		}

		//Response
		$response = array();
		$response['error'] = false;
		$response['type'] = $type;

		//Load billingo API
		$fixed_key = false;
		if(isset($options['account'])) $fixed_key = sanitize_text_field($options['account']);
		$billingo = $this->get_billingo_api($order, false, $fixed_key);

		//Create receipt
		$receiptData = [
			'name' => '',
			'emails' => [$order->get_billing_email()],
			'block_id' => (int)$this->get_block_id($order, $type),
			'type' => $type,
			'payment_method' => $this->check_payment_method_options($order->get_payment_method(), 'billingo_id'),
			'currency' => $order->get_currency() ?: 'HUF',
			'electronic' => $this->get_invoice_type($order),
			'items' => array()
		];

		//Set client name
		if($this->get_option('nev_csere') == 'yes') {
			$receiptData['name'] = $order->get_billing_last_name().' '.$order->get_billing_first_name();
		} else {
			$receiptData['name'] = $order->get_formatted_billing_full_name();
		}

		//Set company name
		if($order->get_billing_company() && $order->get_billing_company() != 'N/A') {
			if($this->get_option('company_name') == 'yes') {
				$receiptData['name'] = $order->get_billing_company().' - '.$receiptData['name'];
			} else {
				$receiptData['name'] = $order->get_billing_company();
			}
		}

		//If the base currency is not HUF, we should define currency rates
		if($receiptData['currency'] != 'HUF') {
			$transient_name = 'wc_billingo_plus_currency_rate_'.strtolower($receiptData['currency']);
			$exchange_rate = get_transient( $transient_name );
			if(!$exchange_rate) {
				$exchange_rate = 1;
				$get_conversion_rate = $billingo->get("currencies?to=HUF&from={$receiptData['currency']}");
				if(is_wp_error($get_conversion_rate)) {
					$this->log_error_messages($get_conversion_rate, 'get_conversion_rate-'.$orderId);
				} else {
					$exchange_rate = $get_conversion_rate['conversation_rate'];
				}
				set_transient( $transient_name, $exchange_rate, 60*60*12 );
			}
			$receiptData['conversion_rate'] = $exchange_rate;
		}

		//Override document type if value submitted
		$doc_type = '';
		if(isset($_POST['doc_type'])) $doc_type = sanitize_text_field($_POST['doc_type']);
		if(isset($options['doc_type'])) $doc_type = sanitize_text_field($options['doc_type']);
		if($doc_type == 'paper') $receiptData['electronic'] = false;
		if($doc_type == 'electronic') $receiptData['electronic'] = true;

		//Add products
		foreach( $order_items as $order_item ) {
			$product_item = array(
				'name' => $order_item->get_name(),
				'unit_price' => 0,
				'vat' => $this->get_order_item_tax_id($order, $order_item)
			);

			//Check for custom vat rate
			$product_item = WC_Billingo_Plus_Helpers::check_vat_override($product_item, 'product', $product_item['vat'], $order);

			//Custom product name
			if($order_item->get_product() && $order_item->get_product()->get_meta('wc_billingo_plus_tetel_nev') && $order_item->get_product()->get_meta('wc_billingo_plus_tetel_nev') != '' && $order_item->get_product()->get_meta('wc_billingo_plus_tetel_nev') != 'Array') {
				$product_item['name'] = $order_item->get_product()->get_meta('wc_billingo_plus_tetel_nev');
			}

			//Check if we need total or subtotal(total includes discount)
			$subtotal = $order_item->get_total();
			$subtotal_tax = $order_item->get_total_tax();

			//Check if custom price is set
			if($order_item->get_product() && $order_item->get_product()->get_meta('wc_billingo_plus_custom_cost') && $order_item->get_product()->get_meta('wc_billingo_plus_custom_cost') != '' && $order_item->get_product()->get_meta('wc_billingo_plus_custom_cost') != 'Array') {
				$orig_net = $subtotal;
				$orig_tax = $subtotal_tax;
				$subtotal = intval($order_item->get_product()->get_meta('wc_billingo_plus_custom_cost')) * $order_item->get_quantity();
				$subtotal_tax = ($subtotal / $orig_net) * $orig_tax;
			}

			//Set item price
			$product_item['unit_price'] = $subtotal + $subtotal_tax;

			//Append to items
			if(!($order_item->get_product() && $order_item->get_product()->get_meta('wc_billingo_plus_hide_item') && $order_item->get_product()->get_meta('wc_billingo_plus_hide_item') == 'yes')) {
				$receiptData['items'][] = apply_filters('wc_billingo_plus_receipt_line_item', $product_item, $order_item, $order, $receiptData);
			}

		}

		//Fees
		$fees = $order->get_fees();
		if(!empty($fees)) {
			foreach( $fees as $fee ) {
				$product_item = array(
					'name' => $fee['name'],
					'unit_price' => 0,
					'vat' => $this->get_order_shipping_tax_id($order, $fee)
				);

				//Check for custom vat rate
				$product_item = WC_Billingo_Plus_Helpers::check_vat_override($product_item, 'fee', $product_item['vat'], $order);

				//Set item price
				$product_item['unit_price'] = $fee->get_total()+$fee->get_total_tax();

				//Append to items
				$receiptData['items'][] = apply_filters('wc_billingo_plus_receipt_line_item', $product_item, $fee, $order, $receiptData);

			}
		}

		//Check for advanced options
		if($this->get_option('advanced_settings', 'no') == 'yes') {
			$receiptData = WC_Billingo_Plus_Conditions::check_advanced_options($receiptData, $order);
		}

		//Create receipt
		$this->log_debug_messages($receiptData, 'generate-receipt-'.$orderId);

		//If theres a proform already existed, we are using a different api call
		$receipt = $billingo->post('documents/receipt', apply_filters('wc_billingo_plus_receipt', $receiptData, $order));
		
		//Check for errors
		if(is_wp_error($receipt)) {

			//Create response
			$response['error'] = true;
			$response['messages'][] = esc_html__('Receipt generation failed.', 'wc-billingo-plus');
			$response['messages'][] = $receipt->get_error_message();
			$order->add_order_note(sprintf(esc_html__('Billingo receipt generation failed! Error code: %s', 'wc-billingo-plus'), $receipt->get_error_message()));

			//Callbacks
			$this->log_error_messages($receipt, 'generate_receipt-'.$orderId);
			do_action('wc_billingo_plus_after_receipt_error', $order, $receipt);
			return $response;

		}

		//If successful, but still no receipt id
		if (!$receipt['id']) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Receipt generation failed for an unknown reason.', 'wc-billingo-plus');
			return $response;
		}

		//Save file if needed
		$pdf_file_name = '';
		if($this->get_option('download_invoice', 'no') == 'yes') {

			//Since the PDF is not ready yet, schedule an action to download it later(at least 5 seconds in the future)
			WC()->queue()->schedule_single(
				time()+10,
				'wc_billingo_plus_download_invoice',
				array('order_id' => $orderId, 'document_type' => $type),
				'woo_billingo_plus'
			);

			//For now, just store the filename as pending
			$pdf_file_name = 'pending';
			$response['pdf'] = 'pending';

		}

		//Create download link
		$billingo_url = '';
		$get_public_url = $billingo->get("documents/{$receipt['id']}/public-url");
		if(is_wp_error($get_public_url)) {
			$this->log_error_messages($get_public_url, 'create_download_link-'.$orderId);
			$response['messages'][] = esc_html__('Failed to create a download link for the receipt.', 'wc-billingo-plus');
		} else {
			$billingo_url = $get_public_url['public_url'];
		}

		//Get receipt data
		$receipt_name = $receipt['id'];
		if(isset($receipt['invoice_number'])) {
			$receipt_name = $receipt['invoice_number'];
		}
		$receipt_id = $receipt['id'];
		$receipt_pdf = $pdf_file_name;

		//Create response
		$response['name'] = $receipt_name;

		//Save order meta
		$order->update_meta_data( '_wc_billingo_plus_'.$type.'_id', $receipt_id );
		$order->update_meta_data( '_wc_billingo_plus_'.$type.'_name', $receipt_name );
		$order->update_meta_data( '_wc_billingo_plus_'.$type.'_pdf', $receipt_pdf );
		$order->update_meta_data( '_wc_billingo_plus_'.$type.'_url', $billingo_url );

		//Based on receipt type
		switch ($type) {

			//Regular receipt
			case 'receipt':
				$response['messages'][] = ($auto_email_sent) ? esc_html__('Receipt successfully generated and sent to the customer via e-mail.','wc-billingo-plus') : esc_html__('Receipt successfully generated.','wc-billingo-plus');

				//Update order notes
				$order->add_order_note(sprintf(esc_html__('Billingo receipt generated successfully. Receipt number: %s', 'wc-billingo-plus'), $receipt_name));

				//Mark as paid(receipts are always paid when generated)
				$order->update_meta_data( '_wc_billingo_plus_completed', date_i18n('Y-m-d') );
				$response['paid'] = date_i18n('Y-m-d');

				//If it was manually generated with a custom account
				if(isset( $_POST['action']) && $_POST['action'] == 'wc_billingo_plus_generate_invoice' && isset($_POST['account']) && $_POST['account'] != $this->get_option('api_key')) {
					$order->update_meta_data( '_wc_billingo_plus_account_id', substr(sanitize_text_field($_POST['account']), 0, 5) );
				}

				//Return download links
				$response['link'] = $this->generate_download_link($order, $type);

				break;

			//Offers
			case 'offer':
				$response['messages'][] = ($auto_email_sent) ? esc_html__('Offer successfully generated and sent to the customer via e-mail.','wc-billingo-plus') : esc_html__('Offer successfully generated.','wc-billingo-plus');

				//Update order notes
				$order->add_order_note(sprintf(esc_html__('Billingo offer generated successfully. Offer number: %s', 'wc-billingo-plus'), $receipt_name));

				//If it was manually generated with a custom account
				if(isset( $_POST['action']) && $_POST['action'] == 'wc_billingo_plus_generate_invoice' && isset($_POST['account']) && $_POST['account'] != $this->get_option('api_key')) {
					$order->update_meta_data( '_wc_billingo_plus_account_id', substr(sanitize_text_field($_POST['account']), 0, 5) );
				}

				//Return download links
				$response['link'] = $this->generate_download_link($order, $type);

				break;

			//Waybills
			case 'waybill':
				$response['messages'][] = ($auto_email_sent) ? esc_html__('Waybill successfully generated and sent to the customer via e-mail.','wc-billingo-plus') : esc_html__('Waybill successfully generated.','wc-billingo-plus');

				//Update order notes
				$order->add_order_note(sprintf(esc_html__('Billingo waybill generated successfully. Waybill number: %s', 'wc-billingo-plus'), $receipt_name));

				//If it was manually generated with a custom account
				if(isset( $_POST['action']) && $_POST['action'] == 'wc_billingo_plus_generate_invoice' && isset($_POST['account']) && $_POST['account'] != $this->get_option('api_key')) {
					$order->update_meta_data( '_wc_billingo_plus_account_id', substr(sanitize_text_field($_POST['account']), 0, 5) );
				}

				//Return download links
				$response['link'] = $this->generate_download_link($order, $type);

				break;


		}

		//Delete void invoice if exists
		$order->delete_meta_data( '_wc_billingo_plus_void_id' );
		$order->delete_meta_data( '_wc_billingo_plus_void_name' );
		$order->delete_meta_data( '_wc_billingo_plus_void_pdf' );
		$order->delete_meta_data( '_wc_billingo_plus_void_url' );
		$order->delete_meta_data( '_wc_billingo_plus_auto_gen_pending' );

		//Save the order
		$order->save();

		//Run action on successful invoice creation
		do_action('wc_billingo_plus_after_receipt_success', $order, $response);

		//Action for webhooks
		do_action( 'wc_billingo_plus_document_created', array('order_id' => $order->get_id(), 'document_type' => $type) );

		return $response;
	}

	//Autogenerate invoice
	public function on_order_complete( $order_id ) {

		//Check payment method settings
		$should_generate_auto_invoice = true;
		$order = wc_get_order($order_id);
		$payment_method = $order->get_payment_method();
		if($this->check_payment_method_options($order->get_payment_method(), 'auto_disabled')) {
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
		$should_generate_auto_invoice = apply_filters('wc_billingo_plus_should_generate_auto_invoice', $should_generate_auto_invoice, $order_id);

		//Only generate invoice, if it wasn't already generated & only if automatic invoice is enabled
		if(!$this->is_invoice_generated($order_id) && $should_generate_auto_invoice) {

			//Check if we generate this invoice deferred
			$deferred = ($this->get_option('defer', 'no') == 'yes');

			//Don't create deferred if we are in an admin page and only mark one order completed
			if(is_admin() && isset( $_GET['action']) && $_GET['action'] == 'woocommerce_mark_order_status') {
				$deferred = false;
			}

			//Don't defer if we are just changing one or two order status using bulk actions
			if(is_admin() && isset($_GET['_wp_http_referer']) && isset($_GET['post']) && count($_GET['post']) < 3) {
				$deferred = false;
			}

			//Check if we need to create a draft invoice
			$draft = ($this->get_option('draft', 'no') == 'yes');
			$invoice_type = 'invoice';
			if($draft) $invoice_type = 'draft';

			//Check if we generate this invoice deferred
			if($deferred) {
				WC()->queue()->add( 'wc_billingo_plus_generate_document_async', array( 'invoice_type' => $invoice_type, 'order_id' => $order_id ), 'wc-billingo-plus' );
			} else {

				if($draft) {
					$return_info = $this->generate_invoice($order_id, 'draft');
				} else {
					$order->update_meta_data('_wc_billingo_plus_auto_gen_pending', true);
					$order->save();
					$return_info = $this->generate_invoice($order_id);
				}

				//If there was an error while generating invoices automatically
				if($return_info && $return_info['error']) {
					$this->on_auto_invoice_error($order_id);
				}
			}

		}

	}

	//Autogenerate proform invoice
	public function on_order_processing( $order_id ) {

		//Only generate invoice, if it wasn't already generated & only if automatic invoice is enabled
		$order = new WC_Order($order_id);
		$payment_method = $order->get_payment_method();

		$invoice_types = array('proform', 'deposit');
		foreach ($invoice_types as $invoice_type) {

			if($this->check_payment_method_options($payment_method, $invoice_type) && !$this->is_invoice_generated($order_id)) {
				if($this->get_option('defer') == 'yes') {
					WC()->queue()->add( 'wc_billingo_plus_generate_document_async', array( 'invoice_type' => $invoice_type, 'order_id' => $order_id ), 'wc-billingo-plus' );
				} else {
					$return_info = $this->generate_invoice($order_id, $invoice_type);
				}
			}
		}

	}

	//Autogenerate invoice
	public function on_order_deleted( $order_id ) {

		//Only generate sztornó, if regular invoice already generated & only if automatic invoice is enabled
		if($this->is_invoice_generated($order_id)) {
			$response = $this->generate_void_invoice($order_id);
		}

	}

	//Send email on error
	public function on_auto_invoice_error( $order_id ) {
		update_option('_wc_billingo_plus_error', $order_id);

		//Check if we need to send an email todo
		if($this->get_option('error_email')) {
			$order = wc_get_order($order_id);
			$mailer = WC()->mailer();
			$content = wc_get_template_html( 'includes/emails/invoice-error.php', array(
				'order' => $order,
				'email_heading' => esc_html__('Failed invoice generation', 'wc-billingo-plus'),
				'plain_text' => false,
				'email' => $mailer,
				'sent_to_admin' => true,
			), '', plugin_dir_path( __FILE__ ) );
			$recipient = $this->get_option('error_email');
			$subject = esc_html__("Failed invoice generation", 'wc-billingo-plus');
			$headers = "Content-Type: text/html\r\n";
			$mailer->send( $recipient, $subject, $content, $headers );
		}

	}

	//Check if it was already generated or not
	public function is_invoice_generated( $order_id, $type = 'invoice' ) {

		$order = wc_get_order($order_id);
		$own_invoice = false;
		if($type == 'invoice' && $order->get_meta('_wc_billingo_plus_own')) {
			return true;
		}
		return ($order->get_meta('_wc_billingo_plus_'.$type.'_id') || $own_invoice);
	}

	//Column on orders page
	public function add_listing_column($columns) {
		$new_columns = array();
		foreach ($columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;
			if ( 'order_total' === $column_name ) {
				$new_columns['wc_billingo_plus'] = __( 'Woo Billingo Plus', 'wc-billingo-plus' );
			}
		}
		return $new_columns;
	}

	//Add icon to order list to show invoice
	public function add_listing_actions( $column, $post_or_order_object ) {
		$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		if ( ! is_object( $order ) && is_numeric( $order ) ) {
			$order = wc_get_order( absint( $order ) );
		}

		if ( 'order_total' === $column && WC_Billingo_Plus_Pro::is_pro_enabled()) {
			echo '<span class="wc-billingo-plus-mark-paid-item">';

			//Replicate the original price content
			if ( $order->get_payment_method_title() ) {
				echo '<span class="tips" data-tip="' . esc_attr( sprintf( __( 'via %s', 'wc-billingo-plus' ), $order->get_payment_method_title() ) ) . '">' . wp_kses_post( $order->get_formatted_order_total() ) . '</span>';
			} else {
				echo wp_kses_post( $order->get_formatted_order_total() );
			}

			if($this->is_invoice_generated($order->get_id(), 'invoice') && !$order->get_meta('_wc_billingo_plus_own')) {

				if($order->get_meta('_wc_billingo_plus_completed')) {
					$paid_date = $order->get_meta('_wc_billingo_plus_completed');
					if (strpos($paid_date, '-') == false) {
						$paid_date = date('Y-m-d', $paid_date);
					}

					echo '<span class="wc-billingo-plus-mark-paid-button paid tips" data-tip="'.sprintf(__('Paid on: %s', 'wc-billingo-plus'), $paid_date).'"></span>';
				} else {
					if(!$order->get_meta('_wc_billingo_plus_own')) {
						echo '<a href="#" data-nonce="'.wp_create_nonce( 'wc_billingo_generate_invoice' ).'" data-order="'.$order->get_id().'" class="wc-billingo-plus-mark-paid-button tips" data-tip="'.__('Mark as paid', 'wc-billingo-plus').'"></a>';
					}
				}

			} else {
				$tip = __("There's no invoice for this order yet", "wc-billingo-plus");
				echo '<span class="wc-billingo-plus-mark-paid-button pending tips" data-tip="'.$tip.'"></span>';
			}

			echo '</span>';
		}

		if ( 'wc_billingo_plus' === $column ) {
			$invoice_types = WC_Billingo_Plus_Helpers::get_document_types();

			foreach ($invoice_types as $invoice_type => $invoice_label) {
				if($this->is_invoice_generated($order->get_id(), $invoice_type) && !$order->get_meta('_wc_billingo_plus_own')):
				?>
					<a href="<?php echo $this->generate_download_link($order, $invoice_type); ?>" class="button tips wc-billingo-plus-button" target="_blank" data-tip="<?php echo $invoice_label; ?>">
						<img src="<?php echo WC_Billingo_Plus::$plugin_url . 'assets/images/icon-'.$invoice_type.'.svg'; ?>" alt="" width="16" height="16">
					</a>
				<?php
				endif;
			}
		}
	}

	//Add to tools column
	public function add_listing_actions_2($order) {
		$this->add_listing_actions('wc_billingo_plus', $order);
	}

	//Generate download url
	public function generate_download_link( $order, $type = 'invoice', $absolute = false) {
		if($order) {
			$pdf_name = $order->get_meta('_wc_billingo_plus_'.$type.'_pdf');
			$billingo_url = $order->get_meta('_wc_billingo_plus_'.$type.'_url');
			$download_link = '';

			if($pdf_name) {

				if($pdf_name == 'pending' && $billingo_url) {
					$download_link = $billingo_url;
				}

				if(strpos($pdf_name, '.pdf') !== false) {
					$UploadDir = wp_upload_dir();
					$UploadURL = $UploadDir['baseurl'];
					if($absolute) {
						$pdf_file_url = $UploadDir['basedir'].'/wc-billingo-plus/'.$pdf_name;
					} else {
						$pdf_file_url = $UploadURL.'/wc-billingo-plus/'.$pdf_name;
					}
					$download_link = $pdf_file_url;
				} elseif(strpos($pdf_name, 'https://') !== false) {
					$download_link = $pdf_name;
				} elseif ($pdf_name == 'pending') {
					if($billingo_url) {
						$download_link = $billingo_url;
					} else {
						$download_link = 'pending';
					}
				} else {
					$download_link = 'https://www.billingo.hu/access/c:' . $pdf_name;
				}

			} elseif ($billingo_url) {
				$download_link = $billingo_url;
			} else {
				$download_link = false;
			}

			return apply_filters('wc_billingo_plus_download_link', $download_link, $order);

		} else {
			return false;
		}
	}

	//Mark invoice as payed
	public function generate_invoice_complete($order_id, $date = false) {
		$order = wc_get_order($order_id);

		//Response
		$response = array();
		$response['error'] = false;

		//Load billingo API
		$billingo = $this->get_billingo_api($order);

		//Get existing invoice data
		$invoice_id = $order->get_meta('_wc_billingo_plus_invoice_id');
		$completed = $order->get_meta('_wc_billingo_plus_completed');

		//Check if we need to convert the invoice id to the new one(v3 api)
		$invoice_id = WC_Billingo_Plus_Helpers::check_legacy_invoice_id($invoice_id, $order_id);

		//If invoice doesn't exists
		if(!$invoice_id) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Invoice not found.', 'wc-billingo-plus');
			return $response;
		}

		//If already marked as payed
		if($completed) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('The invoice has already been marked as paid.', 'wc-billingo-plus');
			return $response;
		}

		//Check if payment date is stored
		$date_paid = $order->get_date_paid();
		$date_paid_paydata = date_i18n('Y-m-d');
		if( ! empty( $date_paid) ) {
			$date_paid_paydata = $date_paid->date("Y-m-d");
		}

		//If a custom date is set
		if($date) {
			$date_paid_paydata = $date;
		}

		//Create data for the request
		$paydata = array(
			'date' => $date_paid_paydata,
			'price' => round($order->get_total(),2),
			'payment_method' => $this->check_payment_method_options($order->get_payment_method(), 'billingo_id')
		);

		//Mark invoice as paid
		$paid = $billingo->put("documents/{$invoice_id}/payments", apply_filters('wc_billingo_plus_complete', array($paydata), $order));

		//Check for errors
		if(is_wp_error($paid)) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Failed to mark the invoice as paid.', 'wc-billingo-plus');
			$response['messages'][] = $paid->get_error_message();
			$this->log_error_messages($paid, 'generate_invoice_complete-'.$order_id);
			$order->add_order_note( sprintf(esc_html__( 'Failed to mark the Billingo invoice as paid! Error code: %s', 'wc-billingo-plus' ), $paid->get_error_message()));
			return $response;
		}

		//Store as a custom field
		$order->update_meta_data( '_wc_billingo_plus_completed', time() );

		//Update order notes
		$order->add_order_note( esc_html__( 'Invoice successfully marked as paid.', 'wc-billingo-plus' ) );

		//Save order
		$order->save();

		//Response
		$response['completed'] = $date_paid_paydata;

		return $response;
	}

	//Generate void invoice
	public function generate_void_invoice($order_id, $options = array()) {
		$order = wc_get_order($order_id);

		//If we only have a proform invoice but not a normal one, delete it instead of creating a void invoice
		if(!$this->is_invoice_generated($order_id) && $this->is_invoice_generated($order_id, 'proform')) {
			return $this->generate_proform_delete($order_id);
		}

		//Response
		$response = array();
		$response['error'] = false;

		//Load billingo API
		$fixed_key = false;
		if(isset($options['account'])) $fixed_key = sanitize_text_field($options['account']);
		if($order->get_meta('_wc_billingo_plus_account_id')) $fixed_key = $this->get_billingo_api_key_by_id($order->get_meta('_wc_billingo_plus_account_id'));
		$billingo = $this->get_billingo_api($order, false, $fixed_key);

		//Get existing invoice id
		$invoice_id = $order->get_meta('_wc_billingo_plus_invoice_id');
		$receipt_id = $order->get_meta('_wc_billingo_plus_receipt_id');

		if(!$invoice_id && !$receipt_id) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Invoice not found.', 'wc-billingo-plus');
			return $response;
		}

		//Check if we need to convert the invoice id to the new one(v3 api)
		$invoice_id = WC_Billingo_Plus_Helpers::check_legacy_invoice_id($invoice_id, $order_id);

		//Check if we need to cancel a receipt
		if($receipt_id) {
			$invoice_id = $receipt_id;
		}

		//Get void reason
		$data = array();
		if(isset($_POST['reason']) && !empty($_POST['reason'])) {
			$data['cancellation_reason'] = sanitize_textarea_field($_POST['reason']);
		} 

		//Create void invoice
		$invoice_void = $billingo->post("documents/{$invoice_id}/cancel", $data);

		//Check for errors
		if(is_wp_error($invoice_void)) {

			//Create response
			$response['error'] = true;
			$response['messages'][] = esc_html__('Failed to create a reverse invoice.', 'wc-billingo-plus');
			$response['messages'][] = $invoice_void->get_error_message();
			$order->add_order_note(sprintf(esc_html__('Billingo reverse invoice generation failed! Error code: %s', 'wc-billingo-plus'), $invoice_void->get_error_message()));

			//Callbacks
			$this->log_error_messages($invoice, 'generate_void_invoice-'.$order_id);
			do_action('wc_billingo_plus_after_invoice_void_error', $order, $invoice);
			return $response;

		}

		//Save file if needed
		$pdf_file_name = '';
		if($this->get_option('download_invoice', 'no') == 'yes') {

			//Since the PDF is not ready yet, schedule an action to download it later(at least 5 seconds in the future)
			WC()->queue()->schedule_single(
				time()+5,
				'wc_billingo_plus_download_invoice',
				array('order_id' => $order_id, 'document_type' => 'void'),
				'woo_billingo_plus'
			);

			//For now, just store the filename as pending
			$pdf_file_name = 'pending';
			$response['pdf'] = 'pending';

		}

		//Create download link
		$billingo_url = '';
		$get_public_url = $billingo->get("documents/{$invoice_void['id']}/public-url");
		if(is_wp_error($get_public_url)) {
			$this->log_error_messages($get_public_url, 'create_download_link-'.$order_id);
			$response['messages'][] = esc_html__('Failed to create a download link for the invoice.', 'wc-billingo-plus');
		} else {
			$billingo_url = $get_public_url['public_url'];
		}

		//Get invoice data
		$invoice_void_name = $invoice_void['id'];
		if(isset($invoice_void['invoice_number'])) {
			$invoice_void_name = $invoice_void['invoice_number'];
		}
		$invoice_void_id = $invoice_void['id'];
		$invoice_void_pdf = $pdf_file_name;

		//Create response
		$response['name'] = $invoice_void_name;

		//Save void meta
		$order->update_meta_data( '_wc_billingo_plus_void_id', $invoice_void_id );
		$order->update_meta_data( '_wc_billingo_plus_void_name', $invoice_void_name );
		$order->update_meta_data( '_wc_billingo_plus_void_pdf', $invoice_void_pdf );
		$order->update_meta_data( '_wc_billingo_plus_void_url', $billingo_url );

		//Delete existing meta
		$order->delete_meta_data( '_wc_billingo_plus_completed' );
		$order->delete_meta_data( '_wc_billingo_plus_account_id' );
		$types_to_delete = array('invoice', 'proform', 'deposit', 'draft', 'receipt', 'waybill', 'offer');
		foreach ($types_to_delete as $type) {
			$order->delete_meta_data( '_wc_billingo_plus_'.$type.'_id' );
			$order->delete_meta_data( '_wc_billingo_plus_'.$type.'_name' );
			$order->delete_meta_data( '_wc_billingo_plus_'.$type.'_pdf' );
			$order->delete_meta_data( '_wc_billingo_plus_'.$type.'_url' );
		}

		//Update order notes
		$response['messages'][] = esc_html__('Reverse invoice created successfully.','wc-billingo-plus');
		$order->add_order_note(sprintf(esc_html__('Reverse invoice created successfully. Invoice number: %s', 'wc-billingo-plus'), $invoice_void_name));

		//Return download links
		$response['link'] = $this->generate_download_link($order, 'void');

		//Save the order
		$order->save();

		//Action for webhooks
		do_action( 'wc_billingo_plus_document_created', array('order_id' => $order_id, 'document_type' => 'void') );

		return $response;
	}

	//Generate void invoice
	public function generate_proform_delete($order_id, $options = array()) {
		$order = wc_get_order($order_id);

		//Response
		$response = array();
		$response['error'] = false;

		//Load billingo API
		$fixed_key = false;
		if(isset($options['account'])) $fixed_key = sanitize_text_field($options['account']);
		if($order->get_meta('_wc_billingo_plus_account_id')) $fixed_key = $this->get_billingo_api_key_by_id($order->get_meta('_wc_billingo_plus_account_id'));
		$billingo = $this->get_billingo_api($order, false, $fixed_key);

		//Get existing invoice id
		$invoice_id = $order->get_meta('_wc_billingo_plus_proform_id');
		if(!$invoice_id) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Proform not found.', 'wc-billingo-plus');
			return $response;
		}

		//Get name to store in comments
		$invoice_void_name = $order->get_meta('_wc_billingo_plus_proform_name');

		//Create void invoice
		$invoice_void = $billingo->put("documents/{$invoice_id}/archive");

		//Check for errors
		if(is_wp_error($invoice_void)) {

			//Create response
			$response['error'] = true;
			$response['messages'][] = esc_html__('Failed to delete a proform invoice.', 'wc-billingo-plus');
			$response['messages'][] = $invoice_void->get_error_message();
			$order->add_order_note(sprintf(esc_html__('Billingo proform invoice delete failed! Error code: %s', 'wc-billingo-plus'), $invoice_void->get_error_message()));

			//Callbacks
			$this->log_error_messages($invoice, 'generate_proform_delete-'.$order_id);
			do_action('wc_billingo_plus_after_proform_delete_error', $order, $invoice);
			return $response;

		}

		//Delete existing meta
		$order->delete_meta_data( '_wc_billingp_plus_completed' );
		$order->delete_meta_data( '_wc_billingo_plus_account_id' );
		$types_to_delete = array('invoice', 'proform', 'deposit', 'draft', 'receipt', 'waybill', 'offer');
		foreach ($types_to_delete as $type) {
			$order->delete_meta_data( '_wc_billingo_plus_'.$type.'_id' );
			$order->delete_meta_data( '_wc_billingo_plus_'.$type.'_name' );
			$order->delete_meta_data( '_wc_billingo_plus_'.$type.'_pdf' );
			$order->delete_meta_data( '_wc_billingo_plus_'.$type.'_url' );
		}

		//Update order notes
		$response['messages'][] = esc_html__('Proform invoice deleted successfully.','wc-billingo-plus');
		$order->add_order_note(sprintf(esc_html__('Proform invoice deleted successfully. This was the proforma invoice number: %s', 'wc-billingo-plus'), $invoice_void_name));

		//Return download links
		$response['link'] = 'proform_deleted';

		//Save the order
		$order->save();

		//Action for webhooks
		do_action( 'wc_szamlazz_after_proform_delete_success', array('order_id' => $order_id) );

		return $response;
	}

	//Resend email notification
	public function resend_email($order_id) {
		$order = new WC_Order($order_id);

		//Response
		$response = array();
		$response['error'] = false;

		//Load billingo API
		$billingo = $this->get_billingo_api($order);

		//Get existing invoice data
		$invoice_id = $order->get_meta('_wc_billingo_plus_invoice_id');

		//If invoice doesn't exists
		if(!$invoice_id) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Invoice not found.', 'wc-billingo-plus');
			return $response;
		}

		//Setup request
		$paydata = array();

		//Check if we need to convert the invoice id to the new one(v3 api)
		$invoice_id = WC_Billingo_Plus_Helpers::check_legacy_invoice_id($invoice_id, $order_id);

		//Try to send out the invoice
		$email = $billingo->post("documents/{$invoice_id}/send", apply_filters('wc_billingo_plus_send', $paydata, $order));

		//Check for errors
		if(is_wp_error($email)) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Failed to email invoice.', 'wc-billingo-plus');
			$response['messages'][] = $email->get_error_message();
			$this->log_error_messages($email, 'resend_email-'.$order_id);
		}

		return $response;
	}

	//Add download icons to order details page
	public function orders_download_button($actions, $order) {
		$order_id = $order->get_id();

		if($this->get_option('customer_download','no') == 'yes') {
			$document_types = WC_Billingo_Plus_Helpers::get_document_types();

			foreach ($document_types as $document_type => $document_label) {
				if($this->is_invoice_generated($order_id, $document_type)) {
					$link = $this->generate_download_link($order, $document_type);
					$actions['billingo_plus_pdf'] = array(
						'url' => $link,
						'name' => $document_label
					);
				}
			}
		}

		return $actions;
	}

	//Get options stored
	public function get_option($key, $default = '') {
		$settings = get_option( 'woocommerce_wc_billingo_plus_settings', null );
		$value = $default;

		if($settings && isset($settings[$key])) {
			$value = $settings[$key];
		} else if(get_option($key)) {
			$value = get_option($key);
		}

		//Try to get password from wp-config
		if($key == 'api_key' && defined( 'WC_BILLINGO_API_KEY' )) {
			$value = WC_BILLINGO_API_KEY;
		}

		return apply_filters('wc_billingo_plus_get_option', $value, $key);
	}

	//Plugin links
	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . esc_url(admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_billingo_plus' )) . '" aria-label="' . esc_attr__( 'Woo Billingo Plus Settings', 'wc-billingo-plus' ) . '">' . esc_html__( 'Settings', 'wc-billingo-plus' ) . '</a>',
		);
		return array_merge( $action_links, $links );
	}

	public static function plugin_row_meta( $links, $file ) {
		$basename = plugin_basename( WC_BILLINGO_PLUS_PLUGIN_FILE );
		if ( $basename !== $file ) {
			return $links;
		}

		$row_meta = array(
			'documentation' => '<a href="https://visztpeter.me/dokumentacio/" target="_blank" aria-label="' . esc_attr__( 'Woo Billingo Plus Documentation', 'wc-billingo-plus' ) . '">' . esc_html__( 'Documentation', 'wc-billingo-plus' ) . '</a>'
		);

		if (!WC_Billingo_Plus_Pro::is_pro_enabled() ) {
			$row_meta['get-pro'] = '<a target="_blank" rel="noopener noreferrer" style="color:#46b450;" href="https://visztpeter.me/woo-billingo-plus" aria-label="' . esc_attr__( 'Woo Billingo Plus Pro version', 'wc-billingo-plus' ) . '">' . esc_html__( 'Pro version', 'wc-billingo-plus' ) . '</a>';
		}

		return array_merge( $links, $row_meta );
	}

	public function get_invoice_type($order) {
		if($order->get_billing_company()) {
			$type = $this->get_option('invoice_type_company', 'paper');
		} else {
			$type = $this->get_option('invoice_type', 'paper');
		}
		return ($type == 'electronic');
	}

	public function get_rounding_option($order) {
		$order_currency = $order->get_currency();
		$rounding_type = 0;
		$rounding_options = $this->get_option('wc_billingo_plus_rounding_options', array());
		foreach ($rounding_options as $currency => $rounding) {
			if($currency == $order_currency) {
				$rounding_type = intval($rounding);
			}
		}

		$mapping = array(0 => 'none', 1 => 'one', 5 => 'five', 10 => 'ten');
		return $mapping[$rounding_type];
	}

	public function check_payment_method_options($payment_method_id, $option) {
		$found = false;
		$payment_method_options = $this->get_option('wc_billingo_plus_payment_method_options');
		if(isset($payment_method_options[$payment_method_id]) && isset($payment_method_options[$payment_method_id][$option])) {
			$found = $payment_method_options[$payment_method_id][$option];
		}

		//Coupon method for orders without a payment method
		if(!$payment_method_id && $option == 'billingo_id') {
			$found = 'coupon';
		}

		return $found;
	}

	public function get_payment_method_deadline($payment_method_id) {
		$deadline = $this->get_option('payment_deadline');
		$custom_deadline = $this->check_payment_method_options($payment_method_id, 'deadline');
		if($custom_deadline != '' && $custom_deadline !== false) {
			$deadline = $custom_deadline;
		}
		return $deadline;
	}

	public function get_order_item_tax_id($order, $item) {

		//If a fixed value is set in settings
		if($this->get_option('afakulcs') != '') {
			return $this->get_option('afakulcs');
		}

		$tax_item_label = '';

		//Check what is set in woocommerce tax settings
		if(wc_tax_enabled()) {
			$tax_items_labels = array();
			$valid_tax_labels = array('AM', 'EU', 'FAD', 'ÁTHK', 'AAM', 'AKK', 'TAM', 'EUK', 'MAA', 'ÁKK', 'K.AFA');

			//Get all tax labels indexed by rate id
			foreach ( $order->get_items('tax') as $tax_item ) {
				$tax_items_labels[$tax_item->get_rate_id()] = $tax_item->get_label();
			}

			//Get line item tax id and find label
			if(count($tax_items_labels) > 0) {
				$taxes = $item->get_taxes();
				foreach( $taxes['subtotal'] as $rate_id => $tax ){
					$tax_item_label = $tax_items_labels[$rate_id];
				}
			}

			//If its not a valid label
			if(!in_array($tax_item_label, $valid_tax_labels)) {
				$tax_item_label = '';
			}
		}

		//If its a free item, try to get tax class anyway
		if(
			($this->get_option('separate_coupon', 'no') == 'yes' && round($item->get_subtotal(), 2) == 0) ||
			($this->get_option('separate_coupon', 'no') == 'no' && round($item->get_total(), 2) == 0)) {

				//Get the product's tax class(by default its standard, empty)
				$tax_class = '';
				if($item->get_product()) {
					$tax_class = $item->get_product()->get_tax_class();
				}

				//Get the WC_Tax class
				$wc_tax = new WC_Tax();

				//Find rates based on the billing country
				$tax_rates = $wc_tax->find_rates(
					array(
						"tax_class" => $tax_class,
						"country" => $order->get_billing_country(),
				));

				//If rates are found, get the first result and check the label or the rate as a valid tax type
				//Only if the order is taxed
				if($tax_rates && $order->get_items('tax')) {
					$tax_rate = reset($tax_rates);
					$tax_item_label = $tax_rate['label'];
					if(!in_array($tax_item_label, $valid_tax_labels)) {
						$tax_item_label = $tax_rate['rate'].'%';
					}
				}
		}

		//If theres no ID, return percentage value
		if($tax_item_label == '') {
			if($this->get_option('separate_coupon', 'no') == 'yes') {
				if(round($item->get_subtotal(), 2) == 0) {
					$tax_item_label = '0%';
				} else {
					$tax_item_label = round( ($item->get_subtotal_tax()/$item->get_subtotal()) * 100 ).'%';
				}
			} else {
				if(round($item->get_total(), 2) == 0) {
					$tax_item_label = '0%';
				} else {
					$tax_item_label = round( ($item->get_total_tax()/$item->get_total()) * 100 ).'%';
				}
			}
		}

		//If tax is empty, maybe replace it with EU and EUK
		if($tax_item_label == 0 && $order->get_billing_country() != 'HU') {
			$eu_countries = WC()->countries->get_european_union_countries('eu_vat');
			if(in_array($order->get_billing_country(), $eu_countries)) {
				if($this->get_option('afakulcs_eu', 'no') == 'yes') {
					$tax_item_label = 'EU';
				}
			} else {
				if($this->get_option('afakulcs_euk', 'no') == 'yes') {
					$tax_item_label = 'EUK';
				}
			}
		}

		return $tax_item_label;
	}

	public function get_order_shipping_tax_id($order, $shipping_item_obj) {
		$tax_item_label = '';

		//If a fixed value is set in settings
		if($this->get_option('afakulcs') != '') {
			return $this->get_option('afakulcs');
		}

		//Check what is set in woocommerce tax settings
		if(wc_tax_enabled()) {
			$valid_tax_labels = array('AM', 'EU', 'FAD', 'ÁTHK', 'AAM', 'AKK', 'TAM', 'EUK', 'MAA', 'ÁKK');

			$tax_data = $shipping_item_obj->get_taxes();
			foreach ( $order->get_items('tax') as $tax_item ) {
				$tax_item_id = $tax_item->get_rate_id();
				$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';

				if($tax_item_total != '') {
					$tax_item_label = $tax_item->get_label();
				}
			}

			//If its not a valid label
			if(!in_array($tax_item_label, $valid_tax_labels)) {
				$tax_item_label = '';
			}
		}

		if($tax_item_label == '') {
			$order_shipping = $shipping_item_obj->get_total();
			$order_shipping_tax = $shipping_item_obj->get_total_tax();
			if($order_shipping != 0 && $order_shipping != '') {
				$tax_item_label = round(($order_shipping_tax/$order_shipping)*100).'%';
			} else {
				$tax_item_label = '0%';

				//If shipping was free, try to get the first non-free item's tax rate instead
				$order_items = $order->get_items();
				$previous_vat_id = 0;
				foreach ($order_items as $order_item) {
					if($order_item->get_total() != 0) {
						$previous_vat_id = $this->get_order_item_tax_id($order, $order_item);
						break;
					}
				}

				if($previous_vat_id) {
					return $previous_vat_id;
				} else {
					$tax_item_label = '0%';
				}
			}
		}

		return $tax_item_label;
	}

	public function get_coupon_invoice_item_details($order) {
		$details = array(
			"name" => esc_html__('Discount', 'wc-billingo-plus'),
			"comment" => ''
		);

		$order_discount = method_exists( $order, 'get_discount_total' ) ? $order->get_discount_total() : $order->order_discount;
		if ( $order_discount > 0 ) {
			$coupons = implode(', ', $order->get_coupon_codes());
			$discount = strip_tags(html_entity_decode($order->get_discount_to_display()));
			$details["comment"] = sprintf( esc_html__( '%1$s discount with the following coupon code: %2$s', 'wc-billingo-plus' ), $discount, $coupons );

			if($this->get_option('separate_coupon_name')) {
				$details["name"] = $this->get_option('separate_coupon_name');
			}

			if($this->get_option('separate_coupon_desc')) {
				$discount_note_replacements = array('{kedvezmeny_merteke}' => $discount, '{kupon}' => $coupons);
				$discount_note = str_replace( array_keys( $discount_note_replacements ), array_values( $discount_note_replacements ), $this->get_option('separate_coupon_desc'));
				$discount_note = WC_Billingo_Plus_Helpers::replace_coupon_placeholders($discount_note, $order);
				$details["comment"] = $discount_note;
			}
		}

		return apply_filters('wc_billingo_plus_coupon_invoice_item_details', $details, $order);
	}

	//Start a new billingo api request
	public function get_billingo_api($order, $return_key = false, $fixed_key = false) {
		$api_key = '';

		if(isset($_POST['woocommerce_wc_billingo_plus_api_key'])) {

			//This is after the admin settings saved, so blocks, vat ids and stuff already loaded on refresh
			$api_key = esc_attr($_POST['woocommerce_wc_billingo_plus_api_key']);

		} else {

			//Check if its manually created, if so, $_POST might have a key set
			if(isset( $_POST['action']) && $_POST['action'] == 'wc_billingo_plus_generate_invoice' && isset($_POST['account'])) {
				$keys = sanitize_text_field($_POST['account']);
				$keys = explode(':', $keys);
				$keys = array(
					'api_key' => $keys[0],
				);
			} else {
				$keys = $this->get_billingo_keys($order);
			}

			$api_key = $keys['api_key'];
		}

		if($return_key) {

			return $api_key;

		} else {

			//If a fixed key is set
			if($fixed_key) $api_key = $fixed_key;

			return new WC_Billingo_Plus_Api($api_key);

		}
	}

	public function get_block_id($order, $type = 'invoice') {
		$block_id = '';

		if(isset( $_POST['action']) && $_POST['action'] == 'wc_billingo_plus_generate_invoice' && isset($_POST['account'])) {
			$keys = sanitize_text_field($_POST['account']);
			$keys = explode(':', $keys);
			$block_id = (int)$keys[2];
		} else {
			$keys = $this->get_billingo_keys($order);
			$block_id = (int)$keys['block_id'];

			if($type == 'waybill') {
				$block_id = $this->get_option('waybill_block_id');
			}

			if($type == 'offer') {
				$block_id = $this->get_option('offer_block_id');
			}

			//If its a receipt
			if($order->get_meta('_wc_billingo_plus_type_receipt')) {
				$block_id = $this->get_option('receipt_block');
			}

		}

		return $block_id;
	}

	public function get_bank_account_id($order) {
		$bank_account_id = '';

		if(isset( $_POST['action']) && $_POST['action'] == 'wc_billingo_plus_generate_invoice' && isset($_POST['account'])) {
			$keys = sanitize_text_field($_POST['account']);
			$keys = explode(':', $keys);
			$bank_account_id = (int)$keys[1];
		} else {
			$keys = $this->get_billingo_keys($order);
			$bank_account_id = (int)$keys['bank_account_id'];
		}

		return $bank_account_id;
	}

	public function get_billingo_invoice_blocks($refresh = false) {
		$invoice_blocks = get_transient('wc_billingo_plus_invoice_blocks');

		if (!$invoice_blocks || $refresh) {

			//Load billingo API
			$billingo = $this->get_billingo_api(false);
			$billingo_invoice_blocks = false;

			//Get blocks
			$billingo_invoice_blocks = $billingo->get('document-blocks');
			if ( is_wp_error( $billingo_invoice_blocks ) ) {
				$this->log_error_messages($billingo_invoice_blocks, 'get_billingo_invoice_blocks');
			}

			//Create a simple array
			$invoice_blocks = array();
			if(is_array($billingo_invoice_blocks)) {
				foreach ($billingo_invoice_blocks as $billingo_invoice_block) {
					$invoice_blocks[$billingo_invoice_block['id']] = $billingo_invoice_block['name'];
				}
			}

			//Save vat ids for a day
			set_transient('wc_billingo_plus_invoice_blocks', $invoice_blocks, 60 * 60 * 24);
		}

		return $invoice_blocks;
	}

	public function get_billingo_bank_accounts($refresh = false) {
		$bank_accounts = get_transient('wc_billingo_plus_bank_accounts');
		if (!$bank_accounts || $refresh) {

			//Load billingo API
			$billingo = $this->get_billingo_api(false);
			$billingo_bank_accounts = false;

			//Get bank accounts
			$billingo_bank_accounts = $billingo->get('bank-accounts');

			if ( is_wp_error( $billingo_bank_accounts ) ) {
				$this->log_error_messages($billingo_bank_accounts, 'get_billingo_bank_accounts');
			}

			//Create a simple array
			$bank_accounts = array();

			if(is_array($billingo_bank_accounts)) {
				foreach ($billingo_bank_accounts as $billingo_bank_account) {
					$bank_accounts[$billingo_bank_account['id']] = $billingo_bank_account['name'];
				}
			}

			//Save bank accounts for a day
			set_transient('wc_billingo_plus_bank_accounts', $bank_accounts, 60 * 60 * 24);
		}

		return $bank_accounts;
	}

	//Log error message if needed
	public function log_error_messages($e, $source) {
		$logger = wc_get_logger();
		$error = $e;

		if(is_wp_error( $e )) {
			$error = $e->get_error_message();
		}

		$logger->error(
			$source.' - '.$error,
			array( 'source' => 'wc_billingo_plus' )
		);
	}

	//Log debug messages if needed
	public function log_debug_messages($data, $source, $force = false) {
		if($this->get_option('debug', 'no') == 'yes' || $force) {
			$logger = wc_get_logger();
			$logger->debug(
				$source.' - '.json_encode($data),
				array( 'source' => 'wc_billingo_plus' )
			);
		}
	}

	//Disable invoice generation for free orders
	public function disable_invoice_for_free_order($order_id, $data, $order) {
		$order_total = $order->get_total();
		if($order_total == 0 && ($this->get_option('disable_free_order', 'yes') == 'yes')) {
			$order->update_meta_data( '_wc_billingo_plus_own', __('Invoices not required for free orders', 'wc-billingo-plus') );
			$order->save();
		}
	}

	//Calculate gross or net prices for the invoice line items correctly
	public function calculate_item_prices($args) {
		unset($args['product_item']['unit_price']);
		unset($args['product_item']['unit_price_type']);

		//Fix for some edge cases
		if($args['net'] == '') {
			$args['net'] = 0;
		}

		//Check if we have net or gross prices set in WooCommerce
		if ( wc_prices_include_tax() ) {

			//Rounding precision. For HUF orders, we are rounding gross to 0 decimals
			$rounding = ($args['order']->get_currency() == 'HUF') ? 0 : wc_get_price_decimals();
			$rounding = apply_filters('wc_billingo_plus_gross_unit_price_rounding_precision', $rounding, $args);
			$gross_unit_price = round(($args['net']+$args['tax'])/$args['product_item']['quantity'], $rounding);
			$args['product_item']['unit_price'] = $gross_unit_price;
			$args['product_item']['unit_price_type'] = 'gross';
		} else {
			$args['product_item']['unit_price'] = $args['net']/$args['product_item']['quantity'];
			$args['product_item']['unit_price_type'] = 'net';
		}

		return $args['product_item'];
	}

	//Get order note
	public function get_invoice_note($order, $document_type, $invoice_lang) {

		//If we don't have any notes, try to return the old one
		$notes = get_option('wc_billingo_plus_notes');
		if(!$notes) return $this->get_option('note');

		//Custom conditions
		$order_details = WC_Billingo_Plus_Conditions::get_order_details($order, 'notes');
		$order_details['language'] = $invoice_lang;
		$order_details['document'] = $document_type;

		//We will return a single note at the end
		$final_note = '';

		//Loop through each note
		foreach ($notes as $note_id => $note) {

			//If this is based on a condition
			if($note['conditional']) {

				//Compare conditions with order details and see if we have a match
				$note_is_a_match = WC_Billingo_Plus_Conditions::match_conditions($notes, $note_id, $order_details);

				//If its not a match, continue to next not
				if(!$note_is_a_match) continue;

				//Check if we need to append or replace the text
				if($note['append']) {
					$final_note .= "\n".$note['comment'];
				} else {
					$final_note = $note['comment'];
				}

			} else {
				$final_note = $note['comment'];
			}

		}

		return $final_note;
	}

	//Get available billingo accounts
	public function get_billingo_accounts() {
		$accounts = array(
			$this->get_option('api_key').':'.$this->get_option('bank_account_id').':'.$this->get_option('block_uid') => 'Alapértelmezett'
		);

		$extra_accounts_enabled = $this->get_option('multiple_accounts', 'no');
		$extra_accounts = get_option('wc_billingo_plus_additional_accounts');
		if($extra_accounts && $extra_accounts_enabled == 'yes') {
			foreach ($extra_accounts as $extra_account) {
				$accounts[$extra_account['api_key'].':'.$extra_account['bank_account_id'].':'.$extra_account['block_id']] = $extra_account['name'];
			}
		}

		return $accounts;
	}

	//Get account thats related to the order
	public function get_billingo_keys($order, $string = false) {

		//Default key
		$keys = array(
			'api_key' => $this->get_option('api_key', ''),
			'bank_account_id' => $this->get_option('bank_account', ''),
			'block_id' => (int)$this->get_option('block_uid')
		);

		//Get accounts
		$extra_accounts_enabled = $this->get_option('multiple_accounts', 'no');
		$extra_accounts = get_option('wc_billingo_plus_additional_accounts');
		$conditions = array();

		//Return if just a single account is setup
		if($extra_accounts_enabled == 'no' || !$extra_accounts || empty($extra_accounts) || !$order) {
			return $keys;
		}

		//Get payment method id
		$conditions[] = $order->get_payment_method();

		//Get shipping method id
		$shipping_method = '';
		$shipping_methods = $order->get_shipping_methods();
		if($shipping_methods) {
			foreach( $shipping_methods as $shipping_method_obj ){
				$conditions[] = $shipping_method_obj->get_method_id().':'.$shipping_method_obj->get_instance_id();
			}
		}

		//Get product category ids
		$product_categories = array();
		$order_items = $order->get_items();
		foreach ($order_items as $order_item) {
			if($order_item->get_product() && $order_item->get_product()->get_category_ids()) {
				$product_categories = $product_categories+$order_item->get_product()->get_category_ids();
			}
		}

		//Append to conditions
		foreach ($product_categories as $category_id) {
			$conditions[] = 'product_cat_'.$category_id;
		}

		//Get currency
		$conditions[] = $order->get_currency();

		//Get order type
		$conditions[] = ($order->get_billing_company()) ? 'order-company' : 'order-individual';

		//Custom conditions
		$conditions = apply_filters('wc_billingo_plus_account_conditions_values', $conditions, $order);

		//Find a matching account
		foreach ($extra_accounts as $extra_account) {
			if($extra_account['condition'] && in_array($extra_account['condition'], $conditions)) {
				$keys = array(
					'api_key' => $extra_account['api_key'],
					'bank_account_id' => $extra_account['bank_account_id'],
					'block_id' => $extra_account['block_id']
				);
			}
		}

		if($string) {
			$keys = $keys['api_key'].':'.$keys['bank_account_id'].':'.$keys['block_id'];
		}

		return $keys;
	}

	//Get account by id
	public function get_billingo_api_key_by_id($key_id) {

		//Default key
		$key = $this->get_option('agent_key', '');

		//Get accounts
		$extra_accounts_enabled = $this->get_option('multiple_accounts', 'no');
		$extra_accounts = get_option('wc_billingo_plus_additional_accounts');

		//Return if just a single account is setup
		if($extra_accounts_enabled == 'no' || !$extra_accounts || empty($extra_accounts)) {
			return $key;
		}

		foreach ($extra_accounts as $extra_account) {
			if(substr($extra_account['api_key'], 0, 5) == $key_id) {
				$key = $extra_account['api_key'];
			}
		}

		return $key;
	}

}

//WC Detection
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ;
	}
}


//WooCommerce inactive notice.
function wc_billingo_plus_woocommerce_inactive_notice() {
	if ( current_user_can( 'activate_plugins' ) ) {
		echo '<div id="message" class="error"><p>';
		printf( __( '%1$sWoo Billingo Plus is inactive%2$s. The %3$s1$sWooCommerce plugin %4$s must be active. %5$sPlease install or activate the latest WooCommerce &raquo;%6$s', 'wc-billingo-plus' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' );
		echo '</p></div>';
	}
}

//Initialize
if ( is_woocommerce_active() ) {
	function WC_Billingo_Plus() {
		return WC_Billingo_Plus::instance();
	}

	//For backward compatibility
	$GLOBALS['wc_billingo_plus'] = WC_Billingo_Plus();
} else {
	add_action( 'admin_notices', 'wc_billingo_plus_woocommerce_inactive_notice' );
}
