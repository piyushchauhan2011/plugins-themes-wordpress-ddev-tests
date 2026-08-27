import { registerHotelBlock } from '../register-block';
import metadata from './block.json';
import './style.scss';

registerHotelBlock( metadata, {
	edit: () => null,
	save: () => null,
} );
