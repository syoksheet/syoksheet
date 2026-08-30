<script lang="ts">
  let { locale }: { locale: string } = $props();
</script>

<!--
  Every page in this folder needs its own head block. When SSR works, Inertia uses the
  head it rendered and skips the fallback in the root view, so a page without one goes
  out to search engines with no title and no description.

  Placeholder for now. Phase 14 does real per-page SEO and Open Graph tags, with the
  description coming from the translation files.
-->
<svelte:head>
  <title>syoksheet</title>
  <meta name="description" content="Document your professional achievements." />
</svelte:head>

<!-- Placeholder markup. The strings here go through the translation files once
     there are real screens to build. -->
<main>
  <h1>syoksheet</h1>
  <p>Locale: {locale}</p>
</main>
