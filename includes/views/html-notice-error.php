<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-error wc-billingo-plus-notice">
	<button type="button" class="notice-dismiss wc-billingo-plus-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-billingo-plus-hide-notice' )?>" data-notice="error"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'wc-billingo-plus' ); ?></span></button>
	<h2><?php esc_html_e('Invoice generation failed', 'wc-billingo-plus'); ?></h2>
	<p><?php printf( esc_html__( 'The invoice for order %s could not be created automatically for some reason. You will see the exact error in the order notes.', 'wc-billingo-plus' ), esc_html($order_number) ); ?></p>
	<p>
		<a class="button-secondary" href="<?php echo esc_url($order_link); ?>"><?php esc_html_e( 'Order details', 'wc-billingo-plus' ); ?></a>
	</p>
</div>
