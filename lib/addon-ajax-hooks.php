<?php

/**
 * Ajax called from Thickbox to show the User's Add Product Screen.
 *
 * @since 1.0.0
*/
function it_exchange_advanced_us_taxes_addon_get_certs() {	
	error_log( 'it_exchange_advanced_us_taxes_addon_get_certs' );
	//wp_send_json_error( 'it_exchange_advanced_us_taxes_addon_print_tax_certs' );
	wp_send_json_success( 'it_exchange_advanced_us_taxes_addon_get_certs' );
}
add_action( 'wp_ajax_it-exchange-advanced-us-taxes-get-certs', 'it_exchange_advanced_us_taxes_addon_get_certs' );

/**
 * Ajax called from Thickbox to show the User's Add Product Screen.
 *
 * @since 1.0.0
*/
function it_exchange_advanced_us_taxes_addon_add_cert() {	
	
	$output = '';
	$errors = array();
	
	if ( !is_user_logged_in() ) {
	
		$errors[] = __( 'You must be logged in to add new Tax Exempt Certificates', 'LION' );
		
	} else {
	
		if ( ! empty( $_POST ) ) {
			
			if ( wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-advanced-us-taxes-new-cert' ) ) {
		
				$settings = it_exchange_get_option( 'addon_advanced_us_taxes' );
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
						
		        if ( !empty( $_POST['order_number'] ) ) {
		            $detail['order_number'] = empty( $_POST['order_number'] ) ? '' : $_POST['order_number'];
		        }
						
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
						        
		        if ( 'StateIssued' == $detail['exemption_type'] ) {
			        if ( empty( $_POST['exemption_type_issuer_state'] ) ) {
			            $errors[] = __( 'You must specify who the state the exemption was issued by.', 'LION' );
			        } else {
			            $detail['exemption_type_issuer'] = $_POST['exemption_type_issuer_state'];
			        }
		        } else if ( 'ForeignDiplomat' ==  $detail['exemption_type'] ) {
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
								        
		        if ( 'Other' == $detail['business_type'] ) {
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
		        		        
		        if ( empty( $errors ) ) {
			
					$query = array(
						'apiLoginID' => $settings['tax_cloud_api_id'],
						'apiKey'     => $settings['tax_cloud_api_key'],
						'customerID' => $customer->ID,
						'exemptCert' => array(
							'CertificateID' => NULL, //automatically generated by TaxCloud API
							'Detail'        => array(
								'ExemptStates'              => array( $detail['exempt_state'] ),
								'SinglePurchase'            => ( 'single' == $_POST['exempt_type'] ) ? true : false,
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
					//wp_send_json_success( $query );
			
					//$client = new SOAPClient('https://api.taxcloud.net/1.0/?wsdl', array('trace' => true, 'soap_version' => SOAP_1_2));

					//$response = $client->AddExemptCertificate( $query );
					//wp_send_json_success( $response );
					
					if ( 'single' == $_POST['exempt_type'] ) {
						
						//add the query to a session variable
						
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
								if ( 0 != $body->ResponseType ) {
									//add the certificate ID to a session variable
									wp_send_json_success( $body->ExemptCertificates );
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
				}		
			} else {
				
				$errors[] = __( 'Unable to verify security token, please try again', 'LION' );
				
			}

		}
		
	}
	
	wp_send_json_error( $errors );
}
add_action( 'wp_ajax_it-exchange-advanced-us-taxes-add-cert', 'it_exchange_advanced_us_taxes_addon_add_cert' );