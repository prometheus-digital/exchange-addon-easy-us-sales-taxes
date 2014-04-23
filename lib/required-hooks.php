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
	
	$url_base = ITUtility::get_url_from_file( dirname( __FILE__ ) );
	
	if ( ( isset( $post_type ) && 'it_exchange_prod' === $post_type )
		|| ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'advanced-us-taxes' === $_GET['add-on-settings'] ) ) {
		
		$deps = array( 'jquery' );
		wp_enqueue_script( 'it-exchange-advanced-us-taxes-addon-taxcloud-tic-selector', $url_base . '/js/jquery.tic2.public.js', $deps, '', true );
		
	}
}
add_action( 'admin_enqueue_scripts', 'it_exchange_advanced_us_taxes_addon_admin_wp_enqueue_scripts' );

/**
 * Loads the frontend CSS on all exchange pages
 *
 * @since 0.4.0
 *
 * @return void
*/
function it_exchange_advanced_us_taxes_load_public_scripts( $current_view ) {
	
	// ****** CART/CHECKOUT SPECIFIC SCRIPTS *******
	if ( it_exchange_is_page( 'checkout' ) || it_exchange_is_page( 'cart' ) 
		|| it_exchange_in_superwidget() ) {

		$url_base = ITUtility::get_url_from_file( dirname( __FILE__ ) );

		$deps = array( 'jquery', 'wp-backbone', 'underscore' );
		wp_enqueue_script( 'ite-aut-addon-exemption-certificate-models',  $url_base . '/js/models/exemption-certificate-models.js', $deps );
		$deps[] =  'ite-aut-addon-exemption-certificate-models';
		wp_enqueue_script( 'ite-aut-addon-exemption-certificate-collections',  $url_base . '/js/collections/exemption-certificate-collections.js', $deps );
		$deps[] =  'ite-aut-addon-exemption-certificate-collections';
		wp_enqueue_script( 'ite-aut-addon-exemption-certificate-views',  $url_base . '/js/views/exemption-certificate-views.js', $deps );
		$deps[] =  'ite-aut-addon-exemption-certificate-views';
		wp_enqueue_script( 'ite-aut-addon-exemption-certificate-manager', $url_base . '/js/exemption-certificate-manager.js', $deps );
		
		wp_enqueue_style( 'ite-aut-addon-exemption-certificate-manager', $url_base . '/styles/exemption-certificate-manager.css' );
		
		//wp_localize_script( 'ite-aut-addon-exemption-certificate-models', 'ite_aust_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		
		add_action( 'wp_footer', 'it_exchange_advanced_us_taxes_addon_manage_certificates_backbone_template' );
		add_action( 'wp_footer', 'it_exchange_advanced_us_taxes_addon_list_existing_certificates_backbone_template' );
		add_action( 'wp_footer', 'it_exchange_advanced_us_taxes_addon_add_new_certificate_backbone_template' );

	}
	// ****** END CART/CHECKOUT SPECIFIC SCRIPTS *******

}
add_action( 'wp_enqueue_scripts', 'it_exchange_advanced_us_taxes_load_public_scripts' );
add_action( 'it_exchange_enqueue_super_widget_scripts', 'it_exchange_advanced_us_taxes_load_public_scripts' );

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
	$tax_cloud_session = it_exchange_get_session_data( 'addon_advanced_us_taxes' );
	
	//If we don't have a tax cloud Cart ID, we cannot authorize and capture the tax
	if ( !empty( $tax_cloud_session['cart_id'] ) ) {
		$settings = it_exchange_get_option( 'addon_advanced_us_taxes' );
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
					update_post_meta( $transaction_id, '_it_exchange_advanced_us_taxes', $tax_cloud_session['taxes'] );
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
			wp_mail( $exchange['company-email'], __( 'Error with Advanced U.S. Taxes', 'LION' ), $error );
	    }
	}
	
	it_exchange_clear_session_data( 'addon_advanced_us_taxes' );
	return;
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_advanced_us_taxes_transaction_hook' );

function it_exchange_advanced_us_taxes_transaction_refund( $transaction, $amount, $date, $options ) {
	if ( $taxes = update_post_meta( $transaction->ID, '_it_exchange_advanced_us_taxes', true ) ) {
		//We have taxes
		$total = it_exchange_get_transaction_total( $transaction->ID, false, true );
		if ( 0 >= $total ) {
			$settings = it_exchange_get_option( 'addon_advanced_us_taxes' );
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
				wp_mail( $exchange['company-email'], __( 'Error with Advanced U.S. Taxes', 'LION' ), $error );
		    }
		}		
	}
}
add_action( 'it_exchange_add_refund_to_transaction', 'it_exchange_advanced_us_taxes_transaction_refund', 10, 4 );

function it_exchange_advanced_us_taxes_addon_manage_certificates_backbone_template() {
	?>
	<script type="text/template" id="tmpl-it-exchange-advanced-us-taxes-manage-certs-container">
		<span class="it-exchange-aust-close-cert-manager"><a href="">&times;</a></span>
		<div id="it-exchange-advanced-us-taxes-exemption-manager">
			<div id="it-exchange-advanced-us-taxes-exemption-manager-title-area">
				<h3 class="it-exchange-aust-tax-emeption-title">
					<?php _e( 'Tax Exemption Manager', 'LION' ); ?>
				</h3>
				<!--
				<span class="it-exchange-aust-close-cert-manager">
					<a href="">&times;</a>
				</span>
				
				-->
			</div>
			
			<div id="it-exchange-advanced-us-taxes-exemption-manager-content-area">
				<div id="it-exchange-advanced-us-taxes-exemption-manager-add-new-certificates">
					<div id="it-exchange-advanced-us-taxes-exemption-manager-error-area"></div>
					<img title="Create/register a new Exemption Certificate" src="//taxcloud.net/imgs/cert/new_certificate150x120.png" style="cursor:pointer;" height="120" width="150" align="left" />
					<?php
					echo it_exchange_advanced_us_taxes_addon_add_exemption();
					?>
				</div>
				<div id="it-exchange-advanced-us-taxes-exemption-manager-existing-certificates"></div>
			</div>
		</div>
	</script>
	<?php
}

function it_exchange_advanced_us_taxes_addon_list_existing_certificates_backbone_template() {
	?>
	<script type="text/template" id="tmpl-it-exchange-advanced-us-taxes-list-certs-container">
		<div class="it-exchange-advanced-us-taxes-exemption-manager-list-certs-content-area">
			
			<div class="it-exchange-advanced-us-taxes-existing-exemption-image">
				<a class="view-existing-certificate" data-cert-id="{{{ data.CertificateID }}}" href="#">
					<img title="Existing Exemption Certificate" src="//taxcloud.net/imgs/cert/exemption_certificate150x120.png" style="cursor:pointer;" height="120" width="150" />
				</a>
			</div>
			<div class="it-exchange-advanced-us-taxes-existing-exemption-text">
				<p>
					<strong><?php _e( 'Issued To:', 'LION' ); ?></strong> {{{ data.PurchaserFirstName }}} {{{ data.PurchaserLastName }}}<br />
					<strong><?php _e( 'Exempt State(s):', 'LION' ); ?></strong> {{{ data.ExemptStates }}}<br />
					<strong><?php _e( 'Date:', 'LION' ); ?></strong> {{{ data.CreatedDate }}}<br />
					<strong><?php _e( 'Purpose:', 'LION' ); ?></strong> {{{ data.PurchaserExemptionReason }}}<br />
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

function it_exchange_advanced_us_taxes_addon_add_new_certificate_backbone_template() {

	$errors = array();

	if ( !is_user_logged_in() ) {
	
		$errors[] = __( 'You must be logged in to apply for tax exemption', 'LION' );
		
	} else {
		
		$form_options = array(
			'id'      => apply_filters( 'it_exchange_add_on_advanced_us_taxes_add_cert', 'it-exchange-add-on-advanced-us-taxes-add-cert' ),
			'enctype' => apply_filters( 'it_exchange_add_on_advanced_us_taxes_add_cert_form_enctype', false ),
			'action'  => '',
		);
		$form = new ITForm();

	}
	
	?>
	<div id="it-exchange-advanced-us-taxes-exemption-manager-wrapper" class="it-exchange-hidden"></div>
	<script type="text/template" id="tmpl-it-exchange-advanced-us-taxes-add-cert-container">
		<span class="it-exchange-aust-close-cert-manager"><a href="">&times;</a></span>
		<div id="it-exchange-advanced-us-taxes-exemption-manager">
			<div id="it-exchange-advanced-us-taxes-exemption-manager-title-area">
				<h3 class="it-exchange-aust-tax-emeption-title">
					<?php _e( 'Tax Exemption Manager', 'LION' ); ?>
				</h3>
				<!--
				<span class="it-exchange-aust-close-cert-manager">
					<a href="">&times;</a>
				</span>
				-->
			</div>
		
			<div id="it-exchange-advanced-us-taxes-exemption-manager-content-area">
				<div id="it-exchange-advanced-us-taxes-exemption-manager-error-area"></div>
				<?php
				if ( !empty( $errors ) ) {
				
					ITUtility::show_error_message( $errors );
					
				} else {
				
					$form->start_form( $form_options, 'it-exchange-advanced-us-taxes-new-cert' );
					
					if ( !empty( $form_values ) )
						foreach ( $form_values as $key => $var )
							$form->set_option( $key, $var );
					?>
					
					<div class="it-exchange-advanced-us-taxes-add-exemption-section">
						<h3><?php _e( 'Warning to Purchaser', 'LION' ); ?></h3>
						<p>
						<?php _e( '<strong>This is a multistate form. Not all states allow all exemptions</strong> listed on this form. Purchasers are responsible for knowing if they qualify to claim exemption from tax in the state that is due tax on this sale. The state that is due tax on this sale will be notified that you claimed exemption from sales tax. You will be held liable for any tax and interest, as well as civil and criminal penalties imposed by the member state, if you are not eligible to claim this exemption. Sellers may not accept a certificate of exemption for an entity-based exemption on a sale at a location operated by the seller within the designated state if the state does not allow such an entity-based exemption.', 'LION' ); ?>
						</p>
					</div>
					
					<div class="it-exchange-advanced-us-taxes-add-exemption-section">
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
						
					<div class="it-exchange-advanced-us-taxes-add-exemption-section">
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
						<?php wp_nonce_field( 'it-exchange-advanced-us-taxes-add-cert', 'it-exchange-advanced-us-taxes-add-cert-nonce' ); ?>
					</div>
				    <?php $form->end_form(); ?>
				<?php } ?>
			</div>
		</div>
	</script>
	<?php
}