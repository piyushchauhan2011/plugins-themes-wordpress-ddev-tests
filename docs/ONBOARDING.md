# First hour

This is the joiner path. You should not start from an empty WordPress site. After seed you have pages, five rooms, sample inquiries, and three local users.

Requires [DDEV](https://ddev.com/) and Docker. Deeper topic notes live in the [root README](../README.md).

## Accounts (local only)

| User | Password | Role | What it is for |
| --- | --- | --- | --- |
| `admin` | `admin` | Administrator | Settings, wp-admin, everything |
| `desk` | `desk` | Hotel manager | `/staff-login/` → `/desk/` (not wp-admin, not Settings, cannot delete) |
| `guest` | `guest` | Subscriber | Public site only; desk stays closed |

Site: https://hotel-booking.ddev.site  
wp-admin: https://hotel-booking.ddev.site/wp-admin

## Start

Already installed (usual):

```bash
cd hotel-booking
ddev start
ddev wp plugin activate hotel-booking-core
ddev wp theme activate hotel-booking
ddev build-blocks
ddev seed-content
ddev launch
```

Fresh clone (WordPress core is gitignored):

```bash
ddev start
ddev wp core download
ddev wp core install --url='$DDEV_PRIMARY_URL' --title='Hotel Booking' --admin_user=admin --admin_password=admin --admin_email=admin@hotel-booking.ddev.site --skip-email
ddev wp plugin activate hotel-booking-core
ddev wp theme activate hotel-booking
ddev build-blocks
ddev seed-content
```

Rebuild demo data: `ddev seed-content --force`.

Object cache: `ddev describe` lists **redis**. After seed, `ddev redis-cli ping` should print `PONG` and `ddev wp redis status` should show Connected. Flush with `ddev redis-flush`.

Page cache: anonymous Home is nginx FastCGI. `curl -sI https://hotel-booking.ddev.site/` twice should show `X-Cache: MISS` then `HIT`. `/wp-json/` and logged-in requests should show `BYPASS`. Flush with `ddev nginx-cache-flush`.

Room search: `ddev describe` lists **opensearch**. After seed, `ddev exec curl -s http://opensearch:9200/_cluster/health` should show `green` or `yellow`. Rebuild the index with `ddev wp hotel-booking reindex`. Dashboards: `ddev launch :5602`. The Search page is `/search/` (Spanish `/es/buscar/`).

Jobs: `ddev describe` lists **rabbitmq**. Management UI `ddev launch :15673` (user `rabbitmq` / `rabbitmq`). Desk mail lands in Mailpit (`ddev launch :8026`). After seed, Priya Shah is a pending inquiry with `wait_until` in the past so `ddev wp hotel-booking remind-stale` (workflow tick) has a due timer. Daily digest: `ddev wp hotel-booking digest`. Desk status uses Contact / Close / Reopen, not a free-form status list. WP-Cron is disabled for visitors; a web daemon ticks `wp cron event run --due-now`.

Observability: `ddev describe` lists **prometheus** and **grafana**. Metrics: `ddev exec curl -s http://web/wp-json/hotel-booking/v1/metrics`. Grafana `ddev launch :3001` (anonymous or `admin` / `admin`); Prometheus `ddev launch :9091`. PHPUnit never starts those containers. See [OBSERVABILITY.md](OBSERVABILITY.md).

PHP errors: `ddev describe` lists **loki**. File log: `ddev exec tail -n 80 wp-content/debug.log`. Container stderr: `ddev logs`. After seed, Query Monitor is in the wp-admin bar as `admin`. Grafana **Hotel Booking logs** dashboard (`ddev launch :3001`). Loki has no UI; `ddev launch :3101/ready` should print `ready`. See [DEBUG.md](DEBUG.md).

Traces: `ddev describe` lists **tempo**. Grafana **Hotel Booking traces** dashboard. Tempo has no UI; `ddev launch :3201/ready`. Seed (and `ddev demo-observability`) curls `/rooms` and `/metrics` so the dashboards are not empty.

## What seed gives you

- Home (static front page), About, Amenities, Contact, Booking, Desk, Staff login, Search
- Five rooms on `/rooms/` (Deluxe King, Garden Suite, Family Room, Penthouse, Courtyard Twin)
- Spanish copies at `/es/` (King Deluxe, …) via Polylang; header **English / Español** switches URL and content
- Booking form guest dropdown **1–6** (`max_guests` in settings)
- Settings: hotel name **The Oak House**, desk email `desk@hotel-booking.ddev.site`
- Redis object cache (plugin via seed; drop-in gitignored)
- nginx FastCGI page cache (anonymous HTML; not a WordPress plugin)
- OpenSearch rooms index (`hotel-booking-rooms`; plugin HTTP client, not a WordPress plugin)
- RabbitMQ (`hotel-booking` topic exchange) plus WP-Cron daily stale-pending and desk digest
- Prometheus + Grafana (inquiry counts; not in PHPUnit or the theme/plugin zip)
- Query Monitor (wp-admin bar as `admin`; not in the zip)
- Loki + Promtail (tails `wp-content/debug.log`; not in PHPUnit or the zip)
- Tempo traces (OTLP from Core REST / inquiry / OpenSearch; not in PHPUnit or the zip)
- About six rows in `wp_hb_inquiries` (`pending`, `contacted`, `closed`); Priya Shah is backdated ~50 hours for the reminder job
- Primary navigation

## Click through

1. https://hotel-booking.ddev.site/ — landing patterns, fluid headings; click **Dark** in the header (theme Interactivity); click **Quiet hours** on the Stay FAQ; click **Español** (Spanish rooms and `/es/`)
2. https://hotel-booking.ddev.site/rooms/ — archive grid; open **Courtyard Twin**
3. https://hotel-booking.ddev.site/stay/ — plugin custom blocks; click **4+** on the rooms grid (Interactivity + REST)
4. https://hotel-booking.ddev.site/search/ — type **gar** for Garden Suite typeahead; try guests/beds/price filters
5. https://hotel-booking.ddev.site/booking/ — guest stepper; submit if you want another row (desk email appears in Mailpit; `/desk/` shows **Mailed**)
6. https://hotel-booking.ddev.site/staff-login/ — `desk` / `desk`, then `/desk/`: named guests; Contact/Close, no Delete
7. https://hotel-booking.ddev.site/wp-admin/admin.php?page=hotel-booking — same inquiries (`admin` only)
8. https://hotel-booking.ddev.site/wp-admin/admin.php?page=hotel-booking-settings — The Oak House (`admin` only)

In the editor: Pages → Stay, or add a block and pick the **Hotel Booking** category.

Log in as `guest` / `guest` and hit `/desk/` again: still closed (no `hb_access_desk`).

## Commands to run once

```bash
# Theme smoke
ddev phpunit --filter Test_Hotel_Booking_Theme

# Rooms API
curl -s https://hotel-booking.ddev.site/wp-json/hotel-booking/v1/rooms
curl -s https://hotel-booking.ddev.site/wp-json/hotel-booking/v1/rooms?guests=4

# Full PHPUnit (after ddev setup-tests, once)
ddev phpunit

# Static analysis and security
ddev phpcs
ddev phpstan # PHPDoc @template helpers: docs/PHPSTAN.md
ddev typecheck
ddev plugin-check
ddev composer audit
ddev pnpm-audit

# Object cache (after ddev start / seed)
ddev redis-cli ping
ddev wp redis status

# Page cache (anonymous HTML)
curl -sI https://hotel-booking.ddev.site/ | grep -i x-cache
ddev nginx-cache-flush

# Room search, jobs, Mailpit
ddev wp hotel-booking reindex
ddev wp hotel-booking workflow tick
ddev wp hotel-booking remind-stale
ddev wp hotel-booking digest
ddev rabbitmq launch
# Mailpit: ddev launch :8026

# Prometheus + Grafana (inquiry counts)
ddev exec curl -s http://web/wp-json/hotel-booking/v1/metrics
# Grafana: ddev launch :3001
# Prometheus: ddev launch :9091

# PHP errors
ddev logs
ddev exec tail -n 80 wp-content/debug.log
# Loki: ddev launch :3101/ready
# Query Monitor: wp-admin as admin
ddev demo-observability
# Tempo: ddev launch :3201/ready

# Rebuild plugin and theme blocks/CSS after clone, or leave watchers running while you edit
ddev build-blocks
# ddev watch-plugin    # plugin src/ → build/
# ddev watch-theme     # theme src/ + screen.scss → build/

# Optional browser e2e (needs pnpm + Chromium on the host)
pnpm install
pnpm exec playwright install chromium
ddev e2e
```

## Where things live

| Path | Role |
| --- | --- |
| `wp-content/themes/hotel-booking/` | Block theme: templates, patterns, `theme.json`, Stay FAQ block, booking/desk PHP views |
| `wp-content/plugins/hotel-booking-core/` | CPT, custom table, REST, shortcodes, wp-admin, Gutenberg blocks |
| `tests/` | `WP_UnitTestCase` (project root, not in the theme) |
| `e2e/` | Playwright specs |
| `content/` | Seed script and content notes |

## After the first hour

Seed is demo data, not a backup. Before you experiment with `--force` or a copied host, snapshot locally and read how to ship only the theme and plugin.

- [BACKUP.md](BACKUP.md) — `ddev snapshot`, SQL dumps, uploads, recovery drill
- [DEPLOYMENT.md](DEPLOYMENT.md) — zip/SFTP or git onto a real WordPress install
- [SCALING.md](SCALING.md) — primary/replica routing and sharding (documentation only; DDEV still has one database)
- [JOBS.md](JOBS.md) — WP-Cron tick, RabbitMQ worker, desk email / digest / stale reminders, async OpenSearch
- [OBSERVABILITY.md](OBSERVABILITY.md) — Prometheus, Grafana, Tempo traces (DDEV only; not in the zip)
- [DEBUG.md](DEBUG.md) — `debug.log`, Query Monitor, Loki, Tempo, `ddev logs`, Xdebug
- [WORKFLOW.md](WORKFLOW.md) — inquiry state machine (Symfony Workflow + MariaDB runs; not Temporal)
- [PHPSTAN.md](PHPSTAN.md) — PHPDoc `@template` helpers (`hotel_booking_array_map` / `array_find`); `ddev phpstan` is the checker
- [I18N.md](I18N.md) — gettext (theme/plugin chrome, `es_ES`) vs editorial content and inquiry rows
- [AUTH.md](AUTH.md) — front-end `/staff-login/`, `hotel_manager` role, desk policies (not wp-admin)
- [CAPACITOR.md](CAPACITOR.md) — Capacitor iOS/Android shell (documentation only; no native app in this repo)
- [PLUGINS.md](PLUGINS.md) — sibling team plugins, pre-deploy checks, shared `theme.json`, reversible uninstall (documentation only)

## Next

- Theme Check and Theme Unit Test: [README](../README.md#content)
- Watch plugin/theme `src/` instead of rebuilding: [README](../README.md#live-development)
- Settings API, REST, Playwright details: [README](../README.md)
- What `ddev seed-content` creates: [content/README.md](../content/README.md)
