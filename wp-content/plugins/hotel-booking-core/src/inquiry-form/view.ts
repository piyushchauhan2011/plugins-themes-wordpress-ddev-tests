import { store } from '@wordpress/interactivity';

type InquiryFormStore = {
	state: {
		checkIn: string;
		guests: number;
		maxGuests: number;
		readonly minCheckOut: string;
	};
	actions: {
		setCheckIn: ( event: Event ) => void;
		setGuestsFromSelect: ( event: Event ) => void;
		incrementGuests: () => void;
		decrementGuests: () => void;
	};
};

const { state } = store< InquiryFormStore >( 'hotel-booking/inquiry-form', {
	state: {
		get minCheckOut(): string {
			if ( ! state.checkIn ) {
				return '';
			}
			const next = new Date( state.checkIn + 'T00:00:00' );
			next.setDate( next.getDate() + 1 );
			return next.toISOString().slice( 0, 10 );
		},
	},
	actions: {
		setCheckIn( event: Event ) {
			const target = event.target as HTMLInputElement;
			state.checkIn = target.value;
		},
		setGuestsFromSelect( event: Event ) {
			const target = event.target as HTMLSelectElement;
			const value = parseInt( target.value, 10 );
			if ( ! Number.isNaN( value ) ) {
				state.guests = value;
			}
		},
		incrementGuests() {
			const max = state.maxGuests || 8;
			if ( state.guests < max ) {
				state.guests += 1;
			}
		},
		decrementGuests() {
			if ( state.guests > 1 ) {
				state.guests -= 1;
			}
		},
	},
} );
