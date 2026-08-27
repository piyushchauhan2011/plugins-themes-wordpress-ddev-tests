import { store, getContext } from '@wordpress/interactivity';

type InquiryListContext = {
	status?: string;
	rowStatus?: string;
};

type InquiryListStore = {
	state: {
		filter: string;
		readonly isAll: boolean;
		readonly isFilterActive: boolean;
		readonly rowHidden: boolean;
	};
	actions: {
		filterAll: () => void;
		setFilter: () => void;
	};
};

const { state } = store< InquiryListStore >( 'hotel-booking/inquiry-list', {
	state: {
		get isAll(): boolean {
			return state.filter === 'all';
		},
		get isFilterActive(): boolean {
			const ctx = getContext< InquiryListContext >();
			return Boolean( ctx && ctx.status === state.filter );
		},
		get rowHidden(): boolean {
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
