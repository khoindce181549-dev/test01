<template>
  <Teleport to="body">
    <Transition name="quizably-drawer">
      <div
        v-if="modelValue"
        class="quizably-drawer__backdrop"
        :data-size="size"
        @mousedown.self="onBackdropMouseDown"
        @click.self="onBackdropClick"
      >
        <aside
          ref="panelRef"
          class="quizably-drawer"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          tabindex="-1"
          @keydown="onKeyDown"
        >
          <header class="quizably-drawer__header">
            <h2
              :id="titleId"
              class="quizably-drawer__title"
            >
              {{ title }}
            </h2>
            <button
              type="button"
              class="quizably-drawer__close"
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
          <div class="quizably-drawer__body">
            <slot />
          </div>
          <footer
            v-if="$slots.footer"
            class="quizably-drawer__footer"
          >
            <slot name="footer" />
          </footer>
        </aside>
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
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  closeOnBackdrop: { type: Boolean, default: true },
  closeOnEsc: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'close']);

const panelRef = ref(null);
let previouslyFocused = null;
let backdropMouseDownOnBackdrop = false;

const titleId = computed(
  () => `quizably-drawer-title-${Math.random().toString(36).slice(2, 9)}`
);

function close() {
  emit('update:modelValue', false);
  emit('close');
}

function onBackdropMouseDown(event) {
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
  if (!panelRef.value) return [];
  return Array.from(
    panelRef.value.querySelectorAll(
      'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )
  );
}

function trapFocus(event) {
  const focusables = getFocusable();
  if (focusables.length === 0) {
    event.preventDefault();
    panelRef.value?.focus();
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
        panelRef.value?.focus();
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
.quizably-drawer__backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  justify-content: flex-end;
  background: rgba(10, 10, 11, 0.55);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

.quizably-drawer {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100vh;
  background: var(--bg-surface);
  box-shadow: var(--shadow-xl);
  color: var(--ink-1);
  outline: none;
}

.quizably-drawer__backdrop[data-size='sm'] .quizably-drawer {
  max-width: 320px;
}
.quizably-drawer__backdrop[data-size='md'] .quizably-drawer {
  max-width: 480px;
}
.quizably-drawer__backdrop[data-size='lg'] .quizably-drawer {
  max-width: 640px;
}

.quizably-drawer__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid var(--border-1);
  gap: 16px;
  flex-shrink: 0;
}

.quizably-drawer__title {
  font-family: var(--f-display);
  font-size: 20px;
  font-weight: 500;
  letter-spacing: -0.015em;
  color: var(--ink-1);
  margin: 0;
  line-height: 1.2;
}

.quizably-drawer__close {
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
.quizably-drawer__close:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.quizably-drawer__body {
  padding: 20px 22px;
  overflow-y: auto;
  flex: 1;
}

.quizably-drawer__footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 8px;
  padding: 16px 22px;
  border-top: 1px solid var(--border-1);
  background: var(--bg-canvas);
  flex-shrink: 0;
}

.quizably-drawer-enter-active,
.quizably-drawer-leave-active {
  transition: opacity 250ms ease;
}
.quizably-drawer-enter-active .quizably-drawer,
.quizably-drawer-leave-active .quizably-drawer {
  transition: transform 250ms ease;
}
.quizably-drawer-enter-from,
.quizably-drawer-leave-to {
  opacity: 0;
}
.quizably-drawer-enter-from .quizably-drawer,
.quizably-drawer-leave-to .quizably-drawer {
  transform: translateX(100%);
}
.quizably-drawer-enter-from .quizably-drawer:dir(rtl),
.quizably-drawer-leave-to .quizably-drawer:dir(rtl) {
  transform: translateX(-100%);
}

@media (prefers-reduced-motion: reduce) {
  .quizably-drawer-enter-active,
  .quizably-drawer-leave-active,
  .quizably-drawer-enter-active .quizably-drawer,
  .quizably-drawer-leave-active .quizably-drawer {
    transition-duration: 0ms;
  }
}
</style>
