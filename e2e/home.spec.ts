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
