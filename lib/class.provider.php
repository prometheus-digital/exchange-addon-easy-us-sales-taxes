<?php

/**
 * TaxCloud Provider.
 *
 * @since   1.36
 * @license GPLv2
 */
class ITE_TaxCloud_Tax_Provider extends ITE_Tax_Provider {

	const TIC_SHIPPING = 11010;
	const TIC_FEE = 10010;

	/**
	 * @var \ITE_TaxCloud_API_Lookup
	 */
	protected $lookup;

	/**
	 * ITE_TaxCloud_Tax_Provider constructor.
	 */
	public function __construct() {
		$this->lookup = new ITE_TaxCloud_API_Lookup( it_exchange_get_option( 'addon_easy_us_sales_taxes' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function get_tax_code_for_product( IT_Exchange_Product $product ) {
		return $product->get_feature( 'us-tic' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_tax_code_for_item( ITE_Line_Item $item ) {

		if ( $item instanceof ITE_Cart_Product ) {
			return $this->get_tax_code_for_product( $item->get_product() );
		}

		if ( $item instanceof ITE_Shipping_Line_Item ) {
			return self::TIC_SHIPPING;
		}

		if ( $item instanceof ITE_Fee_Line_Item ) {
			return self::TIC_FEE;
		}

		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function is_product_tax_exempt( IT_Exchange_Product $product ) {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_class() {
		return 'ITE_TaxCloud_Line_Item';
	}

	/**
	 * @inheritDoc
	 */
	public function add_taxes_to( ITE_Taxable_Line_Item $item, ITE_Cart $cart ) {

		$cert = $cart->has_meta( 'taxcloud_exempt_certificate' ) ? $cart->get_meta( 'taxcloud_exempt_certificate' ) : array();

		try {
			$this->lookup->for_line_item( $item, $cart, array(
				'certificate' => $cert,
			) );
		} catch ( Exception $e ) {
			$cart->get_feedback()->add_error( $e->getMessage() );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function is_restricted_to_location() {
		return new ITE_Simple_Zone( array(
			'country'  => 'US',
			'state'    => ITE_Zone::WILD,
			'city'     => ITE_Zone::WILD,
			'zip'      => ITE_Zone::WILD,
			'address1' => ITE_Zone::WILD,
			'address2' => ITE_Zone::WILD
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function finalize_taxes( ITE_Cart $cart ) {

		$cert = $cart->has_meta( 'taxcloud_exempt_certificate' ) ? $cart->get_meta( 'taxcloud_exempt_certificate' ) : array();

		try {
			$this->lookup->for_cart( $cart, array( 'certificate' => $cert ) );
		} catch ( Exception $e ) {
			$cart->get_feedback()->add_error( $e->getMessage() );
		}
	}
}