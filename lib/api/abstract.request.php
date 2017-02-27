<?php
/**
 * TaxCloud API Request object.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Class ITE_TaxCloud_API_Request
 */
abstract class ITE_TaxCloud_API_Request {

	const URL = 'https://api.taxcloud.net/1.0/Taxcloud/';

	/** @var array */
	protected $settings = array();

	/**
	 * ITE_TaxCloud_API_Request constructor.
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings ) { $this->settings = $settings; }

	/**
	 * Perform a request to the TaxCloud API Servers.
	 *
	 * @param string $type
	 * @param array  $request
	 *
	 * @return array
	 *
	 * @throws \Exception If the HTTP Request failed.
	 */
	protected function request( $type, array $request ) {

		if ( ! isset( $request['apiLoginID'] ) ) {
			$request['apiLoginID'] = $this->settings['tax_cloud_api_id'];
		}

		$url = self::URL . $type;

		if ( ! isset( $request['apiKey'] ) ) {
			$url = add_query_arg( 'apiKey', $this->settings['tax_cloud_api_key'], $url );
		}

		$args     = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => json_encode( $request ),
		);
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		if ( ! $body && ( $code < 200 || $code >= 300 ) ) {

			if ( $type === 'Lookup' ) {
				throw new Exception( __( 'Unable to calculate tax: Unknown Error', 'LION' ) );
			} else {
				throw new Exception( sprintf( __( 'Unable to perform %s request.', 'LION' ), $type ) );
			}
		}

		$body = json_decode( $body, true );

		if ( function_exists( 'json_last_error' ) && json_last_error() && json_last_error_msg() ) {
			throw new Exception( json_last_error_msg() );
		}

		if ( empty( $body['ResponseType'] ) ) {
			$errors = array();

			foreach ( $body['Messages'] as $message ) {
				$errors[] = $message['Message'];
			}

			if ( $type === 'Lookup' ) {
				throw new Exception( sprintf( __( 'Unable to calculate tax: %s', 'LION' ), implode( ',', $errors ) ) );
			} else {
				throw new Exception( sprintf( __( 'Unable to perform %s request: %s', 'LION' ), $type, implode( ',', $errors ) ) );
			}
		}

		return $body;
	}
}