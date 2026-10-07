<template>
  <section
    v-if="question"
    :class="['quizably-question', `quizably-question--align-${questionAlign}`]"
  >
    <p class="eyebrow quizably-question__count">
      {{ sprintf(__('Question %1$d of %2$d'), index + 1, total) }}
    </p>

    <!-- Per-question countdown timer — only shown when question.settings.timer.enabled -->
    <div
      v-if="timerEnabled"
      class="quizably-question__timer"
      :class="{ 'is-urgent': timeLeft <= 10 }"
      role="timer"
      :aria-label="sprintf(__('%d seconds remaining'), timeLeft)"
    >
      <div class="quizably-question__timer-track">
        <div
          class="quizably-question__timer-fill"
          :style="{ width: `${timerPct}%` }"
        />
      </div>
      <span class="quizably-question__timer-count">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" aria-hidden="true">
          <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
        </svg>
        {{ sprintf(__('%ds'), timeLeft) }}
      </span>
    </div>

    <!-- Title and description may contain formatted HTML from the admin editor.
         eslint-disable-next-line vue/no-v-html -->
    <!-- eslint-disable-next-line vue/no-v-html -->
    <h2
      class="quizably-question__title"
      v-html="question.title || ''"
    />

    <!-- Question image — after title, before description.
         image_fit setting: 'contain' (default) uses <img> for accessibility + no-crop guarantee;
         'cover' and 'repeat' use a background-div to comply with the CLAUDE.md rule
         (object-fit:cover must never appear on <img> tags). -->
    <figure
      v-if="question.media_url && imageFit === 'contain'"
      class="quizably-question__media"
      :style="imageHeightStyle"
    >
      <img
        :src="question.media_url"
        :alt="question.media_alt ?? ''"
      >
    </figure>
    <div
      v-else-if="question.media_url"
      class="quizably-question__media quizably-question__media--bg"
      :class="`quizably-question__media--${imageFit}`"
      :style="{ backgroundImage: `url(${question.media_url})`, ...imageHeightStyle }"
      role="img"
      :aria-label="question.media_alt ?? question.title"
    />

    <!-- eslint-disable-next-line vue/no-v-html -->
    <div
      v-if="question.description"
      class="quizably-question__desc"
      v-html="question.description"
    />

    <div class="quizably-question__answers">
      <component
        :is="typeComponent"
        :question="question"
        :value="currentValue"
        :auto-advance="autoAdvance"
        @update:value="onAnswer"
        @advance="onAdvance"
      />
    </div>

    <p
      v-if="error"
      class="quizably-question__error"
      role="alert"
    >
      {{ error }}
    </p>

    <div class="quizably-question__actions">
      <QuizButton
        variant="ghost"
        @click="onPrev"
      >
        {{ __('Back') }}
      </QuizButton>
      <QuizButton
        variant="primary"
        size="lg"
        :disabled="!canAdvance && question.settings?.required !== false"
        @click="onNext"
      >
        {{ isLast ? __('Submit') : __('Next') }}
      </QuizButton>
    </div>
  </section>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { computed, inject, onBeforeUnmount, ref, watch } from 'vue';
import SingleChoice from '../questions/SingleChoice.vue';
import MultiChoice from '../questions/MultiChoice.vue';
import TrueFalse from '../questions/TrueFalse.vue';
import Dropdown from '../questions/Dropdown.vue';
import ShortText from '../questions/ShortText.vue';
import Rating from '../questions/Rating.vue';
import { isValueQuestion } from '@shared/questionTypes.js';
import QuizButton from '../components/QuizButton.vue';
import { shuffledAnswers } from '../composables/useRandomizedAnswers.js';

const props = defineProps({
  quiz: { type: Object, required: true },
});

const emit = defineEmits(['nav']);

const flow = inject('flow');

const question = computed(() => flow.currentQuestion.value);
const index = computed(() => flow.currentIndex.value);
const total = computed(() => flow.totalQuestions.value);
const isLast = computed(() => index.value === total.value - 1);

const autoAdvance = computed(
  () => props.quiz?.settings?.question?.auto_advance !== false
);

const error = ref('');

const currentValue = computed(() => {
  if (!question.value) return null;
  const v = flow.answers[question.value.id];
  if (question.value.type === 'multi' || question.value.type === 'multiple') {
    return Array.isArray(v) ? v : [];
  }
  return v ?? null;
});

const canAdvance = computed(() => flow.isCurrentAnswered.value);

// ── Per-question countdown timer ──────────────────────────────────────────────
// Reads question.settings.timer.{enabled, seconds}. When enabled, counts down
// and auto-advances at zero (bypasses required check — time ran out).
const timerEnabled = computed(() => Boolean(question.value?.settings?.timer?.enabled));
const timerSeconds = computed(() => Math.max(1, Number(question.value?.settings?.timer?.seconds) || 30));
const timeLeft = ref(0);
const timerPct = computed(() => Math.max(0, (timeLeft.value / timerSeconds.value) * 100));

let _timerInterval = null;

function clearTimer() {
  if (_timerInterval) { clearInterval(_timerInterval); _timerInterval = null; }
}

function startTimer() {
  clearTimer();
  // A resumed visit starts from what was left, not the full allowance (a
  // reload must not hand out a fresh countdown). At least 1s so the normal
  // expiry path still runs.
  const used = flow.takeResumedElapsed?.(question.value?.id) ?? 0;
  timeLeft.value = Math.max(1, timerSeconds.value - used);
  _timerInterval = setInterval(() => {
    timeLeft.value -= 1;
    if (timeLeft.value <= 0) {
      clearTimer();
      // Emit 'expired' — Quiz.vue routes this to flow.forceNext() which
      // skips the required check. Using 'next' would silently block on
      // required unanswered questions.
      error.value = '';
      emit('nav', 'expired');
    }
  }, 1000);
}

// Restart the timer whenever the active question changes.
watch(question, (q) => {
  if (q && q.settings?.timer?.enabled) {
    startTimer();
  } else {
    clearTimer();
    timeLeft.value = 0;
  }
}, { immediate: true });

onBeforeUnmount(clearTimer);

// ── Alignment — set by QuestionProperties sidebar ────────────────────────────
const questionAlign = computed(() => {
  const v = question.value?.settings?.align;
  return v === 'center' || v === 'right' ? v : 'left';
});

// image_fit from question settings. 'contain' (default) renders as <img> for accessibility
// and full-artwork guarantee. 'cover'/'repeat' render as a background-div — CLAUDE.md HARD rule
// forbids object-fit:cover on <img> tags.
const imageFit = computed(() => {
  const fit = question.value?.settings?.image_fit;
  return fit === 'cover' || fit === 'repeat' ? fit : 'contain';
});

// image_height — set in the QuestionProperties sidebar height input (default 300px).
const DEFAULT_MEDIA_HEIGHT = 300;
const imageHeightStyle = computed(() => {
  const h = Number(question.value?.settings?.image_height);
  return Number.isFinite(h) && h > 0
    ? { height: `${h}px` }
    : { height: `${DEFAULT_MEDIA_HEIGHT}px` };
});

// Built-in question types shipped by the free plugin. Pro and other
// extensions may register additional types via
// `window.Quizably.frontendHooks.registerQuestionType(key, component)` which
// stores the component in `window.Quizably._questionTypes[key]`.
const BUILTIN_TYPES = {
  single: SingleChoice,
  // The admin builder writes the type as `multiple` (Phase-4 default); older
  // payloads use `multi`. Accept both so the right component renders.
  multi: MultiChoice,
  multiple: MultiChoice,
  true_false: TrueFalse,
  truefalse: TrueFalse,
  dropdown: Dropdown,
  short_text: ShortText,
  rating: Rating,
};

const typeComponent = computed(() => {
  const t = question.value?.type ?? 'single';
  const registered =
    typeof window !== 'undefined' ? window.Quizably?._questionTypes?.[t] : null;
  if (registered) return registered;
  return BUILTIN_TYPES[t] ?? SingleChoice;
});

function onAnswer(value) {
  error.value = '';
  if (!question.value) return;
  const q = question.value;
  // Record the displayed answer order so analytics can reconstruct the
  // shuffled presentation. null when randomize_answers is off (no-op for server).
  const answer_order = q.settings?.randomize_answers
    ? shuffledAnswers(q).map((a) => Number(a.id))
    : null;
  emit('nav', 'answer', { questionId: q.id, value, answer_order });
}

function onAdvance() {
  // Delegates through the same pipeline so the outer layer stays in control.
  onNext();
}

function onNext() {
  if (!canAdvance.value && question.value?.settings?.required !== false) {
    // A typed or rated answer isn't "choosing", so say what's actually missing.
    error.value = isValueQuestion(question.value?.type)
      ? __('Please answer this question to continue.')
      : __('Please choose an answer to continue.');
    return;
  }
  error.value = '';
  emit('nav', 'next');
}

function onPrev() {
  error.value = '';
  emit('nav', 'prev');
}
</script>

<style scoped>
.quizably-question {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* ── Alignment variants ──────────────────────────────────────────────────────── */
.quizably-question--align-left   { text-align: start;  align-items: flex-start; }
.quizably-question--align-center { text-align: center; align-items: center; }

/* The answers wrapper always spans the full card width regardless of
   which alignment the parent flex container uses. align-self: stretch
   overrides the inherited flex-start / center / flex-end. */
.quizably-question__answers {
  align-self: stretch;
  width: 100%;
}
.quizably-question--align-right  { text-align: end;    align-items: flex-end; }

.quizably-question__count {
  margin: 0;
}

/* Height is driven by the inline imageHeightStyle (default 300px).
   Modifier classes must NOT set a static height — inline style owns it.
   Width must be explicit: .quizably-question is a column flex container whose
   alignment variants set align-items to flex-start/center/flex-end (never
   stretch), so a flex item with no sizing signal of its own collapses to
   zero width. The <img> in contain mode masks this (an <img> has its own
   intrinsic size to fall back on); the cover/repeat variant is a bare
   background-image <div> with nothing to establish a size, so it collapsed
   to 0×height and rendered nothing despite backgroundImage being set
   correctly — reproduced on both this screen and QuestionPreview.vue. */
.quizably-question__media {
  width: 100%;
  margin: 0 0 4px;
  border-radius: var(--r-md);
  overflow: hidden;
  background: var(--bg-muted);
}
/* Contain mode: <img> fills the container set by inline height. */
.quizably-question__media img {
  display: block;
  width: 100%;
  height: 100%;
  /* Show the author's full image — never crop (CLAUDE.md HARD rule). */
  object-fit: contain;
  background: var(--bg-surface);
}

/* Background-div variants — used when image_fit is 'cover' or 'repeat'.
   Never use object-fit on <img> for these modes — CLAUDE.md HARD rule. */
.quizably-question__media--bg {
  background-color: var(--bg-muted);
  background-position: center;
  background-repeat: no-repeat;
}
.quizably-question__media--cover {
  background-size: cover;
}
.quizably-question__media--repeat {
  background-size: auto;
  background-repeat: repeat;
}

/* ── Per-question countdown timer ──────────────────────────────────────────── */
.quizably-question__timer {
  display: flex;
  align-items: center;
  gap: 10px;
}

.quizably-question__timer-track {
  flex: 1;
  height: 4px;
  background: var(--border-2, #e5e7eb);
  border-radius: var(--r-pill);
  overflow: hidden;
}

.quizably-question__timer-fill {
  height: 100%;
  background: var(--quizably-quiz-brand, #4f46e5);
  border-radius: var(--r-pill);
  transition: width 1s linear;
}

.quizably-question__timer-count {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-family: var(--f-mono);
  font-size: 12px;
  font-weight: 600;
  color: var(--quizably-quiz-brand, #4f46e5);
  flex-shrink: 0;
  min-width: 3ch;
}

/* Urgent state — last 10 seconds turn red */
.quizably-question__timer.is-urgent .quizably-question__timer-fill {
  background: #ef4444;
}
.quizably-question__timer.is-urgent .quizably-question__timer-count {
  color: #ef4444;
}

.quizably-question__title {
  font-family: var(--f-display);
  font-size: 30px;
  line-height: 1.15;
  letter-spacing: -0.015em;
  margin: 0;
  color: var(--quizably-quiz-text);
}

.quizably-question__desc {
  font-size: 15px;
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
  margin: 0;
}

.quizably-question__error {
  color: var(--danger);
  font-size: 13px;
  margin: 0;
}

.quizably-question__actions {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-top: 10px;
  /* Always stretch to full card width so Back stays pinned to the inline-start and
     Next to the inline-end regardless of the content alignment setting. */
  align-self: stretch;
  width: 100%;
}

@media (max-width: 640px) {
  .quizably-question__title {
    font-size: 24px;
  }
}
</style>
