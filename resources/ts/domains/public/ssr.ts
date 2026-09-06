import { createInertiaApp } from '@inertiajs/svelte';

/*
 * The server-side rendering entry, used by the apex only. The build wraps this file
 * with the server bootstrap, so none of that is written out here.
 *
 * Only public pages can be reached from this entry. The SSR process is long-lived and
 * shared by every visitor, so nothing behind a login is ever rendered in it.
 */
// PhpStorm picks the wrong createInertiaApp overload here. Both tsc and
// svelte-check accept this.
// noinspection TypeScriptValidateTypes
void createInertiaApp({
  pages: { path: './pages', extension: '.page.svelte' },
});
