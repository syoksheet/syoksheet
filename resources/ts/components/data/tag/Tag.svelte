<script lang="ts">
  import type { Snippet } from 'svelte';

  type Variant = 'skill' | 'keyword';

  interface Props {
    /** `skill` comes from the curated taxonomy, `keyword` is freeform and rendered with a leading #. */
    variant?: Variant;
    /** Renders a button instead of a span. Filters are the only interactive tags. */
    onclick?: (event: MouseEvent) => void;
    /** Filter state. Only meaningful with `onclick`. */
    active?: boolean;
    children: Snippet;
  }

  const { variant = 'skill', onclick, active = false, children }: Props = $props();
</script>

{#if onclick}
  <button class="tag {variant}" class:active type="button" aria-pressed={active} {onclick}>
    {#if variant === 'keyword'}<span class="hash">#</span>{/if}{@render children()}
  </button>
{:else}
  <span class="tag {variant}">
    {#if variant === 'keyword'}<span class="hash">#</span>{/if}{@render children()}
  </span>
{/if}

<style lang="scss">
  .tag {
    display: inline-flex;
    gap: 2px;
    align-items: center;
    block-size: 22px;
    padding-inline: 9px;
    border: 1px solid transparent;
    border-radius: 999px;
    font-size: 12px;
    white-space: nowrap;
  }

  button.tag {
    cursor: pointer;
  }

  button.tag:focus-visible {
    outline: 2px solid var(--color-focus);
    outline-offset: 2px;
  }

  .skill {
    background: var(--color-action-subtle);
    border-color: var(--color-info-border);
    color: var(--color-info-text);
    font-family: var(--font-sans);
    font-weight: 500;
  }

  .keyword {
    background: transparent;
    border-color: var(--color-border);
    color: var(--color-text-secondary);
    font-family: var(--font-mono);
  }

  .hash {
    opacity: 0.6;
  }

  // A filter that is on inverts to solid, so on and off are distinguishable without color alone.
  .tag.active {
    background: var(--color-action);
    border-color: var(--color-action);
    color: var(--color-on-action);
  }
</style>
