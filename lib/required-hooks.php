<?php
/**
 * iThemes Exchange Easy U.S. Sales Taxes Add-on
 * @package exchange-addon-easy-us-sales-taxes
 * @since 1.0.0
*/

//For calculation shipping, we need to require billing addresses... 
//incase a product doesn't have a shipping address and the shipping add-on is not enabled
add_filter( 'it_exchange_billing_address_purchase_requirement_enabled', '__return_true' );


/**
 * Shows the nag when needed.
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_easy_us_sales_taxes_addon_show_conflict_nag() {
    if ( ! empty( $_REQUEST['it_exchange_easy_us_sales_taxes-dismiss-conflict-nag'] ) )
        update_option( 'it-exchange-easy-us-sales-taxes-conflict-nag', true );

    if ( true == (boolean) get_option( 'it-exchange-easy-us-sales-taxes-conflict-nag' ) )
        return;

	$taxes_addons = it_exchange_get_enabled_addons( array( 'category' => 'taxes' ) );
	
	if ( 1 < count( $taxes_addons ) ) {
		?>
		<div id="it-exchange-easy-us-sales-taxes-conflict-nag" class="it-exchange-nag">
			<?php
			$nag_dismiss = add_query_arg( array( 'it_exchange_easy_us_sales_taxes-dismiss-conflict-nag' => true ) );
			echo __( 'Warning: You have multiple tax add-ons enabled. You may need to disable one to avoid conflicts.', 'LION' );
			?>
			<a class="dismiss btn" href="<?php echo esc_url( $nag_dismiss ); ?>">&times;</a>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function() {
				if ( jQuery( '.wrap > h2' ).length == '1' ) {
					jQuery("#it-exchange-easy-us-sales-taxes-conflict-nag").insertAfter( '.wrap > h2' ).addClass( 'after-h2' );
				}
			});
		</script>
		<?php
	}
}
add_action( 'admin_notices', 'it_exchange_easy_us_sales_taxes_addon_show_conflict_nag' );

/**
 * Enqueues Easy U.S. Sales Taxes scripts to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix WordPress passed variable
 * @return void
*/
function it_exchange_easy_us_sales_taxes_addon_admin_wp_enqueue_scripts( $hook_suffix ) {
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
	
	$url_base = ITUtility::get_url_from_file( dirname( __FILE__ ) );
	
	if ( ( isset( $post_type ) && 'it_exchange_prod' === $post_type && ( 'post-new.php' === $hook_suffix || 'post.php' === $hook_suffix ) )
		|| ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'easy-us-sales-taxes' === $_GET['add-on-settings'] ) ) {
		
		$deps = array( 'jquery' );
		wp_enqueue_script( 'it-exchange-easy-us-sales-taxes-addon-taxcloud-tic-selector', $url_base . '/js/jquery.tic2.public.js', $deps, '', true );
		
	}
	
	if ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'easy-us-sales-taxes' === $_GET['add-on-settings'] ) {
	
		$deps = array( 'jquery' );
		wp_enqueue_script( 'it-exchange-easy-us-sales-taxes-addon-admin-js', $url_base . '/js/admin.js' );

	}
}
add_action( 'admin_enqueue_scripts', 'it_exchange_easy_us_sales_taxes_addon_admin_wp_enqueue_scripts' );

/**
 * Loads the frontend CSS on all exchange pages
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_easy_us_sales_taxes_load_public_scripts( $current_view ) {

	$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes' );
	
	if ( !empty( $settings['tax_exemptions'] ) && ( it_exchange_is_page( 'checkout' ) || it_exchange_in_superwidget() ) ) {

		$url_base = ITUtility::get_url_from_file( dirname( __FILE__ ) );
		
		if ( it_exchange_is_page( 'checkout' ) )
			wp_enqueue_script( 'ite-aut-addon-checkout-page-var',  $url_base . '/js/checkout-page.js' );

		$deps = array( 'jquery', 'wp-backbone', 'underscore' );
		wp_enqueue_script( 'ite-aut-addon-exemption-certificate-models',  $url_base . '/js/models/exemption-certificate-models.js', $deps );
		$deps[] =  'ite-aut-addon-exemption-certificate-models';
		wp_enqueue_script( 'ite-aut-addon-exemption-certificate-collections',  $url_base . '/js/collections/exemption-certificate-collections.js', $deps );
		$deps[] =  'ite-aut-addon-exemption-certificate-collections';
		wp_enqueue_script( 'ite-aut-addon-exemption-certificate-views',  $url_base . '/js/views/exemption-certificate-views.js', $deps );
		$deps[] =  'ite-aut-addon-exemption-certificate-views';
		wp_enqueue_script( 'ite-aut-addon-exemption-certificate-manager', $url_base . '/js/exemption-certificate-manager.js', $deps );
		
		wp_enqueue_style( 'ite-aut-addon-exemption-certificate-manager', $url_base . '/styles/exemption-certificate-manager.css' );
		
		add_action( 'wp_footer', 'it_exchange_easy_us_sales_taxes_addon_manage_certificates_backbone_template' );
		add_action( 'wp_footer', 'it_exchange_easy_us_sales_taxes_addon_list_existing_certificates_backbone_template' );
		add_action( 'wp_footer', 'it_exchange_easy_us_sales_taxes_addon_add_new_certificate_backbone_template' );

	}

}
add_action( 'wp_enqueue_scripts', 'it_exchange_easy_us_sales_taxes_load_public_scripts' );
add_action( 'it_exchange_enqueue_super_widget_scripts', 'it_exchange_easy_us_sales_taxes_load_public_scripts' );

/**
 * Enqueues Easy U.S. Sales Taxes styles to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_easy_us_sales_taxes_addon_admin_wp_enqueue_styles() {
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
	
	// Easy US Sales Taxes settings page
	if ( ( isset( $post_type ) && 'it_exchange_prod' === $post_type )
		|| ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'easy-us-sales-taxes' === $_GET['add-on-settings'] ) ) {
		
		wp_enqueue_style( 'it-exchange-easy-us-sales-taxes-addon-admin-style', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product.css' );
		
	}

}
add_action( 'admin_print_styles', 'it_exchange_easy_us_sales_taxes_addon_admin_wp_enqueue_styles' );

/**
 * Add Easy U.S. Sales Taxes to the content-cart totals and content-checkout loop
 *
 * @since 1.0.0
 *
 * @param array $elements list of existing elements
 * @return array
*/
function it_exchange_easy_us_sales_taxes_addon_add_taxes_to_template_totals_elements( $elements ) {
	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'totals-savings', $elements );
	if ( false === $index )
		$index = -1;
		
	// Bump index by 1 to show tax after discounts
	if ( -1 != $index )
		$index++;

	array_splice( $elements, $index, 0, 'easy-us-sales-taxes' );
	return $elements;
}
add_filter( 'it_exchange_get_content_checkout_totals_elements', 'it_exchange_easy_us_sales_taxes_addon_add_taxes_to_template_totals_elements' );
add_filter( 'it_exchange_get_content_confirmation_transaction_summary_elements', 'it_exchange_easy_us_sales_taxes_addon_add_taxes_to_template_totals_elements' );

/**
 * Add Easy U.S. Sales Taxes to the super-widget-checkout totals loop
 *
 * @since 1.0.0
 *
 * @param array $loops list of existing elements
 * @return array
*/
function it_exchange_easy_us_sales_taxes_addon_add_taxes_to_sw_template_totals_loops( $loops ) {
	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'discounts', $loops );
	if ( false === $index )
		$index = -1;
		
	// Bump index by 1 to show tax after discounts
	if ( -1 != $index )
		$index++;

	array_splice( $loops, $index, 0, 'easy-us-sales-taxes' );
	return $loops;
}
add_filter( 'it_exchange_get_super-widget-checkout_after-cart-items_loops', 'it_exchange_easy_us_sales_taxes_addon_add_taxes_to_sw_template_totals_loops' );

/**
 * Adds our templates directory to the list of directories
 * searched by Exchange
 *
 * @since 1.0.0
 *
 * @param array $template_path existing array of paths Exchange will look in for templates
 * @param array $template_names existing array of file names Exchange is looking for in $template_paths directories
 * @return array Modified template paths
*/
function it_exchange_easy_us_sales_taxes_addon_taxes_register_templates( $template_paths, $template_names ) {
	// Bail if not looking for one of our templates
	$add_path = false;
	$templates = array(
		'content-checkout/elements/easy-us-sales-taxes.php',
		'content-confirmation/elements/easy-us-sales-taxes.php',
		'super-widget-checkout/loops/easy-us-sales-taxes.php',
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
add_filter( 'it_exchange_possible_template_paths', 'it_exchange_easy_us_sales_taxes_addon_taxes_register_templates', 10, 2 );

/**
 * Adjusts the cart total if on a checkout page
 *
 * @since 1.0.0
 *
 * @param int $total the total passed to us by Exchange.
 * @return int New Total
*/
function it_exchange_easy_us_sales_taxes_addon_taxes_modify_total( $total ) {
	if ( !it_exchange_is_page( 'cart' ) || it_exchange_in_superwidget() ) //we just don't want to modify anything on the cart page
		$total += it_exchange_easy_us_sales_taxes_addon_get_taxes_for_cart( false );
	return $total;
}
add_filter( 'it_exchange_get_cart_total', 'it_exchange_easy_us_sales_taxes_addon_taxes_modify_total' );

/**
 * Verify Customer Address(es) in TaxCloud's API for tax calculation
 *
 * @since 1.0.0
 *
 * @param array $address Customers billing or shipping address.
 * @param int $customer_id Customer's WordPress ID
 * @return mixed Verified Address or false if failed
*/
function it_exchange_easy_us_sales_taxes_verify_customer_address( $address, $customer_id ) {
	if ( !empty( $address['country'] ) && 'US' !== $address['country'] )
		return $address; //Can only verify US addresses
	
	$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes' );
	
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
				$address['address1'] = $body->Address1;
				$address['city']     = $body->City;
				$address['state']    = $body->State;
				$address['zip5']     = $body->Zip5;
				$address['zip4']     = $body->Zip4;
			} else if ( 97 == $body->ErrNumber ) {
				//This is a non-blocking error, no changes needed
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
add_filter( 'it_exchange_save_customer_billing_address', 'it_exchange_easy_us_sales_taxes_verify_customer_address', 10, 2 );
add_filter( 'it_exchange_save_customer_shipping_address', 'it_exchange_easy_us_sales_taxes_verify_customer_address', 10, 2 );

/**
 * Authorize and capture successful transactions in TaxCloud's API
 *
 * @since 1.0.0
 *
 * @param int $transaction_id Transaction ID
*/
function it_exchange_easy_us_sales_taxes_transaction_hook( $transaction_id ) {
	$tax_cloud_session = it_exchange_get_session_data( 'addon_easy_us_sales_taxes' );
	
	//If we don't have a TaxCloud Cart ID, we cannot authorize and capture the tax
	if ( !empty( $tax_cloud_session['cart_id'] ) ) {
		$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes' );
		$customer = it_exchange_get_current_customer();

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
					update_post_meta( $transaction_id, '_it_exchange_easy_us_sales_taxes', $tax_cloud_session['taxes'] );
				} else {
					$errors = array();
					foreach( $body->Messages as $message ) {
						$errors[] = $message->ResponseType . ' ' . $message->Message;
					}
					throw new Exception( implode( ',', $errors ) );
				}
			} else {
				throw new Exception( __( 'Unknown error when trying to authorize and capture a transaction with TaxCloud.net', 'LION' ) );
			}
		}
	    catch( Exception $e ) {
			$exchange = it_exchange_get_option( 'settings_general' );
			$error = sprintf( __( 'Unable to authorize transaction with TaxCloud.net: %s', 'LION' ), $e->getMessage() );
			wp_mail( $exchange['company-email'], __( 'Error with Easy U.S. Sales Taxes', 'LION' ), $error );
	    }
	}
	
	it_exchange_clear_session_data( 'addon_easy_us_sales_taxes' );
	return;
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_easy_us_sales_taxes_transaction_hook' );

/**
 * Refund transactions in TaxCloud's API
 * (We can only do full refunds)
 *
 * @since 1.0.0
 *
 * @param object $transaction Exchange Transaction Object
 * @param int $amount Amount being refunded
 * @param string $date Date of refund
 * @param array $options Options
*/
function it_exchange_easy_us_sales_taxes_transaction_refund( $transaction, $amount, $date, $options ) {
	if ( $taxes = get_post_meta( $transaction->ID, '_it_exchange_easy_us_sales_taxes', true ) ) {
		//We have taxes
		$total = it_exchange_get_transaction_total( $transaction->ID, false, true );
		if ( 0 >= $total ) {
			$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes' );
			//We've refunded the entire purchase, we need to report this to TaxCloud
			//We cannot do individual product refunds only whole cart refunds
			$query = array(
				'apiLoginID'   => $settings['tax_cloud_api_id'],
				'apiKey'       => $settings['tax_cloud_api_key'],
				'orderID'      => $transaction->ID,
				'cartItems'    => null,
				'returnedDate' => gmdate( DATE_ATOM )
			);
			
			try {
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body' => json_encode( $query ),
			    );
				$result = wp_remote_post( ITE_TAXCLOUD_API . 'Returned', $args );
			
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				} else if ( !empty( $result['body'] ) ) {
					$body = json_decode( $result['body'] );
					if ( 0 == $body->ResponseType ) {
						$errors = array();
						foreach( $body->Messages as $message ) {
							$errors[] = $message->ResponseType . ' ' . $message->Message;
						}
						throw new Exception( implode( ',', $errors ) );
					}
				} else {
					throw new Exception( __( 'Unknown error when trying to return transaction with TaxCloud.net', 'LION' ) );
				}
			}
		    catch( Exception $e ) {
				$exchange = it_exchange_get_option( 'settings_general' );
				$error = sprintf( __( 'Unable to returning transaction with TaxCloud.net: %s', 'LION' ), $e->getMessage() );
				wp_mail( $exchange['company-email'], __( 'Error with Easy U.S. Sales Taxes', 'LION' ), $error );
		    }
		}		
	}
}
add_action( 'it_exchange_add_refund_to_transaction', 'it_exchange_easy_us_sales_taxes_transaction_refund', 15, 4 );

/**
 * Helper function to convert returned Exempt Reason strings from TaxCloud's API to readable string
 *
 * @since 1.0.0
 *
 * @param string $reason_string TaxCloud Primary Reason
 * @param string $reason_explained TaxCloud Other Reason
 * @return string Readably string (mostly just addes spaces)
*/
function it_exchange_easy_us_sales_taxes_addon_convert_reason_to_readable_string( $reason_string, $reason_explained ) {
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

/**
 * Backbone template for primary Tax Exemption Manager screen.
 * Invoked by wp.template() and WordPress 
 *
 * add_action( 'wp_footer', 'it_exchange_easy_us_sales_taxes_addon_manage_certificates_backbone_template' );
 *
 * @since 1.0.0
 */
function it_exchange_easy_us_sales_taxes_addon_manage_certificates_backbone_template() {
	?>
	<script type="text/template" id="tmpl-it-exchange-easy-us-sales-taxes-manage-certs-container">
		<span class="it-exchange-aust-close-cert-manager"><a href="">&times;</a></span>
		<div id="it-exchange-easy-us-sales-taxes-exemption-manager">
			<div id="it-exchange-easy-us-sales-taxes-exemption-manager-title-area">
				<h3 class="it-exchange-aust-tax-emeption-title">
					<?php _e( 'Tax Exemption Manager', 'LION' ); ?>
				</h3>
			</div>
			
			<div id="it-exchange-easy-us-sales-taxes-exemption-manager-content-area">
				<div id="it-exchange-easy-us-sales-taxes-exemption-manager-add-new-certificates">
					<div id="it-exchange-easy-us-sales-taxes-exemption-manager-error-area"></div>
					<img title="Create/register a new Exemption Certificate" src="//taxcloud.net/imgs/cert/new_certificate150x120.png" style="cursor:pointer;" height="120" width="150" align="left" />
					<?php
					echo it_exchange_easy_us_sales_taxes_addon_add_exemption();
					?>
				</div>
				<div id="it-exchange-easy-us-sales-taxes-exemption-manager-existing-certificates"></div>
			</div>
		</div>
	</script>
	<?php
}

/**
 * Backbone template for listing all existing certificates in the Tax Exemption Manager.
 * Invoked by wp.template() and WordPress 
 *
 * Called by add_action( 'wp_footer', 'it_exchange_easy_us_sales_taxes_addon_list_existing_certificates_backbone_template' );
 *
 * @since 1.0.0
 */
function it_exchange_easy_us_sales_taxes_addon_list_existing_certificates_backbone_template() {
	?>
	<script type="text/template" id="tmpl-it-exchange-easy-us-sales-taxes-list-certs-container">
		<div class="it-exchange-easy-us-sales-taxes-exemption-manager-list-certs-content-area">
			
			<div class="it-exchange-easy-us-sales-taxes-existing-exemption-image">
				<a class="view-existing-certificate" data-cert-id="{{{ data.CertificateID }}}" href="#">
					<img title="Existing Exemption Certificate" src="//taxcloud.net/imgs/cert/exemption_certificate150x120.png" style="cursor:pointer;" height="120" width="150" />
				</a>
			</div>
			<div class="it-exchange-easy-us-sales-taxes-existing-exemption-text">
				<p>
					<strong><?php _e( 'Issued To:', 'LION' ); ?></strong> {{{ data.PurchaserFirstName }}} {{{ data.PurchaserLastName }}}<br />
					<strong><?php _e( 'Exempt State(s):', 'LION' ); ?></strong> {{{ data.ExemptStates }}}<br />
					<strong><?php _e( 'Date:', 'LION' ); ?></strong> {{{ data.CreatedDate }}}<br />
					<strong><?php _e( 'Purpose:', 'LION' ); ?></strong> {{{ data.PurchaserExemptionReason }}}<br />
				</p>
				<p>
					<!-- <a href="#" id="it-exchange-aust-view-existing-certificate" class="view-existing-certificate button" data-cert-id="{{{ data.CertificateID }}}">View</a> -->
					<a href="#" id="it-exchange-aust-use-existing-certificate" class="button" data-cert-id="{{{ data.CertificateID }}}"><?php _e( 'Use', 'LION' ); ?></a>
					<a href="#" id="it-exchange-aust-remove-existing-certificate" class="button" data-cert-id="{{{ data.CertificateID }}}"><?php _e( 'Remove', 'LION' ); ?></a>
				</p>
			</div>
		</div>
	</script>
	<?php
}

/**
 * Backbone template for Add New Tax Exemptions screen.
 * Invoked by wp.template() and WordPress 
 *
 * Called by add_action( 'wp_footer', 'it_exchange_easy_us_sales_taxes_addon_add_new_certificate_backbone_template' );
 *
 * @since 1.0.0
 */
function it_exchange_easy_us_sales_taxes_addon_add_new_certificate_backbone_template() {

	$form_options = array(
		'id'      => apply_filters( 'it_exchange_add_on_easy_us_sales_taxes_add_cert', 'it-exchange-add-on-easy-us-sales-taxes-add-cert' ),
		'enctype' => apply_filters( 'it_exchange_add_on_easy_us_sales_taxes_add_cert_form_enctype', false ),
		'action'  => '',
	);
	$form = new ITForm();
	
	?>
	<div id="it-exchange-easy-us-sales-taxes-exemption-manager-wrapper" class="it-exchange-hidden"></div>
	<script type="text/template" id="tmpl-it-exchange-easy-us-sales-taxes-add-cert-container">
		<span class="it-exchange-aust-close-cert-manager"><a href="">&times;</a></span>
		<div id="it-exchange-easy-us-sales-taxes-exemption-manager">
			<div id="it-exchange-easy-us-sales-taxes-exemption-manager-title-area">
				<h3 class="it-exchange-aust-tax-emeption-title">
					<?php _e( 'Tax Exemption Manager', 'LION' ); ?>
				</h3>
			</div>
		
			<div id="it-exchange-easy-us-sales-taxes-exemption-manager-content-area">
				<div id="it-exchange-easy-us-sales-taxes-exemption-manager-error-area"></div>
				<?php
					$form->start_form( $form_options, 'it-exchange-easy-us-sales-taxes-new-cert' );
					
					if ( !empty( $form_values ) )
						foreach ( $form_values as $key => $var )
							$form->set_option( $key, $var );
					?>
					
					<div class="it-exchange-easy-us-sales-taxes-add-exemption-section">
						<h3><?php _e( 'Warning to Purchaser', 'LION' ); ?></h3>
						<p>
						<?php _e( '<strong>This is a multistate form. Not all states allow all exemptions</strong> listed on this form. Purchasers are responsible for knowing if they qualify to claim exemption from tax in the state that is due tax on this sale. The state that is due tax on this sale will be notified that you claimed exemption from sales tax. You will be held liable for any tax and interest, as well as civil and criminal penalties imposed by the member state, if you are not eligible to claim this exemption. Sellers may not accept a certificate of exemption for an entity-based exemption on a sale at a location operated by the seller within the designated state if the state does not allow such an entity-based exemption.', 'LION' ); ?>
						</p>
					</div>
					
					<div class="it-exchange-easy-us-sales-taxes-add-exemption-section">
						<div class="it-exchange-exemption-section-half-width">
							<h3><?php _e( 'Certificate of Exemption', 'LION' ); ?></h3>
							<p>
								<label for="exempt_state"><?php _e( 'Select the state under whose laws you are claiming exemption.', 'LION' ); ?></label><br />
								<?php
									$states = it_exchange_get_data_set( 'states', array( 'country' => 'US', 'include-territories' => true ) );
									$form->add_drop_down( 'exempt_state', $states ); 
								?>
							</p>
						</div>
						<div class="it-exchange-exemption-section-half-width">
							<h3><?php _e( 'Select one:', 'LION' ); ?></h3>
							<p>
								<?php $form->add_radio( 'exempt_type', array( 'value' => 'single' ) ); ?> <label for="exempt_type-single"><?php _e( 'Single purchase certificate.', 'LION' ); ?></label> <span id="exempt_type_single_selected" class="it-exchange-hidden"><?php _e( 'Relates to invoice/purchase order #', 'LION' ); ?> <?php $form->add_text_box( 'order_number' ); ?></span>
								<br />
								<?php $form->add_radio( 'exempt_type', array( 'value' => 'bulk', 'checked' => true ) ); ?> <label for="exempt_type-bulk"><?php _e( 'Blanket certificate.', 'LION' ); ?></label> <span id="exempt_type_bulk_selected"><?php _e( 'If selected, this certificate continues in force until canceled by the purchaser.', 'LION' ); ?></span>
							</p>
						</div>
					</div>
						
					<div class="it-exchange-easy-us-sales-taxes-add-exemption-section">
						<h3><?php _e( 'Purchaser Identification', 'LION' ); ?></h3>
						<div class="it-exchange-exemption-section-half-width">
							<p>
								<label for="purchaser_name"><?php _e( 'Purchaser Name', 'LION' ); ?></label><br />
								<?php $form->add_text_box( 'purchaser_name' ); ?>
							</p>
							<p>
								<label for="business_address"><?php _e( 'Business Address', 'LION' ); ?></label><br />
								<?php $form->add_text_box( 'business_address' ); ?>
							</p>
							<p>
								<label for="business_city"><?php _e( 'City', 'LION' ); ?></label><br />
								<?php $form->add_text_box( 'business_city' ); ?>
							</p>
							<p>
								<label for="business_state"><?php _e( 'State', 'LION' ); ?></label><br />
								<?php $form->add_drop_down( 'business_state', $states ); ?>
							</p>
							<p>
								<label for="business_zip_5"><?php _e( 'Zip Code', 'LION' ); ?></label><br />
								<?php $form->add_text_box( 'business_zip_5' ); ?>
							</p>
						</div>
						
						<div class="it-exchange-exemption-section-half-width">
							<p>
								<label for="exemption_type"><?php _e( "Purchaser's Exemption ID number", 'LION' ); ?></label><br />
								<?php 
								$exemption_types = array(
									'FEIN'            => __( 'Federal Employer ID', 'LION' ),
									'StateIssued'     => __( 'State Issued Exemption ID or Drivers License', 'LION' ),
									'ForeignDiplomat' => __( 'Foreign Diplomat ID', 'LION' ),
								);
								$form->add_drop_down( 'exemption_type', $exemption_types ); 
								?>
								<br />
								<label for="exemption_type_number" ><?php _e( 'Number:', 'LION' ); ?></label><br />
								<?php $form->add_text_box( 'exemption_type_number' ); ?>
								<div id="exemption_type_issuer_state_div" class="it-exchange-hidden">
									<label for="exemption_type_issuer_state"><?php _e( 'Issued By:', 'LION' ); ?></label><br />
									<?php $form->add_drop_down( 'exemption_type_issuer_state', $states ); ?>
								</div>
								<div id="exemption_type_issuer_other_div" class="it-exchange-hidden">
									<label for="exemption_type_issuer_other">
									<?php _e( 'Issued By:', 'LION' ); ?>
									</label>
									<?php $form->add_text_box( 'exemption_type_issuer_other' ); ?>
								</div>
							</p>
							
							<p>
								<label for="business_type"><?php _e( 'Purchaser Business Type', 'LION' ); ?></label><br />
								<?php 
									$business_type = array(
										'AccommodationAndFoodServices' => __( 'Accommodation And Food Services', 'LION' ),
										'Agricultural_Forestry_Fishing_Hunting' => __( 'Agricultural/Forestry/Fishing/Hunting', 'LION' ),
										'Construction' => __( 'Construction', 'LION' ),
										'FinanceAndInsurance' => __( 'Finance or Insurance', 'LION' ),
										'Information_PublishingAndCommunications' => __( 'Information Publishing and Communications', 'LION' ),
										'Manufacturing' => __( 'Manufacturing', 'LION' ),
										'Mining' => __( 'Mining', 'LION' ),
										'RealEstate' => __( 'Real Estate', 'LION' ),
										'RentalAndLeasing' => __( 'Rental and Leasing', 'LION' ),
										'RetailTrade' => __( 'Retail Trade', 'LION' ),
										'TransportationAndWarehousing' => __( 'Transportation and Warehousing', 'LION' ),
										'Utilities' => __( 'Utilities', 'LION' ),
										'WholesaleTrade' => __( 'Wholesale Trade', 'LION' ),
										'BusinessServices' => __( 'Business Services', 'LION' ),
										'ProfessionalServices' => __( 'Professional Services', 'LION' ),
										'EducationAndHealthCareServices' => __( 'Education and Health Care Services', 'LION' ),
										'NonprofitOrganization' => __( 'Nonprofit Organization', 'LION' ),
										'Government' => __( 'Government', 'LION' ),
										'NotABusiness' => __( 'Not a Business', 'LION' ),
										'Other' => __( 'Other', 'LION' ),
									);
									$form->add_drop_down( 'business_type', $business_type ); 
								?>
								<div id="business_type_other_div" class="it-exchange-hidden">
									<label for="business_type_other"><?php _e( 'Please explain:', 'LION' ); ?></label><br />
									<?php $form->add_text_box( 'business_type_other' ); ?>
								</div>
							</p>
							
							<p>
								<label for="exemption_reason"><?php _e( 'Reason for exemption', 'LION' ); ?></label><br />
								<?php 
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
								$form->add_drop_down( 'exemption_reason', $exemption_types ); 
								?>
								<div id="exemption_reason_value_div">
									<label for="exemption_reason_value"><?php _e( 'Please explain:', 'LION' ); ?></label><br />
									<?php $form->add_text_box( 'exemption_reason_value' ); ?>
								</div>
							</p>
						</div>
					</div>
					<div class="field it-exchange-add-cert-submit">
						<input type="submit" value="<?php _e( 'Save Certificate', 'LION' ); ?>" class="button button-large it-exchange-aust-save-cert-button" id="save" name="save">
						<input type="submit" value="Cancel" class="button button-large it-exchange-aust-cancel-cert-button" id="cancel" name="cancel">
						<?php wp_nonce_field( 'it-exchange-easy-us-sales-taxes-add-cert', 'it-exchange-easy-us-sales-taxes-add-cert-nonce' ); ?>
					</div>
				    <?php $form->end_form(); ?>
			</div>
		</div>
	</script>
	<?php
}

/**
 * Adds the cart taxes to the transaction object
 *
 * @since CHANGEME
 *
 * @param string $taxes incoming from WP Filter. False by default.
 * @return string
 *
*/
function it_exchange_easy_us_sales_taxes_add_cart_taxes_to_txn_object() {
    $formatted = ( 'it_exchange_set_transaction_objet_cart_taxes_formatted' == current_filter() );
    return it_exchange_easy_us_sales_taxes_addon_get_taxes_for_cart( $formatted );
}
add_filter( 'it_exchange_set_transaction_objet_cart_taxes_formatted', 'it_exchange_easy_us_sales_taxes_add_cart_taxes_to_txn_object' );
add_filter( 'it_exchange_set_transaction_objet_cart_taxes_raw', 'it_exchange_easy_us_sales_taxes_add_cart_taxes_to_txn_object' );
