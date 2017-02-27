<?php
/**
 * TaxCloud Authorize API request.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Class ITE_TaxCloud_API_Authorize
 */
class ITE_TaxCloud_API_Authorize extends ITE_TaxCloud_API_Request {

	/**
	 * Perform an AuthorizeWithCapture request.
	 *
	 * @since 2.0.0
	 *
	 * @param IT_Exchange_Transaction $transaction
	 * @param string                  $tax_cloud_cart_id
	 *
	 * @throws Exception
	 */
	public function authorize_with_capture( IT_Exchange_Transaction $transaction, $tax_cloud_cart_id = '' ) {

		$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes' );

		$query = array(
			'apiLoginID'     => $settings['tax_cloud_api_id'],
			'apiKey'         => $settings['tax_cloud_api_key'],
			'customerID'     => $transaction->customer_id ?: uniqid( 'CID', false ),
			'cartID'         => $tax_cloud_cart_id ?: $transaction->cart_id,
			'orderID'        => $transaction->ID,
			'dateAuthorized' => gmdate( DATE_ATOM ),
			'dateCaptured'   => gmdate( DATE_ATOM )
		);

		$this->request( 'AuthorizedWithCapture', $query );

		$total = $transaction->get_items( 'tax', true )->with_only_instances_of( 'ITE_TaxCloud_Line_Item' )->total();

		if ( $total || $transaction->cart()->has_meta( 'taxcloud_exempt_certificate' ) ) {
			update_post_meta( $transaction->get_ID(), '_it_exchange_easy_us_sales_taxes', $total );
		}
	}
}