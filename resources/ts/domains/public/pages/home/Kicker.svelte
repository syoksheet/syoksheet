<script lang="ts">
  import type { Snippet } from 'svelte';

  interface Props {
    /** Two-digit section number, running in order down the page. */
    number?: string;
    children: Snippet;
  }

  const { number, children }: Props = $props();
</script>

<div class="kicker">
  {#if number}<span class="number">{number}</span>{/if}
  {@render children()}
</div>

<style lang="scss">
  @use '../../../../../scss/typography-marketing' as marketing;

  .kicker {
    display: flex;
    gap: var(--space-3);
    align-items: center;
    color: var(--color-text-muted);

    @include marketing.marketing-label;
  }

  .number {
    color: var(--color-action);
  }

  .kicker::after {
    content: '';
    flex: 1;
    min-inline-size: 24px;
    block-size: 1px;
    background: linear-gradient(to right, var(--color-border), transparent);
  }
</style>
