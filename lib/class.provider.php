<?php
/**
 * TaxCloud Provider.
 *
 * @since   1.36
 * @license GPLv2
 */

class ITE_TaxCloud_Tax_Provider extends ITE_Tax_Provider {

	/**
	 * @inheritDoc
	 */
	public function get_tax_code_for_product( IT_Exchange_Product $product ) {
		return $product->get_feature( 'us-tic' );
	}

	/**
	 * @inheritDoc
	 */
	public function is_product_tax_exempt( IT_Exchange_Product $product ) {
		return false;
	}
}