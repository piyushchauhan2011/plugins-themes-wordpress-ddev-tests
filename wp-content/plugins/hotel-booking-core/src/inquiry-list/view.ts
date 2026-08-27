import { store, getContext } from '@wordpress/interactivity';

type InquiryListContext = {
	status?: string;
	rowStatus?: string;
};

type InquiryListState = {
	filter: string;
};

const { state } = store< InquiryListState >( 'hotel-booking/inquiry-list', {
	state: {
		get isAll() {
			return state.filter === 'all';
		},
		get isFilterActive() {
			const ctx = getContext< InquiryListContext >();
			return ctx && ctx.status === state.filter;
		},
		get rowHidden() {
			const ctx = getContext< InquiryListContext >();
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
			const ctx = getContext< InquiryListContext >();
			if ( ctx && ctx.status ) {
				state.filter = ctx.status;
			}
		},
	},
} );
