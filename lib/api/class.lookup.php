<?php
/**
 * Tax Cloud API Lookup.
 *
 * @since   1.5.0
 * @license GPLv2
 */

/**
 * Class ITE_TaxCloud_API_Lookup
 */
class ITE_TaxCloud_API_Lookup {

	const URL = 'https://api.taxcloud.net/1.0/Taxcloud/';

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * ITE_TaxCloud_API_Lookup constructor.
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings ) { $this->settings = $settings; }

	/**
	 * Get the taxes for a given line item.
	 *
	 * @param \ITE_Taxable_Line_Item $item
	 * @param \ITE_Cart              $cart
	 * @param array                  $cert
	 *
	 * @return \ITE_Taxable_Line_Item|null Null if no taxes were applied.
	 *
	 * @throws \Exception
	 */
	public function for_line_item( ITE_Taxable_Line_Item $item, ITE_Cart $cart, $cert = array() ) {

		if ( $item->is_tax_exempt( new ITE_TaxCloud_Tax_Provider() ) ) {
			return null;
		}

		$additional = array(
			'destination' => $this->generate_destination( $cart ),
			'customerID'  => $cart->get_customer() ? $cart->get_customer()->ID : uniqid( 'CID', false ),
			'cartID'      => $cart->get_id(),
			'cartItems'   => $this->generate_cart_items( array( $item ) )
		);

		if ( count( $additional['destination'] ) === 0 ) {
			return null;
		}

		if ( $cert ) {
			$additional['exemptCert'] = $cert;
		}

		$response = $this->request( $this->generate_body( $additional ) );

		/** @noinspection LoopWhichDoesNotLoopInspection */
		foreach ( $response['CartItemsResponse'] as $item_response ) {
			$item->remove_all_taxes();

			$tax = ITE_TaxCloud_Line_Item::create(
				100 * ( $item_response['TaxAmount'] / ( $item->get_taxable_amount() * $item->get_quantity() ) ), $item
			);

			if ( ! empty( $cert['CertificateID'] ) ) {
				$tax->set_param( 'exemption', $cert['CertificateID'] );
			}

			$item->add_tax( $tax );
			$cart->get_repository()->save( $item );

			return $tax;
		}

		return null;
	}

	/**
	 * Get the taxes for all taxable line items in the cart.
	 *
	 * @since 1.5.0
	 *
	 * @param \ITE_Cart $cart
	 * @param array     $cert
	 *
	 * @return \ITE_Line_Item_Collection Collection of taxes;
	 *
	 * @throws \Exception
	 */
	public function for_cart( ITE_Cart $cart, $cert = array() ) {

		$taxable = $cart->get_items( 'product', true )
		                ->taxable()
		                ->filter( function ( ITE_Taxable_Line_Item $item ) {
			                return ! $item->is_tax_exempt( new ITE_TaxCloud_Tax_Provider() ) && $item->get_taxable_amount() > 0;
		                } );

		if ( $taxable->count() === 0 ) {
			return new ITE_Line_Item_Collection( array(), $cart->get_repository() );
		}

		$additional = array(
			'destination' => $this->generate_destination( $cart ),
			'customerID'  => $cart->get_customer() ? $cart->get_customer()->ID : uniqid( 'CID', false ),
			'cartID'      => $cart->get_id(),
			'cartItems'   => $this->generate_cart_items( $taxable->to_array() )
		);

		if ( count( $additional['destination'] ) === 0 ) {
			return new ITE_Line_Item_Collection( array(), $cart->get_repository() );
		}

		if ( $cert ) {
			$additional['exemptCert'] = $cert;
		}

		$response = $this->request( $this->generate_body( $additional ) );
		$taxes    = array();
		$items    = array();

		// There is only one
		foreach ( $response['CartItemsResponse'] as $item_response ) {
			/** @var ITE_Taxable_Line_Item $item */
			$item = $taxable->offsetGet( $item_response['CartItemIndex'] );
			$tax  = ITE_TaxCloud_Line_Item::create(
				100 * ( $item_response['TaxAmount'] / ( $item->get_taxable_amount() * $item->get_quantity() ) ), $item
			);

			if ( ! empty( $cert['CertificateID'] ) ) {
				$tax->set_param( 'exemption', $cert['CertificateID'] );
			}

			$item->remove_all_taxes();
			$item->add_tax( $tax );

			$taxes[] = $tax;
			$items[] = $item;
		}

		$cart->get_repository()->save_many( $items );

		return new ITE_Line_Item_Collection( $taxes, $cart->get_repository() );
	}

	/**
	 * Generate the data for the cart items property.
	 *
	 * @since 1.5.0
	 *
	 * @param ITE_Taxable_Line_Item[] $items
	 *
	 * @return array
	 */
	protected function generate_cart_items( array $items ) {

		$cart_items = array();

		foreach ( $items as $i => $item ) {
			$cart_items[] = array(
				'Index'  => $i,
				'TIC'    => $item instanceof ITE_Shipping_Line_Item ? 11010 : $item->get_tax_code( new ITE_TaxCloud_Tax_Provider() ),
				'ItemID' => $item->get_id(),
				'Price'  => $item->get_taxable_amount(),
				'Qty'    => $item->get_quantity()
			);
		}

		return $cart_items;
	}

	/**
	 * Generate the body of the request.
	 *
	 * @since 1.5.0
	 *
	 * @param array $additional
	 *
	 * @return array
	 */
	protected function generate_body( array $additional ) {
		return array_merge( $additional, array(
			'apiLoginID'        => $this->settings['tax_cloud_api_id'],
			'apiKey'            => $this->settings['tax_cloud_api_key'],
			'origin'            => $this->generate_origin(),
			'deliveredBySeller' => false,
		) );
	}

	/**
	 * Generate the shipping origin address.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	protected function generate_origin() {

		$origin = array(
			'Address1' => $this->settings['business_address_1'],
			'City'     => $this->settings['business_city'],
			'State'    => $this->settings['business_state'],
			'Zip5'     => $this->settings['business_zip_5'],
			'Zip4'     => $this->settings['business_zip_4'],
		);

		if ( ! empty( $this->settings['business_address_2'] ) ) {
			$origin['Address2'] = $this->settings['business_address_2'];
		}

		return $origin;
	}

	/**
	 * Generate the destination for a cart.
	 *
	 * @since 1.5.0
	 *
	 * @param \ITE_Cart $cart
	 *
	 * @return array
	 */
	protected function generate_destination( ITE_Cart $cart ) {

		$address = $cart->get_shipping_address();

		if ( empty( $address['address1'] ) || empty( $address['zip'] ) ) {
			$address = $cart->get_billing_address();
		}

		if ( $address['country'] !== 'US' ) {
			return array();
		}

		$destination = array(
			'Address1' => $address['address1'],
			'Address2' => ! empty( $address['address2'] ) ? $address['address2'] : '',
			'City'     => ! empty( $address['city'] ) ? $address['city'] : '',
			'State'    => ! empty( $address['state'] ) ? $address['state'] : '',
			'Zip5'     => substr( $address['zip'], 0, 5 ), // just get the first five
		);

		if ( ! empty( $address['zip4'] ) ) {
			$destination['Zip4'] = $address['zip4'];
		}

		return $destination;
	}

	/**
	 * Perform a request to the TaxCloud API Servers.
	 *
	 * @param array $request
	 *
	 * @return array
	 *
	 * @throws \Exception If the HTTP Request failed.
	 */
	protected function request( array $request ) {

		$args     = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => json_encode( $request ),
		);
		$response = wp_remote_post( self::URL . 'Lookup', $args );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body ) {
			throw new Exception( __( 'Unable to verify calculate Tax: Unknown Error', 'LION' ) );
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

			throw new Exception( sprintf( __( 'Unable to calculate Tax: %s', 'LION' ), implode( ',', $errors ) ) );
		}

		return $body;
	}
}