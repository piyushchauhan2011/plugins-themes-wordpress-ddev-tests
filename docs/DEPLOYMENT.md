# Deployment

This repo is a **DDEV learning site**. Production only needs the theme and the plugin, not the whole WordPress tree, `wp-config.php`, or DDEV files.

Ship these two folders:

- [`wp-content/themes/hotel-booking/`](../wp-content/themes/hotel-booking/)
- [`wp-content/plugins/hotel-booking-core/`](../wp-content/plugins/hotel-booking-core/)

Do **not** deploy `.ddev/`, `tests/`, `e2e/`, `content/`, or `wp-config.php`. Do not reuse local accounts (`admin` / `admin`, `desk` / `desk`) on a real host.

Plugin activation creates the custom table `{$wpdb->prefix}hb_inquiries` (`wp_hb_inquiries` with the default prefix). Activate the plugin **before** the theme so shortcodes and the room CPT exist when templates load.

## SFTP / zip

1. Zip the two folders above (or copy them as-is).
2. Upload into an existing WordPress install:
   - theme → `wp-content/themes/hotel-booking/`
   - plugin → `wp-content/plugins/hotel-booking-core/`
3. Activate plugin, then theme:

```bash
wp plugin activate hotel-booking-core
wp theme activate hotel-booking
wp rewrite flush
```

Same steps in wp-admin: **Plugins**, then **Appearance → Themes**, then **Settings → Permalinks → Save**.

## Git on the server

Clone this repo (or a sparse checkout of those two paths) next to WordPress, then symlink or copy them into `wp-content/themes/` and `wp-content/plugins/`. Keep the host’s `wp-config.php` and uploads out of git — they are already gitignored in this project.

## Content

[`ddev seed-content`](../content/README.md) is **local demo only**. Production uses real pages and rooms, or a WXR import (Tools → Import). After a URL change:

```bash
wp search-replace 'https://hotel-booking.ddev.site' 'https://example.com' --skip-columns=guid
```

Inquiries live in a **custom table**, not in WXR. Shipping theme/plugin code without a database dump leaves `/desk/` empty even if rooms and pages import correctly. Copy the database (and uploads) separately — see [BACKUP.md](BACKUP.md).

## Database scale (not in this zip)

The zip/SFTP flow above assumes **one MySQL**. Read replicas, `db.php` drop-ins, ProxySQL, and why WordPress core tables do not shard are documented in [SCALING.md](SCALING.md). Cron, queues, and Elasticsearch sketches in [JOBS.md](JOBS.md) are also **not** part of deploying the theme and plugin folders. Gettext **source** catalogs (`.pot` / `.po`) **are** inside those two folders. Compile `.mo` / `.l10n.php` / plugin editor `.json` with `ddev compile-i18n` before a zip if you need Spanish at runtime; see [I18N.md](I18N.md). Free Polylang is installed by local seed, not shipped in the zip. An inquiry `locale` column is not.
