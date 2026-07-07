---
trigger: model_decision
description: Apply when writing, reviewing, or optimizing PHP/MySQL code for response time, memory, CPU, caching (Redis), queues, pagination, assets, or scalability in production-level SaaS/dashboard/LMS/ERP systems under high concurrent load.
---

## Philosophy

- Performance-first always. Before building any feature, evaluate: CPU, RAM, DB hits, disk I/O, network, response time, concurrency, cacheability, queue-worthiness.
- Goal: min CPU/RAM/disk I/O/network, max throughput, min response time.

## Response Time Targets

Static page ≤100ms · Dashboard ≤300ms · API ≤200ms · Search ≤300ms · Login ≤300ms.
Heavy work never runs inline in the request.

## Memory

- No large arrays/datasets held in memory. Use streaming/generators where possible. Release unused variables.

## CPU

- No repeated calculation — compute once, cache result. Minimize nested loops.
- Prefer O(1)/O(log n)/O(n); avoid O(n²)/O(n³).

## Database Hits

- Same query never repeated within a request — forbidden. Cache instead.

## Redis

- Cache: settings, permissions, roles, categories, languages, frequent lookups, dashboard summaries, nav menus, feature flags. Prefer Redis over DB where suitable.

## Cache Strategy

- Cache-first, DB-fallback. Define TTL and invalidation strategy for every cache.

## Query Performance

- Every query indexed, no full table scans, no N+1 — batch queries instead.

## Loading Strategy

- Lazy-load only what's needed. Eager-load related data together to avoid repeated queries.

## Assets

- Minify CSS/JS, enable Gzip/Brotli compression, cache headers, versioning.

## Images

- Never serve originals — use thumbnails, lazy load, prefer WebP/AVIF, responsive images.

## Files

- No large files fully loaded into memory — use streaming/chunked upload & download.

## Queue / Background Jobs

- Offload: email, notifications, reports, backups, PDF, image/video processing, import/export. User never waits on these.

## Scheduler

- Cron for: cleanup, cache refresh, backups, expired token removal, log rotation, report generation.

## Session

- Keep small — no objects/arrays/DB results in session. Prefer Redis-backed sessions.

## Includes / Autoload

- No unnecessary require/include — use autoloading.

## Filesystem & Config

- Cache repeated file reads and repeated config reads in memory.

## Loop Restrictions

- Forbidden inside loops: DB queries, file reads, HTTP calls, email sends, log writes.

## HTTP Calls

- Cache repeated API calls. Always set timeout + retry strategy.

## API

- Keep responses small, no unnecessary JSON fields, enable compression, paginate.

## Search

- Debounce + cache + FullText + index.

## Pagination

- Every list paginated; prefer cursor pagination.

## Dashboard

- Avoid 20+ live queries — use summary tables/cache/Redis.

## Logging

- Avoid heavy synchronous logging; consider async logging + log rotation.

## Exceptions

- No repeated exception throwing; log unexpected exceptions only.

## Compression / OPcache / CDN

- Gzip/Brotli on all responses. OPcache enabled in production (consider preloading).
- CDN for static files, images, CSS, JS.

## Connections

- Reuse DB connections, no repeated open/close. Persistent connection decision based on deployment profile, not assumed.

## Load Testing & Monitoring

- Evaluate performance at 100/1,000/10,000 concurrent users.
- Monitor: CPU, RAM, disk I/O, slow queries, cache hit rate, error rate, response time, queue length, active DB connections.

## Scaling

- Architecture must allow easy future addition of: Redis, load balancer, read replicas, queue workers, multiple servers, CDN, object storage.

## AI Pre-Feature Checklist

Can reduce queries? Cacheable? Redis-usable? Queue-worthy? Background job? Reduce memory/CPU? Shrink response? Lazy-load? Batchable? Compressible? Streamable? Scales 100x?

## Hard Restrictions — Never

DB query/file read/HTTP call/email send inside loops · repeated identical query in same request · loading full table into memory · loading large files fully into RAM · unnecessary object creation · blocking tasks in request cycle · re-fetching cacheable data from DB every time.

## Enterprise Scale (10k+ concurrent users)

Defined performance budget per feature (must not be exceeded) · combined Redis + OPcache + CDN + HTTP cache architecture · long cache headers + content-hash versioning on static assets · cache stampede protection (locking/stale-while-revalidate) · long-running tasks in worker processes only · streaming/chunked import/export/CSV/PDF/video/backup · scheduled restarts for long-running workers (memory leak prevention) · profile and optimize hot paths · regular review of slow query log/metrics/error tracking · optimization order: Algorithm → Database → Cache → I/O → Network → Micro-optimization (never start with micro-optimization) · load/stress/regression performance testing before each release · stateless app + shared cache + queue + horizontal-scaling-ready design.
