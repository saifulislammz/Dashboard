---
trigger: model_decision
description: Apply when designing, creating, or modifying database schemas, tables, migrations, indexes, or queries for production-level PHP SaaS/LMS/ERP/dashboard projects requiring scalable, normalized, secure database architecture.
---

# PHP Database Design Rules (Production, Scalable)

## Philosophy

- Normalization first, performance second. No unnecessary duplication.
- Design for millions of rows. Each table = single clear purpose.
- No business logic in DB (no SP/triggers unless special case). Schema changes via migrations only.

## Naming

- Tables: snake_case, plural (`users`, `order_items`). No reserved keywords.
- Columns: snake_case. FK: `user_id`, `course_id`. Bool: `is_active`, `is_verified`. Dates: `created_at`, `updated_at`, `deleted_at`, `last_login_at`.

## Primary/Foreign Keys

- PK: `id`, BIGINT AUTO_INCREMENT (UUID only for distributed/public IDs). Never change PK.
- FK only where logical relation exists; always indexed. No orphan data. No blind cascade delete — decide cascade per business logic.

## Data Types

- Use smallest sufficient int type (TINYINT/SMALLINT/INT/BIGINT).
- Currency: `DECIMAL(12,2)` — never FLOAT. Bool: `TINYINT(1)`. Dates: DATE/DATETIME. Flexible data: JSON. Long text: LONGTEXT.

## NULL & Defaults

- Avoid NULL when a default is possible. Set sane defaults (`status='active'`, `is_active=1`, `created_at=CURRENT_TIMESTAMP`).

## Timestamps & Soft Delete

- Every table: `created_at`, `updated_at`. Prefer soft delete (`deleted_at`) over hard delete. NULL = active, set = deleted.

## Status

- Use a `status` column + app-level constants, not heavy ENUM usage.

## Indexing

- Every table needs indexes: FK, email, username, slug, status, created_at, frequently searched/joined fields.
- Composite index when columns are queried together (`status, created_at`), ordered by access pattern.
- Unique index for: email, username, phone, slug, invoice no, transaction id.
- No `LIKE '%x%'` — use FULLTEXT index or dedicated search engine.

## Relationships

- Proper junction tables for many-to-many (e.g. `course_students`), relation-only columns, composite unique index.

## Large Tables

- Plan indexing, archiving, partitioning (if needed), read optimization for multi-million-row tables.

## Design Process

- Before designing schema: analyze insert/update/read/search/filter/sort/join patterns first, then design schema around access patterns (not the reverse).

## Normalization

- Minimum 3NF. Denormalize only for proven performance need, never arbitrarily.

## Counters & Metadata

- Use counter columns or summary tables for views/likes/counts — never COUNT() on the fly repeatedly.
- No unlimited EAV-style meta tables for fixed/structured data — use real columns.

## JSON

- Only for flexible/non-searchable data. Never store searchable, filterable, or index-needed data in JSON.

## Audit & Transactions

- `audit_logs` table for critical changes: user, action, table, record_id, old/new value, IP, timestamp.
- Multi-table updates wrapped in transactions (BEGIN/COMMIT/ROLLBACK).
- Row-level locking for race-prone ops: balance, wallet, seat booking, inventory, coupons.

## Sensitive Data & Files

- Passwords/OTP/tokens/secrets: hashed, never plaintext.
- Never store files (image/PDF/video) in DB — store `file_path`/`file_url` only.

## Pagination

- Avoid deep OFFSET on large datasets — use cursor/keyset pagination. Never `SELECT * FROM huge_table`.

## Backup & Access

- Planned daily + incremental backups with restore testing.
- App never connects as DB root — least-privilege DB user only.

## AI Hard Constraints — Never Do

- `SELECT *`. Unindexed large tables. Ignored FKs where applicable.
- Blanket `VARCHAR(255)` everywhere. FLOAT for currency. Plaintext passwords.
- BLOB storage for images/video. Searchable data in JSON.
- Schema design without analyzing query patterns first. Unnecessary NULLs. Skipping index strategy on large tables.

## Enterprise Add-ons (10k+ concurrent users)

Read/write DB separation-ready architecture · connection pooling · covering indexes · composite index column order per access pattern · EXPLAIN-informed query design · hot/cold table separation · archive policy · cache-friendly (Redis-ready) schema · idempotent transactions · migration rollback strategy · referential integrity through schema evolution · write-performance-aware indexing on high-write tables · access-pattern-first schema design.
