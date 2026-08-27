import { registerHotelBlock } from '../register-block';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

function Edit() {
	const props = useBlockProps( { className: 'hb-room-search-editor' } );
	return (
		<div { ...props }>
			<p>
				{ __(
					'Room search — typeahead and filters on the front (OpenSearch when the cluster is up, WP_Query otherwise).',
					'hotel-booking-core'
				) }
			</p>
		</div>
	);
}

registerHotelBlock( metadata, {
	edit: Edit,
	save: () => null,
} );
