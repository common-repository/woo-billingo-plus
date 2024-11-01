<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hide_link = esc_url(admin_url( wp_nonce_url('admin.php?page=wc-settings&tab=integration&section=wc_billingo_plus&v3_update=1', 'wc-billingo-plus-hide-notice' ) ));

?>
<div class="notice notice-warning wc-billingo-plus-notice wc-billingo-plus-welcome">
	<div class="wc-billingo-plus-welcome-body">
		<a style="text-decoration:none;" href="<?php echo $hide_link; ?>" class="notice-dismiss wc-billingo-plus-hide-notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'wc-billingo-plus' ); ?></span></a>
		<h2><?php esc_html_e('A new Billingo API key is required', 'wc-billingo-plus'); ?></h2>
		<p><?php _e('The latest version of Woo Billingo Plus uses the Billingo API V3, for which you need to generate a <strong> new API key </strong> and enter it in the settings. You will also need to select the <strong> bank account number </strong> used for billing.', 'wc-billingo-plus'); ?></p>
		<p><?php echo sprintf('You can find more details about the changes in the <a href="https://visztpeter.me/dokumentacio/v3-api" target="_blank">documentation</a>.', 'wc-billingo-plus'); ?></p>
		<p><?php _e("Please <strong> verify that your invoices are properly created </strong> for new orders once you've made the necessary changes!", 'wc-billingo-plus'); ?></p>
		<p>
			<a class="button-secondary" href="<?php echo $hide_link; ?>"><?php esc_html_e( 'Go to settings', 'wc-billingo-plus' ); ?></a>
		</p>
	</div>
</div>
