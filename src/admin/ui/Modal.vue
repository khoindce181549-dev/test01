<template>
  <Teleport to="body">
    <Transition name="quizably-modal">
      <div
        v-if="modelValue"
        class="quizably-modal__backdrop"
        :data-size="size"
        @mousedown.self="onBackdropMouseDown"
        @click.self="onBackdropClick"
      >
        <div
          ref="dialogRef"
          class="quizably-modal"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          tabindex="-1"
          @keydown="onKeyDown"
        >
          <header class="quizably-modal__header">
            <h2
              :id="titleId"
              class="quizably-modal__title"
            >
              {{ title }}
            </h2>
            <button
              type="button"
              class="quizably-modal__close"
              :aria-label="__('Close')"
              @click="close"
            >
              <svg
                viewBox="0 0 16 16"
                width="14"
                height="14"
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
          </header>
          <div class="quizably-modal__body">
            <slot />
          </div>
          <footer
            v-if="$slots.footer"
            class="quizably-modal__footer"
          >
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
  },
  closeOnBackdrop: { type: Boolean, default: true },
  closeOnEsc: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'close']);

const dialogRef = ref(null);
let previouslyFocused = null;
let backdropMouseDownOnBackdrop = false;

const titleId = computed(
  () => `quizably-modal-title-${Math.random().toString(36).slice(2, 9)}`
);

function close() {
  emit('update:modelValue', false);
  emit('close');
}

function onBackdropMouseDown(event) {
  // Track if mouse down started on backdrop — prevents close when user
  // drags from inside dialog and releases on backdrop.
  backdropMouseDownOnBackdrop = event.target === event.currentTarget;
}

function onBackdropClick() {
  if (!props.closeOnBackdrop) return;
  if (!backdropMouseDownOnBackdrop) return;
  backdropMouseDownOnBackdrop = false;
  close();
}

function onKeyDown(event) {
  if (event.key === 'Escape' && props.closeOnEsc) {
    event.preventDefault();
    close();
    return;
  }
  if (event.key === 'Tab') {
    trapFocus(event);
  }
}

function getFocusable() {
  if (!dialogRef.value) return [];
  return Array.from(
    dialogRef.value.querySelectorAll(
      'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )
  );
}

function trapFocus(event) {
  const focusables = getFocusable();
  if (focusables.length === 0) {
    event.preventDefault();
    dialogRef.value?.focus();
    return;
  }
  const first = focusables[0];
  const last = focusables[focusables.length - 1];
  const active = document.activeElement;

  if (event.shiftKey && active === first) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && active === last) {
    event.preventDefault();
    first.focus();
  }
}

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      previouslyFocused = document.activeElement;
      await nextTick();
      const focusables = getFocusable();
      if (focusables.length > 0) {
        focusables[0].focus();
      } else {
        dialogRef.value?.focus();
      }
    } else if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
      previouslyFocused.focus();
      previouslyFocused = null;
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
    previouslyFocused.focus();
  }
});
</script>

<style scoped>
.quizably-modal__backdrop {
  position: fixed;
  /* Full-viewport layer. It deliberately covers the WP admin bar as well:
     reserving the bar's height (32px, 46px on mobile) left the bar bright
     above a dimmed page — and, in the builder's fullscreen mode where the
     bar is hidden, an undimmed 32px strip with nothing in it. */
  top: 0;
  inset-inline-end: 0;
  bottom: 0;
  inset-inline-start: 0;
  /* Above the WP admin bar (99999) so the overlay dims it too, but below
     WP's own media-library modal (160000) which opens from inside dialogs. */
  z-index: 100000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  background: rgba(10, 10, 11, 0.55);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  overflow-y: auto;
}

.quizably-modal {
  width: 100%;
  background: var(--bg-surface);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-xl), 0 0 0 1px var(--border-1);
  color: var(--ink-1);
  outline: none;
  display: flex;
  flex-direction: column;
  /* Leave 24px breathing room top + bottom of the viewport */
  max-height: calc(100vh - 48px);
}

.quizably-modal__backdrop[data-size='sm'] .quizably-modal {
  max-width: 420px;
}
.quizably-modal__backdrop[data-size='md'] .quizably-modal {
  max-width: 560px;
}
.quizably-modal__backdrop[data-size='lg'] .quizably-modal {
  max-width: 760px;
}
.quizably-modal__backdrop[data-size='xl'] .quizably-modal {
  max-width: 1080px;
}

.quizably-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border-1);
  gap: 16px;
}

.quizably-modal__title {
  font-family: var(--f-display);
  font-size: 22px;
  font-weight: 500;
  letter-spacing: -0.015em;
  color: var(--ink-1);
  margin: 0;
  line-height: 1.2;
}

.quizably-modal__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: transparent;
  border: 0;
  border-radius: var(--r-md);
  color: var(--ink-3);
  cursor: pointer;
  transition: background-color 150ms ease, color 150ms ease;
}
.quizably-modal__close:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.quizably-modal__body {
  padding: 20px 24px;
  overflow-y: auto;
  flex: 1;
}

.quizably-modal__footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 8px;
  padding: 16px 24px;
  border-top: 1px solid var(--border-1);
  background: var(--bg-canvas);
  border-end-start-radius: var(--r-xl);
  border-end-end-radius: var(--r-xl);
}

.quizably-modal-enter-active,
.quizably-modal-leave-active {
  transition: opacity 180ms ease;
}
.quizably-modal-enter-active .quizably-modal,
.quizably-modal-leave-active .quizably-modal {
  transition: transform 180ms ease, opacity 180ms ease;
}
.quizably-modal-enter-from,
.quizably-modal-leave-to {
  opacity: 0;
}
.quizably-modal-enter-from .quizably-modal,
.quizably-modal-leave-to .quizably-modal {
  transform: translateY(8px) scale(0.98);
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .quizably-modal-enter-active,
  .quizably-modal-leave-active,
  .quizably-modal-enter-active .quizably-modal,
  .quizably-modal-leave-active .quizably-modal {
    transition-duration: 0ms;
  }
}
</style>
