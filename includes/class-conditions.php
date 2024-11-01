<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Conditions', false ) ) :

	class WC_Billingo_Plus_Conditions {

		//Get possible conditional values
		public static function get_conditions($group = 'notes') {

			//Get country list
			$countries_obj = new WC_Countries();
			$countries = $countries_obj->__get('countries');

			//Setup conditions
			$conditions = array(
				'payment_method' => array(
					"label" => __('Payment method', 'wc-billingo-plus'),
					'options' => WC_Billingo_Plus_Helpers::get_payment_methods()
				),
				'shipping_method' => array(
					"label" => __('Shipping method', 'wc-billingo-plus'),
					'options' => WC_Billingo_Plus_Helpers::get_shipping_methods()
				),
				'type' => array(
					"label" => __('Order type', 'wc-billingo-plus'),
					'options' => array(
						'individual' => __('Individual', 'wc-billingo-plus'),
						'company' => __('Company', 'wc-billingo-plus'),
					)
				),
				'product_category' => array(
					'label' => __('Product category', 'wc-billingo-plus'),
					'options' => array()
				),
				'language' => array(
					'label' => __('Invoice language', 'wc-billingo-plus'),
					'options' => WC_Billingo_Plus_Helpers::get_supported_languages()
				),
				'document' => array(
					'label' => __('Document type', 'wc-billingo-plus'),
					'options' => array(
						'invoice' => __('Invoice', 'wc-billingo-plus'),
						'proform' => __('Proforma invoice', 'wc-billingo-plus'),
						'deposit' => __('Deposit invoice', 'wc-billingo-plus'),
					)
				),
				'account' => array(
					'label' => __('Billingo account', 'wc-billingo-plus'),
					'options' => array()
				),
				'billing_address' => array(
					"label" => __('Billing address', 'wc-billingo-plus'),
					'options' => array(
						'eu' => __('Inside the EU', 'wc-billingo-plus'),
						'world' => __('Outside of the EU', 'wc-billingo-plus'),
					)
				),
				'billing_country' => array(
					"label" => __('Billing country', 'wc-billingo-plus'),
					'options' => $countries
				),
				'currency' => array(
					"label" => __('Order currency', 'wc-billingo-plus'),
					'options' => array()
				),
			);

			//Add category options
			foreach (get_terms(array('taxonomy' => 'product_cat')) as $category) {
				$conditions['product_category']['options'][$category->term_id] = $category->name;
			}

			//Add account options
			foreach (WC_Billingo_Plus()->get_billingo_accounts() as $account_key => $account_name) {
				$conditions['account']['options'][$account_key] = $account_name.' - '.substr(esc_html($account_key), 0, 10).'...';
			}

			//Add VAT rates as a condition
			if($group == 'advanced_options') {
				$vat_ids = WC_Billingo_Plus_Helpers::get_billingo_vat_ids();
				$conditions['vat_id'] = array(
					"label" => __('VAT ID', 'wc-billingo-plus'),
					'options' => $vat_ids
				);
			}

			//Add currency options
			$currency_code_options = get_woocommerce_currencies();
			foreach ( $currency_code_options as $code => $name ) {
				$conditions['currency']['options'][ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
			}

			//Apply filters
			$conditions = apply_filters('wc_billingo_plus_'.$group.'_conditions', $conditions);

			return $conditions;
		}

		public static function get_sample_row($group = 'notes') {
			$conditions = self::get_conditions($group);
			ob_start();
			?>
			<script type="text/html" id="wc_billingo_plus_<?php echo $group; ?>_condition_sample_row">
				<li>
					<select class="condition" data-name="wc_billingo_plus_<?php echo $group; ?>[X][conditions][Y][category]">
						<?php foreach ($conditions as $condition_id => $condition): ?>
							<option value="<?php echo esc_attr($condition_id); ?>"><?php echo esc_html($condition['label']); ?></option>
						<?php endforeach; ?>
					</select>
					<select class="comparison" data-name="wc_billingo_plus_<?php echo $group; ?>[X][conditions][Y][comparison]">
						<option value="equal"><?php _e('Equal', 'wc-billingo-plus'); ?></option>
						<option value="not_equal"><?php _e('Not equal', 'wc-billingo-plus'); ?></option>
					</select>
					<?php foreach ($conditions as $condition_id => $condition): ?>
						<select class="value <?php if($condition_id == 'payment_method'): ?>selected<?php endif; ?>" data-condition="<?php echo esc_attr($condition_id); ?>" data-name="wc_billingo_plus_<?php echo $group; ?>[X][conditions][Y][<?php echo esc_attr($condition_id); ?>]" <?php if($condition_id != 'payment_method'): ?>disabled="disabled"<?php endif; ?>>
							<?php foreach ($condition['options'] as $option_id => $option_name): ?>
								<option value="<?php echo esc_attr($option_id); ?>"><?php echo esc_html($option_name); ?></option>
							<?php endforeach; ?>
						</select>
					<?php endforeach; ?>
					<a href="#" class="add-row"><span class="dashicons dashicons-plus-alt"></span></a>
					<a href="#" class="delete-row"><span class="dashicons dashicons-dismiss"></span></a>
				</li>
			</script>
			<?php
			return ob_get_clean();
		}

		public static function get_order_details($order, $group) {

			//Get order type
			$order_type = ($order->get_billing_company()) ? 'company' : 'individual';

			//Get billing address location
			$eu_countries = WC()->countries->get_european_union_countries('eu_vat');
			$billing_address = 'world';
			if(in_array($order->get_billing_country(), $eu_countries)) {
				$billing_address = 'eu';
			}

			//Get payment method id
			$payment_method = $order->get_payment_method();

			//Get shipping method id
			$shipping_method = '';
			$shipping_methods = $order->get_shipping_methods();
			if($shipping_methods) {
				foreach( $shipping_methods as $shipping_method_obj ){
					$shipping_method = $shipping_method_obj->get_method_id().':'.$shipping_method_obj->get_instance_id();
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

			//Account
			$api_key = WC_Billingo_Plus()->get_billingo_api($order, true);
			$account = $api_key;

			//Setup parameters for conditional check
			$order_details = array(
				'payment_method' => $payment_method,
				'shipping_method' => $shipping_method,
				'type' => $order_type,
				'billing_address' => $billing_address,
				'billing_country' => $order->get_billing_country(),
				'product_categories' => $product_categories,
				'account' => $account
			);

			//Custom conditions
			return apply_filters('wc_billingo_plus_'.$group.'_conditions_values', $order_details, $order);

		}

		public static function match_conditions($items, $item_id, $order_details) {
			$item = $items[$item_id];

			//Check if the conditions match
			foreach ($item['conditions'] as $condition_id => $condition) {
				$comparison = ($condition['comparison'] == 'equal');

				switch ($condition['category']) {
					case 'product_category':
						if(in_array($condition['value'], $order_details['product_categories'])) {
							$items[$item_id]['conditions'][$condition_id]['match'] = $comparison;
						} else {
							$items[$item_id]['conditions'][$condition_id]['match'] = !$comparison;
						}
						break;
					default:
						if($condition['value'] == $order_details[$condition['category']]) {
							$items[$item_id]['conditions'][$condition_id]['match'] = $comparison;
						} else {
							$items[$item_id]['conditions'][$condition_id]['match'] = !$comparison;
						}
						break;
				}
			}

			//Count how many matches we have
			$matched = 0;
			foreach ($items[$item_id]['conditions'] as $condition) {
				if($condition['match']) $matched++;
			}

			//Check if we need to match all or just one
			$condition_is_a_match = false;
			if($item['logic'] == 'and' && $matched == count($item['conditions'])) $condition_is_a_match = true;
			if($item['logic'] == 'or' && $matched > 0) $condition_is_a_match = true;

			return $condition_is_a_match;
		}

		public static function check_advanced_options($invoiceData, $order) {
			$order_details = self::get_order_details($order, 'advanced_options');

			//Check for options
			$advanced_options = get_option('wc_billingo_plus_advanced_options', array());

			//Skip if theres none
			if(empty($advanced_options)) {
				return $invoiceData;
			}

			//Check one by one
			foreach ($advanced_options as $option_id => $option) {

				//Check for entitlements
				if($option['property'] == 'entitlement') {
					$invoiceData = self::check_item_entitlements($invoiceData, $option, $order_details);
				} else {
					//Compare conditions with order details and see if we have a match
					$is_a_match = self::match_conditions($advanced_options, $option_id, $order_details);
					$value = str_replace($option['property'].'_', '', $option['value']);

					//If its not a match, continue to next one
					if(!$is_a_match) continue;

					//It is a match, so try to change parameters
					if($option['property'] == 'bank_account') {
						$invoiceData['bank_account_id'] = (int)$value;
					}

					if($option['property'] == 'invoice_block') {
						$invoiceData['block_id'] = (int)$value;
					}

					if($option['property'] == 'language') {
						$invoiceData['language'] = $value;
					}
				}

			}

			return $invoiceData;
		}

		public static function check_item_entitlements($invoiceData, $option, $order_details) {
			$value = str_replace($option['property'].'_', '', $option['value']);

			foreach ($invoiceData['items'] as $item_id => $invoice_item) {
				$vat_id = str_replace('%', '', $invoice_item['vat']);
				$order_details['vat_id'] = $vat_id;
				$is_a_match = self::match_conditions(array($option), 0, $order_details);

				if($is_a_match) {
					$invoiceData['items'][$item_id]['entitlement'] = $value;
				}
			}

			return $invoiceData;
		}

	}

endif;
