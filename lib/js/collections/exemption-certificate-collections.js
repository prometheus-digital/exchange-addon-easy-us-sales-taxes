var AUSTCertManager = AUSTCertManager || {};

(function ($) {
	'use strict';

	/**
	 * Variants Collection
	 * Does not include variant values
	*/
	AUSTCertManager.ExistingCertificates = Backbone.Collection.extend({
		model: AUSTCertManager.ExistingCertificate,
		//url: ite_aust_ajax.ajax_url,,
		
		/**
		 * Overrides Backbone.Collection.sync
		 *
		 * @param {String} method
		 * @param {Backbone.Model} model
		 * @param {Object} [options={}]
		 * @returns {Promise}
		 */
		sync: function( method, model, options ) {
			var args, fallback;

			// Overload the read method so ExistingCertificates.fetch() functions correctly.
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action: 'it-exchange-aust-existing-get-existing-certs'
				});
				return wp.ajax.send( options );

			// Otherwise, fall back to `Backbone.sync()`.
			} else {
				/**
				 * Call `sync` directly on Backbone.Model
				 */
				return Backbone.Model.prototype.sync.apply( this, arguments );
			}

		}
	});
	

	AUSTCertManager.certificates = new AUSTCertManager.ExistingCertificates();
	
}(jQuery));