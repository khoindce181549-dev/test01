import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import legacy from '@vitejs/plugin-legacy';
import { resolve } from 'node:path';

// Map of @wordpress/* package names → WP global used in the browser.
// Keep this list aligned with the imports in src/block-editor/main.js.
const WP_EXTERNALS = {
  '@wordpress/blocks': 'wp.blocks',
  '@wordpress/element': 'wp.element',
  '@wordpress/block-editor': 'wp.blockEditor',
  '@wordpress/components': 'wp.components',
  '@wordpress/i18n': 'wp.i18n',
  '@wordpress/api-fetch': 'wp.apiFetch',
};

// Keep the admin app's own code OUT of the entry file (admin.js).
//
// Left alone, Rollup puts everything the admin entry imports into admin.js and
// the lazy views import it back as "../admin.js". That URL has no hash and no
// version, while WordPress loads the entry as admin.js?ver=<mtime>, so:
//   - the browser fetches and caches a SECOND, unversioned copy, which after a
//     rebuild or plugin update can be an older build - the lazy view then fails
//     with "does not provide an export named 'p'" (minified names change per build);
//   - the entry mounts the app when it runs, so that second copy mounts it twice.
// Moving those modules into a content-hashed chunk makes every import a hashed
// URL, and admin.js becomes a thin entry that only imports it.
//
// Only modules reachable through static imports from the admin entry, and from no
// other entry, go there: code shared with the frontend bundle (Vue) keeps its own
// shared chunk, and lazy views stay lazy.
let entryIds = null;
const reachableFrom = new Map();

function staticallyReachable(entryId, getModuleInfo) {
  let seen = reachableFrom.get(entryId);
  if (seen) return seen;
  seen = new Set();
  const stack = [entryId];
  while (stack.length) {
    const id = stack.pop();
    if (seen.has(id)) continue;
    seen.add(id);
    stack.push(...(getModuleInfo(id)?.importedIds ?? []));
  }
  reachableFrom.set(entryId, seen);
  return seen;
}

function adminCoreChunk(id, { getModuleInfo, getModuleIds }) {
  entryIds ??= [...getModuleIds()].filter((m) => getModuleInfo(m)?.isEntry);
  const adminEntry = entryIds.find((m) => /[\\/]src[\\/]admin[\\/]main\.js$/.test(m));
  // The entry module itself must stay out: a chunk that contains an entry module
  // BECOMES the entry file, which is exactly the "../admin.js" import to avoid.
  if (!adminEntry || id === adminEntry || !staticallyReachable(adminEntry, getModuleInfo).has(id)) return undefined;
  const usedElsewhere = entryIds.some((m) => m !== adminEntry && staticallyReachable(m, getModuleInfo).has(id));
  // Name the shared group too. Left to Rollup, it folds it into admin-core, and the
  // frontend bundle (every public quiz page) would then import the whole admin app.
  return usedElsewhere ? 'shared-runtime' : 'admin-core';
}

export default defineConfig({
  base: './',
  plugins: [
    vue(),
    legacy({
      targets: ['defaults', 'not IE 11'],
      renderLegacyChunks: false,
      modernPolyfills: true,
    }),
  ],
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    manifest: false,
    // All CSS collapses into a single `styles.css` so both admin and frontend
    // can enqueue one predictable stylesheet. Avoids shared-chunk CSS attribution
    // (previously tokens/fonts/base got attributed to `_plugin-vue_export-helper.css`
    // because both entries share Vue's runtime helper).
    cssCodeSplit: false,
    rollupOptions: {
      // @wordpress/* packages are provided by WP core as browser globals
      // (window.wp.*). Marking them external means the editor bundle only
      // contains our own code — WordPress supplies the runtime.
      external: Object.keys(WP_EXTERNALS),
      input: {
        admin: resolve(__dirname, 'src/admin/main.js'),
        frontend: resolve(__dirname, 'src/frontend/main.js'),
        'block-editor': resolve(__dirname, 'src/block-editor/main.js'),
      },
      output: {
        manualChunks: adminCoreChunk,
        entryFileNames: '[name].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            // Single combined stylesheet → always `styles.css`.
            return 'styles.css';
          }
          return 'assets/[name]-[hash][extname]';
        },
        globals: WP_EXTERNALS,
      },
    },
  },
  resolve: {
    alias: {
      '@admin': resolve(__dirname, 'src/admin'),
      '@frontend': resolve(__dirname, 'src/frontend'),
      '@shared': resolve(__dirname, 'src/shared'),
    },
  },
});
