import ServerSideRender from '@wordpress/server-side-render';
import { SelectControl, PanelBody, TextControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

const optionIdOptions = [];

wp.apiFetch(
	{path: "/bilingual-linker/v1/languagelist"} ).then(
	posts => {
	jQuery.each( posts, function( key, val ) {
	optionIdOptions.push(
	{label: 'Language #' + key
	+ ': ' + val, value: key} );
	} );
	} ).catch()

const edit = props => {
	const { attributes: { lang_id }, className, setAttributes } = props;

	const setOptionID = lang_id => {
		props.setAttributes( { lang_id } );
	};

	const inspectorControls = (
		<InspectorControls key="inspector">
		<PanelBody>
		<SelectControl
		label="Language" value={ lang_id }
		options= { optionIdOptions }
		onChange = { setOptionID } />
		</PanelBody>
		</InspectorControls> );

	return [
		<div className={ props.className } key="returneddata">
			<ServerSideRender block="bilingual-linker/bl-lang-switcher" attributes = { props.attributes } /> 
			{ inspectorControls }
		</div>
	];
};
export default edit;