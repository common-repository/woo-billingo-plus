<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_values = get_option('wc_billingo_plus_additional_accounts');

$product_categories = array();
foreach (get_terms(array('taxonomy' => 'product_cat')) as $category) {
	$product_categories['product_cat_'.$category->term_id] = $category->name;
}

$condition_select_values = apply_filters('wc_billingo_plus_account_conditions', array(
	array(
		"label" => __('Payment method', 'wc-billingo-plus'),
		"options" => $this->get_payment_methods()
	),
	array(
		"label" => __('Shipping method', 'wc-billingo-plus'),
		"options" => $this->get_shipping_methods()
	),
	array(
		"label" => __('Currency', 'wc-billingo-plus'),
		'options' => array(
			'HUF' => __( 'Forint', 'wc-billingo-plus' ),
			'EUR' => __( 'Euro', 'wc-billingo-plus' ),
			'USD' => __( 'US dollars', 'wc-billingo-plus' ),
		)
	),
	array(
		"label" => __('Order type', 'wc-billingo-plus'),
		"options" => array(
			'order-individual' => __('Individual', 'wc-billingo-plus'),
			'order-company' => __('Company', 'wc-billingo-plus')
		)
	),
	array(
		"label" => __('Product category', 'wc-billingo-plus'),
		"options" => $product_categories
  )
));
?>

<tr valign="top">
	<th scope="row" class="titledesc"></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="wc-billingo-plus-settings–inline-table-scroll border">
			<table class="wc-billingo-plus-settings–inline-table wc-billingo-plus-settings–inline-table-accounts">
				<thead>
					<tr>
						<th><?php _e('Account name', 'wc-billingo-plus'); ?></th>
						<th><?php _e('API V3 key', 'wc-billingo-plus'); ?></th>
						<th><?php _e('Bank account ID', 'wc-billingo-plus'); ?></th>
						<th><?php _e('Document block ID', 'wc-billingo-plus'); ?></th>
						<th><?php _e('Condition', 'wc-billingo-plus'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if($saved_values): ?>
						<?php foreach ( $saved_values as $account_id => $account ): ?>
							<?php
							if(!isset($account['api_key'])) $account['api_key'] = '';
							if(!isset($account['bank_account_id'])) $account['bank_account_id'] = '';
							?>
							<tr>
								<td>
									<input type="text" placeholder="<?php _e('Can be anything', 'wc-billingo-plus'); ?>" data-name="wc_billingo_plus_additional_accounts[X][name]" name="wc_billingo_plus_additional_accounts[<?php echo esc_attr( $account_id ); ?>][name]" value="<?php echo esc_attr($account['name']); ?>" />
								</td>
								<td>
									<input type="text" placeholder="<?php _e('API V3 key', 'wc-billingo-plus'); ?>" data-name="wc_billingo_plus_additional_accounts[X][api_key]" name="wc_billingo_plus_additional_accounts[<?php echo esc_attr( $account_id ); ?>][api_key]" value="<?php echo esc_attr($account['api_key']); ?>" />
								</td>
								<td>
									<input type="text" placeholder="<?php _e('Bank account ID', 'wc-billingo-plus'); ?>" data-name="wc_billingo_plus_additional_accounts[X][bank_account_id]" name="wc_billingo_plus_additional_accounts[<?php echo esc_attr( $account_id ); ?>][bank_account_id]" value="<?php echo esc_attr($account['bank_account_id']); ?>" />
								</td>
								<td>
									<input type="text" placeholder="<?php _e('Document block ID', 'wc-billingo-plus'); ?>" data-name="wc_billingo_plus_additional_accounts[X][block_id]" name="wc_billingo_plus_additional_accounts[<?php echo esc_attr( $account_id ); ?>][block_id]" value="<?php echo esc_attr($account['block_id']); ?>" />
								</td>
								<td>
									<select data-name="wc_billingo_plus_additional_accounts[X][condition]" name="wc_billingo_plus_additional_accounts[<?php echo esc_attr( $account_id ); ?>][condition]" <?php if(empty($account['condition'])): ?>class="placeholder"<?php endif; ?>>
										<option value=""><?php _e('Not conditional', 'wc-billingo-plus'); ?></option>
										<?php foreach ($condition_select_values as $option_group): ?>
											<optgroup label="<?php echo esc_attr($option_group['label']); ?>">
												<?php foreach ($option_group['options'] as $option_id => $option_label): ?>
													<option value="<?php echo esc_attr($option_id); ?>" <?php selected( $account['condition'], $option_id ); ?>><?php echo esc_html($option_label); ?></option>
												<?php endforeach; ?>
											</optgroup>
										<?php endforeach; ?>
									</select>
								</td>
								<td>
									<a href="#" class="delete-row"><span class="dashicons dashicons-dismiss"></span></a>
								</td>
							</tr>
						<?php endforeach ?>
					<?php endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="4">
							<a href="#"><span class="dashicons dashicons-plus-alt"></span> <span><?php _e('Add a new account', 'wc-billingo-plus'); ?></span></a>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
	</td>
</tr>

<script type="text/html" id="wc_billingo_plus_additional_accounts_sample_row">
	<tr>
		<td>
			<input type="text" placeholder="<?php _e('Can be anything', 'wc-billingo-plus'); ?>" data-name="wc_billingo_plus_additional_accounts[X][name]" />
		</td>
		<td>
			<input type="text" placeholder="<?php _e('API V3 key', 'wc-billingo-plus'); ?>" data-name="wc_billingo_plus_additional_accounts[X][api_key]" />
		</td>
		<td>
			<input type="text" placeholder="<?php _e('Bank account ID', 'wc-billingo-plus'); ?>" data-name="wc_billingo_plus_additional_accounts[X][bank_account_id]" />
		</td>
		<td>
			<input type="text" placeholder="<?php _e('Document block ID', 'wc-billingo-plus'); ?>" data-name="wc_billingo_plus_additional_accounts[X][block_id]" />
		</td>
		<td>
			<select data-name="wc_billingo_plus_additional_accounts[X][condition]" class="placeholder">
				<option value=""><?php _e('Not conditional', 'wc-billingo-plus'); ?></option>
				<?php foreach ($condition_select_values as $option_group): ?>
					<optgroup label="<?php echo esc_attr($option_group['label']); ?>">
						<?php foreach ($option_group['options'] as $option_id => $option_label): ?>
							<option value="<?php echo esc_attr($option_id); ?>"><?php echo esc_html($option_label); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			<a href="#" class="delete-row"><span class="dashicons dashicons-dismiss"></span></a>
		</td>
	</tr>
</script>
