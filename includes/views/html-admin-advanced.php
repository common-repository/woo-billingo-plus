<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('wc_billingo_plus_advanced_options');

//Set valid documents for automation
$line_item_types = array(
	'bank_account' => __('Bank account', 'wc-billingo-plus'),
	'language' => __('Language', 'wc-billingo-plus'),
	'invoice_block' => __('Invoice block', 'wc-billingo-plus'),
	'entitlement' => __('Line item entitlement', 'wc-billingo-plus')
);

//Setup possible values
$options_values = array();

//Setup bank accounts
$bank_accounts = $this->get_bank_accounts();
$invoice_blocks = $this->get_invoice_blocks();
$languages = WC_Billingo_Plus_Helpers::get_supported_languages();
$entitlements = WC_Billingo_Plus_Helpers::get_billingo_entitlements();

foreach ($bank_accounts as $bank_account_id => $bank_account_label) {
	$options_values['bank_account_'.$bank_account_id] = $bank_account_label;
}

foreach ($invoice_blocks as $invoice_block_id => $invoice_block_label) {
	$options_values['invoice_block_'.$invoice_block_id] = $invoice_block_label;
}

foreach ($languages as $language_id => $language_label) {
	$options_values['language_'.$language_id] = $language_label;
}

foreach ($entitlements as $entitlement_id => $entitlement_label) {
	$options_values['entitlement_'.$entitlement_id] = $entitlement_label;
}

//Apply filters
$conditions = WC_Billingo_Plus_Conditions::get_conditions('advanced_options');

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="wc-billingo-plus-settings-advanced-options">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $automation_id => $automation ): ?>
					<div class="wc-billingo-plus-settings-advanced-option wc-billingo-plus-settings-repeat-item">
						<div class="wc-billingo-plus-settings-advanced-option-title">
							<span class="text"><?php echo esc_html_x('Set', 'advanced-options', 'wc-billingo-plus'); ?></span>
							<div class="select-field">
								<label><span>-</span></label>
								<select class="wc-billingo-plus-settings-advanced-option-property wc-billingo-plus-settings-repeat-select" data-name="wc_billingo_plus_advanced_options[X][property]">
									<?php foreach ($line_item_types as $value => $label): ?>
										<option value="<?php echo esc_attr($value); ?>" <?php if(isset($automation['property'])) selected( $automation['property'], $value ); ?>><?php echo esc_html($label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<span class="text"><?php echo esc_html_x('to', 'advanced-options', 'wc-billingo-plus'); ?></span>
							<div class="select-field">
								<label><span>-</span></label>
								<select class="wc-billingo-plus-settings-advanced-option-value wc-billingo-plus-settings-repeat-select" data-name="wc_billingo_plus_advanced_options[X][value]">
									<?php foreach ($options_values as $value => $label): ?>
										<option value="<?php echo esc_attr($value); ?>" <?php if(isset($automation['value'])) selected( $automation['value'], $value ); ?>><?php echo esc_html($label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<a href="#" class="delete-advanced-option"><?php _e('delete', 'wc-billingo-plus'); ?></a>
						</div>
						<div class="wc-billingo-plus-settings-advanced-option-if">
							<div class="wc-billingo-plus-settings-advanced-option-if-header">
								<label>
									<input type="checkbox" data-name="wc_billingo_plus_advanced_options[X][condition_enabled]" class="condition" value="yes" checked style="display:none;">
									<span><?php _e('Based on the following conditions, if', 'wc-billingo-plus'); ?></span>
								</label>
								<select data-name="wc_billingo_plus_advanced_options[X][logic]">
									<option value="and" <?php if(isset($note['logic'])) selected( $note['logic'], 'and' ); ?>><?php _e('All', 'wc-billingo-plus'); ?></option>
									<option value="or" <?php if(isset($note['logic'])) selected( $note['logic'], 'or' ); ?>><?php _e('One', 'wc-billingo-plus'); ?></option>
								</select>
								<span><?php _e('of the following match', 'wc-billingo-plus'); ?></span>
							</div>
							<ul class="wc-billingo-plus-settings-advanced-option-if-options conditions" <?php if(isset($automation['conditions'])): ?>data-options="<?php echo esc_attr(json_encode($automation['conditions'])); ?>"<?php endif; ?>></ul>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="wc-billingo-plus-settings-advanced-option-add">
			<a href="#"><span class="dashicons dashicons-plus-alt"></span> <span><?php _e('Add new', 'wc-billingo-plus'); ?></span></a>
		</div>
		<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
	</td>
</tr>

<script type="text/html" id="wc_billingo_plus_advanced_option_sample_row">
	<div class="wc-billingo-plus-settings-advanced-option wc-billingo-plus-settings-repeat-item">
		<div class="wc-billingo-plus-settings-advanced-option-title">
			<span class="text"><?php echo esc_html_x('Set', 'advanced-options', 'wc-billingo-plus'); ?></span>
			<div class="select-field">
				<label><span>-</span></label>
				<select class="wc-billingo-plus-settings-advanced-option-property wc-billingo-plus-settings-repeat-select" data-name="wc_billingo_plus_advanced_options[X][property]">
					<?php foreach ($line_item_types as $value => $label): ?>
						<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<span class="text"><?php echo esc_html_x('to', 'advanced-options', 'wc-billingo-plus'); ?></span>
			<div class="select-field">
				<label><span>-</span></label>
				<select class="wc-billingo-plus-settings-advanced-option-value wc-billingo-plus-settings-repeat-select" data-name="wc_billingo_plus_advanced_options[X][value]">
					<?php foreach ($options_values as $value => $label): ?>
						<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<a href="#" class="delete-advanced-option"><?php _e('delete', 'wc-billingo-plus'); ?></a>
		</div>
		<div class="wc-billingo-plus-settings-advanced-option-if">
			<div class="wc-billingo-plus-settings-advanced-option-if-header">
				<label>
					<input type="checkbox" data-name="wc_billingo_plus_advanced_options[X][condition_enabled]" class="condition" value="yes" checked style="display:none;">
					<span><?php _e('Based on the following conditions, if', 'wc-billingo-plus'); ?></span>
				</label>
				<select data-name="wc_billingo_plus_advanced_options[X][logic]">
					<option value="and"><?php _e('All', 'wc-billingo-plus'); ?></option>
					<option value="or"><?php _e('One', 'wc-billingo-plus'); ?></option>
				</select>
				<span><?php _e('of the following match', 'wc-billingo-plus'); ?></span>
			</div>
			<ul class="wc-billingo-plus-settings-advanced-option-if-options conditions"></ul>
		</div>
	</div>
</script>

<?php echo WC_Billingo_Plus_Conditions::get_sample_row('advanced_options'); ?>
