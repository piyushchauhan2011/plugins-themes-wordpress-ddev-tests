import type { Page } from '@playwright/test';

export async function loginAsAdmin( page: Page ): Promise<void> {
	const user = process.env.WP_USERNAME ?? 'admin';
	const pass = process.env.WP_PASSWORD ?? 'admin';

	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( user );
	await page.locator( '#user_pass' ).fill( pass );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( /\/wp-admin/ );
}

export async function loginAsDesk( page: Page ): Promise<void> {
	await page.goto( '/staff-login/' );
	await page.locator( '.hb-staff-login input[name="log"]' ).fill( 'desk' );
	await page.locator( '.hb-staff-login input[name="pwd"]' ).fill( 'desk' );
	await page.locator( '.hb-staff-login button[type="submit"]' ).click();
	await page.waitForURL( /\/desk/ );
}
