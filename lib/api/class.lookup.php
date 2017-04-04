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
class ITE_TaxCloud_API_Lookup extends ITE_TaxCloud_API_Request {

	/**
	 * Get the taxes for a given line item.
	 *
	 * @param \ITE_Taxable_Line_Item $item
	 * @param \ITE_Cart              $cart
	 * @param array                  $args
	 *
	 * @return \ITE_Taxable_Line_Item|null Null if no taxes were applied.
	 *
	 * @throws \Exception
	 */
	public function for_line_item( ITE_Taxable_Line_Item $item, ITE_Cart $cart, array $args = array() ) {

		if ( $item->is_tax_exempt( new ITE_TaxCloud_Tax_Provider() ) ) {
			return null;
		}

		$include_one_time_aggregatables = empty( $args['include_one_time_aggregatables'] ) ? false : true;
		$certificate                    = isset( $args['certificate'] ) ? $args['certificate'] : array();
		$save                           = isset( $args['save'] ) ? $args['save'] : true;

		if ( is_string( $certificate ) ) {
			$certificate = array( 'CertificateID' => $certificate );
		}

		$item->remove_all_taxes();

		$additional = array(
			'destination'       => $this->generate_destination( $cart ),
			'origin'            => $this->generate_origin(),
			'deliveredBySeller' => false,
			'customerID'        => $cart->get_customer() ? $cart->get_customer()->ID : uniqid( 'CID', false ),
			'cartID'            => $cart->get_id(),
			'cartItems'         => $this->generate_cart_items( array( $item ), array(), $include_one_time_aggregatables )
		);

		if ( count( $additional['destination'] ) === 0 ) {
			return null;
		}

		if ( $certificate ) {
			$additional['exemptCert'] = $certificate;
		}

		$response = $this->request( 'Lookup', $additional );

		/** @noinspection LoopWhichDoesNotLoopInspection */
		foreach ( $response['CartItemsResponse'] as $item_response ) {

			if ( empty( $item_response['TaxAmount'] ) ) {
				continue;
			}

			$taxable_total = $this->get_taxable_amount_for_item( $item, $include_one_time_aggregatables ) * $item->get_quantity();
			$rate          = $taxable_total ? $item_response['TaxAmount'] / $taxable_total : 0;
			$percentage    = $rate * 100;

			$tax = ITE_TaxCloud_Line_Item::create( $percentage, $item );
			$item->add_tax( $tax );

			if ( $save ) {
				$cart->get_repository()->save( $item );
			}

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
	 * @param array     $args
	 *
	 * @return \ITE_Line_Item_Collection Collection of taxes;
	 *
	 * @throws \Exception
	 */
	public function for_cart( ITE_Cart $cart, array $args = array() ) {
		return $this->for_line_items( $cart->get_items(), $cart, $args );
	}

	/**
	 * Calculate Tax Cloud taxes for a collection of line items.
	 *
	 * @since 2.0.0
	 *
	 * @param ITE_Line_Item_Collection $collection
	 * @param ITE_Cart                 $cart
	 * @param array                    $args
	 *
	 * @return ITE_Line_Item_Collection
	 */
	public function for_line_items( ITE_Line_Item_Collection $collection, ITE_Cart $cart, array $args = array() ) {

		$include_one_time_aggregatables = empty( $args['include_one_time_aggregatables'] ) ? false : true;
		$certificate                    = isset( $args['certificate'] ) ? $args['certificate'] : array();
		$save                           = isset( $args['save'] ) ? $args['save'] : true;

		if ( is_string( $certificate ) ) {
			$certificate = array( 'CertificateID' => $certificate );
		}

		$provider = new ITE_TaxCloud_Tax_Provider();
		$taxable  = $collection
			->taxable()
			->filter( function ( ITE_Taxable_Line_Item $item ) use ( $provider ) {
				return ! $item->is_tax_exempt( $provider );
			} );

		if ( $taxable->count() === 0 ) {
			return new ITE_Line_Item_Collection( array(), $cart->get_repository() );
		}

		/** @var ITE_Line_Item[] $negative_items */
		$negative_items = array();

		foreach ( $taxable as $item ) {
			$item->remove_all_taxes();

			if ( $this->get_taxable_amount_for_item( $item, $include_one_time_aggregatables ) < 0 ) {
				$negative_items[] = $item;
			}
		}

		foreach ( $negative_items as $item ) {
			$taxable->remove( $item->get_type(), $item->get_id() );
		}

		$body = array(
			'destination'       => $this->generate_destination( $cart ),
			'origin'            => $this->generate_origin(),
			'deliveredBySeller' => false,
			'customerID'        => $cart->get_customer() ? $cart->get_customer()->ID : uniqid( 'CID', false ),
			'cartID'            => $cart->get_id(),
			'cartItems'         => $this->generate_cart_items( $taxable->to_array(), $negative_items, $include_one_time_aggregatables )
		);

		if ( count( $body['destination'] ) === 0 ) {
			return new ITE_Line_Item_Collection( array(), $cart->get_repository() );
		}

		if ( $certificate ) {
			$body['exemptCert'] = $certificate;
		}

		$response = $this->request( 'Lookup', $body );
		$taxes    = array();
		$items    = array();

		// There is only one
		foreach ( $response['CartItemsResponse'] as $item_response ) {

			/** @var ITE_Taxable_Line_Item $item */
			$item = $taxable->offsetGet( $item_response['CartItemIndex'] );

			$taxable_total = $this->get_taxable_amount_for_item( $item, $include_one_time_aggregatables ) * $item->get_quantity();
			$rate          = $taxable_total ? $item_response['TaxAmount'] / $taxable_total : 0;
			$percentage    = $rate * 100;

			$tax = ITE_TaxCloud_Line_Item::create( $percentage, $item );

			if ( ! empty( $item_response['TaxAmount'] ) ) {
				$item->add_tax( $tax );
				$taxes[] = $tax;
			}

			$items[] = $item;
		}

		if ( $save ) {
			$cart->get_repository()->save_many( $items );
		}

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
		$taxable = $cart
			->get_items()
			->taxable()
			->filter( function ( ITE_Taxable_Line_Item $item ) {
				return ! $item->is_tax_exempt( new ITE_TaxCloud_Tax_Provider() );
			} );

		/** @var ITE_Line_Item[] $negative_items */
		$negative_items = array();

		foreach ( $taxable as $item ) {
			if ( $this->get_taxable_amount_for_item( $item, true ) < 0 ) {
				$negative_items[] = $item;
			}
		}

		foreach ( $negative_items as $item ) {
			$taxable->remove( $item->get_type(), $item->get_id() );
		}

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
			'cartItems'         => $this->generate_cart_items( $taxable->to_array(), $negative_items )
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
	 * @param array                   $negative_items Negative line items that need to have their cost distributed
	 *                                                across the other line items.
	 * @param bool                    $include_one_time_aggregatables
	 *
	 * @return array
	 */
	protected function generate_cart_items( array $items, array $negative_items = array(), $include_one_time_aggregatables = false ) {

		$cart_items = array();
		$provider   = new ITE_TaxCloud_Tax_Provider();

		$negative_total = 0.0;

		foreach ( $negative_items as $negative_item ) {
			$negative_total += $this->get_taxable_amount_for_item( $negative_item, $include_one_time_aggregatables );
		}

		foreach ( $items as $i => $item ) {

			$tic = $item->get_tax_code( $provider );

			$price = $this->get_taxable_amount_for_item( $item, $include_one_time_aggregatables );

			$cart_items[] = array(
				'Index'  => $i,
				'TIC'    => $tic,
				'ItemID' => $item->get_id(),
				'Price'  => $price,
				'Qty'    => $item->get_quantity()
			);
		}

		$cart_items = it_exchange_proportionally_distribute_cost( $negative_total, $cart_items, 'Price' );

		return $cart_items;
	}

	/**
	 * Get the total amount that is taxable.
	 *
	 * @since    2.0.0
	 *
	 * @param ITE_Taxable_Line_Item $item
	 * @param bool                  $include_one_time_aggregatables
	 *
	 * @return float
	 */
	protected function get_taxable_amount_for_item( ITE_Taxable_Line_Item $item, $include_one_time_aggregatables = false ) {

		$amount = $item->get_taxable_amount();

		if ( $item instanceof ITE_Aggregate_Line_Item ) {
			$provider = new ITE_TaxCloud_Tax_Provider();

			$taxable = $item->get_line_items()->flatten()->taxable()
			                ->filter( function ( ITE_Taxable_Line_Item $taxable ) use ( $provider ) {
				                return ! $taxable->is_tax_exempt( $provider );
			                } );

			if ( ! $include_one_time_aggregatables ) {
				$taxable = $taxable->filter( function ( ITE_Line_Item $item ) {
					return ! $item instanceof ITE_Fee_Line_Item || $item->is_recurring();
				} );
			}

			$amount += $taxable->total() / $item->get_quantity();
		}

		return (float) $amount;

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
}