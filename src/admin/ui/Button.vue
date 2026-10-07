<template>
  <component
    :is="href ? 'a' : 'button'"
    :href="href || undefined"
    :type="href ? undefined : type"
    :class="[
      'quizably-button',
      `quizably-button--${variant}`,
      `quizably-button--${size}`,
      { 'quizably-button--loading': loading },
    ]"
    :disabled="href ? undefined : isDisabled"
    :aria-disabled="href && isDisabled ? 'true' : undefined"
    :aria-busy="loading || undefined"
    @click="onClick"
  >
    <span
      v-if="loading"
      class="quizably-button__spinner"
      aria-hidden="true"
    >
      <svg
        viewBox="0 0 24 24"
        width="14"
        height="14"
      >
        <circle
          cx="12"
          cy="12"
          r="9"
          fill="none"
          stroke="currentColor"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-dasharray="40 60"
        />
      </svg>
    </span>
    <span
      v-else-if="$slots['icon-left']"
      class="quizably-button__icon quizably-button__icon--left"
    >
      <slot name="icon-left" />
    </span>
    <span class="quizably-button__label"><slot /></span>
    <span
      v-if="!loading && $slots['icon-right']"
      class="quizably-button__icon quizably-button__icon--right"
    >
      <slot name="icon-right" />
    </span>
  </component>
</template>

<script setup>
/**
 * Renders an <a> when given `href` (a link that should look like a button,
 * e.g. to an external page); a <button> otherwise. Pass target/rel as
 * attributes — they fall through to the root element.
 */
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'outline', 'ghost', 'danger'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md'].includes(v),
  },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
  href: { type: String, default: '' },
});

const emit = defineEmits(['click']);

const isDisabled = computed(() => props.disabled || props.loading);

function onClick(event) {
  if (isDisabled.value) {
    // A disabled <button> never fires click; a link has to be stopped by hand.
    if (props.href) event.preventDefault();
    return;
  }
  emit('click', event);
}
</script>

<style scoped>
.quizably-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border-radius: var(--r-md);
  font-family: inherit;
  font-weight: 500;
  line-height: 1;
  white-space: nowrap;
  transition:
    background-color 150ms ease,
    border-color 150ms ease,
    color 150ms ease,
    box-shadow 150ms ease,
    opacity 150ms ease;
  border: 1px solid transparent;
  cursor: pointer;
  text-decoration: none;
}

.quizably-button--sm {
  padding: 8px 12px;
  font-size: 13px;
}

.quizably-button--md {
  padding: 10px 18px;
  font-size: 14px;
}

.quizably-button--primary {
  background: var(--brand);
  color: #fff;
  box-shadow: 0 1px 2px rgba(79, 70, 229, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}
.quizably-button--primary:hover:not(:disabled) {
  background: var(--brand-hover);
}

.quizably-button--outline {
  background: var(--bg-surface);
  color: var(--ink-2);
  border-color: var(--border-2);
  box-shadow: var(--shadow-xs);
}
.quizably-button--outline:hover:not(:disabled) {
  background: var(--bg-subtle);
  border-color: var(--border-3);
}

.quizably-button--ghost {
  background: transparent;
  color: var(--ink-2);
}
.quizably-button--ghost:hover:not(:disabled) {
  background: var(--bg-subtle);
}

.quizably-button--danger {
  background: var(--danger);
  color: #fff;
  box-shadow: 0 1px 2px rgba(220, 38, 38, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}
.quizably-button--danger:hover:not(:disabled) {
  background: #B91C1C;
}

.quizably-button:disabled,
.quizably-button[aria-disabled='true'] {
  opacity: 0.4;
  cursor: not-allowed;
}

.quizably-button__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.quizably-button__icon :deep(svg) {
  width: 14px;
  height: 14px;
}

.quizably-button__spinner {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  animation: quizably-button-spin 700ms linear infinite;
}

@keyframes quizably-button-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-button__spinner {
    animation-duration: 0ms;
  }
}
</style>
