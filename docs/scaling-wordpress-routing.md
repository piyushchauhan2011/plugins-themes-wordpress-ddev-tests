# How WordPress routes multiple database connections

WordPress does **not** open a “read connection” and a “write connection” in application code. Plugins call `global $wpdb` and run `$wpdb->get_results()` / `$wpdb->insert()`. Routing happens **inside `$wpdb`** if you replace the class with a drop-in.

This project is unchanged: inquiries still look like this:

```php
$inserted = $wpdb->insert(
	hotel_booking_inquiries_table_name(),
	$row,
	array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
);
```

See [`inc/inquiries.php`](../wp-content/plugins/hotel-booking-core/inc/inquiries.php). A replica-aware `$wpdb` subclass still receives that call; it decides **which host** runs it.

## Default: one DSN

Without a drop-in, `wp-config.php` defines `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`. Core instantiates `wpdb` and keeps **one** TCP connection for the request. Reads and writes share it. That is what DDEV does.

## Drop-in: `wp-content/db.php`

If [`wp-content/db.php`](https://developer.wordpress.org/advanced-administration/wordpress/drop-ins/) exists, WordPress loads it **instead of** constructing the stock `wpdb` the usual way. The file must define a `$wpdb` instance (typically a subclass).

That is the official hook for:

- Primary + replica pools
- Different credentials per host
- Dataset rules (send some tables only to the primary)

**This repo does not ship `wp-content/db.php`.** A sketch lives in [`snippets/db.php.example`](snippets/db.php.example).

## HyperDB and LudicrousDB

Two common drop-ins:

| | [HyperDB](https://wordpress.org/plugins/hyperdb/) (Automattic) | [LudicrousDB](https://github.com/stuttter/ludicrousdb) |
| --- | --- | --- |
| Mechanism | `db.php` + `db-config.php` | Same idea; often easier callbacks |
| Writes | Hosts with write flag / dataset | Primary / writable servers |
| Reads | Replica list, optional lag checks | Replica list |
| After a write | Subsequent reads on the **primary** for the rest of the request (avoids replica lag) | Same pattern |

Typical rules:

- SQL that is not a `SELECT` (and DDL) → **primary**
- `SELECT` → a **replica**, unless this request already wrote, or lag is too high, or the table is in a “primary only” list (`wp_options` during `UPDATE`, transients, etc.)
- Connection failure on a replica → try another replica, then primary

Example configs (not installed): [`snippets/hyperdb-db-config.php.example`](snippets/hyperdb-db-config.php.example), [`snippets/ludicrousdb-db-config.php.example`](snippets/ludicrousdb-db-config.php.example).

### Request lifetime (lag stickiness)

1. Guest submits a booking form → `$wpdb->insert` on **primary**.
2. Same PHP request then loads `/desk/` or a “thank you” query → HyperDB keeps **SELECTs on the primary** so the new row is visible.
3. A **later** request from another staff browser may hit a replica that is 200ms behind → empty or stale list until catch-up.

Plugin authors do not opt into that stickiness; the drop-in implements it. You still must not assume a replica is current across HTTP requests.

## Schema and `dbDelta` always on the primary

[`hotel_booking_install_inquiries_table()`](../wp-content/plugins/hotel-booking-core/inc/database.php) uses `dbDelta()` and `update_option()`. Those must run on the **primary**. Replicas apply the DDL through replication; running `dbDelta` against a replica is wrong (and often denied).

Configure the drop-in so:

- `CREATE` / `ALTER` / `dbDelta` → primary
- `update_option( 'hotel_booking_db_version', … )` → primary (`wp_options` writes)

## Application vs proxy routing

You can split traffic in PHP (HyperDB) **or** in the network (ProxySQL, cloud “reader endpoint”) **or** both:

- **PHP only:** WordPress sees several hosts. Fine for a few replicas. Credentials live in `db-config.php` (keep them out of git).
- **Proxy only:** WordPress still has one `DB_HOST` (the proxy). The proxy parses SQL and picks hostgroups. WordPress stays stock `wpdb`. See [scaling-replicas.md](scaling-replicas.md).
- **Both:** unusual; pick one source of truth unless you have a clear split (e.g. proxy for pooling, HyperDB for “this table is primary-only”).

## What would change in Hotel Booking Core

For **replicas only:** nothing in `inquiries.php` if the drop-in is correct.

Optional hardening later (still not in this repo):

- After `hotel_booking_insert_inquiry()`, do not immediately `get_results` on a **new** HTTP request expecting the row without stickiness or a cache.
- Health: if all replicas are down, fail open to the primary for reads.

For **sharding**, PHP must choose a table or connection. That is a plugin change — [scaling-sharding.md](scaling-sharding.md).
