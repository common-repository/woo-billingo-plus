<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Billingo_Plus_Background_Migrator', false ) ) :

	class WC_Billingo_Plus_Background_Migrator {

		public static function init() {

			//Function to run for scheduled async jobs
			add_action('wc_billingo_plus_migrate_orders', array(__CLASS__, 'migrate_orders'), 10, 3);

		}

		public static function migrate_orders($limit = 20) {
			global $wc_billingo_plus;
			global $wpdb;
			$query = array(
				'limit' => $limit,
				'meta_key'		 => '_wc_billingo_plus_migrated',
				'meta_compare' => 'NOT EXISTS'
			);

			$orders = wc_get_orders( $query );
			$count = 0;

			if($orders) {
				foreach ( $orders as $order ) {
					$order_id = $order->get_id();

					//First, check for custom table data, used by billingo 3.2+
					$sql = "SELECT * FROM {$wpdb->prefix}billingo_documents WHERE order_id = {$order_id}";
					$result = $wpdb->get_results( $sql, 'ARRAY_A' );

					foreach ($result as $db_record) {
						$billingo_meta_keys = array('invoice', 'proforma', 'cancellation');
						$woo_billingo_meta_key = array('invoice', 'proform', 'void');
						$billingo_meta_key = array_search($db_record['type'], $billingo_meta_keys);
						$meta_key = $woo_billingo_meta_key[$billingo_meta_key];

						update_post_meta($order_id, '_wc_billingo_plus_'.$meta_key.'_name', $db_record['billingo_number']);
						update_post_meta($order_id, '_wc_billingo_plus_'.$meta_key.'_id', $db_record['billingo_id']);
						update_post_meta($order_id, '_wc_billingo_plus_'.$meta_key.'_pdf', $db_record['link']);
					}

					//And check for post meta too
					$old_meta_keys = array('', '_id', '_pdf', '_dijbekero', '_dijbekero_id', '_dijbekero_pdf', '_own');
					$new_meta_keys = array('invoice_name', 'invoice_id', 'invoice_pdf', 'proform_name', 'proform_id', 'proform_pdf', 'own');

					foreach ($old_meta_keys as $index => $old_meta_key) {
						$old_meta_value = get_post_meta($order_id, '_wc_billingo'.$old_meta_key, true);
						if($old_meta_value) {
							update_post_meta($order_id, '_wc_billingo_plus_'.$new_meta_keys[$index], $old_meta_value);
						}
					}

					if(is_a( $order, 'WC_Order' ) && $wc_billingo_plus->check_payment_method_options($order->get_payment_method(), 'complete')) {
						update_post_meta($order_id, '_wc_billingo_plus_completed', true);
					}

					update_post_meta($order_id, '_wc_billingo_plus_migrated', true);

					$count++;
				}
			}

			if($count == 0) {
				//Migration finished, no more orders found
				update_option('_wc_billingo_plus_migrating', false);
				update_option('_wc_billingo_plus_migrated', true);
			} else {

				//Schedule it again
				WC()->queue()->add( 'wc_billingo_plus_migrate_orders', array(), 'wc-billingo-plus' );

			}
		}

	}

	WC_Billingo_Plus_Background_Migrator::init();

endif;
