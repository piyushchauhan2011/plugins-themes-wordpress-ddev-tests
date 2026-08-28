# WordPress learning gaps

This DDEV site is past beginner WordPress: block theme + `theme.json`, plugin-territory CPT/meta/REST/shortcodes, Settings API, Interactivity, gettext + Polylang, custom caps/roles, `$wpdb` + `dbDelta`, WP-Cron, WP-CLI, PHPUnit, Plugin Check, Redis, nginx FastCGI. What is thin is **core CMS and plugin APIs**, not more Docker.

This file is a map, not a backlog in git. Nothing listed here is in the theme/plugin zip unless another doc says it shipped.

## What this repo already teaches

| Piece | Where |
| --- | --- |
| Block theme, patterns, Dawn/Dusk | [`wp-content/themes/hotel-booking/`](../wp-content/themes/hotel-booking/) |
| CPT `hb_room`, `register_post_meta`, REST reads | [`inc/post-types.php`](../wp-content/plugins/hotel-booking-core/inc/post-types.php), [`inc/rest-api.php`](../wp-content/plugins/hotel-booking-core/inc/rest-api.php) |
| Custom table, Settings API, shortcodes, blocks | Core `inc/` + `src/` |
| Staff role, front-end login | [AUTH.md](AUTH.md) |
| Gettext + Polylang copies | [I18N.md](I18N.md) |
| Jobs, search, workflow | [JOBS.md](JOBS.md), [WORKFLOW.md](WORKFLOW.md) |
| Object/page cache, traces, logs | [SCALING.md](SCALING.md), [OBSERVABILITY.md](OBSERVABILITY.md), [DEBUG.md](DEBUG.md) |
| Tests and PR quality | `ddev phpunit`, [QUALITY.md](QUALITY.md) |

## Highest-leverage gaps

These are WordPress APIs a theme/plugin job assumes. The hotel site barely uses them.

### 1. Taxonomies

Rooms are a CPT with post meta (`hb_price`, `hb_guests`, …) and OpenSearch facets. There is no `register_taxonomy` (amenities, view, wing). There is no `taxonomy-*.html`, `post-terms` for a custom tax, `tax_query`, or REST `wp/v2/hb_amenity`. Amenities as terms vs a meta blob is a classic modeling lesson.

### 2. Admin UI for custom data

Inquiries are a custom table and a hand-rolled admin view. There is no `WP_List_Table` (sortable columns, bulk actions, views, search), no `add_meta_box` on `hb_room`, and no Gutenberg **PluginSidebar** / document panel for price/guests. The editor still relies on “custom fields” + `register_post_meta`.

### 3. `admin-ajax.php` vs REST writes

Booking POSTs via `admin-post.php`. REST is **read** (`GET /rooms`, `/metrics`) with `permission_callback` `__return_true`. There is no `wp_ajax_*`, no authenticated `POST`/`PATCH`/`DELETE` on the plugin namespace, no cookie + REST nonce, no Application Passwords. A desk JSON API would teach a real `permission_callback`.

### 4. Comments, users, and discussion

[`templates/single.html`](../wp-content/themes/hotel-booking/templates/single.html) includes the Comments block. Comments are not a product: no moderation, `pre_comment_approved`, REST comments, or reviews on rooms. Same for registration, lost password, and profile / `user_meta` beyond [AUTH.md](AUTH.md) staff login.

### 5. Query modification, not a new `WP_Query`

Helpers construct `WP_Query` for rooms and OpenSearch fallback. There is no `pre_get_posts` (exclude rooms from `/`, force `orderby` meta), no custom query vars, and no `add_rewrite_rule`. That is how most plugins change archives without replacing the Loop.

### 6. Plugin lifecycle documented but not shipped for Core

[PLUGINS.md](PLUGINS.md) already says Core has **no `uninstall.php`**. Activate runs `dbDelta` / `hotel_booking_db_version`; deactivate clears cron. Missing: uninstall that drops tables and caps, and a **schema bump** that migrates existing rows (add a column, backfill).

### 7. Media pipeline

JPEG uploads can become WebP ([`hotel-booking-core.php`](../wp-content/plugins/hotel-booking-core/hotel-booking-core.php)). Cards use `wp_get_attachment_image`. There is no `add_image_size`, `srcset`/`sizes` teaching, `upload_mimes`, sideload (`media_sideload_image`), or the REST media endpoint. Featured images exist; the Media API as a topic does not.

### 8. Privacy / personal data

Inquiries store name and email in `wp_hb_inquiries`. There is no `wp_add_privacy_policy_content`, exporter, or eraser. For a hotel desk that is the GDPR-shaped WordPress API, not a legal product.

## Gutenberg / Site Editor still small

Blocks exist (SSR + Inspector + Interactivity). The editor surface is still thin:

- InnerBlocks / parent–child blocks (for example a rooms grid that only allows room-card)
- Block variations and block styles (`registerBlockVariation`, `registerBlockStyle`)
- Block Bindings (bind a paragraph to `hb_price` instead of custom PHP)
- Template lock beyond the one-paragraph CPT `template` in [`inc/post-types.php`](../wp-content/plugins/hotel-booking-core/inc/post-types.php)
- Navigation block menus as a first-class lesson (seed creates `wp_navigation` for Polylang)
- Query Loop variations for `hb_room` (archives are mostly templates + shortcodes/blocks)
- Editor SlotFill, command palette, Data Views

Skip Customizer and classic widgets on this block theme.

## Templates and classic WordPress

Shipped templates: front-page, page, single, `single-hb_room`, archives, search, 404. Not shipped: **author**, **date**, **category/tag**, **taxonomy**, **attachment**, password-protected, or a **child theme**.

A classic PHP theme (`index.php` Loop, `header.php`) is absent on purpose. One small contrast (docs or a throwaway `classic/` outside the zip) is enough; do not rewrite Hotel Booking.

## Docs-only (already labeled)

Do not count these as learned in code: [CAPACITOR.md](CAPACITOR.md), replicas/sharding in [SCALING.md](SCALING.md), sibling `uninstall.php` in [PLUGINS.md](PLUGINS.md). **Multisite** (`switch_to_blog`, network activate, `sunrise.php`) is still a real WordPress gap if you care about networks.

## Fine to skip unless that is the next job

WooCommerce/payments, WPGraphQL/headless, XML-RPC, Customizer, widgets, BuddyPress, another observability collector. Ops on DDEV is already deep (Redis, FastCGI, OpenSearch, RabbitMQ, Grafana/Tempo).

## If you only add three exercises

Keep them on this hotel site:

1. `hb_amenity` taxonomy + archive template
2. Room meta in a `PluginDocumentSettingPanel` or meta boxes, plus `WP_List_Table` for inquiries
3. Authenticated REST write for desk status with a real `permission_callback`

That is the hole between “I can ship a block theme and a plugin” and “I’ve used the APIs WordPress jobs assume.”
