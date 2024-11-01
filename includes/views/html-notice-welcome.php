<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info wc-billingo-plus-notice wc-billingo-plus-welcome">
	<div class="wc-billingo-plus-welcome-body">
		<button type="button" class="notice-dismiss wc-billingo-plus-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-billingo-plus-hide-notice' )?>" data-notice="welcome"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'wc-billingo-plus' ); ?></span></button>
		<h2><?php esc_html_e('Woo Billingo Plus', 'wc-billingo-plus'); ?></h2>
		<p><?php esc_html_e("Thank you for using this extension. To get started, go to the settings page and enter your invoicing details. If you are not aware of it, there's a PRO version of this extension, which offers a lot more features compared to the free version, for example, automatic invoice generation, bulk actions, and a lot more.", 'wc-billingo-plus'); ?></p>
		<p>
			<a class="button-primary" target="_blank" rel="noopener noreferrer" href="https://visztpeter.me/woo-billingo-plus"><?php esc_html_e( 'Upgrade to PRO', 'wc-billingo-plus' ); ?></a>
			<a class="button-secondary" href="<?php echo esc_url(admin_url( wp_nonce_url('admin.php?page=wc-settings&tab=integration&section=wc_billingo_plus&welcome=1', 'wc-billingo-plus-hide-notice' ) )); ?>"><?php esc_html_e( 'Go to Settings', 'wc-billingo-plus' ); ?></a>
		</p>
	</div>
</div>
