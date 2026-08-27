import { store, getContext } from '@wordpress/interactivity';

type AmenitiesContext = {
	index: number;
};

const { state } = store( 'hotel-booking/amenities', {
	state: {
		get isOpen() {
			const ctx = getContext< AmenitiesContext >();
			return ctx && ctx.index === state.openIndex;
		},
		get isHidden() {
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
