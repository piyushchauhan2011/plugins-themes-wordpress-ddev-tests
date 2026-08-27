# Backup and recovery

Local DDEV already knows how to snapshot MariaDB and the default file mount. Use that for experiments; do not treat `ddev seed-content` as a backup.

## Local (DDEV)

Named snapshot (database plus DDEV’s default file handling):

```bash
ddev snapshot -n before-experiment
ddev snapshot restore before-experiment
ddev snapshot list
```

SQL-only:

```bash
ddev export-db --file=backup.sql.gz
ddev import-db --file=backup.sql.gz
```

Snapshots land under `.ddev/db_snapshots/` (gitignored).

## What a dump includes

A database dump (snapshot or `export-db`) includes:

- Posts and pages (rooms, Home, Desk, …)
- Options, including `hotel_booking_settings`
- Users
- Custom table `wp_hb_inquiries`

It does **not** include theme or plugin code (that lives in git) unless you also copy uploads.

## Uploads

Seeded room featured images live in `wp-content/uploads/` (gitignored). Next to an SQL dump:

```bash
tar -czf uploads.tar.gz wp-content/uploads
```

Restore: extract into `wp-content/uploads/`, then `ddev import-db` if you replaced the database.

## Recovery drill

Confirm a snapshot actually brings the site back:

1. `ddev snapshot -n before-seed-force`
2. `ddev seed-content --force` (destructive: rebuilds demo pages, rooms, inquiries)
3. `ddev snapshot restore before-seed-force`
4. Open `/desk/` — Priya Shah (and the other sample guests) should still be there
5. Open `/rooms/` — Courtyard Twin should still exist

## What not to do

`ddev seed-content` rebuilds **demo** data. It does not restore real inquiries you collected on a copied site. After a real booking form submission, recover from a snapshot or SQL dump, not from seed.
