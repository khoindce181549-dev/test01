<template>
  <div class="design-rail">
      <div class="design-rail__scroll">
        <!-- Layout mode (card vs full-width) + dimension controls -->
        <section class="design-rail__section design-section">
          <header class="design-rail__head">
            <span class="design-rail__title">{{ __('Layout') }}</span>
          </header>
          <div class="design-rail__body">

            <!-- Card / Full Width toggle — not applicable to Fullscreen or Splitscreen -->
            <template v-if="!isFullscreen && !isSplitscreen">
              <div class="design-seg design-seg--2" role="radiogroup" :aria-label="__('Layout mode')">
                <button
                  type="button" role="radio"
                  :aria-checked="layoutMode === 'card'"
                  :class="['design-seg__opt', { 'is-active': layoutMode === 'card' }]"
                  @click="onLayoutModeChange('card')"
                >{{ __('Card') }}</button>
                <button
                  type="button" role="radio"
                  :aria-checked="layoutMode === 'full'"
                  :class="['design-seg__opt', { 'is-active': layoutMode === 'full' }]"
                  @click="onLayoutModeChange('full')"
                >{{ __('Full width') }}</button>
              </div>

              <!-- Card max-width — hidden in full mode -->
              <template v-if="layoutMode === 'card'">
                <p class="design-field__label" style="margin-top:14px;">
                  {{ __('Card width') }}
                  <span class="design-field__value">{{ cardMaxWidth }}{{ cardMaxWidthUnit }}</span>
                </p>
                <div class="design-slider-row">
                  <input
                    type="range" class="design-slider"
                    :min="cardWidthMin" :max="cardWidthMax" :step="cardWidthStep"
                    :value="cardMaxWidth"
                    :aria-label="__('Card width')"
                    @input="onCardMaxWidthInput"
                  >
                  <div class="design-unit-seg" role="radiogroup" :aria-label="__('Card width unit')">
                    <button
                      v-for="u in ['px', '%']" :key="u"
                      type="button" role="radio"
                      :aria-checked="cardMaxWidthUnit === u"
                      :class="['design-unit-btn', { 'is-active': cardMaxWidthUnit === u }]"
                      @click="onCardMaxWidthUnitChange(u)"
                    >{{ u }}</button>
                  </div>
                </div>
              </template>

              <!-- Min height — Fullscreen is always 100vh; Splitscreen controls this in its own section -->
              <p class="design-field__label" style="margin-top:14px;">
                {{ __('Min height') }}
                <span class="design-field__value">{{ cardMinHeight > 0 ? cardMinHeight + cardMinHeightUnit : __('auto') }}</span>
              </p>
              <div class="design-slider-row">
                <input
                  type="range" class="design-slider"
                  :min="0" :max="minHeightMax" :step="minHeightStep"
                  :value="cardMinHeight"
                  :aria-label="__('Min height')"
                  @input="onCardMinHeightInput"
                >
                <div class="design-unit-seg" role="radiogroup" :aria-label="__('Min height unit')">
                  <button
                    v-for="u in ['px', 'vh']" :key="u"
                    type="button" role="radio"
                    :aria-checked="cardMinHeightUnit === u"
                    :class="['design-unit-btn', { 'is-active': cardMinHeightUnit === u }]"
                    @click="onCardMinHeightUnitChange(u)"
                  >{{ u }}</button>
                </div>
              </div>
            </template>

            <!-- Inner container width (both modes) -->
            <p class="design-field__label" style="margin-top:14px;">
              {{ __('Inner width') }}
              <span class="design-field__value">{{ innerMaxWidth > 0 ? innerMaxWidth + innerMaxWidthUnit : __('auto') }}</span>
            </p>
            <div class="design-slider-row">
              <input
                type="range" class="design-slider"
                :min="0" :max="innerWidthMax" :step="innerWidthStep"
                :value="innerMaxWidth"
                :aria-label="__('Inner content width')"
                @input="onInnerMaxWidthInput"
              >
              <div class="design-unit-seg" role="radiogroup" :aria-label="__('Inner width unit')">
                <button
                  v-for="u in ['px', '%']" :key="u"
                  type="button" role="radio"
                  :aria-checked="innerMaxWidthUnit === u"
                  :class="['design-unit-btn', { 'is-active': innerMaxWidthUnit === u }]"
                  @click="onInnerMaxWidthUnitChange(u)"
                >{{ u }}</button>
              </div>
            </div>

            <p class="design-hint" style="margin-top:4px;">
              {{ __('Inner width constrains the readable content column inside the container. Useful for wide cards or full-width mode.') }}
            </p>

            <!-- Card border toggle — only meaningful for card-surface templates -->
            <label v-if="isCardTemplate" class="design-toggle" style="margin-top:16px;">
              <span class="design-toggle__track">
                <input
                  type="checkbox"
                  class="design-toggle__input"
                  :checked="showCardBorder"
                  @change="onShowCardBorderChange($event.target.checked)"
                >
                <span class="design-toggle__thumb" />
              </span>
              <span class="design-toggle__label">{{ __('Show card border') }}</span>
            </label>
          </div>
        </section>

        <!-- Template -->
        <section class="design-rail__section design-section">
          <header class="design-rail__head">
            <span class="design-rail__title">{{ __('Template') }}</span>
            <span class="design-rail__sub">{{ activeTemplate?.name || '—' }}</span>
          </header>
          <div class="design-rail__body">
            <div
              v-if="loadingTemplates && !templatesStore.items.length"
              class="design-tmpl-loading"
              aria-live="polite"
            >
              {{ __('Loading templates…') }}
            </div>
            <div
              v-else
              class="design-tmpl-strip design-template"
              role="radiogroup"
              :aria-label="__('Template')"
            >
              <button
                v-for="tpl in visibleTemplates"
                :key="tpl.id"
                type="button"
                role="radio"
                :aria-checked="isActiveTemplate(tpl)"
                :class="[
                  'design-tmpl',
                  {
                    'is-active': isActiveTemplate(tpl),
                    'is-pro': tpl.pro && !isProUser,
                  },
                ]"
                :disabled="applyingTemplate"
                @click="onPickTemplate(tpl)"
              >
                <div
                  class="design-tmpl__thumb"
                  :style="templateThumbStyle(tpl)"
                >
                  <img
                    v-if="tpl.thumbnail"
                    :src="tpl.thumbnail"
                    :alt="sprintf(__('%s preview'), tpl.name)"
                    loading="lazy"
                  >
                </div>
                <div class="design-tmpl__row">
                  <span class="design-tmpl__name">{{ tpl.name }}</span>
                  <Badge
                    v-if="tpl.pro"
                    variant="pro"
                    size="sm"
                  >
                    {{ __('Pro') }}
                  </Badge>
                </div>
              </button>
            </div>
          </div>
        </section>

        <!-- Colors -->
        <section class="design-rail__section design-section">
          <header class="design-rail__head">
            <span class="design-rail__title">{{ __('Colors') }}</span>
            <button
              type="button"
              class="design-rail__link"
              @click="resetColors"
            >
              {{ __('Reset') }}
            </button>
          </header>
          <div class="design-rail__body">
            <div class="design-colors">
              <label
                v-for="c in colorFields"
                :key="c.key"
                class="design-color"
              >
                <span class="design-color__chip-wrap">
                  <input
                    type="color"
                    class="design-color__chip"
                    :value="colors[c.key] || c.default"
                    @input="(e) => onColorChange(c.key, e.target.value)"
                  >
                </span>
                <span class="design-color__meta">
                  <span class="design-color__label">{{ c.label }}</span>
                  <span class="design-color__hex">
                    {{ (colors[c.key] || c.default).toUpperCase() }}
                  </span>
                </span>
              </label>
            </div>
            <div
              v-if="colorFields.some(c => c.key === 'background')"
              class="design-field design-field--bg-opacity"
            >
              <p class="design-field__label">
                {{ __('Background opacity') }}
                <span class="design-field__value">{{ backgroundOpacity }}%</span>
              </p>
              <input
                type="range"
                class="design-slider"
                min="0"
                max="100"
                step="1"
                :aria-label="__('Background opacity')"
                :value="backgroundOpacity"
                @input="onBgOpacityInput"
              >
              <div class="design-slider-ticks">
                <span>0%</span>
                <span>50%</span>
                <span>100%</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Typography -->
        <section class="design-rail__section design-section">
          <header class="design-rail__head">
            <span class="design-rail__title">{{ __('Typography') }}</span>
          </header>
          <div class="design-rail__body">
            <Select
              :model-value="design.font_family || 'default'"
              :options="fontOptions"
              @update:model-value="onFontChange"
            />

            <p class="design-field__label" style="margin: 6px 0 4px;">
              {{ __('Text size') }} <span class="design-field__value">{{ fontSizePct }}%</span>
            </p>
            <input
              type="range"
              class="design-slider"
              min="70"
              max="150"
              step="5"
              :value="fontSizePct"
              :aria-label="__('Text size')"
              @input="onFontSizeInput"
            >
            <div class="design-slider-ticks">
              <span>70%</span><span>100%</span><span>150%</span>
            </div>

            <p
              v-if="promoVisible"
              class="design-hint"
            >
              <Badge variant="pro" size="sm">{{ __('Pro') }}</Badge>
              {{ __('Google Fonts available with the Pro upgrade.') }}
            </p>
          </div>
        </section>

        <!-- Button style -->
        <section class="design-rail__section design-section">
          <header class="design-rail__head">
            <span class="design-rail__title">{{ __('Button style') }}</span>
          </header>
          <div class="design-rail__body">
            <div
              class="design-seg"
              role="radiogroup"
              :aria-label="__('Button style')"
            >
              <button
                v-for="opt in buttonStyles"
                :key="opt.value"
                type="button"
                role="radio"
                :aria-checked="buttonStyle === opt.value"
                :class="['design-seg__opt', { 'is-active': buttonStyle === opt.value }]"
                @click="onButtonStyleChange(opt.value)"
              >
                {{ opt.label }}
              </button>
            </div>
          </div>
        </section>

        <!-- Splitscreen-specific controls -->
        <section
          v-if="activeTemplateSlug === 'splitscreen'"
          class="design-rail__section"
        >
          <header class="design-rail__head">
            <span class="design-rail__title">{{ __('Layout') }}</span>
          </header>
          <div class="design-rail__body">

            <!-- Image position -->
            <p class="design-field__label">{{ __('Image position') }}</p>
            <div class="design-seg" role="radiogroup" :aria-label="__('Image side')">
              <button
                type="button" role="radio"
                :aria-checked="splitLayout === 'image-left'"
                :class="['design-seg__opt', { 'is-active': splitLayout === 'image-left' }]"
                @click="onSplitLayoutChange('image-left')"
              >{{ __('Left') }}</button>
              <button
                type="button" role="radio"
                :aria-checked="splitLayout === 'image-right'"
                :class="['design-seg__opt', { 'is-active': splitLayout === 'image-right' }]"
                @click="onSplitLayoutChange('image-right')"
              >{{ __('Right') }}</button>
            </div>

            <!-- Image panel width -->
            <p class="design-field__label" style="margin-top:14px;">
              {{ __('Image width') }} <span class="design-field__value">{{ splitImageWidth }}%</span>
            </p>
            <input
              type="range" class="design-slider"
              min="10" max="70" step="1" :value="splitImageWidth"
              :aria-label="__('Image panel width')"
              @input="onSplitImageWidthInput"
            >
            <div class="design-slider-ticks"><span>10%</span><span>40%</span><span>70%</span></div>

            <!-- Content position -->
            <p class="design-field__label" style="margin-top:14px;">{{ __('Content position') }}</p>
            <div class="design-seg" role="radiogroup" :aria-label="__('Content align')">
              <button
                type="button" role="radio"
                :aria-checked="splitContentAlign === 'top'"
                :class="['design-seg__opt', { 'is-active': splitContentAlign === 'top' }]"
                @click="onSplitContentAlignChange('top')"
              >{{ __('Top') }}</button>
              <button
                type="button" role="radio"
                :aria-checked="splitContentAlign === 'center'"
                :class="['design-seg__opt', { 'is-active': splitContentAlign === 'center' }]"
                @click="onSplitContentAlignChange('center')"
              >{{ __('Center') }}</button>
              <button
                type="button" role="radio"
                :aria-checked="splitContentAlign === 'bottom'"
                :class="['design-seg__opt', { 'is-active': splitContentAlign === 'bottom' }]"
                @click="onSplitContentAlignChange('bottom')"
              >{{ __('Bottom') }}</button>
            </div>

            <!-- Panel size -->
            <div class="design-size-head" style="margin-top:14px;">
              <p class="design-field__label" style="margin:0;">{{ __('Panel size') }}</p>
              <button
                type="button"
                class="design-fullscreen-btn"
                :title="__('Set 100vw × 100vh')"
                @click="onSplitFullScreen"
              >{{ __('Full screen') }}</button>
            </div>

            <!-- Width -->
            <p class="design-field__label" style="margin-top:10px;">
              {{ __('Width') }} <span class="design-field__value">{{ splitWidthValue }}{{ splitWidthUnit }}</span>
            </p>
            <div class="design-slider-row">
              <input
                type="range" class="design-slider"
                :min="widthSliderMin" :max="widthSliderMax" :step="widthSliderStep"
                :value="splitWidthValue"
                :aria-label="__('Max width')"
                @input="onSplitWidthValueInput"
              >
              <div class="design-unit-seg" role="radiogroup" :aria-label="__('Max width unit')">
                <button
                  v-for="u in ['vw','%','px']" :key="u"
                  type="button" role="radio"
                  :aria-checked="splitWidthUnit === u"
                  :class="['design-unit-btn', { 'is-active': splitWidthUnit === u }]"
                  @click="onSplitWidthUnitChange(u)"
                >{{ u }}</button>
              </div>
            </div>

            <!-- Height -->
            <p class="design-field__label" style="margin-top:10px;">
              {{ __('Height') }} <span class="design-field__value">{{ splitHeightValue }}{{ splitHeightUnit }}</span>
            </p>
            <div class="design-slider-row">
              <input
                type="range" class="design-slider"
                :min="heightSliderMin" :max="heightSliderMax" :step="heightSliderStep"
                :value="splitHeightValue"
                :aria-label="__('Height')"
                @input="onSplitHeightValueInput"
              >
              <div class="design-unit-seg" role="radiogroup" :aria-label="__('Height unit')">
                <button
                  v-for="u in ['vh','px']" :key="u"
                  type="button" role="radio"
                  :aria-checked="splitHeightUnit === u"
                  :class="['design-unit-btn', { 'is-active': splitHeightUnit === u }]"
                  @click="onSplitHeightUnitChange(u)"
                >{{ u }}</button>
              </div>
            </div>

          </div>
        </section>

        <!-- Animation -->
        <section class="design-rail__section design-section">
          <header class="design-rail__head">
            <span class="design-rail__title">{{ __('Animation') }}</span>
          </header>
          <div class="design-rail__body">
            <Select
              :model-value="design.animation || 'fade'"
              :options="animationOptions"
              @update:model-value="onAnimationChange"
            />
          </div>
        </section>

        <!-- Background — label and hints adapt per template layout -->
        <section class="design-rail__section design-section">
          <header class="design-rail__head">
            <span class="design-rail__title">{{ templateConfig.bgTitle }}</span>
          </header>
          <div class="design-rail__body">
            <p
              v-if="templateConfig.bgHint"
              class="design-hint design-hint--scope"
            >
              {{ templateConfig.bgHint }}
            </p>
            <MediaPicker
              :model-value="design.background_image || ''"
              label=""
              helper-text=""
              :button-text="__('Upload background')"
              :replace-text="__('Replace background')"
              :remove-text="__('Remove background')"
              @update:model-value="onBackgroundImageInput"
            />

            <!-- Overlay only makes sense when a background image is set -->
            <div v-if="hasBackgroundImage" class="design-overlay">
              <div class="design-overlay__row">
                <span class="design-overlay__chip-wrap">
                  <input
                    type="color"
                    class="design-overlay__chip"
                    :value="overlayColor"
                    :aria-label="__('Overlay color')"
                    @input="(e) => onOverlayColorChange(e.target.value)"
                  >
                </span>
                <div class="design-overlay__slider">
                  <label class="design-overlay__label">
                    {{ __('Overlay') }}
                    <span class="design-overlay__pct">{{ overlayOpacity }}%</span>
                  </label>
                  <input
                    type="range"
                    min="0"
                    max="100"
                    step="1"
                    class="design-overlay__range"
                    :value="overlayOpacity"
                    :aria-label="__('Overlay opacity')"
                    @input="(e) => onOverlayOpacityChange(Number(e.target.value))"
                  >
                </div>
              </div>
              <p class="design-hint">
                {{ __('Tint the background to make text more readable. Set opacity to 0% to disable.') }}
              </p>
            </div>

            <p
              v-if="hasBackgroundImage && templateConfig.overlayNote"
              class="design-hint design-hint--scope"
            >
              {{ templateConfig.overlayNote }}
            </p>

            <p
              v-if="promoVisible"
              class="design-hint"
            >
              <Badge variant="pro" size="sm">{{ __('Pro') }}</Badge>
              {{ __('Looping video backgrounds available with Pro.') }}
            </p>
          </div>
        </section>

        <!-- Advanced — Custom CSS is a Pro-tier control: the section is absent
             unless Pro is active (editable) or promoted (locked preview). -->
        <section
          v-if="proTierVisible"
          class="design-rail__section design-section"
        >
          <header class="design-rail__head">
            <span class="design-rail__title">{{ __('Advanced') }}</span>
          </header>
          <div class="design-rail__body">
            <div :class="['design-row is-stack', { 'is-pro': !isProUser }]">
              <div class="design-row__label">
                <span>{{ __('Custom CSS') }}</span>
                <Badge v-if="!isProUser" variant="pro" size="sm">{{ __('Pro') }}</Badge>
              </div>
              <textarea
                v-if="isProUser"
                class="design-css"
                rows="4"
                placeholder=".quizably-quiz { --brand: …; }"
                :value="customCss"
                data-testid="design-custom-css"
                @input="onCustomCssInput"
              />
              <textarea
                v-else
                class="design-css"
                rows="4"
                placeholder=".quizably-quiz { --brand: …; }"
                disabled
              />
            </div>
          </div>
        </section>
      </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Badge, MediaPicker, Select, Toggle, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { useTemplatesStore } from '@admin/stores/templates';
import { __, sprintf } from '@shared/i18n';
import { isPro, proFeaturesVisible, showProPromo } from '@admin/api/pro.js';

// No auto-save — all changes accumulate in pending patches and are only
// persisted when the user clicks Save (quizably:flush-pending-saves event).

// Per-template metadata that drives the Background section labels and hints.
// Templates where the background image covers only PART of the layout need
// labels that set the right expectation for the author.
// overlayDefaultColor is the COLOR that becomes active when the user drags
// the opacity slider up from 0. Dark (#000000) for full-bleed / image-heavy
// templates (darkens bright images so white text stays readable). Light
// (#FFFFFF) for card-based templates (softens images behind a white card).
// overlayDefaultOpacity is 0 for every template — no tint until the author
// explicitly asks for one.
const TEMPLATE_CONFIGS = {
  classic: {
    bgTitle: __('Background'),
    bgHint: null,
    overlayNote: null,
    overlayDefaultOpacity: 0,
    overlayDefaultColor: '#FFFFFF',
  },
  minimal: {
    bgTitle: __('Background'),
    bgHint: null,
    overlayNote: null,
    overlayDefaultOpacity: 0,
    overlayDefaultColor: '#FFFFFF',
  },
  fullscreen: {
    bgTitle: __('Full-bleed background'),
    bgHint: null,
    overlayNote: __('Use a dark overlay color so white text stays readable over bright images. A light overlay will wash out the text.'),
    overlayDefaultOpacity: 0,
    overlayDefaultColor: '#000000',
  },
  splitscreen: {
    bgTitle: __('Panel image'),
    bgHint: __('Fills the image panel on the left side. Your Background color fills the right content panel.'),
    overlayNote: __('Overlay tints the image panel only — the content panel is unaffected.'),
    overlayDefaultOpacity: 0,
    overlayDefaultColor: '#000000',
  },
  cardstack: {
    bgTitle: __('Background'),
    bgHint: null,
    overlayNote: null,
    overlayDefaultOpacity: 0,
    overlayDefaultColor: '#FFFFFF',
  },
  conversational: {
    bgTitle: __('Background'),
    bgHint: null,
    overlayNote: null,
    overlayDefaultOpacity: 0,
    overlayDefaultColor: '#FFFFFF',
  },
  gamified: {
    bgTitle: __('Background image'),
    bgHint: __('Fills the quiz stage area behind the questions. The header bar always shows the brand colours.'),
    overlayNote: __('Overlay tints the stage area only.'),
    overlayDefaultOpacity: 0,
    overlayDefaultColor: '#000000',
  },
  magazine: {
    bgTitle: __('Background image'),
    bgHint: __('Fills the article area behind the quiz content. The masthead always shows in the text colour.'),
    overlayNote: __('Overlay tints the article area only.'),
    overlayDefaultOpacity: 0,
    overlayDefaultColor: '#000000',
  },
};

const DEFAULT_TEMPLATE_CONFIG = {
  bgTitle: __('Background'),
  bgHint: null,
  overlayNote: null,
  overlayDefaultOpacity: 0,
  overlayDefaultColor: '#FFFFFF',
};

const DEFAULT_COLORS = {
  primary: '#4F46E5',
  background: '#FFFFFF',
  text: '#0A0A0B',
  accent: '#F59E0B',
};

// Full set — used for templates that render all four color slots.
const ALL_COLOR_FIELDS = [
  { key: 'primary', label: __('Brand'), default: DEFAULT_COLORS.primary },
  { key: 'background', label: __('Background'), default: DEFAULT_COLORS.background },
  { key: 'text', label: __('Text'), default: DEFAULT_COLORS.text },
  { key: 'accent', label: __('Accent'), default: DEFAULT_COLORS.accent },
];

// Reduced sets for templates that don't render every slot.
// Labels may be renamed when the CSS slot has a different visual role
// (e.g. Fullscreen's --p-text drives a gradient base, not text color).
const TEMPLATE_COLOR_FIELDS = {
  fullscreen: [
    { key: 'primary',    label: __('Brand'),       default: DEFAULT_COLORS.primary },
    { key: 'background', label: __('Background'),  default: DEFAULT_COLORS.background },
    { key: 'text',       label: __('Dark tone'),   default: DEFAULT_COLORS.text },
    // accent slot repurposed as content text/border color; white is the
    // natural default for a dark full-bleed card.
    { key: 'accent',     label: __('Text'),        default: '#FFFFFF' },
  ],
  minimal: [
    { key: 'primary', label: __('Brand'), default: DEFAULT_COLORS.primary },
    { key: 'background', label: __('Background'), default: DEFAULT_COLORS.background },
    { key: 'text', label: __('Text'), default: DEFAULT_COLORS.text },
    { key: 'accent', label: __('Accent'), default: DEFAULT_COLORS.accent },
  ],
  conversational: [
    { key: 'primary', label: __('Brand'), default: DEFAULT_COLORS.primary },
    { key: 'background', label: __('Background'), default: DEFAULT_COLORS.background },
    { key: 'text', label: __('Text'), default: DEFAULT_COLORS.text },
    { key: 'accent', label: __('Accent'), default: DEFAULT_COLORS.accent },
  ],
};

const store = useQuizBuilderStore();
const templatesStore = useTemplatesStore();
const toast = useToast();

const applyingTemplate = ref(false);
const loadingTemplates = ref(false);

const design = computed(() => store.quiz?.design ?? {});
const colors = computed(() => design.value.colors ?? {});
const buttonStyle = computed(() => design.value.button_style || 'rounded');
const overlayColor = computed(
  () => design.value.overlay_color || templateConfig.value.overlayDefaultColor,
);
const overlayOpacity = computed(() => {
  const v = design.value.overlay_opacity;
  // Fall back to the template's own default (never 88 globally).
  if (Number.isFinite(v)) return Math.max(0, Math.min(100, v));
  return templateConfig.value.overlayDefaultOpacity ?? 0;
});

const backgroundOpacity = computed(() => {
  const v = design.value.background_opacity;
  const n = Number(v);
  return Number.isFinite(n) ? Math.max(0, Math.min(100, Math.round(n))) : 100;
});

const colorFields = computed(
  () => TEMPLATE_COLOR_FIELDS[activeTemplateSlug.value] ?? ALL_COLOR_FIELDS,
);

const fontSizePct = computed(() => Math.min(150, Math.max(70, Number(design.value.font_size) || 100)));

// ── Layout section ───────────────────────────────────────────────────────────
const layoutMode       = computed(() => design.value.layout_mode || 'card');
const cardMaxWidth     = computed(() => Math.max(0, Number(design.value.card_max_width) || 640));
const cardMaxWidthUnit = computed(() => design.value.card_max_width_unit || 'px');
const cardMinHeight    = computed(() => Math.max(0, Number(design.value.card_min_height) || 0));
const cardMinHeightUnit= computed(() => design.value.card_min_height_unit || 'px');
const innerMaxWidth    = computed(() => Math.max(0, Number(design.value.inner_max_width) || 0));
const innerMaxWidthUnit= computed(() => design.value.inner_max_width_unit || 'px');

const cardWidthMin  = computed(() => cardMaxWidthUnit.value === 'px' ? 200  : 10);
const cardWidthMax  = computed(() => cardMaxWidthUnit.value === 'px' ? 1600 : 100);
const cardWidthStep = computed(() => cardMaxWidthUnit.value === 'px' ? 10   : 1);
const minHeightMax  = computed(() => cardMinHeightUnit.value === 'px' ? 1200 : 200);
const minHeightStep = computed(() => cardMinHeightUnit.value === 'px' ? 10   : 5);
const innerWidthMax = computed(() => innerMaxWidthUnit.value === 'px' ? 1600 : 100);
const innerWidthStep= computed(() => innerMaxWidthUnit.value === 'px' ? 10   : 1);

// show_border: true by default — the card always had a border; unchecking hides it
const showCardBorder = computed(() => design.value.show_border !== false);

function onLayoutModeChange(mode) {
  pendingMiscPatch = { ...pendingMiscPatch, layout_mode: mode };
  shadowDesignPatch({ layout_mode: mode });
}
function onShowCardBorderChange(checked) {
  pendingMiscPatch = { ...pendingMiscPatch, show_border: checked };
  shadowDesignPatch({ show_border: checked });
}
function onCardMaxWidthInput(e) {
  const v = Math.max(cardWidthMin.value, Math.min(cardWidthMax.value, Number(e.target.value) || cardWidthMin.value));
  pendingMiscPatch = { ...pendingMiscPatch, card_max_width: v };
  shadowDesignPatch({ card_max_width: v });
}
function onCardMaxWidthUnitChange(unit) {
  let v = cardMaxWidth.value;
  if (unit === 'px' && cardMaxWidthUnit.value === '%') v = Math.round(v * 16);
  if (unit === '%' && cardMaxWidthUnit.value === 'px') v = Math.round(v / 16);
  v = Math.max(unit === 'px' ? 200 : 10, Math.min(unit === 'px' ? 1600 : 100, v));
  pendingMiscPatch = { ...pendingMiscPatch, card_max_width_unit: unit, card_max_width: v };
  shadowDesignPatch({ card_max_width_unit: unit, card_max_width: v });
}
function onCardMinHeightInput(e) {
  const v = Math.max(0, Math.min(minHeightMax.value, Number(e.target.value) || 0));
  pendingMiscPatch = { ...pendingMiscPatch, card_min_height: v };
  shadowDesignPatch({ card_min_height: v });
}
function onCardMinHeightUnitChange(unit) {
  let v = cardMinHeight.value;
  if (unit === 'px' && cardMinHeightUnit.value === 'vh') v = Math.min(1200, Math.max(0, Math.round(v * 9)));
  if (unit === 'vh' && cardMinHeightUnit.value === 'px') v = Math.min(200, Math.max(0, Math.round(v / 9)));
  pendingMiscPatch = { ...pendingMiscPatch, card_min_height_unit: unit, card_min_height: v };
  shadowDesignPatch({ card_min_height_unit: unit, card_min_height: v });
}
function onInnerMaxWidthInput(e) {
  const v = Math.max(0, Math.min(innerWidthMax.value, Number(e.target.value) || 0));
  pendingMiscPatch = { ...pendingMiscPatch, inner_max_width: v };
  shadowDesignPatch({ inner_max_width: v });
}
function onInnerMaxWidthUnitChange(unit) {
  let v = innerMaxWidth.value;
  if (unit === 'px' && innerMaxWidthUnit.value === '%') v = Math.round(v * 16);
  if (unit === '%' && innerMaxWidthUnit.value === 'px') v = Math.round(v / 16);
  v = Math.max(0, Math.min(unit === 'px' ? 1600 : 100, v));
  pendingMiscPatch = { ...pendingMiscPatch, inner_max_width_unit: unit, inner_max_width: v };
  shadowDesignPatch({ inner_max_width_unit: unit, inner_max_width: v });
}

const splitLayout       = computed(() => design.value.split_layout || 'image-left');
const splitImageWidth   = computed(() => Math.min(70, Math.max(10, Number(design.value.split_image_width) || 42)));
const splitContentAlign = computed(() => design.value.split_content_align || 'center');
const splitHeightValue  = computed(() => Number(design.value.split_height_value) || 100);
const splitHeightUnit   = computed(() => design.value.split_height_unit  || 'vh');
const splitWidthValue   = computed(() => Number(design.value.split_width_value)  || 100);
const splitWidthUnit    = computed(() => design.value.split_width_unit   || 'vw');

const heightSliderMin  = computed(() => splitHeightUnit.value === 'px' ? 200  : 10);
const heightSliderMax  = computed(() => splitHeightUnit.value === 'px' ? 2000 : 200);
const heightSliderStep = computed(() => splitHeightUnit.value === 'px' ? 10   : 5);
const widthSliderMin   = computed(() => splitWidthUnit.value  === 'px' ? 300  : 10);
const widthSliderMax   = computed(() => splitWidthUnit.value  === 'px' ? 2000 : 100);
const widthSliderStep  = computed(() => splitWidthUnit.value  === 'px' ? 10   : 5);

const fontOptions = [
  { value: 'default', label: __('Default') },
  { value: 'geist', label: 'Geist' },
  { value: 'inter', label: 'Inter' },
  { value: 'system', label: __('System Sans-Serif') },
  { value: 'georgia', label: 'Georgia' },
  { value: 'courier', label: 'Courier' },
];

const buttonStyles = [
  { value: 'rounded', label: __('Rounded') },
  { value: 'pill', label: __('Pill') },
  { value: 'sharp', label: __('Sharp') },
];

// The Zoom transition is Pro-tier: listed for Pro users and while Pro is
// promoted, absent from the dropdown otherwise (never a hidden selection —
// the saved/default value is 'fade').
const ALL_ANIMATION_OPTIONS = [
  { value: 'fade', label: __('Fade') },
  { value: 'slide', label: __('Slide') },
  { value: 'zoom', label: __('Zoom (Pro)'), pro: true },
  { value: 'none', label: __('None') },
];
const animationOptions = computed(() =>
  ALL_ANIMATION_OPTIONS.filter((o) => !o.pro || proTierVisible.value)
);

const activeTemplate = computed(() => {
  const key = store.quiz?.template;
  if (!key) return null;
  return (
    templatesStore.items.find(
      (t) => t.slug === key || String(t.id) === String(key) || t.id === key
    ) || null
  );
});

// Slug used as a CSS modifier so the preview can adapt per template.
// Falls back to store.quiz.template when templatesStore.items hasn't loaded
// yet — avoids a race condition where isCardTemplate evaluates to false while
// the templates store is still fetching, hiding the card border toggle.
const activeTemplateSlug = computed(() => activeTemplate.value?.slug || store.quiz?.template || 'default');

const CARD_TEMPLATES = new Set(['classic', 'cardstack']);
const isCardTemplate = computed(() => CARD_TEMPLATES.has(activeTemplateSlug.value));
const isFullscreen   = computed(() => activeTemplateSlug.value === 'fullscreen');
const isSplitscreen  = computed(() => activeTemplateSlug.value === 'splitscreen');
const hasBackgroundImage = computed(() => !!design.value.background_image);

const templateConfig = computed(() => TEMPLATE_CONFIGS[activeTemplateSlug.value] ?? DEFAULT_TEMPLATE_CONFIG);

function templateThumbStyle(tpl) {
  if (!tpl) return {};
  if (tpl.thumbnail) return {};
  const palettes = [
    ['#6366F1', '#EC4899'],
    ['#1E1B4B', '#4338CA'],
    ['#F87171', '#FBBF24'],
    ['#7C3AED', '#EC4899'],
    ['#0EA5E9', '#22D3EE'],
    ['#10B981', '#34D399'],
    ['#F59E0B', '#F97316'],
    ['#EF4444', '#F472B6'],
  ];
  const key = String(tpl.id || tpl.slug || tpl.name || '').length;
  const pair = palettes[key % palettes.length];
  return { background: `linear-gradient(135deg, ${pair[0]}, ${pair[1]})` };
}

function isActiveTemplate(tpl) {
  const cur = store.quiz?.template;
  if (!cur) return false;
  return tpl.slug === cur || String(tpl.id) === String(cur) || tpl.id === cur;
}

// Pending patches — accumulated on every change, flushed only on explicit Save.
let pendingColorPatch = {};
let pendingOverlayPatch = {};
let pendingMiscPatch = {};
let pendingBg = null; // null = nothing pending; '' = cleared; string = URL

function shadowDesignPatch(patch) {
  if (!store.quiz) return;
  store.quiz.design = { ...(store.quiz.design || {}), ...patch };
}

function onColorChange(key, value) {
  pendingColorPatch = { ...pendingColorPatch, [key]: value };
  if (store.quiz) {
    const d = store.quiz.design || {};
    store.quiz.design = {
      ...d,
      colors: { ...(d.colors || {}), [key]: value },
    };
  }
}

async function resetColors() {
  pendingColorPatch = {};
  try {
    await store.updateDesign({ colors: { ...DEFAULT_COLORS } });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not reset colors'),
      message: e.message,
    });
  }
}

function onBgOpacityInput(e) {
  const raw = Number(e.target.value);
  const v = Math.round(Math.max(0, Math.min(100, Number.isFinite(raw) ? raw : 100)));
  pendingMiscPatch = { ...pendingMiscPatch, background_opacity: v };
  shadowDesignPatch({ background_opacity: v });
}

function onFontChange(next) {
  pendingMiscPatch = { ...pendingMiscPatch, font_family: next };
  shadowDesignPatch({ font_family: next });
}

function onFontSizeInput(e) {
  const val = Math.min(150, Math.max(70, Number(e.target.value) || 100));
  pendingMiscPatch = { ...pendingMiscPatch, font_size: val };
  shadowDesignPatch({ font_size: val });
}

function onButtonStyleChange(next) {
  pendingMiscPatch = { ...pendingMiscPatch, button_style: next };
  shadowDesignPatch({ button_style: next });
}

function onSplitLayoutChange(next) {
  pendingMiscPatch = { ...pendingMiscPatch, split_layout: next };
  shadowDesignPatch({ split_layout: next });
}

function onSplitContentAlignChange(next) {
  pendingMiscPatch = { ...pendingMiscPatch, split_content_align: next };
  shadowDesignPatch({ split_content_align: next });
}

function onSplitImageWidthInput(e) {
  const val = Math.min(70, Math.max(10, Number(e.target.value) || 42));
  pendingMiscPatch = { ...pendingMiscPatch, split_image_width: val };
  shadowDesignPatch({ split_image_width: val });
}

function onSplitHeightValueInput(e) {
  const val = Math.min(heightSliderMax.value, Math.max(heightSliderMin.value, Number(e.target.value) || heightSliderMin.value));
  pendingMiscPatch = { ...pendingMiscPatch, split_height_value: val };
  shadowDesignPatch({ split_height_value: val });
}

function onSplitHeightUnitChange(unit) {
  let val = splitHeightValue.value;
  if (unit === 'px' && splitHeightUnit.value === 'vh') val = Math.min(2000, Math.max(200, Math.round(val * 9)));
  if (unit === 'vh' && splitHeightUnit.value === 'px') val = Math.min(200, Math.max(10, Math.round(val / 9)));
  pendingMiscPatch = { ...pendingMiscPatch, split_height_unit: unit, split_height_value: val };
  shadowDesignPatch({ split_height_unit: unit, split_height_value: val });
}

function onSplitWidthValueInput(e) {
  const val = Math.min(widthSliderMax.value, Math.max(widthSliderMin.value, Number(e.target.value) || widthSliderMin.value));
  pendingMiscPatch = { ...pendingMiscPatch, split_width_value: val };
  shadowDesignPatch({ split_width_value: val });
}

function onSplitWidthUnitChange(unit) {
  let val = splitWidthValue.value;
  if (unit === 'px') val = Math.min(2000, Math.max(300, Math.round(splitWidthValue.value * 14)));
  if (unit !== 'px') val = Math.min(100, Math.max(10, Math.round(splitWidthValue.value / 14)));
  if (splitWidthUnit.value !== 'px' && unit !== 'px') val = splitWidthValue.value; // vw ↔ % keep value
  pendingMiscPatch = { ...pendingMiscPatch, split_width_unit: unit, split_width_value: val };
  shadowDesignPatch({ split_width_unit: unit, split_width_value: val });
}

function onSplitFullScreen() {
  const patch = { split_height_value: 100, split_height_unit: 'vh', split_width_value: 100, split_width_unit: 'vw' };
  pendingMiscPatch = { ...pendingMiscPatch, ...patch };
  shadowDesignPatch(patch);
}

function onAnimationChange(next) {
  if (next === 'zoom') {
    toast.push({
      variant: 'info',
      title: __('Pro animation'),
      message: __('Zoom transitions require Quizably Pro.'),
    });
    return;
  }
  pendingMiscPatch = { ...pendingMiscPatch, animation: next };
  shadowDesignPatch({ animation: next });
}

function onOverlayColorChange(value) {
  pendingOverlayPatch = { ...pendingOverlayPatch, overlay_color: value };
  shadowDesignPatch({ overlay_color: value });
}

function onOverlayOpacityChange(value) {
  const v = Math.max(0, Math.min(100, Number(value) || 0));
  pendingOverlayPatch = { ...pendingOverlayPatch, overlay_opacity: v };
  shadowDesignPatch({ overlay_opacity: v });
}

function onBackgroundImageInput(value) {
  pendingBg = value ?? '';
  shadowDesignPatch({ background_image: pendingBg });
}


function onCustomCssInput(event) {
  const value = String(event?.target?.value ?? '');
  pendingMiscPatch = { ...pendingMiscPatch, custom_css: value };
  shadowDesignPatch({ custom_css: value });
}

const isProUser = computed(() => isPro());
// Pro-tier CONTROLS (Pro templates, Zoom, Custom CSS) are listed for Pro users
// and, as locked previews, while Pro promotion is on. Pure TEASERS (the
// "available with Pro" hints) need promotion on and Pro not active.
const proTierVisible = computed(() => proFeaturesVisible());
const promoVisible = computed(() => showProPromo());

// Pro templates are absent from the picker unless Pro is active or promoted.
const visibleTemplates = computed(() =>
  templatesStore.items.filter((t) => !t.pro || proTierVisible.value)
);

function proToast(label) {
  toast.push({
    variant: 'info',
    title: __('Pro feature'),
    // translators: %s: name of the Pro feature
    message: sprintf(__('%s requires Quizably Pro.'), label),
  });
}

const customCss = computed(() => String(store.quiz?.design?.custom_css ?? ''));

async function onPickTemplate(tpl) {
  if (tpl.pro && !isProUser.value) {
    toast.push({
      variant: 'info',
      title: __('Pro template'),
      // translators: %s: name of the Pro template
      message: sprintf(__('%s requires Quizably Pro.'), tpl.name),
    });
    return;
  }
  if (isActiveTemplate(tpl) || applyingTemplate.value) return;
  applyingTemplate.value = true;
  try {
    const slug = tpl.slug || String(tpl.id);
    await store.updateTemplate(slug);

    // Apply overlay defaults for the new template immediately (system action,
    // not user input — persists right away and clears any user-pending overlay).
    const cfg = TEMPLATE_CONFIGS[slug] ?? DEFAULT_TEMPLATE_CONFIG;
    pendingOverlayPatch = {};
    shadowDesignPatch({
      overlay_opacity: cfg.overlayDefaultOpacity,
      overlay_color: cfg.overlayDefaultColor,
    });
    await store.updateDesign({
      overlay_opacity: cfg.overlayDefaultOpacity,
      overlay_color: cfg.overlayDefaultColor,
    });

    toast.push({
      variant: 'success',
      title: __('Template applied'),
      // translators: %s: template name
      message: sprintf(__('%s is now active.'), tpl.name),
    });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not apply template'),
      message: e.message || __('Please try again.'),
    });
  } finally {
    applyingTemplate.value = false;
  }
}

async function loadTemplates() {
  if (templatesStore.loaded) return;
  loadingTemplates.value = true;
  try {
    await templatesStore.fetch();
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not load templates'),
      message: e.message,
    });
  } finally {
    loadingTemplates.value = false;
  }
}

async function flushAllPending() {
  const merged = {};

  if (Object.keys(pendingColorPatch).length) {
    merged.colors = { ...(colors.value || {}), ...pendingColorPatch };
    pendingColorPatch = {};
  }
  if (pendingBg !== null) {
    merged.background_image = pendingBg;
    pendingBg = null;
  }
  if (Object.keys(pendingOverlayPatch).length) {
    Object.assign(merged, pendingOverlayPatch);
    pendingOverlayPatch = {};
  }
  if (Object.keys(pendingMiscPatch).length) {
    Object.assign(merged, pendingMiscPatch);
    pendingMiscPatch = {};
  }

  if (!Object.keys(merged).length) return;

  try {
    await store.updateDesign(merged);
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save design'),
      message: e?.message ?? __('Please try again.'),
    });
  }
}

onMounted(() => {
  loadTemplates();
  if (typeof window !== 'undefined') {
    window.addEventListener('quizably:flush-pending-saves', flushAllPending);
  }
});

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('quizably:flush-pending-saves', flushAllPending);
  }
});

defineExpose({ flush: flushAllPending });
</script>

<style scoped>
/* Standalone customizer rail — fills the drawer panel */
.design-rail {
  background: var(--bg-surface);
  overflow: hidden;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.design-rail__scroll {
  height: 100%;
  overflow-y: auto;
  padding-bottom: 32px;
}

.design-rail__section + .design-rail__section {
  border-top: 1px solid var(--border-1);
}

.design-rail__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 16px 6px;
  gap: 8px;
}

.design-rail__title {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
}

.design-rail__sub {
  font-size: 11.5px;
  color: var(--ink-3);
  font-family: var(--f-mono);
}

.design-rail__link {
  background: transparent;
  border: 0;
  padding: 0;
  cursor: pointer;
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--ink-3);
}

.design-rail__link:hover {
  color: var(--brand);
}

.design-rail__body {
  padding: 4px 16px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Template strip — compact 2-col grid of small thumbs */
.design-tmpl-loading {
  font-size: 12px;
  color: var(--ink-3);
  padding: 10px 0;
}

.design-tmpl-strip {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.design-tmpl {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 6px;
  background: transparent;
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  cursor: pointer;
  text-align: start;
  font-family: inherit;
  transition: border-color 150ms, background 150ms, box-shadow 150ms;
}

.design-tmpl:hover:not(:disabled):not(.is-active) {
  border-color: var(--border-3);
  background: var(--bg-canvas);
}

.design-tmpl.is-active {
  border-color: var(--brand);
  background: var(--brand-tint);
  box-shadow: inset 0 0 0 1px var(--brand);
}

.design-tmpl:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.design-tmpl__thumb {
  aspect-ratio: 4 / 3;
  border-radius: var(--r-xs);
  overflow: hidden;
  background: var(--bg-subtle);
  border: 1px solid var(--border-1);
}

.design-tmpl__thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  background: var(--bg-surface);
}

.design-tmpl__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 4px;
  min-width: 0;
}

.design-tmpl__name {
  font-size: 11.5px;
  font-weight: 600;
  color: var(--ink-1);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Color swatches — 2x2 grid of compact rows. */
.design-colors {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.design-color {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: border-color 150ms ease;
}

.design-color:hover {
  border-color: var(--border-3);
}

.design-color__chip-wrap {
  display: inline-grid;
  place-items: center;
  width: 26px;
  height: 26px;
  border-radius: var(--r-xs);
  border: 1px solid var(--border-2);
  overflow: hidden;
  flex-shrink: 0;
}

.design-color__chip {
  -webkit-appearance: none;
  appearance: none;
  border: 0;
  padding: 0;
  width: 28px;
  height: 28px;
  border-radius: var(--r-xs);
  cursor: pointer;
  background: transparent;
}

.design-color__chip::-webkit-color-swatch-wrapper {
  padding: 0;
}

.design-color__chip::-webkit-color-swatch {
  border: 0;
  border-radius: var(--r-xs);
}

.design-color__meta {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.design-color__label {
  font-size: 11.5px;
  color: var(--ink-2);
  font-weight: 500;
}

.design-color__hex {
  font-family: var(--f-mono);
  font-size: 10px;
  color: var(--ink-3);
  letter-spacing: 0.02em;
}

/* Segmented control */
.design-seg {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}
/* Two-option variant (e.g. Card / Full Width) */
.design-seg--2 {
  grid-template-columns: repeat(2, 1fr);
}

.design-seg__opt {
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

.design-seg__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.design-seg__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.design-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 8px 10px;
  background: var(--bg-canvas);
  border-radius: var(--r-sm);
  font-size: 13px;
  color: var(--ink-2);
}

.design-field__label {
  display: block;
  font-size: 11.5px;
  font-weight: 500;
  color: var(--ink-3);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin: 0 0 6px;
}

.design-height-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.design-height-input {
  flex: 1;
  min-width: 0;
  padding: 6px 8px;
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  background: var(--bg-surface);
  font-family: var(--f-mono);
  font-size: 13px;
  color: var(--ink-1);
  outline: none;
  transition: border-color 150ms;
}

.design-height-input:focus {
  border-color: var(--quizably-brand);
}

.design-height-unit {
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-3);
  white-space: nowrap;
}

.design-field__value {
  margin-inline-start: 4px;
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--quizably-brand, #4f46e5);
  font-weight: 600;
  letter-spacing: 0;
  text-transform: none;
}

.design-slider {
  width: 100%;
  accent-color: var(--quizably-brand, #4f46e5);
  cursor: pointer;
  margin: 2px 0;
  display: block;
}

.design-slider-ticks {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  color: var(--ink-4, var(--ink-3));
  margin-top: 1px;
}

.design-slider-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.design-slider-row .design-slider {
  flex: 1;
  min-width: 0;
}

.design-size-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.design-fullscreen-btn {
  font-size: 11px;
  font-weight: 600;
  font-family: inherit;
  color: var(--quizably-brand, #4f46e5);
  background: color-mix(in srgb, var(--quizably-brand, #4f46e5) 8%, transparent);
  border: 1px solid color-mix(in srgb, var(--quizably-brand, #4f46e5) 25%, transparent);
  border-radius: var(--r-sm);
  padding: 3px 8px;
  cursor: pointer;
  white-space: nowrap;
  transition: background 150ms;
}
.design-fullscreen-btn:hover {
  background: color-mix(in srgb, var(--quizably-brand, #4f46e5) 15%, transparent);
}

.design-unit-seg {
  display: flex;
  gap: 2px;
  flex-shrink: 0;
}

.design-unit-btn {
  padding: 4px 7px;
  border: 1px solid var(--border-1);
  border-radius: var(--r-xs);
  background: var(--bg-canvas);
  font-family: var(--f-mono);
  font-size: 10px;
  font-weight: 600;
  color: var(--ink-3);
  cursor: pointer;
  transition: background 120ms, color 120ms, border-color 120ms;
}
.design-unit-btn:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}
.design-unit-btn.is-active {
  background: var(--quizably-brand, #4f46e5);
  color: #fff;
  border-color: var(--quizably-brand, #4f46e5);
}

.design-row.is-pro {
  background: transparent;
  border: 1px dashed var(--border-2);
  color: var(--ink-3);
}

.design-row.is-stack {
  flex-direction: column;
  align-items: stretch;
  gap: 8px;
}

.design-row__label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.design-css {
  width: 100%;
  padding: 8px 10px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-xs);
  font-family: var(--f-mono);
  font-size: 11.5px;
  color: var(--ink-4);
  cursor: not-allowed;
  resize: vertical;
}

.design-hint {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 11.5px;
  color: var(--ink-3);
  margin: 0;
}

/* Scope hints explain WHICH part of a template the control applies to */
.design-hint--scope {
  display: block;
  padding: 7px 10px;
  background: color-mix(in srgb, var(--brand) 6%, transparent);
  border-inline-start: 2px solid var(--brand);
  border-start-start-radius: 0;
  border-start-end-radius: var(--r-xs);
  border-end-end-radius: var(--r-xs);
  border-end-start-radius: 0;
  color: var(--ink-2);
  line-height: 1.5;
}

.design-overlay {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
}

.design-overlay__row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.design-overlay__chip-wrap {
  display: inline-grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border-radius: var(--r-xs);
  border: 1px solid var(--border-2);
  overflow: hidden;
  flex-shrink: 0;
}

.design-overlay__chip {
  -webkit-appearance: none;
  appearance: none;
  border: 0;
  padding: 0;
  width: 32px;
  height: 32px;
  background: transparent;
  cursor: pointer;
}

.design-overlay__chip::-webkit-color-swatch-wrapper {
  padding: 0;
}

.design-overlay__chip::-webkit-color-swatch {
  border: 0;
  border-radius: var(--r-xs);
}

.design-overlay__slider {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}

.design-overlay__label {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  font-size: 11.5px;
  font-weight: 500;
  color: var(--ink-2);
}

.design-overlay__pct {
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-3);
  letter-spacing: 0.02em;
}

.design-overlay__range {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 4px;
  background: var(--border-2);
  border-radius: var(--r-pill);
  outline: none;
  cursor: pointer;
}

.design-overlay__range::-webkit-slider-thumb {
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

.design-overlay__range::-moz-range-thumb {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--brand);
  cursor: pointer;
  border: 2px solid #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.18);
}

.design-overlay__range:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

/* ── Toggle switch (show card border) ──────────────────────────────── */
.design-toggle {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  user-select: none;
}

.design-toggle__track {
  position: relative;
  width: 36px;
  height: 20px;
  flex-shrink: 0;
}

.design-toggle__input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.design-toggle__thumb {
  position: absolute;
  inset: 0;
  border-radius: 99px;
  background: var(--border-3);
  transition: background 150ms ease;
}

.design-toggle__thumb::after {
  content: '';
  position: absolute;
  top: 2px;
  inset-inline-start: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
  transition: transform 150ms ease;
}

.design-toggle__input:checked + .design-toggle__thumb {
  background: var(--brand);
}

.design-toggle__input:checked + .design-toggle__thumb::after {
  transform: translateX(16px);
}

.design-toggle__input:checked + .design-toggle__thumb:dir(rtl)::after {
  transform: translateX(-16px);
}

.design-toggle__input:focus-visible + .design-toggle__thumb {
  box-shadow: var(--shadow-focus);
}

.design-toggle__label {
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-2);
}

</style>
