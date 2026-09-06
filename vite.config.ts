import { defineConfig, type Plugin, type ResolvedServerUrls, type ViteDevServer } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import inertia from '@inertiajs/vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

/*
 * Vite runs inside the DDEV container, so binding to the default 127.0.0.1 makes it
 * reachable only from inside that container. Laravel writes whatever address Vite
 * reports into public/hot, the @vite directive turns it into a script src, and the
 * browser on the host then asks its own 127.0.0.1 for assets nothing is serving there.
 *
 * Binding to every interface and reporting the DDEV URL fixes both halves. The values
 * come from the container's own environment rather than a hardcoded hostname, so the
 * config keeps working if the project is renamed. Outside DDEV, they are absent and Vite
 * falls back to its own defaults.
 */
const VITE_PORT = 5173;
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
 * Everything needed to run Vite inside the DDEV container and have a browser on the
 * host reach it. Off DDEV the options are undefined and every hook returns early, so
 * Vite keeps its own defaults.
 *
 * Two separate problems, which is why there are two hooks:
 *
 * `config` covers the ordinary case. Vite binds inside the container, so it must listen
 * on every interface and report the routed DDEV URL rather than the address it bound
 * to, or Laravel writes that address into public/hot and the browser asks its own
 * machine for assets. One server answers four hostnames, hence the CORS pattern.
 *
 * `configureServer` covers @inertiajs/vite, which builds its SSR stylesheet links from
 * `server.resolvedUrls.local[0]` and never reads `server.origin` (dist/index.js,
 * resolveDevServerOrigin). Setting origin alone leaves every SSR-injected <link>
 * pointing at localhost while Laravel's own tags are correct, which is a confusing
 * half-broken state. Vite assigns resolvedUrls during listen, after any 'listening'
 * handler could run, so the value is intercepted rather than assigned. Only `local` is
 * rewritten; the real network URLs are passed through.
 */
function ddevDevServer({ url, host, port }: DdevServerOptions): Plugin {
  const origin = url ? `${url.replace(/\/$/, '')}:${port}` : undefined;

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
          // Fail loudly on a taken port. Moving to 5174 would put an address in
          // public/hot that DDEV does not route.
          strictPort: true,
          origin,
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
      /*
       * Only the apex is server-rendered, so there is one SSR entry rather than one
       * per bundle. Host is pinned to loopback on purpose: the renderer should never
       * be reachable from off the machine, and we would rather say so than rely on a
       * default staying put.
       */
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

  // Everything DDEV-specific moved into the plugin above. What is left applies wherever
  // this runs.
  server: {
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
  },
});
