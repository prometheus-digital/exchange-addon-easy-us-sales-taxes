/*global jQuery */
var AUSTCertManager = AUSTCertManager || {};

jQuery(document).ready(function($) {
	AUSTCertManager.ListCertsView = new AUSTCertManager.ListCertsView();
		
	$( '#it-exchange-advanced-us-tax-list-existing-certs' ).click( function() {
		console.log( 'list-existing-certs click' );
		AUSTCertManager.ListCertsView.render();
	});
	
    $( 'input[name="exempt_type"]' ).live( 'change', function() {
	    if ( 'single' == $( this ).val() ) {
			$( '#exempt_type_single_selected' ).show();
			$( '#exempt_type_bulk_selected' ).hide();
	    } else {
			$( '#exempt_type_single_selected' ).hide();
			$( '#exempt_type_bulk_selected' ).show();
		}
    });
    
    $( 'select[name="exemption_type"]' ).live( 'change', function() {
		console.log( $( this ).val() );
		if ( 'StateIssued' == $( this ).val() ) {
			$( '#exemption_type_issuer_other_div' ).hide();
			$( '#exemption_type_issuer_state_div' ).show();
		} else if ( 'ForeignDiplomat' == $( this ).val() ) {
			$( '#exemption_type_issuer_state_div' ).hide();
			$( '#exemption_type_issuer_other_div' ).show();
		} else {
			$( '#exemption_type_issuer_state_div' ).hide();
			$( '#exemption_type_issuer_other_div' ).hide();
		}
    });
    
    $( 'select[name="business_type"]' ).live( 'change', function() {
		console.log( $( this ).val() );
		if ( 'Other' == $( this ).val() ) {
			$( '#business_type_other_div' ).show();
		} else {
			$( '#business_type_other_div' ).hide();
		}
    });
});