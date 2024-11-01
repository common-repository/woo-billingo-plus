<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vat_number = $order->get_meta('billingo_plus_adoszam');
if($order->get_meta('_billing_wc_billingo_plus_adoszam')) $vat_number = $order->get_meta('_billing_wc_billingo_plus_adoszam');

?>
	<table>
		<?php if($vat_number_data['valid'] === 'unknown'): ?>
			<tr>
				<th><?php esc_html_e('VAT number', 'wc-billingo-plus'); ?></th>
				<td><?php echo esc_html($vat_number); ?> <span class="dashicons dashicons-warning"></span></td>
			</tr>
			<tr><td colspan="2"><small><?php esc_html_e('When creating the order, it was not possible to retrieve the tax number data from NAV.', 'wc-billingo-plus'); ?></small></td></tr>
		<?php else: ?>
			<tr>
				<th><?php esc_html_e('VAT number', 'wc-billingo-plus'); ?></th>
				<td><?php echo esc_html($vat_number); ?> <span class="dashicons dashicons-yes"></span></td>
			</tr>
		<?php endif; ?>

		<?php if(array_key_exists('address',$vat_number_data)): ?>
			<tr>
				<th><?php esc_html_e('Name', 'wc-billingo-plus'); ?></th>
				<td><?php echo esc_html($vat_number_data['name']); ?></td>
			</tr>
			<?php $address_labels = array(
				"countryCode" => esc_html__('Country code', 'wc-billingo-plus'),
				"postalCode" => esc_html__('Postcode', 'wc-billingo-plus'),
				"city" => esc_html__('City', 'wc-billingo-plus'),
				"streetName" => esc_html__('Street', 'wc-billingo-plus'),
				"publicPlaceCategory" => esc_html__('Street type', 'wc-billingo-plus'),
				"number" => esc_html__('Number', 'wc-billingo-plus'),
				"building" => esc_html__('Building', 'wc-billingo-plus'),
				"staircase" => esc_html__('Staircase', 'wc-billingo-plus'),
				"floor" => esc_html__('Floor', 'wc-billingo-plus'),
				"door" => esc_html__('Door', 'wc-billingo-plus')
			);
			?>
			<?php foreach ($address_labels as $field => $label): ?>
				<?php if($vat_number_data['address'][$field]): ?>
					<tr>
						<th><?php echo $label; ?></th>
						<td><?php echo esc_html($vat_number_data['address'][$field]); ?></td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
			<tr><td colspan="2"><small><?php esc_html_e('The data was retrieved from the NAV database.', 'wc-billingo-plus'); ?></small></td></tr>
		<?php endif; ?>
	</table>
