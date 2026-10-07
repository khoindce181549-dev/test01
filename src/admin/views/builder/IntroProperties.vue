<template>
  <div class="iprops">
    <div class="iprops__scroll">
      <section class="iprops-section">
        <header class="iprops-section__head">
          <span class="iprops-section__title">{{ __('Image') }}</span>
        </header>
        <div class="iprops-section__body">
          <MediaPicker
            :model-value="screens.intro_cover || ''"
            label=""
            helper-text=""
            :button-text="__('Upload image')"
            :replace-text="__('Replace image')"
            :remove-text="__('Remove image')"
            @update:model-value="(v) => onScreensImageInput('intro_cover', v)"
          />

          <div v-if="screens.intro_cover" class="iprops-fit">
            <span class="iprops-fit__label">{{ __('Image fit') }}</span>
            <div class="iprops-fit__seg" role="radiogroup" :aria-label="__('Image fit')">
              <button
                v-for="opt in fitOptions"
                :key="opt.value"
                type="button"
                role="radio"
                :aria-checked="introImageFit === opt.value"
                :class="['iprops-fit__opt', { 'is-active': introImageFit === opt.value }]"
                @click="onFitChange(opt.value)"
              >{{ opt.label }}</button>
            </div>
            <p class="iprops-fit__hint">{{ activeFitHint }}</p>
          </div>

          <div v-if="screens.intro_cover" class="iprops-overlay">
            <div class="iprops-overlay__label">
              <span>{{ __('Height') }}</span>
              <span class="iprops-overlay__pct">{{ introImageHeight }} px</span>
            </div>
            <input
              type="range"
              min="80"
              max="800"
              step="10"
              class="iprops-overlay__range"
              :value="introImageHeight"
              :aria-label="__('Image height in pixels')"
              @input="onImageHeightChange($event.target.value)"
            >
          </div>
        </div>
      </section>

      <section class="iprops-section">
        <header class="iprops-section__head">
          <span class="iprops-section__title">{{ __('Alignment') }}</span>
        </header>
        <div class="iprops-section__body">
          <div class="iprops-align" role="radiogroup" :aria-label="__('Content alignment')">
            <button
              v-for="opt in ALIGN_OPTIONS"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="introAlign === opt.value"
              :class="['iprops-align__opt', { 'is-active': introAlign === opt.value }]"
              :aria-label="opt.label"
              :title="opt.label"
              @click="onAlignChange(opt.value)"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span class="iprops-align__icon" aria-hidden="true" v-html="opt.icon" />
            </button>
          </div>
        </div>
      </section>

      <section class="iprops-section">
        <header class="iprops-section__head">
          <span class="iprops-section__title">{{ __('Vertical alignment') }}</span>
        </header>
        <div class="iprops-section__body">
          <div class="iprops-align" role="radiogroup" :aria-label="__('Vertical alignment')">
            <button
              v-for="opt in VALIGN_OPTIONS"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="introValign === opt.value"
              :class="['iprops-align__opt', { 'is-active': introValign === opt.value }]"
              :aria-label="opt.label"
              :title="opt.label"
              @click="onValignChange(opt.value)"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span class="iprops-align__icon" aria-hidden="true" v-html="opt.icon" />
            </button>
          </div>
        </div>
      </section>

      <section class="iprops-section">
        <header class="iprops-section__head">
          <span class="iprops-section__title">{{ __('Labels') }}</span>
        </header>
        <div class="iprops-section__body">
          <Input
            :model-value="screens.intro_button || ''"
            :label="__('Start button label')"
            :placeholder="__('Start quiz')"
            @update:model-value="(v) => scheduleScreensSave('intro_button', v)"
            @blur="flushScreensSave"
          />
        </div>
      </section>

      <section class="iprops-section">
        <header class="iprops-section__head">
          <span class="iprops-section__title">{{ __('Display') }}</span>
        </header>
        <div class="iprops-section__body">
          <div class="iprops-row">
            <div class="iprops-row__label">
              <span>{{ __('Show estimated time') }}</span>
              <Tooltip :label="__('Display question count, estimated completion time, and result count on the intro screen.')">
                <span class="quizably-help-dot" aria-hidden="true">?</span>
              </Tooltip>
            </div>
            <Toggle
              :model-value="screens.intro_show_meta !== false"
              size="sm"
              data-testid="intro-show-meta-toggle"
              @update:model-value="onShowMetaChange"
            />
          </div>

        </div>
      </section>

      <section class="iprops-section">
        <header class="iprops-section__head">
          <span class="iprops-section__title">{{ __('Background') }}</span>
        </header>
        <div class="iprops-section__body">
          <div class="iprops-row">
            <div class="iprops-row__label">
              <span>{{ __('Custom background') }}</span>
            </div>
            <Toggle
              :model-value="introBgEnabled"
              size="sm"
              data-testid="intro-bg-toggle"
              @update:model-value="onIntroBgToggle"
            />
          </div>
          <template v-if="introBgEnabled">
            <MediaPicker
              :model-value="screens.intro_bg_image || ''"
              label=""
              helper-text=""
              :button-text="__('Upload background')"
              :replace-text="__('Replace background')"
              :remove-text="__('Remove background')"
              @update:model-value="(v) => onScreensImageInput('intro_bg_image', v)"
            />
            <div class="iprops-overlay">
              <label class="iprops-overlay__label">
                {{ __('Overlay opacity') }}
                <span class="iprops-overlay__pct">{{ introBgOpacity }}%</span>
              </label>
              <input
                type="range"
                min="0"
                max="100"
                step="1"
                class="iprops-overlay__range"
                :value="introBgOpacity"
                data-testid="intro-bg-opacity"
                :aria-label="__('Background overlay opacity')"
                @input="(e) => onIntroBgOpacityInput(Number(e.target.value))"
              >
            </div>
          </template>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { __ } from '@shared/i18n';
import { Input, MediaPicker, Toggle, Tooltip } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';

const store = useQuizBuilderStore();

const settings = computed(() => store.quiz?.settings ?? {});
const screens = computed(() => settings.value.screens ?? {});

// ── Alignment ─────────────────────────────────────────────────────────────────
const ALIGN_OPTIONS = [
  { value: 'left',   label: __('Left'),   icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="2" y1="8" x2="10" y2="8"/><line x1="2" y1="12" x2="12" y2="12"/></svg>` },
  { value: 'center', label: __('Center'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="4" y1="8" x2="12" y2="8"/><line x1="3" y1="12" x2="13" y2="12"/></svg>` },
  { value: 'right',  label: __('Right'),  icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="6" y1="8" x2="14" y2="8"/><line x1="4" y1="12" x2="14" y2="12"/></svg>` },
];

const introAlign = computed(() => {
  const v = screens.value.intro_align;
  return v === 'left' || v === 'right' ? v : 'center';
});

function onAlignChange(next) {
  if (next === introAlign.value) return;
  store.stageSettings({ screens: { ...screens.value, intro_align: next } });
}

// ── Vertical alignment ─────────────────────────────────────────────────────────
const VALIGN_OPTIONS = [
  { value: 'top',    label: __('Top'),    icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="2.5" x2="14" y2="2.5"/><line x1="2" y1="6.5" x2="12" y2="6.5"/><line x1="2" y1="10" x2="9" y2="10"/></svg>` },
  { value: 'center', label: __('Center'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="5.25" x2="12" y2="5.25"/><line x1="2" y1="8.75" x2="9" y2="8.75"/></svg>` },
  { value: 'bottom', label: __('Bottom'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="6" x2="9" y2="6"/><line x1="2" y1="9.5" x2="12" y2="9.5"/><line x1="2" y1="13.5" x2="14" y2="13.5"/></svg>` },
];

const introValign = computed(() => {
  const v = screens.value.intro_valign;
  return v === 'center' || v === 'bottom' ? v : 'top';
});

function onValignChange(next) {
  if (next === introValign.value) return;
  store.stageSettings({ screens: { ...screens.value, intro_valign: next } });
}

function scheduleScreensSave(key, value) {
  store.stageSettings({ screens: { ...screens.value, [key]: value } });
}

// Called on @blur for inputs that already persist every keystroke via
// scheduleScreensSave. Nothing to flush — the store is always up to date.
// eslint-disable-next-line @typescript-eslint/no-empty-function
function flushScreensSave() {}

function onScreensImageInput(key, value) {
  store.stageSettings({ screens: { ...screens.value, [key]: value ?? '' } });
  // Image uploads (cover + background) persist immediately so a reload keeps the image.
  store.flushStagedSettings();
}

function onShowMetaChange(next) {
  store.stageSettings({ screens: { ...screens.value, intro_show_meta: !!next } });
}

// ── Image fit ─────────────────────────────────────────────────────────────────
const fitOptions = [
  { value: 'contain', label: __('Contain') },
  { value: 'cover',   label: __('Cover') },
  { value: 'repeat',  label: __('Repeat') },
];

const FIT_HINTS = {
  contain: __('Whole image visible. No cropping.'),
  cover:   __('Fills the slot. May crop your image.'),
  repeat:  __('Tiles at native size — best for patterns.'),
};

const introImageFit = computed(() => {
  const v = screens.value.intro_image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

const activeFitHint = computed(() => FIT_HINTS[introImageFit.value]);

function onFitChange(next) {
  if (next === introImageFit.value) return;
  store.stageSettings({ screens: { ...screens.value, intro_image_fit: next } });
  store.flushStagedSettings();
}

// ── Image height ──────────────────────────────────────────────────────────────
const DEFAULT_IMAGE_HEIGHT = 300;

const introImageHeight = computed(() => {
  const h = Number(screens.value.intro_image_height);
  return Number.isFinite(h) && h > 0 ? h : DEFAULT_IMAGE_HEIGHT;
});

// Slider fires @input on every tick while dragging — update local store immediately
// for live preview, but debounce the API write so only one save fires when the user stops.
let heightFlushTimer = null;
function onImageHeightChange(rawValue) {
  const parsed = Math.max(80, Math.min(800, Math.floor(Number(rawValue) || DEFAULT_IMAGE_HEIGHT)));
  store.stageSettings({ screens: { ...screens.value, intro_image_height: parsed } });
  if (heightFlushTimer) clearTimeout(heightFlushTimer);
  heightFlushTimer = setTimeout(() => store.flushStagedSettings(), 300);
}

const introBgEnabled = computed(() => Boolean(screens.value.intro_bg_enabled));
const introBgOpacity = computed(() => {
  const v = Number(screens.value.intro_bg_opacity);
  return Number.isFinite(v) ? Math.max(0, Math.min(100, v)) : 0;
});

function onIntroBgToggle(next) {
  store.stageSettings({ screens: { ...screens.value, intro_bg_enabled: Boolean(next) } });
  // Toggles persist immediately so a reload reflects the new state right away.
  store.flushStagedSettings();
}

function onIntroBgOpacityInput(value) {
  scheduleScreensSave('intro_bg_opacity', Math.max(0, Math.min(100, Number(value) || 0)));
}


</script>

<style scoped>
.iprops {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--bg-surface);
  min-height: 0;
}

.iprops__scroll {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
  padding-bottom: 32px;
}

.iprops-section + .iprops-section {
  border-top: 1px solid var(--border-1);
}

.iprops-section__head {
  padding: 18px 16px 8px;
}

.iprops-section__title {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
}

.iprops-section__body {
  padding: 4px 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.iprops-hint {
  margin: 0;
  font-size: 11.5px;
  color: var(--ink-3);
  line-height: 1.4;
}

.iprops-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  background: var(--bg-canvas);
  border-radius: var(--r-sm);
  font-size: 13px;
  color: var(--ink-2);
}


.iprops-row.is-pro {
  background: transparent;
  border: 1px dashed var(--border-2);
  color: var(--ink-3);
}

.iprops-row__label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
}

.iprops-overlay {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
}

.iprops-overlay__label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
}

.iprops-overlay__pct {
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-3);
}

.iprops-overlay__range {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 4px;
  background: var(--border-2);
  border-radius: var(--r-pill);
  outline: none;
  cursor: pointer;
}

.iprops-overlay__range::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--brand);
  cursor: pointer;
  border: 2px solid #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.18);
}

:deep(.quizably-help-dot) {
  display: inline-grid;
  place-items: center;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--bg-subtle);
  color: var(--ink-3);
  font-size: 10px;
  font-weight: 600;
  cursor: help;
  user-select: none;
  border: 1px solid var(--border-1);
}

/* ── Image fit ───────────────────────────────────────────────────────────────── */
.iprops-fit {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.iprops-fit__label {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-3);
  font-weight: 500;
}

.iprops-fit__seg {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.iprops-fit__opt {
  padding: 7px 8px;
  border: 0;
  border-radius: var(--r-xs);
  background: transparent;
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-3);
  cursor: pointer;
  transition: background 150ms, color 150ms;
}

.iprops-fit__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.iprops-fit__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.iprops-fit__hint {
  margin: 0;
  font-size: 11.5px;
  color: var(--ink-3);
  line-height: 1.4;
}

/* ── Alignment segmented control ─────────────────────────────────────────────── */
.iprops-align {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.iprops-align__opt {
  padding: 8px;
  border: 0;
  border-radius: var(--r-xs);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ink-3);
  cursor: pointer;
  transition: background 150ms, color 150ms;
}

.iprops-align__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.iprops-align__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.iprops-align__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
}

.iprops-align__icon :deep(svg) {
  width: 16px;
  height: 16px;
}

</style>
