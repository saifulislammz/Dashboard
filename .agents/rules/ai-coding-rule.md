---
trigger: model_decision
description: Apply to every PHP coding task as the master standard — governs architecture, security, database, performance, and review discipline that all other rule files (security/db/query/backend/performance/auth) must follow together.
---

# AI Coding Rules — Master Rule (Enterprise Production)

## Core Principle

- Always production-ready code. Never: demo/example/prototype code, fake/temporary logic, placeholders, unfinished TODOs.

## Think Before Code

- Before coding, analyze: requirements, security risk, DB design, query optimization, performance impact, scalability, memory/CPU, future expansion, error handling, cache strategy, queue need, transaction need. No code without analysis.

## Respect Existing Code

- Never break existing code. Maintain backward compatibility. Follow existing architecture.

## Priority Order

1. Architecture first, then code.
2. Security first — check for every feature: SQL injection, XSS, CSRF, authN/authZ, validation, output escaping, safe file upload, rate limiting, sensitive data exposure.
3. Performance first — query count, Redis/cache use, queue need, memory/CPU, response time.
4. Database first — new table/index/FK/composite-index needs, normalization check.
5. Reuse first — search for existing logic before writing new; never duplicate.

## Change Discipline

- No 1000-line single dumps — break features into steps, complete each step.
- One responsibility per function/class/module.

## Clean Code & Naming

- Readable, meaningful names everywhere.
- Variables: camelCase · Classes: PascalCase · DB: snake_case · Constants: UPPER_CASE · Routes: kebab-case.
- Comments only for complex logic, not for self-evident code.

## Error Handling & Validation

- Handle every exception with user-friendly messages; log internal errors.
- Validate all input, escape all output, check all permissions.

## Transactions / Cache / Queue

- Multi-table writes → transaction.
- Repeated-use data → cache (avoid repeated DB hits).
- Heavy work → queue (never block the user).

## Testing Mindset

- Always consider: edge cases, invalid input, large datasets, concurrent users, race conditions, timeouts, rollback.

## Scalability & Maintainability

- Feature must work at both 10 users and 10M users.
- Code must be understandable by another developer 6 months later.

## File/Folder Structure

- No oversized files — split when too large. Organize by feature and by layer.

## Dependencies

- No unnecessary libraries — prefer native PHP. If using a library: well-known, maintained, security-vetted only.

## API Consistency

- Consistent response format and error format across all APIs.

## Docs / Git

- New feature → update README. DB change → migration. API change → update docs.
- Small, atomic commits: one commit = one feature.

## No Magic Values

- No hardcoded URLs/passwords/API keys/status/role/permission — all from config.

## Logging & Monitoring

- Log where needed; never log sensitive data. Critical features must be monitorable.

## Backward Compatibility & Refactoring

- New features never break old ones; DB migrations must be safe.
- Refactor for better approach only if existing behavior is preserved.

## AI Self-Review Checklist (after every feature)

**Architecture:** followed? layers respected?
**Security:** SQLi/XSS safe? CSRF? permission/auth checks?
**Database:** indexes right? queries optimized? transaction needed? no N+1?
**Performance:** Redis/cache usable? queue needed? memory/CPU minimized?
**Backend:** controller thin? service/repository correct?
**Frontend:** responsive? Tailwind correct? lazy-loaded?
**API:** status codes & response format correct?
**Logging:** necessary logs present? no secrets logged?
**Error handling:** all exceptions handled, user-friendly?
**Scalability:** survives 100x data growth and concurrent load?

## Hard Restrictions — Never

Demo/placeholder code · unfinished `TODO` features · skipped security · `SELECT *` · query-in-loop · duplicate logic · business logic in controller · SQL in views · plaintext passwords · hardcoded secrets · inline CSS/JS (when externals required) · full large-file memory loads · heavy tasks inline in requests · breaking existing features · unvalidated user input · silently ignored errors · `@` error-suppression operator · debug statements (`var_dump`, `print_r`, `die`, `exit`) left in production code.

## Standard Development Workflow

Requirement Analysis → Architecture Design → Database Design → Security Analysis
→ Performance Analysis → API Design → Backend Dev → Frontend Dev
→ Validation & Authorization → Error Handling → Logging
→ Testing (Edge Cases) → Optimization → Self Review → Production Ready

## Final Master Principles

- Correctness before performance.
- Security is never sacrificed for performance.
- Performance is never achieved by sacrificing readability.
- Readability never compromises maintainability.
- Maintainability never blocks scalability.
- Every feature: modular, secure, testable, reusable.
- Code must support a team scaling from 1 to 50 developers on the same codebase.
- Every decision considers future extension, debugging, and production stability.
