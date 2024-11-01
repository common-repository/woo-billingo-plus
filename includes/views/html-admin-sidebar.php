<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wc-billingo-plus-settings-sidebar" data-nonce="<?php echo wp_create_nonce( 'wc-billingo-plus-license-check' )?>">

	<?php if(!get_option( 'woocommerce_calc_taxes' ) && !$this->get_option('afakulcs') && get_option('wc_billingo_plus_payment_method_options')): ?>
		<div class="wc-billingo-plus-settings-widget wc-billingo-plus-settings-widget-vat">
			<h3><span class="dashicons dashicons-warning"></span> <?php _e('TAX rate setup', 'wc-billingo-plus'); ?></h3>
			<p><?php _e('You are using the default VAT rate and taxes are disabled in WooCommerce, so the VAT rate will be 0% on the invoice.', 'wc-billingo-plus'); ?></p>
			<p><?php _e("If you don't need to charge sales tax, you can set a fixed VAT rate in the settings, like AAM. If you need percentage-based VAT rates, or you have a more complicated situation, you need to configure the WooCommerce TAX settings properly. Here is a guide to do this:", 'wc-billingo-plus'); ?></p>
			<p><a href="https://visztpeter.me/2020/05/13/afakulcsok-beallitasa-woocommerce-aruhazakban/" target="_blank"><?php _e('Setting up VAT/TAX rates in WooCommerce shops.', 'wc-billingo-plus'); ?></a></p>
		</div>
	<?php endif; ?>

	<?php if(WC_Billingo_Plus_Pro::is_pro_enabled() || (!WC_Billingo_Plus_Pro::is_pro_enabled() && WC_Billingo_Plus_Pro::get_license_key())): ?>

		<div class="wc-billingo-plus-settings-widget wc-billingo-plus-settings-widget-pro wc-billingo-plus-settings-widget-pro-active wc-billingo-plus-settings-widget-pro-<?php if(WC_Billingo_Plus_Pro::is_pro_enabled()): ?>state-active<?php else: ?>state-expired<?php endif; ?>">

			<?php if(WC_Billingo_Plus_Pro::is_pro_enabled()): ?>
				<h3><span class="dashicons dashicons-yes-alt"></span> <?php _e('The PRO version is active', 'wc-billingo-plus'); ?></h3>
				<p><?php _e('You have successfully activated the PRO version.', 'wc-billingo-plus'); ?></p>
			<?php else: ?>
				<h3><span class="dashicons dashicons-warning"></span> <?php _e('The PRO version is expired', 'wc-billingo-plus'); ?></h3>
				<p><?php _e('The following license key is expired.', 'wc-billingo-plus'); ?></p>
			<?php endif; ?>

			<p>
				<span class="wc-billingo-plus-settings-widget-pro-label"><?php _e('License key', 'wc-billingo-plus'); ?></span><br>
				<?php echo esc_html(WC_Billingo_Plus_Pro::get_license_key()); ?>
			</p>

			<?php $license = WC_Billingo_Plus_Pro::get_license_key_meta(); ?>
			<?php if(isset($license['type'])): ?>
			<p class="single-license-info">
				<span class="wc-billingo-plus-settings-widget-pro-label"><?php _e('License type', 'wc-billingo-plus'); ?></span><br>
				<?php if ( $license['type'] == 'unlimited' ): ?>
					<?php _e( 'Unlimited', 'wc-billingo-plus' ); ?>
				<?php else: ?>
					<?php _e( 'Subscription', 'wc-billingo-plus' ); ?>
				<?php endif; ?>
			</p>
			<?php endif; ?>

			<?php if(isset($license['next_payment'])): ?>
			<p class="single-license-info">
				<span class="wc-billingo-plus-settings-widget-pro-label"><?php _e('Next payment', 'wc-billingo-plus'); ?></span><br>
				<?php echo esc_html($license['next_payment']); ?>
			</p>
			<?php endif; ?>

			<div class="wc-billingo-plus-settings-widget-pro-deactivate">
				<p>
					<a class="button-secondary" id="wc_billingo_plus_deactivate_pro"><?php esc_html_e( 'Deactivate license', 'wc-billingo-plus' ); ?></a>
					<a class="button-secondary" id="wc_billingo_plus_validate_pro"><?php esc_html_e( 'Reload license', 'wc-billingo-plus' ); ?></a>
				</p>
				<p><small><?php esc_html_e( 'If you want to activate the license on another website, you must first deactivate it on this website.', 'wc-billingo-plus' ); ?></small></p>
			</div>
		</div>

	<?php else: ?>

		<div class="wc-billingo-plus-settings-widget wc-billingo-plus-settings-widget-pro">
			<h3><?php esc_html_e( 'PRO version', 'wc-billingo-plus' ); ?></h3>
			<p><?php esc_html_e( 'If you have already purchased the PRO version, enter the license key and the e-mail address used to purchase:', 'wc-billingo-plus' ); ?></p>

			<div class="wc-billingo-plus-settings-widget-pro-notice" style="display:none">
				<span class="dashicons dashicons-warning"></span>
				<p></p>
			</div>

			<fieldset>
				<input class="input-text regular-input" type="text" name="woocommerce_wc_billingo_pro_key" id="woocommerce_wc_billingo_pro_key" value="" placeholder="<?php esc_html_e( 'License key', 'wc-billingo-plus' ); ?>"><br>
			</fieldset>
			<p>
				<button class="button-primary" type="button" id="wc_billingo_plus_activate_pro"><?php _e('Activate', 'wc-billingo-plus'); ?></button>
			</p>
			<h4><?php esc_html_e( 'Why should I use the PRO version?', 'wc-billingo-plus' ); ?></h4>
			<ul>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Automatic invoicing', 'wc-billingo-plus' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Bulk invoice generation', 'wc-billingo-plus' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Bulk download and print', 'wc-billingo-plus' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Attach PDF invoices to WooCommerce e-mails', 'wc-billingo-plus' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Premium support and a lot more', 'wc-billingo-plus' ); ?></li>
			</ul>
			<div class="wc-billingo-plus-settings-widget-pro-cta">
				<a href="https://visztpeter.me/woo-billingo-plus/"><span class="dashicons dashicons-cart"></span> <span><?php esc_html_e( 'Purchase PRO version', 'wc-billingo-plus' ); ?></span></a>
				<span>
					<small><?php esc_html_e( 'net', 'wc-billingo-plus' ); ?></small>
					<strong><?php esc_html_e( '30 € / year', 'wc-billingo-plus' ); ?></strong>
				</span>
			</div>

		</div>

	<?php endif; ?>

	<?php $vp_woo_pont_slug = 'hungarian-pickup-points-for-woocommerce'; ?>
	<?php if(!file_exists( WP_PLUGIN_DIR . '/' . $vp_woo_pont_slug )): ?>
		<div class="wc-billingo-plus-settings-widget wc-billingo-plus-settings-widget-vp-pont">
			<h3><?php esc_html_e( 'Csomagpontok és címkegenerálás', 'wc-billingo-plus' ); ?></h3>
			<p><?php esc_html_e( 'Próbáld ki az ingyenes csomagpontos bővítményt, ami támogatja az összes népszerű futárszolgálat átvételi helyeit. A PRO verzióval címkét is generálhatsz, házhozszállításra is, automata csomagkövetéssel.', 'wc-billingo-plus' ); ?></p>
			<div class="wc-billingo-plus-settings-widget-vp-pont-logos"></div>
			<div class="wc-billingo-plus-settings-widget-vp-pont-cta">
				<?php
				$vp_woo_pont_install_url = wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'install-plugin',
							'plugin' => $vp_woo_pont_slug
						),
						self_admin_url( 'update.php' )
					),
					'install-plugin_'.$vp_woo_pont_slug
				);
				?>
				<a class="button-primary" href="<?php echo esc_url($vp_woo_pont_install_url); ?>">Telepítés</a>
				<a class="button-secondary" href="https://visztpeter.me/woocommerce-csomagpont-integracio/" target="_blank"><?php esc_html_e( 'Bővebb információk', 'wc-billingo-plus' ); ?></a>
			</div>
		</div>
	<?php endif; ?>

	<?php
	//NAV validator, Check if all data is set
	$vat_number_form_enabled = $this->get_option('vat_number_form', 'no');
	$nav_check_enabled = $this->get_option('vat_number_nav', 'no');
	$config_user = $this->get_option('vat_number_nav_username');
	$config_pass = $this->get_option('vat_number_nav_password');
	$config_tax = $this->get_option('vat_number_nav_number');
	$config_signature = $this->get_option('vat_number_nav_signature');
	if(class_exists('WC_Billingo_Plus_Vat_Number_Field') && $vat_number_form_enabled == 'yes' && $nav_check_enabled == 'yes' && !empty($config_user) && !empty($config_pass) && !empty($config_tax) && !empty($config_signature)): ?>
		<?php $nav_data = WC_Billingo_Plus_Vat_Number_Field::get_vat_number_data($config_tax); ?>
		<div class="wc-billingo-plus-settings-widget wc-billingo-plus-settings-widget-nav <?php if($nav_data && $nav_data['valid'] && $nav_data['name']): ?>ok<?php endif; ?>">
			<h3><?php esc_html_e('NAV tax number validation', 'wc-billingo-plus'); ?></h3>
			<p><?php echo sprintf(esc_html__('The tax number validation is currently enabled and you entered your credentials to make it work. Here you can check that everything is working well by validating your own tax number(%s):', 'wc-billingo-plus'), $config_tax); ?></p>
			<?php if($nav_data && $nav_data['valid'] && $nav_data['name']): ?>
				<div class="wc-billingo-plus-settings-widget-nav-check wc-billingo-plus-settings-widget-nav-check-ok">
					<span class="dashicons dashicons-yes"></span>
					<?php echo $nav_data['name']; ?>
				</div>
			<?php else: ?>
				<div class="wc-billingo-plus-settings-widget-nav-check wc-billingo-plus-settings-widget-nav-check-fail">
					<span class="dashicons dashicons-warning"></span>
					<?php esc_html_e('Your tax number could not be validated. Make sure your NAV username and password is correct. You can find more information in the log files.', 'wc-billingo-plus'); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if(!get_option('_wc_billingo_plus_hide_rate_request')): ?>
		<div class="wc-billingo-plus-settings-widget wc-billingo-plus-settings-widget-rating">
			<h3><?php esc_html_e('Rating', 'wc-billingo-plus'); ?> <span>⭐️⭐️⭐️⭐️⭐️</span></h3>
			<p><?php _e( 'Enjoyed <strong>Woo Billingo Plus</strong>? Please leave us a ★★★★★ rating. We appreciate your support!', 'wc-billingo-plus' ); ?></p>
			<p>
				<a class="button-primary" target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/woo-billingo-plus/reviews/?filter=5#new-post"><?php esc_html_e( 'Leave a review', 'wc-billingo-plus' ); ?></a>
				<a class="button-secondary" data-nonce="<?php echo wp_create_nonce( 'wc-billingo-plus-hide-rate-request' )?>"><?php esc_html_e( 'Hide this message', 'wc-billingo-plus' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<div class="wc-billingo-plus-settings-widget">
		<h3><?php esc_html_e('Support', 'wc-billingo-plus'); ?></h3>
		<p><?php esc_html_e('It is important to point out that this extension was not created by Billingo, so if you have any questions about the operation of the extension, please contact me at one of the following contacts:', 'wc-billingo-plus'); ?></p>
		<ul>
			<li><a href="https://visztpeter.me/dokumentacio/" target="_blank"><?php esc_html_e('Documentation', 'wc-billingo-plus'); ?></a></li>
			<li><a href="mailto:support@visztpeter.me"><?php esc_html_e('E-mail (support@visztpeter.me)', 'wc-billingo-plus'); ?></a></li>
			<li><a href="https://wordpress.org/support/plugin/woo-billingo-plus/" target="_blank"><?php esc_html_e('Forum thread on WordPress.org', 'wc-billingo-plus'); ?></a></li>
		</ul>
	</div>

</div>
