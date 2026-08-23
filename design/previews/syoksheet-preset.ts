/**
 * syoksheet · PrimeNG v21 theme preset
 * ------------------------------------------------------------------
 * Maps the syoksheet design system onto PrimeNG's token-based theming
 * (@primeng/themes). Drop this into an Angular app and register it:
 *
 *   import { providePrimeNG } from 'primeng/config';
 *   import Aura from '@primeng/themes/aura';
 *   import { SyokSheetPreset } from './syoksheet-preset';
 *
 *   providePrimeNG({
 *     theme: {
 *       preset: SyokSheetPreset,
 *       options: { darkModeSelector: '.dark', cssLayer: false }
 *     }
 *   })
 *
 * Fonts: load Geist + Geist Mono globally and set
 *   :root { --p-font-family: 'Geist', system-ui, sans-serif; }
 *   .mono, code, .p-* [data-mono] { font-family: 'Geist Mono', monospace; }
 *
 * Brand rule: `primary` = deep teal #004040 (action / identity).
 * Verification forest green is a SEPARATE custom token group (`verified`)
 * because PrimeNG has no "verified" concept — never reuse primary for it.
 */
import { definePreset } from '@primeng/themes';
import Aura from '@primeng/themes/aura';

export const SyokSheetPreset = definePreset(Aura, {
  primitive: {
    borderRadius: {
      none: '0',
      xs: '5px',     // chips / inputs-in-group
      sm: '5px',
      md: '8px',     // buttons, inputs  (syoksheet --radius)
      lg: '12px',    // cards            (syoksheet --radius-lg)
      xl: '18px',    // hero panels      (syoksheet --radius-xl)
    },

    /* Deep-teal brand ramp (#004040 = 600) */
    teal: {
      50:  '#e9f3f2', 100: '#c9e4e2', 200: '#93c7c5', 300: '#5fa9a6',
      400: '#2c8784', 500: '#0d6360', 600: '#004040', 700: '#003131',
      800: '#002828', 900: '#001f1f', 950: '#001414',
    },

    /* Forest "verified" ramp — semantic, verification only */
    forest: {
      50:  '#ecf4ef', 100: '#d0e7da', 200: '#a6d3b9', 300: '#6fbb92',
      400: '#3fa074', 500: '#1f8a64', 600: '#157a5b', 700: '#0f5e46',
      800: '#0b4634', 900: '#08382a', 950: '#04231a',
    },

    /* Amber (pending) + Clay (destructive) */
    amber: {
      50:  '#fbf3e3', 100: '#f4e4be', 200: '#ecd08a', 300: '#e0b455',
      400: '#d39a2a', 500: '#b07815', 600: '#8a5e12', 700: '#6f4b10',
      800: '#5b3e11', 900: '#4d3411', 950: '#2c1d08',
    },
    clay: {
      50:  '#fbefea', 100: '#f4d8cd', 200: '#eab8a4', 300: '#dd9072',
      400: '#cb5638', 500: '#b2412a', 600: '#8f3120', 700: '#74281b',
      800: '#5f231a', 900: '#511f19', 950: '#2c0e0a',
    },

    /* Pure neutral (white → near-black) — syoksheet has no grey tint */
    neutral: {
      0: '#ffffff', 50: '#fafafa', 100: '#f4f4f5', 200: '#e5e5e8',
      300: '#d4d4d8', 400: '#a1a1aa', 500: '#71717a', 600: '#52525b',
      700: '#3f3f46', 800: '#27272a', 900: '#161618', 950: '#0a0a0b',
    },
  },

  semantic: {
    primary: {
      50:  '{teal.50}',  100: '{teal.100}', 200: '{teal.200}', 300: '{teal.300}',
      400: '{teal.400}', 500: '{teal.500}', 600: '{teal.600}', 700: '{teal.700}',
      800: '{teal.800}', 900: '{teal.900}', 950: '{teal.950}',
    },

    /* syoksheet status semantics mapped onto PrimeNG's named groups */
    // forest → success-equivalent BUT reserved for verification UI
    transitionDuration: '0.14s',
    focusRing: {
      width: '2px',
      style: 'solid',
      color: '{primary.500}',
      offset: '2px',
      shadow: 'none',
    },
    formField: {
      paddingX: '0.75rem',
      paddingY: '0.5rem',
      borderRadius: '{borderRadius.md}',
      focusRing: { width: '3px', style: 'solid', color: 'rgba(0,64,64,0.22)', offset: '0' },
    },

    colorScheme: {
      light: {
        surface: {
          0:  '#ffffff', 50: '{neutral.50}', 100: '{neutral.100}', 200: '{neutral.200}',
          300: '{neutral.300}', 400: '{neutral.400}', 500: '{neutral.500}',
          600: '{neutral.600}', 700: '{neutral.700}', 800: '{neutral.800}',
          900: '{neutral.900}', 950: '{neutral.950}',
        },
        primary: {
          color: '{teal.600}', contrastColor: '#ffffff',
          hoverColor: '{teal.700}', activeColor: '{teal.700}',
        },
        text: { color: '#141416', mutedColor: '{neutral.600}' },
        content: { background: '#ffffff', borderColor: '{neutral.200}' },
      },
      dark: {
        // brand lifts to a brighter teal so it reads on near-black
        primary: {
          color: '#2bb0a7', contrastColor: '#062a2a',
          hoverColor: '#48c6bd', activeColor: '#48c6bd',
        },
        surface: {
          0:  '#0b0c0c', 50: '#141616', 100: '#1a1d1d', 200: '#2a2e2d',
          300: '#3a3f3e', 400: '#6b716f', 500: '#8a908e', 600: '#aab0ae',
          700: '#c6ccc9', 800: '#e2e6e3', 900: '#eef0ed', 950: '#f6f7f4',
        },
        text: { color: '#f3f4f1', mutedColor: '#aab0ae' },
        content: { background: '#141616', borderColor: '#2a2e2d' },
      },
    },
  },

  components: {
    /* Tag/Badge — reuse for verified/pending/unverified via severity.
       The forest "verified" severity is applied with a custom CSS class
       (.p-tag-verified) since PrimeNG severities don't include it. */
    tag: {
      borderRadius: '999px',
      fontWeight: '600',
    },
    button: {
      borderRadius: '{borderRadius.md}',
      paddingX: '1rem',
      gap: '0.5rem',
    },
    card: {
      borderRadius: '{borderRadius.lg}',
      // header / body / footer structure matches syoksheet card spec
    },
    dialog: {
      borderRadius: '{borderRadius.lg}',
    },
    toast: {
      borderRadius: '{borderRadius.md}',
    },
  },
});

/**
 * Custom tokens PrimeNG doesn't model — apply via global CSS, not the preset:
 *
 *   :root {
 *     --ss-verified-50:  #ecf4ef;  --ss-verified-100: #d0e7da;
 *     --ss-verified-600: #157a5b;  --ss-verified-700: #0f5e46;
 *   }
 *   .p-tag.p-tag-verified {
 *     background: var(--ss-verified-50);
 *     color: var(--ss-verified-700);
 *     border: 1px solid var(--ss-verified-100);
 *   }
 *
 * Use .p-tag-verified ONLY for the verification mark / "Verified by …".
 * Pending → severity="warn" (amber). Destructive → severity="danger" (clay).
 */
