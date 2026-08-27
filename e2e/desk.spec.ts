import { expect, test } from '@playwright/test';
import { loginAsDesk } from './helpers/login';

test( 'desk is closed to guests and lists inquiries for staff', async ( { page } ) => {
	const guest = `E2E Guest ${Date.now()}`;

	await page.goto( '/desk/' );
	await expect( page.getByText( /desk book is for staff/i ) ).toBeVisible();

	await page.goto( '/booking/' );
	await page.getByLabel( 'Name' ).fill( guest );
	await page.getByLabel( 'Email' ).fill( 'e2e@example.com' );
	await page.getByLabel( 'Check in' ).fill( '2026-12-01' );
	await page.getByLabel( 'Check out' ).fill( '2026-12-03' );
	await page.getByRole( 'button', { name: 'Send inquiry' } ).click();
	await expect( page.locator( '.hb-inquiry__notice--ok' ) ).toBeVisible();

	await loginAsDesk( page );
	await expect( page.getByText( guest ) ).toBeVisible();
	await expect( page.locator( '.hb-desk__table' ) ).toBeVisible();
	await expect( page.locator( '.hb-desk__delete' ) ).toHaveCount( 0 );
} );
