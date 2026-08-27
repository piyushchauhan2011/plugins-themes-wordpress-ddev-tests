import { store, getContext } from '@wordpress/interactivity';

type AmenitiesContext = {
	index: number;
};

type AmenitiesStore = {
	state: {
		openIndex: number;
		readonly isOpen: boolean;
		readonly isHidden: boolean;
	};
	actions: {
		toggle: () => void;
	};
};

const { state } = store< AmenitiesStore >( 'hotel-booking/amenities', {
	state: {
		get isOpen(): boolean {
			const ctx = getContext< AmenitiesContext >();
			return Boolean( ctx && ctx.index === state.openIndex );
		},
		get isHidden(): boolean {
			const ctx = getContext< AmenitiesContext >();
			return ! ( ctx && ctx.index === state.openIndex );
		},
	},
	actions: {
		toggle() {
			const ctx = getContext< AmenitiesContext >();
			if ( ! ctx ) {
				return;
			}
			state.openIndex = state.openIndex === ctx.index ? -1 : ctx.index;
		},
	},
} );
