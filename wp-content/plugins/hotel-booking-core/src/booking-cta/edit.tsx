import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';

type BookingCtaAttributes = {
	heading: string;
	buttonText: string;
	url: string;
};

export default function Edit( { attributes, setAttributes }: BlockEditProps< BookingCtaAttributes > ) {
	const { heading, buttonText, url } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Link', 'hotel-booking-core' ) }>
					<TextControl
						label={ __( 'URL', 'hotel-booking-core' ) }
						value={ url }
						onChange={ ( value ) => setAttributes( { url: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'hb-booking-cta' } ) }>
				<RichText
					tagName="p"
					className="hb-booking-cta__heading"
					value={ heading }
					onChange={ ( value ) => setAttributes( { heading: value } ) }
					placeholder={ __( 'Heading', 'hotel-booking-core' ) }
				/>
				<RichText
					tagName="span"
					className="hb-booking-cta__button"
					value={ buttonText }
					onChange={ ( value ) => setAttributes( { buttonText: value } ) }
					placeholder={ __( 'Button', 'hotel-booking-core' ) }
				/>
			</div>
		</>
	);
}
