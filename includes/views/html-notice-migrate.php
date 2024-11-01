<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info wc-billingo-plus-notice wc-billingo-plus-welcome">
	<div class="wc-billingo-plus-welcome-body">
		<p><strong><?php esc_html_e('Update Woo Billingo Plus', 'wc-billingo-plus'); ?></strong></p>
		<p><?php esc_html_e('The database contains settings and invoices created by the official Billingo plugin. Want to migrate these settings and invoices to Woo Billingo Plus? After the migration, the official extension will be disabled.', 'wc-billingo-plus'); ?></p>
		<p>
			<a class="button-primary wc-billingo-plus-migrate-button" data-nonce="<?php echo wp_create_nonce( 'wc-billingo-plus-migrate' )?>" href="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_billingo_plus' )); ?>"><?php esc_html_e( 'Yes, migrate the data', 'wc-billingo-plus' ); ?></a>
			<a class="button-secondary wc-billingo-plus-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-billingo-plus-hide-notice' )?>" data-notice="migrate" href="#"><?php esc_html_e( "I don't want to migrate", 'wc-billingo-plus' ); ?></a>
		</p>
	</div>
</div>
