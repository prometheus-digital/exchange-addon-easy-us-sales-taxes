var AUSTCertManager = AUSTCertManager || {};

(function ($) {
	'use strict';
	
	AUSTCertManager.ListCertsView = Backbone.View.extend({
	
		// Metabox container
		el : function() {
			return $( '#it-exchange-advanced-us-taxes-exemption-manager-wrapper' );
		},

		template: wp.template( 'it-exchange-advanced-us-taxes-list-certs-container' ),

		initialize : function() {
			this.ExistingCertificates = new AUSTCertManager.ExistingCertificates;

			this.listenTo(this.ExistingCertificates, 'remove', this.removeCert);
		},

		/**
		 * Event Handlers
		*/
		events : {
			//'click .it-exchange-aust-open-cert-manager-button a' : 'fadeInListCertsPopup',
			'click #it-exchange-advanced-us-tax-add-cert'  : 'addNewCertView',
			'click .it-exchange-aust-close-cert-manager a' : 'fadeOutListCertsPopup',
			'click .it-exchange-aust-select-cert-button a' : 'selectCert',
			'click .it-exchange-aust-view-cert-button a'   : 'viewCert',
			'click .it-exchange-aust-remove-cert-button a' : 'removeCert',
		},
		
		/**
		 * Render the subviews
		*/
		render : function(){
			// Empty container
			this.$el.empty();

			// Render
			this.$el.html( this.template );

			// Fetch and append Existing Certificates (events registered in init build the views on fetch)
			//this.ExistingCertificates.url = ite_aust_ajax.ajax_url + '?action=it-exchange-aust-json-api&endpoint=existing-certificates';
			this.ExistingCertificates = this.getExistingCertificates();
			
			this.$el.fadeIn();

			return this;
		},
		
		addNewCertView : function( event ) {
			console.log( 'addNewCertView' );
			event.preventDefault();
			var view = new AUSTCertManager.AddCertView();
			view.render();
		},
		
		fadeInListCertsPopup : function ( event ) {
			console.log( 'fadeInListCertsPopup' );
			event.preventDefault();
		},
		
		fadeOutListCertsPopup : function ( event ) {
			console.log( 'fadeOutListCertsPopup' );
			event.preventDefault();
			this.$el.fadeOut();
		},
		
		selectCert : function ( event ) {
			console.log( 'selectCert' );
			event.preventDefault();
		},
		
		viewCert : function ( event ) {
			console.log( 'viewCert' );
			event.preventDefault();
		},
		
		removeCert : function ( event ) {
			console.log( 'removeCert' );
			event.preventDefault();
		},
		
		//Auxiliar functions
		getExistingCertificates : function () {
			console.log( 'addCertificate' );
			return wp.ajax.post( 'it-exchange-advanced-us-taxes-get-certs', {} );
		},

	});
	
	AUSTCertManager.CertView = Backbone.View.extend({

		tagName : 'certificate',

		className : 'certificate',

		template : wp.template( 'it-exchange-aust-certificate-body' ),

		render : function () {
			this.id = 'certificate-' + this.model.get('id');
			this.$el.html( this.template( this.model.toJSON() ) );
			return this;
		}

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
			console.log( 'fadeOutListCertsPopup' );
			event.preventDefault();
			this.$el.fadeOut();
		},
		
		saveCert : function ( event ) {
			console.log( 'saveCert' );
			event.preventDefault();
			var self = this;
			this.clearErrors( this.$el );
			var newCert = this.getFormData( this.$el.find('form#it-exchange-add-on-advanced-us-taxes-add-cert') );
			console.log( newCert );
			this.addCertificate(newCert).done( function( data ) {
				console.log( data );
				console.log( 'now we need to reload the cart/page to get the new cert info in there' );
				console.log( 'done, we need to add the cert and refresh the page' );
			}).fail( function( errors ) {
				console.log( errors );
				$( '#it-exchange-advanced-us-taxes-exemption-manager-content-area', self.$el ).scrollTop(0);
				self.displayErrors( self.$el, errors );
			});
		},
		
		//Auxiliar functions
		addCertificate : function ( certificate ) {
			console.log( 'addCertificate' );
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