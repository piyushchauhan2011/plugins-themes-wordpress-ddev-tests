# Sibling plugins (teams, checks, design, uninstall)

This DDEV site still ships **one theme** and **one plugin** ([Hotel Booking Core](../wp-content/plugins/hotel-booking-core/)). There is no second `hotel-booking-*` product in git.

**Possible:** yes — other teams add sibling folders `wp-content/plugins/hotel-booking-<team>/` in this monorepo (or copy them in before zip). One WordPress, one theme. Teams do not fork [`theme.json`](../wp-content/themes/hotel-booking/theme.json) or Core tables.

```
Team PR → DDEV activate extra plugin → phpcs / phpstan / phpunit / plugin-check
        → zip theme + hotel-booking-core + extras
Delete plugin in wp-admin → uninstall.php reverses that plugin’s schema only
```

## What we have today

| Piece | Where |
| --- | --- |
| Zip | Theme + Core only; [DEPLOYMENT.md](DEPLOYMENT.md) |
| Checks | `ddev phpcs`, `ddev phpstan` ([`phpstan.neon.dist`](../phpstan.neon.dist) paths), `ddev phpmd` ([QUALITY.md](QUALITY.md)), `ddev phpunit`, `ddev plugin-check`, CI [`.github/workflows/testing.yml`](../.github/workflows/testing.yml) |
| Design | Theme palettes/type in `theme.json`; Dawn/Dusk in `styles/`. Plugin SCSS uses `--wp--preset--color-*` (inquiry-list) |
| Schema | [`inc/database.php`](../wp-content/plugins/hotel-booking-core/inc/database.php) `dbDelta` on activate. Deactivate clears cron only. **No `uninstall.php`** — deleting Core leaves `hb_inquiries` / workflow tables |

## Ladder

1. **Ownership.** One plugin per team. Prefix functions, caps, options, and tables `hb_` / `hotel_booking_`. Hook Core filters; do not copy theme templates. Custom post types and shortcodes stay plugin territory ([WordPress.org theme review](https://make.wordpress.org/themes/handbook/review/)).
2. **Install before deploy.** Drop the folder in, then on DDEV:

   ```bash
   ddev wp plugin activate hotel-booking-foo
   ```

   Gate it the same way as Core: add a `<file>` in [`phpcs.xml.dist`](../phpcs.xml.dist), add the path in [`phpstan.neon.dist`](../phpstan.neon.dist) and [`phpmd.xml.dist`](../phpmd.xml.dist) (or the `phpmd` Composer paths), run `ddev phpunit`, run `ddev plugin-check` on that slug. If the plugin has `src/`, add it to [`pnpm-workspace.yaml`](../pnpm-workspace.yaml) and `ddev typecheck`. Only then zip **theme + Core + extras**.
3. **Shared design.** Plugins consume theme.json CSS variables (`var(--wp--preset--color--espresso)`, font sizes, spacing) and block `supports`. No second palette. Do not ship a competing plugin `theme.json` (extending via `wp_theme_json_data_theme` is discouraged here). Hardcoded hex in plugin CSS is how the public site diverges from Dawn/Dusk.
4. **Reversible SQL.** Activate = `dbDelta` on **that plugin’s** tables (`{$wpdb->prefix}hb_<slug>_…`). Deactivate = keep data (WordPress default). **Delete** = [`uninstall.php`](https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/) drops those tables, deletes that plugin’s options, removes caps/roles **it** added. Never `DROP` `hb_inquiries` from a sibling. No cross-plugin foreign keys that survive uninstall.

## What we are not adding in this repo

- No second `hotel-booking-*` plugin
- No Composer path-repo / Packagist installer for extras
- No Core `uninstall.php` (Core still leaves tables; the contract above is for **new** plugins)
- The WordPress zip still ships **only** the theme and Core until an extra passes this checklist; see [DEPLOYMENT.md](DEPLOYMENT.md)
