<template>
  <div class="quizably-bg-ctrl">
    <div class="quizably-bg-ctrl__toggle-row">
      <div class="quizably-bg-ctrl__toggle-label">
        <span>{{ __('Custom background') }}</span>
        <Tooltip :label="tooltipLabel">
          <span class="quizably-help-dot" aria-hidden="true">?</span>
        </Tooltip>
      </div>
      <Toggle
        :model-value="enabled"
        size="sm"
        :data-testid="testid ? `${testid}-toggle` : undefined"
        @update:model-value="onEnabledChange"
      />
    </div>

    <div
      v-if="enabled"
      class="quizably-bg-ctrl__body"
    >
      <MediaPicker
        :model-value="modelValue.background_image || ''"
        label=""
        helper-text=""
        :button-text="__('Upload background')"
        :replace-text="__('Replace background')"
        :remove-text="__('Remove background')"
        @update:model-value="onImageChange"
      />

      <div class="quizably-bg-ctrl__overlay">
        <div class="quizably-bg-ctrl__overlay-row">
          <span class="quizably-bg-ctrl__chip-wrap">
            <input
              type="color"
              class="quizably-bg-ctrl__chip"
              :value="overlayColor"
              :aria-label="__('Overlay color')"
              :data-testid="testid ? `${testid}-color` : undefined"
              @input="(e) => onOverlayColorChange(e.target.value)"
            >
          </span>
          <div class="quizably-bg-ctrl__slider">
            <label class="quizably-bg-ctrl__slider-label">
              {{ __('Overlay') }}
              <span class="quizably-bg-ctrl__pct">{{ overlayOpacity }}%</span>
            </label>
            <input
              type="range"
              min="0"
              max="100"
              step="1"
              class="quizably-bg-ctrl__range"
              :value="overlayOpacity"
              :aria-label="__('Overlay opacity')"
              :data-testid="testid ? `${testid}-opacity` : undefined"
              @input="(e) => onOverlayOpacityChange(Number(e.target.value))"
            >
          </div>
        </div>
        <p class="quizably-bg-ctrl__hint">
          {{ __('Tint the background to make text more readable. Set opacity to 0% to disable.') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed } from 'vue';
import MediaPicker from './MediaPicker.vue';
import Toggle from './Toggle.vue';
import Tooltip from './Tooltip.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      enabled: false,
      background_image: '',
      overlay_color: '#000000',
      overlay_opacity: 0,
    }),
  },
  testid: { type: String, default: '' },
  tooltipLabel: {
    type: String,
    default: () => __('Override the quiz-level Design background just for this screen.'),
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const enabled = computed(() => !!props.modelValue.enabled);
const overlayColor = computed(() => props.modelValue.overlay_color || '#000000');
const overlayOpacity = computed(() => {
  const n = Number(props.modelValue.overlay_opacity);
  if (!Number.isFinite(n)) return 0;
  return Math.max(0, Math.min(100, Math.round(n)));
});

function emitPatch(key, value) {
  emit('change', key, value);
  emit('update:modelValue', { ...props.modelValue, [key]: value });
}

function onEnabledChange(next) {
  emitPatch('enabled', !!next);
}

function onImageChange(value) {
  emitPatch('background_image', value || '');
}

function onOverlayColorChange(value) {
  emitPatch('overlay_color', value || '#000000');
}

function onOverlayOpacityChange(value) {
  const v = Math.max(0, Math.min(100, Number(value) || 0));
  emitPatch('overlay_opacity', v);
}
</script>

<style scoped>
.quizably-bg-ctrl {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.quizably-bg-ctrl__toggle-row {
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

.quizably-bg-ctrl__toggle-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
}

.quizably-bg-ctrl__body {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.quizably-bg-ctrl__overlay {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
}

.quizably-bg-ctrl__overlay-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.quizably-bg-ctrl__chip-wrap {
  display: inline-grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border-radius: var(--r-xs);
  border: 1px solid var(--border-2);
  overflow: hidden;
  flex-shrink: 0;
}

.quizably-bg-ctrl__chip {
  -webkit-appearance: none;
  appearance: none;
  border: 0;
  padding: 0;
  width: 32px;
  height: 32px;
  background: transparent;
  cursor: pointer;
}

.quizably-bg-ctrl__chip::-webkit-color-swatch-wrapper {
  padding: 0;
}

.quizably-bg-ctrl__chip::-webkit-color-swatch {
  border: 0;
  border-radius: var(--r-xs);
}

.quizably-bg-ctrl__slider {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}

.quizably-bg-ctrl__slider-label {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  font-size: 11.5px;
  font-weight: 500;
  color: var(--ink-2);
}

.quizably-bg-ctrl__pct {
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-3);
  letter-spacing: 0.02em;
}

.quizably-bg-ctrl__range {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 4px;
  background: var(--border-2);
  border-radius: var(--r-pill);
  outline: none;
  cursor: pointer;
}

.quizably-bg-ctrl__range::-webkit-slider-thumb {
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

.quizably-bg-ctrl__range::-moz-range-thumb {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--brand);
  cursor: pointer;
  border: 2px solid #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.18);
}

.quizably-bg-ctrl__range:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.quizably-bg-ctrl__hint {
  margin: 0;
  font-size: 11.5px;
  line-height: 1.4;
  color: var(--ink-3);
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
</style>
