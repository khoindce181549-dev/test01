<template>
  <div
    v-if="answers.length === 0"
    class="quizably-dropdown quizably-dropdown--empty"
    role="status"
  >
    {{ __('No answer options have been configured for this question yet.') }}
  </div>
  <div
    v-else
    class="quizably-dropdown"
  >
    <select
      class="quizably-dropdown__select"
      :class="{ 'quizably-dropdown__select--chosen': hasValue }"
      :value="hasValue ? String(value) : ''"
      :aria-label="question.title"
      @change="onChange"
    >
      <option
        value=""
        disabled
      >
        {{ placeholder }}
      </option>
      <option
        v-for="answer in answers"
        :key="answer.id"
        :value="String(answer.id)"
      >
        {{ answer.label }}
      </option>
    </select>
    <span
      class="quizably-dropdown__chevron"
      aria-hidden="true"
    >
      <svg
        viewBox="0 0 20 20"
        width="14"
        height="14"
      >
        <path
          d="M5 8l5 5 5-5"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </span>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed } from 'vue';
import { shuffledAnswers } from '../composables/useRandomizedAnswers.js';

/**
 * Dropdown - a single-choice question shown as a <select>.
 *
 * Same data as Single choice (the visitor picks one of the question's answers,
 * scored by answer id); only the control differs. Handy when there are many
 * options, or on a small screen.
 *
 * It does NOT auto-advance. Some browsers fire `change` on every arrow-key
 * press while a <select> is closed, so advancing on change would send a
 * keyboard user to the next question the moment they start browsing options.
 * The visitor moves on with Next, like any other question that needs a
 * deliberate answer.
 */
const props = defineProps({
  question: { type: Object, required: true },
  value: { type: [String, Number, null], default: null },
  // Accepted for the shared question-component contract; intentionally unused.
  autoAdvance: { type: Boolean, default: true },
});

const emit = defineEmits(['update:value', 'advance']);

const answers = computed(() => shuffledAnswers(props.question));
const hasValue = computed(() => props.value !== null && props.value !== undefined && props.value !== '');
const placeholder = computed(() => props.question?.settings?.placeholder || __('Choose an answer'));

function onChange(event) {
  const raw = event.target.value;
  if (raw === '') {
    emit('update:value', null);
    return;
  }
  // <option value> is always a string; hand back the answer's own id (number
  // or string) so it matches what the other choice types report.
  const match = answers.value.find((a) => String(a.id) === raw);
  emit('update:value', match ? match.id : raw);
}
</script>

<style scoped>
.quizably-dropdown {
  position: relative;
}

.quizably-dropdown--empty {
  padding: 16px 18px;
  background: var(--quizably-quiz-option-bg, var(--bg-subtle));
  border: 1px dashed var(--quizably-quiz-option-border, var(--border-2));
  border-radius: var(--r-md);
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
  font-size: 14px;
  text-align: center;
}

.quizably-dropdown__select {
  display: block;
  width: 100%;
  appearance: none;
  -webkit-appearance: none;
  padding-block: 15px;
  padding-inline: 18px 46px; /* end side clears the chevron */
  font-family: inherit;
  font-size: 15px;
  line-height: 1.4;
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
  background: var(--quizably-quiz-option-bg, var(--bg-surface));
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  border-radius: var(--quizably-btn-radius, var(--r-lg));
  cursor: pointer;
  transition: border-color 140ms ease, box-shadow 140ms ease;
}

/* The open list is drawn by the browser, not the page, and stays light on most
   platforms whatever the page theme is. On a dark template the select's own
   text is white, and the options inherit it - white on the browser's white list.
   Explicit colours keep every option readable in any template. */
.quizably-dropdown__select option {
  color: #1f2933;
  background: #ffffff;
}

/* Once an answer is picked, the text reads as an answer, not a prompt. */
.quizably-dropdown__select--chosen {
  color: var(--quizably-quiz-text);
  border-color: var(--quizably-quiz-brand);
  /* Brand-tinted, not a fixed pale orange: see SingleChoice.vue. */
  background: color-mix(in srgb, var(--quizably-quiz-brand) 10%, transparent);
}

.quizably-dropdown__select:hover {
  border-color: var(--quizably-quiz-brand);
}

.quizably-dropdown__select:focus-visible {
  outline: none;
  border-color: var(--quizably-quiz-brand);
  box-shadow: var(--shadow-focus);
}

.quizably-dropdown__chevron {
  position: absolute;
  top: 50%;
  inset-inline-end: 18px;
  transform: translateY(-50%);
  display: inline-flex;
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
  pointer-events: none;
}

@media (prefers-reduced-motion: reduce) {
  .quizably-dropdown__select {
    transition-duration: 0ms;
  }
}
</style>
