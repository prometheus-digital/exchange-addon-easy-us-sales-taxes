<?php
/**
 * iThemes Exchange Advanced U.S. Taxes Add-on
 * @package exchange-addon-advanced-us-taxes
 * @since 1.0.0
*/

function it_exchange_advanced_us_taxes_addon_get_taxes_for_confirmation( $format_price=true ) {
	$taxes = 0;
	if ( !empty( $GLOBALS['it_exchange']['transaction'] ) ) {
		$transaction = $GLOBALS['it_exchange']['transaction'];
		$taxes = get_post_meta( $transaction->ID, '_it_exchange_advanced_us_taxes', true );
	}
	if ( $format_price )
		$taxes = it_exchange_format_price( $taxes );
	return $taxes;	
}

function it_exchange_advanced_us_taxes_addon_get_taxes_for_cart(  $format_price=true, $clear_cache=false ) {
	// Grab the tax rate
	$settings  = it_exchange_get_option( 'addon_advanced_us_taxes' );
	$taxes = 0;
	$cart = it_exchange_get_cart_data();
	$tax_cloud_session = it_exchange_get_session_data( 'addon_advanced_us_taxes' );
	
	$origin = array(
		'Address1' => $settings['business_address_1'],
		'City'     => $settings['business_city'],
		'State'    => $settings['business_state'],
		'Zip5'     => $settings['business_zip_5'],
		'Zip4'     => $settings['business_zip_4'],
	);
	if ( !empty( $settings['business_address_2'] ) )
		$origin['Address2'] = $settings['business_address_2'];
	
	if ( $settings['tax_shipping_address'] )
		$address = it_exchange_get_cart_shipping_address();
	
	//We at minimum need the Address1 and Zip
	if ( empty( $address['address1'] ) && empty( $address['zip'] ) ) 
		$address = it_exchange_get_cart_billing_address();
	
	if ( !empty( $address['address1'] ) && !empty( $address['zip'] ) ) {
		if ( !empty( $address['country'] ) && 'US' !== $address['country'] )
			return 0; //This is US taxes any other country and we don't need to calculate the tax
		
		$dest = array(
			'Address1' => $address['address1'],
			'Address2' => !empty( $address['address2'] ) ? $address['address2'] : '',
			'City'     => !empty( $address['city'] ) ? $address['city'] : '',
			'State'    => !empty( $address['state'] ) ? $address['state'] : '',
			'Zip5'     => substr( $address['zip'], 0, 5 ), // just get the first five
		);
		if ( !empty( $address['zip4'] ) )
			$dest['Zip4'] = $address['zip4'];
	} else {
		return 0;
	}
	
	$products = it_exchange_get_cart_products();
	$products_hash = md5( maybe_serialize( $products ) );
			
	// if we don't have a cache of the products_hash OR if the current cache doesn't match the current products hash
	if ( empty( $tax_cloud_session['products_hash'] )
		|| $tax_cloud_session['products_hash'] !== $products_hash 
		|| !empty( $tax_cloud_session['new_certificate'] ) ) {
	
		$product_count = it_exchange_get_cart_products_count( true );
		$applied_coupons = it_exchange_get_applied_coupons();
		$customer = it_exchange_get_current_customer();
			
		$cart_items = array();
		$i = 0;
		//build the TaxCloud Query
		foreach( $products as $product ) {
			$price = it_exchange_get_cart_product_base_price( $product, false );
			$product_tic = it_exchange_get_product_feature( $product['product_id'], 'us-tic', array( 'setting' => 'code' ) );
			if ( !empty( $applied_coupons ) ) {
				foreach( $applied_coupons as $type => $coupons ) {
					foreach( $coupons as $coupon ) {
						if ( 'cart' === $type ) {
							if ( '%' === $coupon['amount_type'] ) {
								$price *= ( $coupon['amount_number'] / 100 );
							} else {
								$price -= ( $coupon['amount_number'] / $product_count );
							}
						} else if ( 'product' === $type ) {
							if ( $coupon['product_id'] === $product['product_id'] ) {
								if ( '%' === $coupon['amount_type'] ) {
									$price *= ( $coupon['amount_number'] / 100 );
								} else {
									$price -= ( $coupon['amount_number'] / $product_count );
								}
							}
						}
					}
				}
			}
			
			$cart_items[] = array(
				'Index'  => $i,
				'TIC'    => $product_tic,
				'ItemID' => $product['product_id'],
				'Price'  => $price,
				'Qty'    => $product['count'],
			);
			$i++;
		}

		if ( !empty( $settings['tax_exemptions'] ) && !empty( $tax_cloud_session['exempt_certificate'] ) ) {		
			$exempt_cert = $tax_cloud_session['exempt_certificate'];
			$tax_cloud_session['new_certificate'] = false;
		} else {
			$exempt_cert = null;
		}

		$query = array(
			'apiLoginID'        => $settings['tax_cloud_api_id'],
			'apiKey'            => $settings['tax_cloud_api_key'],
			'customerID'        => $customer->ID,
			'cartID'            => '',
			'cartItems'         => $cart_items,
			'origin'            => $origin,
			'destination'       => $dest,
			'deliveredBySeller' => FALSE,
			'exemptCert'        => $exempt_cert,
		);
		
		try {
        	$args = array(
        		'headers' => array(
        			'Content-Type' => 'application/json',
        		),
				'body' => json_encode( $query ),
		    );
        	$result = wp_remote_post( ITE_TAXCLOUD_API . 'Lookup', $args );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			} else if ( !empty( $result['body'] ) ) {
				$body = json_decode( $result['body'] );
				if ( 0 != $body->ResponseType ) {
					$checkout_taxes = 0;
					foreach( $body->CartItemsResponse as $item ) {
						$checkout_taxes += $item->TaxAmount;
					}
					$taxes = apply_filters( 'it_exchange_advanced_us_taxes_addon_get_taxes_for_cart', $checkout_taxes );
					$tax_cloud_session['cart_id'] = $body->CartID; //we need this to authorize and capture the tax
					$tax_cloud_session['products_hash'] = $products_hash;
				} else {
					$errors = array();
					foreach( $body->Messages as $message ) {
						$errors[] = $message->Message;
					}
					throw new Exception( sprintf( __( 'Unable to calculate Tax: %s', 'LION' ), implode( ',', $errors ) ) );

				}
			} else {
				throw new Exception( __( 'Unable to verify calculate Tax: Unknown Error', 'LION' ) );
			}
        } 
        catch( Exception $e ) {
			$errors[] = $e->getMessage();
			$new_values['business_verified'] = false;
        }
	} else {
	
		$taxes = $tax_cloud_session['taxes'];
		
	}
				
	$tax_cloud_session['taxes'] = $taxes;
	it_exchange_update_session_data( 'addon_advanced_us_taxes', $tax_cloud_session );

	if ( $format_price )
		$taxes = it_exchange_format_price( $taxes );
	return $taxes;
}

function it_exchange_advanced_us_taxes_addon_exemptions( $echo=false ) {
	$output = '';
	$settings = it_exchange_get_option( 'addon_advanced_us_taxes' );
	
	if ( is_user_logged_in() && !empty( $settings['tax_exemptions'] ) )
		$output = '<div id="it-exchange-advanced-us-taxes-exempt-label" class="description"><a href="#" title="' . __( 'Manage Certificate Exemptions', 'LION' ) . '" id="it-exchange-advanced-us-tax-list-existing-certs">' . __( 'Tax Exempt?', 'LION' ) . '</a></div>';
	
	if ( $echo )
		echo $output;
	else
		return $output;
}

function it_exchange_advanced_us_taxes_addon_add_exemption( $echo=false ) {
	$output = '';
	
	if ( is_user_logged_in() )
		$output = '<a href="#" title="' . __( 'Manage Certificate Exemptions', 'LION' ) . '" id="it-exchange-advanced-us-tax-add-cert">' . __( 'Add a New Exemption', 'LION' ) . '</a>';
	
	
	if ( $echo )
		echo $output;
	else
		return $output;
}

function it_exchange_advanced_us_taxes_addon_convert_exempt_states_to_string( $tax_cloud_states_object ) {
	$states = array();
	if ( !empty( $tax_cloud_states_object ) ) {
		
		foreach ( $tax_cloud_states_object as $state ) {
			$states[] = $state->StateAbbr;
		}
		
	}
	return implode( ',', $states );
}

function it_exchange_advanced_us_taxes_addon_convert_exempt_createdate_to_date_format( $gm_date ) {
    $format = empty( $format ) ? get_option( 'date_format' ) : $format;

	$date = apply_filters( 'it_exchange_advanced_us_taxes_addon_convert_exempt_createdate_to_date_format', date_i18n( $format, strtotime( $gm_date ) ), $gm_date, $format );

	return $date;
}

function it_exchange_advanced_us_taxes_addon_convert_reason_to_readable_string( $reason_string, $reason_explained ) {
	$exemption_types = array(
		'FederalGovernmentDepartment' => __( 'Federal Government Department', 'LION' ),
		'StateOrLocalGovernmentName' => __( 'State or Local Government', 'LION' ),
		'TribalGovernmentName' => __( 'Tribal Government', 'LION' ),
		'ForeignDiplomat' => __( 'Foreign Diplomat', 'LION' ),
		'CharitableOrganization' => __( 'Charitable Organization', 'LION' ),
		'ReligiousOrEducationalOrganization' => __( 'Religious or Educational Organization', 'LION' ),
		'Resale' => __( 'Resale', 'LION' ),
		'AgriculturalProduction' => __( 'Agricultural Production', 'LION' ),
		'IndustrialProductionOrManufacturing' => __( 'Industrial Production or Manufacturing', 'LION' ),
		'DirectPayPermit' => __( 'Direct Pay Permit', 'LION' ),
		'DirectMail' => __( 'Direct Mail', 'LION' ),
		'Other' => __( 'Other', 'LION' ),
	);
	
	if ( 'Other' === $reason_string )
		return $reason_explained;
	else
		return !empty( $exemption_types[$reason_string] ) ? $exemption_types[$reason_string] : $reason_string;
}
