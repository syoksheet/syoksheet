import { createInertiaApp } from '@inertiajs/svelte';

/*
 * The plugin turns `pages` into an import.meta.glob over this domain's folder alone,
 * which is what keeps the three bundles apart. The `.page.svelte` extension narrows
 * that glob to page files, so a component sitting beside its page is not globbed into
 * a chunk of its own.
 *
 * No `setup` here on purpose. The default already checks data-server-rendered and
 * hydrates or mounts, and it builds the Svelte context that a handwritten setup would
 * quietly drop. Writing one also takes withApp away, since it is typed `never`
 * whenever you pass setup, and puts you on the hook for returning { body, head }
 * yourself on the SSR path.
 */
// PhpStorm resolves the wrong createInertiaApp overload here. tsc and svelte-check both pass.
// noinspection TypeScriptValidateTypes
void createInertiaApp({
  pages: { path: './pages', extension: '.page.svelte' },
});
