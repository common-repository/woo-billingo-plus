<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Get saved values
$saved_values = get_option('wc_billingo_plus_vat_overrides');

//Set valid documents for automation
$line_item_types = array(
	'product' => __('Line item(product)', 'wc-billingo-plus'),
	'shipping' => __('Shipping', 'wc-billingo-plus'),
	'discount' => __('Discount', 'wc-billingo-plus'),
	'fee' => __('Fee', 'wc-billingo-plus'),
	'refund' => __('Refund', 'wc-billingo-plus')
);

//When to generate these documents
$vat_types = WC_Billingo_Plus_Helpers::get_billingo_vat_ids();
$entitlements = WC_Billingo_Plus_Helpers::get_billingo_entitlements();

//Apply filters
$conditions = WC_Billingo_Plus_Conditions::get_conditions('vat_overrides');

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="wc-billingo-plus-settings-vat-overrides">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $automation_id => $automation ): ?>
					<div class="wc-billingo-plus-settings-vat-override wc-billingo-plus-settings-repeat-item">
						<div class="wc-billingo-plus-settings-vat-override-title">
							<span class="text"><?php esc_html_e('Set', 'wc-billingo-plus'); ?></span>
							<div class="select-field">
								<label><span>-</span></label>
								<select class="wc-billingo-plus-settings-vat-override-line-item wc-billingo-plus-settings-repeat-select" data-name="wc_billingo_plus_vat_overrides[X][line_item]">
									<?php foreach ($line_item_types as $value => $label): ?>
										<option value="<?php echo esc_attr($value); ?>" <?php if(isset($automation['line_item'])) selected( $automation['line_item'], $value ); ?>><?php echo esc_html($label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<span class="text"><?php esc_html_e('vat type to', 'wc-billingo-plus'); ?></span>
							<div class="select-field">
								<label><span>-</span></label>
								<select class="wc-billingo-plus-settings-vat-override-vat-type wc-billingo-plus-settings-repeat-select" data-name="wc_billingo_plus_vat_overrides[X][vat_type]">
									<?php foreach ($vat_types as $value => $label): ?>
										<option value="<?php echo esc_attr($value); ?>" <?php if(isset($automation['vat_type'])) selected( $automation['vat_type'], $value ); ?>><?php echo esc_html($label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<a href="#" class="delete-vat-override"><?php _e('delete', 'wc-billingo-plus'); ?></a>
						</div>
						<div class="wc-billingo-plus-settings-vat-override-options">
							<div class="wc-billingo-plus-settings-vat-override-option">
								<label><?php esc_html_e('Entitlement','wc-billingo-plus'); ?></label>
								<div class="wc-billingo-plus-settings-vat-override-option-entitlement">
									<select data-name="wc_billingo_plus_vat_overrides[X][entitlement]">
										<option value="">-</option>
										<?php foreach ($entitlements as $value => $label): ?>
											<option value="<?php echo esc_attr($value); ?>" <?php if(isset($automation['entitlement'])) selected( $automation['entitlement'], $value ); ?>><?php echo esc_html($label); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
						</div>
						<div class="wc-billingo-plus-settings-vat-override-if">
							<div class="wc-billingo-plus-settings-vat-override-if-header">
								<label>
									<input type="checkbox" data-name="wc_billingo_plus_vat_overrides[X][condition_enabled]" <?php checked( $automation['conditional'] ); ?> class="condition" value="yes">
									<span><?php _e('Based on the following conditions, if', 'wc-billingo-plus'); ?></span>
								</label>
								<select data-name="wc_billingo_plus_vat_overrides[X][logic]">
									<option value="and" <?php if(isset($note['logic'])) selected( $note['logic'], 'and' ); ?>><?php _e('All', 'wc-billingo-plus'); ?></option>
									<option value="or" <?php if(isset($note['logic'])) selected( $note['logic'], 'or' ); ?>><?php _e('One', 'wc-billingo-plus'); ?></option>
								</select>
								<span><?php _e('of the following match', 'wc-billingo-plus'); ?></span>
							</div>
							<ul class="wc-billingo-plus-settings-vat-override-if-options conditions" <?php if(!$automation['conditional']): ?>style="display:none"<?php endif; ?> <?php if(isset($automation['conditions'])): ?>data-options="<?php echo esc_attr(json_encode($automation['conditions'])); ?>"<?php endif; ?>></ul>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="wc-billingo-plus-settings-vat-override-add">
			<a href="#"><span class="dashicons dashicons-plus-alt"></span> <span><?php _e('Add new override', 'wc-billingo-plus'); ?></span></a>
		</div>
		<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
	</td>
</tr>

<script type="text/html" id="wc_billingo_plus_vat_override_sample_row">
	<div class="wc-billingo-plus-settings-vat-override wc-billingo-plus-settings-repeat-item">
		<div class="wc-billingo-plus-settings-vat-override-title">
			<span class="text"><?php esc_html_e('Set', 'wc-billingo-plus'); ?></span>
			<div class="select-field">
				<label><span>-</span></label>
				<select class="wc-billingo-plus-settings-vat-override-line-item wc-billingo-plus-settings-repeat-select" data-name="wc_billingo_plus_vat_overrides[X][line_item]">
					<?php foreach ($line_item_types as $value => $label): ?>
						<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<span class="text"><?php esc_html_e('vat type to', 'wc-billingo-plus'); ?></span>
			<div class="select-field">
				<label><span>-</span></label>
				<select class="wc-billingo-plus-settings-vat-override-vat-type wc-billingo-plus-settings-repeat-select" data-name="wc_billingo_plus_vat_overrides[X][vat_type]">
					<?php foreach ($vat_types as $value => $label): ?>
						<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<a href="#" class="delete-vat-override"><?php _e('delete', 'wc-billingo-plus'); ?></a>
		</div>
		<div class="wc-billingo-plus-settings-vat-override-options">
			<div class="wc-billingo-plus-settings-vat-override-option">
				<label><?php esc_html_e('Entitlement','wc-billingo-plus'); ?></label>
				<div class="wc-billingo-plus-settings-vat-override-option-entitlement">
					<select data-name="wc_billingo_plus_vat_overrides[X][entitlement]">
						<option value="">-</option>
						<?php foreach ($entitlements as $value => $label): ?>
							<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
		<div class="wc-billingo-plus-settings-vat-override-if">
			<div class="wc-billingo-plus-settings-vat-override-if-header">
				<label>
					<input type="checkbox" data-name="wc_billingo_plus_vat_overrides[X][condition_enabled]" class="condition" value="yes">
					<span><?php _e('Based on the following conditions, if', 'wc-billingo-plus'); ?></span>
				</label>
				<select data-name="wc_billingo_plus_vat_overrides[X][logic]">
					<option value="and"><?php _e('All', 'wc-billingo-plus'); ?></option>
					<option value="or"><?php _e('One', 'wc-billingo-plus'); ?></option>
				</select>
				<span><?php _e('of the following match', 'wc-billingo-plus'); ?></span>
			</div>
			<ul class="wc-billingo-plus-settings-vat-override-if-options conditions" style="display:none"></ul>
		</div>
	</div>
</script>

<?php echo WC_Billingo_Plus_Conditions::get_sample_row('vat_overrides'); ?>
