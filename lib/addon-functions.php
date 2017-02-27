<?php
/**
 * iThemes Exchange Easy U.S. Sales Taxes Add-on
 * @package exchange-addon-easy-us-sales-taxes
 * @since 1.0.0
 */

/**
 * Gets tax information from transaction meta
 *
 * @since 1.0.0
 *
 * @param bool $format_price Whether or not to format the price or leave as a float
 * @return string The calculated tax from TaxCloud
 */

function it_exchange_easy_us_sales_taxes_addon_get_taxes_for_confirmation( $transaction=false, $format_price=true ) {
	$taxes = 0;
	if ( !empty( $transaction ) ) {
		$transaction = it_exchange_get_transaction( $transaction );
		$taxes = get_post_meta( $transaction->ID, '_it_exchange_easy_us_sales_taxes', true );
	} else if ( !empty( $GLOBALS['it_exchange']['transaction'] ) ) {
		$transaction = $GLOBALS['it_exchange']['transaction'];
		$taxes = get_post_meta( $transaction->ID, '_it_exchange_easy_us_sales_taxes', true );
	}

	return $format_price ? it_exchange_format_price( $taxes ) : $taxes;
}

/**
 * Gets tax information from TaxCloud based on products in cart
 *
 * @since 1.0.0
 *
 * @param bool $format_price Whether or not to format the price or leave as a float
 * @param bool $clear_cache Whether or not to force clear any cached tax values
 *
 * @return float|string The calculated tax from TaxCloud
 */
function it_exchange_easy_us_sales_taxes_addon_get_taxes_for_cart( $format_price=true, $clear_cache=false ) {

	// Grab the tax rate
	$cart  = it_exchange_get_current_cart();
	$taxes = $cart->get_items( 'tax', true )->with_only_instances_of( 'ITE_TaxCloud_Line_Item' );

	if ( $clear_cache || ( $taxes->count() === 0 && ! $cart->has_meta( 'taxcloud_exempt_certificate' ) ) ) {
		$lookup = new ITE_TaxCloud_API_Lookup( it_exchange_get_option( 'addon_easy_us_sales_taxes' ) );

		$cert = $cart->has_meta( 'taxcloud_exempt_certificate' ) ? $cart->get_meta( 'taxcloud_exempt_certificate' ) : array();

		try {
			$total = $lookup->for_cart( $cart, array( 'certificate' => $cert ) )->total();
		} catch ( Exception $e ) {
			it_exchange_add_message( 'error', $e->getMessage() );

			return $format_price ? it_exchange_format_price( 0 ) : 0.00;
		}
	} else {
		$total = $taxes->total();
	}

	$taxes = apply_filters_deprecated( 'it_exchange_easy_us_sales_taxes_addon_get_taxes_for_cart', array( $total ), '1.5' );

	return $format_price ? it_exchange_format_price( $taxes ) : $taxes;
}

/**
 * Helper function to output Tax Exempt link on Super Widget and Checkout page
 *
 * @since 1.0.0
 *
 * @param bool $echo Whether or not return or echo the output
 * @return mixed The HTML output
 */
function it_exchange_easy_us_sales_taxes_addon_exemptions( $echo=false ) {
	$output = '';
	$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes' );

	if ( is_user_logged_in() && !empty( $settings['tax_exemptions'] ) ) {
		$session = it_exchange_get_session_data( 'addon_easy_us_sales_taxes' );

		$output = '<div id="it-exchange-easy-us-sales-taxes-exempt-label" class="description">';
		$output .= '<a href="#" title="' . __( 'Manage Certificate Exemptions', 'LION' ) . '" id="it-exchange-easy-us-sales-tax-list-existing-certs">';

		if ( empty( $session['exempt_certificate'] ) ) {
			$output .= __( 'Tax Exempt?', 'LION' );
		} else {
			$output .= __( 'Tax Exempt', 'LION' );
		}

		$output .= '</a></div>';
	}

	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Helper function to output Add New Tax Exemption link on Super Widget and Checkout page
 *
 * @since 1.0.0
 *
 * @param bool $echo Whether or not return or echo the output
 * @return mixed The HTML output
 */
function it_exchange_easy_us_sales_taxes_addon_add_exemption( $echo=false ) {
	$output = '<a href="#" title="' . __( 'Manage Certificate Exemptions', 'LION' ) . '" id="it-exchange-easy-us-sales-tax-add-cert">' . __( 'Add a New Exemption', 'LION' ) . '</a>';

	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Helper function to convert returned state abbreviations from TaxCloud's API to readable string
 *
 * @since 1.0.0
 *
 * @param object $tax_cloud_states_object TaxCloud States object
 * @return string Comma separated list of States
 */
function it_exchange_easy_us_sales_taxes_addon_convert_exempt_states_to_string( $tax_cloud_states_object ) {
	$states = array();
	if ( !empty( $tax_cloud_states_object ) ) {

		foreach ( $tax_cloud_states_object as $state ) {
			$states[] = $state->StateAbbr;
		}

	}
	return implode( ',', $states );
}

/**
 * Helper function to convert returned creation date from TaxCloud's API to readable string
 *
 * @since 1.0.0
 *
 * @param string $gm_date TaxCloud Creation Date string
 * @return string WordPress formated date string
 */
function it_exchange_easy_us_sales_taxes_addon_convert_exempt_createdate_to_date_format( $gm_date ) {
	$format = empty( $format ) ? get_option( 'date_format' ) : $format;

	$date = apply_filters( 'it_exchange_easy_us_sales_taxes_addon_convert_exempt_createdate_to_date_format', date_i18n( $format, strtotime( $gm_date ) ), $gm_date, $format );

	return $date;
}
