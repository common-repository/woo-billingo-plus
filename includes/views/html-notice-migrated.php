<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info wc-billingo-plus-notice wc-billingo-plus-welcome">
	<div class="wc-billingo-plus-welcome-body">
		<button type="button" class="notice-dismiss wc-billingo-plus-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-billingo-plus-hide-notice' )?>" data-notice="migrated"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'wc-billingo-plus' ); ?></span></button>
		<p><?php esc_html_e('Woo Billingo Plus has finished syncing previously created invoices. The official Billingo plugin has been disabled.', 'wc-billingo-plus'); ?></p>
	</div>
</div>
