<?php
/**
 * Register the location validator.
 *
 * @since   1.36.0
 * @license GPLv2
 */

/**
 * Class ITE_TaxCloud_Location_Validator
 */
class ITE_TaxCloud_Location_Validator implements ITE_Location_Validator {

	/** @var array */
	private $settings = array();

	/**
	 * ITE_TaxCloud_Location_Validator constructor.
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings ) { $this->settings = $settings; }

	/**
	 * @inheritDoc
	 */
	public static function get_name() {
		return 'tax-cloud';
	}

	/**
	 * @inheritDoc
	 */
	public function validate( ITE_Location $location ) {
		return $this->validate_address( $location ) === true;
	}

	/**
	 * @inheritDoc
	 */
	public function validate_for_cart( ITE_Location $location, ITE_Cart $cart ) {

		$message = $this->validate_address( $location );

		if ( $message === true ) {
			return true;
		}

		$cart->get_feedback()->add_error( $message );

		return false;
	}

	/**
	 * Validate an address.
	 *
	 * @since 1.36.0
	 *
	 * @param ITE_Location $location
	 *
	 * @return string|true Error message or boolean true.
	 */
	private function validate_address( $location ) {

		$first_five = absint( substr( $location['zip'], 0, 5 ) ); // just get the first five

		$dest = array(
			'Address1' => $location['address1'],
			'Address2' => ! empty( $location['address2'] ) ? $location['address2'] : '',
			'City'     => ! empty( $location['city'] ) ? $location['city'] : '',
			'State'    => ! empty( $location['state'] ) ? $location['state'] : '',
			'Zip5'     => $first_five, // just get the first five
		);

		$dest['uspsUserId'] = $this->settings['usps_user_id'];

		try {
			$args   = array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => json_encode( $dest ),
			);
			$result = wp_remote_post( ITE_TAXCLOUD_API . 'VerifyAddress', $args );

			if ( is_wp_error( $result ) ) {
				return $result->get_error_message();
			} else if ( ! empty( $result['body'] ) ) {
				$body = json_decode( $result['body'] );
				if ( 0 == $body->ErrNumber ) {
					//set zip 4 with $body->Zip4
					$location['address1'] = $body->Address1;
					$location['city']     = $body->City;
					$location['state']    = $body->State;

					if ( $first_five !== $body->Zip5 ) {
						$location['zip'] = $body->Zip5;
					}

					$location['zip5']     = $body->Zip5;
					$location['zip4']     = $body->Zip4;
				} else if ( 97 == $body->ErrNumber ) {
					return true;
				} else {
					return sprintf( __( 'Unable to verify Address: %s', 'LION' ), $body->ErrDescription );
				}
			} else {
				return __( 'Unable to verify Address: Unknown Error', 'LION' );
			}
		}
		catch ( Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function can_validate() {
		return new ITE_Simple_Zone( array( 'country' => 'US' ) );
	}
}