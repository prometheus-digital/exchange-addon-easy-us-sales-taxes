<?php
/**
 * Tax Cloud Line Item.
 *
 * @since   1.5.0
 * @license GPLv2
 */

/**
 * Class ITE_TaxCloud_Line_Item
 */
class ITE_TaxCloud_Line_Item implements ITE_Tax_Line_Item {

	/** @var ITE_Parameter_Bag */
	private $bag;

	/** @var ITE_Taxable_Line_Item */
	private $taxable;

	/** @var string */
	private $id;

	/** @var ITE_Parameter_bag */
	private $frozen;

	/**
	 * ITE_TaxCloud_Line_Item constructor.
	 *
	 * @param string             $id
	 * @param \ITE_Parameter_Bag $bag
	 * @param \ITE_Parameter_Bag $frozen
	 */
	public function __construct( $id, ITE_Parameter_Bag $bag, ITE_Parameter_Bag $frozen ) {
		$this->id     = $id;
		$this->bag    = $bag;
		$this->frozen = $frozen;
	}

	/**
	 * Create a new TaxCloud Line Item.
	 *
	 * @since 1.36.0
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
	public function get_id() { return $this->id; }

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
			return $this->get_aggregate()->get_taxable_amount() * ( $this->get_rate() / 100 );
		} else {
			return 0;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_total() {
		return $this->get_amount() * $this->get_quantity();
	}

	/**
	 * @inheritDoc
	 */
	final public function get_type( $label = false ) {
		return $label ? __( 'Tax', 'it-l10n-ithemes-exchange' ) : 'tax';
	}

	/**
	 * @inheritDoc
	 */
	public function is_summary_only() { return true; }

	/**
	 * @inheritDoc
	 */
	public function persist( ITE_Line_Item_Repository $repository ) { $repository->save( $this ); }

	/**
	 * @inheritDoc
	 */
	public function has_param( $param ) { return $this->bag->has_param( $param ); }

	/**
	 * @inheritDoc
	 */
	public function get_param( $param ) { return $this->bag->get_param( $param ); }

	/**
	 * @inheritDoc
	 */
	public function get_params() { return $this->bag->get_params(); }

	/**
	 * @inheritDoc
	 */
	public function set_param( $param, $value ) {
		return $this->bag->set_param( $param, $value );
	}

	/**
	 * @inheritDoc
	 */
	public function remove_param( $param ) {
		return $this->bag->remove_param( $param );
	}

	/**
	 * @inheritDoc
	 */
	public function set_aggregate( ITE_Aggregate_Line_Item $aggregate ) { $this->taxable = $aggregate; }

	/**
	 * @inheritDoc
	 */
	public function get_aggregate() { return $this->taxable; }
}