<template>
  <div
    :class="['quizably-toast', `quizably-toast--${variant}`]"
    role="status"
    :aria-live="variant === 'danger' || variant === 'warning' ? 'assertive' : 'polite'"
  >
    <span
      class="quizably-toast__icon"
      aria-hidden="true"
    >
      <svg
        viewBox="0 0 16 16"
        width="16"
        height="16"
      >
        <template v-if="variant === 'success'">
          <path
            d="M3.5 8.5l3 3 6-7"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </template>
        <template v-else-if="variant === 'danger'">
          <circle
            cx="8"
            cy="8"
            r="6"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
          />
          <path
            d="M8 5v4M8 10.5v1"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
          />
        </template>
        <template v-else-if="variant === 'warning'">
          <path
            d="M8 2l6.5 11.5h-13z"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linejoin="round"
          />
          <path
            d="M8 7v3M8 11.5v1"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
          />
        </template>
        <template v-else>
          <circle
            cx="8"
            cy="8"
            r="6"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
          />
          <path
            d="M8 7v4M8 5v1"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
          />
        </template>
      </svg>
    </span>
    <div class="quizably-toast__content">
      <p
        v-if="title"
        class="quizably-toast__title"
      >
        {{ title }}
      </p>
      <p
        v-if="message"
        class="quizably-toast__message"
      >
        {{ message }}
      </p>
    </div>
    <button
      type="button"
      class="quizably-toast__close"
      :aria-label="__('Dismiss')"
      @click="$emit('dismiss', id)"
    >
      <svg
        viewBox="0 0 16 16"
        width="12"
        height="12"
        aria-hidden="true"
      >
        <path
          d="M3.5 3.5l9 9M12.5 3.5l-9 9"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
          stroke-linecap="round"
        />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
defineProps({
  id: { type: String, required: true },
  variant: {
    type: String,
    default: 'info',
    validator: (v) => ['info', 'success', 'warning', 'danger'].includes(v),
  },
  title: { type: String, default: '' },
  message: { type: String, default: '' },
  duration: { type: Number, default: 4000 },
});

defineEmits(['dismiss']);
</script>

<style scoped>
.quizably-toast {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-lg);
  min-width: 280px;
  max-width: 400px;
  color: var(--ink-1);
}

.quizably-toast__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  margin-top: 1px;
}

.quizably-toast--info .quizably-toast__icon {
  color: var(--info);
}
.quizably-toast--success .quizably-toast__icon {
  color: var(--success);
}
.quizably-toast--warning .quizably-toast__icon {
  color: var(--accent);
}
.quizably-toast--danger .quizably-toast__icon {
  color: var(--danger);
}

.quizably-toast__content {
  flex: 1;
  min-width: 0;
}

.quizably-toast__title {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
  line-height: 1.3;
}

.quizably-toast__message {
  margin: 2px 0 0;
  font-size: 13px;
  color: var(--ink-3);
  line-height: 1.4;
}

.quizably-toast__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: transparent;
  border: 0;
  border-radius: var(--r-xs);
  color: var(--ink-4);
  cursor: pointer;
  flex-shrink: 0;
  transition: background-color 150ms ease, color 150ms ease;
}
.quizably-toast__close:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}
</style>
