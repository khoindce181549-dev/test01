<template>
  <div class="rprops">
    <div class="rprops__scroll">
      <!-- Image -->
      <section class="rprops-section">
        <header class="rprops-section__head">
          <span class="rprops-section__title">{{ __('Image') }}</span>
        </header>
        <div class="rprops-section__body">
          <MediaPicker
            :model-value="result.image_url || ''"
            label=""
            helper-text=""
            :button-text="__('Upload image')"
            :replace-text="__('Replace image')"
            :remove-text="__('Delete image')"
            @update:model-value="onImageChange"
          />

          <div
            v-if="result.image_url"
            class="rprops-fit"
          >
            <span class="rprops-fit__label">{{ __('Image fit') }}</span>
            <div
              class="rprops-fit__seg"
              role="radiogroup"
              :aria-label="__('Image fit')"
            >
              <button
                v-for="opt in FIT_OPTIONS"
                :key="opt.value"
                type="button"
                role="radio"
                :aria-checked="imageFit === opt.value"
                :class="['rprops-fit__opt', { 'is-active': imageFit === opt.value }]"
                @click="onFitChange(opt.value)"
              >
                {{ opt.label }}
              </button>
            </div>
            <p class="rprops-hint">
              {{ activeFitHint }}
            </p>
          </div>

          <div
            v-if="result.image_url"
            class="rprops-height"
          >
            <div class="rprops-height__label">
              <span>{{ __('Height') }}</span>
              <span class="rprops-height__val">{{ imageHeight }} px</span>
            </div>
            <input
              type="range"
              min="80"
              max="800"
              step="10"
              class="rprops-height__range"
              :value="imageHeight"
              :aria-label="__('Image height in pixels')"
              @input="onImageHeightChange($event.target.value)"
            >
          </div>
        </div>
      </section>

      <!-- Alignment -->
      <section class="rprops-section">
        <header class="rprops-section__head">
          <span class="rprops-section__title">{{ __('Alignment') }}</span>
        </header>
        <div class="rprops-section__body">
          <div
            class="rprops-align"
            role="radiogroup"
            :aria-label="__('Content alignment')"
          >
            <button
              v-for="opt in ALIGN_OPTIONS"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="resultAlign === opt.value"
              :class="['rprops-align__opt', { 'is-active': resultAlign === opt.value }]"
              :aria-label="opt.label"
              :title="opt.label"
              @click="onAlignChange(opt.value)"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span
                class="rprops-align__icon"
                aria-hidden="true"
                v-html="opt.icon"
              />
            </button>
          </div>
        </div>
      </section>

      <section class="rprops-section">
        <header class="rprops-section__head">
          <span class="rprops-section__title">{{ __('Vertical alignment') }}</span>
        </header>
        <div class="rprops-section__body">
          <div
            class="rprops-align"
            role="radiogroup"
            :aria-label="__('Vertical alignment')"
          >
            <button
              v-for="opt in VALIGN_OPTIONS"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="resultValign === opt.value"
              :class="['rprops-align__opt', { 'is-active': resultValign === opt.value }]"
              :aria-label="opt.label"
              :title="opt.label"
              @click="onValignChange(opt.value)"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span
                class="rprops-align__icon"
                aria-hidden="true"
                v-html="opt.icon"
              />
            </button>
          </div>
        </div>
      </section>

      <!-- Background — free. -->
      <section class="rprops-section">
        <header class="rprops-section__head">
          <span class="rprops-section__title">{{ __('Background') }}</span>
        </header>
        <div class="rprops-section__body">
          <div class="rprops-toggle-row">
            <div class="rprops-toggle-row__label">
              <span>{{ __('Custom background') }}</span>
            </div>
            <Toggle
              :model-value="bgEnabled"
              size="sm"
              data-testid="result-bg-toggle"
              @update:model-value="onBgToggle"
            />
          </div>
          <template v-if="bgEnabled">
            <MediaPicker
              :model-value="resultBg.background_image || ''"
              label=""
              helper-text=""
              :button-text="__('Upload background')"
              :replace-text="__('Replace background')"
              :remove-text="__('Remove background')"
              @update:model-value="onBgImageInput"
            />
            <div class="rprops-height">
              <div class="rprops-height__label">
                <span>{{ __('Overlay opacity') }}</span>
                <span class="rprops-height__val">{{ bgOpacity }}%</span>
              </div>
              <input
                type="range"
                min="0"
                max="100"
                step="1"
                class="rprops-height__range"
                :value="bgOpacity"
                data-testid="r-bg-opacity"
                :aria-label="__('Background overlay opacity')"
                @input="(e) => onBgOpacityInput(Number(e.target.value))"
              >
            </div>
          </template>
        </div>
      </section>

      <!-- On completion -->
      <section class="rprops-section">
        <header class="rprops-section__head">
          <span class="rprops-section__title">{{ __('On completion') }}</span>
        </header>
        <div class="rprops-section__body">
          <div
            class="rprops-mode__seg"
            role="radiogroup"
            :aria-label="__('Completion behavior')"
          >
            <button
              type="button"
              role="radio"
              :aria-checked="mode === 'show'"
              :class="['rprops-mode__opt', { 'is-active': mode === 'show' }]"
              @click="setMode('show')"
            >
              {{ __('Show result') }}
            </button>
            <button
              type="button"
              role="radio"
              :aria-checked="mode === 'redirect'"
              :class="['rprops-mode__opt', { 'is-active': mode === 'redirect' }]"
              @click="setMode('redirect')"
            >
              {{ __('Auto-redirect') }}
            </button>
          </div>

          <template v-if="mode === 'show'">
            <p class="rprops-hint">
              {{ __('Visitors see this result card with your title, content, and image. Optionally add a button below.') }}
            </p>
            <label
              class="rprops-label"
              :for="ctaLabelId"
            >{{ __('Button label') }}</label>
            <Input
              :id="ctaLabelId"
              v-model="draftCtaLabel"
              :placeholder="__('Shop my match')"
              @update:model-value="scheduleCtaSave"
            />
            <label
              class="rprops-label"
              :for="ctaUrlId"
            >{{ __('Button URL') }}</label>
            <Input
              :id="ctaUrlId"
              v-model="draftCtaUrl"
              ltr
              placeholder="https://…"
              @update:model-value="scheduleCtaSave"
            />
          </template>

          <template v-else>
            <label
              class="rprops-label"
              :for="redirectUrlId"
            >{{ __('Redirect URL') }}</label>
            <Input
              :id="redirectUrlId"
              v-model="draftRedirect"
              placeholder="https://…"
              @update:model-value="scheduleRedirectSave"
            />
            <div
              class="rprops-note rprops-note--warn"
              role="note"
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
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                <line
                  x1="12"
                  y1="9"
                  x2="12"
                  y2="13"
                />
                <line
                  x1="12"
                  y1="17"
                  x2="12.01"
                  y2="17"
                />
              </svg>
              <div>
                <strong>{{ __("The result card won't be shown.") }}</strong>
                {{ __("When the visitor finishes the quiz, they're redirected to the URL above immediately — your title, content, image, and CTA are skipped.") }}
              </div>
            </div>
          </template>
        </div>
      </section>

      <!-- Score range (trivia only) -->
      <section
        v-if="quizType === 'trivia'"
        class="rprops-section"
      >
        <header class="rprops-section__head">
          <span class="rprops-section__title">{{ __('Score range') }}</span>
        </header>
        <div class="rprops-section__body">
          <div class="rprops-row-grid">
            <div>
              <label
                class="rprops-label"
                :for="scoreMinId"
              >{{ __('Min') }}</label>
              <Input
                :id="scoreMinId"
                v-model="draftScoreMin"
                type="number"
                @update:model-value="scheduleScoreBoundsSave"
              />
            </div>
            <div>
              <label
                class="rprops-label"
                :for="scoreMaxId"
              >{{ __('Max') }}</label>
              <Input
                :id="scoreMaxId"
                v-model="draftScoreMax"
                type="number"
                @update:model-value="scheduleScoreBoundsSave"
              />
            </div>
          </div>
          <p class="rprops-hint">
            {{ __('Visitors landing inside this range see this result.') }}
          </p>
        </div>
      </section>

      <!-- Weighted conditions (Pro placeholder) -->
      <section
        v-if="quizType === 'weighted' && proTierVisible"
        class="rprops-section"
      >
        <header class="rprops-section__head">
          <span class="rprops-section__title">{{ __('Conditions') }}</span>
          <Badge
            variant="pro"
            size="sm"
          >
            {{ __('Pro') }}
          </Badge>
        </header>
        <div class="rprops-section__body">
          <textarea
            class="rprops-pro-textarea"
            rows="4"
            placeholder="[{&quot;category&quot;:&quot;adventurous&quot;,&quot;min&quot;:5}]"
            disabled
          />
          <p class="rprops-hint">
            {{ __('Upgrade to Pro to use weighted results.') }}
          </p>
        </div>
      </section>

      <!-- ── Chart ─────────────────────────────────────── -->
      <!-- Pro-tier: absent unless Pro is active or promoted. -->
      <section
        v-if="proTierVisible"
        class="rprops-section"
      >
        <header class="rprops-section__head">
          <span class="rprops-section__title">{{ __('Chart') }}</span>
          <Badge
            variant="pro"
            size="sm"
          >
            {{ __('Pro') }}
          </Badge>
        </header>
        <div class="rprops-section__body">
          <p class="rprops-hint">
            {{ __('Visualise the result breakdown as a chart below the result title.') }}
          </p>
          <!-- Type picker -->
          <div
            class="rprops-chart-types"
            role="radiogroup"
            :aria-label="__('Chart type')"
          >
            <button
              v-for="opt in CHART_TYPES"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="chartType === opt.value"
              :class="['rprops-chart-type', { 'is-active': chartType === opt.value, 'is-disabled': !isProUser }]"
              :disabled="!isProUser"
              @click="isProUser && onChartType(opt.value)"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span
                class="rprops-chart-type__icon"
                aria-hidden="true"
                v-html="opt.icon"
              />
              <span class="rprops-chart-type__label">{{ opt.label }}</span>
            </button>
          </div>
          <p
            v-if="!isProUser"
            class="rprops-hint rprops-hint--pro"
          >
            {{ __('Upgrade to Pro to add charts to your result screens.') }}
          </p>
        </div>
      </section>

      <!-- ── Social sharing ─────────────────────────────── -->
      <section class="rprops-section">
        <header class="rprops-section__head">
          <span class="rprops-section__title">{{ __('Social sharing') }}</span>
        </header>
        <div class="rprops-section__body">
          <!-- Show share toggle (free) -->
          <div class="rprops-toggle-row">
            <div class="rprops-toggle-row__label">
              <span>{{ __('Show share buttons') }}</span>
            </div>
            <Toggle
              :model-value="shareEnabled"
              size="sm"
              data-testid="rprops-share-enabled"
              @update:model-value="onShareEnabled"
            />
          </div>

          <template v-if="shareEnabled">
            <!-- Platforms -->
            <p
              class="rprops-label"
              style="margin-top:8px"
            >
              {{ __('Platforms') }}
            </p>
            <div class="rprops-platforms">
              <div
                v-for="p in platforms"
                :key="p.key"
                role="checkbox"
                :aria-checked="isPlatformActive(p.key)"
                :aria-disabled="p.pro && !isProUser ? 'true' : undefined"
                :tabindex="p.pro && !isProUser ? -1 : 0"
                :class="['rprops-platform', { 'is-checked': isPlatformActive(p.key), 'is-pro': p.pro && !isProUser }]"
                :style="{ '--plt-color': p.color }"
                @click="togglePlatform(p.key)"
                @keydown.space.prevent="togglePlatform(p.key)"
                @keydown.enter.prevent="togglePlatform(p.key)"
              >
                <span
                  class="rprops-platform__icon-wrap"
                  aria-hidden="true"
                >
                  <!-- eslint-disable-next-line vue/no-v-html -->
                  <span
                    class="rprops-platform__icon"
                    v-html="p.icon"
                  />
                </span>
                <span class="rprops-platform__name">{{ p.label }}</span>
                <span
                  v-if="isPlatformActive(p.key) && !(p.pro && !isProUser)"
                  class="rprops-platform__check"
                  aria-hidden="true"
                >
                  <svg
                    viewBox="0 0 12 12"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  ><polyline points="2 6 5 9 10 3" /></svg>
                </span>
                <span
                  v-else-if="p.pro && !isProUser"
                  class="rprops-platform__lock"
                  aria-hidden="true"
                >
                  <svg
                    viewBox="0 0 12 12"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  ><rect
                    x="2"
                    y="5.5"
                    width="8"
                    height="5.5"
                    rx="1"
                  /><path d="M4 5.5V4a2 2 0 1 1 4 0v1.5" /></svg>
                </span>
              </div>
            </div>

            <!-- Custom message (Pro-tier): absent unless Pro is active or promoted. -->
            <template v-if="proTierVisible">
              <label
                class="rprops-label"
                style="margin-top:8px"
              >{{ __('Share message') }}</label>
              <Input
                :model-value="shareMessage"
                :placeholder="isProUser ? __('I got: {title} — check it out!') : __('Upgrade to Pro to customise')"
                :disabled="!isProUser"
                @update:model-value="onShareMessage"
              />
              <p class="rprops-hint">
                <!-- eslint-disable-next-line vue/no-v-html -->
                <span v-html="titleTokenHint" />
                <span
                  v-if="!isProUser"
                  class="rprops-hint--pro"
                > {{ __('(Pro)') }}</span>
              </p>
            </template>
          </template>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { __, sprintf } from '@shared/i18n';
import { Badge, Input, MediaPicker, Toggle, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { isPro, proFeaturesVisible } from '@admin/api/pro.js';

const props = defineProps({
  result: { type: Object, required: true },
  quizType: { type: String, default: 'personality' },
});

const store = useQuizBuilderStore();
useToast(); // available if needed

const isProUser = computed(() => isPro());
// Pro-tier controls (background, chart, extra share platforms, share message)
// are only listed when Pro is active or Pro promotion is on (see api/pro.js).
const proTierVisible = computed(() => proFeaturesVisible());

// ---- IDs for accessibility ----
const uid = Math.random().toString(36).slice(2, 9);
const ctaLabelId = `rprops-cta-label-${uid}`;
const ctaUrlId = `rprops-cta-url-${uid}`;
const redirectUrlId = `rprops-redirect-url-${uid}`;
const scoreMinId = `rprops-score-min-${uid}`;
const scoreMaxId = `rprops-score-max-${uid}`;

// ---- Completion behaviour ----
const draftCtaLabel = ref(props.result.cta_label ?? '');
const draftCtaUrl = ref(props.result.cta_url ?? '');
const draftRedirect = ref(props.result.redirect_url ?? '');
const draftScoreMin = ref(props.result.score_min ?? props.result.min_score ?? 0);
const draftScoreMax = ref(props.result.score_max ?? props.result.max_score ?? 0);
const modeOverride = ref(null);

const mode = computed(() => {
  if (modeOverride.value) return modeOverride.value;
  return (draftRedirect.value || '').trim() ? 'redirect' : 'show';
});

function setMode(next) {
  if (next === mode.value) return;
  modeOverride.value = next;
  if (next === 'show') {
    draftRedirect.value = '';
    store.stageResult(props.result.id, { redirect_url: '' });
  }
}

watch(
  () => props.result.id,
  () => {
    draftCtaLabel.value = props.result.cta_label ?? '';
    draftCtaUrl.value = props.result.cta_url ?? '';
    draftRedirect.value = props.result.redirect_url ?? '';
    draftScoreMin.value = props.result.score_min ?? props.result.min_score ?? 0;
    draftScoreMax.value = props.result.score_max ?? props.result.max_score ?? 0;
    modeOverride.value = null;
  }
);

function scheduleCtaSave() {
  store.stageResult(props.result.id, {
    cta_label: (draftCtaLabel.value ?? '').toString(),
    cta_url: (draftCtaUrl.value ?? '').toString(),
  });
}

function scheduleRedirectSave() {
  store.stageResult(props.result.id, { redirect_url: (draftRedirect.value ?? '').toString() });
}

function scheduleScoreBoundsSave() {
  store.stageResult(props.result.id, {
    score_min: Number(draftScoreMin.value) || 0,
    score_max: Number(draftScoreMax.value) || 0,
  });
}

function onImageChange(next) {
  store.stageResult(props.result.id, { image_url: (next ?? '').toString() });
  // Image uploads persist immediately so a reload keeps the chosen image.
  store.flushStagedResults();
}

// ── Image fit ─────────────────────────────────────────────────────────────────
const FIT_OPTIONS = [
  { value: 'contain', label: __('Contain') },
  { value: 'cover',   label: __('Cover') },
  { value: 'repeat',  label: __('Repeat') },
];

const FIT_HINTS = {
  contain: __('Whole image visible. No cropping.'),
  cover:   __('Fills the slot. May crop your image.'),
  repeat:  __('Tiles at native size — best for patterns.'),
};

const imageFit = computed(() => {
  const v = parseSettings(props.result.settings)?.image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

// translators: %s is the literal token {title} wrapped in <code>; static markup, rendered with v-html.
const titleTokenHint = sprintf(__('Use %s to insert the result title.'), '<code>{title}</code>');

const activeFitHint = computed(() => FIT_HINTS[imageFit.value]);

function onFitChange(next) {
  if (next === imageFit.value) return;
  patchSettings('image_fit', next);
  store.flushStagedResults();
}

// ── Image height ──────────────────────────────────────────────────────────────
const DEFAULT_IMAGE_HEIGHT = 240;

const imageHeight = computed(() => {
  const h = Number(parseSettings(props.result.settings)?.image_height);
  return Number.isFinite(h) && h > 0 ? h : DEFAULT_IMAGE_HEIGHT;
});

// Slider fires on every drag tick — update preview immediately, debounce the API write.
let heightFlushTimer = null;
function onImageHeightChange(rawValue) {
  const parsed = Math.max(80, Math.min(800, Math.floor(Number(rawValue) || DEFAULT_IMAGE_HEIGHT)));
  patchSettings('image_height', parsed);
  if (heightFlushTimer) clearTimeout(heightFlushTimer);
  heightFlushTimer = setTimeout(() => store.flushStagedResults(), 300);
}

// ── Alignment ─────────────────────────────────────────────────────────────────
const ALIGN_OPTIONS = [
  { value: 'left',   label: __('Left'),   icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="2" y1="8" x2="10" y2="8"/><line x1="2" y1="12" x2="12" y2="12"/></svg>` },
  { value: 'center', label: __('Center'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="4" y1="8" x2="12" y2="8"/><line x1="3" y1="12" x2="13" y2="12"/></svg>` },
  { value: 'right',  label: __('Right'),  icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="6" y1="8" x2="14" y2="8"/><line x1="4" y1="12" x2="14" y2="12"/></svg>` },
];

const resultAlign = computed(() => {
  const v = parseSettings(props.result.settings)?.align;
  return v === 'left' || v === 'right' ? v : 'center';
});

function onAlignChange(next) {
  if (next === resultAlign.value) return;
  patchSettings('align', next);
}

// ── Vertical alignment ─────────────────────────────────────────────────────────
const VALIGN_OPTIONS = [
  { value: 'top',    label: __('Top'),    icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="2.5" x2="14" y2="2.5"/><line x1="2" y1="6.5" x2="12" y2="6.5"/><line x1="2" y1="10" x2="9" y2="10"/></svg>` },
  { value: 'center', label: __('Center'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="5.25" x2="12" y2="5.25"/><line x1="2" y1="8.75" x2="9" y2="8.75"/></svg>` },
  { value: 'bottom', label: __('Bottom'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="6" x2="9" y2="6"/><line x1="2" y1="9.5" x2="12" y2="9.5"/><line x1="2" y1="13.5" x2="14" y2="13.5"/></svg>` },
];

const resultValign = computed(() => {
  const v = parseSettings(props.result.settings)?.valign;
  return v === 'center' || v === 'bottom' ? v : 'top';
});

function onValignChange(next) {
  if (next === resultValign.value) return;
  patchSettings('valign', next);
}

// ── Per-result background ─────────────────────────────────────────────────────
// Stored at result.settings.background = { enabled, background_image, overlay_color, overlay_opacity }
// Quiz.vue reads r?.settings?.background and passes it through resolveBgStyle().
const resultBg = computed(() => {
  const bg = parseSettings(props.result.settings)?.background;
  return bg && typeof bg === 'object' ? bg : {};
});

const bgEnabled = computed(() => Boolean(resultBg.value.enabled));
const bgOpacity = computed(() => {
  const v = Number(resultBg.value.overlay_opacity);
  return Number.isFinite(v) ? Math.max(0, Math.min(100, v)) : 0;
});

function onBgToggle(next) {
  patchSettings('background', { ...resultBg.value, enabled: Boolean(next) });
  store.flushStagedResults();
}

function onBgImageInput(url) {
  patchSettings('background', { ...resultBg.value, background_image: (url ?? '').toString() });
  store.flushStagedResults();
}

let bgOpacityTimer = null;
function onBgOpacityInput(value) {
  patchSettings('background', { ...resultBg.value, overlay_opacity: Math.max(0, Math.min(100, Number(value) || 0)) });
  if (bgOpacityTimer) clearTimeout(bgOpacityTimer);
  bgOpacityTimer = setTimeout(() => store.flushStagedResults(), 300);
}

// ---- Helpers for merging result.settings without clobbering other keys ----
function parseSettings(raw) {
  if (raw && typeof raw === 'object') return { ...raw };
  if (typeof raw === 'string' && raw) {
    try { return { ...JSON.parse(raw) }; } catch { /* malformed JSON — start fresh */ }
  }
  return {};
}

function patchSettings(key, value) {
  const current = parseSettings(props.result.settings);
  current[key] = value;
  store.stageResult(props.result.id, { settings: current });
}

// ---- Chart ----
const CHART_TYPES = [
  {
    value: 'none',
    label: __('None'),
    icon: `<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="4" x2="16" y2="16"/><line x1="16" y1="4" x2="4" y2="16"/></svg>`,
  },
  {
    value: 'pie',
    label: __('Pie'),
    icon: `<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2a8 8 0 1 0 8 8h-8z" fill="currentColor" opacity=".18"/><path d="M10 2v8h8a8 8 0 0 0-8-8z" fill="currentColor" opacity=".5"/><circle cx="10" cy="10" r="8"/></svg>`,
  },
  {
    value: 'donut',
    label: __('Donut'),
    icon: `<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="8"/><circle cx="10" cy="10" r="4"/><path d="M10 2a8 8 0 0 1 8 8" stroke-width="3"/></svg>`,
  },
  {
    value: 'bar',
    label: __('Bar'),
    icon: `<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="4" height="6" rx="1" fill="currentColor" opacity=".3"/><rect x="8" y="6" width="4" height="11" rx="1" fill="currentColor" opacity=".6"/><rect x="13" y="3" width="4" height="14" rx="1" fill="currentColor" opacity=".9"/></svg>`,
  },
];

const chartType = computed(() => parseSettings(props.result.settings)?.chart?.type ?? 'none');

function onChartType(value) {
  patchSettings('chart', { type: value });
}

// ---- Social sharing ----
const DEFAULT_PLATFORMS = ['facebook', 'twitter', 'linkedin'];

const ALL_PLATFORMS = [
  {
    key: 'facebook', label: 'Facebook', pro: false, color: '#1877f2',
    icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M12 2H10a4 4 0 0 0-4 4v2H4v3h2v5h3v-5h2l.5-3H9V6a.5.5 0 0 1 .5-.5H12V2z"/></svg>`,
  },
  {
    key: 'twitter', label: 'X', pro: false, color: '#000000',
    icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M12.6 2h2.2L9.7 7.7 15.5 14h-4l-3.7-4.9L3.9 14H1.7l5.4-6.2L1.5 2H5.6l3.3 4.4L12.6 2zm-.8 10.8h1.2L4.3 3.2H3l8.8 9.6z"/></svg>`,
  },
  {
    key: 'linkedin', label: 'LinkedIn', pro: false, color: '#0a66c2',
    icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M11 5.5a4 4 0 0 1 4 4V14h-3v-4a1 1 0 0 0-1-1 1 1 0 0 0-1 1v4H7V5.5h3v1a3 3 0 0 1 2-.5 1.5 1.5 0 0 0-1 0zM1 6h3v8H1zM2.5 1a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/></svg>`,
  },
  {
    key: 'whatsapp', label: 'WhatsApp', pro: true, color: '#25d366',
    icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 0 0-6.1 10.4L1 15l3.7-.9A7 7 0 1 0 8 1zm3.6 9.6c-.2.4-.9.8-1.3.8-.3 0-.6.1-1.8-.4C6.9 10.3 6 8.8 5.9 8.7c-.1-.1-.8-1-.8-2s.5-1.4.7-1.6c.2-.2.4-.3.6-.3h.4c.2 0 .3 0 .4.3.2.4.6 1.4.6 1.5 0 .1 0 .2-.1.3l-.3.3c-.1.1-.2.2-.1.4.1.2.6.9 1.2 1.4.6.5 1.1.7 1.3.8.2.1.3 0 .4-.1l.3-.4c.1-.2.2-.2.4-.1l1.1.5c.2.1.3.2.3.3 0 .2-.1.6-.3 1z"/></svg>`,
  },
  {
    key: 'telegram', label: 'Telegram', pro: true, color: '#26a5e4',
    icon: `<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.4 4.8-1.7 8c-.1.5-.5.6-.8.4l-2-1.5-1 .9c-.1.1-.2.2-.5.2l.2-2.1 4.4-4c.2-.2 0-.3-.3-.1L4.5 10.7 2.6 10c-.5-.1-.5-.5.1-.7l9-3.5c.4-.1.8.1.7.9z"/></svg>`,
  },
  {
    key: 'email', label: __('Email'), pro: true, color: '#6b7280',
    icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="m1 4 7 5 7-5"/></svg>`,
  },
];

const platforms = computed(() =>
  ALL_PLATFORMS.filter((p) => !p.pro || proTierVisible.value)
);

const shareSettings = computed(() => {
  const s = parseSettings(props.result.settings)?.share;
  return s && typeof s === 'object' ? s : {};
});
const shareEnabled = computed(() => shareSettings.value.enabled !== false);
const activePlatformKeys = computed(
  () => shareSettings.value.platforms ?? DEFAULT_PLATFORMS
);
const shareMessage = computed(() => shareSettings.value.message ?? '');

function isPlatformActive(key) {
  return activePlatformKeys.value.includes(key);
}

function onShareEnabled(next) {
  patchSettings('share', { ...shareSettings.value, enabled: Boolean(next) });
}

function togglePlatform(key) {
  const p = ALL_PLATFORMS.find((x) => x.key === key);
  if (p?.pro && !isProUser.value) return;
  const current = [...activePlatformKeys.value];
  const idx = current.indexOf(key);
  if (idx >= 0) {
    current.splice(idx, 1);
  } else {
    current.push(key);
  }
  patchSettings('share', { ...shareSettings.value, platforms: current });
}

function onShareMessage(val) {
  if (!isProUser.value) return;
  patchSettings('share', { ...shareSettings.value, message: (val ?? '').toString() });
}
</script>

<style scoped>
.rprops {
  display: flex;
  flex-direction: column;
  flex: 1;
  background: var(--bg-surface);
  min-height: 0;
}

.rprops__scroll {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
  padding-bottom: 32px;
}

.rprops-section + .rprops-section {
  border-top: 1px solid var(--border-1);
}

.rprops-section__head {
  padding: 18px 16px 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.rprops-section__title {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
}

.rprops-section__body {
  padding: 4px 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.rprops-label {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
  margin-top: 4px;
}

.rprops-hint {
  font-size: 11.5px;
  color: var(--ink-3);
  margin: 2px 0 0;
  line-height: 1.45;
}

.rprops-hint code {
  font-family: var(--f-mono);
  font-size: 11px;
  background: var(--bg-subtle);
  padding: 1px 4px;
  border-radius: var(--r-xs);
}

.rprops-hint--pro {
  color: var(--accent);
}

.rprops-row-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.rprops-mode__seg {
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
  margin-bottom: 6px;
}

.rprops-mode__opt {
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

.rprops-mode__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.rprops-mode__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.rprops-note {
  display: flex;
  gap: 10px;
  padding: 10px 12px;
  border-radius: var(--r-md);
  font-size: 12px;
  line-height: 1.5;
  margin-top: 4px;
}

.rprops-note svg {
  flex-shrink: 0;
  width: 16px;
  height: 16px;
  margin-top: 1px;
}

.rprops-note strong {
  display: block;
  font-weight: 600;
  margin-bottom: 2px;
}

.rprops-note--warn {
  background: var(--accent-tint);
  border: 1px solid var(--accent-bg);
  color: var(--accent);
}

.rprops-note--warn strong {
  color: var(--ink-1);
}

.rprops-note--warn > div {
  color: var(--ink-2);
}

/* ── Image fit segmented control ─────────────────────────────────────────────── */
.rprops-fit {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.rprops-fit__label {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-3);
  font-weight: 500;
}

.rprops-fit__seg {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.rprops-fit__opt {
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

.rprops-fit__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.rprops-fit__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

/* ── Image height slider ─────────────────────────────────────────────────────── */
.rprops-height {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
}

.rprops-height__label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
}

.rprops-height__val {
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-3);
}

.rprops-height__range {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 4px;
  background: var(--border-2);
  border-radius: var(--r-pill);
  outline: none;
  cursor: pointer;
}

.rprops-height__range::-webkit-slider-thumb {
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

.rprops-height__range::-moz-range-thumb {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--brand);
  cursor: pointer;
  border: 2px solid #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.18);
}

.rprops-pro-textarea {
  width: 100%;
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  background: var(--bg-canvas);
  padding: 8px 10px;
  font-family: var(--f-mono);
  font-size: 12px;
  color: var(--ink-3);
  resize: vertical;
}

/* ---- Chart type picker ---- */
.rprops-chart-types {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
}

.rprops-chart-type {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  padding: 9px 4px 8px;
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  background: var(--bg-canvas);
  font-family: inherit;
  font-size: 11px;
  font-weight: 500;
  color: var(--ink-3);
  cursor: pointer;
  transition: border-color 130ms, color 130ms, background 130ms;
}

.rprops-chart-type:hover:not(.is-disabled):not(.is-active) {
  border-color: var(--border-3);
  color: var(--ink-1);
  background: var(--bg-subtle);
}

.rprops-chart-type.is-active {
  border-color: var(--brand);
  background: color-mix(in srgb, var(--brand) 8%, transparent);
  color: var(--brand);
}

.rprops-chart-type.is-disabled {
  opacity: 0.45;
  cursor: default;
}

.rprops-chart-type__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
}

.rprops-chart-type__icon :deep(svg) {
  width: 20px;
  height: 20px;
}

.rprops-chart-type__label {
  line-height: 1;
}

/* ---- Share toggle row ---- */
.rprops-toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 10px;
  background: var(--bg-canvas);
  border-radius: var(--r-sm);
}

.rprops-toggle-row__label {
  font-size: 13px;
  color: var(--ink-2);
}

/* ---- Platform chips (2-col grid) ---- */
.rprops-platforms {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 5px;
}

.rprops-platform {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 8px 9px;
  border-radius: var(--r-sm);
  background: var(--bg-canvas);
  border: 1.5px solid var(--border-1);
  cursor: pointer;
  user-select: none;
  transition: border-color 130ms, background 130ms;
}

.rprops-platform:hover:not(.is-pro) {
  border-color: var(--plt-color, var(--border-3));
  background: color-mix(in srgb, var(--plt-color, var(--brand)) 5%, transparent);
}

.rprops-platform.is-checked {
  border-color: var(--plt-color, var(--brand));
  background: color-mix(in srgb, var(--plt-color, var(--brand)) 8%, transparent);
}

.rprops-platform.is-pro {
  opacity: 0.55;
  cursor: default;
}

.rprops-platform__icon-wrap {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--bg-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 130ms;
}

.rprops-platform.is-checked .rprops-platform__icon-wrap {
  background: var(--plt-color, var(--brand));
}

.rprops-platform__icon {
  display: inline-flex;
  align-items: center;
  width: 13px;
  height: 13px;
  color: var(--ink-3);
}

.rprops-platform__icon :deep(svg) {
  width: 13px;
  height: 13px;
}

.rprops-platform.is-checked .rprops-platform__icon {
  color: #fff;
}

.rprops-platform__name {
  flex: 1;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
}

.rprops-platform.is-checked .rprops-platform__name {
  color: var(--ink-1);
}

.rprops-platform__check {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  flex-shrink: 0;
  color: var(--plt-color, var(--brand));
}

.rprops-platform__check svg {
  width: 12px;
  height: 12px;
}

.rprops-platform__lock {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  flex-shrink: 0;
  color: var(--ink-4);
}

.rprops-platform__lock svg {
  width: 12px;
  height: 12px;
}

/* ── Alignment segmented control ─────────────────────────────────────────────── */
.rprops-align {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.rprops-align__opt {
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

.rprops-align__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.rprops-align__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.rprops-align__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
}

.rprops-align__icon :deep(svg) {
  width: 16px;
  height: 16px;
}
</style>
