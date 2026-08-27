import { registerHotelBlock } from '../register-block';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

function Edit() {
	const props = useBlockProps();
	return (
		<div { ...props }>
			<p>{ __( 'Inquiry list — visible to editors on the front. Status chips filter rows with the Interactivity API; saves and deletes still use admin-post.', 'hotel-booking-core' ) }</p>
		</div>
	);
}

registerHotelBlock( metadata, {
	edit: Edit,
	save: () => null,
} );
