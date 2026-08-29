# Auth: Endpoints & Implementation

Core authentication: registration, login, logout, email verification, and password reset. Custom controllers (no Fortify), session/token auth by Sanctum, Google OAuth by Socialite.

## 🏗️ Email Architecture

There is no `email` column on `users`: all emails live in `user_emails` (`primary`/`backup`/`work`). A custom `UserEmailProvider` (`app/Auth/UserEmailProvider.php`) extends `EloquentUserProvider` to resolve users through `user_emails` where `type = primary`, covering login, password reset, and Sanctum token lookup.

## 🔌 Endpoints

Controllers live in `app/Http/Controllers/Auth/User/`.

| Route | Controller | Behaviour |
|-------|-----------|-----------|
| `POST /api/auth/register` | `RegisterController` | name, email, password + confirmation (`Password::default()`, min 8). Creates `User` + `UserEmail` (type=primary, unverified) in one transaction, fires `Registered` → verification email. 201. Duplicate email → 422. |
| `POST /api/auth/login` | `LoginController` | Credentials resolved via `UserEmailProvider`. Sanctum session. 204 on success. |
| `POST /api/auth/logout` | `LoginController` | Ends the session. 204. |
| `POST /api/auth/forgot-password` | `PasswordResetController@sendLink` | Accepts primary **or** backup email. Always 200 with a generic message, no email enumeration. Token valid 60 minutes. |
| `POST /api/auth/reset-password` | `PasswordResetController@reset` | token, email, password + confirmation via `Password::reset()`. Fires `PasswordReset`. 200, or 422 on failure. |
| `POST /api/auth/email/verify` | `EmailVerificationController` | Resend verification. Always 202 (silently skips if already verified). |
| `GET /api/email/verify/{id}/{hash}` | `EmailVerificationController@verify` | Named `verification.verify`. Verifies the SHA-1 hash against the primary email, marks verified, fires `Verified`, redirects to `config('app.frontend_url')`. |
| `GET /sanctum/csrf-cookie` | None | CSRF bootstrap where a token-less client needs it; the Inertia UIs use standard same-origin CSRF. |

## 🔑 Google OAuth (Socialite)

| Route | Behaviour |
|-------|-----------|
| `GET /auth/google/redirect` | Redirect to Google |
| `GET /auth/google/callback` | Three paths: (1) existing social account → login; (2) existing verified matching email → link + login; (3) new → create user + verified primary email + social account + login |

## 🚦 Rate Limits

| Action | Limit |
|--------|-------|
| Login | 5 attempts/min per email; 10 consecutive failures → 15-min lockout |
| Admin login | 5 attempts/min per email; same lockout rules |
| Register | 10/min |
| Password reset request | 3/hour |
| Verification email resend | 3/hour |

## 🔒 Guard Isolation

All `/api/v1/` routes run `auth:sanctum` → `EnsureUser`; an `Admin` token on a user route → 403. The mirror-image `EnsureAdmin` protects `/api/admin/v1/`. See [../admin/endpoints.md](../admin/endpoints.md).

## 📧 Emails

Registration → verification email; password reset → reset link; 2FA enabled/disabled → security confirmation. All from noreply@syoksheet.com.

## 📋 Audit Events

All `auth` domain, `internal` visibility, with IP and user agent always captured. Full catalog in [../audit/events.md](../audit/events.md): `user.registered`, `user.login`, `user.logout`, `user.login_failed`, `user.password_changed`, `user.password_reset_requested`, `user.email_changed`, `user.oauth_connected`, `user.oauth_disconnected`, `user.impersonated`, `admin.login`, `admin.logout`, `admin.login_failed`.

## 🗄️ Tables

See [database/users.md](../../database/users.md).
