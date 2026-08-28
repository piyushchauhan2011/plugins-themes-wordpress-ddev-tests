# WP-Cron, system cron, and Action Scheduler

WordPress “cron” is **not** the Unix daemon. It is a list of timestamps in `wp_options` (`cron`). By default, a front-end or admin hit may spawn `wp-cron.php` in the background. If nobody visits, jobs **do not run**.

Hotel Booking Core schedules these events on `init` if they are missing, and clears them on plugin deactivation:

| Hook | Schedule | Job |
| --- | --- | --- |
| `hotel_booking_workflow_tick` | every minute | Due `wait_until` remind timers |
| `hotel_booking_stale_pending` | daily | Same tick (`remind-stale` CLI) |
| `hotel_booking_desk_digest` | daily | Count pending rows → `desk.digest` |

Run them now without waiting:

```bash
ddev wp hotel-booking workflow tick
ddev wp hotel-booking remind-stale
ddev wp hotel-booking digest
ddev wp cron event run --due-now
```

## Spawn-on-visit vs real cron

Default:

1. Request arrives.
2. If due events exist and the lock is free, WordPress HTTP-requests `wp-cron.php`.
3. That process runs due hooks, then exits.

Problems in production:

- Quiet sites miss schedules (nightly digest never fires).
- Busy sites spawn overlapping `wp-cron.php` until the lock (`doing_cron` transient) serializes them.
- The visitor waits on loopback HTTP (or a race if the loopback fails).

DDEV and production pattern:

```php
define( 'DISABLE_WP_CRON', true );
```

DDEV `post-start` writes that constant. A **web_extra_daemons** loop ticks instead of visitors:

```
while true; do wp cron event run --due-now --quiet; sleep 60; done
```

A second daemon consumes RabbitMQ only when the `queue` profile is up (`getent hosts rabbitmq`). Without it, desk mail and indexing run in-request.

On a real host, a **system** crontab (or systemd timer) does the same. Sketches: [`snippets/system-cron.example`](snippets/system-cron.example).

```
* * * * * wp cron event run --due-now --path=/var/www/html
# or
* * * * * curl -fsS https://example.com/wp-cron.php?doing_wp_cron
```

`wp cron event run` needs WP-CLI on the app server. `curl wp-cron.php` needs the web stack up. Prefer CLI when you have it: no extra PHP-FPM worker, same user as the site.

### Locks and missed events

WP-Cron uses a transient lock. If a job runs longer than the lock TTL, another spawn can start. The plugin callbacks **publish a small AMQP message** (or send one email) and return; the worker does SMTP / OpenSearch.

Missed events: WP-Cron runs **due** hooks on the next tick; it does not catch up 24 missed nightlies as 24 runs. Recurring events reschedule from “now.”

## Action Scheduler

[Action Scheduler](https://actionscheduler.org/) (ships with WooCommerce; also a Composer library) stores jobs in **custom tables**, not `wp_options`. It is the usual WordPress **queue** before you add RabbitMQ. This repo does **not** depend on it.

| Feature | WP-Cron | Action Scheduler |
| --- | --- | --- |
| Storage | `cron` option | `wp_actionscheduler_*` tables |
| Retries | You write them | Built-in |
| Admin UI | Plugins or WP-CLI | Tools → Scheduled Actions (when bundled) |
| Async after insert | `wp_schedule_single_event( time(), … )` | `as_enqueue_async_action()` |
| Groups | No | Yes (`hotel-booking-email`) |

Runner: still needs **something** to process the queue — WP-Cron, or `wp action-scheduler run`, or a dedicated loop. With `DISABLE_WP_CRON`, crontab should also run Action Scheduler.

Sketch: [`snippets/action-scheduler-enqueue.php.example`](snippets/action-scheduler-enqueue.php.example).

### Hotel examples (coded vs not)

**In the plugin** ([`inc/jobs.php`](../wp-content/plugins/hotel-booking-core/inc/jobs.php)):

- After `hotel_booking_insert_inquiry()`: `inquiry.created` → desk `wp_mail` (idempotent via `desk_mailed_at`); durable run + 48h remind `wait_until`
- Minute tick: due remind timers ([WORKFLOW.md](WORKFLOW.md))
- Recurring daily: desk digest of pending count

**Still a sketch:** Action Scheduler instead of RabbitMQ; huge CSV exports in a cron hook.

Do **not** put `dbDelta` or large exports in a cron hook that shares the web request. Offload: [jobs-queues.md](jobs-queues.md).
