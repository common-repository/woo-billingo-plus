<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Webhooks', false ) ) :

	class WC_Billingo_Plus_Webhooks {

		//Init notices
		public static function init() {
			add_filter( 'woocommerce_webhook_topic_hooks', array( __CLASS__, 'add_topics'), 10, 2 );
			add_filter( 'woocommerce_webhook_topics' , array( __CLASS__, 'add_topics_admin_menu'), 10, 1 );
			add_filter( 'woocommerce_valid_webhook_resources', array( __CLASS__, 'add_resource'), 10, 1 );
			add_filter( 'woocommerce_webhook_payload', array( __CLASS__, 'create_payload'), 10, 4 );
			add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'add_documents'), 10, 3 );
		}

		public static function add_topics($topic_hooks, $webhook) {
			if ( 'wc_billingo_plus' == $webhook->get_resource() ) {
				$topic_hooks = array(
					'wc_billingo_plus.created' => array(
						'wc_billingo_plus_document_created',
					),
				);
			}

			return $topic_hooks;
		}

		public static function add_topics_admin_menu( $topics ) {
			$front_end_topics = array(
				'wc_billingo_plus.created' => __( ' Woo Billingo Plus document created', 'wc-billingo-plus' ),
			);
			return array_merge( $topics, $front_end_topics );
		}

		public static function add_resource( $resources ) {
			$resources[] = 'wc_billingo_plus';
			return $resources;
		}

		public static function create_payload( $payload, $resource, $document, $id ) {

			if ( 'wc_billingo_plus' == $resource && empty( $payload ) && isset($document['order_id']) && !empty(wc_get_order($document['order_id'])) ) {
				$webhook = new WC_Webhook( $id );
				$event = $webhook->get_event();
				$current_user = get_current_user_id();

				wp_set_current_user( $webhook->get_user_id() );

				$order = wc_get_order($document['order_id']);
				$document_type = $document['document_type'];
				$payload = array(
					'order_id' => $order->get_id(),
					'document_type' => $document_type,
					'document_url' => WC_Billingo_Plus()->generate_download_link($order, $document_type),
					'document_number' => esc_html($order->get_meta('_wc_billingo_plus_'.$document_type))
				);

				wp_set_current_user( $current_user );
			}

			return $payload;
		}

		public static function add_documents($response, $order, $request) {
			if( empty( $response->data ) ) return $response;

			$order_data = $response->get_data();
			$order_data['wc_billingo_plus'] = array();
			$document_types = WC_Billingo_Plus_Helpers::get_document_types();

			foreach ($document_types as $document_type => $document_label) {
				$document_id = $order->get_meta('_wc_billingo_plus_'.$document_type);
				if($document_id) {
					$order_data['wc_billingo_plus'][$document_type] = array(
						'type' => $document_type,
						'number' => $document_id,
						'url' => WC_Billingo_Plus()->generate_download_link($order, $document_type),
					);
				}
			}

			$response->data = $order_data;
			return $response;
		}

	}

	WC_Billingo_Plus_Webhooks::init();

endif;
