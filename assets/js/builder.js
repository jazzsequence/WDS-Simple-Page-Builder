(function(window, document, $, cmb, undefined){
	'use strict';

	$.each( page_builder_areas, function( key, value ) {

		var app = { $ : {} };

		app.cache = function() {
			app.$.box         = $( document.getElementById( 'cmb2-metabox-spb2_' + value ) );
			app.$.dropdowns   = app.$.box.find( '.simple-page-builder-template-select' );
			app.$.postForm    = $( document.getElementById( 'post' ) );
			app.$.hiddenParts = app.$.box.find( '.hidden-parts-fields' );
		};

		app.init = function() {
			app.resetCacheAndHide();

			app.$.box
				.on( 'change', '.simple-page-builder-template-select', app.maybeUnhide );

			app.$.postForm
				.on( 'submit', app.removeHidden );

			cmb.metabox().find( '.cmb-repeatable-group' )
				.on( 'cmb2_shift_rows_complete', app.resetHide )
				.on( 'cmb2_add_row cmb2_remove_row', app.resetCacheAndHide );
		};

		app.removeHidden = function() {
			$( '.hidden-parts-fields.hidden' ).remove();
		};

		app.resetCacheAndHide = function( evt, row ) {
			app.cache();
			app.resetHide( evt, row );
		};

		app.resetHide = function( evt, row ) {
			if ( row ) {
				cmb.emptyValue( evt, row );
			}

			app.$.hiddenParts.addClass( 'hidden' );

			app.$.dropdowns.each( app.maybeUnhide );
		};

		app.maybeUnhide = function( evt ) {
			var $this = $(this);
			var id    = $this.val();
			var $row  = $this.parents( '.cmb-repeatable-grouping' );


			if ( evt.target ) {
				$row.find( '.hidden-parts-fields' ).addClass( 'hidden' );
			}

			var $hidden = $row.find( '.hidden-parts-' + id );

			if ( $hidden.length ) {
				$hidden.removeClass( 'hidden' );
			}
		};

		$( app.init );

	} );

})(window, document, jQuery, CMB2);
