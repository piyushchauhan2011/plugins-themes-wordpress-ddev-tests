# OpenSearch for rooms (DDEV)

Room search on DDEV uses **OpenSearch**. Inquiries stay in MariaDB (`SELECT` / `LIKE` if you add desk search later). PHPUnit and wp-env never start a cluster: `GET /hotel-booking/v1/rooms` falls back to `WP_Query`.

**When the index matters:** faceted filters (guests + price + beds + free text) and typeahead. Five seeded rooms still work through MySQL if OpenSearch is down.

## What runs locally

OpenSearch is the **`search`** Compose profile. Default `ddev start` does not run it; `/search/` still lists rooms via `WP_Query`.

```bash
ddev start --profiles=search
ddev start-profiles search
```

- Add-on [`ddev/ddev-opensearch`](https://github.com/ddev/ddev-opensearch), image tag **2.x** in [`.ddev/.env.opensearch`](../.ddev/.env.opensearch)
- Cluster `http://opensearch:9200` on the Docker network (security plugin off)
- Dashboards: `ddev launch :5602` — inspect index `hotel-booking-rooms`
- `WP_OPENSEARCH_HOST` / `WP_OPENSEARCH_PORT` from DDEV `post-start` (same idea as Redis). The plugin treats the cluster as off when `opensearch` does not resolve
- Plugin HTTP via `wp_remote_request` in [`inc/opensearch.php`](../wp-content/plugins/hotel-booking-core/inc/opensearch.php) — no Composer client

```bash
ddev describe
ddev exec curl -s http://opensearch:9200/_cluster/health
ddev wp hotel-booking reindex
```

## Documents

**Room** (from `hb_room` + meta), document id = post ID:

- `title` / `excerpt` / `content` (`text`), `title.raw` (`keyword`), `title.suggest` (`completion`)
- `guests`, `price`, `beds`, `size` (`integer`)
- `locale` (`keyword`, Polylang slug or `en`)
- `permalink` (`keyword`, not analyzed)

Inquiries are **not** indexed. Mapping: [`snippets/elasticsearch-room-mapping.json.example`](snippets/elasticsearch-room-mapping.json.example).

## Write path

Publish first, sync if the broker is down:

- `save_post_hb_room` — AMQP `room.updated` / `room.deleted`; on publish failure, the same PUT/DELETE as before
- `before_delete_post` — AMQP `room.deleted`, or sync DELETE
- `wp hotel-booking reindex` — put mapping + bulk index all published `hb_room` (all Polylang languages); seed still runs this at the end (source of truth after Polylang copies)
- `ddev seed-content` runs reindex after rooms and Spanish copies (including the already-seeded early-exit path)

## Read path

`GET /hotel-booking/v1/rooms`:

1. Query OpenSearch (`multi_match` on `q`, `range` on guests/beds/price, `term` on `locale`)
2. Hydrate permalinks and images from WordPress by hit ID
3. Fall back to `WP_Query` if the cluster is down, the index is missing, or the host is unset (CI)

`GET /hotel-booking/v1/rooms/suggest?q=&lang=` uses the completion suggester on `title.suggest`. Fallback: a short `WP_Query` `s`.

Stay’s rooms-grid stays guests-only; it already hits `/rooms`, so it uses OpenSearch when the cluster is up. The seeded **Search** page (`/search/`, Spanish `/es/buscar/`) sends `q` plus facets.

## Elasticsearch vs OpenSearch

Both speak a similar REST API. This project uses **OpenSearch 2.x** on DDEV. Production is a host URL in `wp-config.php` (`WP_OPENSEARCH_HOST`, `WP_OPENSEARCH_PORT`) — do not copy the DDEV compose file. See [DEPLOYMENT.md](DEPLOYMENT.md).

## What would still change for scale

- Index asynchronously after MySQL commits — **done** via RabbitMQ `room.updated` ([jobs-queues.md](jobs-queues.md)); sync PUT remains the fallback
- Inquiry documents if the desk needs full-text on message bodies
- Action Scheduler batches for huge catalogs

Until the cluster is up, keep using the fallback. It is correct and testable (`ddev phpunit`, `e2e/search.spec.ts` on wp-env).
