<script lang="ts">
  import type { Snippet } from 'svelte';
  import Kicker from './Kicker.svelte';

  interface Props {
    /** Two-digit section number for the kicker. */
    number?: string;
    /** The kicker label above the heading. */
    kicker?: string;
    heading: string;
    subheading?: string;
    children?: Snippet;
  }

  const { number, kicker, heading, subheading, children }: Props = $props();
</script>

<header class="head">
  {#if kicker}
    <Kicker {number}>{kicker}</Kicker>
  {/if}

  <h2>{heading}</h2>
  {#if subheading}<p class="subheading">{subheading}</p>{/if}
  {@render children?.()}
</header>

<style lang="scss">
  @use '../../../../../scss/typography-marketing' as marketing;

  h2 {
    max-inline-size: 20ch;
    margin-block-start: var(--space-5);
    text-wrap: balance;

    @include marketing.marketing-h2;
  }

  .subheading {
    max-inline-size: 62ch;
    margin-block-start: var(--space-4);
    color: var(--color-text-secondary);
    text-wrap: pretty;

    @include marketing.marketing-lead;
  }
</style>
