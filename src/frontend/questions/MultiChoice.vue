<template>
  <div
    class="quizably-multi"
    role="group"
    :aria-label="question.title"
  >
    <button
      v-for="(answer, i) in answers"
      :key="answer.id"
      type="button"
      role="checkbox"
      :aria-checked="isSelected(answer.id)"
      :class="[
        'quizably-multi__option',
        { 'quizably-multi__option--active': isSelected(answer.id) },
      ]"
      @click="toggle(answer.id)"
    >
      <span class="quizably-multi__check">
        <svg
          v-if="isSelected(answer.id)"
          viewBox="0 0 20 20"
          width="14"
          height="14"
          aria-hidden="true"
        >
          <path
            d="M4 10l4 4 8-8"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </span>
      <span class="quizably-multi__letter">{{ letter(i) }}</span>
      <span class="quizably-multi__label">{{ answer.label }}</span>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { shuffledAnswers } from '../composables/useRandomizedAnswers.js';

const props = defineProps({
  question: { type: Object, required: true },
  value: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:value']);

const answers = computed(() => shuffledAnswers(props.question));

function letter(i) {
  return String.fromCharCode(65 + i) + '.';
}

function isSelected(id) {
  return Array.isArray(props.value) && props.value.includes(id);
}

function toggle(id) {
  const current = Array.isArray(props.value) ? [...props.value] : [];
  const idx = current.indexOf(id);
  if (idx >= 0) current.splice(idx, 1);
  else current.push(id);
  emit('update:value', current);
}
</script>

<style scoped>
.quizably-multi {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

.quizably-multi__option {
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: start;
  padding: 14px 18px;
  background: var(--quizably-quiz-option-bg, var(--bg-surface));
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  /* Respects design.button_style from the Design tab. */
  border-radius: var(--quizably-btn-radius, var(--r-lg));
  font-size: 15px;
  color: var(--quizably-quiz-text);
  transition:
    background-color 140ms ease,
    border-color 140ms ease,
    box-shadow 140ms ease;
  cursor: pointer;
  width: 100%;
}

.quizably-multi__option:hover {
  background: var(--quizably-quiz-option-bg-hover, var(--bg-subtle));
  border-color: var(--quizably-quiz-option-border, var(--border-3));
}

/* Brand-tinted, not a fixed pale orange: see SingleChoice.vue. */
.quizably-multi__option--active {
  background: color-mix(in srgb, var(--quizably-quiz-brand) 10%, transparent);
  border-color: var(--quizably-quiz-brand);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.quizably-multi__check {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: var(--r-sm);
  border: 1.5px solid var(--quizably-quiz-option-border, var(--border-3));
  color: #fff;
  flex-shrink: 0;
  transition: background-color 140ms ease, border-color 140ms ease;
}

.quizably-multi__option--active .quizably-multi__check {
  background: var(--quizably-quiz-brand);
  border-color: var(--quizably-quiz-brand);
}

.quizably-multi__letter {
  font-family: var(--f-mono);
  font-size: 12px;
  font-weight: 600;
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
  min-width: 22px;
}

.quizably-multi__label {
  flex: 1;
  line-height: 1.45;
}

@media (prefers-reduced-motion: reduce) {
  .quizably-multi__option,
  .quizably-multi__check {
    transition-duration: 0ms;
  }
}
</style>
