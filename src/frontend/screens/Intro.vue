<template>
  <section :class="['quizably-intro', `quizably-intro--align-${introAlign}`]">
    <p class="quizably-intro__eyebrow eyebrow">
      {{ typeLabel }}
    </p>
    <!-- Title and description may contain formatted HTML from the rich-text editor.
         eslint-disable-next-line vue/no-v-html -->
    <!-- eslint-disable-next-line vue/no-v-html -->
    <h2
      class="quizably-intro__title"
      v-html="title || ''"
    />

    <!-- Image — shown after title, before description, respects intro_image_fit and intro_image_height. -->
    <div
      v-if="coverUrl"
      :class="['quizably-intro__cover', `quizably-intro__cover--${introImageFit}`]"
      :style="introImageHeightStyle"
    >
      <!-- Repeat mode: CSS background tile -->
      <div
        v-if="introImageFit === 'repeat'"
        class="quizably-intro__cover-tile"
        :style="{ backgroundImage: `url(${coverUrl})` }"
        role="img"
        :aria-label="title || __('Intro image')"
      />
      <!-- Cover: background-size cover fills the slot — no <img> involved -->
      <div
        v-else-if="introImageFit === 'cover'"
        class="quizably-intro__cover-bg"
        :style="{ backgroundImage: `url(${coverUrl})` }"
        role="img"
        :aria-label="title || __('Intro image')"
      />
      <!-- Contain: semantic <img> letterboxed (CLAUDE.md: object-fit contain) -->
      <img
        v-else
        :src="coverUrl"
        :alt="title || __('Intro image')"
      >
    </div>

    <!-- eslint-disable-next-line vue/no-v-html -->
    <div
      v-if="description"
      class="quizably-intro__desc"
      v-html="description"
    />

    <dl
      v-if="showMeta"
      class="quizably-intro__meta"
    >
      <div class="quizably-intro__meta-item">
        <dt>{{ __('Questions') }}</dt>
        <dd>{{ questionCount }}</dd>
      </div>
      <div class="quizably-intro__meta-item">
        <dt>{{ __('Takes') }}</dt>
        <dd>{{ sprintf(__('~%d min'), estimatedMinutes) }}</dd>
      </div>
    </dl>

    <QuizButton
      variant="primary"
      size="lg"
      @click="$emit('nav', 'start')"
    >
      {{ ctaLabel }}
    </QuizButton>
  </section>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { computed } from 'vue';
import QuizButton from '../components/QuizButton.vue';

const props = defineProps({
  quiz: { type: Object, required: true },
});

defineEmits(['nav']);

// Admin stores intro settings under settings.screens.intro_* keys.
// Legacy quizzes may have used settings.intro.* — keep fallbacks for backward compat.
const title = computed(
  () =>
    props.quiz?.settings?.screens?.intro_title ||
    props.quiz?.title ||
    'Quiz'
);
const description = computed(
  () =>
    props.quiz?.settings?.screens?.intro_subtitle ??
    props.quiz?.settings?.intro?.description ??
    props.quiz?.description ??
    ''
);
const coverUrl = computed(
  () =>
    props.quiz?.settings?.screens?.intro_cover ??
    props.quiz?.settings?.intro?.cover_url ??
    props.quiz?.cover_url ??
    ''
);
const introImageFit = computed(() => {
  const v = props.quiz?.settings?.screens?.intro_image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});
const ctaLabel = computed(
  () =>
    props.quiz?.settings?.screens?.intro_button ??
    props.quiz?.settings?.intro?.cta_label ??
    __('Start quiz')
);
const questionCount = computed(() => (props.quiz?.questions ?? []).length);
const estimatedMinutes = computed(() => {
  const seconds = Math.max(30, questionCount.value * 20);
  return Math.max(1, Math.round(seconds / 60));
});
// Admins can hide the "Questions / Takes ~N min" hint via the Settings tab.
// Defaults to ON for backwards compatibility — only an explicit `false`
// hides the meta block.
const showMeta = computed(
  () => props.quiz?.settings?.screens?.intro_show_meta !== false
);

const typeLabel = computed(() => {
  const t = props.quiz?.type ?? 'personality';
  const map = {
    personality: __('Personality'),
    trivia: __('Trivia'),
    survey: __('Survey'),
    poll: __('Poll'),
  };
  return map[t] ?? __('Quiz');
});

// ── Alignment — set by IntroProperties sidebar ───────────────────────────────
const introAlign = computed(() => {
  const v = props.quiz?.settings?.screens?.intro_align;
  return v === 'left' || v === 'right' ? v : 'center';
});

// intro_image_height — set by the IntroProperties height input (default 280px matches CSS fallback).
const DEFAULT_INTRO_COVER_HEIGHT = 280;
const introImageHeightStyle = computed(() => {
  const h = Number(props.quiz?.settings?.screens?.intro_image_height);
  return Number.isFinite(h) && h > 0
    ? { height: `${h}px` }
    : { height: `${DEFAULT_INTRO_COVER_HEIGHT}px` };
});
</script>

<style scoped>
.quizably-intro {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 16px;
}

/* ── Alignment variants ──────────────────────────────────────────────────────── */
.quizably-intro--align-left   { text-align: start;  align-items: flex-start; }
.quizably-intro--align-center { text-align: center; align-items: center; }
.quizably-intro--align-right  { text-align: end;    align-items: flex-end; }

/* ── Cover image — after description (CLAUDE.md: object-fit contain, never crop) ── */
/* Height is driven by introImageHeightStyle inline style (default 280px).
   Template :deep overrides can still change dimensions but inline style wins over CSS class. */
.quizably-intro__cover {
  width: 100%;
  /* CSS fallback height — inline style from introImageHeightStyle takes precedence. */
  height: 280px;
  margin-bottom: 4px;
  background: var(--bg-muted);
  border-radius: var(--r-md);
  overflow: hidden;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  /* align-self: stretch keeps this full-width regardless of the parent's align-items */
  align-self: stretch;
}

/* Contain: <img> fills container — CLAUDE.md: object-fit contain on <img> elements */
.quizably-intro__cover--contain img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

/* Cover: background-image div fills the slot — no <img> involved, CLAUDE.md safe */
.quizably-intro__cover-bg {
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

/* Repeat: tile at native size */
.quizably-intro__cover-tile {
  width: 100%;
  height: 100%;
  background-repeat: repeat;
  background-position: 0 0;
  background-size: auto;
}

.quizably-intro__eyebrow {
  margin: 0;
}

.quizably-intro__title {
  font-family: var(--f-display);
  font-size: 40px;
  line-height: 1.05;
  letter-spacing: -0.02em;
  margin: 0;
  color: var(--quizably-quiz-text);
}

/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.quizably-intro__desc {
  font-size: 16px;
  line-height: 1.55;
  color: var(--quizably-quiz-text-muted, var(--ink-2));
  margin: 0;
  width: 100%;
  align-self: stretch;
}
.quizably-intro__desc :deep(p) { margin: 0 0 8px; }
.quizably-intro__desc :deep(p:last-child) { margin-bottom: 0; }
.quizably-intro__desc :deep(strong) { font-weight: 700; }
.quizably-intro__desc :deep(em) { font-style: italic; }
.quizably-intro__desc :deep(u) { text-decoration: underline; }
.quizably-intro__desc :deep(a) { color: var(--quizably-quiz-brand); text-decoration: underline; }

.quizably-intro__meta {
  display: flex;
  gap: 32px;
  margin: 8px 0 12px;
  padding: 0;
}

.quizably-intro__meta-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin: 0;
}

.quizably-intro__meta-item dt {
  font-family: var(--f-mono);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
}

.quizably-intro__meta-item dd {
  font-family: var(--f-display);
  font-size: 22px;
  margin: 0;
  color: var(--quizably-quiz-text);
}

@media (max-width: 640px) {
  .quizably-intro__title { font-size: 32px; }
  /* Shrink default box at mobile; template :deep overrides also shrink proportionally */
  .quizably-intro__cover { height: 180px; }
}
</style>
