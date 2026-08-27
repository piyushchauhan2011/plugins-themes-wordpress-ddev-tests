import { expect, test } from '@playwright/test';

test( 'search page finds garden suite by query string', async ( { page } ) => {
	await page.goto( '/search/?q=garden' );

	const search = page.locator( '.hb-room-search' );
	await expect( search ).toBeVisible();
	await expect( search.getByRole( 'heading', { name: 'Garden Suite' } ) ).toBeVisible();
	await expect( search.getByRole( 'heading', { name: 'Deluxe King' } ) ).toHaveCount( 0 );
} );
