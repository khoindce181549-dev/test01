<template>
  <div
    class="quizably-tf"
    role="radiogroup"
    :aria-label="question.title"
  >
    <button
      v-for="answer in answers"
      :key="answer.id"
      type="button"
      role="radio"
      :aria-checked="value === answer.id"
      :class="[
        'quizably-tf__option',
        { 'quizably-tf__option--active': value === answer.id },
      ]"
      @click="select(answer.id)"
    >
      {{ answer.label }}
    </button>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed, nextTick } from 'vue';

const props = defineProps({
  question: { type: Object, required: true },
  value: { type: [String, Number, null], default: null },
  autoAdvance: { type: Boolean, default: true },
});

const emit = defineEmits(['update:value', 'advance']);

// If authors didn't supply answers, synthesize a true/false pair keyed by the
// question id so local scoring still has something to track.
const answers = computed(() => {
  const supplied = props.question.answers ?? [];
  if (supplied.length >= 2) return supplied.slice(0, 2);
  return [
    { id: `${props.question.id}-true`, label: __('True'), is_correct: true },
    { id: `${props.question.id}-false`, label: __('False'), is_correct: false },
  ];
});

function select(id) {
  emit('update:value', id);
  if (props.autoAdvance) {
    nextTick(() => emit('advance'));
  }
}
</script>

<style scoped>
.quizably-tf {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.quizably-tf__option {
  padding: 28px 20px;
  /* Quiz-theme tokens, like the other answer controls: the label is quiz text, so
     a fixed white fill left it white-on-white on a dark quiz. */
  background: var(--quizably-quiz-option-bg, var(--bg-surface));
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  /* Respects design.button_style from the Design tab. */
  border-radius: var(--quizably-btn-radius, var(--r-lg));
  font-family: var(--f-display);
  font-size: 28px;
  font-weight: 600;
  letter-spacing: -0.01em;
  color: var(--quizably-quiz-text);
  cursor: pointer;
  transition:
    background-color 140ms ease,
    border-color 140ms ease,
    box-shadow 140ms ease,
    transform 140ms ease;
}

.quizably-tf__option:hover {
  background: var(--quizably-quiz-option-bg-hover, var(--bg-subtle));
  border-color: var(--quizably-quiz-option-border, var(--border-3));
  transform: translateY(-1px);
}

.quizably-tf__option--active {
  background: var(--quizably-quiz-brand);
  border-color: var(--quizably-quiz-brand);
  color: #fff;
}

@media (max-width: 640px) {
  .quizably-tf {
    grid-template-columns: 1fr;
  }
  .quizably-tf__option {
    padding: 22px;
    font-size: 22px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-tf__option {
    transition-duration: 0ms;
  }
  .quizably-tf__option:hover {
    transform: none;
  }
}
</style>
