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
| PHPCS, PHPStan, PHPMD, TypeScript, Plugin Check, dependency audits | `ddev phpcs`, `ddev phpstan` ([generics PHPDoc](docs/PHPSTAN.md)), `ddev phpmd` ([PR coverage and complexity](docs/QUALITY.md)), `ddev typecheck`, `ddev plugin-check`, `ddev pnpm-audit` |

Block themes use `templates/*.html` instead of the classic PHP template hierarchy (`front-page.php`, `single.php`, …). WordPress still picks a template by the same *names*: `front-page.html` for the home page, `single-hb_room.html` for a room, `archive-hb_room.html` for `/rooms/`.

[WordPress.org theme review](https://make.wordpress.org/themes/handbook/review/) treats custom post types and shortcodes as **plugin territory**. Theme Check will fail if those stay in the theme, so rooms live in Hotel Booking Core.

## Start

Requires [DDEV](https://ddev.com/) and Docker. Full joiner steps (fresh clone, users, URLs): [docs/ONBOARDING.md](docs/ONBOARDING.md). Shipping the theme and plugin: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). Snapshots and recovery: [docs/BACKUP.md](docs/BACKUP.md). Redis object cache and nginx FastCGI page cache on DDEV; replicas/sharding still docs-only: [docs/SCALING.md](docs/SCALING.md). WP-Cron, RabbitMQ jobs, and OpenSearch room search on DDEV: [docs/JOBS.md](docs/JOBS.md). Inquiry desk state machine (not Temporal): [docs/WORKFLOW.md](docs/WORKFLOW.md). Theme/plugin gettext plus Polylang room/page copies (plugin not committed): [docs/I18N.md](docs/I18N.md). Capacitor iOS/Android shell still docs-only: [docs/CAPACITOR.md](docs/CAPACITOR.md). Front-end staff login and hotel_manager policies: [docs/AUTH.md](docs/AUTH.md). Sibling plugins, shared `theme.json`, and reversible uninstall still docs-only: [docs/PLUGINS.md](docs/PLUGINS.md). Prometheus, Grafana, Loki, and Tempo on DDEV: [docs/OBSERVABILITY.md](docs/OBSERVABILITY.md). PHP `debug.log`, Query Monitor, and how to read errors: [docs/DEBUG.md](docs/DEBUG.md).

```bash
ddev start
ddev seed-content
ddev launch
```

## Theme map

```
wp-content/themes/hotel-booking/
  style.css              Theme headers only (WordPress requires this file)
  src/scss/screen.scss   Front-end CSS (compiled to build/screen.css)
  src/                   Theme Gutenberg blocks (Stay FAQ, language switcher, color scheme toggle)
  package.json           @wordpress/scripts + sass (ddev build-blocks)
  readme.txt             WordPress.org-style readme
  theme.json             Palette, fonts, fluid type, spacing (Site Editor → Styles)
  styles/dusk.json       Dark style variation (Appearance → Editor → Styles)
  styles/dawn.json       Light style variation (Appearance → Editor → Styles)
  functions.php          Setup, fonts, pattern category, theme blocks
  templates/             Block templates (front-page, single, archives, 404)
  parts/                 header.html (language switcher, color scheme toggle), footer.html
  patterns/              Landing-page sections (hero, rooms, amenities, stay FAQ, CTA)
  template-parts/        PHP: inquiry-form.php, inquiries-list.php (`$wpdb` data)
  inc/patterns.php       Pattern category
  languages/             gettext POT, es_ES.po, es_ES.mo
```

The booking form **POSTs** into a custom MySQL table (`wp_hb_inquiries`). It is not a payment or reservation engine. Staff can read/update rows on `/desk/` after `/staff-login/` as `desk` / `desk`.

## Theme styles (fluid type, Dawn/Dusk, light/dark toggle)

Headings use fluid font sizes in `theme.json` (`clamp()` via `fluid.min` / `fluid.max`). Room Query loops use `minimumColumnWidth` so the grid wraps on small screens. `src/scss/screen.scss` compiles to `build/screen.css` with `@media` rules for the booking form, desk table, and overlay nav.

**Dawn** (light) and **Dusk** (dark) style variations live in `styles/` (Appearance → Editor → Styles). Visitors can also switch the same palettes from the header **Dark** / **Light** toggle (`hotel-booking-theme/color-scheme-toggle`, Interactivity API). The choice is stored in `localStorage` and follows `prefers-color-scheme` until they click.

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
- Fluid `theme.json` font sizes, Dawn/Dusk style variations, and `@media` in `build/screen.css`
- `hb_room` is registered by the plugin
- `hotel_booking_format_price()`
- `$this->factory()->post->create()` plus meta and `WP_Query`
- `[hotel_room_meta]` shortcode
- `GET /wp-json/hotel-booking/v1/rooms` REST catalog
- Custom table CRUD (`hotel_booking_insert_inquiry`, get, update, delete)
- Inquiry `admin-post` save + `go_to()` booking/desk HTML
- Desk email fallback, stale-pending query, and digest count when AMQP is unset
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
pnpm install
pnpm exec playwright install chromium
ddev e2e
# or: pnpm run e2e
```

CI boots the same specs with `@wordpress/env` (`pnpm exec wp-env start` + `e2e/wp-env-seed.sh`) at `http://localhost:8888`. The seed script compiles Spanish `.po` files to `.mo` / `.l10n.php` because those binaries are gitignored.

## wp-admin (plugin)

Hotel Booking Core adds **Hotel Booking** in the dashboard (capability `manage_options` for inquiries and settings). Hotel managers use `/staff-login/` → `/desk/` and cannot open wp-admin. See [docs/AUTH.md](docs/AUTH.md).

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

# Staff desk (log in at /staff-login/ as desk / desk)
open https://hotel-booking.ddev.site/staff-login/
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

Source is `wp-content/plugins/hotel-booking-core/src/` (TypeScript and SCSS). Compiled files in `build/` are gitignored — compile after clone with `ddev build-blocks`. While you work, leave a watcher running instead of rebuilding by hand (see [Live development](#live-development)).

Demo composition: https://hotel-booking.ddev.site/stay/

```bash
ddev phpunit --filter Test_Hotel_Booking_Blocks
ddev e2e e2e/stay.spec.ts
```

The **theme** also registers Stay FAQ, color-scheme toggle, and language switcher (`hotel-booking-theme/*`) under the core **Theme** category. They use the same Interactivity API (`data-wp-interactive`, `store`) and compile from theme `src/` into `build/`. Home (`front-page.html`) includes the `hotel-booking/stay-faq` pattern.

```bash
ddev phpunit --filter Test_Hotel_Booking_Theme
ddev e2e e2e/home.spec.ts
```

## Live development

WordPress loads compiled files from `build/`, not the TypeScript or SCSS sources. `ddev build-blocks` is a one-shot compile (clone, CI, zip). For day-to-day work, leave webpack and Sass watching `src/` so each save rewrites `build/`. Then refresh the page.

PHP, HTML templates, patterns, and `theme.json` are not compiled. Edit them and refresh.

| You change | Watcher | Check it on |
| --- | --- | --- |
| Plugin `src/` (`.ts`, `.tsx`, `.scss`) | `ddev watch-plugin` | `/stay/`, block editor (Hotel Booking category) |
| Theme blocks `src/stay-faq/`, `color-scheme-toggle/`, `language-switcher/` | `ddev watch-theme` | Home FAQ, header Dark/Light, English/Español |
| Theme `src/scss/screen.scss` | included in `ddev watch-theme` | Booking form, desk table, overlay nav |
| `functions.php`, `render.php`, templates, patterns, `theme.json` | none | Refresh the site or Site Editor |

Run the plugin and theme watchers in **two terminals** if you are touching both:

```bash
ddev watch-plugin
```

```bash
ddev watch-theme
```

Wait until the terminal prints a webpack/Sass compile, then reload https://hotel-booking.ddev.site (hard refresh if CSS looks stale). The block editor needs the same reload after a plugin or theme block change. Front-end Interactivity (FAQ accordion, rooms-grid `4+` filter, color scheme) is not hot-reloaded — a normal refresh is enough.

Equivalent without DDEV wrappers (from the project root):

```bash
( cd wp-content/plugins/hotel-booking-core && pnpm start )
( cd wp-content/themes/hotel-booking && pnpm run start:css )
( cd wp-content/themes/hotel-booking && pnpm start )
```

Do not pass `--watch` to `ddev build-blocks`: that command builds the plugin, then the theme, so a watch on the plugin would never reach the theme. Stop the watchers with Ctrl+C. Ship a zip with `ddev build-blocks`, not a watch compile.

## Static analysis and security

PHPCS uses WordPress Extra (escaping, nonces, prepared SQL) plus PHPCompatibility for PHP 8.2+. PHPStan runs at level 5 with WordPress stubs ([PHPDoc `@template` helpers](docs/PHPSTAN.md)). PHPMD codesize (cyclomatic / NPath / method length) and PHPUnit line coverage fail CI below floors in [`.github/quality-thresholds.json`](.github/quality-thresholds.json) ([QUALITY.md](docs/QUALITY.md)). TypeScript is `tsc --noEmit` on plugin and theme `src/` (`ddev typecheck`). Plugin Check is the WordPress.org PCP tool. Audits cover Composer and pnpm (`--audit-level=high`).

```bash
ddev phpcs
ddev phpstan
ddev phpmd
ddev typecheck
ddev plugin-check
ddev composer audit
ddev pnpm-audit
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
| `ddev seed-content` | Hotel pages, rooms, users, inquiries, settings, Redis Object Cache, OpenSearch reindex |
| `ddev wp hotel-booking reindex` | Rebuild the OpenSearch rooms index |
| `ddev wp hotel-booking worker` | Consume RabbitMQ email and search queues (also a DDEV daemon) |
| `ddev wp hotel-booking remind-stale` / `digest` / `workflow tick` | Run inquiry jobs and due workflow timers now |
| `ddev rabbitmq launch` | RabbitMQ management UI |
| `ddev launch :9091` | Prometheus UI (scrapes `/wp-json/hotel-booking/v1/metrics`) |
| `ddev launch :3001` | Grafana (anonymous Viewer, or `admin` / `admin`) |
| `ddev launch :3101/ready` | Loki health check (no UI; logs are in Grafana) |
| `ddev launch :3201/ready` | Tempo health check (no UI; traces are in Grafana) |
| `ddev demo-observability` | Curl REST + demo log/warning so Grafana has data |
| `ddev logs` | php-fpm / nginx stderr; file log is `wp-content/debug.log` ([DEBUG.md](docs/DEBUG.md)) |
| `ddev xdebug on` / `off` | Step-through PHP debugger (off by default) |
| `ddev snapshot` / `ddev snapshot restore` | Named DB (+ files) snapshot; see [BACKUP.md](docs/BACKUP.md) |
| `ddev export-db` / `ddev import-db` | SQL-only dump and restore |
| `ddev import-theme-unit-test` | Official theme-review XML + Theme Check |
| `ddev setup-tests` | Composer + WordPress PHPUnit library |
| `ddev phpunit` | Run `WP_UnitTestCase` tests |
| `ddev build-blocks` | Compile plugin and theme blocks plus theme CSS (`@wordpress/scripts`) |
| `ddev watch-plugin` | Rebuild plugin blocks into `build/` on each save |
| `ddev watch-theme` | Rebuild theme CSS and blocks into `build/` on each save |
| `ddev phpcs` | WordPress Extra + PHPCompatibility on theme and plugin |
| `ddev phpstan` | PHPStan level 5 with WordPress stubs ([docs/PHPSTAN.md](docs/PHPSTAN.md)) |
| `ddev phpmd` | PHPMD codesize (cyclomatic / NPath) on theme and plugin ([docs/QUALITY.md](docs/QUALITY.md)) |
| `ddev typecheck` | TypeScript `tsc --noEmit` on plugin and theme `src/` |
| `ddev plugin-check` | WordPress Plugin Check on hotel-booking-core |
| `ddev make-pot` | Regenerate theme and plugin `.pot` catalogs (does not overwrite `.po`) |
| `ddev compile-i18n` | Compile `.po` to `.mo`, `.l10n.php`, and plugin editor JSON |
| `ddev pnpm-audit` | pnpm audit (high and critical) |
| `ddev e2e` | Playwright against the DDEV site |
| `ddev redis-cli` / `ddev redis-flush` | Redis CLI and flush object cache |
| `ddev nginx-cache-flush` | Flush nginx FastCGI page cache |
| `ddev wp …` | Any WP-CLI command |
