declare module '*.scss';

declare module '@wordpress/block-editor' {
	import type { ComponentType, ReactNode } from 'react';

	export function useBlockProps(
		props?: Record< string, unknown >
	): Record< string, unknown >;

	export const InspectorControls: ComponentType< { children?: ReactNode } >;

	export const RichText: ComponentType< {
		tagName?: string;
		className?: string;
		value: string;
		onChange: ( value: string ) => void;
		placeholder?: string;
	} >;
}
