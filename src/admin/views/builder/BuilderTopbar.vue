<template>
  <div class="builder-topbar">
    <button
      type="button"
      class="builder-back"
      :aria-label="__('Back to quizzes')"
      @click="goBack"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="m15 18-6-6 6-6" />
      </svg>
    </button>

    <div
      class="builder-title"
      :class="{ 'is-editing': editing }"
    >
      <input
        v-if="editing"
        ref="titleInputRef"
        v-model="draftTitle"
        class="builder-title__input"
        type="text"
        :aria-label="__('Quiz title')"
        @blur="commitTitle"
        @keydown.enter.prevent="commitTitle"
        @keydown.escape.prevent="cancelTitle"
      >
      <template v-else>
        <button
          type="button"
          class="builder-title__btn"
          :aria-label="__('Edit title')"
          @click="startEdit"
        >
          {{ store.quiz?.title || __('Untitled quiz') }}
          <svg
            class="builder-title__edit"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            width="14"
            height="14"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
          </svg>
        </button>
      </template>
    </div>

    <div
      v-if="store.saving || store.lastSavedAt"
      class="builder-status"
      :class="statusClass"
      aria-live="polite"
    >
      <span
        v-if="store.saving"
        class="builder-status__spinner"
        aria-hidden="true"
      />
      <span
        v-else
        class="builder-status__dot"
        aria-hidden="true"
      />
      <span>{{ statusText }}</span>
    </div>

    <div class="builder-spacer" />

    <div class="builder-topbar__actions">
      <button
        type="button"
        class="btb-action btb-action--ghost btb-action--icon-only"
        :aria-label="__('Design settings')"
        :title="__('Design settings')"
        data-testid="builder-design-btn"
        @click="$emit('open-design')"
      >
        <!-- Sliders icon — communicates "customise / design" -->
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
      </button>

      <div
        class="btb-divider"
        aria-hidden="true"
      />

      <button
        type="button"
        class="btb-action btb-action--ghost"
        :disabled="!prevTabKey"
        :aria-label="prevTabKey ? sprintf(__('Back to %s'), prevTabKey) : __('On the first step')"
        data-testid="builder-nav-prev"
        @click="prevTabKey && switchTab(prevTabKey)"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="m15 18-6-6 6-6" />
        </svg>
        {{ __('Back') }}
      </button>

      <div
        class="btb-divider"
        aria-hidden="true"
      />

      <button
        type="button"
        class="btb-action btb-action--save"
        :disabled="forceSaving"
        data-testid="builder-save-btn"
        @click="onForceSave"
      >
        <span
          v-if="forceSaving"
          class="btb-spinner"
          aria-hidden="true"
        />
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
        {{ forceSaving ? __('Saving…') : __('Save') }}
      </button>

      <button
        type="button"
        class="btb-action btb-action--next"
        :disabled="!nextTabKey || forceSaving"
        :aria-label="nextTabKey ? sprintf(__('Continue to %s'), nextTabLabel) : __('On the last step')"
        data-testid="builder-nav-next"
        @click="onNext"
      >
        {{ nextTabKey ? sprintf(__('Next: %s'), nextTabLabel) : __('Done') }}
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="m9 18 6-6-6-6" />
        </svg>
      </button>
    </div>


  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { __, sprintf } from '@shared/i18n';
import { nextTab, prevTab, resultsLabelFor, TAB_LABELS } from './tabOrder.js';

const emit = defineEmits(['open-design']);

const router = useRouter();
const route = useRoute();
const store = useQuizBuilderStore();
const toast = useToast();

const currentTab = computed(() => String(route.params.tab || 'overview'));
const prevTabKey = computed(() => prevTab(currentTab.value, store.quiz?.type));
const nextTabKey = computed(() => nextTab(currentTab.value, store.quiz?.type));
const nextTabLabel = computed(() => {
  if (!nextTabKey.value) return '';
  if (nextTabKey.value === 'results') return resultsLabelFor(store.quiz?.type);
  return TAB_LABELS[nextTabKey.value] ?? nextTabKey.value;
});

function switchTab(key) {
  router.push('/quiz/' + store.quiz?.id + '/' + key);
}

const editing = ref(false);
const draftTitle = ref('');
const titleInputRef = ref(null);
const forceSaving = ref(false);

// Relative-time label ticks every second. `now` is re-read on each tick so
// "Saved 3s ago" stays accurate without a deep watch on the store timestamp.
const now = ref(Date.now());
let tickHandle = null;

onMounted(() => {
  tickHandle = setInterval(() => {
    now.value = Date.now();
  }, 1000);
});

onBeforeUnmount(() => {
  if (tickHandle) clearInterval(tickHandle);
});

const statusText = computed(() => {
  if (store.saving) return __('Saving…');
  if (!store.lastSavedAt) return __('Not saved yet');
  const seconds = Math.max(0, Math.round((now.value - store.lastSavedAt) / 1000));
  if (seconds < 5) return __('Saved just now');
  // translators: %d is a number of seconds.
  if (seconds < 60) return sprintf(__('Saved %ds ago'), seconds);
  const minutes = Math.floor(seconds / 60);
  // translators: %d is a number of minutes.
  if (minutes < 60) return sprintf(__('Saved %dm ago'), minutes);
  const hours = Math.floor(minutes / 60);
  // translators: %d is a number of hours.
  return sprintf(__('Saved %dh ago'), hours);
});

const statusClass = computed(() => ({
  'is-saving': store.saving,
}));

function goBack() {
  router.push('/quizzes');
}

/**
 * Write everything that is still pending. Components in the builder listen for
 * the `quizably:flush-pending-saves` window event and flush their per-field
 * autosave debouncers immediately. We then re-PUT a no-op title patch so the
 * topbar reflects a fresh `lastSavedAt`. Shared by Save and Next so the two
 * can never disagree about what "saved" means.
 */
async function persistAll() {
  // Flush DesignTab's pending color/font/etc. patches (its own pattern).
  if (typeof window !== 'undefined' && typeof window.dispatchEvent === 'function') {
    window.dispatchEvent(new CustomEvent('quizably:flush-pending-saves'));
  }
  // Flush staged settings and result changes accumulated since last Save.
  await store.flushAllStaged();
  // Re-PUT the title as the final confirmation write.
  await store.savePatch({ title: store.quiz.title });
}

/** Explicit Save: write everything, then say so. */
async function onForceSave() {
  if (!store.quiz || forceSaving.value) return;
  forceSaving.value = true;
  try {
    await persistAll();
    toast.push({
      variant: 'success',
      title: __('Saved'),
      message: __('All changes have been written.'),
    });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save'),
      message: e.message || __('Please try again.'),
    });
  } finally {
    forceSaving.value = false;
  }
}

/**
 * Next: save, then move to the next step.
 *
 * Edits made on this step are only staged in the store until something writes
 * them, so moving on without saving left them unsaved on the server. Saving
 * first also flushes whatever the step's own editors are still holding (the
 * `quizably:flush-pending-saves` event) while they are still on screen.
 *
 * If the save fails we stay put and say so - navigating away would hide the
 * error and leave the visitor believing the step was saved. Success is quiet
 * (the "Saved just now" status updates); a toast on every step is noise.
 */
async function onNext() {
  const target = nextTabKey.value; // read now: the route can change while we await
  if (!target || !store.quiz || forceSaving.value) return;
  forceSaving.value = true;
  let saved = false;
  try {
    await persistAll();
    saved = true;
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save'),
      // translators: %s is the error message (or "Please try again.").
      message: sprintf(__('%s You are still on this step.'), e.message || __('Please try again.')),
    });
  } finally {
    forceSaving.value = false;
  }
  if (saved) switchTab(target);
}

function startEdit() {
  draftTitle.value = store.quiz?.title ?? '';
  editing.value = true;
  nextTick(() => {
    titleInputRef.value?.focus();
    titleInputRef.value?.select();
  });
}

async function commitTitle() {
  if (!editing.value) return;
  const next = draftTitle.value.trim();
  editing.value = false;
  if (!next || !store.quiz || next === store.quiz.title) return;
  try {
    await store.savePatch({ title: next });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save title'),
      message: e.message || __('Please try again.'),
    });
  }
}

function cancelTitle() {
  editing.value = false;
  draftTitle.value = '';
}

</script>

<style scoped>
.builder-topbar {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 24px;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-1);
  flex-wrap: nowrap;
}

.builder-back {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border: 0;
  background: transparent;
  border-radius: var(--r-sm);
  color: var(--ink-3);
  cursor: pointer;
  flex-shrink: 0;
}
.builder-back:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}
.builder-back svg {
  width: 16px;
  height: 16px;
}

.builder-title {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  max-width: 420px;
  flex-shrink: 1;
}

.builder-title__btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: 0;
  padding: 4px 6px;
  margin-inline-start: -6px;
  border-radius: var(--r-sm);
  cursor: pointer;
  font-family: var(--f-display);
  font-size: 20px;
  font-weight: 500;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.builder-title__btn:hover {
  background: var(--bg-subtle);
}

.builder-title__edit {
  opacity: 0;
  color: var(--ink-4);
  transition: opacity 150ms ease;
  flex-shrink: 0;
}

.builder-title__btn:hover .builder-title__edit {
  opacity: 1;
}

.builder-title__input {
  font-family: var(--f-display);
  font-size: 20px;
  font-weight: 500;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  background: var(--bg-surface);
  border: 1px solid var(--brand);
  border-radius: var(--r-sm);
  padding: 4px 8px;
  outline: none;
  box-shadow: var(--shadow-focus);
  min-width: 240px;
  width: 100%;
}

.builder-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  background: var(--bg-subtle);
  border-radius: var(--r-pill);
  font-size: 12px;
  color: var(--ink-3);
  flex-shrink: 0;
  font-family: var(--f-mono);
}

.builder-status__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

.builder-status__spinner {
  display: inline-block;
  width: 10px;
  height: 10px;
  border: 1.5px solid var(--border-3);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-topbar-spin 700ms linear infinite;
}

@keyframes quizably-topbar-spin {
  to {
    transform: rotate(360deg);
  }
}

.builder-spacer {
  flex: 1;
}

.builder-topbar__actions {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.btb-divider {
  width: 1px;
  height: 18px;
  background: var(--border-2);
  margin: 0 4px;
  flex-shrink: 0;
}

.btb-action {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 32px;
  padding: 0 12px;
  border-radius: var(--r-xs);
  border: 1px solid transparent;
  font: inherit;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background 120ms, color 120ms, border-color 120ms;
  white-space: nowrap;
  flex-shrink: 0;
  line-height: 1;
}

.btb-action svg {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.btb-action--ghost {
  background: transparent;
  color: var(--ink-3);
}
.btb-action--ghost:hover:not(:disabled) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}
.btb-action--ghost:disabled {
  opacity: 0.35;
  cursor: default;
}

/* Icon-only buttons: fixed square, no text padding */
.btb-action--icon-only {
  width: 32px;
  padding: 0;
  justify-content: center;
}

.btb-action--save {
  background: var(--bg-surface);
  color: var(--ink-1);
  border-color: var(--border-2);
}
.btb-action--save:hover:not(:disabled) {
  background: var(--bg-subtle);
  border-color: var(--border-3);
}
.btb-action--save:disabled {
  opacity: 0.6;
  cursor: default;
}

.btb-action--next {
  background: var(--brand);
  color: #fff;
  border-color: var(--brand);
}
.btb-action--next:hover:not(:disabled) {
  background: var(--brand-hover);
  border-color: var(--brand-hover);
}
.btb-action--next:disabled {
  background: var(--bg-muted);
  border-color: var(--border-2);
  color: var(--ink-3);
  cursor: default;
}

.btb-spinner {
  display: inline-block;
  width: 12px;
  height: 12px;
  border: 1.5px solid var(--border-2);
  border-top-color: var(--ink-2);
  border-radius: 50%;
  animation: quizably-topbar-spin 700ms linear infinite;
}


@media (prefers-reduced-motion: reduce) {
  .builder-status__spinner {
    animation-duration: 0ms;
  }
}
</style>
