<template>
  <div class="summary-scroll">
    <div class="summary">

      <!-- Confetti canvas — fires once on mount -->
      <canvas
        ref="confettiRef"
        class="summary-confetti"
        aria-hidden="true"
      />

      <!-- Hero -->
      <div class="summary-hero">
        <div class="summary-hero__icon" aria-hidden="true">🎉</div>
        <h2 class="summary-hero__title">
          {{ __('Your quiz is ready!') }}
        </h2>
        <p class="summary-hero__sub">
          {{ readyText }}
        </p>
      </div>

      <!-- Embed cards -->
      <div class="summary-cards">

        <!-- Shortcode -->
        <div class="summary-card">
          <div class="summary-card__head">
            <span class="summary-card__label">{{ __('Shortcode') }}</span>
            <span class="summary-card__hint">{{ __('Paste into any WordPress page, post, or widget') }}</span>
          </div>
          <div class="summary-card__row">
            <code class="summary-card__code">{{ shortcode }}</code>
            <button
              type="button"
              class="summary-card__copy"
              :class="{ 'is-copied': copied === 'shortcode' }"
              :aria-label="copied === 'shortcode' ? __('Copied') : __('Copy shortcode')"
              @click="copy('shortcode', shortcode)"
            >
              <svg v-if="copied !== 'shortcode'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" /></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
              {{ copied === 'shortcode' ? __('Copied!') : __('Copy') }}
            </button>
          </div>
        </div>

        <!-- iFrame embed -->
        <div class="summary-card">
          <div class="summary-card__head">
            <span class="summary-card__label">{{ __('iFrame embed') }}</span>
            <span class="summary-card__hint">{{ __('Embed on any external website or landing page') }}</span>
          </div>
          <div class="summary-card__row">
            <code class="summary-card__code summary-card__code--wrap">{{ iframeCode }}</code>
            <button
              type="button"
              class="summary-card__copy"
              :class="{ 'is-copied': copied === 'iframe' }"
              :aria-label="copied === 'iframe' ? __('Copied') : __('Copy iframe code')"
              @click="copy('iframe', iframeCode)"
            >
              <svg v-if="copied !== 'iframe'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" /></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
              {{ copied === 'iframe' ? __('Copied!') : __('Copy') }}
            </button>
          </div>
          <p class="summary-card__note">
            {{ __('The embed URL works on any site — no WordPress needed on the destination.') }}
          </p>
        </div>

        <!-- Popup shortcode -->
        <div class="summary-card">
          <div class="summary-card__head">
            <span class="summary-card__label">{{ __('Popup shortcode') }}</span>
            <span class="summary-card__hint">{{ __('Opens the quiz in a centered modal overlay') }}</span>
          </div>
          <div class="summary-card__row">
            <code class="summary-card__code">{{ popupShortcode }}</code>
            <button
              type="button"
              class="summary-card__copy"
              :class="{ 'is-copied': copied === 'popup' }"
              :aria-label="copied === 'popup' ? __('Copied') : __('Copy popup shortcode')"
              @click="copy('popup', popupShortcode)"
            >
              <svg v-if="copied !== 'popup'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" /></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
              {{ copied === 'popup' ? __('Copied!') : __('Copy') }}
            </button>
          </div>
          <p class="summary-card__note" v-html="popupNote" />
        </div>

        <!-- Slide-in shortcode -->
        <div class="summary-card">
          <div class="summary-card__head">
            <span class="summary-card__label">{{ __('Slide-in shortcode') }}</span>
            <span class="summary-card__hint">{{ __('A button in the corner that opens the quiz in a small panel') }}</span>
          </div>
          <div class="summary-card__row">
            <code class="summary-card__code">{{ slideinShortcode }}</code>
            <button
              type="button"
              class="summary-card__copy"
              :class="{ 'is-copied': copied === 'slidein' }"
              :aria-label="copied === 'slidein' ? __('Copied') : __('Copy slide-in shortcode')"
              @click="copy('slidein', slideinShortcode)"
            >
              <svg v-if="copied !== 'slidein'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" /></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
              {{ copied === 'slidein' ? __('Copied!') : __('Copy') }}
            </button>
          </div>
          <p class="summary-card__note" v-html="slideinNote" />
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { __, sprintf } from '@shared/i18n';

const store = useQuizBuilderStore();
const confettiRef = ref(null);
const copied = ref(null);
let copiedTimer = null;

// translators: %s is the quiz title.
const readyText = computed(() => sprintf(__('%s is published and live. Copy one of the embed options below and drop it anywhere.'), store.quiz?.title || __('Untitled quiz')));

// The notes below contain static, developer-controlled <code> markup only
// (no user input), so they are safe to render with v-html.
const popupNote = computed(() => __('Opens when the button is clicked. To open it by itself, add <code>trigger="delay"</code> (with <code>delay="5"</code> seconds), <code>trigger="scroll"</code> (with <code>scroll="50"</code> percent) or <code>trigger="exit"</code> (desktop only). <code>once="session"</code>, <code>"day"</code> or <code>"always"</code> sets how often it may open on its own (default: session). Press Esc to close it.'));
const slideinNote = computed(() => __('Add <code>position="bottom-left"</code> to move it to the left corner. It takes the same <code>trigger</code>, <code>delay</code>, <code>scroll</code> and <code>once</code> options as the popup, e.g. <code>trigger="scroll" scroll="60" once="day"</code>.'));

const quizId = computed(() => store.quiz?.id ?? '');
const homeUrl = computed(() => (window.QUIZABLY_ADMIN?.homeUrl ?? window.location.origin).replace(/\/$/, ''));

const shortcode = computed(() => `[quizably_quiz id="${quizId.value}"]`);
const popupShortcode = computed(() => `[quizably_quiz_popup id="${quizId.value}"]`);
const slideinShortcode = computed(() => `[quizably_quiz_slidein id="${quizId.value}"]`);
const embedUrl = computed(() => `${homeUrl.value}/?quizably_embed=${quizId.value}`);
const iframeCode = computed(
  () => `<iframe src="${embedUrl.value}" width="100%" height="600" frameborder="0" allowtransparency="true" style="border:none;max-width:100%"></iframe>`
);

function copy(key, text) {
  if (!navigator.clipboard) return;
  navigator.clipboard.writeText(text).then(() => {
    copied.value = key;
    clearTimeout(copiedTimer);
    copiedTimer = setTimeout(() => { copied.value = null; }, 2000);
  });
}

// ---- Canvas confetti ----
const COLORS = ['#6d28d9', '#10b981', '#f59e0b', '#3b82f6', '#ec4899', '#ef4444', '#14b8a6'];

function launchConfetti(canvas) {
  const dpr = window.devicePixelRatio || 1;
  canvas.width = canvas.offsetWidth * dpr;
  canvas.height = canvas.offsetHeight * dpr;
  const ctx = canvas.getContext('2d');
  ctx.scale(dpr, dpr);

  const W = canvas.offsetWidth;
  const H = canvas.offsetHeight;

  const pieces = Array.from({ length: 140 }, () => ({
    x: Math.random() * W,
    y: -20 - Math.random() * H * 0.5,
    w: 6 + Math.random() * 7,
    h: 3 + Math.random() * 4,
    color: COLORS[Math.floor(Math.random() * COLORS.length)],
    vx: (Math.random() - 0.5) * 3,
    vy: 1.5 + Math.random() * 3.5,
    rot: Math.random() * 360,
    vrot: (Math.random() - 0.5) * 9,
    opacity: 1,
  }));

  let raf;
  function tick() {
    ctx.clearRect(0, 0, W, H);
    let alive = false;
    for (const p of pieces) {
      p.x += p.vx;
      p.y += p.vy;
      p.vy += 0.06;
      p.rot += p.vrot;
      if (p.y > H * 0.75) p.opacity -= 0.018;
      if (p.opacity <= 0) continue;
      alive = true;
      ctx.save();
      ctx.globalAlpha = Math.max(0, p.opacity);
      ctx.translate(p.x, p.y);
      ctx.rotate((p.rot * Math.PI) / 180);
      ctx.fillStyle = p.color;
      ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
      ctx.restore();
    }
    if (alive) raf = requestAnimationFrame(tick);
  }
  tick();
  return () => cancelAnimationFrame(raf);
}

onMounted(() => {
  if (confettiRef.value) {
    launchConfetti(confettiRef.value);
  }
});
</script>

<style scoped>
.summary-scroll {
  height: 100%;
  overflow-y: auto;
  min-height: 0;
  position: relative;
}

.summary {
  max-width: 720px;
  margin: 0 auto;
  padding: 48px 32px 64px;
  display: flex;
  flex-direction: column;
  gap: 28px;
  position: relative;
}

/* Confetti canvas fills the hero area */
.summary-confetti {
  position: absolute;
  top: 0;
  inset-inline-start: 0;
  width: 100%;
  height: 340px;
  pointer-events: none;
  z-index: 0;
}

/* ---- Hero ---- */
.summary-hero {
  text-align: center;
  padding: 20px 0 8px;
  position: relative;
  z-index: 1;
}

.summary-hero__icon {
  font-size: 52px;
  line-height: 1;
  margin-bottom: 16px;
  display: block;
  animation: quizably-summary-pop 600ms cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

@keyframes quizably-summary-pop {
  from { transform: scale(0.4); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}

.summary-hero__title {
  font-family: var(--f-display);
  font-size: 28px;
  font-weight: 500;
  letter-spacing: -0.02em;
  color: var(--ink-1);
  margin: 0 0 10px;
  line-height: 1.2;
}

.summary-hero__sub {
  font-size: 14px;
  color: var(--ink-3);
  margin: 0;
  line-height: 1.6;
  max-width: 52ch;
  margin-inline: auto;
}

/* ---- Cards ---- */
.summary-cards {
  display: flex;
  flex-direction: column;
  gap: 14px;
  position: relative;
  z-index: 1;
}

.summary-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  box-shadow: var(--shadow-xs);
}

.summary-card__head {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.summary-card__label {
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-1);
}

.summary-card__hint {
  font-size: 12px;
  color: var(--ink-3);
  line-height: 1.45;
}

.summary-card__row {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 10px 12px;
}

.summary-card__code {
  flex: 1;
  min-width: 0;
  font-family: var(--f-mono);
  font-size: 12.5px;
  color: var(--ink-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.5;
  background: none;
}

.summary-card__code--wrap {
  white-space: pre-wrap;
  word-break: break-all;
  text-overflow: unset;
  overflow: visible;
}

.summary-card__copy {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 11px;
  border-radius: var(--r-xs);
  border: 1px solid var(--border-2);
  background: var(--bg-surface);
  color: var(--ink-2);
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: background 120ms, border-color 120ms, color 120ms;
  white-space: nowrap;
  line-height: 1;
}

.summary-card__copy svg {
  width: 13px;
  height: 13px;
  flex-shrink: 0;
}

.summary-card__copy:hover {
  background: var(--bg-subtle);
  border-color: var(--border-3);
  color: var(--ink-1);
}

.summary-card__copy.is-copied {
  background: var(--success-bg, #ecfdf5);
  border-color: var(--success, #10b981);
  color: var(--success, #10b981);
}

.summary-card__note {
  font-size: 12px;
  color: var(--ink-3);
  margin: 0;
  line-height: 1.5;
}

.summary-card__note code {
  font-family: var(--f-mono);
  font-size: 11.5px;
  color: var(--ink-2);
  background: none;
}

@media (prefers-reduced-motion: reduce) {
  .summary-hero__icon {
    animation: none;
  }
}
</style>
