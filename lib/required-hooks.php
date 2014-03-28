<?php
/**
 * Exchange Transaction Add-ons require several hooks in order to work properly. 
 * Most of these hooks are called in api/transactions.php and are named dynamically
 * so that individual add-ons can target them. eg: it_exchange_refund_url_for_stripe
 * We've placed them all in one file to help add-on devs identify them more easily
*/

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
		wp_enqueue_script( 'it-exchange-advanced-us-taxes-admin-js', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/admin.js', $deps );
		
		$deps = array( 'jquery', 'it-exchange-advanced-us-taxes-admin-js' );
		wp_enqueue_script( 'it-exchange-advanced-us-taxes-addon-taxcloud-tic-selector', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/jquery.tic2.public.js', $deps );
		
	}
}
add_action( 'admin_enqueue_scripts', 'it_exchange_advanced_us_taxes_addon_admin_wp_enqueue_scripts' );


/**
 * Add Advanced U.S. Taxes to the content-cart totals and content-checkout loop
 *
 * @since 1.0.0
 *
 * @param array $elements list of existing elements
 * @return array
*/
function it_exchange_advanced_us_taxes_addon_add_taxes_to_template_totals_loops( $elements ) {
	$tax_options           = it_exchange_get_option( 'addon_taxes' );
	$process_after_savings = ! empty( $tax_options['calculate-after-discounts'] );

	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'totals-savings', $elements );
	if ( false === $index )
		$index = -1;

	// Bump index by 1 if calculating tax after discounts
	if ( -1 != $index && $process_after_savings )
		$index++;

	array_splice( $elements, $index, 0, 'totals-taxes-simple' );
	return $elements;
}
add_filter( 'it_exchange_get_content_cart_totals_elements', 'it_exchange_advanced_us_taxes_addon_add_taxes_to_template_totals_loops' );
add_filter( 'it_exchange_get_content_checkout_totals_elements', 'it_exchange_advanced_us_taxes_addon_add_taxes_to_template_totals_loops' );

/**
 * Add Advanced U.S. Taxes to the super-widget-checkout totals loop
 *
 * @since 1.0.0
 *
 * @param array $loops list of existing elements
 * @return array
*/
function it_exchange_advanced_us_taxes_addon_add_taxes_to_sw_template_totals_loops( $loops ) {
	$tax_options           = it_exchange_get_option( 'addon_taxes' );
	$process_after_savings = ! empty( $tax_options['calculate-after-discounts'] );

	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'discounts', $loops );
	if ( false === $index )
		$index = -1;

	// Bump index by 1 if calculating tax after discounts
	if ( -1 != $index && $process_after_savings )
		$index++;

	array_splice( $loops, $index, 0, 'taxes-simple' );
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
		'super-widget-checkout/advanced-us-taxes.php',
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
function it_exchange_advanced_us_taxes_addon_simple_modify_total( $total ) {
	$taxes = it_exchange_advanced_us_taxes_addon_get_simple_taxes_for_cart( false );
	return $total + $taxes;
}
add_filter( 'it_exchange_get_cart_total', 'it_exchange_advanced_us_taxes_addon_taxes_modify_total' );