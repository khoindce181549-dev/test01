<template>
  <div class="logic-view">
    <header class="logic-view__head">
      <div>
        <span class="logic-view__eyebrow">{{ __('Pro · Workflow editor') }}</span>
        <h1 class="logic-view__title">{{ __('Logic & Branching') }}</h1>
        <p class="logic-view__lede">
          {{ __('Pick a quiz to wire up its branching rules visually. Drag nodes to lay out the flow, click ports to connect answers to the next question (or to End the quiz).') }}
        </p>
      </div>

      <div class="logic-view__quiz-picker">
        <label
          for="logic-view-quiz"
          class="logic-view__picker-label"
        >{{ __('Quiz') }}</label>
        <select
          id="logic-view-quiz"
          class="logic-view__select"
          :value="selectedQuizId ?? ''"
          data-testid="logic-view-quiz-select"
          @change="onSelect($event.target.value)"
        >
          <option
            value=""
            disabled
          >
            {{ __('Select a quiz…') }}
          </option>
          <option
            v-for="q in eligibleQuizzes"
            :key="q.id"
            :value="q.id"
          >
            {{ q.title || __('Untitled quiz') }} — {{ typeLabel(q.type) }}
          </option>
        </select>

        <p
          v-if="ineligibleQuizzes.length"
          class="logic-view__picker-hint"
        >
          {{ ineligibleHint }}
        </p>
      </div>
    </header>

    <div
      v-if="!isProUser"
      class="logic-view__body"
    >
      <LogicProUpsell />
    </div>

    <div
      v-else-if="!quizzesStore.loaded && quizzesStore.loading"
      class="logic-view__loading"
      role="status"
    >
      <span
        class="logic-view__spinner"
        aria-hidden="true"
      />
      <span>{{ __('Loading quizzes…') }}</span>
    </div>

    <div
      v-else-if="!eligibleQuizzes.length"
      class="logic-view__empty"
    >
      <h2>{{ __('No branchable quizzes yet') }}</h2>
      <p>
        {{ __('Create a quiz with at least two questions that have answer choices — single, multi, true/false, dropdown, or image-choice all work — and come back here to wire up the flow.') }}
      </p>
      <RouterLink
        to="/quizzes"
        class="logic-view__cta"
      >
        {{ __('Go to All Quizzes') }}
      </RouterLink>
    </div>

    <div
      v-else-if="!selectedQuizId"
      class="logic-view__empty"
    >
      <h2>{{ __('Pick a quiz from the dropdown') }}</h2>
      <p>{{ __('Choose any quiz above to load its workflow.') }}</p>
    </div>

    <div
      v-else-if="builderStore.loading"
      class="logic-view__loading"
      role="status"
    >
      <span
        class="logic-view__spinner"
        aria-hidden="true"
      />
      <span>{{ loadingQuizText }}</span>
    </div>

    <div
      v-else-if="builderStore.quiz"
      class="logic-view__editor"
    >
      <component
        v-if="injected"
        :is="injected"
        :questions="cleanQuestions"
        :quiz-id="builderStore.quiz?.id ?? null"
        @update-logic="onUpdateLogic"
      />
      <LogicProUpsell v-else />
    </div>

    <div
      v-else
      class="logic-view__empty"
    >
      <p>{{ __('That quiz could not be loaded. Try another from the dropdown.') }}</p>
    </div>
  </div>
</template>

<script setup>
import { __, _n, sprintf } from '@shared/i18n';
import { computed, onBeforeUnmount, onMounted, shallowRef, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useQuizzesStore } from '@admin/stores/quizzes';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { isPro } from '@admin/api/pro.js';
import { useToast } from '@admin/ui';
import LogicProUpsell from './builder/LogicProUpsell.vue';

function stripHtml(html) {
  return (html || '').replace(/<[^>]*>/g, '').trim();
}

const cleanQuestions = computed(() =>
  builderStore.questions.map((q) => ({ ...q, title: stripHtml(q.title) }))
);

function typeLabel(type) {
  const labels = {
    personality: __('Personality'),
    weighted: __('Weighted'),
    trivia: __('Trivia'),
    survey: __('Survey'),
    poll: __('Poll'),
    branching: __('Branching'),
  };
  return labels[String(type ?? '').toLowerCase()] ?? __('Quiz');
}

const ineligibleHint = computed(() =>
  sprintf(
    // translators: %d is the number of quizzes that cannot be used for branching.
    _n(
      '%d quiz hidden (need at least 2 questions with answer choices to branch).',
      '%d quizzes hidden (need at least 2 questions with answer choices to branch).',
      ineligibleQuizzes.value.length
    ),
    ineligibleQuizzes.value.length
  )
);

const quizzesStore = useQuizzesStore();
const builderStore = useQuizBuilderStore();
const toast = useToast();
const isProUser = computed(() => isPro());

const selectedQuizId = shallowRef(null);

// Eligibility: must have at least 2 questions whose type can branch.
// Polls (single question) are intentionally excluded — there's nowhere
// to branch to. We surface a hint about hidden quizzes so the author
// understands the filter.
const BRANCHABLE_QUESTION_TYPES = new Set([
  'single', 'multi', 'multiple', 'true_false', 'truefalse', 'image', 'image_choice', 'dropdown',
]);

const eligibleQuizzes = computed(() =>
  quizzesStore.items.filter((q) => isQuizEligible(q))
);

const ineligibleQuizzes = computed(() =>
  quizzesStore.items.filter((q) => !isQuizEligible(q))
);

function isQuizEligible(q) {
  if (!q) return false;
  if (q.type === 'poll') return false;
  // The list endpoint may not include questions — eligibility falls back
  // to "permitted by type" until the user picks one. Conservative: hide
  // polls; show everything else, since we'll re-check on selection.
  return true;
}

// translators: %s is the title of the quiz being loaded.
const loadingQuizText = computed(() => sprintf(__('Loading %s…'), selectedQuizTitle.value));

const selectedQuizTitle = computed(() => {
  const q = eligibleQuizzes.value.find((x) => Number(x.id) === Number(selectedQuizId.value));
  return q?.title || __('quiz');
});

// Pro hook resolution — same shape as LogicTab inside the builder.
function readInjected() {
  return (
    (typeof window !== 'undefined' && window.Quizably?.adminHooks?.builderTab?.logic) || null
  );
}

const injected = shallowRef(readInjected());

function refreshInjected() {
  injected.value = readInjected();
}

onMounted(() => {
  refreshInjected();
  if (typeof document !== 'undefined') {
    document.addEventListener('quizably:pro-ready', refreshInjected);
  }
  if (!quizzesStore.loaded && !quizzesStore.loading) {
    quizzesStore.fetch().catch(() => {});
  }
});

onBeforeUnmount(() => {
  if (typeof document !== 'undefined') {
    document.removeEventListener('quizably:pro-ready', refreshInjected);
  }
  builderStore.reset();
});

function onSelect(value) {
  const id = Number(value);
  if (!Number.isFinite(id) || id <= 0) return;
  selectedQuizId.value = id;
  builderStore.load(id).catch((e) => {
    toast.push({
      variant: 'danger',
      title: __('Could not load quiz'),
      message: e?.message ?? __('Please try again.'),
    });
  });
}

watch(selectedQuizId, (next, prev) => {
  if (next === prev) return;
  if (next != null && Number.isFinite(Number(next))) {
    builderStore.load(Number(next)).catch(() => {});
  }
});

async function onUpdateLogic(payload) {
  if (!payload || typeof payload !== 'object') return;
  const { questionId, logic } = payload;
  if (!questionId) return;
  try {
    await builderStore.updateQuestion(questionId, { logic: logic ?? null });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save logic rule'),
      message: e?.message ?? __('Unknown error'),
    });
  }
}
</script>

<style scoped>
.logic-view {
  padding: 32px 36px 64px;
  max-width: 1400px;
  margin: 0 auto;
  font-family: var(--f-sans);
  color: var(--ink-1);
}

.logic-view__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 20px;
  padding: 14px 20px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-xs);
}

.logic-view__eyebrow {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  color: var(--brand);
  text-transform: uppercase;
  font-weight: 600;
}

.logic-view__title {
  margin: 2px 0;
  font-family: var(--f-display, var(--f-sans));
  font-size: 18px;
  font-weight: 600;
  letter-spacing: -0.005em;
  line-height: 1.25;
}

.logic-view__lede {
  margin: 0;
  font-size: 12.5px;
  color: var(--ink-3);
  max-width: 64ch;
  line-height: 1.45;
}

.logic-view__quiz-picker {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 320px;
}

.logic-view__picker-label {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-3);
  font-weight: 500;
}

.logic-view__select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  background-color: var(--bg-surface);
  /* Inline chevron — keeps it inside the field's right padding instead
     of relying on the browser's default arrow, which WordPress admin
     can override and float outside the box. */
  background-image: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%2364748b' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 12px 12px;
  padding-block: 7px 7px;
  padding-inline: 12px 32px;
  font: inherit;
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-1);
  cursor: pointer;
  outline: none;
  transition: border-color 120ms, box-shadow 120ms;
}

.logic-view__select:dir(rtl) {
  background-position: left 10px center;
}

.logic-view__select:hover {
  border-color: var(--ink-3);
}

.logic-view__select:focus {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand) 18%, transparent);
}

/* Hide IE/Edge legacy expand button. */
.logic-view__select::-ms-expand {
  display: none;
}

.logic-view__picker-hint {
  margin: 0;
  font-size: 11.5px;
  color: var(--ink-4);
}

.logic-view__loading,
.logic-view__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  text-align: center;
  padding: 60px 24px;
  color: var(--ink-3);
  font-size: 14px;
}

.logic-view__empty h2 {
  margin: 0;
  font-size: 18px;
  color: var(--ink-1);
}

.logic-view__empty p {
  margin: 0;
  max-width: 50ch;
  line-height: 1.55;
}

.logic-view__cta {
  display: inline-flex;
  align-items: center;
  padding: 9px 16px;
  background: var(--ink-1);
  color: #fff;
  border-radius: var(--r-pill);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  margin-top: 6px;
}

.logic-view__cta:hover {
  background: var(--brand);
}

.logic-view__spinner {
  display: inline-block;
  width: 18px;
  height: 18px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-logic-view-spin 700ms linear infinite;
}

@keyframes quizably-logic-view-spin {
  to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
  .logic-view__spinner { animation-duration: 0ms; }
}

.logic-view__editor {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  overflow: hidden;
}
</style>
