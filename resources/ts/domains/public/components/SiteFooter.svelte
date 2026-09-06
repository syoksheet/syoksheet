<script lang="ts">
  import { Logo } from '$components/layout/logo';

</script>

<footer class="footer">
  <div class="inner">
    <div class="top">
      <div class="brand-block">
        <a class="brand" href="/">
          <Logo size={26} tone="inverse" />
          <span>syoksheet</span>
        </a>
        <p class="tagline">
          A record of the work that got you here. Written by you, confirmed by the people who were
          there.
        </p>
      </div>

      <div class="column">
        <h2>Product</h2>
        <ul>
          <li><a href="/#how-it-works">How verification works</a></li>
          <li><a href="/pricing">Pricing</a></li>
        </ul>
      </div>

      <div class="column">
        <h2>For organizations</h2>
        <ul>
          <li><a href="/for-organizations">Why syoksheet</a></li>
          <li><a href="/pricing#organizations">Organization plans</a></li>
        </ul>
      </div>
    </div>

    <div class="bottom">
      <span class="meta">© 2026 syoksheet Sdn Bhd</span>
      <a class="meta" href="/.well-known/security.txt">security.txt</a>
    </div>
  </div>
</footer>

<style lang="scss">
  @use '../../../../scss/typography-marketing' as marketing;
  @use '../../../../scss/breakpoints' as bp;

  .footer {
    padding-block: clamp(44px, 6vw, 64px) 36px;
    background: var(--color-surface-inverse);
    color: var(--color-on-inverse);
  }

  .inner {
    max-inline-size: calc(var(--layout-content-wide) + var(--layout-gutter) * 2);
    margin-inline: auto;
    padding-inline: var(--layout-gutter);
  }

  .top {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-8);
    margin-block-end: clamp(36px, 5vw, 56px);

    @include bp.at-least('md') {
      grid-template-columns: 2fr 1fr 1fr;
      gap: clamp(28px, 4vw, 48px);
    }
  }

  .brand {
    display: inline-flex;
    gap: 9px;
    align-items: center;
    color: var(--color-on-inverse);
    text-decoration: none;

    @include marketing.marketing-h4;
  }

  .tagline {
    max-inline-size: 38ch;
    margin-block-start: var(--space-4);
    color: var(--color-on-inverse-muted);
    text-wrap: pretty;

    @include marketing.marketing-small;
  }

  .column h2 {
    margin-block-end: var(--space-4);
    color: var(--color-on-inverse-muted);

    @include marketing.marketing-label;
  }

  .column ul {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    list-style: none;
  }

  .column a {
    color: var(--color-on-inverse-hover);
    text-decoration: none;

    @include marketing.marketing-small;
  }

  .column a:hover {
    color: var(--color-on-inverse);
  }

  .bottom {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-4) var(--space-6);
    align-items: center;
    justify-content: space-between;
    padding-block-start: var(--space-6);
    border-block-start: 1px solid color-mix(in srgb, var(--color-on-inverse-muted) 26%, transparent);
  }

  .meta {
    color: var(--color-on-inverse-muted);
    text-decoration: none;

    @include marketing.marketing-meta;
  }

  a.meta:hover {
    color: var(--color-on-inverse);
  }
</style>
