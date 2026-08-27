import { store, getContext } from '@wordpress/interactivity';

type RoomPayload = {
	id?: number;
	guests?: number;
	has_image?: boolean;
	excerpt?: string;
};

type Suggestion = {
	text?: string;
	permalink?: string;
};

type RoomSearchContext = {
	room?: RoomPayload;
	suggestion?: Suggestion;
};

type RoomSearchStore = {
	state: {
		rooms: unknown[];
		suggestions: Suggestion[];
		q: string;
		guests: number;
		beds: number;
		priceMin: number;
		priceMax: number;
		lang: string;
		restUrl: string;
		suggestUrl: string;
		readonly hasRooms: boolean;
		readonly suggestionsHidden: boolean;
		readonly guestsLabel: string;
		readonly imageHidden: boolean;
		readonly excerptHidden: boolean;
	};
	actions: {
		onQueryInput: ( event: Event ) => void;
		chooseSuggestion: () => Promise< void >;
		setGuests: ( event: Event ) => void;
		setBeds: ( event: Event ) => void;
		setPriceMin: ( event: Event ) => void;
		setPriceMax: ( event: Event ) => void;
		search: ( event: Event ) => Promise< void >;
	};
};

let suggestTimer = 0;

function parseCount( value: string ): number {
	const n = parseInt( value, 10 );
	return Number.isNaN( n ) || n < 0 ? 0 : n;
}

const { state } = store< RoomSearchStore >( 'hotel-booking/room-search', {
	state: {
		get hasRooms(): boolean {
			return Array.isArray( state.rooms ) && state.rooms.length > 0;
		},
		get suggestionsHidden(): boolean {
			return ! Array.isArray( state.suggestions ) || state.suggestions.length === 0;
		},
		get guestsLabel(): string {
			const ctx = getContext< RoomSearchContext >();
			const room = ctx && ctx.room ? ctx.room : {};
			const n = room.guests ? String( room.guests ) : '';
			return n ? n + ' guests' : '';
		},
		get imageHidden(): boolean {
			const ctx = getContext< RoomSearchContext >();
			return ! ( ctx && ctx.room && ctx.room.has_image );
		},
		get excerptHidden(): boolean {
			const ctx = getContext< RoomSearchContext >();
			return ! ( ctx && ctx.room && ctx.room.excerpt );
		},
	},
	actions: {
		onQueryInput( event: Event ) {
			const target = event.target as HTMLInputElement;
			state.q = target.value;
			window.clearTimeout( suggestTimer );
			suggestTimer = window.setTimeout( () => {
				void fetchSuggestions();
			}, 200 );
		},
		async chooseSuggestion() {
			const ctx = getContext< RoomSearchContext >();
			const text = ctx && ctx.suggestion && ctx.suggestion.text ? ctx.suggestion.text : '';
			if ( text ) {
				state.q = text;
			}
			state.suggestions = [];
			await fetchRooms();
		},
		setGuests( event: Event ) {
			state.guests = parseCount( ( event.target as HTMLInputElement ).value );
		},
		setBeds( event: Event ) {
			state.beds = parseCount( ( event.target as HTMLInputElement ).value );
		},
		setPriceMin( event: Event ) {
			state.priceMin = parseCount( ( event.target as HTMLInputElement ).value );
		},
		setPriceMax( event: Event ) {
			state.priceMax = parseCount( ( event.target as HTMLInputElement ).value );
		},
		async search( event: Event ) {
			event.preventDefault();
			state.suggestions = [];
			await fetchRooms();
		},
	},
} );

function applySearchParams( url: URL ): void {
	if ( state.q ) {
		url.searchParams.set( 'q', String( state.q ) );
	}
	if ( state.guests > 0 ) {
		url.searchParams.set( 'guests', String( state.guests ) );
	}
	if ( state.beds > 0 ) {
		url.searchParams.set( 'beds', String( state.beds ) );
	}
	if ( state.priceMin > 0 ) {
		url.searchParams.set( 'price_min', String( state.priceMin ) );
	}
	if ( state.priceMax > 0 ) {
		url.searchParams.set( 'price_max', String( state.priceMax ) );
	}
	if ( state.lang ) {
		url.searchParams.set( 'lang', String( state.lang ) );
	}
}

async function fetchSuggestions(): Promise< void > {
	const q = String( state.q || '' ).trim();
	if ( q.length < 1 ) {
		state.suggestions = [];
		return;
	}

	const url = new URL( state.suggestUrl, window.location.origin );
	url.searchParams.set( 'q', q );
	if ( state.lang ) {
		url.searchParams.set( 'lang', String( state.lang ) );
	}

	try {
		const response = await fetch( url.href );
		if ( ! response.ok ) {
			return;
		}
		const data: unknown = await response.json();
		state.suggestions = Array.isArray( data ) ? ( data as Suggestion[] ) : [];
	} catch ( err ) {
		state.suggestions = [];
	}
}

async function fetchRooms(): Promise< void > {
	const url = new URL( state.restUrl, window.location.origin );
	applySearchParams( url );

	const page = new URL( window.location.href );
	[ 'q', 'guests', 'beds', 'price_min', 'price_max' ].forEach( ( key ) => {
		page.searchParams.delete( key );
	} );
	applySearchParams( page );
	if ( page.searchParams.has( 'lang' ) && ! new URL( window.location.href ).searchParams.has( 'lang' ) ) {
		page.searchParams.delete( 'lang' );
	}
	window.history.replaceState( {}, '', page );

	try {
		const response = await fetch( url.href );
		if ( ! response.ok ) {
			return;
		}
		state.rooms = await response.json();
	} catch ( err ) {
		// Keep the last SSR list if the request fails.
	}
}
