<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="options_group hide_if_variable hide_if_grouped">
	<h4 style="padding-left:12px;"><?php esc_html_e('Woo Billingo Plus invoice settings', 'wc-billingo-plus'); ?></h4>
	<?php
	woocommerce_wp_text_input(array(
		'id' => 'wc_billingo_plus_mennyisegi_egyseg',
		'label' => esc_html__('Unit type', 'wc-billingo-plus'),
		'placeholder' => esc_html__('pcs', 'wc-billingo-plus'),
		'desc_tip' => true,
		'value' => esc_attr( $post->wc_billingo_plus_mennyisegi_egyseg ),
		'description' => esc_html__('This is the unit type for the line item on the invoice. The default value is set in the plugin settings.', 'wc-billingo-plus'),
	));
	?>
	<?php
	woocommerce_wp_text_input(array(
		'id' => 'wc_billingo_plus_megjegyzes',
		'label' => esc_html__('Line item comment', 'wc-billingo-plus'),
		'desc_tip' => true,
		'value' => esc_attr( $post->wc_billingo_plus_megjegyzes ),
		'description' => esc_html__('This note will be visible on the invoice line item.', 'wc-billingo-plus'),
	));
	?>
	<?php
	woocommerce_wp_text_input(array(
		'id' => 'wc_billingo_plus_tetel_nev',
		'label' => esc_html__('Line item name', 'wc-billingo-plus'),
		'desc_tip' => true,
		'value' => esc_attr( $post->wc_billingo_plus_tetel_nev ),
		'description' => esc_html__('Enter a custom name that will appear on the invoice. Default is the name of the product.', 'wc-billingo-plus'),
	));
	?>
	<?php
	woocommerce_wp_checkbox(array(
		'id' => 'wc_billingo_plus_disable_auto_invoice',
		'label' => esc_html__('Turn off auto invoicing', 'wc-billingo-plus'),
		'desc_tip' => true,
		'value' => esc_attr( $post->wc_billingo_plus_disable_auto_invoice ),
		'description' => esc_html__('If checked, no invoice will be automatically issued for the order if this product is included in the order.', 'wc-billingo-plus')
	));
	?>
	<?php
	woocommerce_wp_checkbox(array(
		'id' => 'wc_billingo_plus_hide_item',
		'label' => esc_html__('Hide from invoice', 'wc-billingo-plus'),
		'desc_tip' => true,
		'value' => esc_attr( $post->wc_billingo_plus_hide_item ),
		'description' => esc_html__('If checked, this product will be hidden on the invoices.', 'wc-billingo-plus')
	));
	?>
	<?php
	woocommerce_wp_text_input(array(
		'id' => 'wc_billingo_plus_custom_cost',
		'label' => esc_html__('Cost on invoice', 'wc-billingo-plus'),
		'desc_tip' => true,
		'value' => esc_attr( $post->wc_billingo_plus_custom_cost ),
		'description' => esc_html__('You can overwrite the price of the product on the invoice with this option(enter a net price).', 'wc-billingo-plus'),
	));
	?>
</div>
