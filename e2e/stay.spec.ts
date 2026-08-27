import { expect, test } from '@playwright/test';

test( 'stay page rooms grid filters by guests', async ( { page } ) => {
	await page.goto( '/stay/' );

	const grid = page.locator( '.hb-rooms-grid' );
	await expect( grid ).toBeVisible();
	await expect( grid.getByRole( 'heading', { name: 'Deluxe King' } ) ).toBeVisible();

	await grid.getByRole( 'button', { name: '4+' } ).click();

	await expect( grid.getByRole( 'heading', { name: 'Family Room' } ) ).toBeVisible();
	await expect( grid.getByRole( 'heading', { name: 'Deluxe King' } ) ).toHaveCount( 0 );
} );
