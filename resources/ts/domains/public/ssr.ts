import { createInertiaApp } from '@inertiajs/svelte';

/*
 * The SSR entry, apex only. @inertiajs/vite wraps this with the createServer bootstrap
 * in a production build and serves it from the dev server otherwise, so neither is
 * written out here.
 *
 * Only public pages can be reached from this entry. Nothing behind a login is ever
 * rendered in the SSR process, which is long-lived and shared by every visitor.
 */
// PhpStorm resolves the wrong createInertiaApp overload here. tsc and svelte-check both pass.
// noinspection TypeScriptValidateTypes
void createInertiaApp({
  pages: { path: './pages', extension: '.page.svelte' },
});
