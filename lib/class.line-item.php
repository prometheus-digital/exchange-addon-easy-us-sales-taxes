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

	/** @var int */
	private $rate;

	/**
	 * ITE_TaxCloud_Line_Item constructor.
	 *
	 * @param int                    $rate
	 * @param \ITE_Taxable_Line_Item $taxable
	 */
	public function __construct( $rate, \ITE_Taxable_Line_Item $taxable = null ) {
		$this->taxable = $taxable;
		$this->rate    = $rate;
		$this->id      = md5( uniqid() );
		$this->bag     = new ITE_Array_Parameter_Bag();
	}

	/**
	 * @inheritdoc
	 */
	public function get_rate() {
		return $this->rate;
	}

	/**
	 * @inheritdoc
	 */
	public function applies_to( ITE_Taxable_Line_Item $item ) {

		if ( $item->is_tax_exempt() ) {
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
	 * @inheritdoc
	 */
	public function create_scoped_for_taxable( ITE_Taxable_Line_Item $item ) {
		return new self( $this->rate, $item );
	}

	/**
	 * @inheritDoc
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return __( 'Taxes', 'LION' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		// TODO: Implement get_description() method.
	}

	/**
	 * @inheritDoc
	 */
	public function get_quantity() {
		return 1;
	}

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
	final public function get_type( $label = false ) {
		return $label ? __( 'Tax', 'it-l10n-ithemes-exchange' ) : 'tax';
	}

	/**
	 * @inheritDoc
	 */
	public function is_summary_only() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function persist( ITE_Line_Item_Repository $repository ) {
		$repository->save( $this );
	}

	/**
	 * @inheritDoc
	 */
	public function has_param( $param ) {
		return $this->bag->has_param( $param );
	}

	/**
	 * @inheritDoc
	 */
	public function get_param( $param ) {
		return $this->bag->get_param( $param );
	}

	/**
	 * @inheritDoc
	 */
	public function get_params() {
		return $this->bag->get_params();
	}

	/**
	 * @inheritDoc
	 */
	public function set_param( $param, $value, $deferred = false ) {
		return $this->bag->set_param( $param, $value, $deferred );
	}

	/**
	 * @inheritDoc
	 */
	public function remove_param( $param, $deferred = false ) {
		return $this->bag->remove_param( $param, $deferred );
	}

	/**
	 * @inheritDoc
	 */
	public function persist_deferred_params() {
		$this->bag->persist_deferred_params();
	}

	/**
	 * @inheritDoc
	 */
	public function set_aggregate( ITE_Aggregate_Line_Item $aggregate ) { $this->taxable = $aggregate; }

	/**
	 * @inheritDoc
	 */
	public function get_aggregate() { return $this->taxable; }

	/**
	 * @inheritDoc
	 */
	public function get_data_to_save( \ITE_Line_Item_Repository $repository = null ) {
		$data = array(
			'params' => $this->get_params(),
			'rate'   => $this->get_rate(),
		);

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public static function from_data( $id, array $data, ITE_Line_Item_Repository $repository ) {

		$item     = new self( $data['rate'] );
		$item->id = $id;

		foreach ( $data['params'] as $key => $value ) {
			$item->bag->set_param( $key, $value );
		}

		return $item;
	}
}