import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';

type AmenityItem = {
	title: string;
	text: string;
};

type AmenitiesAttributes = {
	items: AmenityItem[];
};

export default function Edit( { attributes, setAttributes }: BlockEditProps< AmenitiesAttributes > ) {
	const { items } = attributes;
	const list = Array.isArray( items ) ? items : [];

	const updateItem = ( index: number, key: keyof AmenityItem, value: string ) => {
		const next = list.map( ( item, i ) =>
			i === index ? { ...item, [ key ]: value } : item
		);
		setAttributes( { items: next } );
	};

	const addItem = () => {
		setAttributes( {
			items: [ ...list, { title: '', text: '' } ],
		} );
	};

	const removeItem = ( index: number ) => {
		setAttributes( { items: list.filter( ( _, i ) => i !== index ) } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Items', 'hotel-booking-core' ) }>
					{ list.map( ( item, index ) => (
						<div key={ index } style={ { marginBottom: '1rem' } }>
							<TextControl
								label={ __( 'Title', 'hotel-booking-core' ) }
								value={ item.title }
								onChange={ ( value ) => updateItem( index, 'title', value ) }
							/>
							<TextareaControl
								label={ __( 'Text', 'hotel-booking-core' ) }
								value={ item.text }
								onChange={ ( value ) => updateItem( index, 'text', value ) }
							/>
							<Button
								isDestructive
								variant="tertiary"
								onClick={ () => removeItem( index ) }
							>
								{ __( 'Remove', 'hotel-booking-core' ) }
							</Button>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addItem }>
						{ __( 'Add amenity', 'hotel-booking-core' ) }
					</Button>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'hb-amenities' } ) }>
				{ list.map( ( item, index ) => (
					<div key={ index } className="hb-amenities__item">
						<strong>{ item.title || __( 'Untitled', 'hotel-booking-core' ) }</strong>
						<p>{ item.text }</p>
					</div>
				) ) }
			</div>
		</>
	);
}
