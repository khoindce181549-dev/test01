<template>
  <Modal
    :model-value="modelValue"
    :title="title"
    size="sm"
    @update:model-value="onUpdate"
  >
    <p class="quizably-confirm__message">
      <slot>{{ message }}</slot>
    </p>

    <template #footer>
      <Button
        variant="ghost"
        @click="onUpdate(false)"
      >
        {{ cancelLabel }}
      </Button>
      <Button
        :variant="danger ? 'danger' : 'primary'"
        @click="onConfirm"
      >
        {{ confirmLabel }}
      </Button>
    </template>
  </Modal>
</template>

<script setup>
import { __ } from '@shared/i18n';
import Button from './Button.vue';
import Modal from './Modal.vue';

/**
 * ConfirmDialog — "are you sure?" for actions the user should acknowledge
 * before they run (duplicate, delete, …). Built on Modal, so it inherits the
 * focus trap, Esc / backdrop dismissal and focus restore.
 *
 * The dialog closes itself on confirm *before* emitting `confirm`, so the
 * action can't be fired twice by a double click; the parent reports the
 * outcome (success / failure) with a toast.
 */
defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, required: true },
  message: { type: String, default: '' },
  confirmLabel: { type: String, default: () => __('Confirm') },
  cancelLabel: { type: String, default: () => __('Cancel') },
  /** Destructive action — styles the confirm button as danger. */
  danger: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

function onUpdate(value) {
  emit('update:modelValue', value);
}

function onConfirm() {
  emit('update:modelValue', false);
  emit('confirm');
}
</script>

<style scoped>
.quizably-confirm__message {
  margin: 0;
  font-family: var(--f-sans);
  font-size: 14px;
  line-height: 1.55;
  color: var(--ink-2);
}
</style>
