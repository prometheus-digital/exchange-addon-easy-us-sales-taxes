<?php
/*
 * Plugin Name: iThemes Exchange - Advanced U.S. Taxes
 * Version: 1.0.2
 * Description: Adds Advanced U.S. Taxes to iThemes Exchange with the power of TaxCloud.net
 * Plugin URI: http://ithemes.com/exchange/advanced-us-taxes/
 * Author: iThemes
 * Author URI: http://ithemes.com
 * iThemes Package: exchange-addon-advanced-use-taxes
 
 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 *
*/

/**
 * This registers our plugin as a customer pricing addon
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_register_advanced_us_taxes_addon() {
	$options = array(
		'name'              => __( 'Advanced U.S. Taxes', 'LION' ),
		'description'       => __( 'With the power of TaxCloud.net, store owners can now charge the proper tax for product classes.', 'LION' ),
		'author'            => 'iThemes',
		'author_url'        => 'http://ithemes.com/exchange/advanced-us-taxes/',
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/taxes50px.png' ),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'category'          => 'taxes',
		'basename'          => plugin_basename( __FILE__ ),
		'labels'      => array(
			'singular_name' => __( 'Advanced U.S. Taxes', 'LION' ),
		),
		'settings-callback' => 'it_exchange_advanced_us_taxes_settings_callback',
	);
	it_exchange_register_addon( 'advanced-us-taxes', $options );
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_advanced_us_taxes_addon' );

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
*/
function it_exchange_advanced_us_taxes_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
//add_action( 'plugins_loaded', 'it_exchange_advanced_us_taxes_set_textdomain' );

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
function ithemes_exchange_addon_advanced_us_taxes_updater_register( $updater ) { 
	$updater->register( 'exchange-addon-advanced-us-taxes', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_advanced_us_taxes_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );

function it_exchange_advanced_us_taxes_transaction_hook( $transaction_id ) {
	$settings  = it_exchange_get_option( 'addon_advanced_us_taxes' );
	$customer = it_exchange_get_current_customer();
			
	$query = array(
		'apiLoginID'     => $settings['tax_cloud_api_id'],
		'apiKey'         => $settings['tax_cloud_api_key'],
		'customerID'     => $customer->ID,
		'cartID'         => it_exchange_get_session_id(),
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
				return;
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
add_action( 'it_exchange_add_transaction_success', 'it_exchange_advanced_us_taxes_transaction_hook' );