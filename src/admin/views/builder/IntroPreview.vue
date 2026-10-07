<template>
  <div class="ip" :style="rootVars">
    <div class="ip-head">
      <div class="ip-crumb">
        <span class="chip">{{ __('Intro') }}</span>
        {{ __('Welcome screen') }}
        <span class="ip-tpl-badge">{{ preview.templateSlug }}</span>
      </div>
      <div class="ip-head__actions">
        <button
          type="button"
          class="ip-properties-btn"
          :aria-label="__('Open intro properties')"
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

    <!-- Magazine: editorial hero strip — outside card, spans the full 760px preview width -->
    <div
      v-if="preview.templateSlug === 'magazine' && !isSplitscreen"
      class="ip-mag-hero"
      aria-hidden="true"
    >
      <div class="ip-mag-hero-meta">
        <span class="ip-mag-hero-issue">{{ sprintf(_n('Welcome · %d question', 'Welcome · %d questions', totalQuestions), totalQuestions) }}</span>
      </div>
    </div>

    <div class="ip-card" :class="cardClass" :style="cardStyle">

      <!-- ── Standard (non-splitscreen) layout ──────────────────────────── -->
      <template v-if="!isSplitscreen">

        <!-- Gamified: gradient header with level + pips + score badge -->
        <div
          v-if="preview.templateSlug === 'gamified'"
          class="ip-game-head"
          aria-hidden="true"
        >
          <div class="ip-game-lvl">
            <span class="ip-game-lvl-num">1</span>
            <span class="ip-game-lvl-label">{{ __('Level') }}</span>
          </div>
          <div class="ip-game-pips">
            <span
              v-for="i in 5"
              :key="i"
              :class="['ip-game-pip', { 'is-done': i === 1, 'is-current': i === 1 }]"
            />
          </div>
          <div class="ip-game-score">
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
            <span>0</span>
          </div>
        </div>

        <div
          v-if="bg.overlayOpacity > 0"
          class="ip-card-overlay"
          :style="cardOverlayStyle"
          aria-hidden="true"
        />
        <div
          v-if="screens.intro_bg_enabled"
          class="ip-bg"
          :style="screens.intro_bg_image ? { backgroundImage: `url(${screens.intro_bg_image})` } : {}"
          aria-hidden="true"
        />
        <div
          v-if="screens.intro_bg_enabled"
          class="ip-bg-overlay"
          :style="{ opacity: introBgOpacityDecimal }"
          aria-hidden="true"
        />
        <div :class="['ip-content', `is-align-${introAlign}`]">
          <div
            ref="titleEl"
            class="ip-title ip-title-ce"
            contenteditable="true"
            :data-placeholder="__('Ready to find out?')"
            :aria-label="__('Intro title')"
            @input="onTitleInput"
            @blur="flushTitle"
            @keydown.enter.prevent="flushTitle"
            @paste="onTitlePaste"
          />

          <!-- Image — after title, before subtitle. Opens WP media directly on click. -->
          <div
            v-if="screens.intro_cover"
            :class="['ip-image', `ip-image--${introImageFit}`]"
            :style="introImageHeightStyle"
          >
            <div
              v-if="introImageFit === 'repeat'"
              class="ip-image__tile"
              :style="{ backgroundImage: `url(${screens.intro_cover})` }"
              role="img"
              :aria-label="screens.intro_title || __('Intro image')"
            />
            <div
              v-else-if="introImageFit === 'cover'"
              class="ip-image__cover-bg"
              :style="{ backgroundImage: `url(${screens.intro_cover})` }"
              role="img"
              :aria-label="screens.intro_title || __('Intro image')"
            />
            <img
              v-else
              :src="screens.intro_cover"
              :alt="screens.intro_title || __('Intro image')"
            >
            <div class="ip-image__overlay" aria-hidden="true">
              <button type="button" class="ip-image__overlay-btn" @click.stop="openMediaPicker">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                {{ __('Replace') }}
              </button>
              <button type="button" class="ip-image__overlay-btn ip-image__overlay-btn--remove" @click.stop="removeImage">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                {{ __('Remove') }}
              </button>
            </div>
          </div>
          <button
            v-else
            type="button"
            class="ip-image-placeholder"
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
            v-model="draftSubtitle"
            class="ip-subtitle-rte"
            :toolbarless="true"
            :placeholder="__('A short hook explaining what the quiz is about.')"
            :aria-label="__('Intro subtitle')"
            @update:model-value="onSubtitleUpdate"
          />

          <div v-if="showMeta" class="ip-meta">
            <span v-if="totalQuestions" class="ip-meta__item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" /><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" /><path d="M12 17h.01" />
              </svg>
              {{ sprintf(_n('%d question', '%d questions', totalQuestions), totalQuestions) }}
            </span>
            <span class="ip-meta__item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
              </svg>
              {{ sprintf(__('~%d min'), estimatedMinutes) }}
            </span>
            <span v-if="totalResults" class="ip-meta__item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
              </svg>
              {{ sprintf(_n('%d result', '%d results', totalResults), totalResults) }}
            </span>
          </div>
          <button type="button" class="ip-cta">
            {{ screens.intro_button || __('Start quiz') }}
            <svg class="q-flip-rtl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M13 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </template>

      <!-- ── Splitscreen layout ──────────────────────────────────────────── -->
      <template v-else>
        <!-- Left image panel — intro_cover fills as CSS background; click to pick -->
        <div class="ip-split-panel" :style="splitPanelStyle" @click="openMediaPicker">
          <div
            class="ip-split-placeholder"
            :class="{ 'ip-split-placeholder--replace': !!screens.intro_cover }"
            aria-hidden="true"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <path d="M21 15l-5-5L5 21" />
            </svg>
            <span>{{ screens.intro_cover ? __('Replace') : __('Add image') }}</span>
          </div>
        </div>

        <!-- Right content panel -->
        <div class="ip-split-right">
          <div :class="['ip-content', `is-align-${introAlign}`]">
            <div
              ref="titleEl"
              class="ip-title ip-title-ce"
              contenteditable="true"
              :data-placeholder="__('Ready to find out?')"
              :aria-label="__('Intro title')"
              @input="onTitleInput"
              @blur="flushTitle"
              @keydown.enter.prevent="flushTitle"
            />
            <RichTextEditor
              v-model="draftSubtitle"
              class="ip-subtitle-rte"
              :toolbarless="true"
              :placeholder="__('A short hook explaining what the quiz is about.')"
              :aria-label="__('Intro subtitle')"
              @update:model-value="onSubtitleUpdate"
            />

            <div v-if="showMeta" class="ip-meta">
              <span v-if="totalQuestions" class="ip-meta__item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10" /><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" /><path d="M12 17h.01" />
                </svg>
                {{ sprintf(_n('%d question', '%d questions', totalQuestions), totalQuestions) }}
              </span>
              <span class="ip-meta__item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
                </svg>
                {{ sprintf(__('~%d min'), estimatedMinutes) }}
              </span>
              <span v-if="totalResults" class="ip-meta__item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                </svg>
                {{ sprintf(_n('%d result', '%d results', totalResults), totalResults) }}
              </span>
            </div>
            <button type="button" class="ip-cta">
              {{ screens.intro_button || __('Start quiz') }}
              <svg class="q-flip-rtl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </template>

    </div>

    <!-- Floating title toolbar — Teleported to body, visible in both layouts -->
    <Teleport to="body">
      <div
        v-if="titleBar.visible"
        class="ip-title-bar"
        :style="titleBar.posStyle"
        @mousedown.prevent
      >
        <!-- Alignment -->
        <button type="button" :class="['ip-title-bar__btn', { 'is-active': titleBar.align === 'left' }]" :title="__('Align left')" @click="applyTitleAlign('left')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
        </button>
        <button type="button" :class="['ip-title-bar__btn', { 'is-active': titleBar.align === 'center' }]" :title="__('Align center')" @click="applyTitleAlign('center')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
        </button>
        <button type="button" :class="['ip-title-bar__btn', { 'is-active': titleBar.align === 'right' }]" :title="__('Align right')" @click="applyTitleAlign('right')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="ip-title-bar__sep" />
        <!-- Bold / Italic / Underline -->
        <button type="button" :class="['ip-title-bar__btn', { 'is-active': titleBar.bold }]" :title="__('Bold')" @click="execTitle('bold')"><b>B</b></button>
        <button type="button" :class="['ip-title-bar__btn', { 'is-active': titleBar.italic }]" :title="__('Italic')" @click="execTitle('italic')"><i>I</i></button>
        <button type="button" :class="['ip-title-bar__btn', { 'is-active': titleBar.underline }]" :title="__('Underline')" @click="execTitle('underline')"><u>U</u></button>
        <div class="ip-title-bar__sep" />
        <!-- Color picker -->
        <label class="ip-title-bar__color" :title="__('Text color')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M9 7l6 10H3z"/><line x1="20" y1="17" x2="20" y2="22"/><line x1="17.5" y1="19.5" x2="22.5" y2="19.5"/></svg>
          <span class="ip-title-bar__color-swatch" :style="{ background: titleBar.color }" />
          <input ref="titleColorInputRef" type="color" class="ip-title-bar__color-input" :value="titleBar.color" @input="applyTitleColor($event.target.value)">
        </label>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { __, _n, sprintf } from '@shared/i18n';
import { resolveBgStyle } from '@shared/bgStyle.js';
import { hexToRgba } from '@shared/colorUtils.js';
import { fullscreenStage } from '@shared/fullscreenStage.js';
import { useTabPreview } from '@admin/composables/useTabPreview.js';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { RichTextEditor, useToast } from '@admin/ui';

const props = defineProps({
  screens: { type: Object, default: () => ({}) },
  design: { type: Object, default: () => ({}) },
  totalQuestions: { type: Number, default: 0 },
  totalResults: { type: Number, default: 0 },
  /** Quiz management title — used as fallback when no custom intro title is set */
  quizTitle: { type: String, default: '' },
});

const emit = defineEmits(['open-properties']);

const store = useQuizBuilderStore();
const toast  = useToast();

const preview = reactive(useTabPreview());

const isSplitscreen = computed(() => preview.templateSlug === 'splitscreen');

const fontScale = computed(() => Math.min(1.5, Math.max(0.7, (Number(store.quiz?.design?.font_size) || 100) / 100)));

const rootVars = computed(() => {
  const c = preview.colors;
  return {
    '--ip-primary': c.primary || 'var(--brand)',
    '--ip-text':    c.text    || 'var(--ink-1)',
    '--ip-bg':      hexToRgba(c.background || '#ffffff', preview.backgroundOpacity),
    // Full Screen stage: the same definition the live template paints from.
    '--ip-stage':   fullscreenStage('var(--ip-text)', 'var(--ip-primary)'),
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
  if (!isSplitscreen.value || !props.screens.intro_cover) return {};
  return { backgroundImage: `url("${props.screens.intro_cover}")` };
});

const cardClass = computed(() => [
  `ip-tpl--${preview.templateSlug}`,
  isSplitscreen.value ? `is-content-${preview.splitContentAlign}` : '',
]);

// ── Title contenteditable + floating toolbar ──────────────────────────────────
const DEFAULT_TITLE = __('Ready to find out?');

const titleEl           = ref(null);
const titleColorInputRef = ref(null);
const draftTitle        = ref('');

const titleBar = reactive({
  visible: false,
  bold: false,
  italic: false,
  underline: false,
  align: 'left',
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
  if (titleEl.value) {
    titleEl.value.innerHTML = props.screens.intro_title || props.quizTitle || '';
  }
}

function onTitleInput() {
  draftTitle.value = titleEl.value?.innerHTML ?? '';
  store.stageSettings({ screens: { ...(store.quiz?.settings?.screens ?? {}), intro_title: draftTitle.value } });
}

// Strip all formatting on paste — insert plain text only so the title stays clean.
function onTitlePaste(e) {
  e.preventDefault();
  const text = (e.clipboardData || window.clipboardData).getData('text/plain');
  document.execCommand('insertText', false, text);
}

function flushTitle() {
  // Commit current DOM value on blur/enter — stages locally, no API call.
  const next = (titleEl.value?.innerHTML ?? '').trim();
  store.stageSettings({ screens: { ...(store.quiz?.settings?.screens ?? {}), intro_title: next } });
}

// ── Subtitle / description — RichTextEditor (toolbarless bubble) ──────────────
const draftSubtitle = ref(props.screens.intro_subtitle ?? '');

function onSubtitleUpdate(html) {
  draftSubtitle.value = html;
  store.stageSettings({ screens: { ...(store.quiz?.settings?.screens ?? {}), intro_subtitle: html } });
}

// Seed/sync from store changes (e.g. undo, external update)
watch(
  () => props.screens.intro_title,
  (v) => {
    const text = v || props.quizTitle || '';
    if (text !== draftTitle.value) {
      draftTitle.value = text;
      if (titleEl.value) titleEl.value.innerHTML = text;
    }
  },
  { immediate: true }
);

watch(
  () => props.screens.intro_subtitle,
  (v) => { if (v !== draftSubtitle.value) draftSubtitle.value = v ?? ''; }
);

// Ensure latest contenteditable value is staged before the Save button flushes.
function flushAllPending() {
  store.stageSettings({ screens: { ...(store.quiz?.settings?.screens ?? {}),
    intro_title: (titleEl.value?.innerHTML ?? '').trim(),
    intro_subtitle: draftSubtitle.value ?? '',
  }});
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

// When switching standard ↔ splitscreen Vue remounts the contenteditable — re-seed it.
watch(isSplitscreen, () => nextTick(initTitleEl));

// ── WP media picker — opens directly from the preview canvas ─────────────────
let _wpFrame = null;

function openMediaPicker() {
  if (typeof window === 'undefined' || !window.wp?.media) {
    const url = window.prompt(__('Enter image URL'), props.screens.intro_cover || '');
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
  if (next === (props.screens.intro_cover ?? '')) return;
  store.stageSettings({ screens: { ...props.screens, intro_cover: next } });
  store.flushStagedSettings();
}

function removeImage() {
  try {
    store.stageSettings({ screens: { ...props.screens, intro_cover: '' } });
    store.flushStagedSettings();
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not remove image'), message: e.message });
  }
}

// ── Alignment — driven by IntroProperties sidebar ────────────────────────────
const introAlign = computed(() => {
  const v = props.screens.intro_align;
  return v === 'left' || v === 'right' ? v : 'center';
});

// ── Image fit — read-only in the preview; editing happens in the sidebar ──────
const introImageFit = computed(() => {
  const v = props.screens.intro_image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

// ── Image height — driven by the IntroProperties height input ─────────────────
const DEFAULT_INTRO_IMAGE_HEIGHT = 300;
const introImageHeightStyle = computed(() => {
  const h = Number(props.screens.intro_image_height);
  return Number.isFinite(h) && h > 0
    ? { height: `${h}px` }
    : { height: `${DEFAULT_INTRO_IMAGE_HEIGHT}px` };
});

const showMeta = computed(() => props.screens.intro_show_meta !== false);

const bg = computed(() => resolveBgStyle(null, props.design));

const cardOverlayStyle = computed(() => ({
  background: bg.value.overlayColor,
  opacity: String(bg.value.overlayOpacity / 100),
}));

const introBgOpacityDecimal = computed(() => {
  const v = Number(props.screens.intro_bg_opacity);
  return Number.isFinite(v) ? Math.max(0, Math.min(1, v / 100)) : 0;
});

// Rough estimate: 12 seconds per question, rounded up to whole minutes,
// floored at 1 to avoid "0 min" reading. Matches the runtime renderer's
// later contract; if the renderer disagrees this can be hoisted to a
// shared helper.
const estimatedMinutes = computed(() => {
  const n = Math.max(0, Number(props.totalQuestions) || 0);
  const seconds = n * 12;
  return Math.max(1, Math.ceil(seconds / 60));
});

</script>

<style scoped>
.ip {
  max-width: 760px;
  min-width: 0;
  width: 100%;
  margin: 0 auto;
  padding: 28px 32px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}


.ip-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.ip-crumb {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.ip-crumb .chip {
  padding: 3px 10px;
  background: var(--brand);
  color: #fff;
  border-radius: var(--r-xs);
  font-weight: 600;
}

.ip-head__actions {
  display: flex;
  gap: 10px;
}

.ip-properties-btn {
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

.ip-properties-btn:hover {
  border-color: var(--brand);
  color: var(--brand);
}

.ip-properties-btn svg {
  width: 14px;
  height: 14px;
}

.ip-card {
  display: flex;
  flex-direction: column;
  background-color: var(--ip-bg, var(--bg-surface));
  background-size: cover;
  background-position: center;
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  position: relative;
}

.ip-card-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.ip-bg {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  background-color: var(--bg-canvas);
  pointer-events: none;
  z-index: 0;
}

.ip-bg-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  pointer-events: none;
  z-index: 1;
}

.ip-card > *:not(.ip-card-overlay):not(.ip-bg):not(.ip-bg-overlay) {
  position: relative;
  z-index: 1;
}

/* ── Inline image (after description) ───────────────────────────────────────── */
/* Height is driven by the inline style from introImageHeightStyle (default 300px).
   Modifier classes must NOT set a height — the inline style owns it. */
.ip-image {
  width: 100%;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  /* Height is set via inline style — no default height here. */
  /* align-self: stretch keeps this full-width regardless of the parent's align-items */
  align-self: stretch;
}

/* Contain: letterbox — always fully visible, never cropped (CLAUDE.md rule) */
.ip-image--contain img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

/* Cover: background-size cover fills the slot — no <img> involved */
.ip-image__cover-bg {
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

/* Repeat: tile pattern */
.ip-image__tile {
  width: 100%;
  height: 100%;
  background-repeat: repeat;
  background-position: 0 0;
  background-size: auto;
}

/* Replace / remove overlay — revealed on hover */
.ip-image__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(0, 0, 0, 0.45);
  opacity: 0;
  transition: opacity 150ms;
  border-radius: inherit;
}

.ip-image:hover .ip-image__overlay { opacity: 1; }

.ip-image__overlay-btn {
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

.ip-image__overlay-btn:hover { background: rgba(255, 255, 255, 0.25); }
.ip-image__overlay-btn--remove:hover { background: rgba(220, 38, 38, 0.55); border-color: rgba(255,255,255,.4); }
.ip-image__overlay-btn svg { width: 13px; height: 13px; flex-shrink: 0; }

/* ── Image placeholder ───────────────────────────────────────────────────────── */
.ip-image-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  min-height: 80px;
  padding: 16px;
  background: var(--bg-canvas);
  border: 1.5px dashed var(--border-2);
  border-radius: var(--r-md);
  color: var(--ink-3);
  font-family: var(--f-mono);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: border-color 150ms, background 150ms, color 150ms;
  /* align-self: stretch keeps this full-width regardless of the parent's align-items */
  align-self: stretch;
}

.ip-image-placeholder:hover {
  border-color: var(--brand);
  background: color-mix(in srgb, var(--brand) 5%, var(--bg-canvas));
  color: var(--brand);
}

.ip-image-placeholder svg { width: 20px; height: 20px; }


.ip-content {
  padding: 36px 40px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  align-items: flex-start;
}

/* ── Alignment variants ──────────────────────────────────────────────────────── */
.ip-content.is-align-left   { text-align: left;   align-items: flex-start; } /* rtl-ok: author-chosen physical alignment setting ("left"/"right" is the stored value) */
.ip-content.is-align-center { text-align: center; align-items: center; }
.ip-content.is-align-right  { text-align: right;  align-items: flex-end; } /* rtl-ok: author-chosen physical alignment setting ("left"/"right" is the stored value) */

/* ── Title (contenteditable) ─────────────────────────────────────────────────── */
.ip-title {
  font-family: var(--f-display);
  font-size: calc(26px * var(--quizably-font-scale, 1));
  line-height: 1.15;
  font-weight: 500;
  letter-spacing: -0.015em;
  color: var(--ip-text, var(--ink-1));
  margin: 0;
  width: 100%;
  word-break: break-word;
  background: transparent;
  outline: none;
  padding: 0 0 2px;
  min-height: 1.3em;
}

.ip-title-ce:empty::before {
  content: attr(data-placeholder);
  color: color-mix(in srgb, var(--ip-text, var(--ink-4)) 40%, transparent);
  font-style: italic;
  pointer-events: none;
}

.ip-title-ce:focus {
  border-bottom: 1px dashed color-mix(in srgb, var(--ip-text, var(--ink-3)) 30%, transparent);
}

/* ── Subtitle / description — RichTextEditor (toolbarless bubble) ───────────── */
.ip-subtitle-rte {
  font-family: var(--f-sans);
  font-size: calc(15px * var(--quizably-font-scale, 1));
  line-height: 1.55;
  color: color-mix(in srgb, var(--ip-text, var(--ink-2)) 70%, transparent);
  width: 100%;
  opacity: 0.85;
  /* align-self: stretch keeps this full-width regardless of the parent's align-items */
  align-self: stretch;
}

.ip-subtitle-rte:focus-within { opacity: 1; }

.ip-subtitle-rte :deep(.quizably-rte__doc) {
  font-family: var(--f-sans);
  font-size: calc(15px * var(--quizably-font-scale, 1));
  line-height: 1.55;
  color: color-mix(in srgb, var(--ip-text, var(--ink-2)) 70%, transparent);
}

.ip-subtitle-rte :deep(.quizably-rte__doc p) { margin: 0 0 6px; }
.ip-subtitle-rte :deep(.quizably-rte__doc p:last-child) { margin-bottom: 0; }
.ip-subtitle-rte :deep(.quizably-rte__doc strong) { font-weight: 700; }
.ip-subtitle-rte :deep(.quizably-rte__doc em) { font-style: italic; }
.ip-subtitle-rte :deep(.quizably-rte__doc u) { text-decoration: underline; }

.ip-meta {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 18px;
  margin-top: 4px;
  padding: 10px 16px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-pill);
}

.ip-meta__item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--f-mono);
  font-size: 11.5px;
  letter-spacing: 0.04em;
  color: color-mix(in srgb, var(--ip-text, var(--ink-3)) 55%, transparent);
}

.ip-meta__item svg {
  width: 13px;
  height: 13px;
}

.ip-cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
  padding: 12px 22px;
  background: var(--ip-primary, var(--brand));
  color: #fff;
  border: 0;
  border-radius: var(--r-md);
  font-family: inherit;
  font-size: calc(14.5px * var(--quizably-font-scale, 1));
  font-weight: 600;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: background 150ms, transform 150ms;
  /* align-self: stretch keeps this full-width regardless of the parent's align-items */
  align-self: stretch;
  width: 100%;
  justify-content: center;
}

.ip-cta:hover {
  background: color-mix(in srgb, var(--ip-primary, var(--brand)) 85%, #000);
  transform: translateY(-1px);
}

.ip-cta svg {
  width: 16px;
  height: 16px;
}

.ip-tpl-badge {
  padding: 2px 7px;
  background: var(--brand-tint);
  color: var(--brand);
  border-radius: var(--r-xs);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: lowercase;
}

/* ── Card template variants ─────────────────────────────────────────────────── */
.ip-card {
  --ip-primary: var(--brand);
  --ip-text:    var(--ink-1);
  --ip-bg:      var(--bg-surface);
}

.ip-card.ip-tpl--minimal     { box-shadow: none; border: none; }
/* Same stage as the live template (src/shared/fullscreenStage.js). background-image, not the
   shorthand, so an author's bg image (an inline background-image) keeps the card's cover sizing. */
.ip-card.ip-tpl--fullscreen  { background-image: var(--ip-stage); }
.ip-card.ip-tpl--cardstack   { box-shadow: 0 8px 0 -4px color-mix(in srgb, var(--border-1) 60%, transparent), 0 16px 0 -8px color-mix(in srgb, var(--border-1) 30%, transparent), var(--shadow-md); }
.ip-card.ip-tpl--conversational { border-top: 4px solid var(--ip-primary); border-radius: var(--r-xl); }
/* Gamified: gradient header strip drives identity; keep border+glow on the card */
.ip-card.ip-tpl--gamified    { border: 2px solid var(--ip-primary); box-shadow: 0 0 0 4px color-mix(in srgb, var(--ip-primary) 12%, transparent); }
/* Splitscreen: two-column layout */
.ip-card.ip-tpl--splitscreen {
  flex-direction: row;
  border-inline-start: none;
  min-height: 380px;
  max-height: 70vh;
}

.ip-split-panel {
  display: none;
}

.ip-tpl--splitscreen .ip-split-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42%;
  flex-shrink: 0;
  align-self: stretch;
  background-color: color-mix(in srgb, var(--ip-primary) 85%, #000);
  background-size: cover;
  background-position: center;
  border-start-start-radius: calc(var(--r-lg) - 1px);
  border-start-end-radius: 0;
  border-end-end-radius: 0;
  border-end-start-radius: calc(var(--r-lg) - 1px);
  position: relative;
  overflow: hidden;
}

.ip-split-placeholder {
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
.ip-tpl--splitscreen .ip-split-panel:not([style*="background-image"]) .ip-split-placeholder,
.ip-tpl--splitscreen .ip-split-panel:hover .ip-split-placeholder {
  opacity: 1;
  background: rgba(0,0,0,.32);
}

.ip-split-placeholder svg {
  width: 24px;
  height: 24px;
}

.ip-split-right {
  display: none;
}

.ip-tpl--splitscreen .ip-split-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  justify-content: flex-start;
  min-width: 0;
  overflow-x: hidden;
  overflow-y: auto;
  align-self: stretch;
}

/* Prevent flex from shrinking children so overflow-y scroll actually fires */
.ip-tpl--splitscreen .ip-split-right > * {
  flex-shrink: 0;
}

.ip-card.is-content-top    .ip-split-right { justify-content: flex-start; }
.ip-card.is-content-center .ip-split-right { justify-content: center; }
.ip-card.is-content-bottom .ip-split-right { justify-content: flex-end; }

/* justify-content other than flex-start breaks overflow scroll — force flex-start for splitscreen */
.ip-card.ip-tpl--splitscreen.is-content-center .ip-split-right,
.ip-card.ip-tpl--splitscreen.is-content-bottom .ip-split-right { justify-content: flex-start; }

.ip-tpl--splitscreen .ip-content {
  text-align: start;
  align-items: flex-start;
  padding: 36px 40px;
}
/* Magazine: hero is outside the card; card pulls up flush to the strip with negative margin */
.ip-card.ip-tpl--magazine    { border-top: none; border-radius: 0; margin-top: -14px; }

.ip-card.ip-tpl--fullscreen .ip-title          { color: #fff; }
.ip-card.ip-tpl--fullscreen .ip-title-ce:empty::before { color: rgba(255,255,255,.3); }
.ip-card.ip-tpl--fullscreen .ip-subtitle-rte,
.ip-card.ip-tpl--fullscreen .ip-subtitle-rte :deep(.quizably-rte__doc) { color: rgba(255,255,255,.75); }
.ip-card.ip-tpl--fullscreen .ip-meta     { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.15); }
.ip-card.ip-tpl--fullscreen .ip-cta {
  background: rgba(255,255,255,.15);
  color: #fff;
  border: 1px solid rgba(255,255,255,.3);
}
.ip-card.ip-tpl--conversational .ip-cta { border-radius: var(--r-pill); }

@media (max-width: 1023px) {
  .ip-properties-btn {
    display: inline-flex;
  }
}

/* ── Gamified header strip ──────────────────────────────────────────────────── */
/* ip-card has no padding — no negative margins needed, strip is flush */
.ip-game-head {
  padding: 14px 20px;
  background: linear-gradient(135deg, var(--ip-primary, #4F46E5), color-mix(in srgb, var(--ip-primary, #4F46E5) 60%, #F59E0B));
  color: #fff;
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  z-index: 2;
}

.ip-game-lvl {
  display: flex;
  align-items: baseline;
  gap: 5px;
  flex-shrink: 0;
}

.ip-game-lvl-num {
  font-family: var(--f-display);
  font-size: 22px;
  font-weight: 600;
  line-height: 1;
}

.ip-game-lvl-label {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, .8);
}

.ip-game-pips {
  flex: 1;
  display: flex;
  gap: 4px;
  align-items: center;
}

.ip-game-pip {
  flex: 1;
  height: 5px;
  border-radius: var(--r-pill);
  background: rgba(255, 255, 255, .25);
}

.ip-game-pip.is-done    { background: #fff; }
.ip-game-pip.is-current { background: rgba(255, 255, 255, .85); box-shadow: 0 0 0 2px rgba(255, 255, 255, .4); }

.ip-game-score {
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

.ip-game-score svg { width: 12px; height: 12px; fill: #FCD34D; color: #FCD34D; }

/* ── Magazine hero strip ────────────────────────────────────────────────────── */
.ip-mag-hero {
  margin: 0 -32px;
  height: 72px;
  background: linear-gradient(135deg,
    color-mix(in srgb, var(--ip-primary, #4F46E5) 30%, var(--ip-text, #0a0a0b)),
    var(--ip-text, #0a0a0b)
  );
  border-bottom: 4px solid color-mix(in srgb, var(--ip-primary, #4F46E5) 60%, #F59E0B);
  position: relative;
  z-index: 2;
  display: flex;
  align-items: flex-end;
  padding: 0 22px 10px;
  overflow: hidden;
}

.ip-mag-hero-issue {
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
/* Global — ip-title-bar is Teleported to <body>, so scoped won't reach it */
.ip-title-bar {
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
.ip-title-bar::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%; /* rtl-ok: centring pair with translateX(-50%), symmetric in both directions */
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #1c1c1e;
}
.ip-title-bar__btn {
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
.ip-title-bar__btn:hover { background: rgba(255,255,255,.1); color: #fff; }
.ip-title-bar__btn.is-active { background: rgba(255,255,255,.18); color: #fff; }
.ip-title-bar__sep {
  width: 1px;
  height: 16px;
  background: rgba(255,255,255,.15);
  margin: 0 4px;
  flex-shrink: 0;
}
.ip-title-bar__color {
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
.ip-title-bar__color:hover { background: rgba(255,255,255,.1); color: #fff; }
.ip-title-bar__color-swatch {
  display: block;
  width: 16px;
  height: 3px;
  border-radius: 2px;
  margin-top: -1px;
}
.ip-title-bar__color-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}
</style>
