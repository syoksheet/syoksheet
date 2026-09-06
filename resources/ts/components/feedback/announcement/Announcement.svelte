<script lang="ts">
  import type { Snippet } from 'svelte';

  interface Props {
    /**
     * Stable key for this announcement. Dismissal is remembered against it, so
     * changing the message means changing the id, and the bar returns.
     */
    id: string;
    /** Where the bar links to. The whole message is the link. */
    href: string;
    /** Short mono label before the message, for example "Founding member". */
    tag?: string;
    /** Accessible name for the dismiss control. Omit to make the bar permanent. */
    dismissLabel?: string;
    children: Snippet;
  }

  const { id, href, tag, dismissLabel, children }: Props = $props();

  const STORAGE_PREFIX = 'announcement:';

  /*
   * The bar renders by default, including on the server, and hides on mount for anyone
   * who dismissed it.
   *
   * Doing it the other way round, hiding until storage says otherwise, would leave the
   * bar out of the server render and add it on hydration. That shifts the whole page
   * down for every visitor, just to spare a flash for the few who dismissed it.
   *
   * Storage does not exist during server rendering and throws in some private windows,
   * so every access is guarded and the bar simply stays visible when it is unavailable.
   */
  let dismissed = $state(false);

  $effect(() => {
    try {
      dismissed = localStorage.getItem(STORAGE_PREFIX + id) === '1';
    } catch {
      dismissed = false;
    }
  });

  function dismiss() {
    dismissed = true;
    try {
      localStorage.setItem(STORAGE_PREFIX + id, '1');
    } catch {
      // A viewer with storage blocked simply sees it again next visit.
    }
  }
</script>

{#if !dismissed}
  <div class="announcement">
    <div class="inner">
      {#if tag}<span class="tag">{tag}</span>{/if}
      <a class="message" {href}>
        <span class="label">{@render children()}</span>
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <line x1="5" y1="12" x2="19" y2="12" />
          <polyline points="12 5 19 12 12 19" />
        </svg>
      </a>
    </div>

    {#if dismissLabel}
      <button class="dismiss" type="button" aria-label={dismissLabel} onclick={dismiss}>
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18" />
          <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>
    {/if}
  </div>
{/if}

<style lang="scss">
  @use '../../../../scss/typography' as type;
  @use '../../../../scss/breakpoints' as bp;

  .announcement {
    position: relative;
    background: var(--color-surface-inverse);
    color: var(--color-on-inverse);
  }

  // Mobile first. Centered text has nowhere to go on a narrow screen, so the bar
  // starts left-aligned and centers from `sm` up. The close button always keeps its
  // own room at the end.
  .inner {
    display: flex;
    gap: var(--space-2);
    align-items: center;
    max-inline-size: calc(var(--layout-content-wide) + var(--layout-gutter) * 2);
    margin-inline: auto;
    padding-block: 9px;
    padding-inline: var(--space-5) var(--space-12);
    text-align: start;

    @include bp.at-least('sm') {
      justify-content: center;
      padding-inline: var(--space-12);
      text-align: center;
    }
  }

  // Hidden on the narrowest screens, where the message itself needs every pixel it can
  // get before it starts truncating.
  .tag {
    display: none;
    flex-shrink: 0;
    padding-block: 2px;
    padding-inline: 7px;
    border: 1px solid color-mix(in srgb, var(--color-on-inverse-muted) 34%, transparent);
    border-radius: 999px;
    color: var(--color-on-inverse-muted);
    font-family: var(--font-mono);
    text-transform: uppercase;

    @include type.type-chip;

    letter-spacing: 0.08em;

    @include bp.at-least('sm') {
      display: inline-block;
    }
  }

  // The bar is one line at every width: a message long enough to wrap would push the
  // page down on a phone, so it truncates and the arrow stays put as the affordance.
  .message {
    display: inline-flex;
    gap: var(--space-2);
    align-items: center;
    min-inline-size: 0;
    color: var(--color-on-inverse);
    text-decoration: none;

    @include type.type-body;

    font-weight: 500;
    letter-spacing: -0.005em;
  }

  .label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .message:hover {
    color: var(--color-on-inverse-hover);
  }

  .message svg {
    flex-shrink: 0;
    inline-size: 13px;
    block-size: 13px;
    fill: none;
    stroke: currentcolor;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-width: 2;
    transition: transform var(--duration-fast) var(--ease-move);
  }

  .message:hover svg {
    transform: translateX(2px);
  }

  .dismiss {
    position: absolute;
    inset-block-start: 50%;
    inset-inline-end: var(--space-5);
    display: grid;
    place-items: center;
    inline-size: 24px;
    block-size: 24px;
    padding: 0;
    transform: translateY(-50%);
    border: none;
    border-radius: var(--radius-sm);
    background: transparent;
    color: var(--color-on-inverse-muted);
    cursor: pointer;
  }

  .dismiss:hover {
    background: color-mix(in srgb, var(--color-on-inverse) 10%, transparent);
    color: var(--color-on-inverse);
  }

  .dismiss svg {
    inline-size: 13px;
    block-size: 13px;
    fill: none;
    stroke: currentcolor;
    stroke-linecap: round;
    stroke-width: 2;
  }
</style>
