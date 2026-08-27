import { expect, test } from '@playwright/test';

test( 'home shows the hotel heading and links to rooms', async ( { page } ) => {
	await page.goto( '/' );

	await expect( page.getByRole( 'heading', { name: /quiet night/i } ) ).toBeVisible();
	await page.getByRole( 'link', { name: 'Rooms' } ).first().click();
	await expect( page ).toHaveURL( /\/rooms\/?/ );
} );
