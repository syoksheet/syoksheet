<script lang="ts">
  interface Props {
    /** What belongs here, shown in the placeholder until a real asset replaces it. */
    label: string;
    /** CSS aspect-ratio value, for example "16 / 9". */
    ratio?: string;
    /** Path to the real asset. The placeholder disappears once this is set. */
    src?: string;
    alt?: string;
  }

  const { label, ratio = '16 / 9', src, alt }: Props = $props();
</script>

<div class="slot" style:aspect-ratio={ratio}>
  {#if src}
    <img {src} alt={alt ?? ''} />
  {:else}
    <span class="label" aria-hidden="true">{label}</span>
  {/if}
</div>

<style lang="scss">
  @use '../../../../../scss/typography-marketing' as marketing;

  .slot {
    display: grid;
    place-items: center;
    inline-size: 100%;
    overflow: hidden;
    border: 1px dashed var(--color-border-strong);
    border-radius: var(--radius-lg);
    background: var(--color-surface-sunken);
  }

  .slot:has(img) {
    border-style: solid;
    border-color: var(--color-border);
    box-shadow: var(--shadow-lg);
  }

  img {
    inline-size: 100%;
    block-size: 100%;
    object-fit: cover;
  }

  .label {
    max-inline-size: 32ch;
    padding: var(--space-4);
    color: var(--color-text-muted);
    text-align: center;
    text-wrap: balance;

    @include marketing.marketing-meta;
  }
</style>
