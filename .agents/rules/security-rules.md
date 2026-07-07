---
trigger: model_decision
description: Apply when writing,reviewing or editing PHP backend code(controllers,models,services, repositories,auth,APIs,forms,file uploads,database queries)for production-level WordPress/PHP projects requiring secure,scalable,enterprise-grade implementation
---

# PHP Security Rules (Mandatory, Production)

## General

- Security > convenience, always. No feature ships without it.
- All input = untrusted. Client-side validation never trusted — revalidate server-side.
- Fail secure, least privilege, default deny.

## Input Validation

- Validate ALL sources: GET/POST/PUT/PATCH/DELETE, JSON, multipart, cookies, headers, URL params.
- Check: required, type, min/max length, allowed chars, enum, email, URL, int/float/bool, date, regex, UUID.
- Never use raw/unvalidated input.

## Output Encoding (XSS)

- HTML: `htmlspecialchars()`. JS context: JSON-encode. Attributes: proper escape. URLs: `urlencode()`.
- No raw HTML render. User HTML → must pass through a sanitizer.

## SQL Injection

- Prepared statements + PDO + bound params only. No string concatenation in queries.
- Dynamic ORDER BY / table names → whitelist only.

## CSRF

- CSRF token on every POST/PUT/PATCH/DELETE form. Verify server-side. Invalid → HTTP 403.
- Prefer one-time/rotating tokens.

## Session

- `session_regenerate_id(true)` after login.
- Cookies: HttpOnly, Secure, SameSite=Lax/Strict.
- Idle timeout + absolute expiration. Destroy session on logout.

## Auth

- Never store plaintext passwords. Use `password_hash()`/`password_verify()` (Argon2id/BCrypt).
- Reset tokens: random, single-use, short expiry. Remember-me tokens: hashed at rest.

## Authorization

- AuthN ≠ AuthZ. Every route checks permission (RBAC). Admin routes behind middleware.
- Users can only access their own resources (no IDOR — e.g. `/user/5` can't view user 6).

## File Upload

- Never trust extension. Verify MIME + magic number. Enforce max size. Random filename on store.
- Block executables: php, phar, phtml, cgi, exe, dll, sh, bat. Upload dir = no execute.
- Verify images are genuinely images.

## Rate Limiting / Brute Force

- Rate-limit: login, password reset, OTP, registration, contact form, API, search.
- Block repeated failed logins → temporary lock (never permanent) with unlock time.

## Password Policy

- Min length, strong complexity, reject dictionary/common passwords. Password history for enterprise.

## Transport & Headers

- HTTPS only in prod, force HTTP→HTTPS redirect, no mixed content, secure cookies.
- Set headers: CSP, HSTS, X-Frame-Options (DENY/SAMEORIGIN), X-Content-Type-Options, Referrer-Policy, Permissions-Policy.
- Hide server/PHP version info.

## CORS

- No wildcard origins. Whitelist origins, methods, headers explicitly.

## Error Handling

- `display_errors=off` in prod. Log exceptions, show generic user-friendly messages.
- Never expose stack traces, SQL errors, or PHP warnings to users.

## Logging

- Log security events: login/logout, password change, permission denied, failed login, account lock, file upload, admin actions.
- Never log: passwords, OTP, tokens, JWT, API secrets.

## API Security

- Verify all bearer/JWT/API keys. Reject expired tokens. Separate refresh tokens.
- API rate limiting + versioning required.

## Sensitive Data

- All secrets (passwords, OTP, tokens, API/SMTP/DB secrets) → `.env` only. Never commit secrets to git.

## Cryptography

- Use `random_bytes()`/`bin2hex()` for tokens. Never roll custom crypto — use PHP built-ins only.

## Dependencies

- No unnecessary packages. Remove unused. Keep updated. No known-vulnerable libraries.

## Database

- No root DB user — least-privilege DB user. Separate read/write users for high traffic. Encrypt backups.

## Cache

- Never cache passwords, tokens, or other sensitive data. Permission caches must expire.

## Business Logic

- Never trust client-side price, discount, role, or permission — always re-verify server-side.

## AI Hard Constraints — Never Do

- Raw SQL / string-concatenated queries.
- Inline secrets or hardcoded credentials.
- Plaintext password storage.
- Forms without CSRF tokens.
- Unescaped HTML output.
- Admin routes without permission checks.
- APIs/forms without input validation.
- Upload features without file validation.
- `display_errors=On` in production.
- Leftover debug code or test endpoints.

## Enterprise Add-ons (10k+ concurrent users / SaaS)

CSP with nonce/hash · CSRF token rotation · session binding (UA/IP) · MFA support · login anomaly detection · full admin audit trail · soft delete + audit log · signed URLs for sensitive downloads · webhook signature verification · Redis-based rate limiting · background queue for email/notifications · single-use short-expiry password reset · security monitoring/alerting · DB transactions for multi-table writes · security headers/cookies enforced via middleware.
