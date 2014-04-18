var AUSTCertManager = AUSTCertManager || {};

(function ($){
	'use strict';

	AUSTCertManager.ExistingCertificate = Backbone.Model.extend({});
	
	AUSTCertManager.NewCertificate = Backbone.Model.extend({
		defaults: {
			exempt_state:           '',
			exempt_type:            'bulk',
			purchaser_name:         '',
			business_address:       '',
			business_city:          '',
			business_state:         '',
			business_zip_5:         '',
			exemption_id_type:      'FEIN',
			exemption_id_number:    '',
			exemption_id_issuer:    '',
			business_type:          '',
			business_type_other:    '',
			exemption_reason:       '',
			exemption_reason_other: '',
		}
	});
	
}(jQuery));
