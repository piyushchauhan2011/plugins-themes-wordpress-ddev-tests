# Scaling and jobs snippets (reference only)

These files are **not** loaded by WordPress or DDEV. Do **not** copy them into `wp-content/` or `.ddev/` unless you are standing up a real cluster in another project.

| File | Purpose |
| --- | --- |
| [db.php.example](db.php.example) | What a drop-in is (vendor file, not this sketch) |
| [hyperdb-db-config.php.example](hyperdb-db-config.php.example) | Primary + two replicas for HyperDB |
| [ludicrousdb-db-config.php.example](ludicrousdb-db-config.php.example) | Same topology for LudicrousDB |
| [proxysql.cnf.example](proxysql.cnf.example) | Writer/reader hostgroups and `SELECT` rules |
| [inquiries-shard-sketch.php.example](inquiries-shard-sketch.php.example) | Hypothetical table name per property — **not** in the plugin |
| [system-cron.example](system-cron.example) | Crontab tick for WP-Cron / Action Scheduler (DDEV uses a web daemon instead) |
| [action-scheduler-enqueue.php.example](action-scheduler-enqueue.php.example) | Async email/index after inquiry insert — **not loaded**; live jobs are the plugin |
| [rabbitmq-publish.php.example](rabbitmq-publish.php.example) | AMQP sketch — **not loaded**; live client is `inc/amqp.php` |
| [elasticsearch-room-mapping.json.example](elasticsearch-room-mapping.json.example) | Room index mapping (matches DDEV OpenSearch) |
| [elasticsearch-search.php.example](elasticsearch-search.php.example) | Guests range + full-text `_search` body (sketch; live code is `inc/opensearch.php`) |
| [inquiries-locale-column.php.example](inquiries-locale-column.php.example) | Optional guest UI locale on an inquiry — **not** in `dbDelta` |

Architecture: [SCALING.md](../SCALING.md), [JOBS.md](../JOBS.md), [I18N.md](../I18N.md).
