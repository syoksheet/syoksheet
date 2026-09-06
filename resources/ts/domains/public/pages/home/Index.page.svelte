<script lang="ts">
  import { Button } from '$components/actions/button';
  import { Badge } from '$components/data/badge';
  import { ClosingCta, SiteFooter, SiteHeader } from '../../components';
  import ImageSlot from './ImageSlot.svelte';
  import SectionHead from './SectionHead.svelte';

  const premise = [
    {
      number: '01',
      heading: 'A CV is a claim',
      body: 'Anyone can write that they cut churn by a third. Nothing on a CV, a profile or a portfolio is confirmed by anyone else.',
    },
    {
      number: '02',
      heading: 'References are slow and thin',
      body: 'By the time someone calls a former manager, the details are gone. What survives is a job title and a date range.',
    },
    {
      number: '03',
      heading: 'The proof exists at the time',
      body: 'The people who saw the work can confirm it in a minute, if you ask while they still remember. syoksheet is where that gets recorded.',
    },
  ];

  const steps = [
    {
      number: '1',
      stage: 'Draft',
      heading: 'You write it down',
      body: 'Describe the work, the outcome and the dates. Add private context only your verifiers see.',
      placeholder: 'Screenshot: writing a brag',
    },
    {
      number: '2',
      stage: 'Sign-off',
      heading: 'Someone who was there confirms it',
      body: 'Nominate a manager or peers at that organization. They confirm or decline, and never edit your words.',
      placeholder: 'Screenshot: a verifier confirming',
    },
    {
      number: '3',
      stage: 'Confirmed',
      heading: 'It earns a checkmark',
      body: 'Confirmed brags show who vouched and when. Unverified ones stay visible, and clearly marked.',
      placeholder: 'Screenshot: a verified brag',
    },
  ];

  const features = [
    {
      label: '01 · Document',
      heading: 'Every achievement in one tight card.',
      body: 'Title, organization, dates, impact and the skills it exercised. Attach the deck, the dashboard or the commit range as evidence, and keep private context for your verifiers off the public page.',
      placeholder: 'Screenshot: the brag composer',
    },
    {
      label: '02 · Verify',
      heading: 'Confirmed by the company that employed you.',
      body: 'An organization proves it owns its domain with a single DNS record. From then on its people can confirm work done there, and peers can confirm too. Every verification is traceable, revocable, and never capped by plan.',
      placeholder: 'Screenshot: verification requests',
    },
    {
      label: '03 · Share',
      heading: 'One link instead of a list of bullet points.',
      body: 'Publish a clean wall at syoksheet.com/you, or export a recruiter-ready PDF with the verification trail attached. Pin your strongest brags, keep drafts private, and see what people actually read.',
      placeholder: 'Screenshot: a public wall',
    },
  ];

  const orgPoints = [
    { tier: 'Free forever', heading: 'Domain verification', body: 'One DNS record. Your badge and organization wall go live, and your people can start confirming.' },
    { tier: 'Free forever', heading: 'Verification queue', body: 'Unlimited requests, unlimited members and teams. No per-member charge on any tier.' },
    { tier: 'Growth', heading: 'Talent search', body: 'Search verified achievements across the platform and shortlist on evidence, not keywords.' },
    { tier: 'Business', heading: 'API, webhooks, SSO', body: 'Push jobs in, get verification events out, and sign your team in through your own provider.' },
  ];
</script>

<svelte:head>
  <title>syoksheet · Your career, verified</title>
  <meta
    name="description"
    content="Document the work that got you here and let the people who were there confirm it. A professional record hiring managers can check, not just read."
  />
  <meta property="og:title" content="syoksheet · Your career, verified" />
  <meta
    property="og:description"
    content="Document the work that got you here and let the people who were there confirm it."
  />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://syoksheet.com/" />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="canonical" href="https://syoksheet.com/" />
</svelte:head>

<SiteHeader />

<main>
  <section class="hero">
    <div class="container">
      <h1>Your career, <em>verified.</em></h1>
      <p class="intro">
        syoksheet is the record of your work that hiring managers can actually check.
        Document what you did, and let the people who were there confirm it.
      </p>
      <div class="hero-actions">
        <Button variant="primary" size="lg" href="/signup">Get started for free</Button>
      </div>
      <ul class="hero-reassurance">
        <li><b>Free</b> forever tier</li>
        <li><b>No</b> credit card</li>
      </ul>
      <div class="hero-visual">
        <ImageSlot label="Hero: a verified achievement on a public wall" ratio="16 / 9" />
      </div>
    </div>
  </section>

  <section class="premise">
    <div class="container">
      <ol class="premise-grid">
        {#each premise as cell (cell.number)}
          <li>
            <span class="number">{cell.number}</span>
            <h2>{cell.heading}</h2>
            <p>{cell.body}</p>
          </li>
        {/each}
      </ol>
    </div>
  </section>

  <section class="band" id="how-it-works">
    <div class="container">
      <SectionHead
        number="01"
        kicker="How verification works"
        heading="How a claim becomes verified."
        subheading="Three moves, in order. You write it, someone who was there confirms it, and the confirmation is tied to a domain the company proved it owns."
      />
      <ol class="steps">
        {#each steps as step (step.number)}
          <li>
            <div class="step-copy">
              <div class="step-node">
                <span class="step-number">{step.number}</span>
                <span class="step-label">{step.stage}</span>
              </div>
              <h3>{step.heading}</h3>
              <p>{step.body}</p>
            </div>
            <ImageSlot label={step.placeholder} ratio="4 / 3" />
          </li>
        {/each}
      </ol>
    </div>
  </section>

  <section class="band sunken">
    <div class="container">
      <SectionHead
        number="02"
        kicker="What you get"
        heading="A headline says you did it. syoksheet shows who agrees."
        subheading="Three parts, working together: document the work, get it confirmed by people who were there, and share a wall a hiring manager can take at face value."
      />
      <div class="rows">
        {#each features as feature, i (feature.label)}
          <div class="row" class:flip={i % 2 === 1}>
            <div class="row-copy">
              <span class="row-label">{feature.label}</span>
              <h3>{feature.heading}</h3>
              <p>{feature.body}</p>
            </div>
            <ImageSlot label={feature.placeholder} ratio="4 / 3" />
          </div>
        {/each}
      </div>
    </div>
  </section>

  <section class="organizations" id="for-organizations">
    <div class="container">
      <SectionHead
        number="03"
        kicker="For organizations"
        heading="Verify your people. Hire from a record that already checks out."
        subheading="Claim your domain, confirm the work your team actually did, and search candidates by verified achievement instead of self-reported experience."
      />
      <ul class="organization-grid">
        {#each orgPoints as point (point.heading)}
          <li>
            <Badge variant="info">{point.tier}</Badge>
            <h3>{point.heading}</h3>
            <p>{point.body}</p>
          </li>
        {/each}
      </ul>
      <div class="organization-actions">
        <Button variant="primary" size="lg" href="/for-organizations">
          See what organizations get
        </Button>
      </div>
    </div>
  </section>

  <section class="band sunken">
    <div class="container">
      <SectionHead
        number="04"
        kicker="The public wall"
        heading="This is what a hiring manager opens."
        subheading="A wall is the achievements, the evidence, and the people who confirmed them. Verified work carries a checkmark; honest drafts stay clearly marked."
      />
      <div class="wall-visual">
        <ImageSlot label="Screenshot: a public syoksheet wall" ratio="16 / 10" />
      </div>
    </div>
  </section>

  <section class="band">
    <div class="container">
      <SectionHead
        number="05"
        kicker="Pricing"
        heading="Free to add to the record. Paid to pull value out."
        subheading="Writing achievements, requesting verification and holding a public wall are never capped or charged. Analytics, exports, talent search and API access are what people pay for."
      />
      <div class="teasers">
        <div class="teaser">
          <span class="audience">For individuals</span>
          <dl>
            <div><dt>Free<small>Unlimited achievements and verification</small></dt><dd>$0</dd></div>
            <div><dt>Pro<small>Attachments, analytics, PDF export</small></dt><dd>$10 / mo</dd></div>
          </dl>
          <a class="more" href="/pricing#individuals">Compare individual plans</a>
        </div>
        <div class="teaser">
          <span class="audience">For organizations</span>
          <dl>
            <div><dt>Free<small>Verification, wall, 3 job postings</small></dt><dd>$0</dd></div>
            <div><dt>Growth<small>Talent search, analytics, branding</small></dt><dd>$39 / mo</dd></div>
            <div><dt>Business<small>API, webhooks, SSO</small></dt><dd>$99 / mo</dd></div>
          </dl>
          <a class="more" href="/pricing#organizations">Compare organization plans</a>
        </div>
      </div>
      <p class="teaser-note">
        No per-member charge and no per-verification fee. Building the record is always
        free. You pay only to get more out of it.
      </p>
    </div>
  </section>

  <ClosingCta
    heading="Start the record before you need it."
    body="Write the work down while you still remember the numbers, and get it confirmed by the people who were in the room."
    meta={['Free forever tier', 'No credit card', 'Verification never capped']}
  />
</main>

<SiteFooter />

<style lang="scss">
  @use '../../../../../scss/typography' as type;
  @use '../../../../../scss/typography-marketing' as marketing;
  @use '../../../../../scss/breakpoints' as bp;

  .container {
    max-inline-size: calc(var(--layout-content-wide) + var(--layout-gutter) * 2);
    margin-inline: auto;
    padding-inline: var(--layout-gutter);
  }

  .band {
    padding-block: clamp(64px, 9vw, 112px);
  }

  .band.sunken {
    border-block: 1px solid var(--color-border);
    background: var(--color-surface-sunken);
  }

  /* ---------- hero ---------- */

  .hero {
    padding-block: clamp(56px, 8vw, 96px) clamp(48px, 6vw, 80px);
  }

  h1 {
    max-inline-size: 16ch;
    text-wrap: balance;

    @include marketing.marketing-display;
  }

  h1 em {
    color: var(--color-action);
    font-style: normal;
    white-space: nowrap;
  }

  .intro {
    max-inline-size: 54ch;
    margin-block-start: var(--space-5);
    color: var(--color-text-secondary);
    text-wrap: pretty;

    @include marketing.marketing-lead;
  }

  .hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    margin-block-start: var(--space-8);
  }

  .hero-reassurance,
  .organization-grid,
  .premise-grid,
  .steps {
    list-style: none;
  }

  .hero-reassurance {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2) var(--space-5);
    margin-block-start: var(--space-6);
    color: var(--color-text-muted);

    @include marketing.marketing-meta;
  }

  .hero-reassurance b {
    color: var(--color-text);
    font-weight: 500;
  }

  .hero-visual {
    margin-block-start: clamp(40px, 5vw, 64px);
  }

  /* ---------- premise ---------- */

  .premise {
    padding-block: clamp(48px, 6vw, 72px);
    border-block: 1px solid var(--color-border);
  }

  .premise-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-8);

    @include bp.at-least('md') {
      grid-template-columns: repeat(3, 1fr);
      gap: clamp(28px, 4vw, 56px);
    }
  }

  .premise-grid .number {
    color: var(--color-action);

    @include marketing.marketing-label;
  }

  .premise-grid h2 {
    margin-block-start: var(--space-3);

    @include marketing.marketing-h4;
  }

  .premise-grid p {
    margin-block-start: var(--space-2);
    color: var(--color-text-secondary);

    @include marketing.marketing-small;
  }

  /* ---------- steps ---------- */

  .steps {
    display: grid;
    grid-template-columns: 1fr;
    gap: clamp(32px, 4vw, 48px);
    margin-block-start: clamp(40px, 5vw, 64px);

    @include bp.at-least('md') {
      grid-template-columns: repeat(3, 1fr);
    }

    li {
      display: flex;
      flex-direction: column;
    }

    // The copy takes the slack, which lands the visual on the bottom edge of every
    // card without the page reaching into ImageSlot to say so. Grid stretches the
    // three cards to a shared height, so the visuals line up across the row.
    .step-copy {
      flex: 1;
    }
  }

  .step-node {
    display: flex;
    gap: var(--space-3);
    align-items: center;
  }

  .step-number {
    display: grid;
    place-items: center;
    inline-size: 26px;
    block-size: 26px;
    border-radius: 999px;
    background: var(--color-action);
    color: var(--color-on-action);
    font-family: var(--font-mono);

    @include type.type-chip;

    font-weight: 600;
  }

  .step-label {
    color: var(--color-text-muted);

    @include marketing.marketing-label;
  }

  .steps h3 {
    margin-block-start: var(--space-4);

    @include marketing.marketing-h4;
  }

  .steps p {
    margin-block: var(--space-2) var(--space-5);
    color: var(--color-text-secondary);

    @include marketing.marketing-small;
  }

  /* ---------- feature rows ---------- */

  .rows {
    display: flex;
    flex-direction: column;
    gap: clamp(48px, 7vw, 88px);
    margin-block-start: clamp(40px, 5vw, 64px);
  }

  .row {
    display: grid;
    grid-template-columns: 1fr;
    gap: clamp(24px, 4vw, 56px);
    align-items: center;

    @include bp.at-least('lg') {
      grid-template-columns: 1fr 1fr;
    }
  }

  .row.flip .row-copy {
    @include bp.at-least('lg') {
      order: 2;
    }
  }

  .row-label {
    color: var(--color-text-muted);

    @include marketing.marketing-label;
  }

  .row-copy h3 {
    margin-block-start: var(--space-3);
    max-inline-size: 22ch;
    text-wrap: balance;

    @include marketing.marketing-h3;
  }

  .row-copy p {
    max-inline-size: 52ch;
    margin-block-start: var(--space-4);
    color: var(--color-text-secondary);

    @include marketing.marketing-body;
  }

  /* ---------- organizations ---------- */

  .organizations {
    padding-block: clamp(64px, 9vw, 112px);
    border-block: 1px solid var(--color-border);
  }

  .organization-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-6);
    margin-block-start: clamp(32px, 4vw, 48px);

    @include bp.at-least('md') {
      grid-template-columns: repeat(2, 1fr);
    }

    @include bp.at-least('lg') {
      grid-template-columns: repeat(4, 1fr);
    }
  }

  .organization-grid h3 {
    margin-block-start: var(--space-4);

    @include marketing.marketing-h4;
  }

  .organization-grid p {
    margin-block-start: var(--space-2);
    color: var(--color-text-secondary);

    @include marketing.marketing-small;
  }

  .organization-actions {
    margin-block-start: clamp(32px, 4vw, 48px);
  }

  /* ---------- wall + pricing teaser ---------- */

  .wall-visual,
  .teasers {
    margin-block-start: clamp(40px, 5vw, 56px);
  }

  .teasers {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-4);

    @include bp.at-least('md') {
      grid-template-columns: 1fr 1fr;
    }
  }

  .teaser {
    padding: var(--space-6);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xl);
    background: var(--color-surface);
  }

  .audience {
    color: var(--color-text-muted);

    @include marketing.marketing-label;
  }

  .teaser dl {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    margin-block: var(--space-5);
  }

  .teaser dl > div {
    display: flex;
    gap: var(--space-4);
    align-items: baseline;
    justify-content: space-between;
    padding-block-end: var(--space-3);
    border-block-end: 1px solid var(--color-border-subtle);
  }

  .teaser dt {
    @include marketing.marketing-h4;
  }

  .teaser dt small {
    display: block;
    color: var(--color-text-secondary);

    @include marketing.marketing-small;
  }

  .teaser dd {
    flex-shrink: 0;

    @include marketing.marketing-meta;
  }

  .more {
    color: var(--color-action);
    font-weight: 500;
    text-decoration: none;

    @include marketing.marketing-small;
  }

  .teaser-note {
    max-inline-size: 68ch;
    margin-block-start: var(--space-6);
    color: var(--color-text-muted);

    @include marketing.marketing-small;
  }
</style>
