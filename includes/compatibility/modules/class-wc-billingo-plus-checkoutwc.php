<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//CheckoutWC Compatibility
class WC_Billingo_Plus_CheckoutWC_Compatibility {

	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'add_vat_number_compat_script' ) );
	}

	public static function add_vat_number_compat_script() {
		if(is_checkout() && WC_Billingo_Plus()->get_option('vat_number_form', 'no') == 'yes') {
			?>
			<script>
			jQuery(document).ready(function($){

				//If the vat number is required for companies
				if($('.woocommerce-checkout').length) {
					var wc_billingo_plus_show_hide_vat_field_placeholder = function() {

						var $field = $('#billingo_plus_adoszam_field');
						var $input = $field.find('input');
						var $company = $('input#billing_company').val();
						var country = $('select#billing_country').val();
						if(!country) country = 'HU';

						if(country == 'HU') {
							$field.find('label abbr').remove();
							var optional = $field.find('label span.optional').text()
							if($company) {
								$input.attr('placeholder', '12345678-1-12');
							} else {
								$input.attr('placeholder', '12345678-1-12 '+optional);
							}
						}
					}

					//On country and company name change
					$('body').on('blur change keyup', 'select#billing_country, input#billing_company', function(){
						wc_billingo_plus_show_hide_vat_field_placeholder();
					});

					wc_billingo_plus_show_hide_vat_field_placeholder();
				}

			});
			</script>
			<?php
		}
	}

}

WC_Billingo_Plus_CheckoutWC_Compatibility::init();