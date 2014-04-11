jQuery(document).ready(function($) {
    $( 'input[name="exempt_type"]' ).change( function() {
	    if ( 'single' == $( this ).val() ) {
			$( '#exempt_type_single_selected' ).show();
			$( '#exempt_type_bulk_selected' ).hide();
	    } else {
			$( '#exempt_type_single_selected' ).hide();
			$( '#exempt_type_bulk_selected' ).show();
		}
    });
    
    $( 'select[name="exemption_type"]' ).change( function() {
    console.log( $( this ).val() );
	   if ( 'StateIssued' == $( this ).val() ) {
			$( '#exemption_id_issued_by' ).show();
	   } else {
			$( '#exemption_id_issued_by' ).hide();
	   }
    });
    
    $( 'select[name="business_type"]' ).change( function() {
    console.log( $( this ).val() );
	   if ( 'Other' == $( this ).val() ) {
			$( '#other_business_type' ).show();
	   } else {
			$( '#other_business_type' ).hide();
	   }
    });
    
    $( 'select[name="exemption_reason"]' ).change( function() {
    console.log( $( this ).val() );
	   if ( 'Other' == $( this ).val() ) {
			$( '#other_exemption_reason' ).show();
	   } else {
			$( '#other_exemption_reason' ).hide();
	   }
    });
});