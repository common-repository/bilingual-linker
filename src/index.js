import edit from './edit';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 
	'bilingual-linker/bl-lang-switcher', { 
		title: 'Bilingual Linker Language Switcher', 
		icon: 'translation', 
		category: 'design', 
		attributes: {
			lang_id: {
				type: 'string',
				default: '1',	
			},
		},
		edit: edit,
		save() {return null; }, 
	}
);