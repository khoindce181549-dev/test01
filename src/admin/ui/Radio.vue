<template>
  <label :class="['quizably-radio', { 'quizably-radio--disabled': disabled, 'quizably-radio--checked': isChecked }]">
    <input
      type="radio"
      class="quizably-radio__native"
      :checked="isChecked"
      :value="value"
      :name="name"
      :disabled="disabled"
      @change="onChange"
    >
    <span
      class="quizably-radio__circle"
      aria-hidden="true"
    >
      <span
        v-if="isChecked"
        class="quizably-radio__dot"
      />
    </span>
    <span
      v-if="label || $slots.default"
      class="quizably-radio__label"
    >
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number, Boolean, null], default: null },
  value: { type: [String, Number, Boolean], required: true },
  name: { type: String, default: '' },
  label: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isChecked = computed(() => props.modelValue === props.value);

function onChange() {
  emit('update:modelValue', props.value);
  emit('change', props.value);
}
</script>

<style scoped>
.quizably-radio {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 13px;
  color: var(--ink-2);
  user-select: none;
  padding-block: 4px 4px;
  padding-inline: 0 4px;
  border-radius: var(--r-sm);
  transition: color 120ms;
}

.quizably-radio--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quizably-radio:not(.quizably-radio--disabled):hover {
  color: var(--ink-1);
}

.quizably-radio--checked {
  color: var(--ink-1);
}

.quizably-radio__native {
  position: absolute;
  opacity: 0;
  width: 1px;
  height: 1px;
  pointer-events: none;
}

/* The control: a 16px ring whose inner area fills with brand on check.
   Box-shadow is used so the dot is a single inset shadow on the same
   element — eliminates layout shift and gives a snappier transition. */
.quizably-radio__circle {
  position: relative;
  display: inline-flex;
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  background: var(--bg-surface);
  border: 1.5px solid var(--border-3);
  border-radius: 50%;
  transition:
    border-color 140ms ease,
    box-shadow 140ms ease,
    background-color 140ms ease;
}

.quizably-radio:not(.quizably-radio--disabled):hover .quizably-radio__circle {
  border-color: var(--brand);
}

.quizably-radio__native:focus-visible + .quizably-radio__circle {
  border-color: var(--brand);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand) 18%, transparent);
}

.quizably-radio--checked .quizably-radio__circle {
  border-color: var(--brand);
  background: var(--brand);
  box-shadow: inset 0 0 0 3px var(--bg-surface);
}

.quizably-radio--checked.quizably-radio:not(.quizably-radio--disabled):focus-within .quizably-radio__circle {
  box-shadow:
    inset 0 0 0 3px var(--bg-surface),
    0 0 0 4px color-mix(in srgb, var(--brand) 18%, transparent);
}

/* The inner dot is now drawn by the inset shadow on .quizably-radio__circle —
   keep this empty so v-if="isChecked" still works for older callers. */
.quizably-radio__dot {
  display: none;
}

.quizably-radio__label {
  line-height: 1.4;
}
</style>
