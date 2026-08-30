import js from '@eslint/js';
import type { Linter } from 'eslint';
import ts from 'typescript-eslint';
import svelte from 'eslint-plugin-svelte';
import prettier from 'eslint-config-prettier';
import globals from 'globals';
import svelteConfig from './svelte.config.js';

export default [
  js.configs.recommended,
  ...ts.configs.recommended,
  ...svelte.configs.recommended,
  prettier,
  ...svelte.configs.prettier,
  {
    languageOptions: {
      globals: { ...globals.browser, ...globals.node },
    },
  },
  {
    files: ['**/*.svelte', '**/*.svelte.ts', '**/*.svelte.js'],
    languageOptions: {
      parserOptions: {
        parser: ts.parser,
        extraFileExtensions: ['.svelte'],
        svelteConfig,
      },
    },
  },
  {
    // The SSR process imports a module once and keeps it around for every render,
    // unlike PHP-FPM which starts fresh each request. So a mutable binding at module
    // scope is shared by everyone, and one visitor's data can end up on another
    // visitor's page. prefer-const handles the rest, so anything that reaches this rule
    // is actually reassigned, which is the case we care about.
    //
    // This cannot see inside `<script module>` in a component. The Svelte parser does
    // not expose those as top-level declarations, so that case is a review thing and is
    // written down in .ai/rules.
    files: ['resources/ts/**/*.ts'],
    rules: {
      'no-restricted-syntax': [
        'error',
        {
          selector: 'Program > VariableDeclaration[kind="let"]',
          message:
            'Mutable module-scope state leaks between users under SSR. Keep request-scoped values inside a component or pass them as props.',
        },
      ],
    },
  },
  {
    ignores: [
      'vendor/',
      'node_modules/',
      'public/',
      'storage/',
      'bootstrap/',
      'design/',
      'docs/',
      'bruno/',
      '.ddev/',
      'resources/views/',
    ],
  },
] satisfies Linter.Config[];
