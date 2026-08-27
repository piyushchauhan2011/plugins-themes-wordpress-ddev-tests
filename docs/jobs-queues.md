# Offloading work and RabbitMQ

The HTTP request should **commit MySQL and return**. Email, PDF exports, search indexing, and “notify Slack” belong in a worker. If the worker is down, the inquiry row must still exist; the job retries.

## What to offload here

| Trigger | Heavy work | Why not in-request |
| --- | --- | --- |
| `hotel_booking_insert_inquiry()` | Email desk, index inquiry | SMTP and ES latency, timeouts |
| `save_post_hb_room` / trash | Reindex or delete ES document | Mapping/bulk API |
| wp-admin “export inquiries” | CSV of thousands of rows | PHP time limit |
| Recurring digest | Query + email | Minutes of work |

Pattern:

1. Write the source of truth (MySQL).
2. Publish a **small message** (`inquiry_id`, `room_id`, event name).
3. Return 200 / redirect.
4. Consumer loads the row, does I/O, **acks**.

## Action Scheduler vs RabbitMQ

Use **Action Scheduler** ([jobs-wp-cron.md](jobs-wp-cron.md)) when:

- One WordPress app, PHP-only workers
- Tens of jobs per minute
- You want retries in wp-admin

Use a **broker (RabbitMQ)** when:

- Workers are separate processes or languages
- Fan-out (email **and** index **and** analytics from one `inquiry.created`)
- Backpressure (web can publish faster than ES can index)
- You already run AMQP for other services

You can use both: WordPress publishes to RabbitMQ from a tiny Action Scheduler job if you do not want AMQP libraries in the web request. Simplest production story: publish from the request **after** insert if the AMQP client is fast and failure is logged + retried.

## RabbitMQ shape

```
WordPress  --publish-->  exchange hotel-booking
                              ├─ queue email     (routing key inquiry.created)
                              ├─ queue search    (inquiry.created, room.updated)
                              └─ queue analytics (optional)
```

- **Exchange:** topic `hotel-booking`
- **Routing keys:** `inquiry.created`, `inquiry.updated`, `room.updated`, `room.deleted`
- **Consumer:** long-running `php bin/worker.php` (or a small Go/Python worker) using php-amqp / php-amqplib
- **Ack:** after success. **Nack / requeue** on retryable errors. **Dead-letter** after N failures
- **Idempotency:** key `inquiry:{id}:email` in Redis or a `wp_hb_inquiry_jobs` table so double delivery does not double-email

Sketch: [`snippets/rabbitmq-publish.php.example`](snippets/rabbitmq-publish.php.example).

### Connection and credentials

Web and workers use `AMQP_URL` from the environment, not git. Do not block the request on a missing broker in production without a fallback (enqueue Action Scheduler, or fail the job log).

### What would change in Hotel Booking Core

- After successful `$wpdb->insert`, `do_action( 'hotel_booking_inquiry_created', $id )` and a listener that publishes (or `as_enqueue_async_action`).
- `save_post_hb_room` → `room.updated` with post ID.
- **No** change to form validation or table schema for a first worker.

Workers need WordPress loaded (`wp-load.php`) if they call `hotel_booking_get_inquiry()` — or they speak only SQL/ES and skip WP. Loading WP in a worker is simpler for this plugin; it is heavier.

## Failure modes

- Broker down: insert succeeded, message lost unless you write an outbox row in the **same** MySQL transaction (transactional outbox). Document that as the robust pattern; the snippet is fire-and-forget for clarity.
- Worker crash after side effect before ack: at-least-once delivery → idempotent consumers.
- Poison message: dead-letter queue + alert, do not block the queue.

Index search documents from the **search** queue: [jobs-search.md](jobs-search.md).
