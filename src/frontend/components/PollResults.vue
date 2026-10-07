<template>
  <div
    class="quizably-poll"
    role="group"
    :aria-label="__('Poll results')"
  >
    <p
      v-if="state === 'loading'"
      class="quizably-poll__note"
    >
      {{ __('Loading results...') }}
    </p>
    <p
      v-else-if="state === 'error'"
      class="quizably-poll__note"
    >
      {{ __('Results are not available right now.') }}
    </p>
    <template v-else>
      <ul class="quizably-poll__list">
        <li
          v-for="o in options"
          :key="o.answer_id"
          :class="['quizably-poll__row', { 'is-mine': mine.has(o.answer_id) }]"
        >
          <div class="quizably-poll__head">
            <span class="quizably-poll__label">
              {{ o.label }}
              <span
                v-if="mine.has(o.answer_id)"
                class="quizably-poll__you"
              >{{ __('Your vote') }}</span>
            </span>
            <span class="quizably-poll__pct">{{ formatPercent(o.percent) }}%</span>
          </div>
          <div
            class="quizably-poll__track"
            aria-hidden="true"
          >
            <div
              class="quizably-poll__bar"
              :style="{ width: `${o.percent}%` }"
            />
          </div>
          <span class="quizably-poll__votes">{{ sprintf(_n('%d vote', '%d votes', o.votes), o.votes) }}</span>
        </li>
      </ul>
      <p class="quizably-poll__total">
        {{ sprintf(_n('%d vote in total', '%d votes in total', total), total) }}
      </p>
    </template>
  </div>
</template>

<script setup>
import { __, _n, sprintf } from '@shared/i18n';
import { computed, onMounted, ref } from 'vue';
import { api } from '../api/client';

const props = defineProps({
  quiz: { type: Object, required: true },
  // The visitor's own answers, keyed by question id.
  answers: { type: Object, default: () => ({}) },
});

const state = ref('loading'); // loading | ready | error
const options = ref([]);
const total = ref(0);

const question = computed(() => props.quiz?.questions?.[0] ?? null);

// The answer ids this visitor picked, to mark "Your vote".
const mine = computed(() => {
  const v = question.value ? props.answers?.[question.value.id] : null;
  const ids = Array.isArray(v) ? v : v != null ? [v] : [];
  return new Set(ids.map((x) => Number(x)));
});

function formatPercent(p) {
  return Number.isInteger(p) ? String(p) : p.toFixed(1);
}

// Demo mode has no server: show only the visitor's own vote.
function demoResults() {
  const picked = mine.value;
  const list = (question.value?.answers ?? []).map((a) => ({
    answer_id: Number(a.id),
    label: a.label,
    votes: picked.has(Number(a.id)) ? 1 : 0,
    percent: picked.has(Number(a.id)) ? 100 : 0,
  }));
  return { options: list, total: picked.size > 0 ? 1 : 0 };
}

onMounted(async () => {
  if (props.quiz?._demo || !props.quiz?.uuid) {
    const d = demoResults();
    options.value = d.options;
    total.value = d.total;
    state.value = 'ready';
    return;
  }
  try {
    const res = await api.get(`quiz/${props.quiz.uuid}/poll-results`);
    const data = res?.data ?? res;
    options.value = Array.isArray(data?.options) ? data.options : [];
    total.value = Number(data?.total ?? 0);
    state.value = 'ready';
  } catch (e) {
    state.value = 'error';
  }
});
</script>

<style>
.quizably-poll {
  margin: 20px 0;
  text-align: start;
}
.quizably-poll__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 14px;
}
.quizably-poll__head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 15px;
  color: var(--quizably-quiz-text, inherit);
}
.quizably-poll__label {
  font-weight: 600;
}
.quizably-poll__you {
  margin-inline-start: 8px;
  padding: 2px 8px;
  border-radius: 999px;
  background: var(--quizably-quiz-option-bg, var(--bg-subtle));
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  font-size: 12px;
  font-weight: 600;
  color: var(--quizably-quiz-text-muted, var(--ink-2));
}
.quizably-poll__pct {
  font-variant-numeric: tabular-nums;
  color: var(--quizably-quiz-text-muted, var(--ink-2));
}
.quizably-poll__track {
  margin-top: 6px;
  height: 10px;
  border-radius: 999px;
  background: var(--quizably-quiz-option-bg, var(--bg-subtle));
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  overflow: hidden;
}
.quizably-poll__bar {
  height: 100%;
  border-radius: 999px;
  background: var(--quizably-quiz-brand, #4f46e5);
  transition: width 0.4s ease;
}
.quizably-poll__row.is-mine .quizably-poll__bar {
  background: var(--quizably-quiz-accent, var(--quizably-quiz-brand, #4f46e5));
}
.quizably-poll__votes {
  display: block;
  margin-top: 4px;
  font-size: 13px;
  color: var(--quizably-quiz-text-muted, var(--ink-2));
}
.quizably-poll__total,
.quizably-poll__note {
  margin: 14px 0 0;
  font-size: 14px;
  color: var(--quizably-quiz-text-muted, var(--ink-2));
}
@media (prefers-reduced-motion: reduce) {
  .quizably-poll__bar {
    transition: none;
  }
}
</style>
