import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'hotel-booking-theme/stay-faq', {
	state: {
		get isOpen() {
			const ctx = getContext();
			return ctx && ctx.index === state.openIndex;
		},
		get isHidden() {
			const ctx = getContext();
			return ! ( ctx && ctx.index === state.openIndex );
		},
	},
	actions: {
		toggle() {
			const ctx = getContext();
			if ( ! ctx ) {
				return;
			}
			state.openIndex = state.openIndex === ctx.index ? -1 : ctx.index;
		},
	},
} );
