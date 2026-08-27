import { store } from '@wordpress/interactivity';

const STORAGE_KEY = 'hotel-booking-color-scheme';

type ColorScheme = 'dark' | 'light';

type ColorSchemeState = {
	scheme: ColorScheme;
	labelLight: string;
	labelDark: string;
	shortLight: string;
	shortDark: string;
};

function readScheme(): ColorScheme {
	const fromDom = document.documentElement.dataset.colorScheme;
	if ( fromDom === 'dark' || fromDom === 'light' ) {
		return fromDom;
	}

	try {
		const stored = localStorage.getItem( STORAGE_KEY );
		if ( stored === 'dark' || stored === 'light' ) {
			return stored;
		}
	} catch ( err ) {
		// Ignore blocked storage.
	}

	return window.matchMedia( '(prefers-color-scheme: dark)' ).matches
		? 'dark'
		: 'light';
}

function applyScheme( scheme: ColorScheme ) {
	document.documentElement.dataset.colorScheme = scheme;
	try {
		localStorage.setItem( STORAGE_KEY, scheme );
	} catch ( err ) {
		// Ignore blocked storage.
	}
}

const { state } = store< ColorSchemeState >( 'hotel-booking-theme/color-scheme', {
	state: {
		scheme: readScheme(),
		get isDark() {
			return state.scheme === 'dark';
		},
		get label() {
			return state.scheme === 'dark' ? state.labelLight : state.labelDark;
		},
		get shortLabel() {
			return state.scheme === 'dark' ? state.shortLight : state.shortDark;
		},
	},
	actions: {
		toggle() {
			state.scheme = state.scheme === 'dark' ? 'light' : 'dark';
			applyScheme( state.scheme );
		},
	},
} );

applyScheme( state.scheme );
