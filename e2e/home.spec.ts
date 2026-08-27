import { expect, test } from '@playwright/test';

test( 'home shows the hotel heading and links to rooms', async ( { page } ) => {
	await page.goto( '/' );

	await expect( page.getByRole( 'heading', { name: /quiet night/i } ) ).toBeVisible();
	await page.getByRole( 'link', { name: 'Rooms' } ).first().click();
	await expect( page ).toHaveURL( /\/rooms\/?/ );
} );

test( 'home stay FAQ accordion toggles with Interactivity', async ( { page } ) => {
	await page.goto( '/' );

	const faq = page.locator( '.hb-stay-faq' );
	await expect( faq ).toBeVisible();
	await expect( faq.getByText( /rooms are ready after 3pm/i ) ).toBeVisible();

	await faq.getByRole( 'button', { name: 'Quiet hours' } ).click();

	await expect( faq.getByText( /after 10pm the house stays still/i ) ).toBeVisible();
	await expect( faq.getByText( /rooms are ready after 3pm/i ) ).toBeHidden();
} );

test( 'home color scheme toggle switches light and dark', async ( { page } ) => {
	await page.addInitScript( () => {
		localStorage.setItem( 'hotel-booking-color-scheme', 'light' );
	} );

	await page.goto( '/' );

	const html = page.locator( 'html' );
	await expect( html ).toHaveAttribute( 'data-color-scheme', 'light' );

	await page.getByRole( 'button', { name: 'Use dark appearance' } ).click();
	await expect( html ).toHaveAttribute( 'data-color-scheme', 'dark' );

	await page.getByRole( 'button', { name: 'Use light appearance' } ).click();
	await expect( html ).toHaveAttribute( 'data-color-scheme', 'light' );
} );

test( 'header language switcher translates chrome to Spanish', async ( { page } ) => {
	await page.goto( '/' );

	await page.getByRole( 'navigation', { name: 'Language' } ).getByRole( 'link', { name: 'Español' } ).click();

	await expect( page.getByRole( 'heading', { name: /noche tranquila/i } ) ).toBeVisible();
	await expect( page.getByRole( 'button', { name: 'Usar apariencia oscura' } ) ).toBeVisible();
	await expect( page.getByRole( 'heading', { name: 'King Deluxe' } ) ).toBeVisible();
	await expect( page.getByRole( 'heading', { name: 'Deluxe King' } ) ).toHaveCount( 0 );

	await page.getByRole( 'link', { name: 'English' } ).click();
	await expect( page.getByRole( 'heading', { name: /quiet night/i } ) ).toBeVisible();
} );
