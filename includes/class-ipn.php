<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_IPN', false ) ) :

	class WC_Billingo_Plus_IPN {

		public static function load() {
			add_action( 'init', array( __CLASS__, 'init' ) );

			//Display a button in the orders admin page to manually run sync
			add_action( 'manage_posts_extra_tablenav', array( __CLASS__, 'display_sync_button' ), 9 );
			add_action( 'wp_ajax_wc_billingo_plus_run_sync', array( __CLASS__, 'run_sync_with_ajax' ) );
		}

		public static function init() {
			if(WC_Billingo_Plus()->get_option('bank_sync', 'no') == 'yes') {
				$queue = WC()->queue();
				if($queue) {
					$next = $queue->get_next( 'wc_billingo_plus_ipn_check' );
					if ( ! $next ) {
						$interval = WC_Billingo_Plus()->get_option('bank_sync_interval', 30);
						$queue->cancel_all( 'wc_billingo_plus_ipn_check' );
						$queue->schedule_recurring( time(), MINUTE_IN_SECONDS*intval($interval), 'wc_billingo_plus_ipn_check' );
					}
				}
				add_action( 'wc_billingo_plus_ipn_check', array( __CLASS__, 'ipn_check' ) );
			}
		}

		public static function ipn_check() {

			//Get orders that has an invoice and not marked paid
			$target_status = WC_Billingo_Plus()->get_option('bank_sync_status', 'no');
			$query = array(
				'post_type' => 'shop_order',
				'posts_per_page' => -1,
				'post_status' => 'any',
				'date_query' => array(
					array(
						'after' => '14 days ago',
					)
				),
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_wc_billingo_plus_invoice_id',
							'compare' => 'EXISTS'
						),
						array(
								'key'     => '_wc_billingo_plus_proform_id',
								'compare' => 'EXISTS'
						),
					),
					array(
						'key'     => '_wc_billingo_plus_completed',
						'compare' => 'NOT EXISTS'
					),
				),
				'fields' => 'ids'
			);

			$orders = get_posts( $query );

			//Create results
			$results = array();

			foreach ($orders as $order_id) {
				$order = wc_get_order($order_id);

				//Get invoice data
				$invoice_id = $order->get_meta('_wc_billingo_plus_invoice_id');
				$order_id = $order->get_id();

				//If theres no invoice, check for proform
				if(!$invoice_id) {
					$invoice_id = $order->get_meta('_wc_billingo_plus_proform_id');
				}

				//Get billingo data
				$billingo = WC_Billingo_Plus()->get_billingo_api($order);
				$document = $billingo->get('documents/'.$invoice_id);

				//Check for errors
				if(is_wp_error($document)) {
					WC_Billingo_Plus()->log_error_messages($document, 'transaction-sync-'.$order_id);
					continue;
				}

				//If no errors, check if its paid
				if(isset($document['payment_status']) && $document['payment_status'] == 'paid') {
					$order->add_order_note( __( 'Billingo credit entry successfully recorded(through bank sync)', 'wc-billingo-plus' ) );
					$order->update_meta_data( '_wc_billingo_plus_completed', $document['paid_date'] );
					$order->set_date_paid( time() );
					$order->save();
					$results[] = $order_id;

					//Change status if needed
					if($target_status && $target_status != 'no') {
						if(apply_filters('wc_billingo_plus_ipn_should_change_order_status', ($order->get_status() != 'completed'), $order, $document, $target_status)) {
							$order->update_status($target_status, __( 'Order status changed with Billingo bank sync.', 'wc-billingo-plus' ));
						}
					}
				}

			}

			return $results;
		}

		public static function display_sync_button() {
			global $pagenow, $post_type;
			if( 'shop_order' === $post_type && 'edit.php' === $pagenow ) {
				?>
				<div class="alignleft actions wc-billingo-plus-start-sync">
					<a href="#" class="button" data-nonce="<?php echo wp_create_nonce( "wc_billingo_plus_start_sync" ); ?>">
						<span class="dashicons dashicons-update"></span>
						<span><?php esc_html_e('Bank Sync', 'wc-billingo-plus'); ?></span>
					</a>
				</div>
				<?php
			}
		}

		public static function run_sync_with_ajax() {
			check_ajax_referer( 'wc_billingo_plus_start_sync', 'nonce' );
			if ( !current_user_can( 'edit_shop_orders' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this action.', 'wc-billingo-plus' ) );
			}
			$processed_orders = self::ipn_check();
			wp_send_json_success($processed_orders);
		}
	}

	WC_Billingo_Plus_IPN::load();

endif;
