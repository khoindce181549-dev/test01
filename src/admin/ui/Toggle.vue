<template>
  <button
    type="button"
    role="switch"
    :class="[
      'quizably-toggle',
      `quizably-toggle--${size}`,
      { 'quizably-toggle--on': modelValue, 'quizably-toggle--disabled': disabled },
    ]"
    :aria-checked="modelValue ? 'true' : 'false'"
    :aria-disabled="disabled || undefined"
    :disabled="disabled"
    @click="onClick"
  >
    <span
      class="quizably-toggle__knob"
      aria-hidden="true"
    />
  </button>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md'].includes(v),
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

function onClick() {
  if (props.disabled) return;
  const next = !props.modelValue;
  emit('update:modelValue', next);
  emit('change', next);
}
</script>

<style scoped>
.quizably-toggle {
  position: relative;
  display: inline-block;
  border: 0;
  padding: 0;
  cursor: pointer;
  border-radius: var(--r-pill);
  background: var(--border-3);
  transition: background-color 150ms ease;
  flex-shrink: 0;
  vertical-align: middle;
}

.quizably-toggle--md {
  width: 36px;
  height: 20px;
}

.quizably-toggle--sm {
  width: 28px;
  height: 16px;
}

.quizably-toggle__knob {
  position: absolute;
  top: 2px;
  inset-inline-start: 2px;
  background: #fff;
  border-radius: 50%;
  box-shadow: var(--shadow-sm);
  transition: transform 150ms ease;
}

.quizably-toggle--md .quizably-toggle__knob {
  width: 16px;
  height: 16px;
}

.quizably-toggle--sm .quizably-toggle__knob {
  width: 12px;
  height: 12px;
}

.quizably-toggle--on {
  background: var(--brand);
}

.quizably-toggle--md.quizably-toggle--on .quizably-toggle__knob {
  transform: translateX(16px);
}

.quizably-toggle--sm.quizably-toggle--on .quizably-toggle__knob {
  transform: translateX(12px);
}

.quizably-toggle--md.quizably-toggle--on .quizably-toggle__knob:dir(rtl) {
  transform: translateX(-16px);
}

.quizably-toggle--sm.quizably-toggle--on .quizably-toggle__knob:dir(rtl) {
  transform: translateX(-12px);
}

.quizably-toggle--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (prefers-reduced-motion: reduce) {
  .quizably-toggle,
  .quizably-toggle__knob {
    transition-duration: 0ms;
  }
}
</style>
