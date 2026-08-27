import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'hotel-booking/inquiry-list', {
	state: {
		get isAll() {
			return state.filter === 'all';
		},
		get isFilterActive() {
			const ctx = getContext();
			return ctx && ctx.status === state.filter;
		},
		get rowHidden() {
			const ctx = getContext();
			if ( ! ctx || state.filter === 'all' ) {
				return false;
			}
			return ctx.rowStatus !== state.filter;
		},
	},
	actions: {
		filterAll() {
			state.filter = 'all';
		},
		setFilter() {
			const ctx = getContext();
			if ( ctx && ctx.status ) {
				state.filter = ctx.status;
			}
		},
	},
} );
