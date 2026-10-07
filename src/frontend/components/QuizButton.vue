<template>
  <button
    :type="type"
    :class="[
      'quizably-qbutton',
      `quizably-qbutton--${variant}`,
      `quizably-qbutton--${size}`,
      { 'quizably-qbutton--block': block },
    ]"
    :disabled="disabled"
    @click="onClick"
  >
    <slot />
  </button>
</template>

<script setup>
const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'ghost', 'outline'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['md', 'lg'].includes(v),
  },
  type: { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

function onClick(event) {
  if (props.disabled) return;
  emit('click', event);
}
</script>

<style scoped>
.quizably-qbutton {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-family: var(--f-sans);
  font-weight: 500;
  line-height: 1;
  /* Respects design.button_style set by the quiz author in the Design tab.
     Falls back to --r-md (rounded) when the quiz root hasn't set the var. */
  border-radius: var(--quizably-btn-radius, var(--r-md));
  border: 1px solid transparent;
  cursor: pointer;
  transition:
    background-color 160ms ease,
    border-color 160ms ease,
    color 160ms ease,
    box-shadow 160ms ease,
    transform 160ms ease;
}

.quizably-qbutton--md {
  padding: 12px 20px;
  font-size: 14px;
}

.quizably-qbutton--lg {
  padding: 16px 28px;
  font-size: 16px;
}

.quizably-qbutton--block {
  width: 100%;
}

.quizably-qbutton--primary {
  background: var(--quizably-quiz-brand);
  color: #fff;
  box-shadow: 0 1px 2px rgba(10, 10, 11, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}
.quizably-qbutton--primary:hover:not(:disabled) {
  filter: brightness(0.92);
  transform: translateY(-1px);
}
.quizably-qbutton--primary:active:not(:disabled) {
  transform: translateY(0);
}

/* Ghost and outline are coloured from the quiz-scoped tokens (Quiz.vue derives
   them from --quizably-quiz-text), not the global --bg-* / --ink-* / --border-* scale.
   That scale is light-only: on a dark quiz the text side goes white while the
   --bg-* side stays cream, which left "Back" as white text on a cream pill. The
   global token is kept only as the fallback for a button outside a .quizably-quiz root.
   See tests/js/frontend/QuizButton.tokens.test.js. */
.quizably-qbutton--outline {
  background: transparent;
  color: var(--quizably-quiz-text);
  border-color: var(--quizably-quiz-option-border, var(--border-2));
}
.quizably-qbutton--outline:hover:not(:disabled) {
  background: var(--quizably-quiz-option-bg, var(--bg-subtle));
  border-color: var(--quizably-quiz-text-subtle, var(--border-3));
}

.quizably-qbutton--ghost {
  background: var(--quizably-quiz-option-bg, var(--bg-subtle));
  color: var(--quizably-quiz-text-muted, var(--ink-3));
}
.quizably-qbutton--ghost:hover:not(:disabled) {
  color: var(--quizably-quiz-text);
  background: var(--quizably-quiz-option-bg-hover, var(--bg-muted));
}

.quizably-qbutton:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  transform: none;
}

@media (prefers-reduced-motion: reduce) {
  .quizably-qbutton {
    transition-duration: 0ms;
  }
  .quizably-qbutton--primary:hover:not(:disabled) {
    transform: none;
  }
}
</style>
