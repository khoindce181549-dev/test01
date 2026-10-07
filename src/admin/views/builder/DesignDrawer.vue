<template>
  <Teleport to="body">
    <Transition name="quizably-drawer">
      <div
        v-show="open"
        class="quizably-design-drawer"
        role="dialog"
        :aria-label="__('Design settings')"
        aria-modal="true"
      >
        <div
          class="quizably-design-drawer__backdrop"
          @click="$emit('close')"
        />
        <div class="quizably-design-drawer__panel">
          <header class="quizably-design-drawer__head">
            <div class="quizably-design-drawer__head-left">
              <!-- Palette icon -->
              <svg
                class="quizably-design-drawer__head-icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <line x1="21" y1="4" x2="7" y2="4" />
                <line x1="3" y1="4" x2="5" y2="4" />
                <line x1="21" y1="12" x2="13" y2="12" />
                <line x1="3" y1="12" x2="11" y2="12" />
                <line x1="21" y1="20" x2="17" y2="20" />
                <line x1="3" y1="20" x2="15" y2="20" />
                <circle cx="6" cy="4" r="2" />
                <circle cx="12" cy="12" r="2" />
                <circle cx="16" cy="20" r="2" />
              </svg>
              <span class="quizably-design-drawer__title">{{ __('Design') }}</span>
            </div>
            <button
              type="button"
              class="quizably-design-drawer__close"
              :aria-label="__('Close design settings')"
              @click="$emit('close')"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </header>
          <div class="quizably-design-drawer__body">
            <DesignTab ref="designTab" />
          </div>
          <footer class="quizably-design-drawer__footer">
            <button
              type="button"
              class="quizably-design-drawer__save"
              :disabled="saving"
              @click="handleSave"
            >
              <svg
                v-if="saving"
                class="quizably-design-drawer__save-spinner"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                aria-hidden="true"
              >
                <path d="M12 2a10 10 0 0 1 10 10" />
              </svg>
              <svg
                v-else
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                <polyline points="17 21 17 13 7 13 7 21" />
                <polyline points="7 3 7 8 15 8" />
              </svg>
              {{ saving ? __('Saving…') : __('Save') }}
            </button>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import DesignTab from './DesignTab.vue';
import { __ } from '@shared/i18n';

const props = defineProps({
  open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const saving = ref(false);
const designTab = ref(null);

async function handleSave() {
  saving.value = true;
  try {
    // Call flush directly on the child component so the spinner stays visible
    // until the actual API call completes (not just a fixed-duration timer).
    if (designTab.value?.flush) {
      await designTab.value.flush();
    } else {
      // Fallback: event bus for consumers that don't expose flush()
      window.dispatchEvent(new CustomEvent('quizably:flush-pending-saves'));
      await new Promise((r) => setTimeout(r, 600));
    }
  } finally {
    saving.value = false;
  }
}

function onKeydown(e) {
  if (e.key === 'Escape' && props.open) emit('close');
}

// Flush any pending design changes to the server the moment the drawer closes.
// Because DesignTab uses v-show (not v-if) the component stays mounted and its
// pending-patch objects survive — but we fire the flush immediately so the user
// doesn't have to remember to hit Save in the topbar after closing the drawer.
watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen && typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('quizably:flush-pending-saves'));
    }
  },
);

onMounted(() => {
  window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown);
});
</script>

<style scoped>
/* ── Overlay root ───────────────────────────────────────────────────────────── */
.quizably-design-drawer {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  justify-content: flex-end;
  /* Pointer events propagate to children — backdrop and panel */
  pointer-events: auto;
}

/* ── Scrim ──────────────────────────────────────────────────────────────────── */
.quizably-design-drawer__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.32);
}

/* ── Slide-in panel ─────────────────────────────────────────────────────────── */
.quizably-design-drawer__panel {
  position: relative;
  width: 380px;
  max-width: 92vw;
  height: 100%;
  background: var(--bg-surface);
  border-inline-start: 1px solid var(--border-1);
  display: flex;
  flex-direction: column;
  box-shadow: -6px 0 32px rgba(0, 0, 0, 0.14);
  overflow: hidden;
}

.quizably-design-drawer__panel:dir(rtl) {
  box-shadow: 6px 0 32px rgba(0, 0, 0, 0.14);
}

/* ── Header ─────────────────────────────────────────────────────────────────── */
.quizably-design-drawer__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-block: 14px 14px;
  padding-inline: 20px 16px;
  border-bottom: 1px solid var(--border-1);
  flex-shrink: 0;
  background: var(--bg-surface);
}

.quizably-design-drawer__head-left {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.quizably-design-drawer__head-icon {
  width: 16px;
  height: 16px;
  color: var(--ink-3);
  flex-shrink: 0;
}

.quizably-design-drawer__title {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
  letter-spacing: -0.01em;
}

.quizably-design-drawer__close {
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border: 0;
  background: transparent;
  border-radius: var(--r-sm);
  color: var(--ink-3);
  cursor: pointer;
  transition: background 120ms ease, color 120ms ease;
  flex-shrink: 0;
}
.quizably-design-drawer__close:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}
.quizably-design-drawer__close svg {
  width: 16px;
  height: 16px;
}

/* ── Body — fills remaining panel height ────────────────────────────────────── */
.quizably-design-drawer__body {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

/* ── Sticky footer ──────────────────────────────────────────────────────────── */
.quizably-design-drawer__footer {
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
  padding: 12px 16px;
  border-top: 1px solid var(--border-1);
  background: var(--bg-surface);
}

.quizably-design-drawer__save {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 18px;
  height: 36px;
  font-family: var(--f-sans);
  font-size: 13px;
  font-weight: 600;
  color: #fff;
  background: var(--accent, #6c47ff);
  border: 0;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: background 140ms ease, opacity 140ms ease;
  letter-spacing: -0.01em;
  white-space: nowrap;
}
.quizably-design-drawer__save:hover:not(:disabled) {
  background: var(--accent-hover, #5535e0);
}
.quizably-design-drawer__save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.quizably-design-drawer__save svg {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

@keyframes quizably-spin {
  to { transform: rotate(360deg); }
}
.quizably-design-drawer__save-spinner {
  animation: quizably-spin 0.7s linear infinite;
}

/* ── Enter / leave transition ───────────────────────────────────────────────── */
.quizably-drawer-enter-active {
  transition: opacity 200ms ease;
}
.quizably-drawer-leave-active {
  transition: opacity 180ms ease;
}

.quizably-drawer-enter-active .quizably-design-drawer__panel {
  transition: transform 240ms cubic-bezier(0.22, 1, 0.36, 1);
}
.quizably-drawer-leave-active .quizably-design-drawer__panel {
  transition: transform 200ms cubic-bezier(0.55, 0, 1, 0.45);
}

.quizably-drawer-enter-from,
.quizably-drawer-leave-to {
  opacity: 0;
}
.quizably-drawer-enter-from .quizably-design-drawer__panel,
.quizably-drawer-leave-to .quizably-design-drawer__panel {
  transform: translateX(100%);
}
.quizably-drawer-enter-from .quizably-design-drawer__panel:dir(rtl),
.quizably-drawer-leave-to .quizably-design-drawer__panel:dir(rtl) {
  transform: translateX(-100%);
}
</style>
