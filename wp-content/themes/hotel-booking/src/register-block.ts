import { registerBlockType } from '@wordpress/blocks';
import type { ComponentType } from 'react';

type BlockMetadata = {
	name: string;
};

type BlockSettings = {
	edit: ComponentType< any > | ( () => null );
	save: () => null;
};

export function registerHotelBlock(
	metadata: BlockMetadata,
	settings: BlockSettings
): void {
	registerBlockType( metadata.name, {
		...metadata,
		...settings,
	} as never );
}
