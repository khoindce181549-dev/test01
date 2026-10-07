<template>
  <div :class="['result-card', { 'is-open': open }]">
    <header class="result-card__head">
      <button
        type="button"
        class="result-card__toggle"
        :aria-expanded="open"
        @click="open = !open"
      >
        <svg
          class="result-card__chevron"
          :class="{ 'is-open': open }"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          width="14"
          height="14"
        >
          <path d="m9 18 6-6-6-6" />
        </svg>
      </button>
      <input
        v-model="draftTitle"
        class="result-card__title"
        type="text"
        :placeholder="__('Result title')"
        @blur="flushTitle"
        @input="scheduleTitleSave"
      >
      <div class="result-card__meta">
        <Badge
          v-if="quizType === 'trivia'"
          variant="default"
          size="sm"
        >
          {{ rangeLabel }}
        </Badge>
        <Badge
          v-else-if="quizType === 'personality'"
          variant="default"
          size="sm"
        >
          {{ sprintf(__('%d mapped'), mappedAnswerCount) }}
        </Badge>
      </div>
      <button
        type="button"
        class="result-card__delete"
        :aria-label="__('Delete result')"
        @click="onDelete"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          width="14"
          height="14"
        >
          <polyline points="3 6 5 6 21 6" />
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
        </svg>
      </button>
    </header>

    <div
      v-if="open"
      class="result-card__body"
    >
      <!-- Plain textarea in Free. Tiptap rich-text editor lives in the Pro addon. -->
      <label class="result-card__label">{{ __('Content') }}</label>
      <textarea
        v-model="draftContent"
        class="result-card__textarea"
        rows="6"
        :placeholder="__('Describe the result — what does it mean for the user?')"
        @blur="flushContent"
        @input="scheduleContentSave"
      />

      <MediaPicker
        :model-value="draftImage"
        :label="__('Image')"
        :helper-text="__('Optional hero image shown with this result.')"
        @update:model-value="onImageInput"
      />

      <div class="result-card__grid">
        <div>
          <label class="result-card__label">{{ __('CTA label') }}</label>
          <Input
            v-model="draftCtaLabel"
            :placeholder="__('Shop my match')"
            @blur="flushCta"
            @update:model-value="scheduleCtaSave"
          />
        </div>
        <div>
          <label class="result-card__label">{{ __('CTA URL') }}</label>
          <Input
            v-model="draftCtaUrl"
            ltr
            placeholder="https://…"
            @blur="flushCta"
            @update:model-value="scheduleCtaSave"
          />
        </div>
      </div>

      <label class="result-card__label">{{ __('Redirect URL') }}</label>
      <Input
        v-model="draftRedirect"
        placeholder="https://…"
        :helper-text="__('Optional — redirect immediately instead of showing this screen.')"
        @blur="flushRedirect"
        @update:model-value="scheduleRedirectSave"
      />

      <!-- Trivia: score range -->
      <div
        v-if="quizType === 'trivia'"
        class="result-card__row"
      >
        <div>
          <label class="result-card__label">{{ __('Score min') }}</label>
          <Input
            v-model="draftScoreMin"
            type="number"
            @blur="flushScoreBounds"
            @update:model-value="scheduleScoreBoundsSave"
          />
        </div>
        <div>
          <label class="result-card__label">{{ __('Score max') }}</label>
          <Input
            v-model="draftScoreMax"
            type="number"
            @blur="flushScoreBounds"
            @update:model-value="scheduleScoreBoundsSave"
          />
        </div>
      </div>

      <!-- Personality: mapping read-only list -->
      <div
        v-else-if="quizType === 'personality'"
        class="result-card__mapping"
      >
        <label class="result-card__label">{{ __('Answers mapping to this result') }}</label>
        <p class="result-card__hint">
          {{ __('Edit the mapping inside individual questions in the Questions tab.') }}
        </p>
        <ul
          v-if="mappedAnswers.length"
          class="result-card__map-list"
        >
          <li
            v-for="m in mappedAnswers"
            :key="m.id"
          >
            <span class="result-card__map-q">{{ sprintf(__('Q%d'), m.qPos) }}</span>
            <span class="result-card__map-a">{{ m.label || __('(untitled answer)') }}</span>
          </li>
        </ul>
        <p
          v-else
          class="result-card__empty-map"
        >
          {{ __('No answers map to this result yet.') }}
        </p>
      </div>

      <!-- Weighted: Pro conditions (absent unless Pro is active or promoted) -->
      <div
        v-else-if="quizType === 'weighted' && proTierVisible"
        class="result-card__pro"
      >
        <div class="result-card__pro-head">
          <label class="result-card__label">{{ __('Conditions (JSON)') }}</label>
          <Badge
            variant="pro"
            size="sm"
          >
            {{ __('Pro') }}
          </Badge>
        </div>
        <textarea
          class="result-card__textarea is-disabled"
          rows="4"
          placeholder="[{&quot;category&quot;:&quot;adventurous&quot;,&quot;min&quot;:5}]"
          disabled
        />
        <p class="result-card__pro-hint">
          {{ __('Upgrade to Pro to use weighted results.') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { __, _n, sprintf } from '@shared/i18n';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Badge, Input, MediaPicker } from '@admin/ui';
import { proFeaturesVisible } from '@admin/api/pro.js';

const AUTOSAVE_DEBOUNCE = 600;

// The weighted-conditions block is Pro-tier: only listed when Pro is active
// or Pro promotion is on (see api/pro.js).
const proTierVisible = computed(() => proFeaturesVisible());

const props = defineProps({
  result: { type: Object, required: true },
  quizType: { type: String, default: 'personality' },
  questions: { type: Array, default: () => [] },
  defaultOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['update', 'delete']);

const open = ref(props.defaultOpen);

const draftTitle = ref(props.result.title ?? '');
const draftContent = ref(props.result.content ?? '');
const draftImage = ref(props.result.image_url ?? '');
const draftCtaLabel = ref(props.result.cta_label ?? '');
const draftCtaUrl = ref(props.result.cta_url ?? '');
const draftRedirect = ref(props.result.redirect_url ?? '');
const draftScoreMin = ref(
  props.result.score_min ?? props.result.min_score ?? 0
);
const draftScoreMax = ref(
  props.result.score_max ?? props.result.max_score ?? 0
);

// Re-seed drafts when a different result swaps in (e.g. after sort).
watch(
  () => props.result.id,
  () => {
    flushAllPending();
    draftTitle.value = props.result.title ?? '';
    draftContent.value = props.result.content ?? '';
    draftImage.value = props.result.image_url ?? '';
    draftCtaLabel.value = props.result.cta_label ?? '';
    draftCtaUrl.value = props.result.cta_url ?? '';
    draftRedirect.value = props.result.redirect_url ?? '';
    draftScoreMin.value = props.result.score_min ?? props.result.min_score ?? 0;
    draftScoreMax.value = props.result.score_max ?? props.result.max_score ?? 0;
  }
);

// Personality mapping — count answers across all questions whose
// personality_result_id matches this result. Displays as a read-only list.
const mappedAnswers = computed(() => {
  const out = [];
  (props.questions || []).forEach((q, qIdx) => {
    (q.answers || []).forEach((a) => {
      if (a.personality_result_id === props.result.id) {
        out.push({ id: a.id, label: a.label, qPos: qIdx + 1 });
      }
    });
  });
  return out;
});

const mappedAnswerCount = computed(() => mappedAnswers.value.length);

const rangeLabel = computed(() => {
  const min = Number(draftScoreMin.value) || 0;
  const max = Number(draftScoreMax.value) || 0;
  return `${min}–${max}`;
});

// One debounce timer per logical field group. Blur/switch flushes pending.
let titleTimer = null;
let contentTimer = null;
let imageTimer = null;
let ctaTimer = null;
let redirectTimer = null;
let scoreTimer = null;

function scheduleTitleSave() {
  if (titleTimer) clearTimeout(titleTimer);
  titleTimer = setTimeout(flushTitle, AUTOSAVE_DEBOUNCE);
}

function scheduleContentSave() {
  if (contentTimer) clearTimeout(contentTimer);
  contentTimer = setTimeout(flushContent, AUTOSAVE_DEBOUNCE);
}

// MediaPicker emits the chosen URL — propagate to the draft and persist
// immediately when the user selects from the modal or clears, since there
// is no blur event to flush on.
function onImageInput(next) {
  draftImage.value = next ?? '';
  flushImage();
}

function scheduleCtaSave() {
  if (ctaTimer) clearTimeout(ctaTimer);
  ctaTimer = setTimeout(flushCta, AUTOSAVE_DEBOUNCE);
}

function scheduleRedirectSave() {
  if (redirectTimer) clearTimeout(redirectTimer);
  redirectTimer = setTimeout(flushRedirect, AUTOSAVE_DEBOUNCE);
}

function scheduleScoreBoundsSave() {
  if (scoreTimer) clearTimeout(scoreTimer);
  scoreTimer = setTimeout(flushScoreBounds, AUTOSAVE_DEBOUNCE);
}

function flushTitle() {
  if (titleTimer) {
    clearTimeout(titleTimer);
    titleTimer = null;
  }
  const next = (draftTitle.value ?? '').toString();
  if (next === (props.result.title ?? '')) return;
  emit('update', { title: next });
}

function flushContent() {
  if (contentTimer) {
    clearTimeout(contentTimer);
    contentTimer = null;
  }
  const next = (draftContent.value ?? '').toString();
  if (next === (props.result.content ?? '')) return;
  emit('update', { content: next });
}

function flushImage() {
  if (imageTimer) {
    clearTimeout(imageTimer);
    imageTimer = null;
  }
  const next = (draftImage.value ?? '').toString();
  if (next === (props.result.image_url ?? '')) return;
  emit('update', { image_url: next });
}

function flushCta() {
  if (ctaTimer) {
    clearTimeout(ctaTimer);
    ctaTimer = null;
  }
  const label = (draftCtaLabel.value ?? '').toString();
  const url = (draftCtaUrl.value ?? '').toString();
  const patch = {};
  if (label !== (props.result.cta_label ?? '')) patch.cta_label = label;
  if (url !== (props.result.cta_url ?? '')) patch.cta_url = url;
  if (Object.keys(patch).length) emit('update', patch);
}

function flushRedirect() {
  if (redirectTimer) {
    clearTimeout(redirectTimer);
    redirectTimer = null;
  }
  const next = (draftRedirect.value ?? '').toString();
  if (next === (props.result.redirect_url ?? '')) return;
  emit('update', { redirect_url: next });
}

function flushScoreBounds() {
  if (scoreTimer) {
    clearTimeout(scoreTimer);
    scoreTimer = null;
  }
  const min = Number(draftScoreMin.value) || 0;
  const max = Number(draftScoreMax.value) || 0;
  const currentMin = Number(props.result.score_min ?? props.result.min_score ?? 0);
  const currentMax = Number(props.result.score_max ?? props.result.max_score ?? 0);
  if (min === currentMin && max === currentMax) return;
  emit('update', { score_min: min, score_max: max });
}

function flushAllPending() {
  if (titleTimer) flushTitle();
  if (contentTimer) flushContent();
  if (imageTimer) flushImage();
  if (ctaTimer) flushCta();
  if (redirectTimer) flushRedirect();
  if (scoreTimer) flushScoreBounds();
}

function onDelete() {
  emit('delete');
}

onBeforeUnmount(flushAllPending);
</script>

<style scoped>
.result-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  overflow: hidden;
  transition: border-color 150ms ease, box-shadow 150ms ease;
}

.result-card.is-open {
  border-color: var(--border-2);
  box-shadow: var(--shadow-xs);
}

.result-card__head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  position: relative;
}

.result-card__toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border: 0;
  background: transparent;
  color: var(--ink-3);
  cursor: pointer;
  border-radius: var(--r-xs);
  flex-shrink: 0;
}

.result-card__toggle:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.result-card__chevron {
  transition: transform 150ms ease;
}

.result-card__chevron.is-open {
  transform: rotate(90deg);
}

.result-card__title {
  flex: 1;
  font-family: var(--f-display);
  font-size: 15px;
  font-weight: 500;
  letter-spacing: -0.005em;
  color: var(--ink-1);
  border: 0;
  outline: 0;
  background: transparent;
  padding: 4px 6px;
  border-radius: var(--r-xs);
  min-width: 0;
}

.result-card__title:focus {
  background: var(--bg-subtle);
}

.result-card__title::placeholder {
  color: var(--ink-4);
}

.result-card__meta {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.result-card__delete {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 0;
  background: transparent;
  color: var(--ink-4);
  cursor: pointer;
  border-radius: var(--r-xs);
  opacity: 0;
  transition: opacity 150ms ease, background-color 150ms ease, color 150ms ease;
  flex-shrink: 0;
}

.result-card__head:hover .result-card__delete,
.result-card__delete:focus-visible {
  opacity: 1;
}

.result-card__delete:hover {
  background: var(--danger-bg);
  color: var(--danger);
}

.result-card__body {
  padding-block: 4px 20px;
  padding-inline: 46px 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  border-top: 1px solid var(--border-1);
  padding-top: 16px;
}

.result-card__label {
  display: block;
  font-size: 12.5px;
  font-weight: 500;
  color: var(--ink-2);
  margin-bottom: 6px;
}

.result-card__textarea {
  width: 100%;
  min-height: 120px;
  padding: 10px 14px;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-md);
  font-size: 14px;
  font-family: inherit;
  color: var(--ink-1);
  line-height: 1.5;
  resize: vertical;
  outline: none;
  transition: border-color 150ms ease, box-shadow 150ms ease;
}

.result-card__textarea:focus {
  border-color: var(--brand);
  box-shadow: var(--shadow-focus);
}

.result-card__textarea.is-disabled {
  background: var(--bg-subtle);
  color: var(--ink-4);
  cursor: not-allowed;
}

.result-card__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.result-card__row {
  display: grid;
  grid-template-columns: 140px 140px;
  gap: 12px;
}

.result-card__mapping {
  background: var(--bg-subtle);
  padding: 12px 14px;
  border-radius: var(--r-md);
}

.result-card__hint {
  font-size: 12px;
  color: var(--ink-3);
  margin: 0 0 8px;
  line-height: 1.5;
}

.result-card__map-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.result-card__map-list li {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--ink-2);
}

.result-card__map-q {
  font-family: var(--f-mono);
  font-size: 10.5px;
  padding: 2px 6px;
  background: var(--bg-surface);
  border-radius: var(--r-xs);
  color: var(--ink-3);
  flex-shrink: 0;
}

.result-card__map-a {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.result-card__empty-map {
  font-size: 12px;
  color: var(--ink-4);
  margin: 0;
}

.result-card__pro {
  background: var(--bg-subtle);
  padding: 14px;
  border: 1px dashed var(--border-2);
  border-radius: var(--r-md);
}

.result-card__pro-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.result-card__pro-hint {
  font-size: 12px;
  color: var(--ink-3);
  margin: 8px 0 0;
}

@media (max-width: 560px) {
  .result-card__grid,
  .result-card__row {
    grid-template-columns: 1fr;
  }
}
</style>
