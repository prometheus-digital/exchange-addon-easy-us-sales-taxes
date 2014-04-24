jQuery(document).ready(function($) {    
    $( '#tax_cloud_api_id, #tax_cloud_api_key' ).live( 'change', function() {
		$( '#it-exchange-aust-taxcloud-verified' ).hide();
    });

    $( '#business_address_1, #business_address_2, #business_city, #business_state, #business_zip_5, #business_zip_4' ).live( 'change', function() {
		$( '#it-exchange-aust-business-verified' ).hide();
    });
});