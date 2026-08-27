import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import type { BlockEditProps } from '@wordpress/blocks';

type RoomCardAttributes = {
	roomId: number;
};

type RoomEntity = {
	id: number;
	title: { rendered: string };
};

type CoreDataSelect = {
	getEntityRecords: (
		kind: string,
		name: string,
		query?: Record< string, unknown >
	) => RoomEntity[] | null | undefined;
};

export default function Edit( { attributes, setAttributes }: BlockEditProps< RoomCardAttributes > ) {
	const { roomId } = attributes;
	const rooms = useSelect( ( select ) => {
		return ( select( 'core' ) as CoreDataSelect ).getEntityRecords( 'postType', 'hb_room', {
			per_page: 50,
			status: 'publish',
			orderby: 'title',
			order: 'asc',
		} );
	}, [] );

	const options = [
		{ label: __( 'Latest room', 'hotel-booking-core' ), value: '0' },
	];
	if ( rooms ) {
		rooms.forEach( ( room ) => {
			options.push( { label: room.title.rendered, value: String( room.id ) } );
		} );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Room', 'hotel-booking-core' ) }>
					{ undefined === rooms ? (
						<Spinner />
					) : (
						<SelectControl
							label={ __( 'Show', 'hotel-booking-core' ) }
							value={ String( roomId ) }
							options={ options }
							onChange={ ( value ) =>
								setAttributes( { roomId: parseInt( value, 10 ) || 0 } )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<ServerSideRender
					block="hotel-booking/room-card"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
