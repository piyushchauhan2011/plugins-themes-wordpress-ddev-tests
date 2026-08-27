import { expect, test } from '@playwright/test';

test( 'rooms archive lists a hotel room', async ( { page } ) => {
	await page.goto( '/rooms/' );

	await expect( page.getByRole( 'heading', { name: 'Rooms', level: 1 } ) ).toBeVisible();
	await expect( page.getByRole( 'heading', { name: 'Deluxe King' } ) ).toBeVisible();
} );
