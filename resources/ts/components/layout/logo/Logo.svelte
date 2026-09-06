<script lang="ts">
  type Tone = 'brand' | 'ink' | 'inverse';
  type Variant = 'mark' | 'tile';

  interface Props {
    /** Rendered size in pixels. */
    size?: number;
    /** `tile` is the reversed square for app icons and favicons. */
    variant?: Variant;
    /** Ignored by `tile`, which is always reversed. */
    tone?: Tone;
    /** Omit where adjacent text already names the product; the mark is then hidden. */
    label?: string;
  }

  const { size = 24, variant = 'mark', tone = 'brand', label }: Props = $props();

  const MARK_SHARE_OF_TILE = 0.65;
  const COMPACT_MASTER_MAX_PX = 28;

  const markSize = $derived(variant === 'tile' ? size * MARK_SHARE_OF_TILE : size);
  const compact = $derived(markSize <= COMPACT_MASTER_MAX_PX);
</script>

{#if variant === 'tile'}
  <span
    class="tile"
    style:--size="{size}px"
    role={label ? 'img' : undefined}
    aria-label={label}
    aria-hidden={label ? undefined : 'true'}
  >
    <span class="mark" class:compact style:--size="{markSize}px"></span>
  </span>
{:else}
  <span
    class="mark {tone}"
    class:compact
    style:--size="{size}px"
    role={label ? 'img' : undefined}
    aria-label={label}
    aria-hidden={label ? undefined : 'true'}
  ></span>
{/if}

<style lang="scss">
  .mark {
    --mask-default: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Crect x='3' y='30.5' width='20' height='10' rx='5'/%3E%3Crect x='14' y='18.5' width='20' height='10' rx='5'/%3E%3Crect x='25' y='6.5' width='20' height='10' rx='5'/%3E%3C/svg%3E");
    --mask-compact: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Crect x='3' y='29.5' width='20' height='12' rx='6'/%3E%3Crect x='14' y='18' width='20' height='12' rx='6'/%3E%3Crect x='25' y='6.5' width='20' height='12' rx='6'/%3E%3C/svg%3E");

    display: inline-block;
    flex-shrink: 0;
    inline-size: var(--size);
    block-size: var(--size);

    // Solid ink cut to shape by the mask, so the mark inherits `color`.
    background: currentcolor;
    -webkit-mask: var(--mask-default) center / contain no-repeat;
    mask: var(--mask-default) center / contain no-repeat;
  }

  .mark.compact {
    -webkit-mask-image: var(--mask-compact);
    mask-image: var(--mask-compact);
  }

  .mark.brand {
    color: var(--color-action);
  }

  .mark.ink {
    color: var(--color-text);
  }

  .mark.inverse {
    color: var(--color-canvas);
  }

  .tile {
    display: inline-grid;
    place-items: center;
    flex-shrink: 0;
    inline-size: var(--size);
    block-size: var(--size);
    border-radius: 22%;
    background: var(--color-action);
    color: var(--color-canvas);
  }
</style>
