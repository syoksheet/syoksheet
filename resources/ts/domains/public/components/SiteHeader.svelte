<script lang="ts">
  import { Dialog } from 'bits-ui';
  import { Announcement } from '$components/feedback/announcement';
  import { Button } from '$components/actions/button';
  import { Logo } from '$components/layout/logo';

  const links = [
    { href: '/for-organizations', label: 'For organizations' },
    { href: '/pricing', label: 'Pricing' },
  ];

  let menuOpen = $state(false);

  /*
   * This has to stay in step with the `md` breakpoint in the SCSS. A media query cannot
   * read a custom property, so the value lives in both places and nothing checks that.
   *
   * The menu closes when the viewport grows past it, rather than simply hiding. Hiding
   * an open dialog would leave the scroll lock on and focus trapped in a subtree nobody
   * can see, and the button that would close it is hidden at this width too.
   */
  const MENU_BREAKPOINT = '(min-width: 840px)';

  $effect(() => {
    const query = window.matchMedia(MENU_BREAKPOINT);
    const close = () => {
      if (query.matches) {
        menuOpen = false;
      }
    };

    close();
    query.addEventListener('change', close);
    return () => query.removeEventListener('change', close);
  });
</script>

<Announcement id="free-tier-launch" href="/pricing" tag="Free" dismissLabel="Dismiss announcement">
  Unlimited achievements and verification, free forever
</Announcement>

<div class="header-bar">
  <nav class="nav" aria-label="Primary">
    <a class="brand" href="/">
      <Logo size={24} />
      <span>syoksheet</span>
    </a>

    <div class="actions">
      {#each links as link (link.href)}
        <a class="link" href={link.href}>{link.label}</a>
      {/each}
      <Button variant="ghost" size="md" href="/signin">Sign in</Button>
      <Button variant="primary" size="md" href="/signup">Get started</Button>
    </div>

    <Dialog.Root bind:open={menuOpen}>
      <Dialog.Trigger>
        {#snippet child({ props })}
          <button {...props} class="toggle" aria-label="Open menu">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <line x1="4" y1="9" x2="20" y2="9" />
              <line x1="4" y1="15" x2="20" y2="15" />
            </svg>
          </button>
        {/snippet}
      </Dialog.Trigger>

      <Dialog.Portal>
        <Dialog.Content>
          {#snippet child({ props })}
            <div {...props} class="panel">
              <Dialog.Title class="visually-hidden">Menu</Dialog.Title>

              <div class="panel-bar">
                <a class="brand" href="/" onclick={() => (menuOpen = false)}>
                  <Logo size={24} />
                  <span>syoksheet</span>
                </a>

                <Dialog.Close>
                  {#snippet child({ props: closeProps })}
                    <button {...closeProps} class="toggle" aria-label="Close menu">
                      <svg viewBox="0 0 24 24" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                      </svg>
                    </button>
                  {/snippet}
                </Dialog.Close>
              </div>

              <nav class="panel-links" aria-label="Menu">
                <ul>
                  {#each links as link (link.href)}
                    <li>
                      <a href={link.href} onclick={() => (menuOpen = false)}>{link.label}</a>
                    </li>
                  {/each}
                </ul>
              </nav>

              <div class="panel-actions">
                <Button variant="secondary" size="lg" href="/signin" width="full">Sign in</Button>
                <Button variant="primary" size="lg" href="/signup" width="full">Get started</Button>
              </div>
            </div>
          {/snippet}
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  </nav>
</div>

<style lang="scss">
  @use '../../../../scss/typography-marketing' as marketing;
  @use '../../../../scss/breakpoints' as bp;

  .header-bar {
    position: sticky;
    inset-block-start: 0;
    z-index: 50;
    background: color-mix(in srgb, var(--color-canvas) 82%, transparent);
    box-shadow: 0 1px 0 var(--color-border);
    backdrop-filter: saturate(160%) blur(12px);
  }

  .nav {
    display: flex;
    gap: var(--space-4);
    align-items: center;
    justify-content: space-between;
    max-inline-size: calc(var(--layout-content-wide) + var(--layout-gutter) * 2);
    block-size: 64px;
    margin-inline: auto;
    padding-inline: var(--layout-gutter);
  }

  .brand {
    display: inline-flex;
    flex: none;
    gap: 9px;
    align-items: center;
    color: var(--color-text);
    text-decoration: none;

    @include marketing.marketing-h4;
  }

  .actions {
    display: none;
    flex: none;
    gap: var(--space-2);
    align-items: center;

    @include bp.at-least('md') {
      display: flex;
    }
  }

  .link {
    display: inline-flex;
    padding-inline: var(--space-3);
    color: var(--color-text-secondary);
    font-weight: 500;
    text-decoration: none;

    @include marketing.marketing-small;
  }

  .link:hover {
    color: var(--color-text);
  }

  .toggle {
    display: grid;
    place-items: center;
    inline-size: 40px;
    block-size: 40px;
    padding: 0;
    border-radius: var(--radius-sm);
    color: var(--color-text);

    @include bp.at-least('md') {
      display: none;
    }
  }

  .toggle:hover {
    background: var(--color-surface-sunken);
  }

  .toggle svg {
    inline-size: 22px;
    block-size: 22px;
    fill: none;
    stroke: currentcolor;
    stroke-linecap: round;
    stroke-width: 2;
  }

  // There is no overlay. The panel is opaque and covers the whole viewport, so a
  // scrim behind it would only ever show if the panel failed to paint.
  .panel {
    position: fixed;
    z-index: 100;
    display: flex;
    flex-direction: column;
    inset: 0;
    overflow-y: auto;
    background: var(--color-canvas);
  }

  .panel-bar {
    display: flex;
    flex: none;
    align-items: center;
    justify-content: space-between;
    block-size: 64px;
    padding-inline: var(--layout-gutter);
    border-block-end: 1px solid var(--color-border);
  }

  .panel-links {
    flex: 1;
    padding-inline: var(--layout-gutter);
  }

  .panel-links li + li {
    border-block-start: 1px solid var(--color-border-subtle);
  }

  .panel-links a {
    display: block;
    padding-block: var(--space-5);
    color: var(--color-text);
    font-weight: 500;
    text-decoration: none;

    @include marketing.marketing-h4;
  }

  .panel-actions {
    display: flex;
    flex: none;
    flex-direction: column;
    gap: var(--space-3);
    padding: var(--layout-gutter);
    padding-block-start: var(--space-6);
  }
</style>
