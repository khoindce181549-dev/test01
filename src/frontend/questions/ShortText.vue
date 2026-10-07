<template>
  <div class="quizably-shorttext">
    <input
      class="quizably-shorttext__input"
      type="text"
      autocomplete="off"
      :value="text"
      :maxlength="maxLength"
      :placeholder="placeholder"
      :aria-label="question.title"
      @input="onInput"
      @keydown.enter.prevent="emit('advance')"
    >
    <span
      v-if="text.length > 0"
      class="quizably-shorttext__count"
      :class="{ 'quizably-shorttext__count--near': remaining <= Math.max(10, Math.round(maxLength * 0.1)) }"
      aria-live="off"
    >
      {{ text.length }} / {{ maxLength }}
    </span>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed } from 'vue';
import { DEFAULT_TEXT_ANSWER_LENGTH, MAX_TEXT_ANSWER_LENGTH } from '@shared/questionTypes.js';

/**
 * Short text - the visitor types a free-text answer.
 *
 * The answer is a VALUE, not one of the question's answer rows: it is saved as
 * text and does not affect the score or the result. Enter moves on (through
 * the normal Next check, so a required question can't be skipped by Enter).
 *
 * Reads what the editor saves: `settings.text.placeholder` and
 * `settings.text.max_length`. The limit can be lowered per question, never
 * raised past what the server keeps.
 */
const props = defineProps({
  question: { type: Object, required: true },
  value: { type: [String, Number, null], default: null },
  // Accepted for the shared question-component contract; intentionally unused.
  autoAdvance: { type: Boolean, default: true },
});

const emit = defineEmits(['update:value', 'advance']);

const config = computed(() => {
  const s = props.question?.settings?.text;
  return s && typeof s === 'object' ? s : {};
});

const maxLength = computed(() => {
  const n = Math.floor(Number(config.value.max_length));
  if (!Number.isFinite(n) || n < 1) return DEFAULT_TEXT_ANSWER_LENGTH;
  return Math.min(n, MAX_TEXT_ANSWER_LENGTH);
});

const placeholder = computed(() => String(config.value.placeholder || '') || __('Your answer…'));

const text = computed(() => (props.value === null || props.value === undefined ? '' : String(props.value)));
const remaining = computed(() => Math.max(0, maxLength.value - text.value.length));

function onInput(event) {
  // `maxlength` stops typing, but a paste can still slip past on some platforms.
  emit('update:value', event.target.value.slice(0, maxLength.value));
}
</script>

<style scoped>
.quizably-shorttext {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.quizably-shorttext__input {
  display: block;
  width: 100%;
  padding: 15px 18px;
  font-family: inherit;
  font-size: 15px;
  line-height: 1.4;
  color: var(--quizably-quiz-text);
  background: var(--quizably-quiz-option-bg, var(--bg-surface));
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  border-radius: var(--quizably-btn-radius, var(--r-lg));
  transition: border-color 140ms ease, box-shadow 140ms ease;
}

.quizably-shorttext__input::placeholder {
  color: var(--quizably-quiz-text-subtle, var(--ink-4));
}

.quizably-shorttext__input:hover {
  border-color: var(--quizably-quiz-brand);
}

.quizably-shorttext__input:focus {
  outline: none;
  border-color: var(--quizably-quiz-brand);
  box-shadow: var(--shadow-focus);
}

.quizably-shorttext__count {
  align-self: flex-end;
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.04em;
  color: var(--quizably-quiz-text-subtle, var(--ink-4));
  /* "12 / 100" is a numeric readout: keep it LTR so an RTL page can't reorder it. */
  direction: ltr;
  unicode-bidi: isolate;
}

.quizably-shorttext__count--near {
  color: var(--danger);
}

@media (prefers-reduced-motion: reduce) {
  .quizably-shorttext__input {
    transition-duration: 0ms;
  }
}
</style>
