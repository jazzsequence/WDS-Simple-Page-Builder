var selected = jQuery('#parts_saved_layouts_repeat .cmb-td option[selected="selected"]');
selected.each(function(index){
	if ( 'none' == jQuery(this).val() ) {
		jQuery(this).parent().parent().parent().hide();
		jQuery('button.cmb-add-row-button').click(function(){
			jQuery('.empty-row.hidden').show();

		});
	}
});