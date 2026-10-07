<template>
  <div
    class="tpl-minimal"
    :style="cssVars"
    :class="`is-buttons-${buttonStyle}`"
  >
    <div
      v-if="backgroundImage"
      class="tpl-minimal__bg"
      :style="{ backgroundImage: `url(${backgroundImage})` }"
      aria-hidden="true"
    />
    <div
      v-if="hasOverlay"
      class="tpl-minimal__overlay"
      :style="overlayStyle"
      aria-hidden="true"
    />
    <div class="tpl-minimal__inner">
      <h4 class="tpl-minimal__q">
        {{ question }}
      </h4>
      <ol class="tpl-minimal__answers">
        <li
          v-for="(a, i) in answers"
          :key="a"
          class="tpl-minimal__answer"
        >
          <span class="tpl-minimal__letter">{{ letterFor(i) }}</span>
          <span class="tpl-minimal__answer-text">{{ a }}</span>
        </li>
      </ol>
      <div class="tpl-minimal__foot">
        <span class="tpl-minimal__step">{{ position }} / {{ total }}</span>
        <button
          type="button"
          class="tpl-minimal__next"
        >
          Continue →
        </button>
      </div>
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

const hasOverlay = computed(() => (props.overlayOpacity || 0) > 0);
const overlayStyle = computed(() => ({
  background: props.overlayColor,
  opacity: Math.max(0, Math.min(100, props.overlayOpacity)) / 100,
}));

function letterFor(i) {
  return String.fromCharCode(65 + i);
}
</script>

<style scoped>
.tpl-minimal {
  position: relative;
  width: 100%;
  max-width: 520px;
  background: var(--p-background);
  color: var(--p-text);
  border-radius: var(--r-md);
  border: 1px solid color-mix(in srgb, var(--p-text) 8%, transparent);
  padding: 28px 32px;
  overflow: hidden;
}

.tpl-minimal__bg {
  position: absolute;
  inset: 0;
  background-position: center;
  background-size: cover;
  z-index: 0;
}

.tpl-minimal__overlay {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.tpl-minimal__inner {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.tpl-minimal__q {
  font-family: var(--f-display);
  font-size: calc(24px * var(--quizably-font-scale, 1));
  line-height: 1.3;
  font-weight: 500;
  letter-spacing: -0.005em;
  margin: 0;
  color: var(--p-text);
}

.tpl-minimal__answers {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.tpl-minimal__answer {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 4px;
  font-size: calc(16px * var(--quizably-font-scale, 1));
  cursor: pointer;
  border-bottom: 1px solid color-mix(in srgb, var(--p-text) 6%, transparent);
}

.tpl-minimal__answer:first-child {
  border-top: 1px solid color-mix(in srgb, var(--p-text) 6%, transparent);
}

.tpl-minimal__answer:hover {
  background: color-mix(in srgb, var(--p-primary) 4%, transparent);
}

.tpl-minimal__letter {
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 600;
  width: 22px;
  height: 22px;
  display: inline-grid;
  place-items: center;
  border: 1px solid color-mix(in srgb, var(--p-text) 14%, transparent);
  border-radius: 4px;
  color: color-mix(in srgb, var(--p-text) 70%, transparent);
}

.tpl-minimal__answer-text {
  flex: 1;
  color: var(--p-text);
}

.tpl-minimal__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 4px;
}

.tpl-minimal__step {
  font-family: var(--f-mono);
  font-size: 11px;
  color: color-mix(in srgb, var(--p-text) 60%, transparent);
  letter-spacing: 0.04em;
}

.tpl-minimal__next {
  background: transparent;
  color: var(--p-primary);
  border: 0;
  padding: 4px 0;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  letter-spacing: 0.005em;
}
</style>
