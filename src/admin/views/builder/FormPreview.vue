<template>
  <div class="op-preview" :style="rootVars">
    <div class="op-preview__head">
      <div class="op-preview__crumb">
        <span class="chip">{{ __('Form') }}</span>
        {{ sprintf(__('Form · %s'), placementLabel) }}
        <span class="op-tpl-badge">{{ preview.templateSlug }}</span>
      </div>
      <button
        type="button"
        class="op-preview__properties-btn"
        :aria-label="__('Open form properties')"
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

    <div
      v-if="placement === 'none'"
      class="op-preview__disabled"
    >
      <div class="op-preview__disabled-icon" aria-hidden="true">
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.6"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M3 3l18 18" />
          <path d="M9 3h12v12" />
          <path d="M3 21V9" />
        </svg>
      </div>
      <h3>{{ __('Form is off') }}</h3>
      <p v-html="offHint" />
    </div>

    <!-- Magazine: editorial hero strip — outside form, spans the full 760px preview width -->
    <div
      v-if="placement !== 'none' && preview.templateSlug === 'magazine' && !isSplitscreen"
      class="op-mag-hero"
      aria-hidden="true"
    >
      <div class="op-mag-hero-meta">
        <span class="op-mag-hero-issue">{{ __('Form · Results ahead') }}</span>
      </div>
    </div>

    <form
      v-if="placement !== 'none'"
      class="op-form"
      :class="formClass"
      :style="formStyle"
      @submit.prevent
    >
      <!-- ── Standard (non-splitscreen) layout ──────────────────────────── -->
      <template v-if="!isSplitscreen">

        <!-- Gamified: gradient header with level + pips + score badge -->
        <div
          v-if="preview.templateSlug === 'gamified'"
          class="op-game-head"
          aria-hidden="true"
        >
          <div class="op-game-lvl">
            <span class="op-game-lvl-num">1</span>
            <span class="op-game-lvl-label">{{ __('Level') }}</span>
          </div>
          <div class="op-game-pips">
            <span
              v-for="i in 5"
              :key="i"
              :class="['op-game-pip', { 'is-done': i === 1, 'is-current': i === 1 }]"
            />
          </div>
          <div class="op-game-score">
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
          class="op-form-overlay"
          :style="formOverlayStyle"
          aria-hidden="true"
        />

        <div class="op-form__head">
          <div
            ref="titleEl"
            class="op-form__title op-form__title-ce"
            contenteditable="true"
            :data-placeholder="formCopy.title"
            :aria-label="__('Form title')"
            @input="onTitleInput"
            @blur="flushTitle"
            @keydown.enter.prevent="flushTitle"
            @paste="onTitlePaste"
          />
          <RichTextEditor
            v-model="draftDesc"
            class="op-form__desc-rte"
            :toolbarless="true"
            :placeholder="__('Add a subheading…')"
            :aria-label="__('Form description')"
            @update:model-value="onDescUpdate"
          />
        </div>

        <!-- Image slot — inline after heading, three-branch fit rendering -->
        <template v-if="props.optin.image_url">
          <!-- Contain: <figure><img> -->
          <figure
            v-if="imageFit === 'contain'"
            class="op-image"
            :style="imageHeightStyle"
            :aria-label="__('Form image')"
          >
            <img :src="props.optin.image_url" :alt="__('Form image')" class="op-image__img">
            <div class="op-image__overlay">
              <button type="button" class="op-image__action" @click.prevent="openMediaPicker">{{ __('Replace') }}</button>
              <button type="button" class="op-image__action op-image__action--remove" @click.prevent="removeImage">{{ __('Remove') }}</button>
            </div>
          </figure>
          <!-- Cover: <div> with background-size:cover -->
          <div
            v-else-if="imageFit === 'cover'"
            class="op-image op-image--bg"
            :style="{ ...imageHeightStyle, backgroundImage: `url(${props.optin.image_url})` }"
            :aria-label="__('Form image')"
          >
            <div class="op-image__overlay">
              <button type="button" class="op-image__action" @click.prevent="openMediaPicker">{{ __('Replace') }}</button>
              <button type="button" class="op-image__action op-image__action--remove" @click.prevent="removeImage">{{ __('Remove') }}</button>
            </div>
          </div>
          <!-- Repeat: <div> with background-repeat:repeat -->
          <div
            v-else
            class="op-image op-image--repeat"
            :style="{ ...imageHeightStyle, backgroundImage: `url(${props.optin.image_url})` }"
            :aria-label="__('Form image')"
          >
            <div class="op-image__overlay">
              <button type="button" class="op-image__action" @click.prevent="openMediaPicker">{{ __('Replace') }}</button>
              <button type="button" class="op-image__action op-image__action--remove" @click.prevent="removeImage">{{ __('Remove') }}</button>
            </div>
          </div>
        </template>
        <button
          v-else
          type="button"
          class="op-image-placeholder"
          :aria-label="__('Add image')"
          @click.prevent="openMediaPicker"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2" />
            <circle cx="8.5" cy="8.5" r="1.5" />
            <path d="M21 15l-5-5L5 21" />
          </svg>
          <span>{{ __('Add image') }}</span>
        </button>

        <div class="op-form__fields">
          <div v-for="field in enabledFields" :key="field.id" class="op-form__field">
            <label class="op-form__label">
              {{ field.label }}
              <span v-if="field.required" class="op-form__req" :aria-label="__('Required')">*</span>
            </label>
            <input :type="field.inputType" class="op-form__input" :placeholder="field.placeholder" :readonly="true" tabindex="-1">
          </div>
        </div>
        <div :class="['op-form__gdpr', { 'is-placeholder': !gdprText }]">
          <span class="op-form__gdpr-checkbox" aria-hidden="true">
            <svg viewBox="0 0 10 8" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="1,4 3.5,6.5 9,1" />
            </svg>
          </span>
          <span>{{ gdprText || gdprPlaceholder }}</span>
        </div>
        <button type="button" class="op-form__submit">{{ submitLabel }}</button>
        <button
          v-if="skippable"
          type="button"
          class="op-form__skip"
          tabindex="-1"
        >
          {{ __('Skip') }}
        </button>
      </template>

      <!-- ── Splitscreen layout ──────────────────────────────────────────── -->
      <template v-else>
        <!-- Left decorative panel — image_url fills as CSS background -->
        <div class="op-split-panel" :style="splitPanelStyle">
          <button
            v-if="!props.optin.image_url"
            type="button"
            class="op-split-placeholder"
            :aria-label="__('Add image')"
            @click.prevent="openMediaPicker"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <path d="M21 15l-5-5L5 21" />
            </svg>
            <span>{{ __('Add image') }}</span>
          </button>
          <div
            v-else
            class="op-split-image-overlay"
          >
            <button type="button" class="op-image__action" @click.prevent="openMediaPicker">{{ __('Replace') }}</button>
            <button type="button" class="op-image__action op-image__action--remove" @click.prevent="removeImage">{{ __('Remove') }}</button>
          </div>
        </div>

        <!-- Right form panel -->
        <div class="op-split-right">
          <div class="op-form__head">
            <div
              ref="titleEl"
              class="op-form__title op-form__title-ce"
              contenteditable="true"
              :data-placeholder="formCopy.title"
              :aria-label="__('Form title')"
              @input="onTitleInput"
              @blur="flushTitle"
              @keydown.enter.prevent="flushTitle"
            />
            <RichTextEditor
              v-model="draftDesc"
              class="op-form__desc-rte"
              :toolbarless="true"
              :placeholder="__('Add a subheading…')"
              :aria-label="__('Form description')"
              @update:model-value="onDescUpdate"
            />
          </div>
          <div class="op-form__fields">
            <div v-for="field in enabledFields" :key="field.id" class="op-form__field">
              <label class="op-form__label">
                {{ field.label }}
                <span v-if="field.required" class="op-form__req" :aria-label="__('Required')">*</span>
              </label>
              <input :type="field.inputType" class="op-form__input" :placeholder="field.placeholder" :readonly="true" tabindex="-1">
            </div>
          </div>
          <div :class="['op-form__gdpr', { 'is-placeholder': !gdprText }]">
            <span class="op-form__gdpr-checkbox" aria-hidden="true">
              <svg viewBox="0 0 10 8" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1,4 3.5,6.5 9,1" />
              </svg>
            </span>
            <span>{{ gdprText || gdprPlaceholder }}</span>
          </div>
          <button type="button" class="op-form__submit">{{ submitLabel }}</button>
          <button
            v-if="skippable"
            type="button"
            class="op-form__skip"
            tabindex="-1"
          >
            {{ __('Skip') }}
          </button>
        </div>
      </template>
    </form>

    <!-- Floating title toolbar — Teleported to body, works in both layouts -->
    <Teleport to="body">
      <div
        v-if="titleBar.visible"
        class="op-title-bar"
        :style="titleBar.posStyle"
        @mousedown.prevent
      >
        <!-- Alignment -->
        <button type="button" :class="['op-title-bar__btn', { 'is-active': titleBar.align === 'left' }]" :title="__('Align left')" @click="applyTitleAlign('left')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
        </button>
        <button type="button" :class="['op-title-bar__btn', { 'is-active': titleBar.align === 'center' }]" :title="__('Align center')" @click="applyTitleAlign('center')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
        </button>
        <button type="button" :class="['op-title-bar__btn', { 'is-active': titleBar.align === 'right' }]" :title="__('Align right')" @click="applyTitleAlign('right')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="op-title-bar__sep" />
        <!-- Bold / Italic / Underline -->
        <button type="button" :class="['op-title-bar__btn', { 'is-active': titleBar.bold }]" :title="__('Bold')" @click="execTitle('bold')"><b>B</b></button>
        <button type="button" :class="['op-title-bar__btn', { 'is-active': titleBar.italic }]" :title="__('Italic')" @click="execTitle('italic')"><i>I</i></button>
        <button type="button" :class="['op-title-bar__btn', { 'is-active': titleBar.underline }]" :title="__('Underline')" @click="execTitle('underline')"><u>U</u></button>
        <div class="op-title-bar__sep" />
        <!-- Color picker -->
        <label class="op-title-bar__color" :title="__('Text color')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M9 7l6 10H3z"/><line x1="20" y1="17" x2="20" y2="22"/><line x1="17.5" y1="19.5" x2="22.5" y2="19.5"/></svg>
          <span class="op-title-bar__color-swatch" :style="{ background: titleBar.color }" />
          <input ref="titleColorInputRef" type="color" class="op-title-bar__color-input" :value="titleBar.color" @input="applyTitleColor($event.target.value)">
        </label>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { __, sprintf } from '@shared/i18n';
import { resolveBgStyle } from '@shared/bgStyle.js';
import { formCopyFor, resolveFormPlacement, resolveFormSkippable } from '@shared/formPlacement.js';
import { hexToRgba } from '@shared/colorUtils.js';
import { fullscreenStage } from '@shared/fullscreenStage.js';
import { useTabPreview } from '@admin/composables/useTabPreview.js';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { RichTextEditor, useToast } from '@admin/ui';

const props = defineProps({
  optin: { type: Object, default: () => ({}) },
  design: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['open-properties']);

const store = useQuizBuilderStore();
const toast = useToast();

const preview = reactive(useTabPreview());

const isSplitscreen = computed(() => preview.templateSlug === 'splitscreen');

const fontScale = computed(() => Math.min(1.5, Math.max(0.7, (Number(store.quiz?.design?.font_size) || 100) / 100)));

const rootVars = computed(() => {
  const c = preview.colors;
  return {
    '--op-primary': c.primary || 'var(--brand)',
    '--op-text':    c.text    || 'var(--ink-1)',
    '--op-bg':      hexToRgba(c.background || '#ffffff', preview.backgroundOpacity),
    // Full Screen stage: the same definition the live template paints from.
    '--op-stage':   fullscreenStage('var(--op-text)', 'var(--op-primary)'),
    '--quizably-font-scale': fontScale.value,
  };
});

const formStyle = computed(() => {
  if (isSplitscreen.value) return {};
  return bg.value.backgroundImage
    ? { backgroundImage: `url("${bg.value.backgroundImage}")` }
    : {};
});

const splitPanelStyle = computed(() => {
  if (!isSplitscreen.value || !props.optin.image_url) return {};
  return { backgroundImage: `url("${props.optin.image_url}")` };
});

// ── Alignment — driven by FormProperties (formerly OptinProperties) sidebar ──
// `screens.optin_align` is the actual stored key name (kept for compatibility
// with every already-saved quiz).
const formAlign = computed(() => {
  const v = store.quiz?.settings?.screens?.optin_align;
  return v === 'left' || v === 'right' ? v : 'center';
});

const formClass = computed(() => [
  `op-tpl--${preview.templateSlug}`,
  isSplitscreen.value ? `is-content-${preview.splitContentAlign}` : '',
  `is-align-${formAlign.value}`,
]);

// ── Title contenteditable + floating toolbar ──────────────────────────────────
// Position / skippable / default wording come from the same shared helpers the
// sidebar and the live quiz use - see src/shared/formPlacement.js. (This block
// sits above the title watcher on purpose: that watcher is `immediate`.)
const placement = computed(() => resolveFormPlacement(props.optin));
const skippable = computed(() => resolveFormSkippable(props.optin));
const formCopy = computed(() => formCopyFor(placement.value));

const titleEl            = ref(null);
const titleColorInputRef = ref(null);
const draftTitle         = ref('');

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
    titleEl.value.innerHTML = props.optin.title ?? props.optin.copy?.title ?? '';
  }
}

function onTitleInput() {
  draftTitle.value = titleEl.value?.innerHTML ?? '';
  store.stageSettings({ optin: { ...props.optin, title: draftTitle.value } });
}

// Strip all formatting on paste — insert plain text only so the title stays clean.
function onTitlePaste(e) {
  e.preventDefault();
  const text = (e.clipboardData || window.clipboardData).getData('text/plain');
  document.execCommand('insertText', false, text);
}

function flushTitle() {
  const next = (titleEl.value?.innerHTML ?? '').trim();
  store.stageSettings({ optin: { ...props.optin, title: next } });
  // No auto-save: API write happens only when the user clicks Save.
}

// ── Description — RichTextEditor (toolbarless bubble) ────────────────────────
const draftDesc = ref(props.optin.description ?? props.optin.copy?.description ?? '');

function onDescUpdate(html) {
  draftDesc.value = html;
  store.stageSettings({ optin: { ...props.optin, description: html } });
}

// Sync from external store changes (e.g. undo)
watch(
  // Also re-runs when the position changes, so an untouched title follows it
  // (the default for a form at the start differs from one at the end).
  () => [props.optin.title ?? props.optin.copy?.title, formCopy.value.title],
  ([v, fallback]) => {
    const text = v || fallback;
    if (text !== draftTitle.value) {
      draftTitle.value = text;
      if (titleEl.value) titleEl.value.innerHTML = text;
    }
  },
  { immediate: true }
);

watch(
  () => props.optin.description ?? props.optin.copy?.description,
  (v) => { if (v !== draftDesc.value) draftDesc.value = v ?? ''; }
);

// Safety net: called by the quizably:flush-pending-saves event (fired by the Save
// button before store.flushAllStaged()). Ensures the latest contenteditable
// DOM value is staged even if the blur handler hasn't fired yet.
function flushAllPending() {
  store.stageSettings({ optin: { ...props.optin, title: (titleEl.value?.innerHTML ?? '').trim() } });
  store.stageSettings({ optin: { ...props.optin, description: draftDesc.value ?? '' } });
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

// Re-seed when switching layouts — Vue remounts the contenteditable.
watch(isSplitscreen, () => nextTick(initTitleEl));

const FIELD_DEFAULTS = {
  name: { label: __('Name'), placeholder: __('Jane Doe'), inputType: 'text' },
  email: { label: __('Email'), placeholder: __('you@example.com'), inputType: 'email' },
  phone: { label: __('Phone'), placeholder: '(555) 123-4567', inputType: 'tel' },
  custom: { label: __('Field'), placeholder: '', inputType: 'text' },
};

const FIELD_ORDER = ['name', 'email', 'phone', 'custom'];

// Per-screen form background wins over the quiz-level design background.
// Matches the same fallback chain used by Quiz.vue on the live frontend.
const bg = computed(() => resolveBgStyle({
  enabled: props.optin.bg_enabled === true,
  background_image: props.optin.bg_image ?? '',
  overlay_color: props.optin.bg_overlay_color ?? '#000000',
  overlay_opacity: Number(props.optin.bg_opacity) || 0,
}, props.design));

const formOverlayStyle = computed(() => ({
  background: bg.value.overlayColor,
  opacity: String(bg.value.overlayOpacity / 100),
}));

// Breadcrumb: where the form sits, and whether visitors can skip it.
const placementLabel = computed(() => {
  const where = { start: __('Before the quiz'), end: __('End of the quiz'), mid: __('Mid-quiz') }[placement.value];
  if (!where) return __('Off');
  // translators: %s is where the form sits, e.g. "Before the quiz".
  return skippable.value ? sprintf(__('%s · optional'), where) : sprintf(__('%s · required'), where);
});

const fieldsList = computed(() => {
  const stored = Array.isArray(props.optin.fields) ? props.optin.fields : ['name', 'email'];
  // email is always shown regardless of stored array
  const enabled = Array.from(new Set([...stored, 'email']));
  const config = props.optin.field_config || {};
  const sorted = [...enabled].sort((a, b) => {
    const ai = FIELD_ORDER.indexOf(a);
    const bi = FIELD_ORDER.indexOf(b);
    return (ai === -1 ? FIELD_ORDER.length : ai) - (bi === -1 ? FIELD_ORDER.length : bi);
  });
  return sorted.map((id) => {
    const defaults = FIELD_DEFAULTS[id] || FIELD_DEFAULTS.custom;
    const cfg = config[id] || {};
    return {
      id,
      label: cfg.label || defaults.label,
      placeholder: cfg.placeholder || defaults.placeholder,
      inputType: defaults.inputType,
      required: cfg.required !== false,
    };
  });
});

const enabledFields = computed(() => fieldsList.value);
const copy = computed(() => props.optin.copy || {});
const gdprText = computed(() => props.optin.gdpr_text || '');
const gdprPlaceholder = __('I agree to receive follow-up emails about my results.');

// Static developer-controlled markup around the translated option names.
const offHint = computed(() => sprintf(
  // translators: %1$s, %2$s and %3$s are option names wrapped in <strong> tags: "Show the form", "Before quiz", "End of quiz".
  __('Visitors take the quiz without entering any details. Under %1$s, choose %2$s or %3$s to capture leads.'),
  '<strong>' + __('Show the form') + '</strong>',
  '<strong>' + __('Before quiz') + '</strong>',
  '<strong>' + __('End of quiz') + '</strong>'
));
const submitLabel = computed(() => props.optin.submit_label || formCopy.value.submit);

// ── Image slot ───────────────────────────────────────────────────────────────
const imageFit = computed(() => {
  const v = props.optin.image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

const DEFAULT_IMAGE_HEIGHT = 240;
const imageHeightStyle = computed(() => {
  const h = Number(props.optin.image_height);
  return Number.isFinite(h) && h > 0
    ? { height: `${h}px` }
    : { height: `${DEFAULT_IMAGE_HEIGHT}px` };
});

function openMediaPicker() {
  if (window.wp?.media) {
    const frame = window.wp.media({
      title: __('Select image'),
      multiple: false,
      library: { type: 'image' },
    });
    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      saveMediaUrl(attachment.url);
    });
    frame.open();
  } else {
    const url = window.prompt(__('Image URL'));
    if (url) saveMediaUrl(url);
  }
}

function saveMediaUrl(url) {
  store.stageSettings({ optin: { ...props.optin, image_url: (url ?? '').toString() } });
  store.flushStagedSettings();
}

function removeImage() {
  store.stageSettings({ optin: { ...props.optin, image_url: '' } });
  store.flushStagedSettings();
}
</script>

<style scoped>
.op-preview {
  max-width: 760px;
  min-width: 0;
  width: 100%;
  margin: 0 auto;
  padding: 28px 32px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}


.op-preview__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.op-preview__crumb {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.op-preview__crumb .chip {
  padding: 3px 10px;
  background: var(--brand);
  color: #fff;
  border-radius: var(--r-xs);
  font-weight: 600;
}

.op-preview__properties-btn {
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

.op-preview__properties-btn:hover {
  border-color: var(--brand);
  color: var(--brand);
}

.op-preview__properties-btn svg {
  width: 14px;
  height: 14px;
}

/* Disabled state — placement is "none" */
.op-preview__disabled {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 8px;
  padding: 40px 32px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  color: var(--ink-3);
}

.op-preview__disabled-icon {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--bg-subtle);
  color: var(--ink-4);
  margin-bottom: 4px;
}

.op-preview__disabled-icon svg {
  width: 22px;
  height: 22px;
}

.op-preview__disabled h3 {
  font-family: var(--f-display);
  font-size: 18px;
  font-weight: 500;
  margin: 0;
  color: var(--ink-1);
}

.op-preview__disabled p {
  margin: 0;
  font-size: 13.5px;
  line-height: 1.55;
  max-width: 44ch;
}

.op-preview__disabled strong {
  font-weight: 600;
  color: var(--ink-2);
}

/* Form preview */
.op-form {
  background-color: var(--op-bg, var(--bg-surface));
  background-size: cover;
  background-position: center;
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: var(--shadow-sm);
  position: relative;
  overflow: hidden;
}

/* ── Alignment variants ──────────────────────────────────────────────────────── */
.op-form.is-align-left   { text-align: left;   align-items: flex-start; } /* rtl-ok: author-chosen physical alignment setting ("left"/"right" is the stored value) */
.op-form.is-align-center { text-align: center; align-items: center; }
.op-form.is-align-right  { text-align: right;  align-items: flex-end; } /* rtl-ok: author-chosen physical alignment setting ("left"/"right" is the stored value) */

.op-form-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.op-form > *:not(.op-form-overlay) {
  position: relative;
  z-index: 1;
}

/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.op-form__head {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 4px;
  align-self: stretch;
  width: 100%;
}

/* ── Title (contenteditable) ─────────────────────────────────────────────────── */
.op-form__title {
  font-family: var(--f-display);
  font-size: calc(26px * var(--quizably-font-scale, 1));
  line-height: 1.2;
  font-weight: 500;
  letter-spacing: -0.01em;
  color: var(--op-text, var(--ink-1));
  background: transparent;
  border-bottom: 1px dashed transparent;
  width: 100%;
  padding: 0 0 2px;
  outline: none;
  word-break: break-word;
}

.op-form__title-ce:empty::before {
  content: attr(data-placeholder);
  color: color-mix(in srgb, var(--op-text, var(--ink-4)) 40%, transparent);
  pointer-events: none;
  font-style: italic;
}

.op-form__title-ce:focus {
  border-bottom-color: var(--border-2);
}

/* ── Description — RichTextEditor (toolbarless bubble) ─────────────────────── */
.op-form__desc-rte {
  font-family: inherit;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  line-height: 1.55;
  color: color-mix(in srgb, var(--op-text, var(--ink-2)) 70%, transparent);
  background: transparent;
  width: 100%;
  opacity: 0.85;
}

.op-form__desc-rte:focus-within { opacity: 1; }

.op-form__desc-rte :deep(.quizably-rte__doc) {
  font-family: inherit;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  line-height: 1.55;
  color: color-mix(in srgb, var(--op-text, var(--ink-2)) 70%, transparent);
}

.op-form__desc-rte :deep(.quizably-rte__doc p) { margin: 0 0 6px; }
.op-form__desc-rte :deep(.quizably-rte__doc p:last-child) { margin-bottom: 0; }
.op-form__desc-rte :deep(.quizably-rte__doc strong) { font-weight: 700; }
.op-form__desc-rte :deep(.quizably-rte__doc em) { font-style: italic; }
.op-form__desc-rte :deep(.quizably-rte__doc u) { text-decoration: underline; }

/* align-self: stretch keeps this full-width regardless of the parent's align-items.
   text-align: start ensures labels and inputs are always start-aligned even when the
   parent .op-form uses center or right alignment for the title/description. */
.op-form__fields {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-self: stretch;
  width: 100%;
  text-align: start;
}

.op-form__field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.op-form__label {
  font-size: 12.5px;
  font-weight: 500;
  color: color-mix(in srgb, var(--op-text, var(--ink-2)) 75%, transparent);
}

.op-form__req {
  color: var(--danger);
  margin-inline-start: 2px;
}

.op-form__input {
  font-family: inherit;
  font-size: 14px;
  padding: 10px 12px;
  border: 1px solid color-mix(in srgb, var(--op-primary, var(--border-2)) 25%, var(--border-2));
  border-radius: var(--r-sm);
  background: color-mix(in srgb, var(--op-bg, var(--bg-surface)) 90%, var(--op-primary));
  color: var(--op-text, var(--ink-1));
  cursor: default;
}

.op-form__input::placeholder {
  color: color-mix(in srgb, var(--op-text, var(--ink-4)) 35%, transparent);
}

/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.op-form__gdpr {
  display: flex;
  gap: 10px;
  margin: 4px 0 0;
  font-size: 12.5px;
  color: var(--ink-2);
  line-height: 1.5;
  align-items: center;
  align-self: stretch;
  width: 100%;
}

.op-form__gdpr-checkbox {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border-radius: 4px;
  background: var(--op-primary, var(--brand));
  border: 1.5px solid var(--op-primary, var(--brand));
  flex-shrink: 0;
}

.op-form__gdpr-checkbox svg {
  width: 10px;
  height: 8px;
}

.op-form__gdpr.is-placeholder span {
  color: var(--ink-4);
  font-style: italic;
}

/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.op-form__submit {
  margin-top: 4px;
  padding: 11px 22px;
  background: var(--op-primary, var(--brand));
  color: #fff;
  border: 0;
  border-radius: var(--r-md);
  font-family: inherit;
  font-size: calc(14px * var(--quizably-font-scale, 1));
  font-weight: 600;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: background 150ms;
  align-self: stretch;
  width: 100%;
}

.op-form__submit:hover {
  background: color-mix(in srgb, var(--op-primary, var(--brand)) 85%, #000);
}

/* Shown when the form is optional - mirrors the live form's Skip. */
.op-form__skip {
  align-self: stretch;
  width: 100%;
  padding: 8px 22px;
  background: transparent;
  color: var(--op-text, var(--ink-3));
  border: 1px solid var(--border-2);
  border-radius: var(--r-md);
  font-family: inherit;
  font-size: calc(13px * var(--quizably-font-scale, 1));
  font-weight: 500;
  cursor: default;
}

.op-tpl-badge {
  padding: 2px 7px;
  background: var(--brand-tint);
  color: var(--brand);
  border-radius: var(--r-xs);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: lowercase;
}

/* Template variant overrides */
.op-form {
  --op-primary: var(--brand);
  --op-text:    var(--ink-1);
  --op-bg:      var(--bg-surface);
}

.op-form.op-tpl--minimal     { box-shadow: none; border: none; }
/* Same stage as the live template (src/shared/fullscreenStage.js). */
.op-form.op-tpl--fullscreen  { background: var(--op-stage); border-color: transparent; color: #fff; }
.op-form.op-tpl--cardstack   { box-shadow: 0 8px 0 -4px color-mix(in srgb, var(--border-1) 60%, transparent), 0 16px 0 -8px color-mix(in srgb, var(--border-1) 30%, transparent), var(--shadow-md); }
.op-form.op-tpl--conversational { border-top: 4px solid var(--op-primary); border-radius: var(--r-xl); }
/* Gamified: gradient header strip drives identity */
.op-form.op-tpl--gamified    { padding-top: 0; border: 2px solid var(--op-primary); box-shadow: 0 0 0 4px color-mix(in srgb, var(--op-primary) 12%, transparent); }
/* Splitscreen: two-column layout */
.op-form.op-tpl--splitscreen {
  flex-direction: row;
  padding: 0;
  gap: 0;
  border-inline-start: none;
  min-height: 360px;
  max-height: 70vh;
}

.op-split-panel { display: none; }

.op-tpl--splitscreen .op-split-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42%;
  flex-shrink: 0;
  min-height: 420px;
  align-self: stretch;
  background-color: color-mix(in srgb, var(--op-primary) 85%, #000);
  background-size: cover;
  background-position: center;
  border-start-start-radius: calc(var(--r-lg) - 1px);
  border-start-end-radius: 0;
  border-end-end-radius: 0;
  border-end-start-radius: calc(var(--r-lg) - 1px);
  position: relative;
  overflow: hidden;
}

.op-split-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  height: 100%;
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.55);
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  position: relative;
  z-index: 1;
  transition: color 150ms;
}

.op-split-placeholder:hover {
  color: rgba(255, 255, 255, 0.9);
}

.op-split-placeholder svg {
  width: 28px;
  height: 28px;
}

.op-split-right { display: none; }

.op-tpl--splitscreen .op-split-right {
  flex: 1;
  align-self: stretch;
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 32px;
  min-width: 0;
  overflow-x: hidden;
  overflow-y: auto;
  justify-content: flex-start;
}

/* Prevent flex from shrinking children so overflow-y scroll actually fires */
.op-tpl--splitscreen .op-split-right > * {
  flex-shrink: 0;
}

.op-form.is-content-top    .op-split-right { justify-content: flex-start; }
.op-form.is-content-center .op-split-right { justify-content: center; }
.op-form.is-content-bottom .op-split-right { justify-content: flex-end; }

/* justify-content other than flex-start breaks overflow scroll — force flex-start for splitscreen */
.op-form.op-tpl--splitscreen.is-content-center .op-split-right,
.op-form.op-tpl--splitscreen.is-content-bottom .op-split-right { justify-content: flex-start; }
/* Magazine: hero is outside the form; form pulls up flush to the strip with negative margin */
.op-form.op-tpl--magazine    { border-top: none; border-radius: 0; margin-top: -14px; }

.op-form.op-tpl--fullscreen .op-form__title        { color: #fff; }
.op-form.op-tpl--fullscreen .op-form__title-ce:empty::before { color: rgba(255,255,255,.3); }
.op-form.op-tpl--fullscreen .op-form__desc-rte,
.op-form.op-tpl--fullscreen .op-form__desc-rte :deep(.quizably-rte__doc) { color: rgba(255,255,255,.75); }
.op-form.op-tpl--fullscreen .op-form__label { color: rgba(255,255,255,.8); }
.op-form.op-tpl--fullscreen .op-form__input { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.25); color: #fff; }
.op-form.op-tpl--fullscreen .op-form__input::placeholder { color: rgba(255,255,255,.4); }
.op-form.op-tpl--fullscreen .op-form__gdpr  { color: rgba(255,255,255,.75); }
.op-form.op-tpl--fullscreen .op-form__gdpr-checkbox { background: rgba(255,255,255,.35); border-color: rgba(255,255,255,.5); }
.op-form.op-tpl--fullscreen .op-form__submit { background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.35); color: #fff; }
.op-form.op-tpl--conversational .op-form__submit { border-radius: var(--r-pill); }

@media (max-width: 1023px) {
  .op-preview__properties-btn {
    display: inline-flex;
  }
}

/* ── Gamified header strip ──────────────────────────────────────────────────── */
/* op-form has padding: 32px — use negative margins to flush strip to card edges */
.op-game-head {
  margin: 0 -32px;
  padding: 14px 20px;
  background: linear-gradient(135deg, var(--op-primary, #4F46E5), color-mix(in srgb, var(--op-primary, #4F46E5) 60%, #F59E0B));
  color: #fff;
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  z-index: 2;
}

.op-game-lvl {
  display: flex;
  align-items: baseline;
  gap: 5px;
  flex-shrink: 0;
}

.op-game-lvl-num {
  font-family: var(--f-display);
  font-size: 22px;
  font-weight: 600;
  line-height: 1;
}

.op-game-lvl-label {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, .8);
}

.op-game-pips {
  flex: 1;
  display: flex;
  gap: 4px;
  align-items: center;
}

.op-game-pip {
  flex: 1;
  height: 5px;
  border-radius: var(--r-pill);
  background: rgba(255, 255, 255, .25);
}

.op-game-pip.is-done    { background: #fff; }
.op-game-pip.is-current { background: rgba(255, 255, 255, .85); box-shadow: 0 0 0 2px rgba(255, 255, 255, .4); }

.op-game-score {
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

.op-game-score svg { width: 12px; height: 12px; fill: #FCD34D; color: #FCD34D; }

/* ── Inline image slot (after heading) ──────────────────────────────────────── */
/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.op-image {
  width: 100%;
  border-radius: var(--r-md);
  border: 1px solid var(--border-1);
  overflow: hidden;
  position: relative;
  flex-shrink: 0;
  align-self: stretch;
}

.op-image__img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  background: var(--bg-subtle);
}

.op-image--bg {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-color: var(--bg-subtle);
}

.op-image--repeat {
  background-size: auto;
  background-repeat: repeat;
  background-position: top left; /* rtl-ok: tile origin of a repeating image, mirrors the player's own rule */
  background-color: var(--bg-subtle);
}

.op-image__overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  opacity: 0;
  transition: opacity 150ms;
}

.op-image:hover .op-image__overlay {
  opacity: 1;
}

.op-image__action {
  padding: 6px 14px;
  border-radius: var(--r-sm);
  font-family: inherit;
  font-size: 12px;
  font-weight: 600;
  border: 1.5px solid rgba(255, 255, 255, 0.7);
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  cursor: pointer;
  transition: background 150ms;
}

.op-image__action:hover {
  background: rgba(255, 255, 255, 0.3);
}

.op-image__action--remove {
  border-color: rgba(239, 68, 68, 0.7);
  background: rgba(239, 68, 68, 0.2);
}

.op-image__action--remove:hover {
  background: rgba(239, 68, 68, 0.4);
}

/* Placeholder button — dashed border, brand accent on hover */
/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.op-image-placeholder {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 28px 16px;
  background: transparent;
  border: 1.5px dashed var(--border-2);
  border-radius: var(--r-md);
  color: var(--ink-4);
  font-family: inherit;
  font-size: 12.5px;
  font-weight: 500;
  cursor: pointer;
  transition: color 150ms, border-color 150ms, background 150ms;
  align-self: stretch;
}

.op-image-placeholder:hover {
  color: var(--brand);
  border-color: var(--brand);
  background: color-mix(in srgb, var(--brand) 4%, transparent);
}

.op-image-placeholder svg {
  width: 28px;
  height: 28px;
  opacity: 0.55;
  transition: opacity 150ms;
}

.op-image-placeholder:hover svg {
  opacity: 1;
}

/* Splitscreen panel image hover overlay */
.op-split-image-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  opacity: 0;
  transition: opacity 150ms;
}

.op-tpl--splitscreen .op-split-panel:hover .op-split-image-overlay {
  opacity: 1;
}

/* ── Magazine hero strip ────────────────────────────────────────────────────── */
.op-mag-hero {
  margin: 0 -32px;
  height: 72px;
  background: linear-gradient(135deg,
    color-mix(in srgb, var(--op-primary, #4F46E5) 30%, var(--op-text, #0a0a0b)),
    var(--op-text, #0a0a0b)
  );
  border-bottom: 4px solid color-mix(in srgb, var(--op-primary, #4F46E5) 60%, #F59E0B);
  position: relative;
  z-index: 2;
  display: flex;
  align-items: flex-end;
  padding: 0 22px 10px;
  overflow: hidden;
}

.op-mag-hero-issue {
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
/* Global — op-title-bar is Teleported to <body>, so scoped won't reach it */
.op-title-bar {
  position: absolute;
  z-index: 9999;
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 5px 6px;
  background: #1c1c1e;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0,0,0,.35), 0 1px 4px rgba(0,0,0,.2);
  pointer-events: all;
  user-select: none;
}
.op-title-bar::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 50%; /* rtl-ok: centring pair with translateX(-50%), symmetric in both directions */
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-bottom: none;
  border-top-color: #1c1c1e;
}
.op-title-bar__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border: none;
  background: transparent;
  color: rgba(255,255,255,.65);
  border-radius: 5px;
  cursor: pointer;
  font-size: 12px;
  padding: 0;
  transition: background 100ms, color 100ms;
  line-height: 1;
}
.op-title-bar__btn:hover  { background: rgba(255,255,255,.1); color: #fff; }
.op-title-bar__btn.is-active { background: rgba(255,255,255,.18); color: #fff; }
.op-title-bar__sep {
  width: 1px;
  height: 16px;
  background: rgba(255,255,255,.15);
  margin: 0 2px;
  flex-shrink: 0;
}
.op-title-bar__color {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 0 5px;
  height: 26px;
  border: none;
  background: transparent;
  color: rgba(255,255,255,.65);
  border-radius: 5px;
  cursor: pointer;
  font-size: 12px;
  transition: background 100ms, color 100ms;
}
.op-title-bar__color:hover { background: rgba(255,255,255,.1); color: #fff; }
.op-title-bar__color-swatch {
  display: block;
  width: 16px;
  height: 16px;
  border-radius: 3px;
  border: 1px solid rgba(255,255,255,.25);
  margin-top: -1px;
}
.op-title-bar__color-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}
</style>
