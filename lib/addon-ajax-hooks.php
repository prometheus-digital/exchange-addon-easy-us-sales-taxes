<?php

/**
 * Ajax called from Thickbox to show the User's Add Product Screen.
 *
 * @since 1.0.0
*/
function it_exchange_advanced_us_taxes_addon_print_tax_certs() {	
	global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow, $wp_version,
	$update_title, $total_update_count, $parent_file, $current_screen;
	
	// Catch plugins that include admin-header.php before admin.php completes.
	if ( empty( $current_screen ) )
		set_current_screen();
	
	$output = '';
	$errors = '';
	
	if ( is_user_logged_in() ) {
		$settings = it_exchange_get_option( 'addon_advanced_us_taxes' );
		$customer = it_exchange_get_current_customer();

		$query = array(
			'apiLoginID'     => $settings['tax_cloud_api_id'],
			'apiKey'         => $settings['tax_cloud_api_key'],
			'customerID'     => $customer->ID,
		);
		
		try {
			$args = array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body' => json_encode( $query ),
		    );
			$result = wp_remote_post( ITE_TAXCLOUD_API . 'GetExemptCertificates', $args );
		
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			} else if ( !empty( $result['body'] ) ) {
				$body = json_decode( $result['body'] );
				if ( 0 != $body->ResponseType ) {
					$exempt_certificates = $body->ExemptCertificates;
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
			$errors = sprintf( __( 'Unable to authorize transaction with TaxCloud.net: %s', 'LION' ), $e->getMessage() );
	    }
	} else {
		
		$errors = __( 'You must be logged in to apply for tax exemption', 'LION' );
		
	}
	
	wp_user_settings();
	@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
	_wp_admin_html_begin();
	?>
	<title><?php _e( 'Add Manual Purchase', 'LION' ); ?></title>
	<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	</script>
	<?php
	wp_enqueue_style( 'colors' );
	wp_enqueue_style( 'ie' );
	wp_enqueue_script( 'utils' );
	
	$hook_suffix = 'user.php-it-exchange-advanced-us-taxes-manage-certs-thickbox';
	do_action( 'admin_enqueue_scripts', $hook_suffix );
	do_action( "admin_print_styles-$hook_suffix" );
	do_action( 'admin_print_styles' );
	do_action( "admin_print_scripts-$hook_suffix" );
	do_action( 'admin_print_scripts' );
	do_action( "admin_head-$hook_suffix" );
	do_action( 'admin_head' );
	
	$admin_body_class = preg_replace('/[^a-z0-9_-]+/i', '-', $hook_suffix);
	
	if ( get_user_setting('mfold') == 'f' )
		$admin_body_class .= ' folded';
	
	if ( !get_user_setting('unfold') )
		$admin_body_class .= ' auto-fold';
	
	if ( is_rtl() )
		$admin_body_class .= ' rtl';
	
	if ( $current_screen->post_type )
		$admin_body_class .= ' post-type-' . $current_screen->post_type;
	
	if ( $current_screen->taxonomy )
		$admin_body_class .= ' taxonomy-' . $current_screen->taxonomy;
	
	$admin_body_class .= ' branch-' . str_replace( array( '.', ',' ), '-', floatval( $wp_version ) );
	$admin_body_class .= ' version-' . str_replace( '.', '-', preg_replace( '/^([.0-9]+).*/', '$1', $wp_version ) );
	$admin_body_class .= ' admin-color-' . sanitize_html_class( get_user_option( 'admin_color' ), 'fresh' );
	$admin_body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );
	
	if ( wp_is_mobile() )
		$admin_body_class .= ' mobile';
	
	if ( is_multisite() )
		$admin_body_class .= ' multisite';
	
	if ( is_network_admin() )
		$admin_body_class .= ' network-admin';
	
	$admin_body_class .= ' no-customize-support no-svg';
	?>
	</head>
	<body class="wp-admin wp-core-ui no-js <?php echo apply_filters( 'admin_body_class', '' ) . " $admin_body_class"; ?>">
	<?php 
	
	if ( !empty( $errors ) ) {
	
		echo '<h1>' . $errors . '</h1>';
		
	} else {
	
		?>
		<img title="Create/register a new Exemption Certificate" src="http://taxcloud.net/imgs/cert/new_certificate150x120.png" style="cursor:pointer;" height="120" width="150" align="left">
		<?php
		echo it_exchange_advanced_us_taxes_addon_add_exemption();
	
		if ( !empty( $exempt_certificates ) ) {
			
			foreach( $exempt_certificates as $cert ) {
				
				//do stuff
				
			}
			
		}
	}
		
	?>
	</body>
	</html>
	
	<?php
	die();
}
add_action( 'wp_ajax_it-exchange-advanced-us-tax-certs', 'it_exchange_advanced_us_taxes_addon_print_tax_certs' );
add_action( 'wp_ajax_nopriv_it-exchange-advanced-us-tax-certs', 'it_exchange_advanced_us_taxes_addon_print_tax_certs' );

/**
 * Ajax called from Thickbox to show the User's Add Product Screen.
 *
 * @since 1.0.0
*/
function it_exchange_advanced_us_taxes_addon_print_add_tax_cert() {	
	global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow, $wp_version,
	$update_title, $total_update_count, $parent_file, $current_screen;
	
	// Catch plugins that include admin-header.php before admin.php completes.
	if ( empty( $current_screen ) )
		set_current_screen();
	
	$output = '';
	$errors = '';
	
	if ( !is_user_logged_in() ) {
	
		$errors = __( 'You must be logged in to apply for tax exemption', 'LION' );
		
	} else {
		
		$form_values  = ITForm::get_post_data();
		$form_options = array(
			'id'      => apply_filters( 'it_exchange_add_on_advanced_us_taxes_add_cert', 'it-exchange-add-on-advanced-us-taxes-add-cert' ),
			'enctype' => apply_filters( 'it_exchange_add_on_advanced_us_taxes_add_cert_form_enctype', false ),
			'action'  => '',
		);
		$form = new ITForm( $form_values );

	}

	wp_user_settings();
	@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
	_wp_admin_html_begin();
	?>
	<title><?php _e( 'Add Manual Purchase', 'LION' ); ?></title>
	<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	</script>
	<?php
	wp_enqueue_style( 'colors' );
	wp_enqueue_style( 'ie' );
	wp_enqueue_script( 'utils' );
	
	$hook_suffix = 'user.php-it-exchange-advanced-us-taxes-add-cert-thickbox';
	do_action( 'admin_enqueue_scripts', $hook_suffix );
	do_action( "admin_print_styles-$hook_suffix" );
	do_action( 'admin_print_styles' );
	do_action( "admin_print_scripts-$hook_suffix" );
	do_action( 'admin_print_scripts' );
	do_action( "admin_head-$hook_suffix" );
	do_action( 'admin_head' );
	
	$admin_body_class = preg_replace('/[^a-z0-9_-]+/i', '-', $hook_suffix);
	
	if ( get_user_setting('mfold') == 'f' )
		$admin_body_class .= ' folded';
	
	if ( !get_user_setting('unfold') )
		$admin_body_class .= ' auto-fold';
	
	if ( is_rtl() )
		$admin_body_class .= ' rtl';
	
	if ( $current_screen->post_type )
		$admin_body_class .= ' post-type-' . $current_screen->post_type;
	
	if ( $current_screen->taxonomy )
		$admin_body_class .= ' taxonomy-' . $current_screen->taxonomy;
	
	$admin_body_class .= ' branch-' . str_replace( array( '.', ',' ), '-', floatval( $wp_version ) );
	$admin_body_class .= ' version-' . str_replace( '.', '-', preg_replace( '/^([.0-9]+).*/', '$1', $wp_version ) );
	$admin_body_class .= ' admin-color-' . sanitize_html_class( get_user_option( 'admin_color' ), 'fresh' );
	$admin_body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );
	
	if ( wp_is_mobile() )
		$admin_body_class .= ' mobile';
	
	if ( is_multisite() )
		$admin_body_class .= ' multisite';
	
	if ( is_network_admin() )
		$admin_body_class .= ' network-admin';
	
	$admin_body_class .= ' no-customize-support no-svg';
	?>
	</head>
	<body class="wp-admin wp-core-ui no-js <?php echo apply_filters( 'admin_body_class', '' ) . " $admin_body_class"; ?>">
	<?php 
	
	if ( !empty( $errors ) ) {
	
		echo '<h1>' . $errors . '</h1>';
		
	} else {
	
		$form->start_form( $form_options, 'it-exchange-advanced-us-taxes-new-cert' );
		
		if ( !empty( $form_values ) )
			foreach ( $form_values as $key => $var )
				$form->set_option( $key, $var );
				
		?>
		<h3><?php _e( 'Warning to Purchaser', 'LION' ); ?></h3>
		<p>
		<?php _e( '<strong>This is a multistate form. Not all states allow all exemptions</strong> listed on this form. Purchasers are responsible for knowing if they qualify to claim exemption from tax in the state that is due tax on this sale. The state that is due tax on this sale will be notified that you claimed exemption from sales tax. You will be held liable for any tax and interest, as well as civil and criminal penalties imposed by the member state, if you are not eligible to claim this exemption. Sellers may not accept a certificate of exemption for an entity-based exemption on a sale at a location operated by the seller within the designated state if the state does not allow such an entity-based exemption.', 'LION' ); ?>
		</p>
		
		<h3><?php _e( 'Certificate of Exemption', 'LION' ); ?></h3>
		<p>
		<?php
        $states = it_exchange_get_data_set( 'states', array( 'country' => 'US', 'include-territories' => true ) );
        $form->add_drop_down( 'exempt_state', $states ); 
		?>
		<span><?php _e( 'Select the state under whose laws you are claiming exemption.', 'LION' ); ?></span>
		</p>
		
		<h3><?php _e( 'Select one:', 'LION' ); ?></h3>
        <p>
        <?php $form->add_radio( 'exempt_type', array( 'value' => 'single' ) ); ?><?php _e( 'Single purchase certificate.', 'LION' ); ?></label> <span id="exempt_type_single_selected" class="hidden"><?php _e( 'Relates to invoice/purchase order #', 'LION' ); ?> <?php $form->add_text_box( 'order_number' ); ?></span>
        </p>
        <p>
        <?php $form->add_radio( 'exempt_type', array( 'value' => 'bulk', 'checked' => true ) ); ?><?php _e( 'Blanket certificate.', 'LION' ); ?></label> <span id="exempt_type_bulk_selected"><?php _e( 'If selected, this certificate continues in force until canceled by the purchaser.', 'LION' ); ?></span>
        </p>
        
        <h3><?php _e( 'Purchaser Identification', 'LION' ); ?></h3>
        <p>
        <label for="purchaser_name"><?php _e( 'Purchaser Name', 'LION' ); ?></label>
        <?php $form->add_text_box( 'purchaser_name' ); ?>
        </p>
        <p>
        <label for="business_address"><?php _e( 'Business Address', 'LION' ); ?></label>
        <?php $form->add_text_box( 'business_address' ); ?>
        </p>
        <p>
        <label for="business_city"><?php _e( 'City', 'LION' ); ?></label>
        <?php $form->add_text_box( 'business_city' ); ?>
        </p>
        <p>
        <label for="business_state"><?php _e( 'State', 'LION' ); ?></label>
        <?php $form->add_drop_down( 'business_state', $states ); ?>
        </p>
        <p>
        <label for="business_zip_5"><?php _e( 'Zip Code', 'LION' ); ?></label>
        <?php $form->add_text_box( 'business_zip_5' ); ?>
        </p>
        <p>
        <label for="exemption_type"><?php _e( "Purchaser's Exemption ID number", 'LION' ); ?></label>
        <?php 
        $exemption_types = array(
        	'FEIN'            => __( 'Federal Employer ID', 'LION' ),
        	'StateIssued'     => __( 'State Issued Exemption ID or Drivers License', 'LION' ),
        	'ForeignDiplomat' => __( 'Foreign Diplomat ID', 'LION' ),
        );
        $form->add_drop_down( 'exemption_type', $exemption_types ); 
        ?>
        <span id="exemption_id_number" ><?php _e( 'Number:', 'LION' ); ?></span>
        <?php $form->add_text_box( 'exemption_type_id' ); ?>
        <span id="exemption_id_issued_by" class="hidden">
        <?php _e( 'Issued By:', 'LION' ); ?>
        <?php $form->add_drop_down( 'exemption_type_issuer', $states ); ?>
        </span>
        </p>
        <p>
        <label for="business_type"><?php _e( 'Purchaser Business Type', 'LION' ); ?></label>
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
        <span id="other_business_type" class="hidden"><?php _e( 'Please explain:', 'LION' ); ?> <?php $form->add_text_box( 'other_exemption_type' ); ?></span>
        </p>
        <p>
        <label for="exemption_reason"><?php _e( 'Reason for exemption', 'LION' ); ?></label>
        <?php 
        $exemption_types = array(
        	'FederalGovernmentDepartment' => __( 'Federal Government Department', 'LION' ),
        	'StateOrLocalGovernmentName' => __( 'State or Local Government', 'LION' ),
        	'TribalGovernmentName' => __( 'Tribal Government', 'LION' ),
        	'FederalGovernmentDepartment' => __( 'Government', 'LION' ),
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
        $form->add_drop_down( 'reason_type', $exemption_types ); 
        ?>
        <span id="other_exemption_reason" class="hidden"><?php _e( 'Please explain:', 'LION' ); ?> <?php $form->add_text_box( 'other_reason_type' ); ?></span>
        </p>
        <?php
	}
		
	?>
	</body>
	</html>
	
	<?php
	die();
}
add_action( 'wp_ajax_it-exchange-advanced-us-tax-add-cert', 'it_exchange_advanced_us_taxes_addon_print_add_tax_cert' );
add_action( 'wp_ajax_nopriv_it-exchange-advanced-us-tax-add-cert', 'it_exchange_advanced_us_taxes_addon_print_add_tax_cert' );