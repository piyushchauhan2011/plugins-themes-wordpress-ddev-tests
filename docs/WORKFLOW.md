# Inquiry workflow (not Temporal)

Hotel Booking Core uses **[Symfony Workflow](https://symfony.com/doc/current/components/workflow.html)** as a **state machine** for desk inquiries, and **MariaDB** as the durable store. This is not Temporal: there is no workflow-function replay, no Temporal server, and no RoadRunner.

Symfony Workflow only knows a **graph** (places + transitions) and a **marking** (current place). Durability here means: checkpoint `hb_inquiries.status` plus a **run** row (`wait_until` timers) and an **append-only event log**. The DDEV WP-Cron daemon (`wp cron event run --due-now` every 60s) resumes due waits. Side effects still go through RabbitMQ (`inquiry.created` / `inquiry.remind`) with in-request fallback.

```
POST booking → COMMIT inquiry + run + events → publish AMQP
Desk Save    → apply transition in a transaction
WP-Cron tick → SELECT waiting runs WHERE wait_until <= now → remind or skip
```

## Graph

Places: `pending`, `contacted`, `closed` (same slugs as before).

| Transition | From | To |
| --- | --- | --- |
| `contact` | pending | contacted |
| `close` | pending or contacted | closed |
| `reopen` | closed | pending (reschedules the 48h remind wait) |

The desk `<select>` lists **enabled transitions**, not every status. Illegal jumps (for example contacted → pending) return `WP_Error`.

**Remind** is not a place change. It is a durable **timer** (`wait_name=remind`) while the marking stays `pending`.

## Tables

[`inc/database.php`](../wp-content/plugins/hotel-booking-core/inc/database.php) (`HOTEL_BOOKING_DB_VERSION` 1.2.0):

- `wp_hb_inquiries.status` — live marking (desk lists)
- `wp_hb_workflow_runs` — one row per inquiry (`waiting` / `open` / `completed`, `wait_until`, `wait_name`)
- `wp_hb_workflow_events` — `transition`, `timer_scheduled`, `timer_fired`, `timer_skipped`, `activity_scheduled`, `activity_completed`

Insert is one transaction: inquiry, run, start events. AMQP publish happens **after COMMIT**.

## Code

- [`inc/workflow.php`](../wp-content/plugins/hotel-booking-core/inc/workflow.php) — `StateMachine`, custom marking store, `hotel_booking_apply_inquiry_transition()`, `hotel_booking_workflow_tick()`
- Library: project Composer `symfony/workflow` (same `vendor/` autoload as php-amqplib). Zip does not ship `vendor/`.
- Minute schedule `hotel_booking_minute` so DDEV’s existing cron tick picks up due waits soon after a crash
- CLI: `ddev wp hotel-booking workflow tick` (also `remind-stale`, which calls the same tick)

PHPUnit does not start extra Docker; Composer installs Symfony Workflow and tests apply transitions with AMQP unset.

## What this will not do

- Replay PHP locals the way Temporal does
- Exactly-once activities (still at-least-once AMQP + `desk_mailed_at` / `reminded_at`)
- A generic engine for rooms / OpenSearch

Production: `composer require symfony/workflow` next to php-amqplib. See [DEPLOYMENT.md](DEPLOYMENT.md). Jobs and queues: [JOBS.md](JOBS.md).
