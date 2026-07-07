---
trigger: model_decision
description: Apply when designing, writing, or reviewing PHP backend architecture, controllers, services, repositories, APIs, middleware, or business logic for production-level PHP SaaS/LMS/ERP/dashboard systems without a framework
---

## Philosophy

- Production-ready only. No demo code, dummy logic, hardcoded values.
- Backend must be: secure, modular, reusable, testable, scalable, maintainable, low-memory, high-performance.

## Architecture

Request → Router → Middleware → Controller → Service → Repository → Database

- Controller: never touches DB or business logic.
- View/Helper: never touch DB.

## Controller

- Thin: receive request → validate/call validator → call service → return response.
- No SQL, no business logic, no HTML generation, no filesystem logic.

## Service Layer

- All business logic here (login, registration, payment, invoice, booking, enrollment, notifications, email, permissions, billing). Must be reusable.

## Repository

- DB-only (SELECT/INSERT/UPDATE/DELETE). No business logic — just returns data.

## Model

- Data structure only. No heavy business logic.

## Router

- Centralized route definitions. RESTful pattern (`GET /courses`, `POST /courses`, etc.).

## Middleware

- Use for: auth, authorization, CSRF, rate limiting, logging, maintenance mode, security headers, request validation, API auth.

## Dependency Injection

- Classes never self-instantiate dependencies — inject via constructor (`__construct(UserService $service)`).

## Validation

- Done in controller/dedicated validator. Service never skips validation.
- Types: required, email, int, float, bool, UUID, date, enum, min/max, regex, file, image, MIME.

## Exceptions

- Catch specific exceptions, not blanket catch-all. Global exception handler. User-friendly message shown; full error logged.

## Response Format

- Consistent structure: `{ success, message, data }` (and matching error format).

## Helpers

- Utility-only (date/currency/string/array formatting). Never touch DB, session, or business logic.

## Session

- Auth/minimal user state only. No large objects or DB result sets in session.

## Config

- All config from `.env` (DB, Redis, SMTP, queue, cache, API keys, JWT). No secrets in code.

## File Structure (Feature Modules)

Modules/{User,Course,Payment,Booking,Invoice}
Controller, Service, Repository, Validator, Routes, View(optional)

## Function/Class Rules

- One function = one job, ~20-30 lines, early returns, minimal nesting.
- One class = one responsibility (SRP). No God classes.

## Logging

- Log: login/logout, registration, payment, failed login, permission denied, exceptions, queue failures, file uploads.
- Never log sensitive data.

## Cache

- Cache frequently-read static/semi-static data (settings, permissions, roles, categories, countries, languages) via Redis.

## Queue

- Offload heavy work to queue: email, SMS, notifications, reports, PDF/image/video processing, backups.

## File Upload

- Dedicated upload service (controller stays unaware). Validate, virus-scan if possible, random filename, organized directories, size limit, MIME verification.

## Transactions

- Multi-repository updates wrapped in transaction: commit on success, rollback on failure.

## Events

- Use event/listener pattern for loose coupling (e.g. user registered → separate listeners for welcome email, activity log, notification, analytics).

## Background Jobs

- Heavy processes never block requests — dispatch to background jobs.

## Code Reuse

- No duplicate logic — extract to service/trait(careful)/helper/utility class.

## Performance

- Minimize: query count, memory/CPU usage, response time, file includes, unnecessary object creation.

## Security

- Never trust client input. Always validate, sanitize, authorize, authenticate.

## API

- Versioned (`/api/v1/`), consistent response format, rate-limited.

## AI Pre-Feature Checklist

Controller thin? Logic in service? Repository DB-only? Validation complete? Transaction needed? Cacheable? Queue-worthy? Errors handled? Logging needed? Permissions checked? Security risk? Easy to extend later?

## Hard Restrictions — Never

SQL in controller · business logic in controller · DB access from view/helper · hardcoded secrets · duplicate logic · redundant re-queries within same request · reliance on global variables · misused static utility classes · skipped transactions where needed · silently hidden/ignored exceptions.

## Enterprise Scale (10k+ concurrent users)

Stateless-ready architecture · Redis-portable session storage · interface-based config/cache/queue/storage (swappable providers) · idempotent operations · locking strategy for payment/wallet/booking/inventory · feature-flag-ready architecture · background worker retry + dead-letter queue · health check/readiness/liveness endpoints · pluggable monitoring (response time, error rate, queue length, cache hit rate) · consistent structure across all new modules (exceptions only with clear technical justification)
