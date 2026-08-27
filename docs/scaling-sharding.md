# Sharding (mostly not for WordPress core)

**Sharding** splits rows across many MySQL servers so **writes** and data size scale. Replicas only copy the **same** dataset.

WordPress **can** sit behind a sharded data store for **your** tables. It **cannot** treat `wp_posts` + `wp_postmeta` + `wp_options` + `wp_users` as independent shards without breaking JOINs, `WP_Query`, and autoloaded options.

This demo hotel has **one** property. There is no `property_id` on inquiries. Sharding is a future multi-tenant design, not a next DDEV step.

## Why core tables resist sharding

| Table | Problem |
| --- | --- |
| `wp_options` | Autoloaded on every request; must be complete and consistent |
| `wp_posts` / `wp_postmeta` | `WP_Query` JOINs; rooms (`hb_room`) live here |
| `wp_users` / `wp_usermeta` | Auth, caps, `desk` vs `guest` |
| `wp_term_relationships` | Taxonomies join posts |

A shard that has “some posts” cannot answer “all rooms with `hb_guests >= 4`” without scatter-gather. Core will not do that for you.

**Multisite** (`wp_2_posts`, …) is prefix isolation per site, not a transparent shard of one site. Different product.

## What *could* shard here: inquiries

[`wp_hb_inquiries`](../wp-content/plugins/hotel-booking-core/inc/database.php) is a normal InnoDB table, not used by `WP_Query`. If you ran **many hotels** on one WordPress (or many apps sharing a plugin), you could shard by `property_id`:

- Hash: `shard = crc32( property_id ) % N`
- Range: property 1–1000 → shard A
- Directory: mapping table on the primary (small)

Then either:

- **N tables** on one cluster: `wp_hb_inquiries_0` … `wp_hb_inquiries_15`, or
- **N MySQL clusters**, one connection per shard (drop-in datasets or separate `$wpdb` clones)

`hotel_booking_inquiries_table_name()` today returns one name. Sharding would become `hotel_booking_inquiries_table_name_for( $property_id )`. Sketch: [`snippets/inquiries-shard-sketch.php.example`](snippets/inquiries-shard-sketch.php.example) — **not loaded by the plugin**.

### Application changes (if you ever did this)

- Add `property_id` (or `hotel_id`) to the table and to every insert/list/update/delete.
- Desk list for “all properties” becomes N queries or a search index (OpenSearch), not one `get_results`.
- `$wpdb->insert_id` is **per connection**; IDs are not globally unique unless you use UUIDs or Hi/Lo.
- `dbDelta` must run **on every shard** (or you migrate with a job).
- REST and admin-post handlers must pass the shard key; never query “all shards” on a hot path.

Until you have that key, **do not shard**. Use replicas.

## Vitess

[Vitess](https://vitess.io/) sits in front of MySQL and shards by a key you declare. WordPress would still use one `DB_HOST` (the Vitess gateway).

Reality check:

- You would shard **Vindexes** on `wp_hb_inquiries.property_id` if it existed.
- Leaving `wp_*` core tables **unsharded** (single unsharded keyspace) is the usual pattern.
- Cross-shard JOINs between `hb_inquiries` and `wp_posts` (room titles) become scatter or denormalized room fields on the inquiry row (this plugin already stores `room_id`; listing still wants post data from core).

Vitess does not make `WP_Query` shard-aware. It is an ops choice for **your** large table plus a boring unsharded WordPress keyspace.

## Other “horizontal” options that are not sharding WP

- **Archive** old `closed` inquiries to cold storage; keep hot rows small.
- **Separate WordPress per brand** (true isolation; operationally many sites).
- **Queue + worker** for analytics so `/desk/` is not `SELECT *` on millions of rows.

## Decision for this learning site

Keep one primary. Document replicas. Do not implement HyperDB, Vitess, or inquiry shards in-tree. Revisit sharding only if inquiries become huge **and** you have a real tenant key.
