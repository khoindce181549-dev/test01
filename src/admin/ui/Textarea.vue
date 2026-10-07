<template>
  <div :class="['quizably-textarea', { 'quizably-textarea--error': Boolean(errorText), 'quizably-textarea--disabled': disabled }]">
    <label
      v-if="label"
      :for="resolvedId"
      class="quizably-textarea__label"
    >
      {{ label }}
      <span
        v-if="required"
        class="quizably-textarea__required"
        aria-hidden="true"
      >*</span>
    </label>
    <textarea
      :id="resolvedId"
      ref="textareaRef"
      class="quizably-textarea__control"
      :value="modelValue"
      :rows="rows"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :aria-invalid="Boolean(errorText) || undefined"
      :aria-describedby="descriptionId"
      @input="onInput"
      @change="onChange"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />
    <span
      v-if="errorText"
      :id="descriptionId"
      class="quizably-textarea__error"
    >{{ errorText }}</span>
    <span
      v-else-if="helperText"
      :id="descriptionId"
      class="quizably-textarea__helper"
    >{{ helperText }}</span>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  rows: { type: Number, default: 4 },
  placeholder: { type: String, default: '' },
  label: { type: String, default: '' },
  helperText: { type: String, default: '' },
  errorText: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  autofocus: { type: Boolean, default: false },
  id: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'change', 'blur', 'focus']);

let uidCounter = 0;
const resolvedId = computed(
  () => props.id || `quizably-textarea-${++uidCounter || Math.random().toString(36).slice(2, 9)}`
);
const descriptionId = computed(() =>
  props.errorText || props.helperText ? `${resolvedId.value}-desc` : undefined
);

const textareaRef = ref(null);

function onInput(event) {
  emit('update:modelValue', event.target.value);
}

function onChange(event) {
  emit('change', event.target.value);
}

onMounted(() => {
  if (props.autofocus && textareaRef.value) {
    textareaRef.value.focus();
  }
});
</script>

<style scoped>
.quizably-textarea {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.quizably-textarea__label {
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-2);
}

.quizably-textarea__required {
  color: var(--danger);
  margin-inline-start: 2px;
}

.quizably-textarea__control {
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-md);
  padding: 10px 14px;
  font-size: 14px;
  color: var(--ink-1);
  font-family: inherit;
  line-height: 1.5;
  resize: vertical;
  min-height: 80px;
  transition:
    border-color 150ms ease,
    box-shadow 150ms ease,
    background-color 150ms ease;
  outline: none;
  width: 100%;
}

.quizably-textarea__control::placeholder {
  color: var(--ink-4);
}

.quizably-textarea__control:hover:not(:disabled) {
  border-color: var(--border-3);
}

.quizably-textarea__control:focus {
  border-color: var(--brand);
  box-shadow: var(--shadow-focus);
}

.quizably-textarea--error .quizably-textarea__control {
  border-color: var(--danger);
}

.quizably-textarea--error .quizably-textarea__control:focus {
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.18);
}

.quizably-textarea__control:disabled {
  background: var(--bg-subtle);
  color: var(--ink-4);
  cursor: not-allowed;
}

.quizably-textarea__helper {
  font-size: 12px;
  color: var(--ink-3);
}

.quizably-textarea__error {
  font-size: 12px;
  color: var(--danger);
}
</style>
