var selected = jQuery('#parts_saved_layouts_repeat .cmb-td option[selected="selected"]');
selected.each(function(index){
	if ( 'none' == jQuery(this).val() ) {
		jQuery(this).parent().parent().parent().hide();
		jQuery('button.cmb-add-row-button').click(function(){
			jQuery('.empty-row.hidden').show();

		});
	}
});

jQuery("select.simple-page-builder-template-select").change(function () {
	var $this = jQuery(this);
	var title = $this.closest( '.cmb-row.cmb-repeatable-grouping' );
	var new_title = $this.find( 'option:selected').text();

	title.find( '.cmb-group-title').html( new_title );
});

jQuery(document).ready(function($){
	// Get all the template select boxes
	 jQuery("select.simple-page-builder-template-select :selected").each(function( k, v ) {
		var title = jQuery( this ).closest( '.cmb-row.cmb-repeatable-grouping' );
		var new_title = jQuery( this ).text();

		title.find( '.cmb-group-title').html( new_title );
	 });
});
