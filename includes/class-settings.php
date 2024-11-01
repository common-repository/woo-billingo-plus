<?php

if ( ! class_exists( 'WC_Billingo_Plus_Settings' ) ) :

class WC_Billingo_Plus_Settings extends WC_Integration {
	public static $activation_url;

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id = 'wc_billingo_plus';
		$this->method_title = __( 'Woo Billingo Plus', 'wc-billingo-plus' );

		// Load the settings.
		if(isset($_GET['tab']) && ($_GET['tab'] == 'integration' || $_GET['tab'] == 'debug')) {
			$this->migrate_to_v3_api();
			$this->init_form_fields();
			$this->init_settings();
		}

		//Customize admin screen design and layout
		add_filter( 'admin_body_class', array( $this, 'add_class_to_body') );
		add_action( 'woocommerce_sections_integration', array($this, 'wrap_start'), 20 );
		add_action( 'woocommerce_settings_integration', array($this, 'wrap_end'), 20 );

		// Action to save the fields
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'save_payment_options' ) );

		//Ajax function to trigger migrate
		add_action( 'wp_ajax_wc_billingo_plus_migrate', array( $this, 'migrate_settings' ) );

		//Ajax functions on settings page
		add_action( 'wp_ajax_wc_billingo_plus_get_email_ids', array( $this, 'get_email_ids_with_ajax' ) );
		add_action( 'wp_ajax_wc_billingo_plus_reload_block_uid', array( $this, 'reload_blocks' ) );
		add_action( 'wp_ajax_wc_billingo_plus_reload_receipt_block', array( $this, 'reload_blocks' ) );
		add_action( 'wp_ajax_wc_billingo_plus_reload_bank_account', array( $this, 'reload_bank_accounts' ) );
		add_action( 'wp_ajax_wc_billingo_plus_hide_rate_request', array( $this, 'hide_rate_request' ) );

		//Define activation url
		self::$activation_url = 'https://visztpeter.me/';

	}

	public function migrate_to_v3_api() {

		//If already installed
		$payment_method_options = get_option('wc_billingo_plus_payment_method_options');
		$db_version = get_option('_wc_billingo_plus_db_version');

		if($payment_method_options && (!$db_version || $db_version != 'v3')) {
			update_option('_wc_billingo_plus_db_version', 'v3');

			//Migrate payment method ids
			$payment_method_id_pairs = array(
				'5' => 'bankcard',
				'14' => 'barion',
				'1' => 'cash',
				'4' => 'cash_on_delivery',
				'20' => 'elore_utalas',
				'17' => 'online_bankcard',
				'7' => 'paypal',
				'16' => 'paypal_utolag',
				'26' => 'pick_pack_pont',
				'6' => 'szep_card',
				'25' => 'transferwise',
				'2' => 'wire_transfer'
			);

			foreach ($payment_method_options as $payment_method_id => $payment_method_option) {
				if($payment_method_option['billingo_id'] && isset($payment_method_id_pairs[$payment_method_option['billingo_id']])) {
					$payment_method_options[$payment_method_id]['billingo_id'] = $payment_method_id_pairs[$payment_method_option['billingo_id']];
				}
			}
			update_option('wc_billingo_plus_payment_method_options', $payment_method_options);

			//Migrate tax settings
			$existing_tax_setting = $this->get_option('afakulcs');
			$tax_id_pairs = array(
				'4' => 'AM',
				'5' => 'EU',
				'559' => 'FAD',
				'939' => 'ÁTHK',
				'992' => 'AAM',
				'993' => 'ÁKK',
				'994' => 'TAM',
				'995' => 'EUK',
				'1247' => 'MAA',
				'1826' => 'F.AFA',
				'1827' => 'K.AFA',
				'1828' => 'ÁKK'
			);
			if($existing_tax_setting && in_array($existing_tax_setting, array_keys($tax_id_pairs))) {
				$this->update_option('afakulcs', $tax_id_pairs[$existing_tax_setting]);
			}

			//Reset invoice blocks
			$this->update_option('block_uid', 0);

			//If theres no unit type set, set it to default(required for v3 api)
			$unit_type = $this->get_option('unit_type', '');
			if(!$unit_type) {
				$this->update_option('unit_type', __('pcs', 'wc-billingo-plus'));
			}

		}

	}

	public function migrate_settings() {
		check_ajax_referer( 'wc-billingo-plus-migrate', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; huh?' ) );
		}

		//Check if already migrated
		if(get_option('_wc_billingo_plus_migrated')) {
			return false;
		}

		//Gather data
		$option_pairs = array(
			'invoice_block' => 'block_uid',
			'note' => 'note',
			'invoice_lang' => 'language',
			'email' => 'auto_email',
			'api_key' => 'api_key'
		);

		foreach ($option_pairs as $old_option_id => $new_option_id) {
			$old_option_value = get_option('wc_billingo_'.$old_option_id);
			$this->update_option($new_option_id, $old_option_value);
		}

		if(get_option('wc_billingo_electronic') == 'yes') {
			$this->update_option('invoice_type', 'electronic');
		}

		//Save payment options
		$accounts = array();
		$payment_methods = $this->get_payment_methods();
		foreach ($payment_methods as $payment_method_id => $payment_method) {
			$billingo_id = (int)get_option('wc_billingo_payment_method_' . $payment_method_id);
			$deadline = (int)get_option('wc_billingo_paymentdue_' . $payment_method_id);
			$complete = (int)get_option('wc_billingo_mark_as_paid_' . $payment_method_id);
			$proform =	(int)get_option('wc_billingo_proforma_' . $payment_method_id);

			if($billingo_id) {
				$accounts[$payment_method_id] = array(
					'billingo_id' => $billingo_id,
					'deadline' => $deadline,
					'complete' => ($complete) ? true : false,
					'proform' => ($proform) ? true : false
				);
			}
		}
		update_option( 'wc_billingo_plus_payment_method_options', $accounts );

		//Migrate orders too. This might take a while
		WC()->queue()->add( 'wc_billingo_plus_migrate_orders', array(), 'wc-billingo-plus' );
		update_option('_wc_billingo_plus_migrating', true);

		//Disable original plugin
		deactivate_plugins('/billingo/index.php');

		wp_die();
	}

	//Check if we are on the settings page
	public function is_settings_page() {
		global $current_section;
		$is_settings_page = false;
		if( isset( $_GET['page'], $_GET['tab'] ) && 'wc-settings' === $_GET['page'] && 'integration' === $_GET['tab'] ) {
			if ( !$current_section ) {
				$integrations = WC()->integrations->get_integrations();

				if(!empty($integrations)) {
					$current_section_id = current( $integrations )->id;
					if($current_section_id === $this->id) {
						$is_settings_page = true;
					}
				}
			} else if($current_section === $this->id) {
				$is_settings_page = true;
			}
		}
		return $is_settings_page;
	}

	//Add class to body for styling purposes
	public function add_class_to_body($extra_class) {
		if($this->is_settings_page()) {
			$extra_class = $this->id.'_settings_page';
		}

		return $extra_class;
	}

	//Wrap the content in two divs
	public function wrap_start() {
		if($this->is_settings_page()) {
			echo '<div class="wc-billingo-plus-settings-wrapper">';
			echo '<div class="wc-billingo-plus-settings-content">';
		}
	}

	//Show the sidebar too
	public function wrap_end() {
		if($this->is_settings_page()) {
			echo '</div>';
			include( dirname( __FILE__ ) . '/views/html-admin-sidebar.php' );
			echo '</div>';
		}
	}

	//Initialize integration settings form fields.
	public function init_form_fields() {
		$pro_required = false;
		$pro_icon = false;
		if(!WC_Billingo_Plus_Pro::is_pro_enabled()) {
			$pro_required = true;
			$pro_icon = '<i class="wc_billingo_pro_label">PRO</i>';
		}

		//Authentication settings
		$settings_top = array(
			'section_auth' => array(
				'title' => __( 'Account settings', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_title',
				'description' => __( 'Enter the API Key V3 that you generated on Billingo.', 'wc-billingo-plus' ),
			),
			'api_key' => array(
				'title' => __( 'API V3 key', 'wc-billingo-plus' ),
				'type' => 'text',
			),
			'multiple_accounts' => array(
				'title' => __( 'I have multiple accounts', 'wc-billingo-plus' ).$pro_icon,
				'type' => 'checkbox',
				'disabled' => $pro_required,
				'class' => 'wc-billingo-plus-toggle-group-accounts',
				'description' => __('You can set up more accounts based on various conditions, like payment and shipping methods.', 'wc-billingo-plus')
			),
			'multiple_accounts_table' => array(
				'type' => 'wc_billingo_plus_settings_accounts',
				'class' => 'wc-billingo-plus-toggle-group-accounts-item',
				'description' => __('If the condition is not matched, it will use the default API Key for automatic invoice generation. You can change the account if you are creating an invoice manually.', 'wc-billingo-plus')
			)
		);

		$settings_rest = array(

			//General settings
			'invoice_settings' => array(
				'title' => __( 'Invoice settings', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_title',
				'description' => __( 'General settings related to invoices.', 'wc-billingo-plus' ),
			),
			'bank_account' => array(
				'title' => __( 'Bank account', 'wc-billingo-plus' ),
				'type' => 'select',
				'class' => 'chosen_select test',
				'options' => $this->get_bank_accounts(),
				'description' => $this->get_fetch_error_message('bank_accounts').__("You can register your bank account number on Billingo's website under Settings / Bank accounts. If you don't see your bank account in the dropdown, click on the refresh icon.", 'wc-billingo-plus' )
			),
			'block_uid' => array(
				'title' => __( 'Invoice block', 'wc-billingo-plus' ),
				'type'		 => 'select',
				'class' => 'chosen_select',
				'options' => $this->get_invoice_blocks(),
				'description'		 => $this->get_fetch_error_message('blocks').__("You can create a document block on Billingo's website under Settings / Document blocks. If you don't see your document block in the dropdown, click on the refresh icon.", 'wc-billingo-plus' )
			),
			'invoice_type' => array(
				'title' => __( 'Invoice type', 'wc-billingo-plus' ),
				'class' => 'chosen_select',
				'css' => 'min-width:300px;',
				'type' => 'select',
				'options' => array(
					'electronic' => __( 'Electronic', 'wc-billingo-plus' ),
					'paper' => __( 'Paper', 'wc-billingo-plus' )
				)
			),
			'invoice_type_company' => array(
				'title' => __( 'Invoice type on company orders', 'wc-billingo-plus' ),
				'class' => 'chosen_select',
				'css' => 'min-width:300px;',
				'type' => 'select',
				'options' => array(
					'' => __( 'Default', 'wc-billingo-plus' ),
					'electronic' => __( 'Electronic', 'wc-billingo-plus' ),
					'paper' => __( 'Paper', 'wc-billingo-plus' )
				),
				'desc_tip' => __( "Invoice type for company orders(if the customer entered a company name at the checkout form, it's a company order).", 'wc-billingo-plus')
			),
			'payment_deadline' => array(
				'title' => __( 'Payment deadline(days)', 'wc-billingo-plus' ),
				'type' => 'number'
			),
			'default_complete_date' => array(
				'title' => __( 'Default completion date', 'wc-billingo-plus' ),
				'type' => 'select',
				'options' => array(
					'order_created' => _x('Order created', 'Invoice complete date type', 'wc-billingo-plus'),
					'payment_complete' => _x('Payment complete', 'Invoice complete date type', 'wc-billingo-plus'),
					'now' => _x('Document created(today)', 'Invoice complete date type', 'wc-billingo-plus'),
				),
				'default' => 'now',
				'desc_tip' => __( "This will be used as the completion date on the invoice by default. You can overwrite this when you manually create an invoice, or with a custom automation.", 'wc-billingo-plus')
			),
			'notes' => array(
				'title' => __( 'Notes', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_notes',
				'description' => __("You can use the following shortcodes in the description that appears on the invoice:<br>{customer_email} - The customer's e-mail address<br>{customer_phone} - The customer's phone number<br>{transaction_id} - Payment's transaction ID<br>{order_number} - Order number<br>{shipping_address} - Shipping address<br>{customer_notes} - Customer notes", "wc-billingo-plus")
			),
			'hide_item_notes' => array(
				'title' => __( 'Hide line item notes', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __("If turned on, it will hide the note section of the line items.", 'wc-billingo-plus')
			),
			'afakulcs' => array(
				'type'		 => 'select',
				'class' => 'chosen_select',
				'title' => __( 'VAT rates', 'wc-billingo-plus' ),
				'options' => $this->get_vat_options(),
				'description' => $this->get_fetch_error_message().__( "The VAT rate that is visible on the invoice. By default, it will use the values set in WooCommerce / Tax menu, if the values match the following tax types: AM, EU, FAD, ÁTHK, AAM, AKK, TAM, EUK, MAA. If there's no match, it will calculate the percentage based on the net and gross prices.", 'wc-billingo-plus' ),
				'default' => ''
			),
			'vat_overrides_custom' => array(
				'type' => 'checkbox',
				'disabled' => $pro_required,
				'title' => __( 'Custom VAT rate overrrides', 'wc-billingo-plus' ).$pro_icon,
				'description' => __( 'Advanced settings to setup VAT rates.', 'wc-billingo-plus' ),
				'class' => 'wc-billingo-plus-toggle-group-vat-override',
			),
			'vat_overrides' => array(
				'type' => 'wc_billingo_plus_settings_vat_overrides',
				'title' => '',
				'class' => 'wc-billingo-plus-toggle-group-vat-override-item'
			),
			'afakulcs_eu' => array(
				'type'		 => 'checkbox',
				'title' => __( 'EU vat rate', 'wc-billingo-plus' ),
				'description' => __( "If there's 0% VAT on the invoice, but the customer entered an EU VAT Number, it will use the EU vat rate instead of 0%.", 'wc-billingo-plus' ),
				'default' => ''
			),
			'afakulcs_euk' => array(
				'type'		 => 'checkbox',
				'title' => __( 'EUK vat rate', 'wc-billingo-plus' ),
				'description' => __( "If there's 0% VAT on the invoice and the customer's billing and shipping location is outside of the EU, it will use the EUK vat rate.", 'wc-billingo-plus' ),
				'default' => ''
			),
			'language' => array(
				'type'		 => 'select',
				'class' => 'chosen_select',
				'title' => __( 'Invoice language', 'wc-billingo-plus' ),
				'options' => WC_Billingo_Plus_Helpers::get_supported_languages(),
				'default' => 'hu'
			),
			'language_wpml' => array(
				'title' => __( 'WPML, Polylang & Translatepress compatibility', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __('If turned on, the language code stored by WPML, Polylang, or Translatepress will be used to create the invoice.', 'wc-billingo-plus')
			),
			'unit_type' => array(
				'title' => __( 'Quantity unit', 'wc-billingo-plus' ),
				'type' => 'text',
				'default' => __('pcs', 'wc-billingo-plus'),
				'desc_tip' => __('This will be the default quantity unit on the invoice. You can change this for each product one-by-one on the Advanced tab too.', 'wc-billingo-plus')
			),
			'sku' => array(
				'type'		 => 'checkbox',
				'title' => __( 'Show SKUs', 'wc-billingo-plus' ),
				'description' => __( 'Show the product SKU in the invoice line item description.', 'wc-billingo-plus' ),
				'default' => ''
			),
			'company_name' => array(
				'title' => __( 'Company name + name', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __('If turned on and the buyer enters a company name, the regular first/last name will be visible after the company name on the invoice.', 'wc-billingo-plus')
			),
			'rounding' => array(
				'title' => __( 'Rounding', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_rounding'
			),
			'draft' => array(
				'title' => __( 'Create drafts', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'description' => __('If turned on, during automatic invoice generation, it will create drafts instead of real invoices. You can still create invoices manually.', 'wc-billingo-plus')
			),
			'advanced_settings' => array(
				'type' => 'checkbox',
				'disabled' => $pro_required,
				'title' => __( 'Advanced settings', 'wc-billingo-plus' ).$pro_icon,
				'description' => __( 'Overwrite the bank account number, the invoice block and the language based on conditional logic.', 'wc-billingo-plus' ),
				'class' => 'wc-billingo-plus-toggle-group-advanced',
			),
			'advanced' => array(
				'type' => 'wc_billingo_plus_settings_advanced',
				'title' => '',
				'class' => 'wc-billingo-plus-toggle-group-advanced-item'
			),
			/*
			'offer_block_id' => array(
				'title' => __( 'Offer block(optional)', 'wc-billingo-plus' ),
				'type'		 => 'select',
				'class' => 'chosen_select',
				'options' => $this->get_invoice_blocks(),
				'description'		 => __("If you plan to generate offers for orders, select the block for offers.", 'wc-billingo-plus' )
			),
			'waybill_block_id' => array(
				'title' => __( 'Waybill block(optional)', 'wc-billingo-plus' ),
				'type'		 => 'select',
				'class' => 'chosen_select',
				'options' => $this->get_invoice_blocks(),
				'description'		 => __("If you plan to generate waybills for orders, select the block for waybills.", 'wc-billingo-plus' )
			),
			*/

			//Settings related to automation
			'section_automatic' => array(
				'title' => __( 'Automatization', 'wc-billingo-plus' ).$pro_icon,
				'type' => 'wc_billingo_plus_settings_title',
				'description' => __( 'Settings related to automatic invoicing. If the mark as completed option is checked for a specific payment method, the invoice will be marked as paid in Billingo if you close the order. If you turn on the proforma invoice option, a proforma invoice will be created for the order. The payment deadline can be set individually(the global option is above)', 'wc-billingo-plus' ),
			),
			'auto_invoice_custom' => array(
				'type' => 'checkbox',
				'disabled' => $pro_required,
				'title' => __( 'Custom automations', 'wc-billingo-plus' ),
				'description' => __( 'Advanced settings to setup automations.', 'wc-billingo-plus' ),
				'class' => 'wc-billingo-plus-toggle-group-automation',
			),
			'automations' => array(
				'type' => 'wc_billingo_plus_settings_automations',
				'title' => '',
				'class' => 'wc-billingo-plus-toggle-group-automation-item'
			),
			'auto_invoice_status' => array(
				'disabled' => $pro_required,
				'type' => 'wc_billingo_plus_settings_auto_status',
				'title' => __( 'Automatic billing', 'wc-billingo-plus' ),
				'options' => $this->get_order_statuses(),
				'description' => __( 'The invoice will be generated automatically if the order is in this status.', 'wc-billingo-plus' ),
				'class' => 'wc-billingo-plus-toggle-group-automation-item-hide',
			),
			'auto_void_status' => array(
				'disabled' => $pro_required,
				'type' => 'wc_billingo_plus_settings_auto_status',
				'title' => __( 'Automatic reverse invoice', 'wc-billingo-plus' ),
				'options' => $this->get_order_statuses(),
				'description' => __( 'A reverse invoice will be generated automatically if the order is in this status.', 'wc-billingo-plus' ),
				'class' => 'wc-billingo-plus-toggle-group-automation-item-hide',
			),
			'payment_methods' => array(
				'title' => __( 'Payment methods', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_payment_methods',
				'disabled' => $pro_required,
			),
			'bank_sync' => array(
				'type' => 'checkbox',
				'disabled' => $pro_required,
				'title' => __( 'Sync bank transactions', 'wc-billingo-plus' ),
				'description' => __( 'If turned on, this extension will check for unpaid invoices and if it was marked as paid in Billingo, it will be stored in the order data too.', 'wc-billingo-plus' ),
				'class' => 'wc-billingo-plus-toggle-group-sync',
			),
			'bank_sync_interval' => array(
				'title' => __( 'Sync interval(minutes)', 'wc-billingo-plus' ),
				'disabled' => $pro_required,
				'type'		 => 'number',
				'description' => __( 'It will check for paid invoices every X minutes in the Billingo system. Recommended value is 30 minutes.', 'wc-billingo-plus' ),
				'default' => 30,
				'class' => 'wc-billingo-plus-toggle-group-sync-item',
			),
			'bank_sync_status' => array(
				'title' => __( 'Order status after syncing', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_auto_status_sync',
				'options' => $this->get_order_statuses_for_sync(),
				'disabled' => $pro_required,
				'default' => 'no',
				'class' => 'wc-billingo-plus-toggle-group-sync-item',
				'description' => __( 'If an invoice was marked as paid in Billingo, the order will be marked with this status.', 'wc-billingo-plus' ),
			),

			//Settings related to coupons
			'section_coupons' => array(
				'title' => __( 'Discounts', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_title',
				'description' => __( 'Settings related to discounts and coupons on the invoice', 'wc-billingo-plus' ),
			),
			'separate_coupon' => array(
				'title' => __( 'Discount as a separate line item', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'class'		 => 'wc-billingo-plus-toggle-group-coupon',
				'desc_tip' => __('If turned on, the discount will be a new separate negative line item instead of the reduced prices shown for each order item.', 'wc-billingo-plus')
			),
			'separate_coupon_name' => array(
				'title' => __( 'Discount line item name', 'wc-billingo-plus' ),
				'type' => 'text',
				'class'		 => 'wc-billingo-plus-toggle-group-coupon-item',
				'placeholder' => __('Discount', 'wc-billingo-plus'),
				'desc_tip' => __('This is the line item name if a coupon is applied to the order. The default value is "Discount"', 'wc-billingo-plus')
			),
			'separate_coupon_desc' => array(
				'title' => __( 'Discount line item description', 'wc-billingo-plus' ),
				'type' => 'textarea',
				'class'		 => 'wc-billingo-plus-toggle-group-coupon-item',
				'placeholder' => __('{kedvezmeny_merteke} discount with the following coupon code: {kupon}', 'wc-billingo-plus'),
				'desc_tip' => __("If turned on, the discount will be a new separate negative order item and you can change it's description here. Default value: {kedvezmeny_merteke} discount with the following coupon code: {kupon}. Use these shortcodes: {coupon_description}, {coupon_amount}, {coupon_code}", 'wc-billingo-plus')
			),
			'discount_note' => array(
				'title' => __( 'Discounted product note', 'wc-billingo-plus' ),
				'type' => 'textarea',
				'desc_tip' => __('You can display the original price and the amount of the discount in the comment of the line item. Use these shortcodes: {eredeti_ar}, {kedvezmeny_merteke}, {kedvezmenyes_ar}, {coupon_description}, {coupon_amount}, {coupon_code}', 'wc-billingo-plus')
			),
			'hide_free_shipping' => array(
				'title' => __( 'Hide free shipping', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __('If turned on, this will hide the free shipping invoice line item.', 'wc-billingo-plus')
			),
			'disable_free_order' => array(
				'title' => __( 'Do not create an invoice for free orders', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __("If turned on, it won't create an invoice automatically for free orders.", 'wc-billingo-plus'),
				'default' => 'yes'
			),

			//Settings related to VAT number fields
			'section_vat_number' => array(
				'title' => __( 'VAT number', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_title',
				'description' => __( 'If the customer is a company, you are required to collect a VAT number. You can use these options to show an extra field on the checkout form.', 'wc-billingo-plus' ),
			),
			'vat_number_form' => array(
				'title' => __( 'VAT number field during checkout', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'class' => 'wc-billingo-plus-toggle-group-vatnumber',
				'default' => 'yes',
				'desc_tip' => __( 'It will be collected at the end of the checkout form. It is stored in the order details and will be visible on the invoice too. If you need to change this after the order has been created, you can use the \"adoszam\" custom field', 'wc-billingo-plus' ),
			),
			'vat_number_always_show' => array(
				'title' => __( 'VAT number field always visible', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'default' => 'no',
				'class' => 'wc-billingo-plus-toggle-group-vatnumber-item',
				'desc_tip' => __( "If turned on, the VAT number field will be visible by default even if there's no company name entered yet.", 'wc-billingo-plus' ),
			),
			'vat_number_position' => array(
				'title' => __( 'VAT number field position', 'wc-billingo-plus' ),
				'type' => 'number',
				'class' => 'wc-billingo-plus-toggle-group-vatnumber-item',
				'default' => 35,
				'desc_tip' => __( 'The default priority is 35, which will place the field just after the company name field. Change this number if you want to place it somewhere else.', 'wc-billingo-plus' ),
			),
			'vat_number_nav' => array(
				'title' => __( 'VAT number validation with NAV', 'wc-billingo-plus' ).$pro_icon,
				'type' => 'checkbox',
				'class' => 'wc-billingo-plus-toggle-group-vatnumber-item wc-billingo-plus-toggle-group-vatnumber-nav',
				'default' => 'yes',
				'desc_tip' => __( 'If turned on and you entered your Online Invoice credentials and the customer enters a VAT number, it will validate it using the NAV API.', 'wc-billingo-plus' ),
				'description' => __( 'You can find more info about setting this up in the <a href="https://visztpeter.me/gyik/adoszam-mezo-2/">documentation</a>.', 'wc-billingo-plus' ),
				'disabled' => $this->check_nav_availability($pro_required)
			),
			'vat_number_nav_username' => array(
				'title' => __( 'Technical username', 'wc-billingo-plus' ),
				'type' => 'text',
				'class' => 'wc-billingo-plus-toggle-group-vatnumber-nav-item',
			),
			'vat_number_nav_password' => array(
				'title' => __( 'Technical user password', 'wc-billingo-plus' ),
				'type' => 'text',
				'class' => 'wc-billingo-plus-toggle-group-vatnumber-nav-item',
			),
			'vat_number_nav_signature' => array(
				'title' => __( 'XML Signature', 'wc-billingo-plus' ),
				'type' => 'text',
				'class' => 'wc-billingo-plus-toggle-group-vatnumber-nav-item',
			),
			'vat_number_nav_number' => array(
				'title' => __( 'The first 8 digits of your VAT number', 'wc-billingo-plus' ),
				'type' => 'text',
				'class' => 'wc-billingo-plus-toggle-group-vatnumber-nav-item',
			),
			'vat_number_autofill' => array(
				'title' => __( 'Autofill billing fields based on VAT number', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'class' => 'wc-billingo-plus-toggle-group-vatnumber-nav-item',
				'description' => __( 'If the customer enters a VAT number, it will validate it and also prefill the address and company name fields automatically.', 'wc-billingo-plus' ),
			),

			//Settings related to sharing the invoices
			'section_emails' => array(
				'title' => __( 'Invoice sharing', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_title',
				'description' => __( 'Settings related to sending the invoice to the customer.', 'wc-billingo-plus' ),
			),
			'auto_email' => array(
				'title' => __( 'Invoice notification', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __( 'If turned on, Billingo will email the customer about the invoice automatically.', 'wc-billingo-plus' ),
				'default' => 'yes'
			),
			'download_invoice' => array(
				'title' => __( 'Download documents', 'wc-billingo-plus' ).$pro_icon,
				'type' => 'checkbox',
				'description' => __( "If turned on, the PDF files will be downloaded into the wp-content/wc-billingo-plus folder, so it can be attached to WooCommerce emails and you can use the bulk download and print features. If not turned on, it will store only the link that redirects to Billingo's website.", 'wc-billingo-plus' ),
				'default' => 'no',
				'disabled' => $pro_required,
				'class'		 => 'wc-billingo-plus-toggle-group-download',
			),
			'email_attachment' => array(
				'title' => __( 'Insert invoices into e-mails', 'wc-billingo-plus' ).$pro_icon,
				'type' => 'checkbox',
				'disabled' => $pro_required,
				'class'		 => 'wc-billingo-plus-toggle-group-emails',
				'desc_tip' => __( 'This option places the invoice download links into the WooCommerce e-mails. You should disable the invoice notification option in this case. You can select which document type is attached to the WooCommerce e-mails.', 'wc-billingo-plus' ),
			),
			'email_attachment_file' => array(
				'title' => __( 'Attach invoices to e-mail', 'wc-billingo-plus' ).$pro_icon,
				'type' => 'checkbox',
				'disabled' => $pro_required,
				'class'		 => 'wc-billingo-plus-toggle-group-emails wc-billingo-plus-toggle-group-download-item',
				'desc_tip' => __( 'This option attaches the invoices to the WooCommerce e-mails. You should disable the invoice notification option in this case. You can select which document type is attached to the WooCommerce e-mails.', 'wc-billingo-plus' ),
			),
			'email_attachment_invoice' => array(
				'type' => 'multiselect',
				'title' => __( 'Invoice pairing', 'wc-billingo-plus' ),
				'class' => 'wc-enhanced-select wc-billingo-plus-toggle-group-emails-item',
				'default' => array('customer_completed_order', 'customer_invoice'),
				'options' => $this->get_email_types(),
				'description' => '<span id="wc_billingo_plus_load_email_ids_nonce" data-nonce="'.wp_create_nonce("wc_billingo_plus_load_email_ids").'">'
			),
			'email_attachment_proform' => array(
				'type' => 'multiselect',
				'title' => __( 'Proforma pairing', 'wc-billingo-plus' ),
				'class' => 'wc-enhanced-select wc-billingo-plus-toggle-group-emails-item',
				'default' => array('customer_processing_order', 'customer_on_hold_order'),
				'options' => $this->get_email_types(),
			),
			'email_attachment_deposit' => array(
				'type' => 'multiselect',
				'title' => __( 'Deposit invoice pairing', 'wc-billingo-plus' ),
				'class' => 'wc-enhanced-select wc-billingo-plus-toggle-group-emails-item',
				'default' => array('customer_processing_order', 'customer_on_hold_order'),
				'options' => array(),
			),
			'email_attachment_void' => array(
				'type' => 'multiselect',
				'title' => __( 'Reverse invoice pairing', 'wc-billingo-plus' ),
				'class' => 'wc-enhanced-select wc-billingo-plus-toggle-group-emails-item',
				'default' => array('customer_refunded_order', 'cancelled_order'),
				'options' => $this->get_email_types(),
			),
			'email_attachment_position' => array(
				'type' => 'select',
				'class' => 'wc-enhanced-select wc-billingo-plus-toggle-group-emails-item',
				'title' => __( 'E-mail link position', 'wc-billingo-plus' ),
				'desc_tip' => __( 'Where should the download links be included in the emails?', 'wc-billingo-plus' ),
				'default' => 'beginning',
				'options' => array(
					'beginning' => __( 'At the beginning', 'wc-billingo-plus' ),
					'end' => __( 'At the end', 'wc-billingo-plus' ),
				),
			),
			'customer_download' => array(
				'title' => __( 'Invoices in My Orders', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __( 'If turned on, the user can download the invoices once he/she is logged in on the website, under the My Orders page.', 'wc-billingo-plus' ),
				'default' => 'no'
			),
			'invoice_forward' => array(
				'title' => __( 'Invoice forwarding', 'wc-billingo-plus' ).$pro_icon,
				'type' => 'text',
				'disabled' => $pro_required,
				'description' => __('You can enter multiple email addresses separated with a comma and every document created will be forwarded to these addresses. You can use this to setup automation with Zapirt or emailitin.com for example.', 'wc-billingo-plus')
			),

			//Receipt
			'section_receipt' => array(
				'title' => __( 'E-Receipt', 'wc-billingo-plus' ).$pro_icon,
				'type' => 'wc_billingo_plus_settings_title',
				'description' => __( 'You can find settings related to the E-Receipt. With this option, you can collect just the name and email address of the buyer. Instead of an invoice, a simple receipt will be generated that the user receives via email. Ideal for digital products, tickets. Please consult with your accountant first to make sure this is a good solution for you. If the buyer wants a regular invoice, a checkbox is added for that on the checkout form.', 'wc-billingo-plus' ),
			),
			'receipt' => array(
				'disabled' => $pro_required,
				'title' => __( 'Receipt', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'class' => 'wc-billingo-plus-toggle-group-receipt',
			),
			'receipt_block' => array(
				'title' => __( 'Invoice block', 'wc-billingo-plus' ),
				'type'		 => 'select',
				'class' => 'wc-billingo-plus-toggle-group-receipt-item chosen_select test',
				'options' => $this->get_invoice_blocks(),
				'description'		 => $this->get_fetch_error_message('blocks').__("Create a separate receipt block on Billingo's website under Settings / Document blocks. If you don't see your document block in the dropdown, click on the refresh icon.", 'wc-billingo-plus' )
			),
			'receipt_hidden_fields' => array(
				'type' => 'multiselect',
				'title' => __( 'Hidden checkout fields', 'wc-billingo-plus' ),
				'class' => 'wc-enhanced-select wc-billingo-plus-toggle-group-receipt-item',
				'default' => array('billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode', 'billing_country', 'billing_state', 'billing_phone', 'billing_address_2', 'wc_billingo_plus_adoszam', 'order_comments'),
				'description' => 'These fields will be hidden on the checkout field if the customer only needs a receipt. The e-mail address field is required.',
				'options' => $this->get_receipt_billing_fields()
			),

			//Other settings
			'section_other' => array(
				'title' => __( 'Other settings', 'wc-billingo-plus' ),
				'type' => 'wc_billingo_plus_settings_title'
			),
			'nev_csere' => array(
				'title' => __( 'Switch first name / last name', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __( 'If the order of the name is not correct on the invoice, you can use this option to switch the first/last name.', 'wc-billingo-plus' ),
			),
			'error_email' => array(
				'title' => __( 'E-mail address for error notifications', 'wc-billingo-plus' ),
				'type' => 'text',
				'default' => get_option('admin_email'),
				'desc_tip' => __( "If you enter an email address, you will receive a notification if there was an error generating an invoice. Leave it empty if you don't need this.", 'wc-billingo-plus' ),
			),
			'defer' => array(
				'title' => __( 'Delayed invoice generation', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __( 'If turned on, the invoice will be generated in the background process, so the customer can reach the thank you page faster during checkout. Keep in mind that in this case, the invoice is not ready yet when the WooCommerce e-mail is sent, so make sure you have the email notification option turned on.', 'wc-billingo-plus' ),
			),
			'debug' => array(
				'title' => __( 'Developer mode', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __( 'If turned on, the generated XML file will be logged in WooCommerce / Status / Logs. Can be used to debug issues.', 'wc-billingo-plus' ),
			),
			'tools' => array(
				'title' => __( 'Download icons in the tools column', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __( 'This will display the download icons in the Tools column on the orders management table.', 'wc-billingo-plus' ),
			),
			'uninstall' => array(
				'title' => __( 'Delete settings', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'desc_tip' => __( 'If turned on, during plugin uninstall it will also delete all settings from the database.', 'wc-billingo-plus' ),
			),
			'bulk_download_zip' => array(
				'title' => __( 'Create a ZIP file during bulk download', 'wc-billingo-plus' ),
				'type' => 'checkbox',
				'disabled' => (!class_exists('ZipArchive')),
				'desc_tip' => __( 'If you want to download multiple invoices at once, this option will create a ZIP file with separate PDF files(the default option will merge all invoices into a single PDF).', 'wc-billingo-plus' ),
				'description' => $this->get_bulk_zip_error()
			),
			'grouped_invoice_status' => array(
				'type' => 'select',
				'title' => __( 'Combined invoice order status', 'wc-billingo-plus' ).$pro_icon,
				'class' => 'wc-enhanced-select',
				'default' => 'no',
				'disabled' => $pro_required,
				'options' => $this->get_order_statuses_for_void(),
				'desc_tip' => __( 'If you create a combined invoice, the related order statuses will change to this.', 'wc-billingo-plus' ),
			),
			'custom_order_statues' => array(
				'title' => __( 'Custom order statuses', 'wc-billingo-plus' ),
				'type' => 'text',
				'desc_tip' => __( "If you are using a custom order status extension and the automation you setup for that status won't trigger, try to add the slug of your custom status. You can add multiple, separated with a comma.", 'wc-billingo-plus' ),
			)
		);

		$this->form_fields = apply_filters('wc_billingo_plus_settings_fields', array_merge($settings_top, $settings_rest));
	}

	//Get order statues
	public function get_order_statuses() {
		if(function_exists('wc_order_status_manager_get_order_status_posts')) {
			$filtered_statuses = array();
			$custom_statuses = wc_order_status_manager_get_order_status_posts();
			foreach ($custom_statuses as $status ) {
				$filtered_statuses[ 'wc-' . $status->post_name ] = $status->post_title;
			}
			return $filtered_statuses;
		} else {
			return wc_get_order_statuses();
		}
	}

	//Order statuses
	public function get_order_statuses_for_void() {
		$built_in_statuses = array("no"=>__("Turned off")) + $this->get_order_statuses();
		return $built_in_statuses;
	}

	//Order statuses
	public function get_order_statuses_for_sync() {
		$built_in_statuses = array("no"=>__("Do not change status")) + $this->get_order_statuses();
		return $built_in_statuses;
	}

	//Get invoice blocks
	public static function get_invoice_blocks() {
		global $wc_billingo_plus;
		$blocks = array();
		if(is_admin() && isset( $_GET['tab']) && $_GET['tab'] == 'integration') {
			$blocks = $wc_billingo_plus->get_billingo_invoice_blocks();
		}
		return $blocks;
	}

	//Get VAT IDs
	public static function get_vat_options() {
		global $wc_billingo_plus;
		$vat_ids = array();
		if(is_admin() && isset( $_GET['tab']) && $_GET['tab'] == 'integration') {
			$vat_ids = WC_Billingo_Plus_Helpers::get_billingo_vat_ids(true);
		}
		return array("" => "Alapértelmezett") + $vat_ids;
	}

	//Get VAT IDs
	public static function get_bank_accounts() {
		global $wc_billingo_plus;
		$bank_accounts = array();
		if(is_admin() && isset( $_GET['tab']) && $_GET['tab'] == 'integration') {
			$bank_accounts = $wc_billingo_plus->get_billingo_bank_accounts();
		}
		return $bank_accounts;
	}

	//Get payment methods
	public static function get_payment_methods() {
		return WC_Billingo_Plus_Helpers::get_payment_methods();
	}

	//Get shipping methods
	public static function get_shipping_methods() {
		return WC_Billingo_Plus_Helpers::get_shipping_methods();
	}

	//Get email ids
	public static function get_email_types() {
		return array();
	}

	public function get_email_ids_with_ajax() {
		check_ajax_referer( 'wc_billingo_plus_load_email_ids', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; huh?' ) );
		}
		$email_invoice_selected = $this->get_option('email_attachment_invoice');
		$document_types = array('invoice', 'proform', 'deposit', 'void');

		//Get registered emails
		$mailer = WC()->mailer();
		$email_templates = $mailer->get_emails();
		$emails = array();

		//Omit a few one thats not required at all
		$disabled = ['failed_order', 'customer_note', 'customer_reset_password', 'customer_new_account'];

		//Loop through each document type
		foreach ($document_types as $document_type) {

			//Get saved values
			$saved_values = $this->get_option('email_attachment_'.$document_type);
			if(!$saved_values) $saved_values = array();

			//Create options
			$options = array();
			foreach ( $email_templates as $email ) {
				if(!in_array($email->id,$disabled)) {
					$options[] = array(
						'label' => $email->get_title(),
						'selected' => in_array($email->id, $saved_values),
						'id' => $email->id
					);
				}
			}

			//Return select + options
			$emails[] = array(
				'field' => $document_type,
				'options' => $options
			);
		}

		wp_send_json_success($emails);
	}

	//Get currency types
	public static function get_currency_codes() {
		$currency_code_options = get_woocommerce_currencies();
		foreach ( $currency_code_options as $code => $name ) {
			$currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
		}
		return $currency_code_options;
	}

	//Get error message for billingo api
	public function get_fetch_error_message($call = 'vat') {
		$message = '';
		if($call == 'bank_accounts' && count($this->get_bank_accounts()) == 0) {
			$message = '<span class="wc-billingo-plus-settings-error hidden"><span class="dashicons dashicons-warning"></span> '.__("Failed to retrieve available bank accounts. Check the api key or see if you've already created a bank account!", 'wc-billingo-plus').'</span>';
		}
		if($call == 'blocks' && count($this->get_invoice_blocks()) == 0) {
			$message = '<span class="wc-billingo-plus-settings-error hidden"><span class="dashicons dashicons-warning"></span> '.__('Failed to retrieve available invoice blocks. Check the api key!', 'wc-billingo-plus').'</span>';
		}
		return $message;
	}

	public function get_bulk_zip_error() {
		$message = '';
		if(	!class_exists('ZipArchive')) {
			$message = '<span class="wc-billingo-plus-settings-error"><span class="dashicons dashicons-warning"></span> '.__('This feature requires the ZipArchive function and by the looks of it, this is not enabled on your website. You can ask your hosting provider for help.', 'wc-billingo-plus').'</span>';
		}
		return $message;
	}

	//Refresh blocks with ajax
	public function reload_blocks() {
		check_ajax_referer( 'wc_billingo_plus_load_email_ids', 'nonce' );
		if ( !current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wc-billingo-plus' ) );
		}

		global $wc_billingo_plus;
		$blocks = $wc_billingo_plus->get_billingo_invoice_blocks(true);

		$blocks_array = array();
		foreach ($blocks as $block_id => $block_name) {
			$blocks_array[] = array('id' => $block_id, 'name' => $block_name);
		}

		wp_send_json_success($blocks_array);
	}

	//Refresh blocks with ajax
	public function reload_bank_accounts() {
		check_ajax_referer( 'wc_billingo_plus_load_email_ids', 'nonce' );
		if ( !current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wc-billingo-plus' ) );
		}

		global $wc_billingo_plus;
		$bank_accounts = $wc_billingo_plus->get_billingo_bank_accounts(true);

		$bank_accounts_array = array();
		foreach ($bank_accounts as $bank_account_id => $bank_account_name) {
			$bank_accounts_array[] = array('id' => $bank_account_id, 'name' => $bank_account_name);
		}

		wp_send_json_success($bank_accounts_array);
	}


	public function save_payment_options() {

		//Save payment options
		$accounts = array();
		if ( isset( $_POST['wc_billingo_plus_payment_method_options'] ) ) {
			foreach ($_POST['wc_billingo_plus_payment_method_options'] as $payment_method_id => $payment_method) {
				$deadline = wc_clean($payment_method['deadline']);
				$complete = isset($payment_method['complete']) ? true : false;
				$proform = isset($payment_method['proform']) ? true : false;
				$deposit = isset($payment_method['deposit']) ? true : false;
				$auto_disabled = isset($payment_method['auto_disabled']) ? true : false;

				if(isset($payment_method['billingo_id'])) {
					$billingo_id = wc_clean($payment_method['billingo_id']);
				} else {
					$billingo_id = 0;
				}

				$accounts[$payment_method_id] = array(
					'billingo_id' => $billingo_id,
					'deadline' => $deadline,
					'complete' => $complete,
					'proform' => $proform,
					'deposit' => $deposit,
					'auto_disabled' => $auto_disabled
				);
			}
		}
		update_option( 'wc_billingo_plus_payment_method_options', $accounts );

		//Save rounding options
		$roundings = array();
		if ( isset( $_POST['wc_billingo_plus_rounding_options'] ) ) {
			foreach ($_POST['wc_billingo_plus_rounding_options'] as $currency => $rounding) {
				$roundings[$rounding['currency']] = wc_clean($rounding['rounding']);
			}
		}
		update_option( 'wc_billingo_plus_rounding_options', $roundings );

		//Save multi account options
		$extra_accounts = array();
		if ( isset( $_POST['wc_billingo_plus_additional_accounts'] ) ) {
			foreach ($_POST['wc_billingo_plus_additional_accounts'] as $account_id => $account) {
				$name = wc_clean($account['name']);
				$api_key = wc_clean($account['api_key']);
				$bank_account_id = wc_clean($account['bank_account_id']);
				$block_id = wc_clean($account['block_id']);
				$condition = wc_clean($account['condition']);

				$extra_accounts[$account_id] = array(
					'name' => $name,
					'api_key' => $api_key,
					'bank_account_id' => $bank_account_id,
					'block_id' => $block_id,
					'condition' => $condition
				);
			}
		}
		update_option( 'wc_billingo_plus_additional_accounts', $extra_accounts );

		//Save notes
		$notes = array();
		if ( isset( $_POST['wc_billingo_plus_notes'] ) ) {
			foreach ($_POST['wc_billingo_plus_notes'] as $note_id => $note) {

				$comment = sanitize_textarea_field($note['note']);
				$notes[$note_id] = array(
					'comment' => $comment,
					'conditional' => false
				);

				//If theres conditions to setup
				$condition_enabled = isset($note['condition_enabled']) ? true : false;
				$append_enabled = isset($note['append']) ? true : false;
				$conditions = (isset($note['conditions']) && count($note['conditions']) > 0);

				if($condition_enabled && $conditions) {
					$notes[$note_id]['conditional'] = true;
					$notes[$note_id]['conditions'] = array();
					$notes[$note_id]['logic'] = wc_clean($note['logic']);
					$notes[$note_id]['append'] = $append_enabled;

					foreach ($note['conditions'] as $condition) {
						if(isset($condition['category'])) {
							$condition_details = array(
								'category' => wc_clean($condition['category']),
								'comparison' => wc_clean($condition['comparison']),
								'value' => $condition[$condition['category']]
							);

							$notes[$note_id]['conditions'][] = $condition_details;
						}
					}
				}
			}
		}
		update_option( 'wc_billingo_plus_notes', $notes );

		//Save checkbox groups
		$checkbox_groups = array('auto_invoice_status', 'auto_void_status');
		foreach ($checkbox_groups as $checkbox_group) {
			$checkbox_values = array();
			if ( isset( $_POST['wc_billingo_plus_'.$checkbox_group] ) ) {
				foreach ($_POST['wc_billingo_plus_'.$checkbox_group] as $checkbox_value) {
					$checkbox_values[] = wc_clean($checkbox_value);
				}
			}
			update_option('wc_billingo_plus_'.$checkbox_group, $checkbox_values);
		}

		//Save automations
		$automations = array();
		if ( isset( $_POST['wc_billingo_plus_automations'] ) ) {
			foreach ($_POST['wc_billingo_plus_automations'] as $automation_id => $automation) {

				$document = sanitize_text_field($automation['document']);
				$trigger = sanitize_text_field($automation['trigger']);
				$complete = sanitize_text_field($automation['complete']);
				$complete_delay = sanitize_text_field($automation['complete_delay']);
				$deadline = sanitize_text_field($automation['deadline']);
				$paid = isset($automation['paid']) ? true : false;
				$id = sanitize_text_field($automation['id']);
				$automations[$automation_id] = array(
					'document' => $document,
					'trigger' => $trigger,
					'complete' => $complete,
					'complete_delay' => $complete_delay,
					'deadline' => $deadline,
					'paid' => $paid,
					'id' => $id,
					'conditional' => false
				);

				//If theres conditions to setup
				$condition_enabled = isset($automation['condition_enabled']) ? true : false;
				$conditions = (isset($automation['conditions']) && count($automation['conditions']) > 0);

				if($condition_enabled && $conditions) {
					$automations[$automation_id]['conditional'] = true;
					$automations[$automation_id]['conditions'] = array();
					$automations[$automation_id]['logic'] = wc_clean($automation['logic']);

					foreach ($automation['conditions'] as $condition) {
						$condition_details = array(
							'category' => wc_clean($condition['category']),
							'comparison' => wc_clean($condition['comparison']),
							'value' => $condition[$condition['category']]
						);

						$automations[$automation_id]['conditions'][] = $condition_details;
					}
				}
			}
		}
		update_option( 'wc_billingo_plus_automations', $automations );

		//Save vat overrides
		$vat_overrides = array();
		if ( isset( $_POST['wc_billingo_plus_vat_overrides'] ) ) {
			foreach ($_POST['wc_billingo_plus_vat_overrides'] as $vat_override_id => $vat_override) {
				$line_item = sanitize_text_field($vat_override['line_item']);
				$vat_type = sanitize_text_field($vat_override['vat_type']);
				$entitlement = sanitize_text_field($vat_override['entitlement']);
				$vat_overrides[$vat_override_id] = array(
					'line_item' => $line_item,
					'vat_type' => $vat_type,
					'conditional' => false,
					'entitlement' => $entitlement
				);

				//If theres conditions to setup
				$condition_enabled = isset($vat_override['condition_enabled']) ? true : false;
				$conditions = (isset($vat_override['conditions']) && count($vat_override['conditions']) > 0);
				if($conditions && $condition_enabled) {
					$vat_overrides[$vat_override_id]['conditional'] = true;
					$vat_overrides[$vat_override_id]['conditions'] = array();
					$vat_overrides[$vat_override_id]['logic'] = wc_clean($vat_override['logic']);

					foreach ($vat_override['conditions'] as $condition) {
						$condition_details = array(
							'category' => wc_clean($condition['category']),
							'comparison' => wc_clean($condition['comparison']),
							'value' => $condition[$condition['category']]
						);

						$vat_overrides[$vat_override_id]['conditions'][] = $condition_details;
					}
				}
			}
		}
		update_option( 'wc_billingo_plus_vat_overrides', $vat_overrides );

		//Save advanced options
		$advanced_options = array();
		if ( isset( $_POST['wc_billingo_plus_advanced_options'] ) ) {
			foreach ($_POST['wc_billingo_plus_advanced_options'] as $advanced_option_id => $advanced_option) {
				$property = sanitize_text_field($advanced_option['property']);
				$value = sanitize_text_field($advanced_option['value']);
				$advanced_options[$advanced_option_id] = array(
					'property' => $property,
					'value' => $value,
					'conditional' => false
				);

				//If theres conditions to setup
				$condition_enabled = isset($advanced_option['condition_enabled']) ? true : false;
				$conditions = (isset($advanced_option['conditions']) && count($advanced_option['conditions']) > 0);
				if($conditions && $condition_enabled) {
					$advanced_options[$advanced_option_id]['conditional'] = true;
					$advanced_options[$advanced_option_id]['conditions'] = array();
					$advanced_options[$advanced_option_id]['logic'] = wc_clean($advanced_option['logic']);

					foreach ($advanced_option['conditions'] as $condition) {
						$condition_details = array(
							'category' => wc_clean($condition['category']),
							'comparison' => wc_clean($condition['comparison']),
							'value' => $condition[$condition['category']]
						);

						$advanced_options[$advanced_option_id]['conditions'][] = $condition_details;
					}
				}
			}
		}
		update_option( 'wc_billingo_plus_advanced_options', $advanced_options );

		//Reset sync options, so it will be correctly rescheduled if needed
		WC()->queue()->cancel_all( 'wc_billingo_plus_ipn_check' );

	}

	//Save an option with ajax, so the rate request widget can be hidden
	public function hide_rate_request() {
		check_ajax_referer( 'wc-billingo-plus-hide-rate-request', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; huh?' ) );
		}
		update_option('_wc_billingo_plus_hide_rate_request', true);
		wp_send_json_success();
	}

	//Slightly modified html for title section
	public function generate_wc_billingo_plus_settings_title_html( $key, $data ) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for the additional accounts field
	public function generate_wc_billingo_plus_settings_accounts_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for the notes field
	public function generate_wc_billingo_plus_settings_notes_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for the payment method field
	public function generate_wc_billingo_plus_settings_payment_methods_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for the rounding field
	public function generate_wc_billingo_plus_settings_rounding_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for the status field
	public function generate_wc_billingo_plus_settings_auto_status_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for the status field
	public function generate_wc_billingo_plus_settings_auto_status_sync_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for order status selector field
	public function generate_wc_billingo_plus_settings_automations_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for order status selector field
	public function generate_wc_billingo_plus_settings_vat_overrides_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	//Generate html for order status selector field
	public function generate_wc_billingo_plus_settings_advanced_html( $key, $data) {
		return $this->render_custom_setting_html($key, $data);
	}

	public function render_custom_setting_html($key, $data) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);
		$data = wp_parse_args( $data, $defaults );
		$template_name = str_replace('wc_billingo_plus_settings_', '', $data['type']);
		ob_start();
		include( dirname( __FILE__ ) . '/views/html-admin-'.str_replace('_', '-', $template_name).'.php' );
		return ob_get_clean();
	}

	public function check_nav_availability($pro_required) {
		if($pro_required) {
			return true;
		}

		$required_php = '7.1';
		if(! function_exists( 'phpversion' ) || version_compare( phpversion(), $required_php, '<' )) {
			return true;
		}
	}

	//Get checkout form fields as an array
	public function get_receipt_billing_fields() {
		if ( ! class_exists( 'WC_Session' ) ) {
    	include_once( WP_PLUGIN_DIR . '/woocommerce/includes/abstracts/abstract-wc-session.php' );
		}

		WC()->session = new WC_Session_Handler;
		WC()->customer = new WC_Customer;
		$exclude = array('wc_billingo_plus_receipt', 'billing_email');
		$fields = array();
		foreach (WC()->checkout->checkout_fields['billing'] as $field_id => $field) {
			if(!in_array($field_id, $exclude) && isset($field['label'])) {
				$fields[$field_id] = wp_strip_all_tags($field['label']);
			}
		}
		return $fields;
	}

}

endif;
