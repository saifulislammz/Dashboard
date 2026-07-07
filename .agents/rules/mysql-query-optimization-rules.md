---
trigger: model_decision
description: Apply when writing, reviewing, or optimizing MySQL queries, ORM calls, pagination, joins, indexes, or database access code for production PHP SaaS/dashboard/LMS/ERP systems requiring high performance at scale.
---

## Query Philosophy

Before writing any query, evaluate: rows scanned, index used, full scan risk, memory/disk I/O, network cost, locking, concurrency impact, cacheability, 100x-data scalability.
Target: fast read, fast write, low memory/CPU/I/O.

## SELECT

- Never `SELECT *` — only needed columns (`SELECT id,name,email`).

## Result Size & Pagination

- Every list query has LIMIT (default 20, max 100).
- OFFSET pagination only for small datasets. 10k+ rows → cursor pagination (`WHERE id > ? ORDER BY id LIMIT 20`). No deep OFFSET.

## WHERE

- Lead with indexed columns. No functions wrapping indexed columns (❌ `YEAR(created_at)=2026` → ✅ range comparison on raw column).

## Indexing

- Always ask: does this query use an index? If not, rewrite or add index.
- Prefer covering indexes (satisfy query from index alone).
- Composite index column order must match query pattern (`WHERE user_id=? AND status=1 ORDER BY created_at` → `(user_id,status,created_at)`). No random order.

## JOIN

- Minimize joins. All join columns indexed. Filter before joining. Avoid large-table joins. Order joins optimizer-friendly.

## N+1

- Forbidden. Use batch query / JOIN / `IN()` / mapping instead.

## EXISTS vs IN

- Large dataset → EXISTS. Small dataset → IN() is fine.

## Counting & Aggregates

- No repeated `COUNT(*)` on dashboards — use summary table/Redis/counter cache.
- Frequent SUM/AVG/MAX/MIN/GROUP BY → precomputed table.

## ORDER BY

- Must use indexed column. `ORDER BY RAND()` forbidden.

## Search

- `LIKE '%keyword%'` forbidden — use FULLTEXT index, prefix search, or external search engine.

## OR / Subqueries

- Minimize OR (consider UNION ALL or rewrite). Avoid nested subqueries — prefer JOIN or CTE (MySQL 8+). Use CTE for repeated subqueries.

## Batch Operations

- Bulk insert (1000 rows = 1 insert, not 1000 queries).
- Batch update via CASE/JOIN, never loop+UPDATE.
- Large deletes → chunked, limited, background job.

## Transactions & Locking

- Wrap logical units in transactions (commit/rollback mandatory).
- Prepared statements + bound params always — no raw SQL concatenation.
- Dynamic ORDER BY/LIMIT/table/column names → whitelist only.
- Row-level locks for balance/wallet/coupon/inventory/booking. Avoid table locks.
- Keep transactions short; consistent lock ordering to avoid deadlocks.

## Caching

- Frequently used data → Redis/memory cache to reduce DB hits.
- Dashboards: avoid 20+ live queries — use summary queries/materialized cache/Redis.
- Heavy reports → background queue job, not blocking user.
- Autocomplete/search → debounce + cache + prefix index.

## Loops

- No SELECT/INSERT/UPDATE/DELETE inside loops — forbidden.

## EXPLAIN

- Run EXPLAIN on complex queries. Target: type = const/eq_ref/ref/range (avoid ALL), low `rows`, high `filtered`, minimize "Using temporary"/"Using filesort".

## Connections & Scaling

- Don't open/close connections repeatedly; decide persistent connection use per environment (not blindly).
- Design read/write split-ready architecture for future scaling.
- Avoid long-running queries — target millisecond execution.

## AI Pre-Query Checklist

SELECT *? Index used? Full scan? Covering index possible? LIMIT present? Cacheable? Can JOIN be reduced? Rewritable? Batchable? Redis-usable? Summary table option? EXPLAIN result? Already run this request in same call? Scales 100x?

## Hard Restrictions — Never

`SELECT *` · `ORDER BY RAND()` · `LIKE '%text%'` · query-in-loop · dynamic SQL concatenation · unprepared queries · deep OFFSET pagination · unnecessary subqueries/joins · repeated dashboard `COUNT(*)` · likely full-table-scan queries · redundant re-reads of same data.

## Enterprise Scale (1M+ rows / 10k+ concurrent)

Separate hot/cold data strategy · Redis for read-heavy data · no unnecessary indexes on write-heavy tables · archive table strategy · idempotent queries · streaming bulk import/export (never full dataset in memory) · regular slow-query log review (threshold ~200–500ms) · benchmark with realistic data, not dummy data · follow order: Access Pattern → Schema → Index → Query → Cache → Monitoring · evaluate query count/execution time/index impact for every new feature.
