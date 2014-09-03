/**
 * Handle the custom taxonomy nav menu meta box
 */
jQuery( document ).ready( function($) {
     $( '#submit-taxonomy-archives' ).click( function( event ) {
		event.preventDefault();
		
		var $tet_list_items = $( '#' + tet_obj.metabox_list_id + ' li :checked' );
		var $tet_submit = $( 'input#submit-taxonomy-archives' );

		// Get checked boxes
		var Taxonomies = [];
		$tet_list_items.each( function() {
			Taxonomies.push( $( this ).val() );
		} );
		
		// Show spinner
		$( '#' + tet_obj.metabox_id ).find('.spinner').show();
		
		// Disable button
		$tet_submit.prop( 'disabled', true );

		// Send checked post types with our action, and nonce
		$.post( tet_obj.ajaxurl, {
				action: tet_obj.action,
				taxarchive_nonce: tet_obj.nonce,
				taxonomies: Taxonomies,
				nonce: tet_obj.nonce
			},

			// AJAX returns html to add to the menu, hide spinner, remove checks
			function( response ) {
				$( '#menu-to-edit' ).append( response );
				$( '#' + tet_obj.metabox_id ).find('.spinner').hide();
				$tet_list_items.prop("checked", false);
				$tet_submit.prop( 'disabled', false );
			}
		);
	} );
} );
