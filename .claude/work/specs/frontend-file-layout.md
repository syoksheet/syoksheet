# Frontend File Layout

How `resources/ts/` is organized: one subtree per domain, one folder per page, and the rules for where a component or a piece of state lives. Replaces the flat `pages/{domain}/Page.svelte` layout and deletes `resources/ts/marketing/`.

## Why the current layout fails

Pages sit directly under `pages/{domain}/`, so a page has nowhere to put its own parts. The first page that needed parts (the homepage) pushed them into `resources/ts/marketing/`, a folder that maps to no domain and no page.

That folder was created to keep section components out of the Inertia page glob. That reason does not hold. `@inertiajs/vite` globs recursively but looks pages up by exact path (`node_modules/@inertiajs/vite/dist/index.js:354-371`):

```js
const pages = import.meta.glob('./pages/public/**/*.svelte')
const module = await (pages[`./pages/public/${name}.svelte`])?.()
```

A co-located component is never routable, because nothing renders it by name. The real cost is chunking: every globbed file becomes its own lazy chunk, so a page's parts ship as a dozen small requests instead of travelling with the page. On the server-rendered apex that costs LCP.

## The page extension

Both the client glob and the server-side existence check are narrowed to a dedicated extension, so only page files are treated as pages.

| Layer | Setting | Result |
|-------|---------|--------|
| Vite | `pages: { path: './pages', extension: '.page.svelte' }` | Glob becomes `**/*.page.svelte`; co-located components are invisible to it |
| Laravel | `config/inertia.php` → `extensions => ['page.svelte']` | `FileViewFinder` appends the extension to the name, so `ensure_pages_exist` checks the same set |

`pages` accepts `{ path, extension, transform, lazy }`, not only a string (`index.js:324-352`). Only `path` and `extension` are used; `transform` would hide the mapping between render name and file, and `lazy` stays at its default.

## Directory layout

```
resources/ts/
  components/                   design system: every domain, no product logic
    actions/button/
    data/avatar/
  domains/
    public/
      entry.ts                  was public.ts
      ssr.ts                    apex only
      components/               public-wide: SiteHeader, SiteFooter, ClosingCta
      stores/                   public-wide state
      pages/
        home/
          Index.page.svelte     Inertia::render('home/Index')
          Premise.svelte        home only
          Steps.svelte
        pricing/
          Index.page.svelte
          TierTable.svelte
    app/
      entry.ts
      components/
      stores/
      pages/
    admin/
      entry.ts
      components/
      stores/
      pages/
  types/
```

A page always gets a folder, even when it is a single file, so its first part has an obvious home and adding one never renames the render call.

## Page naming

The folder is the resource, the file is the action, following Inertia's own convention:

| File | Render call |
|------|-------------|
| `pages/home/Index.page.svelte` | `Inertia::render('home/Index')` |
| `pages/jobs/Index.page.svelte` | `Inertia::render('jobs/Index')` |
| `pages/jobs/Show.page.svelte` | `Inertia::render('jobs/Show')` |

Parts used by more than one page in the same resource folder go in `pages/{resource}/components/`. Parts used by one page sit beside it.

## Where a component lives

Duplicate freely inside one page. Promotion is by count, not by taste:

| Used by | Lives in |
|---------|----------|
| One page | Beside the page |
| Two or more pages in one resource folder | `pages/{resource}/components/` |
| Three or more pages across the domain | `domains/{domain}/components/` |
| Two or more domains | `components/`, stripped of product logic |

Moving down this table is a rename plus an import fix, so the cheap default is to leave it where it is until the count forces the move.

Site chrome is the one thing that starts at domain level rather than earning its way there. `SiteHeader`, `SiteFooter` and `ClosingCta` are on every page of the domain by definition, so their sharing is structural rather than speculative. Content primitives are not: `SectionHead`, `Kicker` and `ImageSlot` sit beside the home page until a second page actually needs them, because guessing that `/pricing` wants the same section head is exactly the speculation this table exists to prevent.

## Where state lives

| Scope | Location | Form |
|-------|----------|------|
| One component | Inside the component | `$state`, never a module |
| One page | `pages/{resource}/{name}.svelte.ts` | Rune module, imported by that page alone |
| One domain | `domains/{domain}/stores/{name}.svelte.ts` | Rune module |

> [!WARNING]
> The apex is server-rendered, and a module-level `$state` in Node is shared by every request that the process serves. One visitor's state becomes the next visitor's. On `public`, domain-wide state must be created per request and passed through `setContext`/`getContext` from the page, never held in a module.

`app.` and `admin.` are not server-rendered, so a module-level rune is safe there. Keeping the same context pattern across all three is still preferable, because a page that moves to the apex later would otherwise carry a silent leak with it.

## Files this changes

| File | Change |
|------|--------|
| `vite.config.ts` | Three entry paths, the SSR entry path, `$components` alias unchanged |
| `config/inertia.php` | Three `paths` values, `extensions` to `page.svelte`, comment block rewritten |
| `resources/views/domains/{public,app,admin}.blade.php` | `@vite` entry paths |
| `routes/public.php` | `Inertia::render('Home')` to `home/Index` |
| `routes/app.php`, `routes/admin.php` | `Welcome` to `welcome/Index` |
| `tests/Feature/InertiaBootstrapTest.php` | Entry path assertions |
| `tsconfig.json`, `eslint.config.ts` | Include globs still cover `resources/ts/**`, no change expected |
| `resources/ts/marketing/` | Deleted, contents to `domains/public/components/` |

## Known cost

A page importing SCSS breakpoints goes from four `../` to five, because pages sit two levels deeper. Design system components do not move, so their four stay four. The alternative is a Vite `loadPaths` alias, which was rejected earlier because it only resolves in editors configured for it.

## Out of scope

Route-level code splitting, per-page SSR beyond the apex, and any change to the design system's own folder structure.
