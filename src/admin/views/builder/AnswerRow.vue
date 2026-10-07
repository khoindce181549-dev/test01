<template>
  <div
    :class="['answer-row', { 'is-drag-over': dragOver }]"
    draggable="true"
    @dragstart="onDragStart"
    @dragend="onDragEnd"
    @dragover.prevent="onDragOver"
    @dragleave="onDragLeave"
    @drop.prevent="onDrop"
  >
    <span
      class="answer-row__grip"
      aria-hidden="true"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
      >
        <circle cx="9"  cy="5"  r="1" />
        <circle cx="9"  cy="12" r="1" />
        <circle cx="9"  cy="19" r="1" />
        <circle cx="15" cy="5"  r="1" />
        <circle cx="15" cy="12" r="1" />
        <circle cx="15" cy="19" r="1" />
      </svg>
    </span>

    <div class="answer-row__content">
      <div class="answer-row__letter">{{ letter }}</div>
      <div class="answer-row__fields">
        <input
          v-model="draftLabel"
          class="answer-row__text"
          type="text"
          :placeholder="answerLabelText"
          :aria-label="answerAriaLabel"
          @blur="commitLabel"
          @keydown.enter.prevent="commitLabel"
        >
        <!-- Image choice: image URL + alt text inputs shown below the label -->
        <template v-if="questionType === 'image_choice'">
          <input
            v-model="draftImageUrl"
            class="answer-row__text answer-row__text--sub"
            type="url"
            :placeholder="__('Image URL')"
            :aria-label="answerImageUrlAria"
            @blur="commitImageUrl"
            @keydown.enter.prevent="commitImageUrl"
          >
          <input
            v-model="draftAltText"
            class="answer-row__text answer-row__text--sub"
            type="text"
            :placeholder="__('Alt text (accessibility)')"
            :aria-label="answerAltAria"
            @blur="commitAltText"
            @keydown.enter.prevent="commitAltText"
          >
        </template>
      </div>
    </div>

    <!-- Hover tray — fades in over the right portion of the row -->
    <div class="answer-row__tray">
      <div class="answer-row__tray-inner">

        <!-- Trivia: correct toggle + points -->
        <template v-if="quizType === 'trivia'">
          <label class="answer-row__correct">
            <Toggle
              :model-value="Boolean(answer.is_correct)"
              size="sm"
              :aria-label="__('Correct answer')"
              @update:model-value="onCorrectChange"
            />
            <span>{{ __('Correct') }}</span>
          </label>
          <label class="answer-row__points">
            <span>{{ __('Pts') }}</span>
            <input
              v-model.number="draftPoints"
              type="number"
              min="0"
              step="1"
              class="answer-row__points-input"
              :aria-label="__('Points')"
              @blur="commitPoints"
              @keydown.enter.prevent="commitPoints"
            >
          </label>
        </template>

        <!-- Personality / other: result mapping -->
        <template v-else>
          <div class="answer-row__map-wrap">
            <button
              type="button"
              class="answer-row__map"
              :class="{ 'has-mapping': Boolean(mappedResult) }"
              @click.stop="toggleMapMenu"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              {{ stripHtml(mappedResult?.title || mappedResult?.name) || __('Result') }}
            </button>
            <div
              v-if="mapOpen"
              class="answer-row__map-menu"
              role="menu"
            >
              <button
                v-if="results.length === 0"
                type="button"
                class="answer-row__map-item is-muted"
                disabled
              >
                {{ __('No results yet — add them in the Results tab.') }}
              </button>
              <button
                v-for="r in results"
                :key="r.id"
                type="button"
                class="answer-row__map-item"
                :class="{ 'is-selected': r.id === answer.personality_result_id }"
                role="menuitem"
                @click="pickResult(r.id)"
              >
                {{ stripHtml(r.title || r.name) || __('Result') }}
              </button>
              <button
                v-if="answer.personality_result_id"
                type="button"
                class="answer-row__map-item is-clear"
                role="menuitem"
                @click="pickResult(null)"
              >
                {{ __('Clear mapping') }}
              </button>
            </div>
          </div>
        </template>

        <!-- 3-dots menu -->
        <button
          type="button"
          class="answer-row__menu"
          :aria-label="__('Answer actions')"
          @click.stop="toggleActionMenu"
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <circle cx="12" cy="6"  r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="12" cy="18" r="1" />
          </svg>
        </button>

        <div
          v-if="actionMenuOpen"
          class="answer-row__action-menu"
          role="menu"
        >
          <button
            type="button"
            class="answer-row__action-item is-danger"
            role="menuitem"
            @click="onDelete"
          >
            {{ __('Delete answer') }}
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Toggle } from '@admin/ui';
import { __, sprintf } from '@shared/i18n';

function stripHtml(html) {
  return (html || '').replace(/<[^>]*>/g, '').trim();
}

const props = defineProps({
  answer: { type: Object, required: true },
  index: { type: Number, required: true },
  quizType: { type: String, default: 'personality' },
  results: { type: Array, default: () => [] },
  questionType: { type: String, default: 'single' },
});

const emit = defineEmits(['update', 'delete', 'dragstart', 'dragend', 'drop']);

const draftLabel = ref(props.answer.label ?? '');
const draftPoints = ref(Number(props.answer.points ?? 0));
const draftImageUrl = ref(props.answer.image_url ?? '');
const draftAltText = ref(props.answer.alt_text ?? '');
const mapOpen = ref(false);
const actionMenuOpen = ref(false);
const dragOver = ref(false);

watch(() => props.answer.label, (v) => { draftLabel.value = v ?? ''; });
watch(() => props.answer.points, (v) => { draftPoints.value = Number(v ?? 0); });
watch(() => props.answer.image_url, (v) => { draftImageUrl.value = v ?? ''; });
watch(() => props.answer.alt_text, (v) => { draftAltText.value = v ?? ''; });

const letter = computed(() => String.fromCharCode(65 + (props.index % 26)));
// translators: %s: answer letter (A, B, C...)
const answerLabelText = computed(() => sprintf(__('Answer %s'), letter.value));
// translators: %s: answer letter (A, B, C...)
const answerAriaLabel = computed(() => sprintf(__('Answer %s label'), letter.value));
// translators: %s: answer letter (A, B, C...)
const answerImageUrlAria = computed(() => sprintf(__('Answer %s image URL'), letter.value));
// translators: %s: answer letter (A, B, C...)
const answerAltAria = computed(() => sprintf(__('Answer %s alt text'), letter.value));
const mappedResult = computed(() =>
  props.results.find((r) => r.id === props.answer.personality_result_id) || null
);

function commitLabel() {
  const next = (draftLabel.value ?? '').toString();
  if (next !== (props.answer.label ?? '')) emit('update', { label: next });
}

function commitPoints() {
  const next = Number.isFinite(draftPoints.value) ? Number(draftPoints.value) : 0;
  if (next !== Number(props.answer.points ?? 0)) emit('update', { points: next });
}

function commitImageUrl() {
  const next = (draftImageUrl.value ?? '').toString();
  if (next !== (props.answer.image_url ?? '')) emit('update', { image_url: next });
}

function commitAltText() {
  const next = (draftAltText.value ?? '').toString();
  if (next !== (props.answer.alt_text ?? '')) emit('update', { alt_text: next });
}

function onCorrectChange(next) { emit('update', { is_correct: next ? 1 : 0 }); }

function pickResult(id) {
  mapOpen.value = false;
  emit('update', { personality_result_id: id });
}

function toggleMapMenu() {
  mapOpen.value = !mapOpen.value;
  if (mapOpen.value) actionMenuOpen.value = false;
}

function toggleActionMenu() {
  actionMenuOpen.value = !actionMenuOpen.value;
  if (actionMenuOpen.value) mapOpen.value = false;
}

function onDelete() {
  actionMenuOpen.value = false;
  emit('delete');
}

function handleDocClick() {
  mapOpen.value = false;
  actionMenuOpen.value = false;
}

onMounted(() => document.addEventListener('click', handleDocClick));
onBeforeUnmount(() => document.removeEventListener('click', handleDocClick));

function onDragStart(event) {
  try {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(props.answer.id));
  } catch { /* ignore */ }
  emit('dragstart', props.answer.id);
}
function onDragEnd()        { dragOver.value = false; emit('dragend'); }
function onDragOver(event)  {
  dragOver.value = true;
  try { event.dataTransfer.dropEffect = 'move'; } catch { /* ignore */ }
}
function onDragLeave()      { dragOver.value = false; }
function onDrop()           { dragOver.value = false; emit('drop', props.answer.id); }
</script>

<style scoped>
/* ── Row base ─────────────────────────────────────────────────────────────── */
/* No overflow:hidden here (there used to be one): the result-mapping dropdown
   (.answer-row__map-menu) and the 3-dots action menu (.answer-row__action-menu)
   are position:absolute popovers that open *below* the row, and an ancestor
   overflow:hidden clips anything drawn outside its box — the menus were
   rendering (clicking the buttons did toggle them open) but invisible,
   clipped away at the row's own bottom edge. Not needed for the hover tray
   either — .answer-row__tray already carries its own matching border-radius. */
.answer-row {
  display: grid;
  grid-template-columns: 20px 1fr;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  position: relative;
  transition: border-color 150ms, box-shadow 150ms;
}

.answer-row.is-drag-over {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

/* ── Grip ─────────────────────────────────────────────────────────────────── */
.answer-row__grip {
  color: var(--ink-4);
  cursor: grab;
  display: grid;
  place-items: center;
}
.answer-row__grip svg { width: 14px; height: 14px; }

/* ── Content: letter + text input ────────────────────────────────────────── */
.answer-row__content {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.answer-row__letter {
  width: 22px;
  height: 22px;
  border-radius: var(--r-xs);
  background: var(--bg-muted);
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 600;
  display: grid;
  place-items: center;
  color: var(--ink-2);
  flex-shrink: 0;
}

.answer-row__fields {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  gap: 2px;
}

.answer-row__text {
  flex: 1;
  border: 0;
  outline: 0;
  background: transparent;
  font-size: 13.5px;
  color: var(--ink-1);
  min-width: 0;
  padding: 4px 0;
  font-family: inherit;
  width: 100%;
}
.answer-row__text:focus { color: var(--ink-1); }

.answer-row__text--sub {
  font-size: 12px;
  color: var(--ink-3);
  border-top: 1px solid var(--border-1);
  padding-top: 4px;
}
.answer-row__text--sub::placeholder { color: var(--ink-4); font-style: italic; }
.answer-row__text--sub:focus { color: var(--ink-2); }

/* ── Hover tray — absolute overlay on the right ──────────────────────────── */
.answer-row__tray {
  position: absolute;
  top: 0;
  inset-inline-end: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  background: linear-gradient(to right,
    transparent,
    var(--bg-surface) 44px
  );
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  border-start-start-radius: 0;
  border-start-end-radius: calc(var(--r-sm) - 1px);
  border-end-end-radius: calc(var(--r-sm) - 1px);
  border-end-start-radius: 0;
  padding-block: 0 0;
  padding-inline: 56px 8px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 160ms ease;
  z-index: 2;
}

.answer-row:hover .answer-row__tray,
.answer-row:focus-within .answer-row__tray {
  opacity: 1;
  pointer-events: auto;
}

.answer-row__tray-inner {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* ── Result map button — ink on light tray ──────────────────────────────── */
.answer-row__map-wrap { position: relative; }

.answer-row__map {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 10px;
  background: var(--bg-muted);
  border: 1px solid var(--border-2);
  border-radius: var(--r-xs);
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
  cursor: pointer;
  white-space: nowrap;
  transition: background 120ms, border-color 120ms, color 120ms;
  font-family: inherit;
}
.answer-row__map:hover {
  background: var(--bg-subtle);
  border-color: var(--border-3);
  color: var(--ink-1);
}
.answer-row__map.has-mapping {
  background: var(--brand-tint);
  border-color: var(--brand);
  color: var(--brand);
}
.answer-row__map svg { width: 11px; height: 11px; }

/* ── Result dropdown (unchanged white card) ─────────────────────────────── */
.answer-row__map-menu {
  position: absolute;
  top: calc(100% + 4px);
  inset-inline-end: 0;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-lg);
  min-width: 200px;
  z-index: 30;
  padding: 4px;
  display: flex;
  flex-direction: column;
}
.answer-row__map-item {
  display: block;
  width: 100%;
  padding: 7px 10px;
  background: transparent;
  border: 0;
  border-radius: var(--r-xs);
  text-align: start;
  font-size: 13px;
  color: var(--ink-2);
  cursor: pointer;
  font-family: inherit;
}
.answer-row__map-item:hover:not(:disabled) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}
.answer-row__map-item.is-selected {
  background: var(--brand-tint);
  color: var(--brand-hover);
  font-weight: 500;
}
.answer-row__map-item.is-muted {
  color: var(--ink-4);
  font-style: italic;
  cursor: default;
}
.answer-row__map-item.is-clear {
  color: var(--danger);
  border-top: 1px solid var(--border-1);
  margin-top: 2px;
  padding-top: 8px;
}

/* ── Trivia controls on the light tray ───────────────────────────────────── */
.answer-row__correct {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--ink-2);
  cursor: pointer;
}

.answer-row__points {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--ink-3);
}

.answer-row__points-input {
  width: 52px;
  padding: 4px 6px;
  border: 1px solid var(--border-2);
  border-radius: var(--r-xs);
  font-family: var(--f-mono);
  font-size: 12px;
  color: var(--ink-1);
  background: var(--bg-surface);
  outline: none;
}
.answer-row__points-input:focus {
  border-color: var(--brand);
}

/* ── 3-dots menu button — ink icon on light tray ─────────────────────────── */
.answer-row__menu {
  width: 28px;
  height: 28px;
  display: grid;
  place-items: center;
  color: var(--ink-3);
  border-radius: var(--r-xs);
  background: transparent;
  border: 0;
  cursor: pointer;
  transition: background 100ms, color 100ms;
  flex-shrink: 0;
}
.answer-row__menu:hover {
  background: var(--bg-muted);
  color: var(--ink-1);
}
.answer-row__menu svg { width: 14px; height: 14px; }

/* ── Action dropdown ─────────────────────────────────────────────────────── */
.answer-row__action-menu {
  position: absolute;
  top: calc(100% + 4px);
  inset-inline-end: 0;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-lg);
  min-width: 160px;
  z-index: 30;
  padding: 4px;
}
.answer-row__action-item {
  display: block;
  width: 100%;
  padding: 7px 10px;
  background: transparent;
  border: 0;
  border-radius: var(--r-xs);
  text-align: start;
  font-size: 13px;
  color: var(--ink-2);
  cursor: pointer;
  font-family: inherit;
}
.answer-row__action-item.is-danger { color: var(--danger); }
.answer-row__action-item:hover { background: var(--bg-subtle); }
</style>
