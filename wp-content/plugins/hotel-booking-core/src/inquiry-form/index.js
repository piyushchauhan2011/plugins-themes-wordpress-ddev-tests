import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.css';

function Edit() {
	const props = useBlockProps( { className: 'hb-inquiry-form-editor' } );
	return (
		<div { ...props }>
			<p>{ __( 'Inquiry form — guests submit to the custom table. Guest stepper and check-out min date use the Interactivity API on the front.', 'hotel-booking-core' ) }</p>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
