/*global jQuery */
var AUSTCertManager = AUSTCertManager || {};

jQuery(document).ready(function($) {
	var certificate_manager = new AUSTCertManager.ListCertsView();
		
	$( '.it-exchange-super-widget, #it-exchange-easy-us-sales-taxes-exempt-label' ).on( 'click', '#it-exchange-easy-us-sales-tax-list-existing-certs', function( event ) {
		event.preventDefault();
		certificate_manager.render();
	});
	
    $( 'body' ).on( 'change', 'input[name="exempt_type"]', function() {
	    if ( 'single' == $( this ).val() ) {
			$( '#exempt_type_single_selected' ).show();
			$( '#exempt_type_bulk_selected' ).hide();
	    } else {
			$( '#exempt_type_single_selected' ).hide();
			$( '#exempt_type_bulk_selected' ).show();
		}
    });
    
    $( 'body' ).on( 'change', 'select[name="exemption_type"]', function() {
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
    
    $( 'body' ).on( 'change', 'select[name="business_type"]', function() {
		if ( 'Other' == $( this ).val() ) {
			$( '#business_type_other_div' ).show();
		} else {
			$( '#business_type_other_div' ).hide();
		}
    });
});