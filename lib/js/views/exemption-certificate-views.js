var AUSTCertManager = AUSTCertManager || {};

(function ($) {
	'use strict';
	
	AUSTCertManager.ListCertsView = Backbone.View.extend({
	
		// Metabox container
		el : function() {
			return $( '#it-exchange-advanced-us-taxes-exemption-manager-wrapper' );
		},

		template: wp.template( 'it-exchange-advanced-us-taxes-manage-certs-container' ),

		initialize : function() {
			this.$add_cert_view = new AUSTCertManager.AddCertView();
			this.ExistingCertificates = new AUSTCertManager.ExistingCertificates;
			
			this.listenTo( this.ExistingCertificates, 'reset', this.addAll );
			this.listenTo( this.ExistingCertificates, 'remove', this.addAll );
		},

		/**
		 * Event Handlers
		*/
		events : {
			//'click .it-exchange-aust-open-cert-manager-button a' : 'fadeInListCertsPopup',
			'click #it-exchange-advanced-us-tax-add-cert'  : 'addNew',
			'click .it-exchange-aust-close-cert-manager a' : 'fadeOutListCertsPopup',
		},
		
		/**
		 * Render the subviews
		*/
		render : function(){
			// Empty container
			this.$el.empty();

			// Render
			this.$el.html( this.template );
			this.$certs = $( '#it-exchange-advanced-us-taxes-exemption-manager-existing-certificates' );
			
			this.ExistingCertificates.fetch({ reset: true });
			
			this.$el.fadeIn();

			return this;
		},
		
		addNew : function( event ) {
			event.preventDefault();
			this.$add_cert_view.render();
		},
		
		addAll : function() {
			this.$certs.empty();
			this.ExistingCertificates.each( this.addOne, this );
		},
		
		addOne : function( cert ) {
			var view = new AUSTCertManager.CertificateView({ model: cert });
			this.$certs.append( view.render().el );
		},
		
		fadeInListCertsPopup : function ( event ) {
			event.preventDefault();
		},
		
		fadeOutListCertsPopup : function ( event ) {
			event.preventDefault();
			this.$el.fadeOut();
		}
	});
	
	AUSTCertManager.CertificateView = Backbone.View.extend({

		tagName : 'div',

		className : 'ite-aust-certificate',

		template : wp.template( 'it-exchange-advanced-us-taxes-list-certs-container' ),
		
		events : {
			'click #it-exchange-aust-remove-existing-certificate' : 'removeCert',
			//'click #it-exchange-aust-view-existing-certificate' : 'viewCert',
			'click #it-exchange-aust-use-existing-certificate' : 'useCert',
		},
		
		initialize : function() {},

		render : function () {
			this.id = 'certificate-' + this.model.get( 'CertificateID' );
			var data = this.model.toJSON();    
			this.$el.html( this.template( data ) );
			return this;
		},
		
		removeCert : function ( event ) {
			event.preventDefault();
			if ( confirm( 'Are you sure you want to delete this certificate?' ) ) {
				this.model.destroy().done( function( data ) {
					console.log( data );
				}).fail( function( errors ) {
					console.log( errors );
				});
			}
		},
		
		useCert : function ( event ) {
			event.preventDefault();
			this.useCertificate( this.model.id ).done( function( data ) {
				console.log( data );
				console.log( 'now we need to reload the cart/page to get the new cert info in there' );
				console.log( 'done, we need to add the cert and refresh the page' );
			}).fail( function( errors ) {
				$( '#it-exchange-advanced-us-taxes-exemption-manager-content-area', self.$el ).scrollTop(0);
				self.displayErrors( self.$el, errors );
			});
		},
		
		//Auxiliar functions
		useCertificate : function ( cert_id ) {
			return wp.ajax.post( 'it-exchange-aust-existing-use-existing-cert', { cert_id: cert_id } );
		},

	});

	AUSTCertManager.AddCertView = Backbone.View.extend({
		// Metabox container
		el : function() {
			return $( '#it-exchange-advanced-us-taxes-exemption-manager-wrapper' );
		},

		template: wp.template( 'it-exchange-advanced-us-taxes-add-cert-container' ),

		initialize : function() {
		},

		/**
		 * Event Handlers
		*/
		events : {
			//'click .it-exchange-aust-open-cert-manager-button a' : 'fadeInListCertsPopup',
			'click .it-exchange-aust-close-cert-manager a' : 'fadeOutListCertsPopup',
			'click .it-exchange-aust-cancel-cert-button'   : 'fadeOutListCertsPopup',
			'click .it-exchange-aust-save-cert-button'     : 'saveCert',
		},
		
		render : function() {
			// Empty container
			this.$el.empty();

			// Render
			this.$el.html( this.template );
			
			this.$el.show();
		},
		
		fadeOutListCertsPopup : function ( event ) {
			event.preventDefault();
			this.$el.fadeOut();
		},
		
		saveCert : function ( event ) {
			event.preventDefault();
			var self = this;
			this.clearErrors( this.$el );
			var newCert = this.getFormData( this.$el.find('form#it-exchange-add-on-advanced-us-taxes-add-cert') );
			this.addCertificate(newCert).done( function( data ) {
				console.log( data );
				console.log( 'now we need to reload the cart/page to get the new cert info in there' );
				console.log( 'done, we need to add the cert and refresh the page' );
			}).fail( function( errors ) {
				$( '#it-exchange-advanced-us-taxes-exemption-manager-content-area', self.$el ).scrollTop(0);
				self.displayErrors( self.$el, errors );
			});
		},
		
		//Auxiliar functions
		addCertificate : function ( certificate ) {
			return wp.ajax.post( 'it-exchange-advanced-us-taxes-add-cert', certificate );
		},
		
		clearErrors : function ( self ) {
			$( '#it-exchange-advanced-us-taxes-exemption-manager-error-area', self ).empty();
		},
		
		displayErrors : function ( self, errors ) {
			var elements = $();
			elements = '<ul class="it-exchange-messages it-exchange-errors">';
			$.each( errors, function( index, value ) {
			    elements += '<li>'+value+'</li>';
			});
			elements += '</ul>' ;
			$( '#it-exchange-advanced-us-taxes-exemption-manager-error-area', self ).append( elements );
		},
		
		getFormData : function(form) { 
			var unindexed_array = form.serializeArray();
			var indexed_array = {};
			
			$.map(unindexed_array, function(n, i){
				indexed_array[n['name']] = n['value'];
			});
			
			return indexed_array;
		},	
	});
})(jQuery);