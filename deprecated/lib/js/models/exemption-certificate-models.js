var AUSTCertManager = AUSTCertManager || {};

(function ($){
	'use strict';

	AUSTCertManager.ExistingCertificate = Backbone.Model.extend({
		defaults: {
			CertificateID:            '',
			PurchaserFirstName:       '',
			PurchaserLastName:        '',
			ExemptStates:             '',
			CreatedDate:              '',
			PurchaserExemptionReason: '',
		},
		idAttribute: "CertificateID",
		
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

			// Overload the delete method so ExistingCertificate.destroy() functions correctly.
			if ( 'delete' === method ) {
				options = options || {};

				if ( ! options.wait ) {
					this.destroyed = true;
				}

				options.context = this;
				options.data = _.extend( options.data || {}, {
					action:   'it-exchange-aust-existing-remove-existing-cert',
					id:       this.id,
					//_wpnonce: this.get('nonces')['delete']
				});

				return wp.ajax.send( options, {} ).done( function() {
					this.destroyed = true;
				}).fail( function() {
					this.destroyed = false;
				});

			// Otherwise, fall back to `Backbone.sync()`.
			} else {
				/**
				 * Call `sync` directly on Backbone.Model
				 */
				return Backbone.Model.prototype.sync.apply( this, arguments );
			}

		}
	});
	
}(jQuery));
