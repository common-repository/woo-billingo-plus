<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


foreach ($billingo_invoices as $invoice) {

	if($invoice['type'] == 'invoice') {
		echo "\n".esc_html( wc_strtoupper( __( 'Invoice', 'wc-billingo-plus' ) ) ) . "\n";
		echo esc_html__('The invoice for the order can be downloaded from here:', 'wc-billingo-plus').' - '.esc_html($invoice['name']).' - '.esc_url($invoice['link'])."\n\n";
	}
	if($invoice['type'] == 'proform') {
		echo "\n".esc_html( wc_strtoupper( __( 'Proforma invoice', 'wc-billingo-plus' ) ) ) . "\n";
		echo esc_html__('The proforma invoice for the order can be downloaded from here:', 'wc-billingo-plus').' - '.esc_html($invoice['name']).' - '.esc_url($invoice['link'])."\n\n";
	}
	if($invoice['type'] == 'void') {
		echo "\n".esc_html( wc_strtoupper( __( 'Reverse invoice', 'wc-billingo-plus' ) ) ) . "\n";
		echo esc_html__('The previous invoice has been canceled. The reverse invoice for the order can be downloaded from here:', 'wc-billingo-plus').' - '.esc_html($invoice['name']).' - '.esc_url($invoice['link'])."\n\n";
	}
	if($invoice['type'] == 'deposit') {
		echo "\n".esc_html( wc_strtoupper( __( 'Deposit invoice', 'wc-billingo-plus' ) ) ) . "\n";
		echo esc_html__('The deposit invoice for the order can be downloaded from here:', 'wc-billingo-plus').' - '.esc_html($invoice['name']).' - '.esc_url($invoice['link'])."\n\n";
	}

}
