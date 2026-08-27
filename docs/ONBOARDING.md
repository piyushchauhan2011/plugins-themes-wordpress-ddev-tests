# First hour

This is the joiner path. You should not start from an empty WordPress site. After seed you have pages, five rooms, sample inquiries, and three local users.

Requires [DDEV](https://ddev.com/) and Docker. Deeper topic notes live in the [root README](../README.md).

## Accounts (local only)

| User | Password | Role | What it is for |
| --- | --- | --- | --- |
| `admin` | `admin` | Administrator | Settings, wp-admin, everything |
| `desk` | `desk` | Editor | `/desk/` and **Hotel Booking → Inquiries** (not Settings) |
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

## What seed gives you

- Home (static front page), About, Amenities, Contact, Booking, Desk
- Five rooms on `/rooms/` (Deluxe King, Garden Suite, Family Room, Penthouse, Courtyard Twin)
- Spanish copies at `/es/` (King Deluxe, …) via Polylang; header **English / Español** switches URL and content
- Booking form guest dropdown **1–6** (`max_guests` in settings)
- Settings: hotel name **The Oak House**, desk email `desk@hotel-booking.ddev.site`
- Redis object cache (plugin via seed; drop-in gitignored)
- About six rows in `wp_hb_inquiries` (`pending`, `contacted`, `closed`)
- Primary navigation

## Click through

1. https://hotel-booking.ddev.site/ — landing patterns, fluid headings; click **Dark** in the header (theme Interactivity); click **Quiet hours** on the Stay FAQ; click **Español** (Spanish rooms and `/es/`)
2. https://hotel-booking.ddev.site/rooms/ — archive grid; open **Courtyard Twin**
3. https://hotel-booking.ddev.site/stay/ — plugin custom blocks; click **4+** on the rooms grid (Interactivity + REST)
4. https://hotel-booking.ddev.site/booking/ — guest stepper; submit if you want another row
5. https://hotel-booking.ddev.site/desk/ — logged out: staff-only note. Log in as `desk` / `desk`: named guests in the table
6. https://hotel-booking.ddev.site/wp-admin/admin.php?page=hotel-booking — same inquiries
7. https://hotel-booking.ddev.site/wp-admin/admin.php?page=hotel-booking-settings — The Oak House (`admin` only)

In the editor: Pages → Stay, or add a block and pick the **Hotel Booking** category.

Log in as `guest` / `guest` and hit `/desk/` again: still closed (no `edit_posts`).

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
ddev phpstan
ddev typecheck
ddev plugin-check
ddev composer audit
ddev pnpm-audit

# Object cache (after ddev start / seed)
ddev redis-cli ping
ddev wp redis status

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
- [JOBS.md](JOBS.md) — WP-Cron, Action Scheduler, RabbitMQ, Elasticsearch (documentation only; not wired)
- [I18N.md](I18N.md) — gettext (theme/plugin chrome, `es_ES`) vs editorial content and inquiry rows

## Next

- Theme Check and Theme Unit Test: [README](../README.md#content)
- Watch plugin/theme `src/` instead of rebuilding: [README](../README.md#live-development)
- Settings API, REST, Playwright details: [README](../README.md)
- What `ddev seed-content` creates: [content/README.md](../content/README.md)
