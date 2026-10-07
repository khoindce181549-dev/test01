<template>
  <div
    class="tpl-split"
    :style="cssVars"
    :class="[`is-buttons-${buttonStyle}`, `is-layout-${splitLayout}`]"
  >
    <span class="tpl-split__size-badge">
      {{ splitWidthValue }}{{ splitWidthUnit }} × {{ splitHeightValue }}{{ splitHeightUnit }}
    </span>
    <div class="tpl-split__visual">
      <div
        v-if="backgroundImage"
        class="tpl-split__visual-bg"
        :style="{ backgroundImage: `url(${backgroundImage})` }"
      />
      <div
        v-else
        class="tpl-split__visual-fallback"
        aria-hidden="true"
      >
        <svg
          viewBox="0 0 80 80"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <circle cx="40" cy="40" r="22" />
          <circle cx="40" cy="40" r="10" />
          <path d="M40 8v12M40 60v12M8 40h12M60 40h12" />
        </svg>
      </div>
      <div
        v-if="hasOverlay"
        class="tpl-split__overlay"
        :style="overlayStyle"
        aria-hidden="true"
      />
      <span class="tpl-split__chip">{{ position }} / {{ total }}</span>
    </div>
    <div class="tpl-split__content">
      <h4 class="tpl-split__q">
        {{ question }}
      </h4>
      <div class="tpl-split__answers">
        <button
          v-for="a in answers"
          :key="a"
          type="button"
          class="tpl-split__answer"
        >
          {{ a }}
        </button>
      </div>
      <button
        type="button"
        class="tpl-split__next"
      >
        Continue →
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  colors: { type: Object, default: () => ({}) },
  buttonStyle: { type: String, default: 'rounded' },
  backgroundImage: { type: String, default: '' },
  overlayColor: { type: String, default: '#FFFFFF' },
  overlayOpacity: { type: Number, default: 88 },
  fontFamily: { type: String, default: 'default' },
  fontSizePct: { type: Number, default: 100 },
  splitLayout: { type: String, default: 'image-left' },
  splitImageWidth:   { type: Number, default: 42 },
  splitContentAlign: { type: String, default: 'center' },
  splitHeightValue:  { type: Number, default: 100 },
  splitHeightUnit:   { type: String, default: 'vh' },
  splitWidthValue:   { type: Number, default: 100 },
  splitWidthUnit:    { type: String, default: 'vw' },
  question: { type: String, default: '' },
  answers: { type: Array, default: () => [] },
  position: { type: Number, default: 1 },
  total: { type: Number, default: 5 },
});

const cssVars = computed(() => ({
  '--p-primary': props.colors.primary || '#4F46E5',
  '--p-background': props.colors.background || '#FFFFFF',
  '--p-text': props.colors.text || '#0A0A0B',
  '--p-accent': props.colors.accent || '#F59E0B',
  '--p-img-w': `${Math.min(70, Math.max(10, props.splitImageWidth))}%`,
  '--p-content-align': props.splitContentAlign === 'top'    ? 'flex-start'
                     : props.splitContentAlign === 'bottom' ? 'flex-end'
                     : 'center',
  '--p-height': `${props.splitHeightValue}${props.splitHeightUnit}`,
  '--p-width': `${props.splitWidthValue}${props.splitWidthUnit}`,
  '--quizably-font-scale': props.fontSizePct / 100,
}));

const hasOverlay = computed(() => (props.overlayOpacity || 0) > 0);
const overlayStyle = computed(() => ({
  background: props.overlayColor,
  opacity: Math.max(0, Math.min(100, props.overlayOpacity)) / 100,
}));
</script>

<style scoped>
.tpl-split {
  display: grid;
  grid-template-columns: var(--p-img-w, 42%) 1fr;
  width: var(--p-width, 100%);
  height: var(--p-height, auto);
  min-height: 320px;
  background: var(--p-background);
  color: var(--p-text);
  border-radius: var(--r-lg);
  overflow: hidden;
  border: 1px solid var(--border-1);
  box-shadow: var(--shadow-md);
  position: relative;
}

.tpl-split__size-badge {
  position: absolute;
  top: 8px;
  inset-inline-end: 8px;
  z-index: 10;
  font-family: var(--f-mono);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.04em;
  color: #fff;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  padding: 3px 7px;
  border-radius: var(--r-pill);
  pointer-events: none;
}

.tpl-split__visual {
  position: relative;
  isolation: isolate;
  background: linear-gradient(160deg, var(--p-primary), color-mix(in srgb, var(--p-accent) 70%, var(--p-primary)));
  color: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
}

.tpl-split__visual-bg {
  position: absolute;
  inset: 0;
  background-position: center;
  background-size: cover;
}

.tpl-split__overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 1;
}

.tpl-split__visual-fallback {
  position: relative;
  z-index: 1;
  width: 96px;
  height: 96px;
  color: rgba(255, 255, 255, 0.55);
}

.tpl-split__chip {
  position: absolute;
  top: 12px;
  inset-inline-start: 12px;
  z-index: 2;
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #fff;
  background: rgba(0, 0, 0, 0.35);
  padding: 3px 8px;
  border-radius: var(--r-pill);
}

.tpl-split__content {
  padding: 28px;
  display: flex;
  flex-direction: column;
  justify-content: var(--p-content-align, center);
  gap: 14px;
}

.tpl-split__q {
  font-family: var(--f-display);
  font-size: calc(26px * var(--quizably-font-scale, 1));
  line-height: 1.2;
  font-weight: 500;
  letter-spacing: -0.01em;
  margin: 0;
  color: var(--p-text);
}

.tpl-split__answers {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.tpl-split__answer {
  border: 1px solid color-mix(in srgb, var(--p-text) 12%, transparent);
  background: color-mix(in srgb, var(--p-primary) 5%, transparent);
  color: var(--p-text);
  padding: 10px 14px;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  font-family: inherit;
  cursor: pointer;
  text-align: start;
  transition: border-color 150ms, background 150ms;
}

.tpl-split.is-buttons-rounded .tpl-split__answer,
.tpl-split.is-buttons-rounded .tpl-split__next {
  border-radius: var(--r-md);
}

.tpl-split.is-buttons-pill .tpl-split__answer,
.tpl-split.is-buttons-pill .tpl-split__next {
  border-radius: var(--r-pill);
}

.tpl-split.is-buttons-sharp .tpl-split__answer,
.tpl-split.is-buttons-sharp .tpl-split__next {
  border-radius: 0;
}

.tpl-split__answer:hover {
  border-color: var(--p-primary);
  background: color-mix(in srgb, var(--p-primary) 10%, transparent);
}

.tpl-split__next {
  align-self: flex-start;
  background: var(--p-primary);
  color: #fff;
  border: 0;
  padding: 9px 16px;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
  margin-top: 4px;
}

.tpl-split.is-layout-image-right {
  grid-template-columns: 1fr var(--p-img-w, 42%);
}

.tpl-split.is-layout-image-right .tpl-split__visual {
  order: 2;
}

.tpl-split.is-layout-image-right .tpl-split__content {
  order: 1;
}
</style>
