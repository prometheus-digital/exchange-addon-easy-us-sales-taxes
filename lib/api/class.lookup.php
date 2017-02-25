<?php
/**
 * Tax Cloud API Lookup.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Class ITE_TaxCloud_API_Lookup
 */
class ITE_TaxCloud_API_Lookup {

	const URL = 'https://api.taxcloud.net/1.0/Taxcloud/';

	const TIC_SHIPPING = 11010;
	const TIC_FEE = 10010;

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
	 * @param array                  $certificate
	 *
	 * @return \ITE_Taxable_Line_Item|null Null if no taxes were applied.
	 *
	 * @throws \Exception
	 */
	public function for_line_item( ITE_Taxable_Line_Item $item, ITE_Cart $cart, array $certificate = array() ) {

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

		if ( $certificate ) {
			$additional['exemptCert'] = $certificate;
		}

		$response = $this->request( 'Lookup', $this->generate_body( $additional ) );

		/** @noinspection LoopWhichDoesNotLoopInspection */
		foreach ( $response['CartItemsResponse'] as $item_response ) {

			$item->remove_all_taxes();

			if ( empty( $item_response['TaxAmount'] ) ) {
				continue;
			}

			$tax = ITE_TaxCloud_Line_Item::create(
				100 * ( $item_response['TaxAmount'] / ( $item->get_taxable_amount() * $item->get_quantity() ) ), $item
			);

			$item->add_tax( $tax );
			$cart->get_repository()->save( $item );

			return $tax;
		}

		return null;
	}

	/**
	 * Get the taxes for all taxable line items in the cart.
	 *
	 * @since 2.0.0
	 *
	 * @param \ITE_Cart $cart
	 * @param array     $certificate
	 *
	 * @return \ITE_Line_Item_Collection Collection of taxes;
	 *
	 * @throws \Exception
	 */
	public function for_cart( ITE_Cart $cart, array $certificate = array() ) {

		$taxable = $cart->get_items( '', true )
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

		if ( $certificate ) {
			$additional['exemptCert'] = $certificate;
		}

		$response = $this->request( 'Lookup', $this->generate_body( $additional ) );
		$taxes    = array();
		$items    = array();

		// There is only one
		foreach ( $response['CartItemsResponse'] as $item_response ) {

			/** @var ITE_Taxable_Line_Item $item */
			$item = $taxable->offsetGet( $item_response['CartItemIndex'] );

			$taxable_total = $this->get_taxable_amount_for_item( $item ) * $item->get_quantity();
			$rate          = $item_response['TaxAmount'] / $taxable_total;
			$percentage    = $rate * 100;

			$tax = ITE_TaxCloud_Line_Item::create( $percentage, $item );

			$item->remove_all_taxes();

			if ( ! empty( $item_response['TaxAmount'] ) ) {
				$item->add_tax( $tax );
				$taxes[] = $tax;
			}

			$items[] = $item;
		}

		$cart->get_repository()->save_many( $items );

		return new ITE_Line_Item_Collection( $taxes, $cart->get_repository() );
	}

	/**
	 * Add transactions to Tax Cloud that have not been made through the Lookup -> AuthorizedWithCapture flow.
	 *
	 * @since 2.0.0
	 *
	 * @param IT_Exchange_Transaction[] $transactions A maximum of 25 transactions can be processed at once.
	 *
	 * @throws Exception
	 */
	public function add_transactions( array $transactions ) {

		if ( count( $transactions ) > 25 ) {
			throw new Exception( 'Unable to process more than 25 transactions at once.' );
		}

		$body = array(
			'apiLoginID'   => $this->settings['tax_cloud_api_id'],
			'apiKey'       => $this->settings['tax_cloud_api_key'],
			'transactions' => array_map( array( $this, 'generate_transaction' ), $transactions ),
		);

		$this->request( 'AddTransactions', $body );
	}

	/**
	 * Generate the body for an AddTransactions request for a single transaction.
	 *
	 * @since 2.0.0
	 *
	 * @param IT_Exchange_Transaction $transaction
	 *
	 * @return array
	 */
	protected function generate_transaction( IT_Exchange_Transaction $transaction ) {

		$cart    = $transaction->cart();
		$taxable = $cart->get_items()->flatten()->taxable()
		                ->filter( function ( ITE_Taxable_Line_Item $item ) {
			                return ! $item->is_tax_exempt( new ITE_TaxCloud_Tax_Provider() ) && $item->get_taxable_amount() > 0;
		                } );

		$data = array(
			'dateCaptured'      => $transaction->order_date->format( 'Y-m-d' ),
			'dateAuthorized'    => $transaction->order_date->format( 'Y-m-d' ),
			'dateTransaction'   => $transaction->order_date->format( 'Y-m-d' ),
			'deliveredBySeller' => false,
			'destination'       => $this->generate_destination( $cart ),
			'origin'            => $this->generate_origin(),
			'orderID'           => $transaction->get_ID(),
			'cartID'            => $transaction->cart_id,
			'customerID'        => $transaction->customer_id ?: uniqid( 'CID', false ),
			'cartItems'         => $this->generate_cart_items( $taxable->to_array() )
		);

		if ( $cart->has_meta( 'taxcloud_exempt_certificate' ) ) {
			$data['exemptCert'] = $cart->get_meta( 'taxcloud_exempt_certificate' );
		} elseif ( $transaction->parent && $transaction->parent->cart()->has_meta( 'taxcloud_exempt_certificate' ) ) {
			$data['exemptCert'] = $transaction->parent->cart()->get_meta( 'taxcloud_exempt_certificate' );
		}

		return $data;
	}

	/**
	 * Generate the data for the cart items property.
	 *
	 * @since 2.0.0
	 *
	 * @param ITE_Taxable_Line_Item[] $items
	 *
	 * @return array
	 */
	protected function generate_cart_items( array $items ) {

		$cart_items = array();
		$provider   = new ITE_TaxCloud_Tax_Provider();

		foreach ( $items as $i => $item ) {

			if ( $item instanceof ITE_Shipping_Line_Item ) {
				$tic = self::TIC_SHIPPING;
			} elseif ( $item instanceof ITE_Fee_Line_Item && ! $tic = $item->get_tax_code( $provider ) ) {
				$tic = self::TIC_FEE;
			} else {
				$tic = $item->get_tax_code( $provider );
			}

			$cart_items[] = array(
				'Index'  => $i,
				'TIC'    => $tic,
				'ItemID' => $item->get_id(),
				'Price'  => $this->get_taxable_amount_for_item( $item ),
				'Qty'    => $item->get_quantity()
			);
		}

		return $cart_items;
	}

	/**
	 * Get the total amount that is taxable.
	 *
	 * @since 2.0.0
	 *
	 * @param ITE_Taxable_Line_Item $item
	 *
	 * @return float|int
	 */
	protected function get_taxable_amount_for_item( ITE_Taxable_Line_Item $item ) {

		$amount = $item->get_taxable_amount();

		if ( $item instanceof ITE_Aggregate_Line_Item ) {
			$taxable = $item->get_line_items()->flatten()->taxable();

			$amount += $taxable->total() / $item->get_quantity();
		}

		return $amount;

	}

	/**
	 * Generate the body of the request.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @param string $type
	 * @param array  $request
	 *
	 * @return array
	 *
	 * @throws \Exception If the HTTP Request failed.
	 */
	protected function request( $type, array $request ) {

		$args     = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => json_encode( $request ),
		);
		$response = wp_remote_post( self::URL . $type, $args );

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