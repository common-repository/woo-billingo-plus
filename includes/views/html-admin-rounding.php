<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_values = get_option('wc_billingo_plus_rounding_options');
if(empty($saved_values)) {
	$currency = get_woocommerce_currency();
	$saved_values = array($currency=>0);
}
?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php esc_html_e( 'Rounding:', 'wc-billingo-plus' ); ?></th>
	<td class="forminp">
		<table class="wc-billingo-plus-settings–inline-table wc-billingo-plus-settings–inline-table-rounding">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Currency', 'wc-billingo-plus' ); ?></th>
					<th><?php esc_html_e( 'Rounding', 'wc-billingo-plus' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($saved_values as $currency => $rounding): ?>
					<tr>
						<td class="wc-billingo-plus-rounding-table-currency">
							<?php
							woocommerce_form_field( 'wc_billingo_plus_rounding_options[][currency]', array(
								'type' => 'select',
								'options' => $this->get_currency_codes(),
							), $currency );
							?>
						</td>
						<td class="wc-billingo-plus-rounding-table-rounding">
							<?php
							woocommerce_form_field( 'wc_billingo_plus_rounding_options[][rounding]', array(
								'type' => 'select',
								'options' => array(
									0 => __('None', 'wc-billingo-plus'),
									1 => __('Round to 1', 'wc-billingo-plus'),
									5 => __('Round to 5', 'wc-billingo-plus'),
									10 => __('Round to 10', 'wc-billingo-plus'),
								),
							), intval($rounding) );
							?>
						</td>
						<td>
							<a href="#" class="delete-row"><span class="dashicons dashicons-dismiss"></span></a>
							<a href="#" class="add-row"><span class="dashicons dashicons-plus-alt"></span></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'You can set the rounding type for each currency you sell in. This changes the total value on the invoice.', 'wc-billingo-plus' ); ?></p>
	</td>
</tr>
