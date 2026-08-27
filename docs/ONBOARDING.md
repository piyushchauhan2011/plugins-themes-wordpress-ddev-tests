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
ddev seed-content
```

Rebuild demo data: `ddev seed-content --force`.

## What seed gives you

- Home (static front page), About, Amenities, Contact, Booking, Desk
- Five rooms on `/rooms/` (Deluxe King, Garden Suite, Family Room, Penthouse, Courtyard Twin)
- Booking form guest dropdown **1–6** (`max_guests` in settings)
- Settings: hotel name **The Oak House**, desk email `desk@hotel-booking.ddev.site`
- About six rows in `wp_hb_inquiries` (`pending`, `contacted`, `closed`)
- Primary navigation

## Click through

1. https://hotel-booking.ddev.site/ — landing patterns, fluid headings
2. https://hotel-booking.ddev.site/rooms/ — archive grid; open **Courtyard Twin**
3. https://hotel-booking.ddev.site/booking/ — guest select stops at 6; submit if you want another row
4. https://hotel-booking.ddev.site/desk/ — logged out: staff-only note. Log in as `desk` / `desk`: named guests in the table
5. https://hotel-booking.ddev.site/wp-admin/admin.php?page=hotel-booking — same inquiries
6. https://hotel-booking.ddev.site/wp-admin/admin.php?page=hotel-booking-settings — The Oak House (`admin` only)

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

# Optional browser e2e (needs npm + Chromium on the host)
npm install
npx playwright install chromium
ddev e2e
```

## Where things live

| Path | Role |
| --- | --- |
| `wp-content/themes/hotel-booking/` | Block theme: templates, patterns, `theme.json`, booking/desk PHP views |
| `wp-content/plugins/hotel-booking-core/` | CPT, custom table, REST, shortcodes, wp-admin |
| `tests/` | `WP_UnitTestCase` (project root, not in the theme) |
| `e2e/` | Playwright specs |
| `content/` | Seed script and content notes |

## Next

- Theme Check and Theme Unit Test: [README](../README.md#content)
- Settings API, REST, Playwright details: [README](../README.md)
- What `ddev seed-content` creates: [content/README.md](../content/README.md)
