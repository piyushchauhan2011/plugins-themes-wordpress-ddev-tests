import { store, getContext } from '@wordpress/interactivity';

type RoomPayload = {
	guests?: number;
	has_image?: boolean;
	excerpt?: string;
};

type RoomsGridContext = {
	guests?: number;
	room?: RoomPayload;
};

type RoomsGridStore = {
	state: {
		rooms: unknown[];
		guests: number;
		lang: string;
		restUrl: string;
		readonly hasRooms: boolean;
		readonly isFilterActive: boolean;
		readonly guestsLabel: string;
		readonly imageHidden: boolean;
		readonly excerptHidden: boolean;
	};
	actions: {
		filterGuests: () => Promise< void >;
	};
};

const { state } = store< RoomsGridStore >( 'hotel-booking/rooms-grid', {
	state: {
		get hasRooms(): boolean {
			return Array.isArray( state.rooms ) && state.rooms.length > 0;
		},
		get isFilterActive(): boolean {
			const ctx = getContext< RoomsGridContext >();
			return Boolean( ctx && ctx.guests === state.guests );
		},
		get guestsLabel(): string {
			const ctx = getContext< RoomsGridContext >();
			const room = ctx && ctx.room ? ctx.room : {};
			const n = room.guests ? String( room.guests ) : '';
			return n ? n + ' guests' : '';
		},
		get imageHidden(): boolean {
			const ctx = getContext< RoomsGridContext >();
			return ! ( ctx && ctx.room && ctx.room.has_image );
		},
		get excerptHidden(): boolean {
			const ctx = getContext< RoomsGridContext >();
			return ! ( ctx && ctx.room && ctx.room.excerpt );
		},
	},
	actions: {
		async filterGuests() {
			const ctx = getContext< RoomsGridContext >();
			const guests = ctx && typeof ctx.guests === 'number' ? ctx.guests : 0;
			state.guests = guests;

			const url = new URL( state.restUrl, window.location.origin );
			if ( guests > 0 ) {
				url.searchParams.set( 'guests', String( guests ) );
			}
			if ( state.lang ) {
				url.searchParams.set( 'lang', String( state.lang ) );
			}

			try {
				const response = await fetch( url.href );
				if ( ! response.ok ) {
					return;
				}
				state.rooms = await response.json();
			} catch ( err ) {
				// Keep the last SSR list if the request fails.
			}
		},
	},
} );
