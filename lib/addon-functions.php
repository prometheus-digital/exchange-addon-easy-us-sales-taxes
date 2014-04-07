<?php
/**
 * iThemes Exchange Advanced U.S. Taxes Add-on
 * @package exchange-addon-advanced-us-taxes
 * @since 1.0.0
*/

function it_exchange_advanced_us_taxes_addon_get_taxes_for_checkout(  $format_price=true, $clear_cache=false ) {
	// Grab the tax rate
	$settings  = it_exchange_get_option( 'addon_advanced_us_taxes' );
	
	$cart = it_exchange_get_cart_data();
	
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
	$product_count = it_exchange_get_cart_products_count( true );
	$applied_coupons = it_exchange_get_applied_coupons();
	$customer = it_exchange_get_current_customer();
		
	$tax_cloud_query = array();
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
	
	$excempt_cert = null;
	
	// If we don't have a cached query, we need to create one or
	// If the exist query has changed, we need to renew it.
	if ( empty( $GLOBALS['it_exchange']['tax_cloud_query'] )
		|| ( $GLOBALS['it_exchange']['tax_cloud_query'] !== $tax_cloud_query ) ) {

		$query = array(
			'apiLoginID'        => $settings['tax_cloud_api_id'],
			'apiKey'            => $settings['tax_cloud_api_key'],
			'customerID'        => $customer->ID,
			'cartID'            => it_exchange_get_session_id(),
			'cartItems'         => $cart_items,
			'origin'            => $origin,
			'destination'       => $dest,
			'deliveredBySeller' => FALSE,
			'exemptCert'        => $excempt_cert,
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
					$taxes = apply_filters( 'it_exchange_advanced_us_taxes_addon_get_taxes_for_checkout', $checkout_taxes );
					if ( $format_price )
						$taxes = it_exchange_format_price( $taxes );
					return $taxes;
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
	}
	
	return 0;
}