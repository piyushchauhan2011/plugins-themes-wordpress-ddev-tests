# WP-Cron, system cron, and Action Scheduler

WordPress “cron” is **not** the Unix daemon. It is a list of timestamps in `wp_options` (`cron`). By default, a front-end or admin hit may spawn `wp-cron.php` in the background. If nobody visits, jobs **do not run**.

Hotel Booking Core registers **no** `wp_schedule_event` hooks. Core and plugins you activate (Plugin Check, etc.) may still schedule their own events.

## Spawn-on-visit vs real cron

Default:

1. Request arrives.
2. If due events exist and the lock is free, WordPress HTTP-requests `wp-cron.php`.
3. That process runs due hooks, then exits.

Problems in production:

- Quiet sites miss schedules (nightly digest never fires).
- Busy sites spawn overlapping `wp-cron.php` until the lock (`doing_cron` transient) serializes them.
- The visitor waits on loopback HTTP (or a race if the loopback fails).

Production pattern:

```php
define( 'DISABLE_WP_CRON', true );
```

Then a **system** crontab (or systemd timer) hits the site every minute. Sketches: [`snippets/system-cron.example`](snippets/system-cron.example).

```
* * * * * wp cron event run --due-now --path=/var/www/html
# or
* * * * * curl -fsS https://example.com/wp-cron.php?doing_wp_cron
```

`wp cron event run` needs WP-CLI on the app server. `curl wp-cron.php` needs the web stack up. Prefer CLI when you have it: no extra PHP-FPM worker, same user as the site.

### Locks and missed events

WP-Cron uses a transient lock. If a job runs longer than the lock TTL, another spawn can start. Long jobs should **not** be WP-Cron callbacks; enqueue them (Action Scheduler or RabbitMQ) and return quickly.

Missed events: WP-Cron runs **due** hooks on the next tick; it does not catch up 24 missed nightlies as 24 runs. Recurring events reschedule from “now.”

## Action Scheduler

[Action Scheduler](https://actionscheduler.org/) (ships with WooCommerce; also a Composer library) stores jobs in **custom tables**, not `wp_options`. It is the usual WordPress **queue** before you add RabbitMQ.

| Feature | WP-Cron | Action Scheduler |
| --- | --- | --- |
| Storage | `cron` option | `wp_actionscheduler_*` tables |
| Retries | You write them | Built-in |
| Admin UI | Plugins or WP-CLI | Tools → Scheduled Actions (when bundled) |
| Async after insert | `wp_schedule_single_event( time(), … )` | `as_enqueue_async_action()` |
| Groups | No | Yes (`hotel-booking-email`) |

Runner: still needs **something** to process the queue — WP-Cron, or `wp action-scheduler run`, or a dedicated loop. With `DISABLE_WP_CRON`, crontab should also run Action Scheduler.

Sketch: [`snippets/action-scheduler-enqueue.php.example`](snippets/action-scheduler-enqueue.php.example).

### Hotel examples (not coded)

- After `hotel_booking_insert_inquiry()`: enqueue `hotel_booking_send_desk_email` with the insert id (do not send SMTP inside the POST handler).
- After `save_post_hb_room`: enqueue reindex (or publish AMQP — [jobs-queues.md](jobs-queues.md)).
- Recurring `twicedaily`: remind `pending` inquiries older than 48 hours.
- Recurring `daily`: desk digest to `desk@…` from settings.

Keep callbacks **idempotent** (email once per inquiry id; store `email_sent_at` or a unique action hint).

## What would change in this plugin

Add `wp_schedule_event` on activation and `wp_clear_scheduled_hook` on deactivation — or depend on `woocommerce` / `action-scheduler` and call `as_schedule_recurring_action`.

Do **not** put `dbDelta` or large exports in a cron hook that shares the web request. Offload: [jobs-queues.md](jobs-queues.md).
