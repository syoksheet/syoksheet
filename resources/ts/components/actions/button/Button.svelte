<script lang="ts">
  import type { Snippet } from 'svelte';

  type Variant = 'primary' | 'secondary' | 'ghost' | 'verify' | 'destructive' | 'danger';
  type Size = 'sm' | 'md' | 'lg';
  type Width = 'auto' | 'full';

  interface Props {
    /** Only one `primary` per view. `verify` and `danger` are semantic, not hierarchical. */
    variant?: Variant;
    /** `lg` is for marketing and primary funnels only. */
    size?: Size;
    /** `full` stretches to the container, for mobile sheets and single-action forms. */
    width?: Width;
    /** Renders an anchor instead of a button. */
    href?: string;
    type?: 'button' | 'submit';
    disabled?: boolean;
    icon?: Snippet;
    trailing?: Snippet;
    onclick?: (event: MouseEvent) => void;
    /** Required. There are no icon-only buttons; use the icon-button control instead. */
    children: Snippet;
  }

  const {
    variant = 'primary',
    size = 'md',
    width = 'auto',
    href,
    type = 'button',
    disabled = false,
    icon,
    trailing,
    onclick,
    children,
  }: Props = $props();
</script>

{#snippet inner()}
  {#if icon}<span class="icon">{@render icon()}</span>{/if}
  <span class="label">{@render children()}</span>
  {#if trailing}<span class="icon">{@render trailing()}</span>{/if}
{/snippet}

{#if href}
  <a class="button {variant} {size} {width}" {href} aria-disabled={disabled || undefined}>
    {@render inner()}
  </a>
{:else}
  <button class="button {variant} {size} {width}" {type} {disabled} {onclick}
    >{@render inner()}</button
  >
{/if}

<style lang="scss">
  .button {
    display: inline-flex;
    gap: var(--space-2);
    align-items: center;
    justify-content: center;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    font-family: var(--font-sans);
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background var(--duration-fast) var(--ease-fade);
  }

  .full {
    inline-size: 100%;
  }

  .button:disabled,
  .button[aria-disabled='true'] {
    background: var(--color-surface-disabled);
    border-color: var(--color-border-disabled);
    color: var(--color-text-disabled);
    cursor: not-allowed;
  }

  .button:focus-visible {
    outline: 2px solid var(--color-focus);
    outline-offset: 2px;
  }

  .icon {
    display: inline-flex;
    flex-shrink: 0;
    inline-size: 15px;
    block-size: 15px;
  }

  .sm {
    block-size: 31px;
    padding-inline: var(--space-3);
    font-size: 13px;
  }

  .md {
    block-size: 38px;
    padding-inline: var(--space-4);
    font-size: 14px;
  }

  .lg {
    block-size: 46px;
    padding-inline: var(--space-6);
    font-size: 15px;
  }

  .primary {
    background: var(--color-action);
    color: var(--color-on-action);
  }

  .primary:hover:not(:disabled) {
    background: var(--color-action-hover);
  }

  .secondary {
    background: var(--color-surface);
    border-color: var(--color-border-strong);
    color: var(--color-text);
  }

  .secondary:hover:not(:disabled) {
    background: var(--color-surface-sunken);
  }

  .ghost {
    background: transparent;
    color: var(--color-text-secondary);
  }

  .ghost:hover:not(:disabled) {
    background: var(--color-surface-sunken);
    color: var(--color-text);
  }

  .verify {
    background: var(--color-verified);
    color: var(--color-on-success);
  }

  .destructive {
    background: transparent;
    border-color: var(--color-danger-border);
    color: var(--color-danger);
  }

  .destructive:hover:not(:disabled) {
    background: var(--color-danger-subtle);
  }

  .danger {
    background: var(--color-danger);
    color: var(--color-on-danger);
  }

  .danger:hover:not(:disabled) {
    background: var(--color-danger-strong);
  }
</style>
