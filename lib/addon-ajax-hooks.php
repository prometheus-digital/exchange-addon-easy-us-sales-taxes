<?php
/**
 * Includes all of our AJAX functions
 * @since 1.0.0
 * @package exchange-addon-easy-us-sales-taxes
*/

/**
 * Ajax called from Backbone modal to get existing tax exempt certificates from TaxCloud.
 *
 * @since 1.0.0
*/
function it_exchange_easy_us_sales_taxes_addon_get_existing_certs() {	

	if ( ( ! $customer = it_exchange_get_current_customer() ) instanceof IT_Exchange_Customer ) {
	
		$errors[] = __( 'You must be logged in to get your Tax Exempt Certificates', 'LION' );
		
	} else {
	
		$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes' );

		$query = array(
			'apiLoginID'     => $settings['tax_cloud_api_id'],
			'apiKey'         => $settings['tax_cloud_api_key'],
			'customerID'     => $customer->id,
		);
		
		try {
		
			$soap_client = new SOAPClient( ITE_TAXCLOUD_WSDL, array( 'trace' => true, 'soap_version' => SOAP_1_2 ) );

			$result = $soap_client->GetExemptCertificates( $query );
			
			if ( is_soap_fault( $result ) ) {
				throw new Exception( $result->faultstring() );
			} else if ( isset( $result->GetExemptCertificatesResult->ResponseType ) ) {
				if ( 'OK' == $result->GetExemptCertificatesResult->ResponseType ) {
					$data = array();
					if ( !empty( $result->GetExemptCertificatesResult->ExemptCertificates->ExemptionCertificate ) ) {
						if ( !is_array( $result->GetExemptCertificatesResult->ExemptCertificates->ExemptionCertificate ) ) {
							$cert = $result->GetExemptCertificatesResult->ExemptCertificates->ExemptionCertificate;
							
							//We're skipping Single Purchase certificates, because
							//they're single purchase!
							if ( !empty( $cert->Detail->SinglePurchase ) )
								return;
						
							$new_cert['CertificateID']            = $cert->CertificateID;
							$new_cert['PurchaserFirstName']       = $cert->Detail->PurchaserFirstName;
							$new_cert['PurchaserLastName']        = !empty( $cert->Detail->PurchaserLastName ) ? $cert->Detail->PurchaserLastName : '';
							$new_cert['ExemptStates']             = it_exchange_easy_us_sales_taxes_addon_convert_exempt_states_to_string( $cert->Detail->ExemptStates );
							$new_cert['CreatedDate']              = it_exchange_easy_us_sales_taxes_addon_convert_exempt_createdate_to_date_format( $cert->Detail->CreatedDate );
							$new_cert['PurchaserExemptionReason'] = it_exchange_easy_us_sales_taxes_addon_convert_reason_to_readable_string( $cert->Detail->PurchaserExemptionReason, $cert->Detail->PurchaserExemptionReason, $cert->Detail->PurchaserExemptionReasonValue );
							$data[] = $new_cert;
							
						} else {
							foreach ( $result->GetExemptCertificatesResult->ExemptCertificates->ExemptionCertificate as $cert ) {
							
								//We're skipping Single Purchase certificates, because
								//they're single purchase!
								if ( !empty( $cert->Detail->SinglePurchase ) )
									continue;
							
								$new_cert['CertificateID']            = $cert->CertificateID;
								$new_cert['PurchaserFirstName']       = $cert->Detail->PurchaserFirstName;
								$new_cert['PurchaserLastName']        = !empty( $cert->Detail->PurchaserLastName ) ? $cert->Detail->PurchaserLastName : '';
								$new_cert['ExemptStates']             = it_exchange_easy_us_sales_taxes_addon_convert_exempt_states_to_string( $cert->Detail->ExemptStates );
								$new_cert['CreatedDate']              = it_exchange_easy_us_sales_taxes_addon_convert_exempt_createdate_to_date_format( $cert->Detail->CreatedDate );
								$new_cert['PurchaserExemptionReason'] = it_exchange_easy_us_sales_taxes_addon_convert_reason_to_readable_string( $cert->Detail->PurchaserExemptionReason, $cert->Detail->PurchaserExemptionReason, $cert->Detail->PurchaserExemptionReasonValue );
								$data[] = $new_cert;
							}
						}
					}
					wp_send_json_success( $data );
				} else {
					$local_errors = array();
					foreach( $result->GetExemptCertificatesResult->Messages as $message ) {
						$local_errors[] = $message->Message;
					}
					throw new Exception( implode( ',', $local_errors ) );
				}
			} else {
				throw new Exception( __( 'Unknown error when adding certificate on TaxCloud.net', 'LION' ) );
			}
			
		}
	    catch( Exception $e ) {
			$exchange = it_exchange_get_option( 'settings_general' );
			$errors[] = sprintf( __( 'Unable to add certificate on TaxCloud.net: %s', 'LION' ), $e->getMessage() );
	    }
		
	}
	
	wp_send_json_error( $errors );

}
add_action( 'wp_ajax_it-exchange-aust-existing-get-existing-certs', 'it_exchange_easy_us_sales_taxes_addon_get_existing_certs' );
add_action( 'wp_ajax_nopriv_it-exchange-aust-existing-get-existing-certs', 'it_exchange_easy_us_sales_taxes_addon_get_existing_certs' );

/**
 * Ajax called from Backbone modal to remove existing tax exempt certificates from TaxCloud.
 *
 * @since 1.0.0
*/
function it_exchange_easy_us_sales_taxes_addon_remove_existing_cert() {

	if ( !is_user_logged_in() ) {
	
		$errors[] = __( 'You must be logged in to remove your Tax Exempt Certificates', 'LION' );
		
	} else {
	
		if ( !empty( $_POST ) ) {
	
			$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes' );
	
			$query = array(
				'apiLoginID'     => $settings['tax_cloud_api_id'],
				'apiKey'         => $settings['tax_cloud_api_key'],
				'certificateID'  => $_POST['id'],
			);
		    	
			try {
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body' => json_encode( $query ),
			    );
				$result = wp_remote_post( ITE_TAXCLOUD_API . 'DeleteExemptCertificate', $args );
			
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				} else if ( !empty( $result['body'] ) ) {
					$body = json_decode( $result['body'] );
					if ( 3 == $body->ResponseType ) {

						$tax_cloud_session = it_exchange_get_session_data( 'addon_easy_us_sales_taxes' );
						unset( $tax_cloud_session['exempt_certificate'], $tax_cloud_session['new_certificate'] );
						it_exchange_update_session_data( 'addon_easy_us_sales_taxes', $tax_cloud_session );

						it_exchange_easy_us_sales_taxes_addon_get_taxes_for_cart( false, true );

						wp_send_json_success();
					} else {
						$local_errors = array();
						foreach( $body->Messages as $message ) {
							$local_errors[] = $message->ResponseType . ' ' . $message->Message;
						}
						throw new Exception( implode( ',', $local_errors ) );
					}
				} else {
					throw new Exception( __( 'Unknown error when trying to authorize and capture a transaction with TaxCloud.net', 'LION' ) );
				}
			}
		    catch( Exception $e ) {
				$exchange = it_exchange_get_option( 'settings_general' );
				$errors = sprintf( __( 'Unable to authorize transaction with TaxCloud.net: %s', 'LION' ), $e->getMessage() );
		    }
		    
		}
	
	}

	wp_send_json_error( $errors );

}
add_action( 'wp_ajax_it-exchange-aust-existing-remove-existing-cert', 'it_exchange_easy_us_sales_taxes_addon_remove_existing_cert' );
add_action( 'wp_ajax_nopriv_it-exchange-aust-existing-remove-existing-cert', 'it_exchange_easy_us_sales_taxes_addon_remove_existing_cert' );

/**
 * Ajax called from Backbone modal to use a given tax exempt certificates from TaxCloud.
 *
 * @since 1.0.0
*/
function it_exchange_easy_us_sales_taxes_addon_use_existing_cert() {

	$errors = array();

	if ( !is_user_logged_in() ) {
	
		$errors[] = __( 'You must be logged in to use your Tax Exempt Certificates', 'LION' );
		
	} else {
	
		if ( !empty( $_POST ) && !empty( $_POST['cert_id'] ) ) {
			
			$tax_cloud_session = it_exchange_get_session_data( 'addon_easy_us_sales_taxes' );
			$tax_cloud_session['exempt_certificate'] = array( 'CertificateID' => $_POST['cert_id'] );
			$tax_cloud_session['new_certificate'] = true;
			it_exchange_update_session_data( 'addon_easy_us_sales_taxes', $tax_cloud_session );
			it_exchange_easy_us_sales_taxes_addon_get_taxes_for_cart( false, true );
			
			wp_send_json_success();
		    
		}
	
	}

	wp_send_json_error( $errors );

}
add_action( 'wp_ajax_it-exchange-aust-existing-use-existing-cert', 'it_exchange_easy_us_sales_taxes_addon_use_existing_cert' );
add_action( 'wp_ajax_nopriv_it-exchange-aust-existing-use-existing-cert', 'it_exchange_easy_us_sales_taxes_addon_use_existing_cert' );

/**
 * Ajax called from Backbone modal to add new tax exempt certificates to TaxCloud.
 *
 * @since 1.0.0
*/
function it_exchange_easy_us_sales_taxes_addon_add_cert() {	

	$errors = array();
	
	if ( !is_user_logged_in() ) {

		wp_send_json_error( array( __( 'You must be logged in to add new Tax Exempt Certificates', 'LION' ) ) );
	}

	if ( empty( $_POST ) ) {
		wp_send_json_error( array() );
	}

	$settings = it_exchange_get_option( 'addon_easy_us_sales_taxes' );
	$customer = it_exchange_get_current_customer();

    if ( empty( $_POST['exempt_state'] ) ) {
        $errors[] = __( 'You must select a Certificate of Exemption State.', 'LION' );
    } else {
        $detail['exempt_state'] = $_POST['exempt_state'];
    }

    if ( empty( $_POST['exempt_type'] ) ) {
        $errors[] = __( 'You must specify the Exemption Type', 'LION' );
    } else {
        $detail['exempt_type'] = $_POST['exempt_type'];
    }

    $detail['order_number'] = empty( $_POST['order_number'] ) ? '' : $_POST['order_number'];

    if ( empty( $_POST['purchaser_name'] ) ) {
        $errors[] = __( 'You must include the Purchaser Name', 'LION' );
    } else {
        $detail['purchaser_name'] = $_POST['purchaser_name'];
    }

    if ( empty( $_POST['business_address'] ) ) {
        $errors[] = __( 'You must include a Business Address', 'LION' );
    } else {
        $detail['business_address'] = $_POST['business_address'];
    }

    if ( empty( $_POST['business_city'] ) ) {
        $errors[] = __( 'You must include a City', 'LION' );
    } else {
        $detail['business_city'] = $_POST['business_city'];
    }

    if ( empty( $_POST['business_state'] ) ) {
        $errors[] = __( 'You must include a State', 'LION' );
    } else {
        $detail['business_state'] = $_POST['business_state'];
    }

    if ( empty( $_POST['business_zip_5'] ) ) {
        $errors[] = __( 'You must include a Zip Code', 'LION' );
    } else {
        $detail['business_zip_5'] = $_POST['business_zip_5'];
    }

    if ( empty( $_POST['exemption_type'] ) ) {
        $errors[] = __( 'You must include an Exemption Type', 'LION' );
        $detail['exemption_type'] = 'error';
    } else {
        $detail['exemption_type'] = $_POST['exemption_type'];
    }

    if ( empty( $_POST['exemption_type_number'] ) ) {
        $errors[] = __( 'You must include an Exemption Number', 'LION' );
    } else {
        $detail['exemption_type_number'] = $_POST['exemption_type_number'];
    }

    if ( 'StateIssued' === $detail['exemption_type'] ) {
        if ( empty( $_POST['exemption_type_issuer_state'] ) ) {
            $errors[] = __( 'You must specify who the state the exemption was issued by.', 'LION' );
        } else {
            $detail['exemption_type_issuer'] = $_POST['exemption_type_issuer_state'];
        }
    } else if ( 'ForeignDiplomat' ===  $detail['exemption_type'] ) {
        if ( empty( $_POST['exemption_type_issuer_other'] ) ) {
            $errors[] = __( 'You must specify who the exemption was issued by.', 'LION' );
        } else {
            $detail['exemption_type_issuer'] = $_POST['exemption_type_issuer_other'];
        }
    } else {
        $detail['exemption_type_issuer'] = '';
    }

    if ( empty( $_POST['business_type'] ) ) {
        $errors[] = __( 'You must include an Exemption Number', 'LION' );
    } else {
        $detail['business_type'] = $_POST['business_type'];
    }

    if ( 'Other' === $detail['business_type'] ) {
        if ( empty( $_POST['business_type_other'] ) ) {
            $errors[] = __( 'You must specify a Business Type', 'LION' );
        } else {
            $detail['business_type_other'] = $_POST['business_type_other'];
        }
    } else {
        $detail['business_type_other'] = '';
    }

    if ( empty( $_POST['exemption_reason'] ) ) {
        $errors[] = __( 'You must select a Reason for Exemption', 'LION' );
    } else {
        $detail['exemption_reason'] = $_POST['exemption_reason'];
    }

    if ( empty( $_POST['exemption_reason_value'] ) ) {
        $errors[] = __( 'You must explain the Reason for Exemption (such as a name or ID associated with the reason)', 'LION' );
    } else {
        $detail['exemption_reason_value'] = $_POST['exemption_reason_value'];
    }

	if ( count( $errors ) !== 0 ) {
		wp_send_json_error( $errors );
	}

	$query = array(
		'apiLoginID' => $settings['tax_cloud_api_id'],
		'apiKey'     => $settings['tax_cloud_api_key'],
		'customerID' => $customer->ID,
		'exemptCert' => array(
			'CertificateID' => NULL, //automatically generated by TaxCloud API
			'Detail'        => array(
				'ExemptStates'              => array(
					array( 'StateAbbr' => $detail['exempt_state'] ),
				),
				'SinglePurchase'            => ( 'single' === $_POST['exempt_type'] ) ? true : false,
				'SinglePurchaseOrderNumber' => $detail['order_number'],
				'PurchaserFirstName'        => $detail['purchaser_name'],
				'PurchaserLastName'         => '',
				'PurchaserTitle'            => '',
				'PurchaserAddress1'         => $detail['business_address'],
				'PurchaserAddress2'         => '',
				'PurchaserCity'             => $detail['business_city'],
				'PurchaserState'            => $detail['business_state'],
				'PurchaserZip'              => $detail['business_zip_5'],
				'PurchaserTaxID'            => array(
					'TaxType'      => $detail['exemption_type'],
					'IDNumber'     => $detail['exemption_type_number'],
					'StateOfIssue' => $detail['exemption_type_issuer'],
				),
				'PurchaserBusinessType'           => $detail['business_type'],
				'PurchaserBusinessTypeOtherValue' => $detail['business_type_other'],
				'PurchaserExemptionReason'        => $detail['exemption_reason'],
				'PurchaserExemptionReasonValue'   => $detail['exemption_reason_value'],
				'CreatedDate'                     => gmdate( DATE_ATOM ),
			),
		),
	);


	$tax_cloud_session = it_exchange_get_session_data( 'addon_easy_us_sales_taxes' );

	if ( 'single' === $_POST['exempt_type'] ) {

		// w/ TaxCloud, if it is a single use certificate, we do not add it to their database
		// with the AddExemptCertificate API, it gets added to the tax query
		// in it_exchange_easy_us_sales_taxes_addon_get_taxes_for_cart()
		$cert = $query['exemptCert'];
		unset( $cert['CertificateID']);
		$tax_cloud_session['exempt_certificate'] = $cert;
		$tax_cloud_session['new_certificate'] = true;
		it_exchange_update_session_data( 'addon_easy_us_sales_taxes', $tax_cloud_session );
		it_exchange_easy_us_sales_taxes_addon_get_taxes_for_cart( false, true );
		wp_send_json_success( 'it-aust-single-cert-added' );

	} else {

		try {
			$args = array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body' => json_encode( $query ),
		    );
			$result = wp_remote_post( ITE_TAXCLOUD_API . 'AddExemptCertificate', $args );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			} else if ( !empty( $result['body'] ) ) {
				$body = json_decode( $result['body'] );
				if ( 0 !== (int) $body->ResponseType ) {
					//add the certificate ID to a session variable
					$tax_cloud_session['exempt_certificate'] = array( 'CertificateID' => $body->CertificateID );
					$tax_cloud_session['new_certificate'] = true;
					it_exchange_update_session_data( 'addon_easy_us_sales_taxes', $tax_cloud_session );
					it_exchange_easy_us_sales_taxes_addon_get_taxes_for_cart( false, true );
					wp_send_json_success( $body->CertificateID );
				} else {
					$local_errors = array();
					foreach( $body->Messages as $message ) {
						$local_errors[] = $message->Message;
					}
					throw new Exception( implode( ',', $local_errors ) );
				}
			} else {
				throw new Exception( __( 'Unknown error when adding certificate on TaxCloud.net', 'LION' ) );
			}

		}
	    catch( Exception $e ) {
			$exchange = it_exchange_get_option( 'settings_general' );
			$errors[] = sprintf( __( 'Unable to add certificate on TaxCloud.net: %s', 'LION' ), $e->getMessage() );
	    }

    }
	
	wp_send_json_error( $errors );
}
add_action( 'wp_ajax_it-exchange-easy-us-sales-taxes-add-cert', 'it_exchange_easy_us_sales_taxes_addon_add_cert' );
add_action( 'wp_ajax_nopriv_it-exchange-easy-us-sales-taxes-add-cert', 'it_exchange_easy_us_sales_taxes_addon_add_cert' );
