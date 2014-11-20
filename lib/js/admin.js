jQuery(document).ready(function($) {    
    $( '#it-exchange-add-on-easy-us-sales-taxes-settings' ).on( 'change', '#tax_cloud_api_id, #tax_cloud_api_key', function() {
		$( '#it-exchange-aust-taxcloud-verified' ).hide();
    });

    $( '#it-exchange-add-on-easy-us-sales-taxes-settings' ).on( 'change', '#business_address_1, #business_address_2, #business_city, #business_state, #business_zip_5, #business_zip_4', function() {
		$( '#it-exchange-aust-business-verified' ).hide();
    });
});