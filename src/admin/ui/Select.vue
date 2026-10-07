<template>
  <div :class="['quizably-select', { 'quizably-select--error': Boolean(errorText), 'quizably-select--disabled': disabled }]">
    <label
      v-if="label"
      :for="resolvedId"
      class="quizably-select__label"
    >
      {{ label }}
      <span
        v-if="required"
        class="quizably-select__required"
        aria-hidden="true"
      >*</span>
    </label>
    <div class="quizably-select__wrap">
      <select
        :id="resolvedId"
        class="quizably-select__control"
        :value="modelValue"
        :disabled="disabled"
        :required="required"
        :aria-invalid="Boolean(errorText) || undefined"
        :aria-describedby="descriptionId"
        @change="onChange"
        @blur="$emit('blur', $event)"
        @focus="$emit('focus', $event)"
      >
        <option
          v-if="placeholder"
          value=""
          disabled
        >
          {{ placeholder }}
        </option>
        <option
          v-for="opt in options"
          :key="opt.value"
          :value="opt.value"
          :disabled="opt.disabled || false"
        >
          {{ opt.label }}
        </option>
      </select>
      <span
        class="quizably-select__chevron"
        aria-hidden="true"
      >
        <svg
          viewBox="0 0 16 16"
          width="12"
          height="12"
        >
          <path
            d="M4 6l4 4 4-4"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </span>
    </div>
    <span
      v-if="errorText"
      :id="descriptionId"
      class="quizably-select__error"
    >{{ errorText }}</span>
    <span
      v-else-if="helperText"
      :id="descriptionId"
      class="quizably-select__helper"
    >{{ helperText }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number, Boolean, null], default: '' },
  options: {
    type: Array,
    default: () => [],
    validator: (arr) =>
      Array.isArray(arr) && arr.every((o) => o && 'value' in o && 'label' in o),
  },
  placeholder: { type: String, default: '' },
  label: { type: String, default: '' },
  helperText: { type: String, default: '' },
  errorText: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  id: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'change', 'blur', 'focus']);

let uidCounter = 0;
const resolvedId = computed(
  () => props.id || `quizably-select-${++uidCounter || Math.random().toString(36).slice(2, 9)}`
);
const descriptionId = computed(() =>
  props.errorText || props.helperText ? `${resolvedId.value}-desc` : undefined
);

function onChange(event) {
  emit('update:modelValue', event.target.value);
  emit('change', event.target.value);
}
</script>

<style scoped>
.quizably-select {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.quizably-select__label {
  font-size: 12.5px;
  font-weight: 500;
  color: var(--ink-2);
}

.quizably-select__required {
  color: var(--danger);
  margin-inline-start: 2px;
}

.quizably-select__wrap {
  position: relative;
  display: block;
  width: 100%;
}

.quizably-select__control {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  padding-block: 7px 7px;
  padding-inline: 12px 32px;
  font-size: 13px;
  color: var(--ink-1);
  font-family: inherit;
  font-weight: 500;
  width: 100%;
  outline: none;
  cursor: pointer;
  transition:
    border-color 120ms ease,
    box-shadow 120ms ease,
    background-color 120ms ease;
}

/* Hide IE/Edge legacy expand button so our SVG chevron is the only arrow. */
.quizably-select__control::-ms-expand {
  display: none;
}

.quizably-select__control:hover:not(:disabled) {
  border-color: var(--ink-3);
}

.quizably-select__control:focus {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand) 18%, transparent);
}

.quizably-select__control:disabled {
  background: var(--bg-subtle);
  color: var(--ink-4);
  cursor: not-allowed;
}

.quizably-select--error .quizably-select__control {
  border-color: var(--danger);
}

.quizably-select--error .quizably-select__control:focus {
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--danger) 22%, transparent);
}

.quizably-select__chevron {
  position: absolute;
  inset-inline-end: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ink-3);
  pointer-events: none;
  display: inline-flex;
  transition: color 120ms;
}

.quizably-select__control:hover:not(:disabled) ~ .quizably-select__chevron,
.quizably-select__control:focus ~ .quizably-select__chevron {
  color: var(--brand);
}

.quizably-select__helper {
  font-size: 11.5px;
  color: var(--ink-3);
}

.quizably-select__error {
  font-size: 11.5px;
  color: var(--danger);
}
</style>
