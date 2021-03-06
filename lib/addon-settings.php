<?php
/**
 * iThemes Exchange Easy U.S. Sales Taxes Add-on
 * @package exchange-addon-easy-us-sales-taxes
 * @since 1.0.0
*/

/**
 * Call back for settings page
 *
 * This is set in options array when registering the add-on and called from it_exchange_enable_addon()
 *
 * @since 1.0.0
 * @return void
*/
function it_exchange_easy_us_sales_taxes_settings_callback() {
	$IT_Exchange_Easy_US_Sales_Taxes_Add_On = new IT_Exchange_Easy_US_Sales_Taxes_Add_On();
	$IT_Exchange_Easy_US_Sales_Taxes_Add_On->print_settings_page();
}

/**
 * Sets the default options for customer pricing settings
 *
 * @since 1.0.0
 * @return array settings
*/
function it_exchange_easy_us_sales_taxes_default_settings( $defaults ) {
	$defaults = array(
		'tax_cloud_api_id'     => '',
		'tax_cloud_api_key'    => '',
		'tax_cloud_verified'   => false,
		'usps_user_id'         => '',
		'business_address_1'   => '',
		'business_address_2'   => '',
		'business_city'        => '',
		'business_state'       => '',
		'business_zip_5'       => '',
		'business_zip_4'       => '',
		'business_verified'    => false,
		'tax_exemptions'       => false,
		'default_tax_class'    => '',
	);
	return $defaults;
}
add_filter( 'it_storage_get_defaults_exchange_addon_easy_us_sales_taxes', 'it_exchange_easy_us_sales_taxes_default_settings' );

class IT_Exchange_Easy_US_Sales_Taxes_Add_On {

	/**
	 * @var boolean $_is_admin true or false
	 * @since 1.0.0
	*/
	var $_is_admin;

	/**
	 * @var string $_current_page Current $_GET['page'] value
	 * @since 1.0.0
	*/
	var $_current_page;

	/**
	 * @var string $_current_add_on Current $_GET['add-on-settings'] value
	 * @since 1.0.0
	*/
	var $_current_add_on;

	/**
	 * @var string $status_message will be displayed if not empty
	 * @since 1.0.0
	*/
	var $status_message;

	/**
	 * @var string $error_message will be displayed if not empty
	 * @since 1.0.0
	*/
	var $error_message;

	/**
 	 * Class constructor
	 *
	 * Sets up the class.
	 * @since 1.0.0
	 * @return void
	*/
	function __construct() {
		$this->_is_admin       = is_admin();
		$this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
		$this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

		if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'easy-us-sales-taxes' == $this->_current_add_on ) {
			add_action( 'it_exchange_save_add_on_settings_easy_us_sales_taxes', array( $this, 'save_settings' ) );
			do_action( 'it_exchange_save_add_on_settings_easy_us_sales_taxes' );
		}
	}

	/**
 	 * Class deprecated constructor
	 *
	 * Sets up the class.
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Easy_US_Sales_Taxes_Add_On() {
		self::__construct();
	}

	function print_settings_page() {
		$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes', true );
	
		$form_values  = empty( $this->error_message ) ? $settings : ITForm::get_post_data();
		$form_options = array(
			'id'      => apply_filters( 'it_exchange_add_on_easy_us_sales_taxes', 'it-exchange-add-on-easy-us-sales-taxes-settings' ),
			'enctype' => apply_filters( 'it_exchange_add_on_easy_us_sales_taxes_settings_form_enctype', false ),
			'action'  => 'admin.php?page=it-exchange-addons&add-on-settings=easy-us-sales-taxes',
		);
		$form         = new ITForm( $form_values, array( 'prefix' => 'it-exchange-add-on-easy-us-sales-taxes' ) );

		if ( ! empty ( $this->status_message ) )
			ITUtility::show_status_message( $this->status_message );
		if ( ! empty( $this->error_message ) )
			ITUtility::show_error_message( $this->error_message );

		?>
		<div class="wrap">
			<?php screen_icon( 'it-exchange' ); ?>
			<h2><?php _e( 'Easy U.S. Sales Taxes Settings', 'LION' ); ?></h2>

			<?php do_action( 'it_exchange_easy_us_sales_taxes_settings_page_top' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

			<?php $form->start_form( $form_options, 'it-exchange-easy-us-sales-taxes-settings' ); ?>
				<?php do_action( 'it_exchange_easy_us_sales_taxes_settings_form_top' ); ?>
				<?php $this->get_easy_us_sales_taxes_form_table( $form, $form_values ); ?>
				<?php do_action( 'it_exchange_easy_us_sales_taxes_settings_form_bottom' ); ?>
				<p class="submit">
					<?php $form->add_submit( 'submit', array( 'value' => __( 'Save Changes', 'LION' ), 'class' => 'button button-primary button-large' ) ); ?>
				</p>
			<?php $form->end_form(); ?>
			<?php do_action( 'it_exchange_easy_us_sales_taxes_settings_page_bottom' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
		</div>
		<?php
	}

	function get_easy_us_sales_taxes_form_table( $form, $settings = array() ) {
		if ( !empty( $settings ) )
			foreach ( $settings as $key => $var )
				$form->set_option( $key, $var );
		?>
		
        <div class="it-exchange-addon-settings it-exchange-easy-us-sales-taxes-addon-settings">
            <h4>
            	<?php _e( 'TaxCloud Settings', 'LION' ) ?> 
                <?php 
                if ( !empty( $settings['tax_cloud_verified'] ) )
               		$hidden_class = '';
               	else
               		$hidden_class = 'hidden';
               		
               	echo '<img src="' . ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/images/check.png" class="check ' . $hidden_class . '" id="it-exchange-aust-taxcloud-verified" title="' . __( 'TaxCloud Settings Verified', 'LION' ) . '" height="15" >';
                ?>
            </h4>
            <p>
                <label for="easy-us-sales-taxes-tax_cloud_api_id"><?php _e( 'TaxCloud API ID', 'LION' ) ?> <span class="tip" title="<?php _e( 'At TaxCloud.net, go to Websites click Add website to obtain the required API ID and API Key.', 'LION' ); ?>">i</span> </label>
                <?php $form->add_text_box( 'tax_cloud_api_id' ); ?>
            </p>
            <p>
                <label for="easy-us-sales-taxes-tax_cloud_api_key"><?php _e( 'TaxCloud API Key', 'LION' ) ?> <span class="tip" title="<?php _e( 'At TaxCloud.net, go to Websites click Add website to obtain the required API ID and API Key.', 'LION' ); ?>">i</span></label>
                <?php $form->add_text_box( 'tax_cloud_api_key' ); ?>
            </p>
            
            <h4><?php _e( 'USPS Settings', 'LION' ) ?></h4>
            <p>
                <label for="easy-us-sales-taxes-usps_user_id"><?php _e( 'USPS API User ID', 'LION' ) ?> <span class="tip" title="<?php _e( 'Sign up for a USPS WebTools User ID at https://www.usps.com/business/web-tools-apis/welcome.htm and paste it into this field.', 'LION' ); ?>">i</span></label>
                <?php $form->add_text_box( 'usps_user_id' ); ?>
            </p>
            
            <h4>
            	<?php _e( 'Primary Business Address', 'LION' ) ?>
                <?php 
                if ( !empty( $settings['business_verified'] ) )
               		$hidden_class = '';
               	else
               		$hidden_class = 'hidden';
               		
               	echo '<img src="' . ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/images/check.png" class="check ' . $hidden_class . '" id="it-exchange-aust-business-verified" title="' . __( 'Business Address Verified', 'LION' ) . '" height="15">';
                ?>
            </h4>
            <p>
                <label for="easy-us-sales-taxes-business_address_1"><?php _e( 'Address 1', 'LION' ) ?></label>
                <?php $form->add_text_box( 'business_address_1' ); ?>
            </p>
            <p>
                <label for="easy-us-sales-taxes-business_address_2"><?php _e( 'Address 2', 'LION' ) ?></label>
                <?php $form->add_text_box( 'business_address_2' ); ?>
            </p>
            <p>
                <label for="easy-us-sales-taxes-business_city"><?php _e( 'City', 'LION' ) ?></label>
                <?php $form->add_text_box( 'business_city' ); ?>
            </p>
            <p>
                <label for="easy-us-sales-taxes-business_state"><?php _e( 'State', 'LION' ) ?></label>
                <?php 
                $states = it_exchange_get_data_set( 'states', array( 'country' => 'US', 'include-territories' => true ) );
                $form->add_drop_down( 'business_state', $states ); 
                ?>
            </p>
            <p>
                <label for="easy-us-sales-taxes-business_zip_5"><?php _e( 'Zip Code', 'LION' ) ?></label>
                <?php $form->add_text_box( 'business_zip_5' ); ?> - <?php $form->add_text_box( 'business_zip_4' ); ?>
            </p>
            
            <h4><?php _e( 'General Settings', 'LION' ) ?></h4>
            <p>
                <?php $form->add_check_box( 'tax_exemptions' ); ?>
                <label for="tax_exemptions"><?php _e( 'Enable Tax Exemptions?', 'LION' ) ?></label>
            </p>
            <p>
                <label for="easy-us-sales-taxes-us-tic"><?php _e( 'Default Tax Class', 'LION' ) ?></label>
                
                <script type="text/javascript">
					//currentTic must be declared/set, even if TIC has not already been specified.
					var currentTic = "<?php echo empty( $settings['us-tic'] ) ? '' : esc_js( $settings['us-tic'] ); ?>";
					//the ID of the HTML form field to be replaced
					var fieldID = "us-tic";
				</script>
			
                <?php $form->add_text_box( 'us-tic' ); ?> 
            </p>

		</div>
		<?php
	}

	/**
	 * Save settings
	 *
	 * @since 1.0.0
	 * @return void
	*/
    function save_settings() {
    	global $new_values; //We set this as global here to modify it in the error check
    	
        $defaults = it_exchange_get_option( 'addon_easy_us_sales_taxes' );
        $new_values = wp_parse_args( ITForm::get_post_data(), $defaults );
        
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-easy-us-sales-taxes-settings' ) ) {
            $this->error_message = __( 'Error. Please try again', 'LION' );
            return;
        }

        $errors = apply_filters( 'it_exchange_add_on_easy_us_sales_taxes_validate_settings', $this->get_form_errors( $new_values ), $new_values );
        if ( ! $errors && it_exchange_save_option( 'addon_easy_us_sales_taxes', $new_values ) ) {
            ITUtility::show_status_message( __( 'Settings saved.', 'LION' ) );
        } else if ( $errors ) {
            $errors = implode( '<br />', $errors );
            $this->error_message = $errors;
        } else {
            $this->status_message = __( 'Settings not saved.', 'LION' );
        }
    }

    /**
     * Validates for values
     *
     * Returns string of errors if anything is invalid
     *
     * @since 0.1.0
     * @return void
    */
    public function get_form_errors( $values ) {
    	global $new_values;

        $errors = array();
        if ( empty( $values['tax_cloud_api_id'] ) )
            $errors[] = __( 'Please include your TaxCloud API ID', 'LION' );
        if ( empty( $values['tax_cloud_api_key'] ) )
            $errors[] = __( 'Please include your TaxCloud API Key', 'LION' );
        
		//Verify TaxCloud API Connectivity
        if ( empty( $errors ) ) {
	        try {
	        	$body = array(
	        		'apiLoginId' => $values['tax_cloud_api_id'],
	        		'apiKey'     => $values['tax_cloud_api_key'],
	        	);
	        	$args = array(
					'body' => $body,
			    );
	        	$result = wp_remote_post( ITE_TAXCLOUD_API . 'Ping', $args );
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				} else if ( !empty( $result['body'] ) ) {
					$body = json_decode( $result['body'] );
					if ( !empty( $body->Messages ) ) {
						foreach( $body->Messages as $message ) {
							$errors[] = sprintf( __( 'TaxCloud Setting Error #%d: %s', 'LION' ), $message->ResponseType, $message->Message );
						}
					}
				} else {
					$errors[] = __( 'Unable to determine TaxCloud API status', 'LION' );
				}
	        } 
	        catch( Exception $e ) {
				$errors[] = $e->getMessage();
	        }
        }
        
        //If we're still empty, the TaxCloud API was verified
        if ( empty( $errors ) ) {
			$new_values['tax_cloud_verified'] = true;
        } else {
			$new_values['tax_cloud_verified'] = false;
        }
            
        if ( empty( $values['usps_user_id'] ) ) {
            $errors[] = __( 'Please include your USPS API Key', 'LION' );
        } else {
	        $verify_business_address['uspsUserId'] = $values['usps_user_id'];
        }
            
        if ( empty( $values['business_address_1'] ) ) {
            $errors[] = __( 'Please include your Business Address', 'LION' );
        } else {
            $verify_business_address['address1'] = $values['business_address_1'];
        }
        if ( !empty( $values['business_address_2'] ) ) {
            $verify_business_address['address2'] = $values['business_address_2'];
        }
        if ( empty( $values['business_city'] ) ) {
            $errors[] = __( 'Please include your Business City', 'LION' );
        } else {
            $verify_business_address['city'] = $values['business_city'];
        }
        if ( empty( $values['business_state'] ) ) {
            $errors[] = __( 'Please include your Business State', 'LION' );
        } else {
            $verify_business_address['state'] = $values['business_state'];
        }
        if ( empty( $values['business_zip_5'] ) ) {
            $errors[] = __( 'Please include your Business Zip Code', 'LION' );
        } else {
            $verify_business_address['zip5'] = $values['business_zip_5'];
        }
        if ( !empty( $values['business_zip_4'] ) ) {
            $verify_business_address['zip4'] = $values['business_zip_4'];
        }
       
		//Verify Business Address
        if ( empty( $errors ) ) {
	        try {
	        	$args = array(
	        		'headers' => array(
	        			'Content-Type' => 'application/json',
	        		),
					'body' => json_encode( $verify_business_address ),
			    );
	        	$result = wp_remote_post( ITE_TAXCLOUD_API . 'VerifyAddress', $args );
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				} else if ( !empty( $result['body'] ) ) {
					$body = json_decode( $result['body'] );
					if ( 0 == $body->ErrNumber ) {
						$new_values['business_address_1'] = $body->Address1;
						$new_values['business_address_2'] = $body->Address2;
						$new_values['business_city']      = $body->City;
						$new_values['business_state']     = $body->State;
						$new_values['business_zip_5']     = $body->Zip5;
						$new_values['business_zip_4']     = $body->Zip4;
						$new_values['business_verified'] = true;
					} else if ( 97 == $body->ErrNumber ) {
						//do nothing, this is a non-blocking error
						$new_values['business_address_1'] = $values['business_address_1'];
						$new_values['business_address_2'] = $values['business_address_2'];
						$new_values['business_city']      = $values['business_city'];
						$new_values['business_state']     = $values['business_state'];
						$new_values['business_zip_5']     = $values['business_zip_5'];
					} else {
						throw new Exception( sprintf( __( 'Unable to verify Business Address: %s', 'LION' ), $body->ErrDescription ) );
					}
				} else {
					throw new Exception( __( 'Unable to verify Business Address: Unknown Error', 'LION' ) );
				}
	        } 
	        catch( Exception $e ) {
				$errors[] = $e->getMessage();
				$new_values['business_verified'] = false;
	        }
        }

        return $errors;
    }
}
