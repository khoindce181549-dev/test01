<template>
  <div :class="['quizably-input', { 'quizably-input--error': Boolean(errorText), 'quizably-input--disabled': disabled }]">
    <label
      v-if="label"
      :for="resolvedId"
      class="quizably-input__label"
    >
      {{ label }}
      <span
        v-if="required"
        class="quizably-input__required"
        aria-hidden="true"
      >*</span>
    </label>
    <input
      :id="resolvedId"
      ref="inputRef"
      class="quizably-input__control"
      :type="type"
      :dir="ltr ? 'ltr' : undefined"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :aria-invalid="Boolean(errorText) || undefined"
      :aria-describedby="descriptionId"
      @input="onInput"
      @change="onChange"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    >
    <span
      v-if="errorText"
      :id="descriptionId"
      class="quizably-input__error"
    >{{ errorText }}</span>
    <span
      v-else-if="helperText"
      :id="descriptionId"
      class="quizably-input__helper"
    >{{ helperText }}</span>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  label: { type: String, default: '' },
  helperText: { type: String, default: '' },
  errorText: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  autofocus: { type: Boolean, default: false },
  // Force left-to-right for machine values (slugs, URLs, keys) so RTL pages don't scramble them.
  ltr: { type: Boolean, default: false },
  id: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'change', 'blur', 'focus']);

let uidCounter = 0;
const resolvedId = computed(() => props.id || `quizably-input-${++uidCounter || Math.random().toString(36).slice(2, 9)}`);
const descriptionId = computed(() =>
  props.errorText || props.helperText ? `${resolvedId.value}-desc` : undefined
);

const inputRef = ref(null);

function onInput(event) {
  emit('update:modelValue', event.target.value);
}

function onChange(event) {
  emit('change', event.target.value);
}

onMounted(() => {
  if (props.autofocus && inputRef.value) {
    inputRef.value.focus();
  }
});
</script>

<style scoped>
.quizably-input {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.quizably-input__label {
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-2);
}

.quizably-input__required {
  color: var(--danger);
  margin-inline-start: 2px;
}

.quizably-input__control {
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-md);
  padding: 10px 14px;
  font-size: 14px;
  color: var(--ink-1);
  transition:
    border-color 150ms ease,
    box-shadow 150ms ease,
    background-color 150ms ease;
  outline: none;
  width: 100%;
}

.quizably-input__control::placeholder {
  color: var(--ink-4);
}

.quizably-input__control:hover:not(:disabled) {
  border-color: var(--border-3);
}

.quizably-input__control:focus {
  border-color: var(--brand);
  box-shadow: var(--shadow-focus);
}

.quizably-input--error .quizably-input__control {
  border-color: var(--danger);
}

.quizably-input--error .quizably-input__control:focus {
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.18);
}

.quizably-input__control:disabled {
  background: var(--bg-subtle);
  color: var(--ink-4);
  cursor: not-allowed;
}

.quizably-input__helper {
  font-size: 12px;
  color: var(--ink-3);
}

.quizably-input__error {
  font-size: 12px;
  color: var(--danger);
}
</style>
