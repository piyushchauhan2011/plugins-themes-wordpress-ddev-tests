# Scaling snippets (reference only)

These files are **not** loaded by WordPress or DDEV. Do **not** copy them into `wp-content/` or `.ddev/` unless you are standing up a real cluster in another project.

| File | Purpose |
| --- | --- |
| [db.php.example](db.php.example) | What a drop-in is (vendor file, not this sketch) |
| [hyperdb-db-config.php.example](hyperdb-db-config.php.example) | Primary + two replicas for HyperDB |
| [ludicrousdb-db-config.php.example](ludicrousdb-db-config.php.example) | Same topology for LudicrousDB |
| [proxysql.cnf.example](proxysql.cnf.example) | Writer/reader hostgroups and `SELECT` rules |
| [inquiries-shard-sketch.php.example](inquiries-shard-sketch.php.example) | Hypothetical table name per property — **not** in the plugin |

Architecture: [SCALING.md](../SCALING.md).
