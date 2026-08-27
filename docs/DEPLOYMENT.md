# Deployment

This repo is a **DDEV learning site**. Production only needs the theme and the plugin, not the whole WordPress tree, `wp-config.php`, or DDEV files.

Ship these two folders:

- [`wp-content/themes/hotel-booking/`](../wp-content/themes/hotel-booking/)
- [`wp-content/plugins/hotel-booking-core/`](../wp-content/plugins/hotel-booking-core/)

Do **not** deploy `.ddev/`, `tests/`, `e2e/`, `content/`, or `wp-config.php`. Do not reuse local accounts (`admin` / `admin`, `desk` / `desk`) on a real host. Do not put a Capacitor `ios/` or `android/` tree in this zip ([CAPACITOR.md](CAPACITOR.md) is docs-only).

Plugin and theme `build/` folders are gitignored. Before a zip, compile both the same way local DDEV does:

```bash
ddev build-blocks
```

Or without DDEV:

```bash
pnpm install --frozen-lockfile
pnpm --dir wp-content/plugins/hotel-booking-core run build
pnpm --dir wp-content/themes/hotel-booking run build
```

The zip must include those `build/` directories. Plugin activation creates the custom table `{$wpdb->prefix}hb_inquiries` (`wp_hb_inquiries` with the default prefix). Activate the plugin **before** the theme so shortcodes and the room CPT exist when templates load.

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

## Object cache (not in this zip)

Local DDEV runs Redis as a Docker service. The zip does **not** include that compose file or the Redis Object Cache plugin.

On a real host:

1. Run Redis or Valkey on `localhost` (or the platform’s cache host).
2. In `wp-config.php` (before `require wp-settings.php`):

```php
define( 'WP_REDIS_CLIENT', 'phpredis' );
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );
define( 'WP_REDIS_PREFIX', 'hotel-booking:' );
```

3. Install and activate [Redis Object Cache](https://wordpress.org/plugins/redis-cache/), then enable the drop-in (`wp redis enable` or the plugin settings screen). PHP needs the **phpredis** extension (or Predis if you set `WP_REDIS_CLIENT` to `predis`).

## Page cache (not in this zip)

Local DDEV caches anonymous HTML in **nginx FastCGI** (`X-Cache: HIT` / `MISS` / `BYPASS`). The zip does **not** include the DDEV `nginx_full` files.

On a real host, enable nginx FastCGI (or a CDN) with the same bypasses this demo uses:

- POST (inquiry `admin-post.php` saves)
- Cookies `wordpress_logged_in_`, `comment_author_`, `wp-postpass_`
- `/wp-admin/`, `wp-login.php`, `wp-cron.php`, `xmlrpc.php`
- `/wp-json/` (Stay rooms-grid filter)
- URLs with a query string (previews, search)

Do not copy the DDEV FastCGI files onto the server. Object cache (`ddev redis-flush`) does not empty the page cache.

## OpenSearch (not in this zip)

Local DDEV runs OpenSearch as a Docker service. The zip does **not** include that compose file. Room search falls back to `WP_Query` when the cluster is unset or down.

On a real host:

1. Run OpenSearch (or Elasticsearch with a compatible REST API) on the private network.
2. In `wp-config.php` (before `require wp-settings.php`):

```php
define( 'WP_OPENSEARCH_HOST', '127.0.0.1' );
define( 'WP_OPENSEARCH_PORT', 9200 );
```

3. After deploy, reindex published rooms: `wp hotel-booking reindex`.

Do not copy the DDEV OpenSearch compose file onto the server.

## RabbitMQ and WP-Cron (not in this zip)

Local DDEV runs RabbitMQ as a Docker service and ticks WP-Cron from a web daemon. The zip does **not** include that compose file or project `vendor/php-amqplib`. Desk email and OpenSearch still run **in-request** when `WP_AMQP_HOST` is unset.

On a real host:

1. Run RabbitMQ (or another AMQP broker) on the private network. Install [`php-amqplib/php-amqplib`](https://github.com/php-amqplib/php-amqplib) and [`symfony/workflow`](https://github.com/symfony/workflow) next to WordPress (`composer require` at the project root, or another autoload WordPress can see).
2. In `wp-config.php` (before `require wp-settings.php`):

```php
define( 'WP_AMQP_HOST', '127.0.0.1' );
define( 'WP_AMQP_PORT', 5672 );
define( 'WP_AMQP_USER', 'hotel' );
define( 'WP_AMQP_PASS', 'change-me' );
define( 'WP_AMQP_VHOST', '/' );
define( 'DISABLE_WP_CRON', true );
```

3. Tick cron and consume queues (systemd, Supervisor, or crontab):

```
* * * * * wp cron event run --due-now --path=/var/www/html --quiet
wp hotel-booking worker
```

Do not copy the DDEV RabbitMQ compose file onto the server. Inquiry workflow tables are created by the plugin (`dbDelta`); the Symfony component is not in the zip. See [WORKFLOW.md](WORKFLOW.md).

## Database scale (not in this zip)

The zip/SFTP flow above assumes **one MySQL**. Read replicas, `db.php` drop-ins, ProxySQL, and why WordPress core tables do not shard are documented in [SCALING.md](SCALING.md). Production OpenSearch and AMQP are host URLs as above, not DDEV services. Gettext **source** catalogs (`.pot` / `.po`) **are** inside those two folders. Compile `.mo` / `.l10n.php` / plugin editor `.json` with `ddev compile-i18n` before a zip if you need Spanish at runtime; see [I18N.md](I18N.md). Free Polylang is installed by local seed, not shipped in the zip. An inquiry `locale` column is not.
