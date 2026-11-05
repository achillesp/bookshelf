---
module: System
date: 2026-11-05
problem_type: developer_experience
component: tooling
symptoms:
    - 'php artisan db:show reports 618 tables and 11 GB for a freshly created, empty MySQL database'
    - 'Looks as if php artisan migrate:fresh would drop hundreds of unrelated tables'
root_cause: inadequate_documentation
framework_version: 13.40.0
resolution_type: documentation_update
severity: medium
related_components: [database, development_workflow]
tags:
    [artisan, db-show, mysql, migrate-fresh, schema-get-tables, local-database]
---

# `php artisan db:show` counts tables in every database on the MySQL server

## Problem

Checking the local database before a plan's work, `php artisan db:show` for the app's database `bookshelf2` (MySQL 8.4 at `host.docker.internal`, user `root`) reported far more tables and data than the database held. It nearly led to treating `migrate:fresh` as a threat to other projects' data, and a side agent repeated the alarm later.

## Symptoms

```
 MySQL .. 8.4.3
 Database .. bookshelf2
 Tables .. 618
 Total Size .. 11.19 GB
```

while `php artisan migrate:status` said "Migration table not found" (the database was empty).

## What didn't work

- **Trusting the `Tables` / `Total Size` lines as describing `bookshelf2`:** they don't; they cover every schema the connection's user can see.

## Root cause

In Laravel 13 `db:show` sums `Schema::getTables()`, which with no `$schema` argument compiles a query over all schemas visible to the user (`Builder::getTables($schema = null)`). With `root` on a shared local server, that's every project's database plus MySQL's system schemas. `migrate:fresh` is not affected: `dropAllTables()` only drops tables in the connection's current database.

## Fix

Check the one database explicitly before anything destructive:

```bash
php artisan tinker --execute 'echo count(Schema::getTables(DB::getDatabaseName()));'
# or per schema, read-only:
php artisan tinker --execute 'echo json_encode(DB::select("select table_schema, count(*) t from information_schema.tables group by table_schema"));'
```

Here `bookshelf2` held 13 tables (this app's); the 618 were `sph`, `contractuals`, `sph_test`, other projects and the system schemas.

## Prevention

- Before `migrate:fresh` (or any drop), count tables with `Schema::getTables(DB::getDatabaseName())`, not `db:show`.
- Prefer a database user scoped to the app's database for local work, so tooling only sees that database.

## Related

- None yet.
