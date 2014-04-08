<?php
/**
 * iThemes Exchange Advanced U.S. Taxes Add-on
 * @package exchange-addon-advanced-us-taxes
 * @since 1.0.0
*/

/**
 * Exchange Transaction Add-ons require several hooks in order to work properly. 
 * Most of these hooks are called in api/transactions.php and are named dynamically
 * so that individual add-ons can target them. eg: it_exchange_refund_url_for_stripe
 * We've placed them all in one file to help add-on devs identify them more easily
*/

//For calculation shipping, we need to require billing addresses... incase a product doesn't have a shipping address and the shipping add-on is not enabled
apply_filters( 'it_exchange_billing_address_purchase_requirement_enabled', '__return_true' );

/**
 * Enqueues Advanced U.S. Taxes scripts to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix WordPress passed variable
 * @return void
*/
function it_exchange_advanced_us_taxes_addon_admin_wp_enqueue_scripts( $hook_suffix ) {
	global $post;
		
	if ( isset( $_REQUEST['post_type'] ) ) {
		$post_type = $_REQUEST['post_type'];
	} else {
		if ( isset( $_REQUEST['post'] ) )
			$post_id = (int) $_REQUEST['post'];
		elseif ( isset( $_REQUEST['post_ID'] ) )
			$post_id = (int) $_REQUEST['post_ID'];
		else
			$post_id = 0;

		if ( $post_id )
			$post = get_post( $post_id );

		if ( isset( $post ) && !empty( $post ) )
			$post_type = $post->post_type;
	}
	
	if ( ( isset( $post_type ) && 'it_exchange_prod' === $post_type )
		|| ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'advanced-us-taxes' === $_GET['add-on-settings'] ) ) {
		
		$deps = array( 'jquery' );
		//wp_enqueue_script( 'it-exchange-advanced-us-taxes-admin-js', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/admin.js', $deps );
		
		//$deps = array( 'jquery', 'it-exchange-advanced-us-taxes-admin-js' );
		wp_enqueue_script( 'it-exchange-advanced-us-taxes-addon-taxcloud-tic-selector', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/jquery.tic2.public.js', $deps, '', true );
		
	}
}
add_action( 'admin_enqueue_scripts', 'it_exchange_advanced_us_taxes_addon_admin_wp_enqueue_scripts' );

/**
 * Enqueues Advanced U.S. Taxes styles to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_advanced_us_taxes_addon_admin_wp_enqueue_styles() {
	global $post, $hook_suffix;

	if ( isset( $_REQUEST['post_type'] ) ) {
		$post_type = $_REQUEST['post_type'];
	} else {
		if ( isset( $_REQUEST['post'] ) ) {
			$post_id = (int) $_REQUEST['post'];
		} else if ( isset( $_REQUEST['post_ID'] ) ) {
			$post_id = (int) $_REQUEST['post_ID'];
		} else {
			$post_id = 0;
		}

		if ( $post_id )
			$post = get_post( $post_id );

		if ( isset( $post ) && !empty( $post ) )
			$post_type = $post->post_type;
	}
	
	// Advanced US Taxes settings page
	if ( ( isset( $post_type ) && 'it_exchange_prod' === $post_type )
		|| ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'advanced-us-taxes' === $_GET['add-on-settings'] ) ) {
		
		wp_enqueue_style( 'it-exchange-advanced-us-taxes-addon-admin-style', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product.css' );
		
	}

}
add_action( 'admin_print_styles', 'it_exchange_advanced_us_taxes_addon_admin_wp_enqueue_styles' );

/**
 * Add Advanced U.S. Taxes to the content-cart totals and content-checkout loop
 *
 * @since 1.0.0
 *
 * @param array $elements list of existing elements
 * @return array
*/
function it_exchange_advanced_us_taxes_addon_add_taxes_to_template_totals_elements( $elements ) {
	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'totals-savings', $elements );
	if ( false === $index )
		$index = -1;
		
	// Bump index by 1 to show tax after discounts
	if ( -1 != $index )
		$index++;

	array_splice( $elements, $index, 0, 'advanced-us-taxes' );
	return $elements;
}
add_filter( 'it_exchange_get_content_cart_totals_elements', 'it_exchange_advanced_us_taxes_addon_add_taxes_to_template_totals_elements' );
add_filter( 'it_exchange_get_content_checkout_totals_elements', 'it_exchange_advanced_us_taxes_addon_add_taxes_to_template_totals_elements' );
add_filter( 'it_exchange_get_content_confirmation_transaction_summary_elements', 'it_exchange_advanced_us_taxes_addon_add_taxes_to_template_totals_elements' );

/**
 * Add Advanced U.S. Taxes to the super-widget-checkout totals loop
 *
 * @since 1.0.0
 *
 * @param array $loops list of existing elements
 * @return array
*/
function it_exchange_advanced_us_taxes_addon_add_taxes_to_sw_template_totals_loops( $loops ) {
	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'discounts', $loops );
	if ( false === $index )
		$index = -1;
		
	// Bump index by 1 to show tax after discounts
	if ( -1 != $index )
		$index++;

	array_splice( $loops, $index, 0, 'advanced-us-taxes' );
	return $loops;
}
add_filter( 'it_exchange_get_super-widget-checkout_after-cart-items_loops', 'it_exchange_advanced_us_taxes_addon_add_taxes_to_sw_template_totals_loops' );

/**
 * Adds our templates directory to the list of directories
 * searched by Exchange
 *
 * @since 1.0.0
 *
 * @param array $template_path existing array of paths Exchange will look in for templates
 * @param array $template_names existing array of file names Exchange is looking for in $template_paths directories
 * @return array
*/
function it_exchange_advanced_us_taxes_addon_taxes_register_templates( $template_paths, $template_names ) {
	// Bail if not looking for one of our templates
	$add_path = false;
	$templates = array(
		'content-cart/elements/advanced-us-taxes.php',
		'content-checkout/elements/advanced-us-taxes.php',
		'content-confirmation/elements/advanced-us-taxes.php',
		'super-widget-checkout/loops/advanced-us-taxes.php',
	);
	foreach( $templates as $template ) {
		if ( in_array( $template, (array) $template_names ) )
			$add_path = true;
	}
	if ( ! $add_path )
		return $template_paths;

	$template_paths[] = dirname( __FILE__ ) . '/templates';
	return $template_paths;
}
add_filter( 'it_exchange_possible_template_paths', 'it_exchange_advanced_us_taxes_addon_taxes_register_templates', 10, 2 );

/**
 * Adjusts the cart total
 *
 * @since 1.0.0
 *
 * @param $total the total passed to us by Exchange.
 * @return
*/
function it_exchange_advanced_us_taxes_addon_taxes_modify_total( $total ) {
	$total += it_exchange_advanced_us_taxes_addon_get_taxes_for_cart( false );
	return $total;
}
add_filter( 'it_exchange_get_cart_total', 'it_exchange_advanced_us_taxes_addon_taxes_modify_total' );

function it_exchange_advanced_us_taxes_verify_customer_address( $address, $customer_id ) {
	if ( !empty( $address['country'] ) && 'US' !== $address['country'] )
		return $address; //Can only verify US addresses
	
	$settings = it_exchange_get_option( 'addon_advanced_us_taxes' );
	
	$dest = array(
		'Address1' => $address['address1'],
		'Address2' => !empty( $address['address2'] ) ? $address['address2'] : '',
		'City'     => !empty( $address['city'] ) ? $address['city'] : '',
		'State'    => !empty( $address['state'] ) ? $address['state'] : '',
		'Zip5'     => absint( substr( $address['zip'], 0, 5 ) ), // just get the first five
	);
    $dest['uspsUserId'] = $settings['usps_user_id'];

	try {
    	$args = array(
    		'headers' => array(
    			'Content-Type' => 'application/json',
    		),
			'body' => json_encode( $dest ),
	    );
    	$result = wp_remote_post( ITE_TAXCLOUD_API . 'VerifyAddress', $args );
		if ( is_wp_error( $result ) ) {
			throw new Exception( $result->get_error_message() );
		} else if ( !empty( $result['body'] ) ) {
			$body = json_decode( $result['body'] );
			if ( 0 == $body->ErrNumber ) {
				//set zip 4 with $body->Zip4
				$address['zip4'] = $body->Zip4;
			} else {
				throw new Exception( sprintf( __( 'Unable to verify Address: %s', 'LION' ), $body->ErrDescription ) );
			}
		} else {
			throw new Exception( __( 'Unable to verify Address: Unknown Error', 'LION' ) );
		}
    } 
    catch( Exception $e ) {
		it_exchange_add_message( 'error', sprintf( __( 'Address Error: %s', 'LION' ), $e->getMessage() ) );
		return false;
    }
    
    return $address;
}
add_filter( 'it_exchange_save_customer_billing_address', 'it_exchange_advanced_us_taxes_verify_customer_address', 10, 2 );
add_filter( 'it_exchange_save_customer_shipping_address', 'it_exchange_advanced_us_taxes_verify_customer_address', 10, 2 );


function it_exchange_advanced_us_taxes_transaction_hook( $transaction_id ) {
	$settings = it_exchange_get_option( 'addon_advanced_us_taxes' );
	$customer = it_exchange_get_current_customer();
	$tax_cloud_session = it_exchange_get_session_data( 'addon_advanced_us_taxes' );
	
	//If we don't have a tax cloud Cart ID, we cannot authorize and capture the tax
	if ( !empty( $tax_cloud_session['cart_id'] ) ) {
		$query = array(
			'apiLoginID'     => $settings['tax_cloud_api_id'],
			'apiKey'         => $settings['tax_cloud_api_key'],
			'customerID'     => $customer->ID,
			'cartID'         => $tax_cloud_session['cart_id'],
			'orderID'        => $transaction_id,
			'dateAuthorized' => gmdate( DATE_ATOM ),
			'dateCaptured'   => gmdate( DATE_ATOM )
		);
		
		try {
			$args = array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body' => json_encode( $query ),
		    );
			$result = wp_remote_post( ITE_TAXCLOUD_API . 'AuthorizedWithCapture', $args );
		
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			} else if ( !empty( $result['body'] ) ) {
				$body = json_decode( $result['body'] );
				if ( 0 != $body->ResponseType ) {
					update_post_meta( $transaction_id, '_it_exchange_advanced_us_taxes', $tax_cloud_session['taxes'] );
				} else {
					$errors = array();
					foreach( $body->Messages as $message ) {
						$errors[] = $message->ResponseType . ' ' . $message->Message;
					}
					throw new Exception( implode( ',', $errors ) );
				}
			} else {
				throw new Exception( __( 'Unknown Error', 'LION' ) );
			}
		}
	    catch( Exception $e ) {
			$error = sprintf( __( 'Unable to authorize transaction with TaxCloud.net: %s', 'LION' ), $e->getMessage() );
			wp_mail( 'lew@ithemes.com', __( 'Error with Advanced U.S. Taxes', 'LION' ), $error );
	    }
	}
	
	it_exchange_clear_session_data( 'addon_advanced_us_taxes' );
	return;
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_advanced_us_taxes_transaction_hook' );