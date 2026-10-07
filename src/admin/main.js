import '@shared/fonts.css';
import '@shared/tokens.css';
import '@shared/base.css';
import './rtl.css';
import * as Vue from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import { makeRouter } from './router';

const { createApp } = Vue;

// Expose Vue immediately so any sibling bundle that registers extensions
// at script-eval time (e.g. Pro's admin-pro.js) sees a populated runtime
// before its own top-level code runs. The Pro bundle externalises `vue`
// and resolves it to `window.Quizably.vue`; without this assignment, that
// import would crash before the LogicEditor ever registered.
window.Quizably = window.Quizably || {};
window.Quizably.vue = Vue;

// Inlined per-entry to avoid a shared Rollup chunk absorbing CSS-imports above.
// Keep in sync with src/frontend/main.js.
const QUIZABLY_JS_API_VERSION = '1.0.0';

const mount = () => {
  const el = document.getElementById('quizably-admin-app');
  if (!el) return;

  const app = createApp(App);
  app.use(createPinia());
  app.use(makeRouter());
  app.mount(el);

  // Admin and frontend both set this. Guard prevents silent clobber if
  // bundles ever diverge; assert equality for early detection in dev.
  if (window.Quizably.version && window.Quizably.version !== QUIZABLY_JS_API_VERSION) {
    console.error('[quizably] JS API version mismatch', window.Quizably.version, QUIZABLY_JS_API_VERSION);
  }
  window.Quizably.version = QUIZABLY_JS_API_VERSION;
  window.Quizably.adminHooks = window.Quizably.adminHooks || {};
  window.Quizably.adminHooks.onReady = window.Quizably.adminHooks.onReady || ((cb) => cb({}));
  // builderTab is the registry the Pro plugin uses to swap in real
  // editors for Pro-gated tabs (e.g. Logic). Each key holds a Vue
  // component; Free shows an upsell when the slot is empty. We merge
  // (not replace) so a Pro plugin that runs first still wins.
  window.Quizably.adminHooks.builderTab = window.Quizably.adminHooks.builderTab || {};
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount);
} else {
  mount();
}
