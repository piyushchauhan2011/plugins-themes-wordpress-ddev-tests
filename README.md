# Hotel Booking — WordPress theme learning project

A DDEV WordPress site with a custom **block theme**, hotel landing-page content, and `WP_UnitTestCase` tests.

- Site: https://hotel-booking.ddev.site
- Admin: https://hotel-booking.ddev.site/wp-admin  
  User `admin` / password `admin` (local learning only)

## What you will learn

| Piece | Where |
| --- | --- |
| Block theme files (`theme.json`, HTML templates, patterns) | `wp-content/themes/hotel-booking/` |
| PHP you can unit-test (CPT, helpers, pattern category) | `wp-content/themes/hotel-booking/inc/` |
| Demo hotel content | `ddev seed-content` |
| Theme review content + Theme Check | `ddev import-theme-unit-test` |
| PHPUnit + `WP_UnitTestCase` | `ddev phpunit` |

Block themes use `templates/*.html` instead of the classic PHP template hierarchy (`front-page.php`, `single.php`, …). WordPress still picks a template by the same *names*: `front-page.html` for the home page, `single-hb_room.html` for a room, `archive-hb_room.html` for `/rooms/`.

## Start

Requires [DDEV](https://ddev.com/) and Docker.

```bash
cd hotel-booking
ddev start
ddev wp theme activate hotel-booking
ddev seed-content
ddev launch
```

WordPress core is downloaded into this folder by WP-CLI and is gitignored. After a fresh clone:

```bash
ddev start
ddev wp core download
ddev wp core install --url='$DDEV_PRIMARY_URL' --title='Hotel Booking' --admin_user=admin --admin_password=admin --admin_email=admin@hotel-booking.ddev.site --skip-email
ddev wp theme activate hotel-booking
ddev seed-content
```

## Theme map

```
wp-content/themes/hotel-booking/
  style.css              Theme header + a little CSS for the booking form
  theme.json             Palette, fonts, spacing (Site Editor → Styles)
  functions.php          Loads inc/, enqueues fonts
  templates/             Block templates (front-page, single, archives, 404)
  parts/                 header.html, footer.html
  patterns/              Landing-page sections (hero, rooms, amenities, CTA)
  inc/post-types.php     hb_room CPT + post meta
  inc/helpers.php        Price formatting — easy PHPUnit targets
  inc/patterns.php       Pattern category
  tests/                 WP_UnitTestCase examples
```

`hb_room` lives in the theme so you can learn registration and factories in one place. [WordPress.org theme review](https://make.wordpress.org/themes/handbook/review/) treats custom post types as **plugin territory**; a production hotel site would move that PHP to a plugin.

The booking form is a **GET inquiry** to `/booking/`. It is not a payment or reservation engine.

## Content

Hotel demo (default):

```bash
ddev seed-content
ddev seed-content --force
```

Creates Home, About, Amenities, Contact, Booking, four rooms with images, and a Primary navigation. Details: [`content/README.md`](content/README.md).

Theme Unit Test data (mixed posts/comments/media used in theme review):

```bash
ddev import-theme-unit-test
```

Then open **Appearance → Theme Check** and browse archives, singles, comments, and search.

A tiny WXR file at [`content/hotel-booking-demo.xml`](content/hotel-booking-demo.xml) is there so you can practice **Tools → Import**. Prefer `ddev seed-content` for the full hotel demo.

## PHPUnit (`WP_UnitTestCase`)

DDEV wipes `/tmp` on restart, so the WordPress test suite is stored in `.wp-tests/` and uses a separate `wordpress_test` database (created on `ddev start`).

```bash
ddev setup-tests    # once (or after deleting .wp-tests)
ddev phpunit
ddev phpunit --filter test_theme_is_block_theme
```

Tests cover:

- Theme is active and `wp_is_block_theme()` is true
- `hb_room` is registered
- `hotel_booking_format_price()`
- `$this->factory()->post->create()` plus meta and `WP_Query`
- Block patterns / pattern category
- `set_up()` / `tear_down()` calling `parent::`

Read [`tests/bootstrap.php`](wp-content/themes/hotel-booking/tests/bootstrap.php) to see how the suite loads Yoast polyfills, then WordPress, then this theme.

## What to read next

- [Block themes](https://developer.wordpress.org/themes/block-themes/) vs [classic template hierarchy](https://developer.wordpress.org/themes/templates/template-hierarchy/)
- [Theme Unit Test](https://codex.wordpress.org/Theme_Unit_Test)
- [Plugin / theme unit tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/) and `WP_UnitTestCase` factories
- `ddev wp scaffold theme-tests --help`

## Commands

| Command | Purpose |
| --- | --- |
| `ddev start` / `ddev launch` | Run the site |
| `ddev seed-content` | Hotel pages, rooms, navigation |
| `ddev import-theme-unit-test` | Official theme-review XML + Theme Check |
| `ddev setup-tests` | Composer + WordPress PHPUnit library |
| `ddev phpunit` | Run `WP_UnitTestCase` tests |
| `ddev wp …` | Any WP-CLI command |
