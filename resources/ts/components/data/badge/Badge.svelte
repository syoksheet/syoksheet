<script lang="ts">
  import type { Snippet } from 'svelte';

  type Variant = 'verified' | 'pending' | 'unverified' | 'info' | 'error' | 'count';

  interface Props {
    variant?: Variant;
    /** A glyph, so meaning survives without color. Required for every variant except `unverified` and `count`. */
    glyph?: Snippet;
    /** Context read only by assistive tech, for example "2 pending verifications" on a count. */
    screenReaderLabel?: string;
    children: Snippet;
  }

  const { variant = 'unverified', glyph, screenReaderLabel, children }: Props = $props();
</script>

<span class="badge {variant}">
  {#if glyph}<span class="glyph">{@render glyph()}</span>{/if}
  {@render children()}
  {#if screenReaderLabel}<span class="visually-hidden">{screenReaderLabel}</span>{/if}
</span>

<style lang="scss">
  @use '../../../../scss/typography' as type;

  .badge {
    display: inline-flex;
    gap: var(--space-1);
    align-items: center;
    block-size: 22px;
    padding-inline: 10px;
    border: 1px solid transparent;
    border-radius: 999px;
    font-family: var(--font-mono);
    white-space: nowrap;

    @include type.type-chip;
  }

  .badge:has(.glyph) {
    padding-inline-start: 6px;
  }

  .glyph {
    display: inline-flex;
    inline-size: 11px;
    block-size: 11px;
  }

  .verified {
    background: var(--color-verified-subtle);
    border-color: var(--color-verified-border);
    color: var(--color-verified-text);
    font-weight: 600;
  }

  .verified .glyph {
    inline-size: 13px;
    block-size: 13px;
  }

  .pending {
    background: var(--color-warning-subtle);
    border-color: var(--color-warning-border);
    color: var(--color-warning-text);
  }

  .unverified {
    background: var(--color-surface-sunken);
    border-color: var(--color-border);
    color: var(--color-text-secondary);
  }

  .info {
    background: var(--color-info-subtle);
    border-color: var(--color-info-border);
    color: var(--color-info-text);
  }

  .error {
    background: var(--color-danger-subtle);
    border-color: var(--color-danger-border);
    color: var(--color-danger-text);
  }

  .count {
    background: var(--color-surface-sunken);
    border-color: var(--color-border);
    color: var(--color-text);
  }
</style>
