import { expect, test } from '@playwright/test';

test( 'booking form saves an inquiry', async ( { page } ) => {
	await page.goto( '/booking/' );

	await page.getByLabel( 'Name' ).fill( 'Ada Lovelace' );
	await page.getByLabel( 'Email' ).fill( 'ada@example.com' );
	await page.getByLabel( 'Check in' ).fill( '2026-11-01' );
	await page.getByLabel( 'Check out' ).fill( '2026-11-03' );
	await page.getByRole( 'button', { name: 'Send inquiry' } ).click();

	await expect( page.locator( '.hb-inquiry__notice--ok' ) ).toBeVisible();
	await expect( page.getByText( 'Ada Lovelace' ) ).toBeVisible();
} );
