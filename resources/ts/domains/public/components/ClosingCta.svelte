<script lang="ts">
  import { Button } from '$components/actions/button';

  interface Props {
    heading: string;
    body: string;
    /** Short reassurances under the button. Each must be verifiable. */
    meta?: string[];
  }

  const { heading, body, meta = [] }: Props = $props();
</script>

<section class="cta">
  <div class="inner">
    <h2>{heading}</h2>
    <p>{body}</p>
    <Button variant="primary" size="lg" href="/signup">Get started for free</Button>
    {#if meta.length}
      <ul class="meta">
        {#each meta as item (item)}<li>{item}</li>{/each}
      </ul>
    {/if}
  </div>
</section>

<style lang="scss">
  @use '../../../../scss/typography-marketing' as marketing;

  .cta {
    padding-block: clamp(72px, 10vw, 124px);
    border-block-start: 1px solid var(--color-border);
    background: var(--color-surface-sunken);
  }

  .inner {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    align-items: center;
    max-inline-size: calc(var(--layout-content) + var(--layout-gutter) * 2);
    margin-inline: auto;
    padding-inline: var(--layout-gutter);
    text-align: center;
  }

  h2 {
    text-wrap: balance;

    @include marketing.marketing-h1;
  }

  p {
    max-inline-size: 52ch;
    color: var(--color-text-secondary);
    text-wrap: pretty;

    @include marketing.marketing-lead;
  }

  .meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2) var(--space-5);
    justify-content: center;
    margin-block-start: var(--space-1);
    color: var(--color-text-muted);
    list-style: none;

    @include marketing.marketing-meta;
  }
</style>
