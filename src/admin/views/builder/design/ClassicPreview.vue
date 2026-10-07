<template>
  <div
    class="tpl-classic"
    :style="cssVars"
    :class="`is-buttons-${buttonStyle}`"
  >
    <div
      v-if="backgroundImage"
      class="tpl-classic__bg"
      :style="{ backgroundImage: `url(${backgroundImage})` }"
      aria-hidden="true"
    />
    <div
      v-if="hasOverlay"
      class="tpl-classic__overlay"
      :style="overlayStyle"
      aria-hidden="true"
    />
    <div class="tpl-classic__card">
      <div class="tpl-classic__progress">
        <div
          class="tpl-classic__progress-fill"
          :style="{ width: progressWidth }"
        />
      </div>
      <div class="tpl-classic__stepper">
        {{ stepperLabel }}
      </div>
      <h4 class="tpl-classic__q">
        {{ question }}
      </h4>
      <div class="tpl-classic__answers">
        <button
          v-for="a in answers"
          :key="a"
          type="button"
          class="tpl-classic__answer"
        >
          {{ a }}
        </button>
      </div>
      <button
        type="button"
        class="tpl-classic__next"
      >
        Continue
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { __, sprintf } from '@shared/i18n';

const props = defineProps({
  colors: { type: Object, default: () => ({}) },
  buttonStyle: { type: String, default: 'rounded' },
  backgroundImage: { type: String, default: '' },
  overlayColor: { type: String, default: '#FFFFFF' },
  overlayOpacity: { type: Number, default: 88 },
  fontFamily: { type: String, default: 'default' },
  fontSizePct: { type: Number, default: 100 },
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
  '--quizably-font-scale': props.fontSizePct / 100,
}));

// translators: 1: current question number, 2: total number of questions
const stepperLabel = computed(() => sprintf(__('Question %1$d of %2$d'), props.position, props.total));
const hasOverlay = computed(() => (props.overlayOpacity || 0) > 0);
const overlayStyle = computed(() => ({
  background: props.overlayColor,
  opacity: Math.max(0, Math.min(100, props.overlayOpacity)) / 100,
}));

const progressWidth = computed(() => {
  const pct = Math.min(100, Math.max(5, (props.position / props.total) * 100));
  return `${pct}%`;
});
</script>

<style scoped>
.tpl-classic {
  position: relative;
  width: 100%;
  max-width: 560px;
  background: var(--p-background);
  color: var(--p-text);
  border-radius: var(--r-lg);
  border: 1px solid var(--border-1);
  box-shadow: var(--shadow-md);
  padding: 32px;
  overflow: hidden;
}

.tpl-classic__bg {
  position: absolute;
  inset: 0;
  background-position: center;
  background-size: cover;
  z-index: 0;
}

.tpl-classic__overlay {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.tpl-classic__card {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.tpl-classic__progress {
  width: 100%;
  height: 4px;
  background: color-mix(in srgb, var(--p-text) 8%, transparent);
  border-radius: var(--r-pill);
  overflow: hidden;
}

.tpl-classic__progress-fill {
  height: 100%;
  background: var(--p-primary);
  border-radius: var(--r-pill);
}

.tpl-classic__stepper {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--p-accent);
}

.tpl-classic__q {
  font-family: var(--f-display);
  font-size: calc(28px * var(--quizably-font-scale, 1));
  line-height: 1.2;
  font-weight: 500;
  letter-spacing: -0.01em;
  margin: 0;
  color: var(--p-text);
}

.tpl-classic__answers {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.tpl-classic__answer {
  border: 1px solid color-mix(in srgb, var(--p-text) 12%, transparent);
  background: color-mix(in srgb, var(--p-primary) 5%, transparent);
  color: var(--p-text);
  padding: 12px 14px;
  font-size: calc(16px * var(--quizably-font-scale, 1));
  font-family: inherit;
  cursor: pointer;
  text-align: start;
  transition: border-color 150ms, background 150ms;
}

.tpl-classic.is-buttons-rounded .tpl-classic__answer,
.tpl-classic.is-buttons-rounded .tpl-classic__next {
  border-radius: var(--r-md);
}

.tpl-classic.is-buttons-pill .tpl-classic__answer,
.tpl-classic.is-buttons-pill .tpl-classic__next {
  border-radius: var(--r-pill);
}

.tpl-classic.is-buttons-sharp .tpl-classic__answer,
.tpl-classic.is-buttons-sharp .tpl-classic__next {
  border-radius: 0;
}

.tpl-classic__answer:hover {
  border-color: var(--p-primary);
  background: color-mix(in srgb, var(--p-primary) 10%, transparent);
}

.tpl-classic__next {
  align-self: flex-start;
  background: var(--p-primary);
  color: #fff;
  border: 0;
  padding: 10px 18px;
  font-size: calc(16px * var(--quizably-font-scale, 1));
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
  margin-top: 6px;
}
</style>
