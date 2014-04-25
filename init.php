<?php
/**
 * iThemes Exchange Advanced U.S. Taxes Add-on
 * @package exchange-addon-advanced-us-taxes
 * @since 1.0.0
*/

if ( !defined( 'ITE_TAXCLOUD_API' ) )
	define( 'ITE_TAXCLOUD_API', 'https://api.taxcloud.net/1.0/Taxcloud/' );
	
if ( !defined( 'ITE_TAXCLOUD_WSDL' ) )
	define( 'ITE_TAXCLOUD_WSDL', 'https://api.taxcloud.net/1.0/?wsdl' );

/**
 * Exchange will build your add-on's settings page for you and link to it from our add-on
 * screen. You are free to link from it elsewhere as well if you'd like... or to not use our API
 * at all. This file has all the functions related to registering the page, printing the form, and saving
 * the options. This includes the wizard settings. Additionally, we use the Exchange storage API to 
 * save / retreive options. Add-ons are not required to do this.
*/
include( 'lib/addon-settings.php' );

/**
 * We decided to place all AJAX hooked functions into this file, just for ease of use
*/
include( 'lib/addon-ajax-hooks.php' );
/**
 * The following file contains utility functions specific to our customer pricing add-on
 * If you're building your own addon, it's likely that you will
 * need to do similar things.
*/
include( 'lib/addon-functions.php' );

/**
 * Exchange Add-ons require several hooks in order to work properly. 
 * We've placed them all in one file to help add-on devs identify them more easily
*/
include( 'lib/required-hooks.php' );

/**
 * New Product Features added by the Exchange Membership Add-on.
*/
require( 'lib/product-features/load.php' );