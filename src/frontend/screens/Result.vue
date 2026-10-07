<template>
  <section :class="['quizably-result', `quizably-result--align-${resultAlign}`]">
    <DoubleOptinNotice />
    <p class="eyebrow quizably-result__eyebrow">
      {{ eyebrow }}
    </p>

    <!-- Title may contain formatted HTML from the rich-text title editor.
         eslint-disable-next-line vue/no-v-html -->
    <!-- eslint-disable-next-line vue/no-v-html -->
    <h2
      class="quizably-result__title"
      v-html="title || ''"
    />

    <!-- Image — after title, inline (not top-bleed).
         Fit and height driven by result.settings.image_fit / image_height.
         Contain uses <img> for full-artwork guarantee (CLAUDE.md rule).
         Cover/repeat use background-div — CLAUDE.md: never object-fit:cover on <img>. -->
    <figure
      v-if="imageUrl && imageFit === 'contain'"
      class="quizably-result__image"
      :style="imageHeightStyle"
    >
      <img :src="imageUrl" :alt="title">
    </figure>
    <div
      v-else-if="imageUrl && imageFit === 'cover'"
      class="quizably-result__image quizably-result__image--cover"
      :style="{ backgroundImage: `url(${imageUrl})`, ...imageHeightStyle }"
      role="img"
      :aria-label="title"
    />
    <div
      v-else-if="imageUrl"
      class="quizably-result__image quizably-result__image--repeat"
      :style="{ backgroundImage: `url(${imageUrl})`, ...imageHeightStyle }"
      role="img"
      :aria-label="title"
    />

    <!-- Poll: how everyone voted, with the visitor's own pick marked. -->
    <PollResults
      v-if="isPoll"
      :quiz="quiz"
      :answers="answers"
    />

    <p
      v-if="isTrivia && scoreLabel"
      class="quizably-result__score"
    >
      {{ scoreLabel }}
    </p>

    <!-- Chart (configurable type: pie / donut / bar) -->
    <QuizResultChart
      v-if="chartType && chartSegments.length"
      :type="chartType"
      :segments="chartSegments"
      :center-text="chartCenterText"
      :center-sub="chartCenterSub"
      :title="sprintf(__('%s result chart'), title)"
      class="quizably-result__chart"
    />

    <!--
      Result description is author-authored HTML from the admin.
      Sanitization happens in the admin editor (wp_kses on save).
    -->
    <div
      v-if="description"
      class="quizably-result__desc"
      v-html="description"
    />

    <div
      v-if="isTrivia && reviewEnabled"
      class="quizably-result__review"
    >
      <button
        type="button"
        class="quizably-result__review-toggle"
        :aria-expanded="reviewOpen"
        @click="reviewOpen = !reviewOpen"
      >
        {{ reviewOpen ? __('Hide answers') : __('Review answers') }}
      </button>
      <ul
        v-if="reviewOpen"
        class="quizably-result__review-list"
      >
        <li
          v-for="q in questions"
          :key="q.id"
          class="quizably-result__review-item"
        >
          <p class="quizably-result__review-q">
            {{ q.title }}
          </p>
          <p
            :class="[
              'quizably-result__review-a',
              isAnswerCorrect(q) ? 'is-correct' : 'is-wrong',
            ]"
          >
            {{ answerLabelFor(q) }}
          </p>
        </li>
      </ul>
    </div>

    <!-- Social sharing -->
    <div
      v-if="shareEnabled && activePlatforms.length"
      class="quizably-result__share"
    >
      <span class="eyebrow">{{ __('Share your result') }}</span>
      <div class="quizably-result__share-buttons">
        <a
          v-for="p in activePlatforms"
          :key="p.key"
          :href="p.href"
          target="_blank"
          rel="noopener noreferrer"
          :aria-label="sprintf(__('Share on %s'), p.label)"
          class="quizably-result__share-btn"
          :style="{ '--sb-color': p.color }"
        >
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="p.icon" class="quizably-result__share-icon" aria-hidden="true" />
          <span class="quizably-result__share-label">{{ p.label }}</span>
        </a>
      </div>
    </div>

    <a
      v-if="ctaUrl && ctaLabel"
      :href="ctaUrl"
      class="quizably-result__cta"
      target="_blank"
      rel="noopener noreferrer"
    >{{ ctaLabel }}</a>

    <div class="quizably-result__actions">
      <QuizButton
        variant="outline"
        @click="$emit('nav', 'retake')"
      >
        {{ __('Retake quiz') }}
      </QuizButton>
    </div>
  </section>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { computed, ref } from 'vue';
import QuizButton from '../components/QuizButton.vue';
import QuizResultChart from '../components/QuizResultChart.vue';
import PollResults from '../components/PollResults.vue';
import DoubleOptinNotice from '../components/DoubleOptinNotice.vue';

function stripHtml(html) {
  return (html || '').replace(/<[^>]*>/g, '').trim();
}

const props = defineProps({
  quiz: { type: Object, required: true },
  result: { type: Object, default: null },
  score: { type: Number, default: null },
  breakdown: { type: Object, default: null },
  answers: { type: Object, default: () => ({}) },
});

defineEmits(['nav']);

const isTrivia = computed(() => props.quiz?.type === 'trivia');
const isPoll = computed(() => props.quiz?.type === 'poll');
const eyebrow = computed(() => {
  if (isPoll.value) return __('Poll results');
  return isTrivia.value ? __('Your score') : __('Your result');
});
const reviewEnabled = computed(
  () => props.quiz?.settings?.result?.review_answers !== false
);
const reviewOpen = ref(false);

// A poll without a configured result still gets this screen (it shows the tally), so give it a
// fitting heading instead of the generic fallback.
const title = computed(() => props.result?.title ?? (isPoll.value ? __('Thanks for voting') : __('Thanks!')));
const description = computed(() => props.result?.content ?? '');
const imageUrl = computed(() => props.result?.image_url ?? '');
const ctaLabel = computed(() => props.result?.cta_label ?? '');
const ctaUrl = computed(() => props.result?.cta_url ?? '');
const redirectUrl = computed(() => props.result?.redirect_url ?? '');

// ── Alignment — set by ResultProperties sidebar ──────────────────────────────
const resultAlign = computed(() => {
  const v = props.result?.settings?.align;
  return v === 'left' || v === 'right' ? v : 'center';
});

// image_fit — contain (default) / cover / repeat.
// Contain uses <img>; cover/repeat use background-div (CLAUDE.md HARD rule).
const imageFit = computed(() => {
  const v = props.result?.settings?.image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

// image_height — set by the ResultProperties height slider (default 240px).
const DEFAULT_RESULT_IMAGE_HEIGHT = 240;
const imageHeightStyle = computed(() => {
  const h = Number(props.result?.settings?.image_height);
  return Number.isFinite(h) && h > 0
    ? { height: `${h}px` }
    : { height: `${DEFAULT_RESULT_IMAGE_HEIGHT}px` };
});

// ---- Chart ----

const CHART_COLORS = [
  '#6d28d9', '#10b981', '#f59e0b', '#3b82f6',
  '#ec4899', '#ef4444', '#14b8a6', '#f97316',
];

const chartType = computed(() => {
  const t = props.result?.settings?.chart?.type;
  return t && t !== 'none' ? t : null;
});

const chartSegments = computed(() => {
  if (!chartType.value) return [];

  // Trivia: correct vs wrong
  if (isTrivia.value && props.breakdown?.total_questions != null) {
    const correct = props.breakdown.correct_count ?? 0;
    const total = props.breakdown.total_questions;
    const wrong = total - correct;
    return [
      { label: __('Correct'), value: correct, pct: total ? Math.round((correct / total) * 100) : 0, color: '#10b981' },
      { label: __('Wrong'), value: wrong, pct: total ? Math.round((wrong / total) * 100) : 0, color: '#ef4444' },
    ].filter((s) => s.value > 0);
  }

  // Personality / weighted: tally of result votes
  if (['personality', 'weighted'].includes(props.quiz?.type) && props.breakdown?.tally) {
    const results = props.quiz?.results ?? [];
    const tally = props.breakdown.tally ?? {};
    const total = Object.values(tally).reduce((s, v) => s + Number(v), 0);
    if (!total) return [];
    return results
      .filter((r) => (tally[r.id] || 0) > 0)
      .map((r, i) => {
        const val = Number(tally[r.id] || 0);
        return {
          label: stripHtml(r.title) || sprintf(__('Result %d'), i + 1),
          value: val,
          pct: Math.round((val / total) * 100),
          color: CHART_COLORS[i % CHART_COLORS.length],
        };
      });
  }

  return [];
});

const chartCenterText = computed(() => {
  if (chartType.value !== 'donut') return '';
  if (isTrivia.value && props.breakdown?.total_questions) {
    const pct = Math.round(
      ((props.breakdown.correct_count ?? 0) / props.breakdown.total_questions) * 100
    );
    return `${pct}%`;
  }
  return '';
});

const chartCenterSub = computed(() => {
  if (chartType.value !== 'donut') return '';
  return isTrivia.value ? __('correct') : '';
});

const scoreLabel = computed(() => {
  if (!isTrivia.value) return '';
  const s = props.score ?? props.result?._score;
  if (s == null) return '';
  if (props.breakdown?.total_questions != null) {
    // translators: 1: number of correct answers, 2: total number of questions.
    return sprintf(__('%1$d of %2$d correct'), props.breakdown.correct_count ?? s, props.breakdown.total_questions);
  }
  const total = (props.quiz?.questions ?? []).reduce((acc, q) => {
    const correct = (q.answers ?? []).filter((a) => a.is_correct);
    return acc + correct.reduce((sum, a) => sum + (a.points ?? 1), 0);
  }, 0);
  return total > 0 ? sprintf(__('%1$d of %2$d correct'), s, total) : sprintf(__('Score: %d'), s);
});

// ---- Social sharing ----

const shareSettings = computed(() => props.result?.settings?.share ?? {});
const shareEnabled = computed(() => shareSettings.value.enabled !== false);
const configuredPlatformKeys = computed(
  () => shareSettings.value.platforms ?? ['facebook', 'twitter', 'linkedin']
);

const shareUrl = computed(() =>
  typeof window !== 'undefined' ? encodeURIComponent(window.location.href) : ''
);
const shareText = computed(() => {
  const custom = (shareSettings.value.message || '').replace('{title}', title.value);
  return encodeURIComponent(custom || sprintf(__('I got: %s'), title.value));
});

// Platform definitions — icon is a minimal inline SVG string
const PLATFORM_DEFS = {
  facebook: {
    label: 'Facebook',
    color: '#1877f2',
    href: (u, t) => `https://www.facebook.com/sharer/sharer.php?u=${u}`,
    icon: `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>`,
  },
  twitter: {
    label: 'X',
    color: '#000000',
    href: (u, t) => `https://twitter.com/intent/tweet?url=${u}&text=${t}`,
    icon: `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622 5.911-5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>`,
  },
  linkedin: {
    label: 'LinkedIn',
    color: '#0a66c2',
    href: (u, t) => `https://www.linkedin.com/sharing/share-offsite/?url=${u}`,
    icon: `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>`,
  },
  whatsapp: {
    label: 'WhatsApp',
    color: '#25d366',
    href: (u, t) => `https://wa.me/?text=${t}%20${u}`,
    icon: `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a8.26 8.26 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>`,
  },
  telegram: {
    label: 'Telegram',
    color: '#26a5e4',
    href: (u, t) => `https://t.me/share/url?url=${u}&text=${t}`,
    icon: `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>`,
  },
  email: {
    label: __('Email'),
    color: '#6b7280',
    href: (u, t) => `mailto:?subject=${encodeURIComponent(__('Check out my quiz result'))}&body=${t}%20${u}`,
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>`,
  },
};

const activePlatforms = computed(() =>
  configuredPlatformKeys.value
    .map((key) => {
      const def = PLATFORM_DEFS[key];
      if (!def) return null;
      return {
        key,
        label: def.label,
        color: def.color,
        href: def.href(shareUrl.value, shareText.value),
        icon: def.icon,
      };
    })
    .filter(Boolean)
);

// ---- Review answers ----

const questions = computed(() => props.quiz?.questions ?? []);

function answerLabelFor(q) {
  const v = props.answers[q.id];
  if (v === undefined || v === null || v === '') return __('(skipped)');
  const ids = Array.isArray(v) ? v : [v];
  return ids
    .map((id) => (q.answers ?? []).find((a) => a.id === id)?.label ?? '')
    .filter(Boolean)
    .join(', ');
}

function isAnswerCorrect(q) {
  if (!isTrivia.value) return true;
  const v = props.answers[q.id];
  const ids = Array.isArray(v) ? v : v != null ? [v] : [];
  if (ids.length === 0) return false;
  return ids.every((id) => {
    const a = (q.answers ?? []).find((x) => x.id === id);
    return a?.is_correct;
  });
}

// Auto-redirect results never reach this screen — the flow redirects
// immediately in useQuizFlow.complete() before screen.value is set to 'result'.
// No timer needed here.
</script>

<style scoped>
.quizably-result {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* ── Alignment variants ──────────────────────────────────────────────────────── */
.quizably-result--align-left   { text-align: start;  align-items: flex-start; }
.quizably-result--align-center { text-align: center; align-items: center; }
.quizably-result--align-right  { text-align: end;    align-items: flex-end; }

/* Result image — inline slot after title (not top-bleed).
   Height driven by imageHeightStyle inline style (default 240px). */
/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.quizably-result__image {
  width: 100%;
  /* CSS fallback height — inline style overrides */
  height: 240px;
  overflow: hidden;
  background-color: var(--bg-muted);
  border-radius: var(--r-md);
  flex-shrink: 0;
  display: flex;
  align-items: stretch;
  align-self: stretch;
}

/* Contain: <img> letterboxed — CLAUDE.md: object-fit contain on <img> */
.quizably-result__image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  background: var(--bg-muted);
}

/* Cover: background fills slot — CLAUDE.md: no object-fit:cover on <img> */
.quizably-result__image--cover {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

/* Repeat: tile pattern */
.quizably-result__image--repeat {
  background-size: auto;
  background-repeat: repeat;
  background-position: 0 0;
}

.quizably-result__eyebrow {
  margin: 0;
}

.quizably-result__title {
  font-family: var(--f-display);
  font-size: 44px;
  line-height: 1.05;
  letter-spacing: -0.02em;
  margin: 0;
}

.quizably-result__score {
  font-family: var(--f-display);
  font-size: 20px;
  color: var(--quizably-quiz-brand);
  margin: 0;
}

/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.quizably-result__chart {
  margin: 4px 0 2px;
  align-self: stretch;
  width: 100%;
}

/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.quizably-result__desc {
  font-size: 16px;
  line-height: 1.6;
  color: var(--quizably-quiz-text-muted, var(--ink-2));
  align-self: stretch;
  width: 100%;
}
.quizably-result__desc :deep(p) {
  margin: 0 0 10px;
}
.quizably-result__desc :deep(a) {
  color: var(--quizably-quiz-brand);
  text-decoration: underline;
}

/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.quizably-result__review {
  margin-top: 8px;
  border-top: 1px solid var(--border-1);
  padding-top: 16px;
  align-self: stretch;
  width: 100%;
}

.quizably-result__review-toggle {
  font-family: var(--f-mono);
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--quizably-quiz-brand);
  padding: 0;
}

.quizably-result__review-list {
  list-style: none;
  padding: 0;
  margin: 12px 0 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.quizably-result__review-q {
  font-weight: 500;
  margin: 0;
}

.quizably-result__review-a {
  font-size: 13px;
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
  margin: 4px 0 0;
}
.quizably-result__review-a.is-correct {
  color: var(--success);
}
.quizably-result__review-a.is-wrong {
  color: var(--danger);
}

/* ---- Share ---- */
.quizably-result__share {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 6px;
  /* align-items: inherit propagates the parent's left/center/right alignment
     into the "Share your result" label and the buttons row. */
  align-items: inherit;
}

.quizably-result__share-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.quizably-result__share-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 14px;
  border-radius: var(--r-pill);
  /* Quiz-theme tokens: the global --bg-subtle / --ink-2 pair split apart on a dark
     quiz (cream pill, white label). Hover paints the platform colour with #fff. */
  background: var(--quizably-quiz-option-bg, var(--bg-subtle));
  color: var(--quizably-quiz-text);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  transition: background 140ms ease, color 140ms ease, transform 120ms ease;
}

.quizably-result__share-btn:hover {
  background: var(--sb-color, var(--quizably-quiz-brand));
  color: #fff;
  transform: translateY(-1px);
}

.quizably-result__share-icon {
  display: inline-flex;
  align-items: center;
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.quizably-result__share-icon :deep(svg) {
  width: 16px;
  height: 16px;
}

.quizably-result__share-label {
  line-height: 1;
}

/* ---- CTA / actions ---- */
.quizably-result__actions {
  margin-top: 12px;
}

.quizably-result__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 24px;
  /* Respects design.button_style. Default --r-pill keeps a prominent CTA look. */
  border-radius: var(--quizably-btn-radius, var(--r-pill));
  background: var(--quizably-quiz-brand);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  transition: opacity 150ms ease;
  /* align-self: auto inherits the parent container's align-items so left/center/right
     alignment from the sidebar setting controls button position. */
  align-self: auto;
}
.quizably-result__cta:hover {
  opacity: 0.88;
}

@media (max-width: 640px) {
  .quizably-result__title {
    font-size: 34px;
  }
}
</style>
