<?php
/**
 * Tax Cloud Line Item.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Class ITE_TaxCloud_Line_Item
 */
class ITE_TaxCloud_Line_Item extends ITE_Line_Item implements ITE_Tax_Line_Item {

	/** @var ITE_Taxable_Line_Item */
	private $taxable;

	/**
	 * Create a new TaxCloud Line Item.
	 *
	 * @since 2.0.0
	 *
	 * @param int                         $rate
	 * @param \ITE_Taxable_Line_Item|null $taxable
	 *
	 * @return \ITE_TaxCloud_Line_Item
	 */
	public static function create( $rate, ITE_Taxable_Line_Item $taxable = null ) {

		$bag = new ITE_Array_Parameter_Bag();
		$bag->set_param( 'rate', $rate );

		$self = new self( md5( uniqid( 'TAX', true ) ), $bag, new ITE_Array_Parameter_Bag() );

		if ( $taxable ) {
			$self->set_aggregate( $taxable );
		}

		return $self;
	}

	/**
	 * Generate the ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	protected static function generate_id() { return md5( uniqid( 'TAX', true ) ); }

	/**
	 * @inheritdoc
	 */
	public function clone_with_new_id( $include_frozen = true ) {
		return new static( self::generate_id(), $this->bag, $include_frozen ? $this->frozen : new ITE_Array_Parameter_Bag() );
	}

	/**
	 * @inheritdoc
	 */
	public function create_scoped_for_taxable( ITE_Taxable_Line_Item $item ) {
		return self::create( $this->get_rate(), $item );
	}

	public function get_provider() { return new ITE_TaxCloud_Tax_Provider(); }

	/**
	 * @inheritdoc
	 */
	public function get_rate() { return $this->get_param( 'rate' ); }

	/**
	 * @inheritdoc
	 */
	public function applies_to( ITE_Taxable_Line_Item $item ) {

		if ( $item->is_tax_exempt( $this->get_provider() ) ) {
			return false;
		}

		foreach ( $item->get_taxes() as $tax ) {
			if ( $tax instanceof self ) {
				return false; // Duplicate TaxCloud Taxes are not allowed.
			}
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function get_name() { return __( 'Taxes', 'LION' ); }

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		return $this->frozen->has_param( 'description' ) ? $this->frozen->get_param( 'description' ) : '';
	}

	/**
	 * @inheritDoc
	 */
	public function get_quantity() { return 1; }

	/**
	 * @inheritDoc
	 */
	public function get_amount() {
		if ( $this->get_aggregate() ) {
			return $this->get_aggregate()->get_taxable_amount() * $this->get_aggregate()->get_quantity() * ( $this->get_rate() / 100 );
		} else {
			return 0;
		}
	}

	/**
	 * @inheritDoc
	 */
	final public function get_type( $label = false ) { return $label ? __( 'Tax', 'it-l10n-ithemes-exchange' ) : 'tax'; }

	/**
	 * @inheritDoc
	 */
	public function is_summary_only() { return true; }

	/**
	 * @inheritDoc
	 */
	public function set_aggregate( ITE_Aggregate_Line_Item $aggregate ) { $this->taxable = $aggregate; }

	/**
	 * @inheritDoc
	 */
	public function get_aggregate() { return $this->taxable; }
}