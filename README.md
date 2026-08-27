# Hotel Booking — WordPress theme learning project

A DDEV WordPress site with a custom **block theme**, a companion **plugin** for rooms, hotel landing-page content, and `WP_UnitTestCase` tests.

**New here?** Follow [docs/ONBOARDING.md](docs/ONBOARDING.md) (accounts, seed data, first-hour click-through).

- Site: https://hotel-booking.ddev.site
- Admin: https://hotel-booking.ddev.site/wp-admin  
  User `admin` / password `admin` (local learning only)

## What you will learn

| Piece | Where |
| --- | --- |
| Block theme files (`theme.json`, HTML templates, patterns) | `wp-content/themes/hotel-booking/` |
| Plugin-territory PHP (CPT, custom table, REST, shortcodes, wp-admin, **blocks**) | `wp-content/plugins/hotel-booking-core/` |
| Demo hotel content | `ddev seed-content` |
| Theme review content + Theme Check | `ddev import-theme-unit-test` |
| PHPUnit + `WP_UnitTestCase` | `ddev phpunit` (lives **outside** the theme) |
| PHPCS, PHPStan, Plugin Check, dependency audits | `ddev phpcs`, `ddev phpstan`, `ddev plugin-check`, `ddev npm-audit` |

Block themes use `templates/*.html` instead of the classic PHP template hierarchy (`front-page.php`, `single.php`, …). WordPress still picks a template by the same *names*: `front-page.html` for the home page, `single-hb_room.html` for a room, `archive-hb_room.html` for `/rooms/`.

[WordPress.org theme review](https://make.wordpress.org/themes/handbook/review/) treats custom post types and shortcodes as **plugin territory**. Theme Check will fail if those stay in the theme, so rooms live in Hotel Booking Core.

## Start

Requires [DDEV](https://ddev.com/) and Docker. Full joiner steps (fresh clone, users, URLs): [docs/ONBOARDING.md](docs/ONBOARDING.md). Shipping the theme and plugin: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). Snapshots and recovery: [docs/BACKUP.md](docs/BACKUP.md). Primary/replica routing and sharding (docs only, not deployed): [docs/SCALING.md](docs/SCALING.md). Cron, queues, and search (docs only, not deployed): [docs/JOBS.md](docs/JOBS.md). Theme/plugin gettext plus content vs custom-table locale (Spanish catalogs shipped; Polylang not installed): [docs/I18N.md](docs/I18N.md).

```bash
ddev start
ddev seed-content
ddev launch
```

## Theme map

```
wp-content/themes/hotel-booking/
  style.css              Theme header, GPL copyright notice, booking-form CSS
  readme.txt             WordPress.org-style readme
  theme.json             Palette, fonts, fluid type, spacing (Site Editor → Styles)
  styles/dusk.json       Dark style variation (Appearance → Editor → Styles)
  styles/dawn.json       Light style variation (Appearance → Editor → Styles)
  functions.php          Setup, fonts, pattern category, theme blocks
  templates/             Block templates (front-page, single, archives, 404)
  parts/                 header.html (language switcher, color scheme toggle), footer.html
  patterns/              Landing-page sections (hero, rooms, amenities, stay FAQ, CTA)
  blocks/                Theme Gutenberg blocks (Stay FAQ, language switcher, color scheme toggle)
  template-parts/        PHP: inquiry-form.php, inquiries-list.php (`$wpdb` data)
  inc/patterns.php       Pattern category
  languages/             gettext POT, es_ES.po, es_ES.mo
```

The booking form **POSTs** into a custom MySQL table (`wp_hb_inquiries`). It is not a payment or reservation engine. Staff can read/update/delete rows on `/desk/` (log in as admin).

## Theme styles (fluid type, Dawn/Dusk, light/dark toggle)

Headings use fluid font sizes in `theme.json` (`clamp()` via `fluid.min` / `fluid.max`). Room Query loops use `minimumColumnWidth` so the grid wraps on small screens. `style.css` adds `@media` rules for the booking form and desk table.

**Dawn** (light) and **Dusk** (dark) style variations live in `styles/` (Appearance → Editor → Styles). Visitors can also switch the same palettes from the header **Dark** / **Light** toggle (`hotel-booking-theme/color-scheme-toggle`, Interactivity API, no `ddev build-blocks`). The choice is stored in `localStorage` and follows `prefers-color-scheme` until they click.

```bash
ddev phpunit --filter Test_Hotel_Booking_Theme
ddev e2e e2e/home.spec.ts
```

## PHPUnit (`WP_UnitTestCase`)

Test files are at the **project root**, not in the theme. Theme Check treats `phpunit.xml.dist` and `bin/install-wp-tests.sh` as production files if they ship inside the theme.

```
composer.json
phpunit.xml.dist
bin/install-wp-tests.sh
tests/bootstrap.php
tests/test-*.php
```

DDEV wipes `/tmp` on restart, so the WordPress test suite is stored in `.wp-tests/` and uses a separate `wordpress_test` database (created on `ddev start`).

```bash
ddev setup-tests    # once (or after deleting .wp-tests)
ddev phpunit
ddev phpunit --filter test_theme_is_block_theme
```

Tests cover:

- Theme is active and `wp_is_block_theme()` is true
- Fluid `theme.json` font sizes, Dawn/Dusk style variations, and `@media` in `style.css`
- `hb_room` is registered by the plugin
- `hotel_booking_format_price()`
- `$this->factory()->post->create()` plus meta and `WP_Query`
- `[hotel_room_meta]` shortcode
- `GET /wp-json/hotel-booking/v1/rooms` REST catalog
- Custom table CRUD (`hotel_booking_insert_inquiry`, get, update, delete)
- Inquiry `admin-post` save + `go_to()` booking/desk HTML
- wp-admin inquiries list and Settings API
- Block patterns / pattern category
- `set_up()` / `tear_down()` calling `parent::`

## Integration and e2e

PHPUnit **integration** tests post the inquiry form through `hotel_booking_handle_save_inquiry()` (redirect + custom table) and render booking/desk pages with `go_to()`.

```bash
ddev phpunit --filter Test_Hotel_Booking_Integration
```

Playwright **e2e** runs on the host against the DDEV site (Chromium). Needs `ddev start` and `ddev seed-content`.

```bash
npm install
npx playwright install chromium
ddev e2e
# or: npm run e2e
```

CI boots the same specs with `@wordpress/env` (`npx wp-env start` + `e2e/wp-env-seed.sh`) at `http://localhost:8888`. The seed script compiles Spanish `.po` files to `.mo` / `.l10n.php` because those binaries are gitignored.

## wp-admin (plugin)

Hotel Booking Core adds **Hotel Booking** in the dashboard (capability `edit_posts` for inquiries, `manage_options` for settings).

```bash
# Log in as admin / admin
open https://hotel-booking.ddev.site/wp-admin/admin.php?page=hotel-booking
open https://hotel-booking.ddev.site/wp-admin/admin.php?page=hotel-booking-settings
ddev phpunit --filter Test_Hotel_Booking_Admin
```

Settings (`hotel_booking_settings`): hotel name, desk email, max guests (clamps the public booking form dropdown).

## Custom table (inquiries)

Hotel Booking Core creates `{$wpdb->prefix}hb_inquiries` with `dbDelta` on activation (and on `plugins_loaded` if the schema version is stale).

| Function | Role |
| --- | --- |
| `hotel_booking_insert_inquiry()` | `$wpdb->insert` after sanitize/validate |
| `hotel_booking_get_inquiry()` | `$wpdb->get_row` + `$wpdb->prepare` |
| `hotel_booking_get_inquiries()` | `$wpdb->get_results` |
| `hotel_booking_update_inquiry()` | `$wpdb->update` |
| `hotel_booking_delete_inquiry()` | `$wpdb->delete` |

The theme only renders: [template-parts/inquiry-form.php](wp-content/themes/hotel-booking/template-parts/inquiry-form.php) and [template-parts/inquiries-list.php](wp-content/themes/hotel-booking/template-parts/inquiries-list.php), loaded via `[hotel_inquiry_form]` and `[hotel_inquiry_list]`.

```bash
# Book a stay (public)
open https://hotel-booking.ddev.site/booking/

# Staff desk (log in as admin first)
open https://hotel-booking.ddev.site/desk/

ddev phpunit --filter Test_Hotel_Booking_Inquiries
```

## Rooms API

Custom namespace (not the generic `/wp/v2/hb_room` post payload):

```bash
curl -s https://hotel-booking.ddev.site/wp-json/hotel-booking/v1/rooms
curl -s https://hotel-booking.ddev.site/wp-json/hotel-booking/v1/rooms?guests=4
curl -s https://hotel-booking.ddev.site/wp-json/hotel-booking/v1/rooms/10
ddev phpunit --filter Test_Hotel_Booking_Rest_Rooms
```

Each room includes `id`, `title`, `slug`, `excerpt`, `permalink`, `price`, `price_formatted`, `guests`, `beds`, and `size`. Only published rooms are returned.

## Content

Hotel demo (default):

```bash
ddev seed-content
ddev seed-content --force
```

Creates Home, About, Amenities, Contact, Booking, Desk, five rooms, demo users (`desk`, `guest`), sample inquiries, and hotel settings. First hour: [docs/ONBOARDING.md](docs/ONBOARDING.md). Dataset details: [`content/README.md`](content/README.md).

Theme Unit Test data (mixed posts/comments/media used in theme review):

```bash
ddev import-theme-unit-test
```

Then open **Appearance → Theme Check** and browse archives, singles, comments, and search. Check the **theme** folder only; plugin-territory code is in Hotel Booking Core.

## Custom blocks

Hotel Booking Core registers six Gutenberg blocks (category **Hotel Booking** in the inserter). Drop them onto any page:

- Booking CTA, Room card, Rooms grid (guest filter via the Interactivity API + REST)
- Inquiry form, Inquiry list (same POST/admin-post as the shortcodes)
- Amenities accordion (Interactivity API)

Source is `wp-content/plugins/hotel-booking-core/src/`. Compiled files in `build/` are gitignored — compile after clone or after editing block JS/CSS:

```bash
ddev build-blocks
```

Demo composition: https://hotel-booking.ddev.site/stay/

```bash
ddev phpunit --filter Test_Hotel_Booking_Blocks
ddev e2e e2e/stay.spec.ts
```

The **theme** also registers a Stay FAQ accordion (`hotel-booking-theme/stay-faq`) under the core **Theme** category. It uses the same Interactivity API (`data-wp-interactive`, `store`) with an unbundled `view.js` — no theme webpack, no `ddev build-blocks`. Home (`front-page.html`) includes the `hotel-booking/stay-faq` pattern.

```bash
ddev phpunit --filter Test_Hotel_Booking_Theme
ddev e2e e2e/home.spec.ts
```

## Static analysis and security

PHPCS uses WordPress Extra (escaping, nonces, prepared SQL) plus PHPCompatibility for PHP 8.2+. PHPStan runs at level 5 with WordPress stubs. Plugin Check is the WordPress.org PCP tool. Audits cover Composer and npm (`--audit-level=high`).

```bash
ddev phpcs
ddev phpstan
ddev plugin-check
ddev composer audit
ddev npm-audit
```

CI runs the same suite on push and pull request.

## What to read next

- [Block themes](https://developer.wordpress.org/themes/block-themes/) vs [classic template hierarchy](https://developer.wordpress.org/themes/templates/template-hierarchy/)
- [Fluid typography](https://developer.wordpress.org/news/2023/03/fluid-typography-wordpress-6-1/)
- [Style variations](https://developer.wordpress.org/themes/global-settings-and-styles/style-variations/)
- [Theme Unit Test](https://codex.wordpress.org/Theme_Unit_Test)
- [Plugin / theme unit tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/) and `WP_UnitTestCase` factories (`go_to()`, `the_content()`)
- [Interactivity API](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/) (`data-wp-interactive`, `store`)
- [Block editor handbook](https://developer.wordpress.org/block-editor/) (`block.json`, `register_block_type`)
- [Settings API](https://developer.wordpress.org/plugins/settings/settings-api/) (`register_setting`, `add_menu_page`)
- [REST API Handbook](https://developer.wordpress.org/rest-api/) (`register_rest_route`, `rest_do_request`)

## Commands

| Command | Purpose |
| --- | --- |
| `ddev start` / `ddev launch` | Run the site |
| `ddev seed-content` | Hotel pages, rooms, users, inquiries, settings |
| `ddev snapshot` / `ddev snapshot restore` | Named DB (+ files) snapshot; see [BACKUP.md](docs/BACKUP.md) |
| `ddev export-db` / `ddev import-db` | SQL-only dump and restore |
| `ddev import-theme-unit-test` | Official theme-review XML + Theme Check |
| `ddev setup-tests` | Composer + WordPress PHPUnit library |
| `ddev phpunit` | Run `WP_UnitTestCase` tests |
| `ddev build-blocks` | Compile plugin Gutenberg blocks (`@wordpress/scripts`) |
| `ddev phpcs` | WordPress Extra + PHPCompatibility on theme and plugin |
| `ddev phpstan` | PHPStan level 5 with WordPress stubs |
| `ddev plugin-check` | WordPress Plugin Check on hotel-booking-core |
| `ddev make-pot` | Regenerate theme and plugin `.pot` catalogs (does not overwrite `.po`) |
| `ddev compile-i18n` | Compile `.po` to `.mo`, `.l10n.php`, and plugin editor JSON |
| `ddev npm-audit` | npm audit (high and critical) |
| `ddev e2e` | Playwright against the DDEV site |
| `ddev wp …` | Any WP-CLI command |
