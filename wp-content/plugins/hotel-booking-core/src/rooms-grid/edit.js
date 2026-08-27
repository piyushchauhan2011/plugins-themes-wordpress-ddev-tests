import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { guests } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Filter', 'hotel-booking-core' ) }>
					<RangeControl
						label={ __( 'Minimum guests (0 = all)', 'hotel-booking-core' ) }
						value={ guests }
						onChange={ ( value ) => setAttributes( { guests: value } ) }
						min={ 0 }
						max={ 8 }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<ServerSideRender
					block="hotel-booking/rooms-grid"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
