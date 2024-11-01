<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Billingo_Plus_Compatibility {
	protected static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning this object is forbidden.', 'wc-billingo-plus' ), '3.0.0' );
	}

	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'wc-billingo-plus' ), '3.0.0' );
	}

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_modules' ), 100 );
	}

	public function load_modules() {
		$module_paths = array();

		//WooCommerce Advanced Quantity support
		if ( class_exists( 'Morningtrain\WooAdvancedQTY\PluginInit' ) ) {
			$module_paths['advanced_quantity'] = 'modules/class-wc-billingo-plus-advanced-quantity.php';
		}

		//Hucommerce support
		if ( defined( 'SURBMA_HC_PLUGIN_VERSION_NUMBER' ) || defined( 'SURBMA_HC_PLUGIN_VERSION' ) ) {
			$module_paths['hucommerce'] = 'modules/class-wc-billingo-plus-hucommerce.php';
		}

		//WooCommerce EU Vat Assistant support
		if ( isset($GLOBALS['wc-aelia-eu-vat-assistant']) ) {
			$module_paths['eu_vat_assistant'] = 'modules/class-wc-billingo-plus-eu-vat-assistant.php';
		}

		//WooCommerce EU Vat Number support
		if ( defined( 'WC_EU_VAT_VERSION' ) ) {
			$module_paths['wc_eu_vat_number'] = 'modules/class-wc-billingo-plus-eu-vat-number.php';
		}

		//WooCommerce Product Bundles compatibility
		if ( isset($GLOBALS['woocommerce_bundles']) ) {
			$module_paths['woocommerce_bundles'] = 'modules/class-wc-billingo-plus-product-bundles.php';
		}

		//WooCommerce Product Bundles compatibility
		if ( class_exists( 'WC_Subscriptions' ) ) {
			$module_paths['woocommerce_subscriptions'] = 'modules/class-wc-billingo-plus-subscriptions.php';
		}

		//CheckoutWC compatibility
		if ( defined( 'CFW_NAME' ) ) {
			$module_paths['checkoutwc'] = 'modules/class-wc-billingo-plus-checkoutwc.php';
		}

		//Translatepress compatibility
		if ( isset($GLOBALS['TRP_LANGUAGE']) ) {
			$module_paths['translatepress'] = 'modules/class-wc-billingo-plus-translatepress.php';
		}

		//WPML compatibility
		if ( defined('WCML_VERSION')) {
			$module_paths['wcml'] = 'modules/class-wc-billingo-plus-wcml.php';
		}

		if ( defined( 'WOOCCM_PLUGIN_NAME' ) ) {
			$module_paths['checkout_manager'] = 'modules/class-wc-billingo-plus-checkout-manager.php';
		}

		if ( defined( 'WCU_LANG_CODE' ) && WCU_LANG_CODE == 'woo-currency' ) {
			$module_paths['woo_currency'] = 'modules/class-wc-billingo-plus-woo-currency.php';
		}

		if ( isset($GLOBALS['WCFMmp']) ) {
			$module_paths['wcfm'] = 'modules/class-wc-billingo-plus-wcfm.php';
		}

		if ( class_exists( 'WC_Booking_Data_Store' ) ) {
			$module_paths['woocommerce_bookings'] = 'modules/class-wc-billingo-plus-bookings.php';
		}

		if ( defined( 'VP_WOO_PONT_PLUGIN_FILE' ) ) {
			$module_paths['vp_woo_pont'] = 'modules/class-wc-billingo-plus-vp-woo-pont.php';
		}

		$module_paths = apply_filters( 'wc_billingo_plus_compatibility_modules', $module_paths );
		foreach ( $module_paths as $name => $path ) {
			require_once $path;
		}

	}

}
