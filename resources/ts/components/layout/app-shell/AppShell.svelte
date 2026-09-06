<script lang="ts">
  import type { Snippet } from 'svelte';

  type ContentWidth = 'reading' | 'wide';

  interface Props {
    /** Sidebar contents. The frame sets the width; it never shrinks. */
    sidebar?: Snippet;
    /** Topbar leading region. Give it something that truncates: it yields before actions do. */
    breadcrumb?: Snippet;
    /**
     * Topbar trailing region. Never shrinks and is never clipped, so these must drop
     * their own labels below `lg` and fold into an overflow below `md`.
     */
    actions?: Snippet;
    /** `reading` caps the column at 760px, `wide` at 1080px. */
    width?: ContentWidth;
    children: Snippet;
  }

  const { sidebar, breadcrumb, actions, width = 'reading', children }: Props = $props();
</script>

<div class="shell">
  {#if sidebar}
    <aside class="sidebar">{@render sidebar()}</aside>
  {/if}

  <div class="column">
    {#if breadcrumb || actions}
      <header class="topbar">
        <div class="lead">{@render breadcrumb?.()}</div>
        {#if actions}
          <div class="actions">{@render actions()}</div>
        {/if}
      </header>
    {/if}

    <main class="content {width}">{@render children()}</main>
  </div>
</div>

<style lang="scss">
  .shell {
    display: flex;
    min-block-size: 100dvh;
    background: var(--color-canvas);
  }

  .sidebar {
    flex-shrink: 0;
    inline-size: var(--layout-sidebar);
    border-inline-end: 1px solid var(--color-border);
    background: var(--color-surface);
  }

  // The only element that flexes. `min-inline-size: 0` overrides the flex default of
  // `auto`, which would otherwise hold the column at its content's width and push the
  // page sideways.
  .column {
    display: flex;
    flex: 1;
    flex-direction: column;
    min-inline-size: 0;
  }

  .topbar {
    display: flex;
    flex-shrink: 0;
    gap: var(--space-4);
    align-items: center;
    border-block-end: 1px solid var(--color-border);
    padding-inline: var(--layout-gutter);
    block-size: var(--space-16);
  }

  .lead {
    flex: 1;
    min-inline-size: 0;
  }

  .actions {
    display: flex;
    flex-shrink: 0;
    gap: var(--space-2);
    align-items: center;
  }

  .content {
    inline-size: 100%;
    min-inline-size: 0;
    margin-inline: auto;
    padding-block: var(--space-6);
    padding-inline: var(--layout-gutter);
  }

  .content.reading {
    max-inline-size: calc(var(--layout-content) + var(--layout-gutter) * 2);
  }

  .content.wide {
    max-inline-size: calc(var(--layout-content-wide) + var(--layout-gutter) * 2);
  }
</style>
