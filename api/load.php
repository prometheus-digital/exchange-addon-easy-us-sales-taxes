<?php
/**
 * iThemes Exchange Advanced U.S. Taxes Add-on
 * load theme API functions
 * @package exchange-addon-advanced-us-taxes
 * @since 1.0.0
*/

if ( is_admin() ) {
	// Admin only
} else {
	// Frontend only
	include( 'theme.php' );
}