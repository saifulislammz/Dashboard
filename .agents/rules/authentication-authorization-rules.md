---
trigger: model_decision
description: Apply when implementing or reviewing login, registration, sessions, password handling, password reset, RBAC/permissions, admin panel access, or API authentication for production-level PHP SaaS systems.
---

# Authentication & Authorization Rules (Enterprise)

## Philosophy

- AuthN (who) ≠ AuthZ (what they can do). Every request: AuthN → AuthZ → business logic.

## Login

- Verify: email/username, password, account status, email-verification status, lock status, 2FA (if enabled).

## Password Storage

- Never plaintext. `password_hash()`/`password_verify()`, Argon2id (preferred) or BCrypt. No custom hash algorithms.

## Password Policy

- Min 12 chars, require upper/lower/number/special char. Reject common/dictionary passwords. Limit password reuse (enterprise feature).

## Registration

- Validate email + password, check duplicate email/username, rate-limit, verify CSRF.

## Email Verification

- Random single-use token with expiration sent via email.

## Session (Login)

- `session_regenerate_id(true)` mandatory on login success — session fixation protection always on.
- Cookies: HttpOnly, Secure, SameSite=Lax/Strict. Session ID never in URL.
- Idle timeout + absolute timeout (e.g. 30 min idle / 8 hr max).

## Logout

- Destroy session, remove cookie, revoke remember-me token.

## Remember Me

- Random token, hashed before storage (never plaintext). Rotate token on every auto-login.

## Password Reset

- Token: random, single-use, expiring, hashed at rest. Offer option to invalidate all active sessions after reset.

## Multi-Device (Enterprise)

- Track login devices (browser, IP if needed, login time, last activity). Allow remote logout of other devices.

## Brute Force / Failed Login

- Rate limit by IP + account. Temporary lock after repeated failures (not permanent). Delay strategy. Optional notification.

## RBAC

- Structure: Role → Permission → Resource → Action (e.g. Admin, Teacher, Student, Moderator, Support).
- Fine-grained permissions (`user.create`, `course.update`, `invoice.view`) assigned to roles.

## Authorization

- Permission check on every request, ideally in middleware before controller. No permission → 403.
- Resource ownership enforced: own resource = allowed, others' = denied unless explicit permission.

## Admin Panel

- Every admin route: AuthN → AuthZ → CSRF → rate limit → audit log.
- Admin role ≠ auto-allow-all — still permission-based. Super Admin can be separate role.

## API Auth

- Bearer/JWT/opaque token with expiration. Refresh strategy for JWT.
- Per-request: token valid → role → permission → resource ownership → execute.

## CSRF

- Required on stateful (cookie-based) forms/sessions. Not required for stateless bearer-token APIs.

## Sensitive Actions

- Password/email/phone change, account deletion, payment method update → require current-password re-verification or re-authentication.

## Audit Log

- Log: login, logout, failed login, password/email/role/permission change, admin actions.

## User Status

- Check status (active/inactive/suspended/pending/deleted) before authenticating.

## Middleware Responsibilities

- AuthN, AuthZ, role, permission, session, CSRF, rate limit, maintenance mode.

## Tokens

- Cryptographically random, expiring, rotatable where applicable, revocable.

## Caching

- Roles/permissions/settings cacheable in Redis — must invalidate cache on permission change.

## Concurrency

- Sensitive multi-step actions (wallet transfer, booking) → transaction + lock + idempotency.

## AI Pre-Feature Checklist

Password hashed? CSRF present? Session regenerated on login? Rate limit? Failed-login handled? Permission checked? Resource ownership verified? Audit logged? Remember-token secure? Reset token single-use? Re-auth needed for sensitive action?

## Hard Restrictions — Never

Plaintext password/reset-token/remember-token storage · session ID in URL · skipping session regeneration after login · unauthenticated protected routes · unauthorized admin routes · skipping role check on sensitive actions · modifying data without permission check · trusting role/permission from user input · hardcoded session/token secrets · reusable password-reset tokens.

## Enterprise Scale (10k+ concurrent users)

Support both stateless API and stateful web auth · Redis-portable session storage · refresh token rotation (JWT) · device-based session management + revocation · MFA-ready architecture (TOTP/Email OTP/Authenticator) · step-up re-authentication for high-risk actions · permission cache versioning/invalidation strategy · immutable audit trail · auth events feed into monitoring system · authorization logic centralized in middleware/policy/service (never in controller) · default-deny everywhere — access only with explicit permission.
