# Passkeys

WebAuthn passkeys, optional for every user, provided by Laravel Fortify's `passkeys` feature over the first-party `laravel/passkeys` package. There is no TOTP: a passkey is a strong credential in its own right rather than a second factor on a password, it resists phishing, and it syncs through iCloud Keychain and Google Password Manager, so a lost device is not a lost credential. Passwords remain available so that a browser without passkey support never locks anyone out. Product behaviour in syoksheet-docs → features/authentication.md.

## ⚙️ Configuration

Only the `passkeys` feature is enabled. Registration, login, email verification and password reset stay on this application's own controllers, because they assume an `email` column that `user_emails` deliberately does not provide. See [endpoints.md](endpoints.md).

```php
'features' => [
    Features::passkeys(['confirmPassword' => true]),
],
```

> [!WARNING]
> `relying_party_id` must be pinned to `syoksheet.com`, the registrable domain, and **not** left as Fortify's default of the host parsed from `APP_URL`. The default binds a passkey to whichever surface registered it, so one created on `app.` fails on `admin.`. Every surface that can authenticate must also appear in `allowed_origins`.

```php
'passkeys' => [
    'relying_party_id' => 'syoksheet.com',
    'allowed_origins' => [/* apex, app., admin., api. */],
],
```

## 🔌 Routes

Fortify registers these; they are re-registered under the app domain group rather than used as-is, matching the `Route::domain()` structure.

| Route | Behaviour |
|-------|-----------|
| `GET /user/passkeys/options` | WebAuthn creation options for an authenticated user |
| `POST /user/passkeys` | Stores a credential with a user-supplied `name` |
| `DELETE /user/passkeys/{passkey}` | Removes one credential |
| `GET /passkeys/login/options` | WebAuthn challenge for signing in |
| `POST /passkeys/login` | Signs the user in, accepts a `remember` flag |
| `GET /passkeys/confirm/options` | Challenge for confirming a sensitive action |
| `POST /passkeys/confirm` | Marks the session password-confirmed |

Fortify applies its own `passkeys` rate limiter to the login, confirmation and registration routes; adjust through `fortify.limiters.passkeys`.

## 🖥️ Front End

The official `@laravel/passkeys` client, using its Svelte helper from `@laravel/passkeys/svelte`.

```ts
import { Passkeys } from '@laravel/passkeys'

await Passkeys.register({ name: 'MacBook Pro' })
await Passkeys.verify()
```

## 📏 Rules

- Registering or deleting a passkey requires password confirmation, and a passkey itself satisfies that confirmation.
- A user may hold several passkeys, each individually named, listed and revocable.
- Registering or deleting one sends a security confirmation email from `noreply@`.
- Guests of an organisation must hold a passkey: they are exempt from the org SSO gate, and this is the compensating control. See [sso.md](sso.md).

## 🗄️ Tables

Fortify's passkey credential table, plus `users` carrying no TOTP columns. See [database/users.md](../../database/users.md).
