import { registerHotelBlock } from '../register-block';
import Edit from './edit';
import metadata from './block.json';
import './style.scss';

registerHotelBlock( metadata, {
	edit: Edit,
	save: () => null,
} );
