# Database scaling

This DDEV site uses **one MariaDB**. WordPress core and Hotel Booking Core talk to it through a single `global $wpdb`. **Redis object cache** and **nginx FastCGI page cache** are wired. Read replicas and `db.php` are still not.

**Possible:** yes — WordPress can send writes to a **primary** and `SELECT`s to **replicas** if you replace the database layer with a [drop-in](https://developer.wordpress.org/advanced-administration/wordpress/drop-ins/). **Sharding WordPress core tables is a poor fit.** Sharding only `wp_hb_inquiries` is conceivable later if you add a tenant key this demo does not have.

```
Request → PHP (theme, plugin, core)
        → global $wpdb
        → optional wp-content/db.php drop-in
              ├─ INSERT/UPDATE/DELETE/DDL → primary
              └─ SELECT (when lag allows) → replica pool
        → optional ProxySQL / Vitess in front of MySQL
```

## What we have today

| Piece | Where |
| --- | --- |
| One database | DDEV `db` service (MariaDB) |
| Object cache | DDEV `redis` service, `WP_REDIS_HOST=redis`, Redis Object Cache plugin (seed, not committed) |
| Page cache | nginx FastCGI (`X-Cache` header); anonymous HTML only. Flush with `ddev nginx-cache-flush` |
| Connection | `wp-config.php` `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASSWORD` |
| Core content | `wp_posts` (`hb_room`), options, users |
| Custom table | `{$wpdb->prefix}hb_inquiries` via [`inc/database.php`](../wp-content/plugins/hotel-booking-core/inc/database.php) |
| CRUD | [`inc/inquiries.php`](../wp-content/plugins/hotel-booking-core/inc/inquiries.php) (`$wpdb->insert`, `get_results`, `update`, `delete`) |
| Local backup | [BACKUP.md](BACKUP.md) — dump the only server |

Inquiry writes already go through `$wpdb`. A replica drop-in can split connections **without rewriting those functions**. Sharding cannot.

## Scale ladder

1. **Object cache** — running locally: `ddev redis-cli ping`, `ddev wp redis status`, `ddev redis-flush`. Production uses host Redis/Valkey, not the DDEV compose file; see [DEPLOYMENT.md](DEPLOYMENT.md).
2. **Page cache** — running locally: nginx FastCGI. `curl -sI https://hotel-booking.ddev.site/` twice should show `X-Cache: MISS` then `HIT`. Bypass for POST, logged-in cookies, `/wp-admin/`, `/wp-json/`. Flush with `ddev nginx-cache-flush`. Production uses host nginx FastCGI or a CDN, not the DDEV `nginx_full` files; see [DEPLOYMENT.md](DEPLOYMENT.md). Async email and workers: [JOBS.md](JOBS.md). Room search: OpenSearch on DDEV, [jobs-search.md](jobs-search.md).
3. **Vertical** sizing of the primary (CPU, buffer pool, IOPS).
4. **Read replicas** + HyperDB/LudicrousDB and/or ProxySQL. See [scaling-wordpress-routing.md](scaling-wordpress-routing.md) and [scaling-replicas.md](scaling-replicas.md).
5. **Shard only what you own** (inquiries by property/tenant), never `wp_options`. See [scaling-sharding.md](scaling-sharding.md).

## Documents

| Doc | Contents |
| --- | --- |
| [scaling-wordpress-routing.md](scaling-wordpress-routing.md) | How `$wpdb` routes, `db.php`, HyperDB vs LudicrousDB, stickiness after writes, `dbDelta` |
| [scaling-replicas.md](scaling-replicas.md) | Replication, lag, failover, ProxySQL, multi-region, backups |
| [scaling-sharding.md](scaling-sharding.md) | Why core resists sharding, Vitess, hypothetical inquiry shards |
| [snippets/README.md](snippets/README.md) | Reference configs — **do not copy into `wp-content/`** |

## What we are not adding in this repo

- No `wp-content/db.php`
- No second DDEV database service
- No Composer HyperDB/LudicrousDB package
- No PHPUnit against two MySQL instances
- No change to `hotel_booking_insert_inquiry()`
- No Redis in GitHub Actions / wp-env (PHPUnit boots `.wp-tests`, not the DDEV drop-in)
- No nginx FastCGI cache in GitHub Actions / wp-env (they never hit this nginx)
