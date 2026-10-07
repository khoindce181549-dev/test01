import '@shared/fonts.css';
import '@shared/tokens.css';
import '@shared/base.css';
import * as Vue from 'vue';
import Quiz from './Quiz.vue';

const { createApp } = Vue;

// Pro's frontend bundle externalises `vue` and consumes this object so
// both bundles share one runtime. Set before `mountInstances` runs so any
// Pro template registration that fires synchronously during script eval
// can resolve its Vue imports.
window.Quizably = window.Quizably || {};
window.Quizably.vue = Vue;

// Inlined per-entry to avoid a shared Rollup chunk absorbing CSS-imports above.
// Keep in sync with src/admin/main.js.
const QUIZABLY_JS_API_VERSION = '1.0.0';

async function resolveData(root) {
  // Demo mode — load a fixture instead of the hydrated JSON. Lets `npm run dev`
  // or a static HTML page render a real quiz without WordPress. The fixtures
  // module is pulled in dynamically so it stays out of the production bundle
  // unless a `data-quizably-demo` attribute is actually present.
  const demoKey = root.dataset.quizablyDemo;
  if (demoKey) {
    try {
      const fixtures = await import('./fixtures/demo-quizzes.js');
      return fixtures[demoKey] ?? null;
    } catch (err) {
      console.error('[quizably] failed to load demo fixture', demoKey, err);
      return null;
    }
  }

  const uuid = root.dataset.quizUuid || '';
  const dataNode = document.getElementById(`quizably-data-${uuid}`);
  if (!dataNode) return null;

  try {
    return JSON.parse(dataNode.textContent);
  } catch (err) {
    console.error('[quizably] failed to parse hydrated data', err);
    return null;
  }
}

const mountInstances = async () => {
  const roots = document.querySelectorAll('.quizably-quiz-root');
  for (const root of roots) {
    if (root.dataset.quizablyMounted === '1') continue;

    const uuid = root.dataset.quizUuid || '';
    const data = await resolveData(root);

    // `.quizably-embed-wrap` is only ever rendered by QuizEmbedHandler.php's
    // standalone bare-page iframe endpoint — never by the shortcode, the
    // Gutenberg block, or the popup/slide-in embeds, all of which share this
    // same mount path and this same markup otherwise. Quiz.vue uses this to
    // decide whether it's allowed to demand the full viewport height.
    const isEmbed = Boolean(root.closest('.quizably-embed-wrap'));

    // Emitted by QuizShortcode when the block's Auto-start toggle / autostart="1" is on.
    const autoStart = root.dataset.autoStart === '1';

    const app = createApp(Quiz, { uuid, data, isEmbed, autoStart });
    app.mount(root);
    root.dataset.quizablyMounted = '1';
  }
};

// Admin and frontend both set this. Guard prevents silent clobber if
// bundles ever diverge; assert equality for early detection in dev.
if (window.Quizably.version && window.Quizably.version !== QUIZABLY_JS_API_VERSION) {
  console.error('[quizably] JS API version mismatch', window.Quizably.version, QUIZABLY_JS_API_VERSION);
}
window.Quizably.version = QUIZABLY_JS_API_VERSION;
window.Quizably.frontendHooks = window.Quizably.frontendHooks || {
  registerTemplate(key, component) {
    window.Quizably._templates = window.Quizably._templates || {};
    window.Quizably._templates[key] = component;
  },
  registerQuestionType(key, component) {
    window.Quizably._questionTypes = window.Quizably._questionTypes || {};
    window.Quizably._questionTypes[key] = component;
  },
};
// Idempotency: if `frontendHooks` was created before registerQuestionType
// existed (e.g. an older bundle ran first), graft it on without clobbering
// any registry the prior hooks object may have already accumulated.
if (typeof window.Quizably.frontendHooks.registerQuestionType !== 'function') {
  window.Quizably.frontendHooks.registerQuestionType = function (key, component) {
    window.Quizably._questionTypes = window.Quizably._questionTypes || {};
    window.Quizably._questionTypes[key] = component;
  };
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountInstances);
} else {
  mountInstances();
}
