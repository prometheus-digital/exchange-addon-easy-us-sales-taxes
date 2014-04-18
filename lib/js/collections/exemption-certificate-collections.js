var AUSTCertManager = AUSTCertManager || {};

(function ($) {
	'use strict';

	/**
	 * Variants Collection
	 * Does not include variant values
	*/
	AUSTCertManager.ExistingCertificates = Backbone.Collection.extend({
		model: AUSTCertManager.ExistingCertificate
	});
	
}(jQuery));