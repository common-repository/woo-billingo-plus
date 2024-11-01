<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wc_billingo_plus;
$billingo_payment_methods = WC_Billingo_Plus_Helpers::get_billingo_payment_methods();
$saved_values = get_option('wc_billingo_plus_payment_method_options');

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="wc-billingo-plus-settings–inline-table-scroll">
			<table class="wc-billingo-plus-settings–inline-table wc-billingo-plus-settings–inline-table-payment-methods">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Payment method', 'wc-billingo-plus' ); ?></th>
						<th><?php esc_html_e( 'Billingo ID', 'wc-billingo-plus' ); ?></th>
						<th class="wc-billingo-plus-toggle-group-automation-cell-hide"><?php esc_html_e( 'Payment deadline(days)', 'wc-billingo-plus' ); ?></th>
						<th><?php esc_html_e( 'Mark as paid', 'wc-billingo-plus' ); ?> <?php if($data['disabled']): ?><i class="wc_billingo_pro_label">PRO</i><?php endif; ?></th>
						<th class="wc-billingo-plus-toggle-group-automation-cell-hide"><?php esc_html_e( 'Proforma invoice', 'wc-billingo-plus' ); ?> <?php if($data['disabled']): ?><i class="wc_billingo_pro_label">PRO</i><?php endif; ?></th>
						<th class="wc-billingo-plus-toggle-group-automation-cell-hide"><?php esc_html_e( 'Deposit invoice', 'wc-billingo-plus' ); ?> <?php if($data['disabled']): ?><i class="wc_billingo_pro_label">PRO</i><?php endif; ?></th>
						<th><?php esc_html_e( 'DO NOT generate automatically', 'wc-billingo-plus' ); ?> <?php if($data['disabled']): ?><i class="wc_billingo_pro_label">PRO</i><?php endif; ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $this->get_payment_methods() as $payment_method_id => $payment_method ): ?>
						<?php
						if($saved_values && isset($saved_values[esc_attr( $payment_method_id )])) {
							$value_billingo_id = esc_attr( $saved_values[esc_attr( $payment_method_id )]['billingo_id']);
							$value_deadline = esc_attr( $saved_values[esc_attr( $payment_method_id )]['deadline']);
							$value_complete = $saved_values[esc_attr( $payment_method_id )]['complete'];
							$value_proform = $saved_values[esc_attr( $payment_method_id )]['proform'];

							$value_auto_disabled = false;
							if(isset($saved_values[esc_attr( $payment_method_id )]['auto_disabled'])) {
								$value_auto_disabled = $saved_values[esc_attr( $payment_method_id )]['auto_disabled'];
							}

							$value_deposit = false;
							if(isset($saved_values[esc_attr( $payment_method_id )]['deposit'])) {
								$value_deposit = $saved_values[esc_attr( $payment_method_id )]['deposit'];
							}

						} else {
							$value_billingo_id = 'bankcard';
							$value_deadline = '';
							$value_complete = false;
							$value_proform = false;
							$value_deposit = false;
							$value_auto_disabled = false;

							//Pair a couple of basic payment methods by default
							if($payment_method_id == 'bacs') $value_billingo_id = 'wire_transfer';
							if($payment_method_id == 'cod') $value_billingo_id = 'cash_on_delivery';
							if($payment_method_id == 'barion') $value_billingo_id = 'cash_on_delivery';
							if($payment_method_id == 'paylike') $value_billingo_id = 'paylike';

						}
						?>
						<tr>
							<td class="label"><strong><?php echo $payment_method; ?></strong></td>
							<td>
								<?php
								woocommerce_form_field( 'wc_billingo_plus_payment_method_options['.esc_attr($payment_method_id).'][billingo_id]', array(
									'type' => 'select',
									'options' => $billingo_payment_methods,
								), $value_billingo_id );
								?>
							</td>
							<td class="wc-billingo-plus-toggle-group-automation-cell-hide"><input type="number" name="wc_billingo_plus_payment_method_options[<?php echo esc_attr( $payment_method_id ); ?>][deadline]" value="<?php echo $value_deadline; ?>" /></td>
							<td class="cb"><input <?php disabled( $data['disabled'] ); ?> type="checkbox" name="wc_billingo_plus_payment_method_options[<?php echo esc_attr( $payment_method_id ); ?>][complete]" value="1" <?php checked( $value_complete ); ?> /></td>
							<td class="cb wc-billingo-plus-toggle-group-automation-cell-hide"><input <?php disabled( $data['disabled'] ); ?> type="checkbox" name="wc_billingo_plus_payment_method_options[<?php echo esc_attr( $payment_method_id ); ?>][proform]" value="1" <?php checked( $value_proform ); ?> /></td>
							<td class="cb wc-billingo-plus-toggle-group-automation-cell-hide"><input <?php disabled( $data['disabled'] ); ?> type="checkbox" name="wc_billingo_plus_payment_method_options[<?php echo esc_attr( $payment_method_id ); ?>][deposit]" value="1" <?php checked( $value_deposit ); ?> /></td>
							<td class="cb"><input <?php disabled( $data['disabled'] ); ?> type="checkbox" name="wc_billingo_plus_payment_method_options[<?php echo esc_attr( $payment_method_id ); ?>][auto_disabled]" value="1" <?php checked( $value_auto_disabled ); ?> /></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
		<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
	</td>
</tr>
