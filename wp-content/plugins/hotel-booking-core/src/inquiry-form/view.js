import { store } from '@wordpress/interactivity';

const { state } = store( 'hotel-booking/inquiry-form', {
	state: {
		get minCheckOut() {
			if ( ! state.checkIn ) {
				return '';
			}
			const next = new Date( state.checkIn + 'T00:00:00' );
			next.setDate( next.getDate() + 1 );
			return next.toISOString().slice( 0, 10 );
		},
	},
	actions: {
		setCheckIn( event ) {
			state.checkIn = event.target.value;
		},
		setGuestsFromSelect( event ) {
			const value = parseInt( event.target.value, 10 );
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

