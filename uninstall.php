<?php

// If uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

//Check if we need to delete anything
$wc_billingo_plus_settings = get_option( 'woocommerce_wc_billingo_plus_settings', null );
if($wc_billingo_plus_settings['uninstall'] && $wc_billingo_plus_settings['uninstall'] == 'yes') {
	// Delete admin notices
	delete_metadata( 'user', 0, 'wc_billingo_plus_admin_notices', '', true );

	//Delete options
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wc\_billingo_plus\_%';");
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_wc\_billingo_plus\_%';");
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_wc\_billingo_pro\_%';");
	delete_option('woocommerce_wc_billingo_plus_settings');
}
