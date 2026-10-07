<template>
  <div class="rp" :style="rootVars">
    <div class="rp-head">
      <div class="rp-crumb">
        <span class="chip">R{{ position }}</span>
        {{ resultOfTotal }}
        <span class="rp-tpl-badge">{{ preview.templateSlug }}</span>
      </div>
      <div class="rp-head__actions">
        <div class="rp-type">
          {{ typeLabel }}
        </div>
        <button
          type="button"
          class="rp-properties-btn"
          :aria-label="__('Open result properties')"
          @click="emit('open-properties')"
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <line x1="4" y1="6" x2="20" y2="6" />
            <line x1="4" y1="12" x2="20" y2="12" />
            <line x1="4" y1="18" x2="14" y2="18" />
          </svg>
          <span>{{ __('Properties') }}</span>
        </button>
      </div>
    </div>

    <!-- Score range bar — outside the result card for trivia quizzes -->
    <div v-if="quizType === 'trivia'" class="rp-tier">
      <span class="rp-tier__label">{{ __('Score range') }}</span>
      <span class="rp-tier__value">{{ result.score_min ?? 0 }} – {{ result.score_max ?? 0 }}</span>
      <span class="rp-tier__hint">{{ __('Edit the range in the right rail.') }}</span>
    </div>

    <!-- Magazine: editorial hero strip — outside card so it spans the full 760px preview width -->
    <div
      v-if="preview.templateSlug === 'magazine' && !isSplitscreen"
      class="rp-mag-hero"
      aria-hidden="true"
    >
      <div class="rp-mag-hero-meta">
        <span class="rp-mag-hero-issue">{{ resultOfTotal }}</span>
      </div>
    </div>

    <div class="rp-card" :class="[...cardClass, `is-align-${resultAlign}`]" :style="cardStyle">

      <!-- ── Standard (non-splitscreen) layout ──────────────────────────── -->
      <template v-if="!isSplitscreen">

        <!-- Gamified: gradient header with level + pips + score badge -->
        <div
          v-if="preview.templateSlug === 'gamified'"
          class="rp-game-head"
          aria-hidden="true"
        >
          <div class="rp-game-lvl">
            <span class="rp-game-lvl-num">{{ total }}</span>
            <span class="rp-game-lvl-label">{{ __('Done!') }}</span>
          </div>
          <div class="rp-game-pips">
            <span
              v-for="i in total"
              :key="i"
              class="rp-game-pip is-done"
            />
          </div>
          <div class="rp-game-score">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <span>{{ total * 10 }}</span>
          </div>
        </div>

        <div
          v-if="bg.overlayOpacity > 0"
          class="rp-card-overlay"
          :style="cardOverlayStyle"
          aria-hidden="true"
        />
        <div
          ref="titleEl"
          class="rp-title rp-title-ce"
          contenteditable="true"
          :data-placeholder="__('Result title')"
          :aria-label="resultTitleAria"
          @input="onTitleInput"
          @blur="flushTitle"
          @keydown.enter.prevent="flushTitle"
        />

        <!-- Image — shown after title, inline (not bleed).
             Fit and height read from result.settings; editing in the right sidebar. -->
        <div
          v-if="result.image_url"
          :class="['rp-image', `rp-image--${imageFit}`]"
          :style="imageHeightStyle"
        >
          <!-- Contain: <img> letterboxed — CLAUDE.md: object-fit contain -->
          <img
            v-if="imageFit === 'contain'"
            :src="result.image_url"
            :alt="result.title || __('Result image')"
          >
          <!-- Cover: background fills slot — CLAUDE.md: no object-fit:cover on <img> -->
          <div
            v-else-if="imageFit === 'cover'"
            class="rp-image__cover-bg"
            :style="{ backgroundImage: `url(${result.image_url})` }"
            role="img"
            :aria-label="result.title || __('Result image')"
          />
          <!-- Repeat: tile pattern -->
          <div
            v-else
            class="rp-image__tile"
            :style="{ backgroundImage: `url(${result.image_url})` }"
            role="img"
            :aria-label="result.title || __('Result image')"
          />
          <!-- Hover overlay: Replace / Remove -->
          <div class="rp-image__overlay" aria-hidden="true">
            <button type="button" class="rp-image__overlay-btn" @click.stop="openMediaPicker">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              {{ __('Replace') }}
            </button>
            <button type="button" class="rp-image__overlay-btn rp-image__overlay-btn--remove" @click.stop="removeImage">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              {{ __('Remove') }}
            </button>
          </div>
        </div>
        <button
          v-else
          type="button"
          class="rp-image-placeholder"
          :aria-label="__('Add image')"
          @click="openMediaPicker"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2" />
            <circle cx="8.5" cy="8.5" r="1.5" />
            <path d="M21 15l-5-5L5 21" />
          </svg>
          <span>{{ __('Add image') }}</span>
        </button>

        <RichTextEditor
          v-model="draftContent"
          class="rp-content-input"
          :toolbarless="true"
          :placeholder="__('Describe the result — what does it mean for the user?')"
          :aria-label="resultContentAria"
          @update:model-value="scheduleContentSave"
        />
        <!-- Chart preview -->
        <div v-if="chartPreviewType" :class="['rp-chart-preview', { 'rp-chart-preview--bar': chartPreviewType === 'bar' }]">
          <span class="rp-chart-preview__label">{{ chartPreviewLabel }}</span>
          <QuizResultChart
            :type="chartPreviewType"
            :segments="CHART_PREVIEW_SEGMENTS"
            :center-text="chartPreviewType === 'donut' ? '50%' : ''"
            :center-sub="chartPreviewType === 'donut' ? __('sample') : ''"
            class="rp-chart-preview__chart"
          />
        </div>
        <!-- Share preview -->
        <div v-if="sharePreviewEnabled && sharePreviewPlatforms.length" class="rp-share-preview">
          <span class="rp-share-preview__label">{{ __('Share buttons') }}</span>
          <div class="rp-share-preview__buttons">
            <span
              v-for="p in sharePreviewPlatforms"
              :key="p.key"
              class="rp-share-preview__btn"
              :style="{ '--sb-color': p.color }"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span class="rp-share-preview__btn-icon" v-html="p.icon" aria-hidden="true" />
              <span class="rp-share-preview__btn-label">{{ p.label }}</span>
            </span>
          </div>
        </div>
        <div
          v-if="result.cta_label && !result.redirect_url"
          class="rp-cta"
        >
          <span class="rp-cta__btn">{{ result.cta_label }}</span>
        </div>
        <div
          v-else-if="result.redirect_url"
          class="rp-redirect-note"
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
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 16 16 12 12 8" />
            <line x1="8" y1="12" x2="16" y2="12" />
          </svg>
          {{ __('Visitors are redirected — this card is not shown.') }}
        </div>
      </template>

      <!-- ── Splitscreen layout ──────────────────────────────────────────── -->
      <template v-else>
        <div class="rp-split-panel" :style="splitPanelStyle" @click="openMediaPicker">
          <div
            class="rp-split-placeholder"
            :class="{ 'rp-split-placeholder--replace': !!result.image_url }"
            aria-hidden="true"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <path d="M21 15l-5-5L5 21" />
            </svg>
            <span>{{ result.image_url ? __('Replace') : __('Add image') }}</span>
          </div>
        </div>

        <div class="rp-split-right">
          <div
            ref="titleEl"
            class="rp-title rp-title-ce"
            contenteditable="true"
            :data-placeholder="__('Result title')"
            :aria-label="resultTitleAria"
            @input="onTitleInput"
            @blur="flushTitle"
            @keydown.enter.prevent="flushTitle"
            @paste="onTitlePaste"
          />

          <!-- Image after title — same block as standard layout but inside right panel -->
          <div
            v-if="result.image_url"
            :class="['rp-image', `rp-image--${imageFit}`]"
            :style="imageHeightStyle"
          >
            <img v-if="imageFit === 'contain'" :src="result.image_url" :alt="result.title || __('Result image')">
            <div v-else-if="imageFit === 'cover'" class="rp-image__cover-bg" :style="{ backgroundImage: `url(${result.image_url})` }" role="img" :aria-label="result.title || __('Result image')" />
            <div v-else class="rp-image__tile" :style="{ backgroundImage: `url(${result.image_url})` }" role="img" :aria-label="result.title || __('Result image')" />
            <div class="rp-image__overlay" aria-hidden="true">
              <button type="button" class="rp-image__overlay-btn" @click.stop="openMediaPicker">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                {{ __('Replace') }}
              </button>
              <button type="button" class="rp-image__overlay-btn rp-image__overlay-btn--remove" @click.stop="removeImage">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                {{ __('Remove') }}
              </button>
            </div>
          </div>
          <button v-else type="button" class="rp-image-placeholder" :aria-label="__('Add image')" @click="openMediaPicker">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            <span>{{ __('Add image') }}</span>
          </button>

          <RichTextEditor
            v-model="draftContent"
            class="rp-content-input"
            :toolbarless="true"
            :placeholder="__('Describe the result — what does it mean for the user?')"
            :aria-label="resultContentAria"
            @blur="flushContent"
            @update:model-value="scheduleContentSave"
          />
          <!-- Chart preview -->
          <div v-if="chartPreviewType" :class="['rp-chart-preview', { 'rp-chart-preview--bar': chartPreviewType === 'bar' }]">
            <span class="rp-chart-preview__label">{{ chartPreviewLabel }}</span>
            <QuizResultChart
              :type="chartPreviewType"
              :segments="CHART_PREVIEW_SEGMENTS"
              :center-text="chartPreviewType === 'donut' ? '50%' : ''"
              :center-sub="chartPreviewType === 'donut' ? __('sample') : ''"
              class="rp-chart-preview__chart"
            />
          </div>
          <!-- Share preview -->
          <div v-if="sharePreviewEnabled && sharePreviewPlatforms.length" class="rp-share-preview">
            <span class="rp-share-preview__label">{{ __('Share buttons') }}</span>
            <div class="rp-share-preview__buttons">
              <span
                v-for="p in sharePreviewPlatforms"
                :key="p.key"
                class="rp-share-preview__btn"
                :style="{ '--sb-color': p.color }"
              >
                <!-- eslint-disable-next-line vue/no-v-html -->
                <span class="rp-share-preview__btn-icon" v-html="p.icon" aria-hidden="true" />
                <span class="rp-share-preview__btn-label">{{ p.label }}</span>
              </span>
            </div>
          </div>
          <div
            v-if="result.cta_label && !result.redirect_url"
            class="rp-cta"
          >
            <span class="rp-cta__btn">{{ result.cta_label }}</span>
          </div>
          <div
            v-else-if="result.redirect_url"
            class="rp-redirect-note"
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
              <circle cx="12" cy="12" r="10" />
              <polyline points="12 16 16 12 12 8" />
              <line x1="8" y1="12" x2="16" y2="12" />
            </svg>
            {{ __('Visitors are redirected — this card is not shown.') }}
          </div>
        </div>
      </template>

    </div>

    <!-- Floating title toolbar — Teleported to body so it layers above everything,
         works for both standard and splitscreen layouts. -->
    <Teleport to="body">
      <div
        v-if="titleBar.visible"
        class="rp-title-bar"
        :style="titleBar.posStyle"
        @mousedown.prevent
      >
        <!-- Alignment -->
        <button type="button" :class="['rp-title-bar__btn', { 'is-active': titleBar.align === 'left' }]" :title="__('Align left')" @click="applyTitleAlign('left')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
        </button>
        <button type="button" :class="['rp-title-bar__btn', { 'is-active': titleBar.align === 'center' }]" :title="__('Align center')" @click="applyTitleAlign('center')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
        </button>
        <button type="button" :class="['rp-title-bar__btn', { 'is-active': titleBar.align === 'right' }]" :title="__('Align right')" @click="applyTitleAlign('right')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="rp-title-bar__sep" />
        <!-- Bold / Italic / Underline -->
        <button type="button" :class="['rp-title-bar__btn', { 'is-active': titleBar.bold }]" :title="__('Bold')" @click="execTitle('bold')"><b>B</b></button>
        <button type="button" :class="['rp-title-bar__btn', { 'is-active': titleBar.italic }]" :title="__('Italic')" @click="execTitle('italic')"><i>I</i></button>
        <button type="button" :class="['rp-title-bar__btn', { 'is-active': titleBar.underline }]" :title="__('Underline')" @click="execTitle('underline')"><u>U</u></button>
        <div class="rp-title-bar__sep" />
        <!-- Color picker -->
        <label class="rp-title-bar__color" :title="__('Text color')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M9 7l6 10H3z"/><line x1="20" y1="17" x2="20" y2="22"/><line x1="17.5" y1="19.5" x2="22.5" y2="19.5"/></svg>
          <span class="rp-title-bar__color-swatch" :style="{ background: titleBar.color }" />
          <input ref="titleColorInputRef" type="color" class="rp-title-bar__color-input" :value="titleBar.color" @input="applyTitleColor($event.target.value)">
        </label>
      </div>
    </Teleport>

    <!-- Mapping panel — outside the preview card for both layouts -->
    <div v-if="quizType === 'personality'" class="rp-mapping">
      <div class="rp-mapping__head">
        <span class="rp-mapping__label">{{ __('Answers mapping to this result') }}</span>
        <span class="rp-mapping__meta">{{ sprintf(__('%d mapped'), mappedAnswers.length) }}</span>
      </div>
      <p class="rp-mapping__hint">{{ __('Edit the mapping inside individual questions in the Questions tab.') }}</p>
      <ul v-if="mappedAnswers.length" class="rp-mapping__list">
        <li v-for="m in mappedAnswers" :key="m.id">
          <span class="rp-mapping__q">{{ sprintf(__('Q%d'), m.qPos) }}</span>
          <span class="rp-mapping__a">{{ m.label || __('(untitled answer)') }}</span>
        </li>
      </ul>
      <p v-else class="rp-mapping__empty">{{ __('No answers map to this result yet.') }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { __, sprintf } from '@shared/i18n';
import { RichTextEditor, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { resolveBgStyle } from '@shared/bgStyle.js';
import { hexToRgba } from '@shared/colorUtils.js';
import { fullscreenStage } from '@shared/fullscreenStage.js';
import { useTabPreview } from '@admin/composables/useTabPreview.js';
import QuizResultChart from '@frontend/components/QuizResultChart.vue';

const props = defineProps({
  result: { type: Object, required: true },
  quizType: { type: String, default: 'personality' },
  questions: { type: Array, default: () => [] },
  position: { type: Number, required: true },
  total: { type: Number, required: true },
});

const emit = defineEmits(['open-properties']);

const store = useQuizBuilderStore();
const toast = useToast();
const preview = reactive(useTabPreview());

// ── Title contenteditable + floating toolbar ──────────────────────────────────
const titleEl = ref(null);
const titleColorInputRef = ref(null);

const titleBar = reactive({
  visible: false,
  bold: false,
  italic: false,
  underline: false,
  align: 'left',   // 'left' | 'center' | 'right'
  color: '#000000',
  posStyle: { left: '0px', top: '0px', transform: 'translateX(-50%)' },
});

function updateTitleBar() {
  const sel = window.getSelection();
  if (!sel || sel.isCollapsed || !titleEl.value?.contains(sel.anchorNode)) {
    titleBar.visible = false;
    return;
  }
  const rect = sel.getRangeAt(0).getBoundingClientRect();
  titleBar.visible   = true;
  titleBar.bold      = document.queryCommandState('bold');
  titleBar.italic    = document.queryCommandState('italic');
  titleBar.underline = document.queryCommandState('underline');
  const el = sel.anchorNode?.parentElement;
  const align = el ? window.getComputedStyle(el).textAlign : 'left';
  titleBar.align = (align === 'center' || align === 'right') ? align : 'left';
  titleBar.posStyle = {
    left:      `${rect.left + rect.width / 2 + window.scrollX}px`,
    top:       `${rect.top  + window.scrollY - 54}px`,
    transform: 'translateX(-50%)',
  };
}

function execTitle(cmd, value = null) {
  document.execCommand(cmd, false, value);
  titleEl.value?.focus();
  updateTitleBar();
}

function applyTitleAlign(align) {
  const cmd = align === 'center' ? 'justifyCenter' : align === 'right' ? 'justifyRight' : 'justifyLeft';
  execTitle(cmd);
  titleBar.align = align;
}

function applyTitleColor(color) {
  titleBar.color = color;
  execTitle('foreColor', color);
}

function initTitleEl() {
  if (titleEl.value !== null && props.result.title !== undefined) {
    titleEl.value.innerHTML = props.result.title ?? '';
  }
}

const isSplitscreen = computed(() => preview.templateSlug === 'splitscreen');

const fontScale = computed(() => Math.min(1.5, Math.max(0.7, (Number(store.quiz?.design?.font_size) || 100) / 100)));

const rootVars = computed(() => {
  const c = preview.colors;
  return {
    '--rp-primary': c.primary || 'var(--brand)',
    '--rp-text':    c.text    || 'var(--ink-1)',
    '--rp-bg':      hexToRgba(c.background || '#ffffff', preview.backgroundOpacity),
    // Full Screen stage: the same definition the live template paints from.
    '--rp-stage':   fullscreenStage('var(--rp-text)', 'var(--rp-primary)'),
    '--quizably-font-scale': fontScale.value,
  };
});

const cardStyle = computed(() => {
  if (isSplitscreen.value) return {};
  return bg.value.backgroundImage
    ? { backgroundImage: `url("${bg.value.backgroundImage}")` }
    : {};
});

const splitPanelStyle = computed(() => {
  if (!isSplitscreen.value || !props.result.image_url) return {};
  return { backgroundImage: `url("${props.result.image_url}")` };
});

const cardClass = computed(() => [
  `rp-tpl--${preview.templateSlug}`,
  `rp-btn--${preview.buttonStyle}`,
  isSplitscreen.value ? `is-content-${preview.splitContentAlign}` : '',
]);

// ── Alignment — driven by ResultProperties sidebar ────────────────────────────
const resultAlign = computed(() => {
  const s = props.result.settings;
  const settings = s && typeof s === 'object' ? s : {};
  const v = settings?.align;
  return v === 'left' || v === 'right' ? v : 'center';
});


const DEFAULT_CONTENT = '<p>' + __('Describe what this result means for your audience. Explain the key traits it reflects and suggest a clear next step they can take.') + '</p>';

const draftTitle = ref(props.result.title ?? '');
const draftContent = ref(props.result.content || DEFAULT_CONTENT);

watch(
  () => props.result.id,
  () => {
    draftTitle.value = props.result.title ?? '';
    draftContent.value = props.result.content || DEFAULT_CONTENT;
    nextTick(initTitleEl);
  }
);

watch(
  () => props.result.title,
  (v) => {
    if (v !== draftTitle.value) {
      draftTitle.value = v ?? '';
      if (titleEl.value) titleEl.value.innerHTML = v ?? '';
    }
  }
);
watch(
  () => props.result.content,
  (v) => { if (v !== draftContent.value) draftContent.value = v || DEFAULT_CONTENT; }
);

// When the layout switches (splitscreen ↔ standard), Vue remounts the contenteditable —
// re-seed it with the current draft so the DOM stays in sync.
watch(isSplitscreen, () => nextTick(initTitleEl));

// Safety net: called by quizably:flush-pending-saves (fired by the Save button)
// before store.flushAllStaged(). Ensures the latest contenteditable DOM value
// and the RichTextEditor draft are staged even if blur hasn't fired yet.
function flushAllPending() {
  if (titleEl.value) {
    store.stageResult(props.result.id, { title: (titleEl.value.innerHTML ?? '').trim() });
  }
  store.stageResult(props.result.id, { content: (draftContent.value ?? '').toString() });
}

onMounted(() => {
  initTitleEl();
  document.addEventListener('selectionchange', updateTitleBar);
  window.addEventListener('quizably:flush-pending-saves', flushAllPending);
});

onBeforeUnmount(() => {
  document.removeEventListener('selectionchange', updateTitleBar);
  window.removeEventListener('quizably:flush-pending-saves', flushAllPending);
});

// translators: 1: result position, 2: total number of results.
const resultOfTotal = computed(() => sprintf(__('Result %1$s of %2$s'), props.position, props.total));
// translators: %d: result position (accessible name of the title editor).
const resultTitleAria = computed(() => sprintf(__('Result %d title'), props.position));
// translators: %d: result position (accessible name of the content editor).
const resultContentAria = computed(() => sprintf(__('Result %d content'), props.position));

const typeLabel = computed(() => {
  switch (props.quizType) {
    case 'personality': return __('Personality result');
    case 'trivia':      return __('Score tier');
    case 'weighted':    return __('Weighted result');
    case 'survey':
    case 'poll':        return __('Thank-you screen');
    default:            return __('Result');
  }
});

// Per-result background wins over the quiz-level design background.
// Mirrors Quiz.vue's currentScreenBg for the result screen.
const bg = computed(() => {
  const s = props.result?.settings;
  const settings = s && typeof s === 'object' ? s : {};
  return resolveBgStyle(settings.background ?? null, store.quiz?.design);
});

const cardOverlayStyle = computed(() => ({
  background: bg.value.overlayColor,
  opacity: String(bg.value.overlayOpacity / 100),
}));

const chartPreviewType = computed(() => {
  const s = props.result?.settings;
  const settings = s && typeof s === 'object' ? s : {};
  const t = settings?.chart?.type;
  return t && t !== 'none' ? t : null;
});

const chartPreviewLabel = computed(() => {
  switch (chartPreviewType.value) {
    case 'pie':   return __('pie chart preview');
    case 'donut': return __('donut chart preview');
    case 'bar':   return __('bar chart preview');
    default:      return __('chart preview');
  }
});

const CHART_PREVIEW_SEGMENTS = [
  { label: __('Explorer'), value: 6, pct: 50, color: '#6d28d9' },
  { label: __('Creator'),  value: 4, pct: 33, color: '#10b981' },
  { label: __('Analyst'),  value: 2, pct: 17, color: '#f59e0b' },
];

const SHARE_PLATFORM_DEFS = {
  facebook: { key: 'facebook', label: 'Facebook', color: '#1877f2', icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M12 2H10a4 4 0 0 0-4 4v2H4v3h2v5h3v-5h2l.5-3H9V6a.5.5 0 0 1 .5-.5H12V2z"/></svg>` },
  twitter:  { key: 'twitter',  label: 'X',        color: '#000000', icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M12.6 2h2.2L9.7 7.7 15.5 14h-4l-3.7-4.9L3.9 14H1.7l5.4-6.2L1.5 2H5.6l3.3 4.4L12.6 2zm-.8 10.8h1.2L4.3 3.2H3l8.8 9.6z"/></svg>` },
  linkedin: { key: 'linkedin', label: 'LinkedIn', color: '#0a66c2', icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M11 5.5a4 4 0 0 1 4 4V14h-3v-4a1 1 0 0 0-1-1 1 1 0 0 0-1 1v4H7V5.5h3v1a3 3 0 0 1 2-.5 1.5 1.5 0 0 0-1 0zM1 6h3v8H1zM2.5 1a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/></svg>` },
  whatsapp: { key: 'whatsapp', label: 'WhatsApp', color: '#25d366', icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 0 0-6.1 10.4L1 15l3.7-.9A7 7 0 1 0 8 1zm3.6 9.6c-.2.4-.9.8-1.3.8-.3 0-.6.1-1.8-.4C6.9 10.3 6 8.8 5.9 8.7c-.1-.1-.8-1-.8-2s.5-1.4.7-1.6c.2-.2.4-.3.6-.3h.4c.2 0 .3 0 .4.3.2.4.6 1.4.6 1.5 0 .1 0 .2-.1.3l-.3.3c-.1.1-.2.2-.1.4.1.2.6.9 1.2 1.4.6.5 1.1.7 1.3.8.2.1.3 0 .4-.1l.3-.4c.1-.2.2-.2.4-.1l1.1.5c.2.1.3.2.3.3 0 .2-.1.6-.3 1z"/></svg>` },
  telegram: { key: 'telegram', label: 'Telegram', color: '#26a5e4', icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.4 4.8-1.7 8c-.1.5-.5.6-.8.4l-2-1.5-1 .9c-.1.1-.2.2-.5.2l.2-2.1 4.4-4c.2-.2 0-.3-.3-.1L4.5 10.7 2.6 10c-.5-.1-.5-.5.1-.7l9-3.5c.4-.1.8.1.7.9z"/></svg>` },
  email:    { key: 'email',    label: __('Email'),    color: '#6b7280', icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="m1 4 7 5 7-5"/></svg>` },
};

const sharePreviewEnabled = computed(() => {
  const s = props.result?.settings;
  const settings = s && typeof s === 'object' ? s : {};
  return settings?.share?.enabled !== false;
});

const sharePreviewPlatforms = computed(() => {
  const s = props.result?.settings;
  const settings = s && typeof s === 'object' ? s : {};
  const keys = settings?.share?.platforms ?? ['facebook', 'twitter', 'linkedin'];
  return keys.map((k) => SHARE_PLATFORM_DEFS[k]).filter(Boolean);
});

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

function onTitleInput() {
  draftTitle.value = titleEl.value?.innerHTML ?? '';
  store.stageResult(props.result.id, { title: draftTitle.value });
}

// Strip all formatting on paste — insert plain text only so the title stays clean.
function onTitlePaste(e) {
  e.preventDefault();
  const text = (e.clipboardData || window.clipboardData).getData('text/plain');
  document.execCommand('insertText', false, text);
}

function flushTitle() {
  const next = (titleEl.value?.innerHTML ?? '').trim();
  store.stageResult(props.result.id, { title: next });
  // No auto-save: API write happens only when the user explicitly clicks Save,
  // which dispatches quizably:flush-pending-saves then calls store.flushAllStaged().
}

function scheduleContentSave() {
  store.stageResult(props.result.id, { content: (draftContent.value ?? '').toString() });
}

function flushContent() { scheduleContentSave(); }

// ── Image fit — read-only in the preview; editing happens in the sidebar ──────
const imageFit = computed(() => {
  const s = props.result?.settings;
  const settings = s && typeof s === 'object' ? s : {};
  const v = settings.image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

// ── Image height — driven by the ResultProperties height slider ───────────────
const DEFAULT_IMAGE_HEIGHT = 240;
const imageHeightStyle = computed(() => {
  const s = props.result?.settings;
  const settings = s && typeof s === 'object' ? s : {};
  const h = Number(settings.image_height);
  return Number.isFinite(h) && h > 0
    ? { height: `${h}px` }
    : { height: `${DEFAULT_IMAGE_HEIGHT}px` };
});

// ── WP media picker — opens directly from the preview canvas ─────────────────
let _wpFrame = null;

function openMediaPicker() {
  if (typeof window === 'undefined' || !window.wp?.media) {
    const url = window.prompt(__('Enter image URL'), props.result.image_url || '');
    if (url !== null) saveMediaUrl(url);
    return;
  }
  if (!_wpFrame) {
    _wpFrame = window.wp.media({
      title: __('Select image'),
      button: { text: __('Use this image') },
      library: { type: 'image' },
      multiple: false,
    });
    _wpFrame.on('select', () => {
      const att = _wpFrame.state().get('selection').first().toJSON();
      saveMediaUrl(att.url);
    });
  }
  _wpFrame.open();
}

function saveMediaUrl(url) {
  const next = (url ?? '').toString();
  if (next === (props.result.image_url ?? '')) return;
  store.stageResult(props.result.id, { image_url: next });
  store.flushStagedResults();
}

function removeImage() {
  try {
    store.stageResult(props.result.id, { image_url: '' });
    store.flushStagedResults();
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not remove image'), message: e.message });
  }
}

</script>

<style scoped>
.rp {
  max-width: 760px;
  min-width: 0;
  width: 100%;
  margin: 0 auto;
  padding: 28px 32px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.rp-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.rp-crumb {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.rp-crumb .chip {
  padding: 3px 8px;
  background: var(--brand);
  color: #fff;
  border-radius: var(--r-xs);
  font-weight: 600;
}

.rp-head__actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.rp-type {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  font-size: 12.5px;
  color: var(--ink-2);
  box-shadow: var(--shadow-xs);
}

.rp-properties-btn {
  display: none;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  font-family: inherit;
  font-size: 12.5px;
  font-weight: 500;
  color: var(--ink-2);
  cursor: pointer;
}

.rp-properties-btn:hover {
  border-color: var(--brand);
  color: var(--brand);
}

.rp-properties-btn svg {
  width: 14px;
  height: 14px;
}

/* ── Result card ── */
.rp-card {
  background-color: var(--rp-bg, var(--bg-surface));
  background-size: cover;
  background-position: center;
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  padding: 28px 32px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  position: relative;
  overflow: hidden;
}

.rp-card-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.rp-card > *:not(.rp-card-overlay) {
  position: relative;
  z-index: 1;
}

/* ── Alignment variants ──────────────────────────────────────────────────────── */
.rp-card.is-align-left   { text-align: left;   align-items: flex-start; } /* rtl-ok: author-chosen physical alignment setting ("left"/"right" is the stored value) */
.rp-card.is-align-center { text-align: center; align-items: center; }
.rp-card.is-align-right  { text-align: right;  align-items: flex-end; } /* rtl-ok: author-chosen physical alignment setting ("left"/"right" is the stored value) */

/* ── Title (contenteditable) — reads like a heading ── */
.rp-title {
  width: 100%;
  font-family: var(--f-display);
  font-size: calc(26px * var(--quizably-font-scale, 1));
  font-weight: 400;
  letter-spacing: -0.01em;
  color: var(--rp-text, var(--ink-1));
  line-height: 1.25;
  margin: 0;
  word-break: break-word;
  background: transparent;
  outline: none;
  padding: 0;
  min-height: 1.3em;
}

/* Empty placeholder via CSS ::before — mirrors qp-title-ce pattern */
.rp-title-ce:empty::before {
  content: attr(data-placeholder);
  color: color-mix(in srgb, var(--rp-text, var(--ink-4)) 40%, transparent);
  font-style: italic;
  pointer-events: none;
}

.rp-title-ce:focus {
  border-bottom: 1px dashed color-mix(in srgb, var(--rp-text, var(--ink-3)) 30%, transparent);
}

/* ── Content editor — toolbarless TipTap styled as body text ── */
.rp-content-input {
  width: 100%;
  font-family: var(--f-sans);
  font-size: calc(17px * var(--quizably-font-scale, 1));
  line-height: 1.65;
  color: var(--rp-text, var(--ink-2));
  /* align-self: stretch keeps this full-width regardless of the parent's align-items */
  align-self: stretch;
}

/* Ensure deep TipTap elements inherit the card's color token */
.rp-content-input :deep(.quizably-rte__doc) {
  font-size: calc(17px * var(--quizably-font-scale, 1));
  font-family: var(--f-sans);
  line-height: 1.65;
}

.rp-content-input :deep(.quizably-rte__doc p) { margin: 0 0 8px; }
.rp-content-input :deep(.quizably-rte__doc p:last-child) { margin-bottom: 0; }
.rp-content-input :deep(.quizably-rte__doc strong) { font-weight: 700; }
.rp-content-input :deep(.quizably-rte__doc em) { font-style: italic; }
.rp-content-input :deep(.quizably-rte__doc u) { text-decoration: underline; }
.rp-content-input :deep(.quizably-rte__doc ul),
.rp-content-input :deep(.quizably-rte__doc ol) { margin: 4px 0 8px; padding-inline-start: 22px; }
.rp-content-input :deep(.quizably-rte__doc li) { margin-bottom: 3px; }
.rp-content-input :deep(.quizably-rte__doc blockquote) {
  margin: 6px 0;
  padding: 6px 14px;
  border-inline-start: 3px solid var(--border-3);
  color: var(--ink-2);
  background: var(--bg-canvas);
  border-start-start-radius: 0;
  border-start-end-radius: var(--r-sm);
  border-end-end-radius: var(--r-sm);
  border-end-start-radius: 0;
  font-style: italic;
}
.rp-content-input :deep(.quizably-rte__doc a) {
  color: var(--rp-primary, var(--brand));
  text-decoration: underline;
}

/* ── CTA button preview ── */
/* No align-self override — the card's align-items (left/center/right) positions this. */
.rp-cta {
  margin-top: 4px;
}

.rp-cta__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 11px 24px;
  background: var(--rp-primary, var(--brand));
  color: #fff;
  border-radius: var(--r-md);
  font-family: var(--f-sans);
  font-size: calc(14px * var(--quizably-font-scale, 1));
  font-weight: 600;
  line-height: 1;
  cursor: default;
  user-select: none;
}

/* ── Redirect notice ── */
/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.rp-redirect-note {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  font-size: 12.5px;
  color: var(--ink-3);
  align-self: stretch;
  width: 100%;
}

.rp-redirect-note svg { width: 14px; height: 14px; flex-shrink: 0; }

/* ── Chart preview ── */
/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.rp-chart-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 14px 16px;
  background: color-mix(in srgb, var(--rp-bg, var(--bg-surface)) 70%, var(--bg-canvas));
  border: 1px dashed var(--border-2);
  border-radius: var(--r-md);
  align-self: stretch;
  width: 100%;
}

.rp-chart-preview__label {
  font-family: var(--f-mono);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--ink-4);
}

.rp-chart-preview__chart {
  --quizably-result-bg: var(--rp-bg, var(--bg-surface));
  width: 100%;
  max-width: 180px;
}

.rp-chart-preview--bar .rp-chart-preview__chart {
  max-width: none;
}

/* ── Share preview ── */
.rp-share-preview {
  display: flex;
  flex-direction: column;
  gap: 6px;
  /* align-items: inherit propagates the card's left/center/right alignment
     into the share label and buttons row. */
  align-items: inherit;
}

.rp-share-preview__label {
  font-family: var(--f-mono);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--ink-4);
}

.rp-share-preview__buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.rp-share-preview__btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 11px;
  background: var(--sb-color, var(--brand));
  color: #fff;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 500;
  font-family: var(--f-sans);
  cursor: default;
  user-select: none;
}

.rp-share-preview__btn-icon {
  display: inline-flex;
  align-items: center;
  width: 13px;
  height: 13px;
}

.rp-share-preview__btn-icon :deep(svg) {
  width: 13px;
  height: 13px;
}

.rp-share-preview__btn-label { line-height: 1; }

/* ── Inline image (after title) ── */
/* Height driven by imageHeightStyle inline style (default 240px). */
/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.rp-image {
  width: 100%;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  overflow: hidden;
  position: relative;
  display: flex;
  align-items: stretch;
  flex-shrink: 0;
  align-self: stretch;
}

/* Contain: <img> letterboxed — CLAUDE.md: object-fit contain on <img> */
.rp-image--contain img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: var(--bg-subtle);
  display: block;
}

/* Cover: background fills slot */
.rp-image__cover-bg {
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

/* Repeat: tile pattern */
.rp-image__tile {
  width: 100%;
  height: 100%;
  background-repeat: repeat;
  background-position: 0 0;
  background-size: auto;
}

/* Replace / remove overlay — revealed on hover */
.rp-image__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(0, 0, 0, 0.45);
  opacity: 0;
  transition: opacity 150ms;
}

.rp-image:hover .rp-image__overlay { opacity: 1; }

.rp-image__overlay-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 13px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: var(--r-sm);
  color: #fff;
  font: inherit;
  font-size: 12.5px;
  font-weight: 500;
  cursor: pointer;
  transition: background 120ms;
  backdrop-filter: blur(4px);
}

.rp-image__overlay-btn:hover { background: rgba(255, 255, 255, 0.25); }
.rp-image__overlay-btn--remove:hover { background: rgba(220, 38, 38, 0.55); border-color: rgba(255,255,255,.4); }
.rp-image__overlay-btn svg { width: 13px; height: 13px; flex-shrink: 0; }

/* ── Image placeholder (inline, after title) ── */
/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.rp-image-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  min-height: 80px;
  padding: 20px 16px;
  background: var(--bg-canvas);
  border: 1.5px dashed var(--border-2);
  border-radius: var(--r-md);
  color: var(--ink-3);
  font-family: var(--f-mono);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: border-color 150ms, background 150ms, color 150ms;
  align-self: stretch;
}

.rp-image-placeholder:hover {
  border-color: var(--brand);
  background: color-mix(in srgb, var(--brand) 5%, var(--bg-canvas));
  color: var(--brand);
}

.rp-image-placeholder svg {
  width: 20px;
  height: 20px;
}

/* ── Personality mapping ── */
.rp-mapping {
  margin-top: 12px;
  padding: 14px 16px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
}

.rp-mapping__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 4px;
}

.rp-mapping__label {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
}

.rp-mapping__meta {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
}

.rp-mapping__hint {
  font-size: 12.5px;
  color: var(--ink-3);
  margin: 0 0 10px;
}

.rp-mapping__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.rp-mapping__list li {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 10px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  font-size: 13px;
  color: var(--ink-2);
}

.rp-mapping__q {
  font-family: var(--f-mono);
  font-size: 10.5px;
  font-weight: 600;
  color: var(--brand);
  background: var(--brand-tint);
  padding: 2px 6px;
  border-radius: var(--r-xs);
}

.rp-mapping__a {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.rp-mapping__empty {
  font-size: 13px;
  color: var(--ink-4);
  margin: 0;
  font-style: italic;
}

/* ── Score tier bar (outside the card) ── */
.rp-tier {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  margin-bottom: 2px;
}

.rp-tier__label {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--ink-3);
}

.rp-tier__value {
  font-family: var(--f-display);
  font-size: 18px;
  font-weight: 500;
  color: var(--ink-1);
}

.rp-tier__hint { font-size: 12px; color: var(--ink-4); }

/* ── Template badge ── */
.rp-tpl-badge {
  padding: 2px 7px;
  background: var(--brand-tint);
  color: var(--brand);
  border-radius: var(--r-xs);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: lowercase;
}

/* ── Card template variants ── */
.rp-card {
  --rp-primary: var(--brand);
  --rp-text:    var(--ink-1);
  --rp-bg:      var(--bg-surface);
}

.rp-card.rp-tpl--minimal   { border-color: transparent; box-shadow: none; }
.rp-card.rp-tpl--fullscreen {
  /* Same stage as the live template (src/shared/fullscreenStage.js). background-image, not the
     shorthand, so an author's bg image keeps the card's cover sizing. */
  background-image: var(--rp-stage);
  border-color: transparent;
  color: #fff;
}
.rp-card.rp-tpl--cardstack {
  box-shadow:
    0 8px 0 -4px color-mix(in srgb, var(--border-1) 60%, transparent),
    0 16px 0 -8px color-mix(in srgb, var(--border-1) 30%, transparent),
    var(--shadow-md);
}
.rp-card.rp-tpl--conversational { border-top: 4px solid var(--rp-primary); border-radius: var(--r-xl); }
/* Gamified: gradient header strip drives identity */
.rp-card.rp-tpl--gamified {
  padding-top: 0;
  border: 2px solid var(--rp-primary);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--rp-primary) 12%, transparent), var(--shadow-sm);
}

/* Splitscreen: two-column layout */
.rp-card.rp-tpl--splitscreen {
  flex-direction: row;
  padding: 0;
  gap: 0;
  border-inline-start: none;
  min-height: 380px;
  max-height: 70vh;
}

.rp-split-panel { display: none; }

.rp-tpl--splitscreen .rp-split-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42%;
  flex-shrink: 0;
  align-self: stretch;
  background-color: color-mix(in srgb, var(--rp-primary) 85%, #000);
  background-size: cover;
  background-position: center;
  border-start-start-radius: calc(var(--r-lg) - 1px);
  border-start-end-radius: 0;
  border-end-end-radius: 0;
  border-end-start-radius: calc(var(--r-lg) - 1px);
  position: relative;
  overflow: hidden;
}

.rp-split-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  height: 100%;
  position: absolute;
  inset: 0;
  color: rgba(255,255,255,.5);
  background: transparent;
  opacity: 0;
  transition: opacity 150ms, background 150ms;
  z-index: 2;
  cursor: pointer;
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.04em;
}

/* Show hint on hover; always show when no image is set */
.rp-tpl--splitscreen .rp-split-panel:not([style*="background-image"]) .rp-split-placeholder,
.rp-tpl--splitscreen .rp-split-panel:hover .rp-split-placeholder {
  opacity: 1;
  background: rgba(0,0,0,.32);
}

.rp-split-placeholder svg { width: 24px; height: 24px; }

.rp-split-right { display: none; }

.rp-tpl--splitscreen .rp-split-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 28px 32px;
  min-width: 0;
  overflow-x: hidden;
  overflow-y: auto;
  justify-content: flex-start;
  align-self: stretch;
}

/* Prevent flex from shrinking children so overflow-y scroll actually fires */
.rp-tpl--splitscreen .rp-split-right > * {
  flex-shrink: 0;
}

.rp-card.is-content-top    .rp-split-right { justify-content: flex-start; }
.rp-card.is-content-center .rp-split-right { justify-content: center; }
.rp-card.is-content-bottom .rp-split-right { justify-content: flex-end; }

/* justify-content other than flex-start breaks overflow scroll (content renders above scroll origin).
   Force flex-start for splitscreen so the scrollbar works correctly. */
.rp-card.rp-tpl--splitscreen.is-content-center .rp-split-right,
.rp-card.rp-tpl--splitscreen.is-content-bottom .rp-split-right { justify-content: flex-start; }

/* Magazine: hero is outside the card; card pulls up flush to the strip with negative margin */
.rp-card.rp-tpl--magazine { border-top: none; border-radius: 0; margin-top: -14px; }

.rp-tpl--fullscreen .rp-title         { color: #fff; }
.rp-tpl--fullscreen .rp-content-input { color: rgba(255,255,255,.8); }
.rp-tpl--fullscreen .rp-title-ce:empty::before { color: rgba(255,255,255,.3); }

@media (max-width: 1023px) {
  .rp-properties-btn {
    display: inline-flex;
  }
}

/* ── Gamified header strip ──────────────────────────────────────────────────── */
/* rp-card has padding: 28px 32px — use negative margins to flush strip to card edges */
.rp-game-head {
  margin: 0 -32px;
  padding: 14px 20px;
  background: linear-gradient(135deg, var(--rp-primary, #4F46E5), color-mix(in srgb, var(--rp-primary, #4F46E5) 60%, #F59E0B));
  color: #fff;
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  z-index: 2;
}

.rp-game-lvl {
  display: flex;
  align-items: baseline;
  gap: 5px;
  flex-shrink: 0;
}

.rp-game-lvl-num {
  font-family: var(--f-display);
  font-size: 22px;
  font-weight: 600;
  line-height: 1;
}

.rp-game-lvl-label {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, .8);
}

.rp-game-pips {
  flex: 1;
  display: flex;
  gap: 4px;
  align-items: center;
}

.rp-game-pip {
  flex: 1;
  height: 5px;
  border-radius: var(--r-pill);
  background: rgba(255, 255, 255, .25);
}

.rp-game-pip.is-done { background: #fff; }

.rp-game-score {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  background: rgba(0, 0, 0, .18);
  border-radius: var(--r-pill);
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 600;
  flex-shrink: 0;
}

.rp-game-score svg { width: 12px; height: 12px; fill: #FCD34D; color: #FCD34D; }

/* ── Magazine hero strip ────────────────────────────────────────────────────── */
.rp-mag-hero {
  margin: 0 -32px;
  height: 72px;
  background: linear-gradient(135deg,
    color-mix(in srgb, var(--rp-primary, #4F46E5) 30%, var(--rp-text, #0a0a0b)),
    var(--rp-text, #0a0a0b)
  );
  border-bottom: 4px solid color-mix(in srgb, var(--rp-primary, #4F46E5) 60%, #F59E0B);
  position: relative;
  z-index: 2;
  display: flex;
  align-items: flex-end;
  padding: 0 22px 10px;
  overflow: hidden;
}

.rp-mag-hero-issue {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, .9);
  background: rgba(0, 0, 0, .3);
  padding: 3px 8px;
}
</style>

<style>
/* Global — rp-title-bar is Teleported to <body>, so scoped won't reach it */
.rp-title-bar {
  position: absolute;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 1px;
  background: #1c1c1e;
  border-radius: 8px;
  padding: 5px 7px;
  box-shadow: 0 4px 20px rgba(0,0,0,.4), 0 1px 3px rgba(0,0,0,.2);
  pointer-events: auto;
  user-select: none;
}
.rp-title-bar::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%; /* rtl-ok: centring pair with translateX(-50%), symmetric in both directions */
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #1c1c1e;
}
.rp-title-bar__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 26px;
  min-width: 26px;
  padding: 0 5px;
  border: 0;
  border-radius: 5px;
  background: transparent;
  color: rgba(255,255,255,.65);
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 100ms, color 100ms;
  line-height: 1;
}
.rp-title-bar__btn:hover { background: rgba(255,255,255,.1); color: #fff; }
.rp-title-bar__btn.is-active { background: rgba(255,255,255,.18); color: #fff; }
.rp-title-bar__sep {
  width: 1px;
  height: 16px;
  background: rgba(255,255,255,.15);
  margin: 0 4px;
  flex-shrink: 0;
}
.rp-title-bar__color {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 2px;
  height: 26px;
  width: 30px;
  padding: 0 3px;
  border-radius: 5px;
  cursor: pointer;
  color: rgba(255,255,255,.65);
  transition: background 100ms, color 100ms;
}
.rp-title-bar__color:hover { background: rgba(255,255,255,.1); color: #fff; }
.rp-title-bar__color-swatch {
  display: block;
  width: 16px;
  height: 3px;
  border-radius: 2px;
  margin-top: -1px;
}
.rp-title-bar__color-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}
</style>
