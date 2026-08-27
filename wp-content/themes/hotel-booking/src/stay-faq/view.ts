import { store, getContext } from '@wordpress/interactivity';

type StayFaqContext = {
	index: number;
};

type StayFaqStore = {
	state: {
		openIndex: number;
		readonly isOpen: boolean;
		readonly isHidden: boolean;
	};
	actions: {
		toggle: () => void;
	};
};

const { state } = store< StayFaqStore >( 'hotel-booking-theme/stay-faq', {
	state: {
		get isOpen(): boolean {
			const ctx = getContext< StayFaqContext >();
			return Boolean( ctx && ctx.index === state.openIndex );
		},
		get isHidden(): boolean {
			const ctx = getContext< StayFaqContext >();
			return ! ( ctx && ctx.index === state.openIndex );
		},
	},
	actions: {
		toggle() {
			const ctx = getContext< StayFaqContext >();
			if ( ! ctx ) {
				return;
			}
			state.openIndex = state.openIndex === ctx.index ? -1 : ctx.index;
		},
	},
} );
