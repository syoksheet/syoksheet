# Two-Factor Authentication

TOTP-based 2FA using `pragmarx/google2fa-laravel`, with SVG QR codes from `linkxtr/laravel-qrcode`. Authenticator app required.

## 🔌 Endpoints

`TwoFactorController` in `app/Http/Controllers/Api/V1/User/`. Password confirmation is required before enabling or disabling.

| Route | Behaviour |
|-------|-----------|
| `POST /api/v1/me/confirm-password` | Confirms password; cached for `auth.password_timeout` seconds (default 3 hours) under `password_confirmed.{userId}` |
| `POST /api/v1/me/two-factor` | Generates TOTP secret + 8 recovery codes (encrypted at rest); active immediately |
| `DELETE /api/v1/me/two-factor` | Disables 2FA |
| `GET /api/v1/me/two-factor/qr-code` | `{ "svg": "<svg>...</svg>", "url": "otpauth://totp/..." }` |
| `GET /api/v1/me/two-factor/recovery-codes` | The 8 recovery codes |

## 🔧 Implementation

- `HasTwoFactor` trait on `User` — `twoFactorQrCodeUrl()`, `twoFactorQrCodeSvg()`, `recoveryCodes()`
- Secret and recovery codes stored encrypted in `users.two_factor_secret` / `users.two_factor_recovery_codes`
- Recovery code format: `xxxxxxxxxx-xxxxxxxxxx-xxxxxxxxxx`
- Enabling/disabling sends a security confirmation email (noreply@)
- Lost phone + lost recovery codes → support only, intentionally hard

## 🗄️ Tables

See [database/users.md](../../database/users.md).
