# syoksheet

The syoksheet application — a professional achievement platform where users document accomplishments, get them verified by peers and DNS-verified organisations, and verified skills power job matching.

One Laravel application serving four subdomains via domain routing: `api.` (the external API), `app.` and `admin.` (Inertia + Svelte UIs), and `www.` (server-rendered marketing and public pages).

## Documentation

- **Technical spec** — [`docs/`](docs/README.md): architecture, database schema, feature specs, validation, scheduled jobs
- **Product & platform docs** — the `syoksheet-docs` repository (features, pricing, decisions, infrastructure)
- **Design system** — [`design/`](design/): component specs (`docs/`) and preview cards (`previews/`)

## Development

All services run inside DDEV — see [`docs/infrastructure/local-development.md`](docs/infrastructure/local-development.md) for setup and the command reference.

```bash
ddev start          # app + postgres (primary & audit) + redis + mailpit
ddev npm run dev    # Vite dev server
ddev php artisan test --compact
```
