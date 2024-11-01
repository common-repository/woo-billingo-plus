<?php

if (!defined( 'ABSPATH' )) {
	exit;
}

if ( ! class_exists( 'WC_Billingo_Plus_Api', false ) ) :

	class WC_Billingo_Plus_Api {
		private $api_key;
		private $api_url;

		function __construct($api_key) {
			$this->api_key = $api_key;
			$this->api_url = 'https://api.billingo.hu/v3';
		}

		public function request($method, $route, $data = false) {

			//Set default arguments
			$args = array(
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
					'Accept' => 'application/json',
					'X-Api-Key' => $this->api_key
				),
				'method' => $method
			);

			//If its a post or put request, define body too
			if(in_array($method, array('PUT', 'POST'))) {
				$args['data_format'] = 'body';
				$args['body'] = json_encode($data);
			}

			//If its a pdf download requuest, modify headers
			if(strpos($route, 'download') !== false) {
				$args['headers']['Accept'] = 'application/pdf';
			}

			//Make the request
			$response = wp_remote_request( $this->api_url.'/'.$route, $args);

			//Check for http errors
			if(is_wp_error($response)) {
				return $response;
			}

			//Get response body and status code
			$body = wp_remote_retrieve_body( $response );
			$status_code = wp_remote_retrieve_response_code( $response );

			//Check for billingo errors
			if(!in_array($status_code, array(200, 201, 204))) {
				$data = json_decode( $body, true );
				return $this->handleError($data);
			}

			//If it was a download request, return just the body
			if(strpos($route, 'download') !== false) {
				return $body;
			}

			//Otherwise, we can return the decoded body
			return $this->handleResponse($body);

		}

		public function get($route) {
			return $this->request('GET', $route);
		}

		public function post($route, $data = array()) {
			return $this->request('POST', $route, $data);
		}

		public function put($route, $data = array()) {
			return $this->request('PUT', $route, $data);
		}

		public function download($invoice_id, $pdf_file) {

			//If upload folder doesn't exists, create it with an empty index.html file
			$file = array(
				'base' 		=> $pdf_file['file_dir'],
				'file' 		=> 'index.html',
				'content' 	=> ''
			);

			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}

			//Make a GET request and store the pdf file
			$body = $this->request('GET', 'documents/'.$invoice_id.'/download');

			if(is_wp_error($body)) {
				return $body;
			} else {
				file_put_contents($pdf_file['path'], $body);
				return true;
			}

		}

		public function handleResponse($body) {
			$data = json_decode( $body, true );
			if(isset($data['data'])) {
				return $data['data'];
			} else {
				return $data;
			}
		}

		public function handleError($data) {
			$error_msg = 'Something went wrong...';
			if(isset($data['error']) && isset($data['error']['message']) && $data['error']['message'] != '') {
				$error_msg = $data['error']['message'];
			} elseif(isset($data['errors'])) {
				$error_msg = $data['errors'][0]['message'];
			}
			return new WP_Error( 'billingo_error', $error_msg );
		}

	}

endif;
