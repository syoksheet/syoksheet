import { createInertiaApp } from '@inertiajs/svelte';

/*
 * The plugin turns `pages` into an import.meta.glob over this domain's folder alone,
 * which is what keeps the three bundles apart. The .page.svelte extension narrows that
 * glob to page files, so a component sitting beside its page does not become a chunk of
 * its own.
 *
 * There is no `setup` here on purpose. The default one already hydrates or mounts
 * depending on whether the page was server-rendered, and it builds the Svelte context
 * that a hand-written setup would quietly drop. Writing one also removes withApp, which
 * the adapter types as never whenever setup is present.
 */
// PhpStorm picks the wrong createInertiaApp overload here. Both tsc and
// svelte-check accept this.
// noinspection TypeScriptValidateTypes
void createInertiaApp({
  pages: { path: './pages', extension: '.page.svelte' },
});
