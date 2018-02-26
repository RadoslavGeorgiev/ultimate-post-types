(function( $ ) {

	var ui = UltimateFields.UI;

	/**
	 * When the main fields editor is initialized, generate a new context.
	 */
	UltimateFields.addFilter( 'ui.fields_field_rendered', function( view ) {
		if( -1 == [ 'upt_pt_fields', 'upt_tax_fields' ].indexOf( view.model.get( 'name' ) ) ) {
			return;
		}

		ui.Context.addLevel( new ui.ContextLevel({
			label: 'Top-level fields', //@todo: Translate
			fields: view.editor.fields
		}));
	});

})( jQuery );
