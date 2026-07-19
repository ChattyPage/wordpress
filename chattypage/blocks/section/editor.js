/**
 * Editor UI for the chattypage/section block. Deliberately build-free plain JS (ES5 + wp.element
 * createElement): a section picker fed by /wp-json/chattypage/v1/sections and a live server-side
 * preview of the picked section. The published page never loads this file.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var Placeholder = wp.components.Placeholder;
	var Spinner = wp.components.Spinner;
	var ServerSideRender = wp.serverSideRender;

	registerBlockType( 'chattypage/section', {
		edit: function ( props ) {
			var sectionId = props.attributes.sectionId;
			var state = useState( { sections: null, error: null } );
			var data = state[ 0 ];
			var setData = state[ 1 ];

			useEffect( function () {
				wp.apiFetch( { path: '/chattypage/v1/sections' } ).then(
					function ( sections ) { setData( { sections: sections, error: null } ); },
					function ( err ) { setData( { sections: [], error: ( err && err.message ) || 'error' } ); }
				);
			}, [] );

			var options = [ { value: '', label: __( 'Choose a section…', 'chattypage' ) } ].concat(
				( data.sections || [] ).map( function ( s ) {
					return { value: s.id, label: s.name || s.id };
				} )
			);

			var picker = el( SelectControl, {
				label: __( 'Section', 'chattypage' ),
				value: sectionId,
				options: options,
				onChange: function ( value ) { props.setAttributes( { sectionId: value } ); },
			} );

			var body;
			if ( data.sections === null ) {
				body = el( Placeholder, { label: __( 'ChattyPage Section', 'chattypage' ) }, el( Spinner ) );
			} else if ( data.error ) {
				body = el( Placeholder, {
					label: __( 'ChattyPage Section', 'chattypage' ),
					instructions: __( 'Could not load your sections. Connect the plugin under Settings → ChattyPage.', 'chattypage' ),
				} );
			} else if ( ! sectionId ) {
				body = el( Placeholder, {
					label: __( 'ChattyPage Section', 'chattypage' ),
					instructions: __( 'Pick one of your ChattyPage sections to place it here.', 'chattypage' ),
				}, picker );
			} else if ( ServerSideRender ) {
				body = el( ServerSideRender, { block: 'chattypage/section', attributes: props.attributes } );
			} else {
				body = el( 'div', {}, __( 'ChattyPage section', 'chattypage' ) + ': ' + sectionId );
			}

			return el( 'div', useBlockProps(), [
				el( InspectorControls, { key: 'inspector' },
					el( PanelBody, { title: __( 'ChattyPage', 'chattypage' ), initialOpen: true }, picker )
				),
				el( 'div', { key: 'body' }, body ),
			] );
		},

		// Dynamic block: markup comes from PHP at render time.
		save: function () {
			return null;
		},
	} );
} )( window.wp );
