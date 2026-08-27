# Content data

This folder is for **theme testing content**, not application code.

There are two separate datasets on purpose:

## 1. Hotel demo (default)

Creates a small boutique-hotel site you can click through while learning the theme.

```bash
ddev seed-content
ddev seed-content --force   # rebuild rooms and pages
```

What it creates:

- Static front page (`Home`) that uses `templates/front-page.html`
- Pages: About, Amenities, Contact, Booking, Desk, Stay (Gutenberg blocks demo)
- Five `hb_room` posts (Deluxe King, Garden Suite, Family Room, Penthouse, Courtyard Twin)
- Users: `desk` / `desk` (editor), `guest` / `guest` (subscriber); `admin` / `admin` from install
- About six sample rows in `wp_hb_inquiries` (`pending`, `contacted`, `closed`)
- Settings: hotel name The Oak House, desk email, max guests 6
- A Primary `wp_navigation` menu for the block header
- Pretty permalinks (`/%postname%/`)

Joiner walkthrough: [`docs/ONBOARDING.md`](../docs/ONBOARDING.md). Extra fake data is applied by [`content/seed-demo.php`](seed-demo.php).

After seeding, WP-CLI can export a WXR snapshot:

```bash
ddev wp export --dir=content --filename_format=hotel-booking-demo.xml --post_type=page,hb_room
```

`hotel-booking-demo.xml` is optional practice for **Tools → Import**. The seed command is the source of truth.

## 2. Theme Unit Test (on demand)

This is the official mixed-content dump used in [WordPress theme review](https://codex.wordpress.org/Theme_Unit_Test): long titles, comments, sticky posts, media, HTML in content, and so on. It is **not** hotel-themed.

```bash
ddev import-theme-unit-test
```

That command:

1. Installs and activates `wordpress-importer` and `theme-check`
2. Downloads [themeunittestdata.wordpress.xml](https://raw.githubusercontent.com/WPTT/theme-unit-test/master/themeunittestdata.wordpress.xml) into this folder
3. Imports it with `wp import`

Then open **Appearance → Theme Check** and browse archives, singles, comments, and search using the imported posts.

The XML file is gitignored because it is large and fetched on demand.

## What belongs where

| Goal | Use |
| --- | --- |
| Learn the hotel landing page, desk, and inquiries | `ddev seed-content` |
| Stress-test templates like a reviewer | `ddev import-theme-unit-test` |
| Automated assertions | `ddev phpunit` (`WP_UnitTestCase`) |
