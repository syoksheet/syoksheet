import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import inertia from '@inertiajs/vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/scss/app.scss',
        'resources/ts/app.ts',
        'resources/ts/admin.ts',
        'resources/ts/public.ts',
      ],
      refresh: true,
    }),
    svelte(),
    inertia({
      /*
       * Only the apex is server-rendered, so there is one SSR entry rather than one
       * per bundle. Host is pinned to loopback on purpose: the renderer should never
       * be reachable from off the machine, and we would rather say so than rely on a
       * default staying put.
       */
      ssr: {
        entry: 'resources/ts/ssr.ts',
        host: '127.0.0.1',
        port: 13714,
      },
    }),
  ],

  /*
   * Put the token layer on the Sass load path so a component can `@use 'breakpoints'`
   * without walking back up the tree. Only breakpoints are ever imported this way:
   * colors, spacing and type are custom properties, which need no import at all.
   */
  css: {
    preprocessorOptions: {
      scss: {
        loadPaths: [path.resolve(import.meta.dirname, 'resources/scss')],
      },
    },
  },

  resolve: {
    alias: {
      $components: path.resolve(import.meta.dirname, 'resources/ts/components'),
      '@': path.resolve(import.meta.dirname, 'resources/ts'),
    },
  },

  /*
   * Bundle everything into the SSR output so the server needs no node_modules. Node is
   * there as a runtime only, and we never install anything on it.
   */
  ssr: {
    noExternal: true,
  },

  server: {
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
  },
});
