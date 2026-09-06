<script lang="ts">
  type Kind = 'person' | 'organization';
  type Size = 22 | 26 | 34 | 44 | 64;

  interface Props {
    /** Shape encodes type: a circle is always a person, a rounded square always an organization. */
    kind?: Kind;
    size?: Size;
    /** Cropped image. Falls back to initials when absent or when it fails to load. */
    src?: string;
    /** Full name, used for the accessible name and to derive initials. */
    name: string;
    /** Stable key for the fallback color, usually the user or organization id. */
    colorKey?: string;
  }

  const { kind = 'person', size = 34, src, name, colorKey }: Props = $props();

  const PALETTE_SIZE = 5;

  const initials = $derived(
    name
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase() ?? '')
      .join(''),
  );

  const swatch = $derived.by(() => {
    const source = colorKey ?? name;
    let hash = 0;
    for (const character of source) {
      hash = (hash * 31 + character.charCodeAt(0)) | 0;
    }
    return (Math.abs(hash) % PALETTE_SIZE) + 1;
  });

  let failed = $state(false);
</script>

<span
  class="avatar {kind} swatch-{swatch}"
  class:image={src && !failed}
  style:--size="{size}px"
  role="img"
  aria-label={name}
>
  {#if src && !failed}
    <img {src} alt="" onerror={() => (failed = true)} />
  {:else}
    <span aria-hidden="true">{initials}</span>
  {/if}
</span>

<style lang="scss">
  .avatar {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    inline-size: var(--size);
    block-size: var(--size);
    overflow: hidden;
    color: var(--color-on-action);
    font-family: var(--font-sans);
    font-size: calc(var(--size) * 0.4);
    font-weight: 600;
    line-height: 1;
    user-select: none;
  }

  .person {
    border-radius: 999px;
  }

  .organization {
    border-radius: 22%;
  }

  .avatar.image {
    background: var(--color-surface-sunken);
  }

  img {
    inline-size: 100%;
    block-size: 100%;
    object-fit: cover;
  }

  .swatch-1 {
    background: var(--color-avatar-1);
  }

  .swatch-2 {
    background: var(--color-avatar-2);
  }

  .swatch-3 {
    background: var(--color-avatar-3);
  }

  .swatch-4 {
    background: var(--color-avatar-4);
  }

  .swatch-5 {
    background: var(--color-avatar-5);
  }
</style>
