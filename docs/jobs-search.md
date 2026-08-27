# Elasticsearch / OpenSearch for rooms and inquiries

Today search is MySQL:

- Rooms: `WP_Query` `post_type = hb_room` and optional `meta_query` on `hb_guests` ([`hotel_booking_rest_get_rooms()`](../wp-content/plugins/hotel-booking-core/inc/rest-api.php), [`hotel_booking_query_rooms_for_grid()`](../wp-content/plugins/hotel-booking-core/inc/helpers.php)). Cap is 100 posts. Fine for a boutique catalog.
- Inquiries: `SELECT * FROM wp_hb_inquiries WHERE status = … LIMIT n`. No full-text on `message` or guest name beyond what you add later with `LIKE`.

**When to add an index:** thousands of rooms, faceted filters (guests + price + beds + free text), or desk search across message bodies. Not for five seeded rooms.

## Documents

**Room** (from `hb_room` + meta):

- `id` (post ID), `title`, `excerpt`, `content` (optional), `guests`, `price`, `beds`, `size`, `permalink`, `modified`

**Inquiry** (from `wp_hb_inquiries`):

- `id`, `guest_name`, `guest_email`, `check_in`, `check_out`, `guests`, `room_id`, `status`, `message`, `created_at`

Index names e.g. `hotel-booking-rooms`, `hotel-booking-inquiries`. Mapping sketch: [`snippets/elasticsearch-room-mapping.json.example`](snippets/elasticsearch-room-mapping.json.example).

## Write path

Do **not** call ES inside the REST GET. Index **asynchronously** after MySQL commits ([jobs-queues.md](jobs-queues.md)):

- `room.updated` / `room.deleted` → index or delete room document
- `inquiry.created` / `inquiry.updated` → index inquiry document

Reads can be stale for a second (same class of problem as replica lag — [scaling-replicas.md](scaling-replicas.md)). The rooms-grid Interactivity filter can keep using MySQL until you switch the REST callback.

## Read path

Later, `GET /hotel-booking/v1/rooms?guests=4&q=garden` would:

1. Query ES (`range` on `guests`, `multi_match` on title/excerpt).
2. Optionally hydrate permalinks from `WP_Query` by ID if you do not store all fields in ES.
3. Fall back to MySQL if ES is down (degraded: current `WP_Query` behavior).

Sketch: [`snippets/elasticsearch-search.php.example`](snippets/elasticsearch-search.php.example).

Analyzers: `standard` or language-specific for titles; `keyword` for `status`; `date` for check-in. Guest capacity is `integer` + `range` query, not full-text.

## Elasticsearch vs OpenSearch

Both speak a similar REST API. **OpenSearch** is a common self-hosted fork if Elastic’s license does not fit. This repo does not pick a vendor; snippets use generic `_search` JSON.

Managed options: Elastic Cloud, AWS OpenSearch, Elastic on k8s. DDEV would gain a service only when you implement this.

## What would change in this plugin

- New dependency (official ES PHP client or OpenSearch client) **in the worker**, not necessarily in the web request.
- REST list: optional branch `if ( get_option( 'hotel_booking_use_search_index' ) )`.
- Reindex CLI: walk all `hb_room` posts and inquiries (Action Scheduler batches) for mapping changes.

Until then, keep `WP_Query` and SQL. They are correct and testable (`ddev phpunit`, `e2e/stay.spec.ts`).
