import { defineConfig, type Plugin, type ResolvedServerUrls, type ViteDevServer } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import inertia from '@inertiajs/vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

const VITE_PORT = 5173;

// ddevUrl and ddevHost are read from the container's own environment rather than
// hardcoded, so this config keeps working if the project is renamed. Outside DDEV both
// are undefined, and Vite falls back to its own defaults.
const ddevUrl = process.env.DDEV_PRIMARY_URL_WITHOUT_PORT;
const ddevHost = process.env.DDEV_HOSTNAME?.split(',')[0];

interface DdevServerOptions {
  /** DDEV's primary URL with no port, e.g. https://syoksheet.ddev.site. Absent off DDEV. */
  url: string | undefined;
  /** The first hostname DDEV serves. The HMR socket dials this. */
  host: string | undefined;
  port: number;
}

/*
 * Make Vite reachable from a browser on the host while it runs inside the container.
 *
 * Vite binds inside the container, so it has to listen on every interface and report
 * the DDEV URL rather than the address it actually bound to. Laravel writes whatever
 * Vite reports into public/hot, and the browser would otherwise ask its own machine
 * for assets that nothing there is serving.
 *
 * configureServer is separate because @inertiajs/vite builds its server-rendered
 * stylesheet links from the resolved local URL and never looks at the origin we set.
 * Vite fills that value in during listen, after any listening handler could run, so we
 * intercept the property instead of assigning to it. Only the local URL is rewritten,
 * and the real network URLs pass through untouched.
 *
 * Off DDEV there is no URL, and every hook here returns early.
 */
function ddevDevServer({ url, host, port }: DdevServerOptions): Plugin {
  const origin = url ? `${url.replace(/\/$/, '')}:${port}` : undefined;

  // Vite's Plugin type extends Rolldown's, and Rolldown bundles its declarations in a
  // way PhpStorm cannot follow. PhpStorm therefore rejects every plugin object, even
  // one with nothing but a name. tsc accepts them all.
  //
  // The annotation stays because it is what makes tsc catch a misspelled hook name. A
  // hook spelled wrong would leave this plugin loaded and silently doing nothing.
  // noinspection TypeScriptValidateTypes
  return {
    name: 'syoksheet:ddev-dev-server',
    apply: 'serve',

    config() {
      if (!origin) {
        return;
      }

      return {
        server: {
          host: '0.0.0.0',
          port,
          // Fail loudly if the port is taken. If Vite quietly moved to 5174, it
          // would write an address into public/hot that DDEV does not route.
          strictPort: true,
          origin,
          // One dev server answers all four hostnames, so the allowed origin has to
          // match any DDEV subdomain.
          cors: { origin: /^https?:\/\/([a-z0-9-]+\.)*ddev\.site(:\d+)?$/ },
          ws: host ? { protocol: 'wss', host, clientPort: port } : undefined,
        },
      };
    },

    configureServer(server: ViteDevServer) {
      if (!origin) {
        return;
      }

      let resolved: ResolvedServerUrls | null = null;

      Object.defineProperty(server, 'resolvedUrls', {
        configurable: true,
        get: (): ResolvedServerUrls | null => resolved && { ...resolved, local: [`${origin}/`] },
        set: (value: ResolvedServerUrls | null) => {
          resolved = value;
        },
      });
    },
  };
}

export default defineConfig({
  plugins: [
    ddevDevServer({ url: ddevUrl, host: ddevHost, port: VITE_PORT }),
    laravel({
      input: [
        'resources/scss/app.scss',
        'resources/ts/domains/app/entry.ts',
        'resources/ts/domains/admin/entry.ts',
        'resources/ts/domains/public/entry.ts',
      ],
      refresh: true,
    }),
    svelte(),
    inertia({
      // There is one SSR entry rather than one per bundle, because only the apex is
      // server-rendered. The host is pinned to loopback so that the renderer is never
      // reachable from outside this machine.
      ssr: {
        entry: 'resources/ts/domains/public/ssr.ts',
        host: '127.0.0.1',
        port: 13714,
      },
    }),
  ],

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

  // Everything specific to DDEV lives in the plugin above. What is left here applies
  // wherever this runs.
  server: {
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
  },
});
