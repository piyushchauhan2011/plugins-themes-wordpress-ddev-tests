# Replicas, lag, failover, and multi-region

Read replicas scale **SELECT** volume. They do not scale writes. WordPress write amplification (`wp_options`, autoloaded rows, post meta, transients) often saturates the primary before inquiries do. Pair replicas with an **object cache**.

Replicas are **not** running on DDEV. Local object cache is: [SCALING.md](SCALING.md). Local recovery remains [BACKUP.md](BACKUP.md) against the single MariaDB.

## Replication model

A **primary** accepts writes. **Replicas** apply the binary log (or GTID stream) asynchronously unless you pay for a synchronous cluster.

```
Primary  --binlog/GTID--> Replica A
                       --> Replica B
```

Use **GTID** replication in production so failover can point a replica at a new primary without file/position surgery.

### Lag

`SHOW REPLICA STATUS` (MySQL 8.0.22+) / `SHOW SLAVE STATUS`: `Seconds_Behind_Source`. Non-zero means a `SELECT` can miss a row that just committed on the primary.

Hotel Booking example: insert on `/booking/` then open `/desk/` in another tab **before** the replica applies the event → missing inquiry. Same-request reads should stay on the primary ([routing](scaling-wordpress-routing.md)). Cross-request reads need either:

- Accept lag (desk is eventually consistent), or
- Route “fresh” desk queries to the primary (HyperDB callback or ProxySQL rule on that path), or
- Read through Redis after write (invalidate/set the list cache on insert).

HyperDB-style lag checks can skip a replica when `Seconds_Behind_Source` exceeds a threshold (e.g. 1–2 seconds) and use the primary for that `SELECT`.

## Failover

WordPress has **no** cluster manager. If the primary dies:

1. Orchestrator / MHA / cloud (RDS Multi-AZ, Cloud SQL HA, Aurora) **promotes** a replica.
2. You update `DB_HOST` (or ProxySQL’s writer hostgroup, or HyperDB’s write server list).
3. Remaining replicas must **re-parent** to the new primary (GTID makes this tractable).
4. PHP-FPM workers keep old connections until recycle; drain or restart pools.

Split-brain (two writers) corrupts `wp_options` and autoincrement IDs. Use one writer. Do not run two WordPress primaries in two regions without a consensus layer you actually operate.

## ProxySQL vs application routing

**[ProxySQL](https://proxysql.com/)** sits on `DB_HOST`. WordPress uses stock `wpdb` and one hostname.

- Hostgroup 10: writer (primary)
- Hostgroup 20: readers (replicas)
- `mysql_query_rules`: `SELECT` → 20, everything else → 10
- Connection pooling, multiplexing, failover if a backend is `OFFLINE_SOFT`

Tradeoff: the proxy must parse SQL correctly (multi-statements, `SELECT … FOR UPDATE` must go to the writer). HyperDB sees PHP APIs (`$wpdb->query` vs `get_var`) and can be more precise; ProxySQL is nicer for pooling and ops.

Cloud equivalents: RDS **reader endpoint**, Cloud SQL replica IP, Aurora **reader**. Still one writer.

Example: [`snippets/proxysql.cnf.example`](snippets/proxysql.cnf.example).

## Multi-region

| Role | Placement |
| --- | --- |
| WordPress PHP | Often in the **write** region, or read-local with sticky writes |
| Primary | **One** region |
| Replicas | Same region for HA; other regions for read latency |
| Object cache | Local Redis per region (do not share one Redis across oceans for WP autoload) |
| Uploads / media | Object storage + CDN |
| HTML | CDN; bypass cache for `/desk/` and `wp-admin` |

Cross-region replica lag is **larger**. Staff in a distant region should hit the primary or a same-region replica with known lag SLOs.

**Active-active WordPress** (writes in two regions to two primaries) is not supported by core. Conflict resolution for `wp_options` and post IDs is a research project, not a plugin setting.

## Backups

Dump the **primary**. Replica dumps can be slightly behind and missing the latest inquiries. Point-in-time recovery uses primary binlogs + a base backup.

Local DDEV snapshots are unrelated to replica topology; they snapshot the only database. See [BACKUP.md](BACKUP.md).

## Monitoring (production)

- Replica lag histogram, not only a boolean
- Primary threads_running, `Threads_connected`, disk wait
- HyperDB/ProxySQL error rates and failovers to primary
- Disk on binlog retention (replicas that disconnect for hours)

## Suggested order if this site ever left DDEV

1. Redis object cache drop-in (`object-cache.php`)
2. Managed primary + one replica, HyperDB or ProxySQL
3. Lag alerts before adding more replicas
4. Only then consider sharding inquiries — [scaling-sharding.md](scaling-sharding.md)
