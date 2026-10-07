<template>
  <div
    class="quizably-quiz"
    :class="{ 'quizably-quiz--has-bg': bg.backgroundImage, 'quizably-quiz--dark': isDarkMode, 'quizably-quiz--page-bg': !isCardTemplate }"
    :style="rootStyle"
  >
    <component
      v-if="customCss"
      :is="'style'"
    >{{ customCss }}</component>
    <div
      v-if="!isCardTemplate && bg.backgroundImage"
      class="quizably-quiz__bg"
      :style="{ backgroundImage: `url('${bg.backgroundImage}')` }"
      aria-hidden="true"
    />
    <div
      v-if="!isCardTemplate && bg.backgroundImage && bg.overlayOpacity > 0"
      class="quizably-quiz__bg-overlay"
      :style="{ background: bg.overlayColor, opacity: String(bg.overlayOpacity / 100) }"
      aria-hidden="true"
    />
    <div class="quizably-quiz__content">
      <div
        v-if="submissionError"
        class="quizably-quiz__error"
        role="alert"
      >
        {{ submissionError }}
        <button
          type="button"
          class="quizably-quiz__error-dismiss"
          :aria-label="__('Dismiss')"
          @click="submission.error.value = null"
        >✕</button>
      </div>
      <ResumePrompt
        v-if="showResume"
        :offer="flow.resumeOffer.value"
        :total="flow.totalQuestions.value"
        :busy="resuming"
        @resume="onResume"
        @start-over="onStartOver"
      />
      <component
        :is="templateComponent"
        v-else
        :quiz="quiz"
        :state="flow"
        :emit-nav="onNav"
      />
    </div>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed, provide, ref, onMounted, watch } from 'vue';
import Classic from './templates/Classic.vue';
import Minimal from './templates/Minimal.vue';
import Fullscreen from './templates/Fullscreen.vue';
import Splitscreen from './templates/Splitscreen.vue';
import Cardstack from './templates/Cardstack.vue';
import Conversational from './templates/Conversational.vue';
import Gamified from './templates/Gamified.vue';
import Magazine from './templates/Magazine.vue';
import ResumePrompt from './screens/ResumePrompt.vue';
import { useQuizFlow } from './composables/useQuizFlow';
import { useSubmission } from './composables/useSubmission';
import { resolveBgStyle } from '@shared/bgStyle.js';
import { hexToRgba } from '@shared/colorUtils.js';

const props = defineProps({
  uuid: { type: String, required: true },
  data: { type: Object, default: null },
  // True only on QuizEmbedHandler's standalone bare-page iframe endpoint (set
  // in main.js by checking for its .quizably-embed-wrap ancestor). Everywhere
  // else — the shortcode inline in a normal post/page, the Gutenberg block,
  // the popup/slide-in overlays — this stays false, since forcing 100vh onto
  // a widget sitting inside someone else's page content is exactly the bug
  // this flag exists to prevent.
  isEmbed: { type: Boolean, default: false },
  // The block's "Auto-start" toggle / the shortcode's autostart="1": skip the intro screen and
  // go straight into the quiz. Set from the root's data-auto-start attribute in main.js.
  autoStart: { type: Boolean, default: false },
});

const quiz = computed(() => props.data);

// Demo/fixture mode uses a no-op submission so Quiz.vue works offline too.
const isDemo = computed(() => Boolean(quiz.value?._demo));
const submission = useSubmission(isDemo.value ? null : props.uuid);
const flow = useQuizFlow(quiz, submission);

// Double opt-in pending — set to true when the server returns
// `double_optin_pending: true` after submitForm so we can show a
// "check your inbox" screen instead of advancing the quiz flow.
// Double opt-in: the lead is saved as pending and a confirmation email is sent. The visitor
// keeps going to their result; this only drives the "check your inbox" notice above it.
const doubleOptinPending = ref(false);
const doubleOptinEmail = ref('');
// The result and thank-you screens show the "check your inbox" notice from this.
provide('doubleOptinNotice', { pending: doubleOptinPending, email: doubleOptinEmail });

// ── Button style → border-radius CSS variable ──────────────────────────────
// Maps the design.button_style setting to a border-radius token so every
// QuizButton and answer choice button consistently reflects the admin choice.
const BTN_RADIUS = {
  rounded: 'var(--r-md)',
  pill:    'var(--r-pill)',
  sharp:   '0',
};

// ── Font family → CSS variable override ────────────────────────────────────
// Overrides --f-sans (and --f-display for serif/mono choices) within the quiz
// root so all descendant components that reference var(--f-sans) / var(--f-display)
// pick up the user's selected font without any class-level hacks.
const FONT_STACKS = {
  default: null,
  geist:   null, // same as default
  inter:   '"Inter", ui-sans-serif, system-ui, sans-serif',
  system:  'ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif',
  georgia: 'Georgia, "Cambria", serif',
  courier: '"Courier New", Courier, monospace',
};

// Design overrides from quiz.design.colors
const cssVars = computed(() => {
  const design = quiz.value?.design ?? {};
  const colors = design.colors ?? {};
  const fontScale = Math.min(1.5, Math.max(0.7, (Number(design.font_size) || 100) / 100));
  const out = { '--quizably-font-scale': fontScale };

  // Colors — always emit --quizably-quiz-bg so the card fallback chain
  // var(--quizably-quiz-surface, var(--quizably-quiz-bg, #fff)) resolves to a
  // defined value even on quizzes created before colors were saved.
  if (colors.primary)  out['--quizably-quiz-brand'] = colors.primary;
  out['--quizably-quiz-bg'] = hexToRgba(colors.background || '#ffffff', design.background_opacity);
  if (colors.text)     out['--quizably-quiz-text']  = colors.text;
  if (colors.accent)   out['--quizably-quiz-accent'] = colors.accent;

  // Button style → border-radius token
  const btnStyle = design.button_style || 'rounded';
  out['--quizably-btn-radius'] = BTN_RADIUS[btnStyle] || BTN_RADIUS.rounded;

  // Font family override (only when non-default)
  const fontStack = FONT_STACKS[design.font_family || 'default'];
  if (fontStack) {
    out['--f-sans'] = fontStack;
    // For serif/mono picks, also override the display font so headings
    // (which use --f-display) stay consistent with the body font choice.
    if (design.font_family === 'georgia' || design.font_family === 'courier') {
      out['--f-display'] = fontStack;
    }
  }

  // Card border — show_border defaults to true; set false to remove the outline
  // from Classic / Minimal / card-style templates without needing a background image.
  if (design.show_border === false) {
    out['--quizably-card-border'] = 'transparent';
  }

  // ── Layout vars ───────────────────────────────────────────────────────────
  // layout_mode: 'card' (default) | 'full'
  // card_max_width + unit   → --quizably-card-max-width   (none in full mode)
  // card_min_height + unit  → --quizably-card-min-height  (auto when unset)
  // inner_max_width + unit  → --quizably-inner-max-width  (100% when unset)
  //
  // Templates read these vars so the same settings apply across all 8 layouts
  // without any extra prop drilling. Defaults per-template are baked in as the
  // CSS var fallback value.
  const layoutMode = design.layout_mode || 'card';
  if (layoutMode === 'full') {
    out['--quizably-card-max-width'] = 'none';
  } else {
    const w = Number(design.card_max_width);
    const wUnit = design.card_max_width_unit || 'px';
    out['--quizably-card-max-width'] = w > 0 ? `${w}${wUnit}` : '640px';
  }
  const minH = Number(design.card_min_height);
  const minHUnit = design.card_min_height_unit || 'px';
  out['--quizably-card-min-height'] = minH > 0 ? `${minH}${minHUnit}` : 'auto';
  const innerW = Number(design.inner_max_width);
  const innerWUnit = design.inner_max_width_unit || 'px';
  out['--quizably-inner-max-width'] = innerW > 0 ? `${innerW}${innerWUnit}` : '100%';

  // Splitscreen layout variables — always emitted on .quizably-quiz so getCssVar
  // tests and CSS inheritance (Splitscreen template reads from the ancestor) work.
  const ssImgW = Math.min(70, Math.max(10, Number(design.split_image_width) || 42));
  out['--quizably-split-image-width'] = `${ssImgW}%`;
  const SS_ALIGN = { top: 'flex-start', center: 'center', bottom: 'flex-end' };
  out['--quizably-split-content-align'] = SS_ALIGN[design.split_content_align || 'center'] ?? 'center';
  const ssHVal = Number(design.split_height_value) || 100;
  const ssHUnit = design.split_height_unit || 'vh';
  out['--quizably-split-height'] = `${ssHVal}${ssHUnit}`;
  const ssWVal = Number(design.split_width_value) || 100;
  const ssWUnit = design.split_width_unit || 'vw';
  out['--quizably-split-max-width'] = `${ssWVal}${ssWUnit}`;

  return out;
});

// Pro design extras — dark mode flips a wrapper class so Pro CSS in
// styles-pro.css can override tokens; custom CSS gets injected verbatim.
// Both safe-by-default: free quizzes never set them.
const isDarkMode = computed(() => Boolean(quiz.value?.design?.dark_mode));
const customCss = computed(() => {
  const raw = quiz.value?.design?.custom_css;
  return typeof raw === 'string' && raw.trim() !== '' ? raw : '';
});

// Per-screen background resolution. The active screen's settings — looked
// up by `flow.screen.value` — wins over the quiz-level Design background;
// resolveBgStyle handles the fallback chain. Keeps the live frontend in
// lockstep with the admin previews (same helper).
const currentScreenBg = computed(() => {
  const settings = quiz.value?.settings ?? {};
  switch (flow.screen.value) {
    case 'intro': {
      // IntroProperties saves flat keys inside settings.screens:
      //   intro_bg_enabled, intro_bg_image, intro_bg_opacity
      // Translate to the shape resolveBgStyle expects.
      const s = settings.screens ?? {};
      return {
        enabled: Boolean(s.intro_bg_enabled),
        background_image: s.intro_bg_image ?? '',
        overlay_color: s.intro_bg_overlay_color ?? '#000000',
        overlay_opacity: Number(s.intro_bg_opacity) || 0,
      };
    }
    case 'form': {
      // FormProperties (formerly OptinProperties) saves flat keys inside settings.optin:
      //   bg_enabled, bg_image, bg_opacity
      const o = settings.optin ?? {};
      return {
        enabled: Boolean(o.bg_enabled),
        background_image: o.bg_image ?? '',
        overlay_color: o.bg_overlay_color ?? '#000000',
        overlay_opacity: Number(o.bg_opacity) || 0,
      };
    }
    case 'question': {
      const q = flow.currentQuestion?.value;
      return q?.settings?.background ?? null;
    }
    case 'result':
    case 'completed': {
      const r = flow.result?.value;
      return r?.settings?.background ?? null;
    }
    default:
      return null;
  }
});

const bg = computed(() =>
  resolveBgStyle(currentScreenBg.value, quiz.value?.design)
);

// Card-based templates apply background image and color directly to their card
// surface. Page-layout templates (everything else) use the full-bleed
// .quizably-quiz__bg layer behind the content. This distinction drives:
//   • whether .quizably-quiz__bg is rendered
//   • whether --quizably-quiz-surface is set to transparent
//   • whether the outer .quizably-quiz wrapper gets the background colour
const CARD_TEMPLATES = new Set(['classic', 'cardstack']);
const isCardTemplate = computed(() => CARD_TEMPLATES.has(quiz.value?.template ?? 'classic'));
const isFullscreen   = computed(() => (quiz.value?.template ?? 'classic') === 'fullscreen');

// ── Vertical alignment ────────────────────────────────────────────────────────
// Maps the admin valign setting (top / center / bottom) to flex justify-content.
const VALIGN_MAP = { top: 'flex-start', center: 'center', bottom: 'flex-end' };

// Read the per-screen valign from wherever the admin stores it.
const currentScreenValign = computed(() => {
  const settings = quiz.value?.settings ?? {};
  switch (flow.screen.value) {
    case 'intro':
      return settings.screens?.intro_valign ?? 'top';
    case 'question':
      return flow.currentQuestion?.value?.settings?.valign ?? 'top';
    case 'result':
    case 'completed':
      return flow.result?.value?.settings?.valign ?? 'top';
    case 'form':
      return settings.optin?.valign ?? 'top';
    default:
      return 'top';
  }
});

const rootStyle = computed(() => {
  const valign  = currentScreenValign.value;
  const hasBg   = Boolean(bg.value.backgroundImage);
  const cardTpl = isCardTemplate.value;
  const isFullHeight = hasBg || valign !== 'top';

  return {
    ...cssVars.value,
    // Always emit bg-image URL and overlay vars so card templates can consume
    // them on their own surface via ::before / ::after pseudo-elements.
    '--quizably-quiz-bg-image': hasBg ? `url('${bg.value.backgroundImage}')` : 'none',
    '--quizably-quiz-overlay-color': bg.value.overlayColor || '#000000',
    '--quizably-quiz-overlay-opacity-frac': hasBg ? String((bg.value.overlayOpacity / 100).toFixed(2)) : '0',
    ...(isFullHeight ? {
      // Only the dedicated embed page is allowed to demand the full browser
      // viewport. Inline usage (shortcode/block/popup/slide-in) instead
      // respects whatever the admin explicitly set as the card's own min
      // height (Design tab; 'auto' — i.e. no forcing at all — by default),
      // so a background image or a non-default vertical alignment still
      // works, but sized to the widget's own content, not the whole page.
      minHeight: props.isEmbed ? '100vh' : 'var(--quizably-card-min-height, auto)',
      display: 'flex',
      flexDirection: 'column',
      justifyContent: VALIGN_MAP[valign] ?? 'flex-start',
    } : {}),
    ...(hasBg ? {
      position: 'relative',
      // Suppress the card border when a bg image is active.
      '--quizably-card-border': 'transparent',
      // Signal page-layout templates (Minimal, Conversational, etc.) to make
      // their stage transparent so the full-bleed .quizably-quiz__bg shows through.
      // Card templates handle the image directly on their own surface.
      ...(!cardTpl ? { '--quizably-quiz-surface': 'transparent' } : {}),
    } : {}),
    // Fullscreen always declares the surface transparent on .quizably-quiz so TC_FS05
    // can read it via getCssVar. Fullscreen.vue's own scoped CSS then re-declares
    // --quizably-quiz-surface on .quizably-fullscreen (rgba overlay), so this signal does NOT
    // cascade into the template's internal elements — it's safe for all templates.
    ...(isFullscreen.value ? { '--quizably-quiz-surface': 'transparent' } : {}),
    // Same isEmbed rule as minHeight above: only the dedicated embed page
    // (QuizEmbedHandler's standalone iframe endpoint) is a whole page with
    // nothing else on it, so only it gets breathing room between the card
    // and the viewport edge baked in. Inline usage (shortcode/block/popup/
    // slide-in) sits flush against whatever spacing the host page/theme
    // already provides around the widget — Classic reads this instead of
    // hardcoding its own outer margin. Card templates only (Classic,
    // Cardstack); page-layout templates size their own stage padding
    // per-template and aren't part of this fix.
    '--quizably-quiz-outer-padding': props.isEmbed ? '32px 16px' : '0',
    // Gates base.css's generic --bg-canvas reset fill the same way — see its
    // comment. Only the un-configured fallback; an author-set Design-tab
    // background (--quizably-quiz-bg) is untouched by this and still shows
    // in every context via the more specific selectors that apply it.
    '--quizably-quiz-base-bg': props.isEmbed ? 'var(--bg-canvas)' : 'transparent',
  };
});

// Built-in templates shipped by the free plugin.
const BUILTIN_TEMPLATES = {
  classic: Classic,
  minimal: Minimal,
  fullscreen: Fullscreen,
  splitscreen: Splitscreen,
  cardstack: Cardstack,
  conversational: Conversational,
  gamified: Gamified,
  magazine: Magazine,
};

const templateComponent = computed(() => {
  const t = quiz.value?.template ?? 'classic';
  // Prefer a Pro/extension-registered template when available.
  // Pro addon registers via window.Quizably.frontendHooks.registerTemplate(key, component)
  // which stores the component in window.Quizably._templates[key].
  const registered = typeof window !== 'undefined' ? window.Quizably?._templates?.[t] : null;
  if (registered) return registered;
  return BUILTIN_TEMPLATES[t] ?? Classic;
});

// Saved progress waiting for the visitor's choice (see useQuizFlow resume).
// Shown in place of the template so it works the same in every template.
const showResume = computed(() => Boolean(flow.resumeOffer?.value) && flow.screen.value === 'intro');
const resuming = ref(false);

async function onResume() {
  resuming.value = true;
  try {
    await flow.acceptResume();
  } finally {
    resuming.value = false;
  }
}

function onStartOver() {
  flow.declineResume();
}

async function onNav(action, payload) {
  if (action === 'start') {
    flow.start();
  } else if (action === 'next') {
    flow.next();
  } else if (action === 'expired') {
    // Timer ran out — advance unconditionally, even if the question was required.
    flow.forceNext();
  } else if (action === 'prev') {
    flow.prev();
  } else if (action === 'answer') {
    // setAnswer auto-triggers the debounced server save via submission.
    flow.setAnswer(payload.questionId, payload.value, payload.answer_order ?? null);
  } else if (action === 'complete') {
    flow.complete();
  } else if (action === 'form') {
    // Persist the lead BEFORE advancing the flow so a server error (bad
    // email, rate limit, etc.) surfaces to the user instead of silently
    // dropping the lead. We still update the local flow state even on
    // server failure so the UX isn't wedged on the form screen.
    if (!isDemo.value) {
      try {
        const formRes = await submission.submitForm(payload);
        // Double opt-in: the server saved the lead as pending and emailed a confirmation
        // link. Show a notice, then carry on to the result as normal.
        if (formRes?.double_optin_pending) {
          doubleOptinPending.value = true;
          doubleOptinEmail.value = payload?.email || __('your email address');
        }
      } catch (e) {
        // submission.submitForm already captures the error on
        // submission.error.value; we don't rethrow because we still
        // want the user to see the next screen.
      }
    }
    flow.submitForm(payload);
  } else if (action === 'skip') {
    // Visitor declined the optional form — straight to the result, no lead
    // saved. flow.skipForm() ignores this unless placement is 'optional'.
    flow.skipForm();
  } else if (action === 'retake') {
    flow.reset();
    // Start a fresh server submission so retake answers aren't sent to the
    // already-completed UUID (which would get 409 "Submission already completed"
    // on every save_answer call and leave the result screen unreachable).
    if (!isDemo.value && props.uuid) {
      submission.start();
    }
  }
}

onMounted(() => {
  if (!isDemo.value && props.uuid) {
    submission.start();
  }
  // Auto-start skips the intro, unless saved progress is waiting for the visitor's
  // choice (resume / start over) - that prompt must not be bypassed.
  if (props.autoStart && flow.screen.value === 'intro' && !flow.resumeOffer?.value) {
    flow.start();
  }
});

// Record when each question becomes active so elapsed_ms can be sent with the answer save.
watch(
  () => flow.currentQuestion?.value,
  (q) => {
    if (q && !isDemo.value && submission.recordQuestionStart) {
      submission.recordQuestionStart(q.id);
    }
  },
  { immediate: true }
);

provide('quiz', quiz);
provide('flow', flow);

const submissionError = computed(() => submission?.error?.value ?? null);
</script>

<style scoped>
.quizably-quiz {
  /* Horizontal direction multiplier for translateX animations: 1 = LTR, -1 = RTL.
     The player never sets `dir`; it follows the host theme (see :dir(rtl) below). */
  --quizably-dir: 1;
  --quizably-quiz-brand: var(--brand);
  --quizably-quiz-text: var(--ink-1);
  --quizably-quiz-accent: var(--accent);
  /* Derived muted variants — always track the user's text color so they remain
     legible on dark/image backgrounds. Use these instead of --ink-2/--ink-3. */
  --quizably-quiz-text-muted:  color-mix(in srgb, var(--quizably-quiz-text) 65%, transparent);
  /* 60% keeps subtle text (meta labels, hints, placeholders) at WCAG AA contrast: about 5:1 on the
     white and off-white quiz surfaces. 45% rendered #919191 on white, only 3.15:1. */
  --quizably-quiz-text-subtle: color-mix(in srgb, var(--quizably-quiz-text) 60%, transparent);
  /* Answer-option surface tokens — derived from quiz bg+text so option cards
     remain readable regardless of whether the user chose a dark or light scheme.
     Replaces the hardcoded --bg-surface / --border-2 tokens on option rows. */
  --quizably-quiz-option-bg:        color-mix(in srgb, var(--quizably-quiz-text) 8%,  transparent);
  --quizably-quiz-option-bg-hover:  color-mix(in srgb, var(--quizably-quiz-text) 14%, transparent);
  --quizably-quiz-option-border:    color-mix(in srgb, var(--quizably-quiz-text) 20%, transparent);
  --quizably-quiz-option-letter-bg: color-mix(in srgb, var(--quizably-quiz-text) 12%, transparent);
  color: var(--quizably-quiz-text);
}

/* --quizably-quiz-bg colours the outer wrapper for page-layout templates
   (Minimal, Conversational, Gamified, Magazine, Fullscreen, Splitscreen).
   Card-based templates (Classic, Cardstack) render the background colour
   directly on their card surface and keep the outer wrapper transparent. */
.quizably-quiz--page-bg {
  background: var(--quizably-quiz-bg, transparent);
}

.quizably-quiz__bg,
.quizably-quiz__bg-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.quizably-quiz__bg {
  background-position: center;
  background-repeat: no-repeat;
  /* `cover` is correct for a decorative backdrop; the no-crop image rule
     (CLAUDE.md) applies to content <img> tags only. */
  background-size: cover;
}

.quizably-quiz__bg-overlay {
  z-index: 1;
}

.quizably-quiz:dir(rtl) {
  --quizably-dir: -1;
}

.quizably-quiz__content {
  position: relative;
  z-index: 2;
  /* Stretch to fill .quizably-quiz height when Quiz.vue activates full-height
     flex layout (background image or non-top valign) so the template's
     content can centre itself within the full viewport. */
  flex: 1;
  display: flex;
  flex-direction: column;
}

.quizably-quiz__error {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  margin-bottom: 12px;
  background: var(--danger-bg, #fef2f2);
  border: 1px solid var(--danger, #ef4444);
  border-radius: var(--r-md, 8px);
  color: var(--danger, #ef4444);
  font-size: 14px;
}

.quizably-quiz__error-dismiss {
  margin-inline-start: auto;
  padding: 0;
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  font-size: 16px;
  line-height: 1;
}

</style>
