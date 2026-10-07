<template>
  <label :class="['quizably-checkbox', { 'quizably-checkbox--disabled': disabled, 'quizably-checkbox--checked': modelValue }]">
    <input
      type="checkbox"
      class="quizably-checkbox__native"
      :checked="modelValue"
      :disabled="disabled"
      @change="onChange"
    >
    <span
      class="quizably-checkbox__box"
      aria-hidden="true"
    >
      <svg
        v-if="modelValue"
        viewBox="0 0 16 16"
        width="12"
        height="12"
      >
        <path
          d="M3.5 8.5l3 3 6-7"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </span>
    <span
      v-if="label || $slots.default"
      class="quizably-checkbox__label"
    >
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change']);

function onChange(event) {
  emit('update:modelValue', event.target.checked);
  emit('change', event.target.checked);
}
</script>

<style scoped>
.quizably-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: var(--ink-2);
  user-select: none;
}

.quizably-checkbox--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quizably-checkbox__native {
  position: absolute;
  opacity: 0;
  width: 1px;
  height: 1px;
  pointer-events: none;
}

.quizably-checkbox__box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  background: var(--bg-surface);
  border: 1px solid var(--border-3);
  border-radius: var(--r-xs);
  color: #fff;
  transition:
    background-color 150ms ease,
    border-color 150ms ease,
    box-shadow 150ms ease;
}

.quizably-checkbox__native:focus-visible + .quizably-checkbox__box {
  outline: 2px solid var(--brand);
  outline-offset: 2px;
  box-shadow: var(--shadow-focus);
}

.quizably-checkbox--checked .quizably-checkbox__box {
  background: var(--brand);
  border-color: var(--brand);
}

.quizably-checkbox:not(.quizably-checkbox--disabled):hover .quizably-checkbox__box {
  border-color: var(--brand);
}

.quizably-checkbox__label {
  line-height: 1.4;
}
</style>
