import { createInertiaApp } from '@inertiajs/svelte';

/*
 * `pages` is the @inertiajs/vite shorthand. The plugin turns it into an
 * import.meta.glob for that one folder, which is what keeps the three bundles apart.
 *
 * No `setup` here on purpose. The default already checks data-server-rendered and
 * hydrates or mounts, and it builds the Svelte context that a hand-written setup would
 * quietly drop. Writing one also takes withApp away, since it is typed `never`
 * whenever you pass setup, and puts you on the hook for returning { body, head }
 * yourself on the SSR path.
 */
// PhpStorm resolves the wrong createInertiaApp overload here. tsc and svelte-check both pass.
// noinspection TypeScriptValidateTypes
void createInertiaApp({
  pages: './pages/admin',
});
