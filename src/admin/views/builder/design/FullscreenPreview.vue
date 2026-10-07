<template>
  <div
    class="tpl-fs"
    :style="cssVars"
    :class="`is-buttons-${buttonStyle}`"
  >
    <div
      v-if="backgroundImage"
      class="tpl-fs__bg"
      :style="{ backgroundImage: `url(${backgroundImage})` }"
      aria-hidden="true"
    />
    <div class="tpl-fs__overlay" />
    <div
      v-if="hasOverlay"
      class="tpl-fs__user-overlay"
      :style="overlayStyle"
      aria-hidden="true"
    />
    <div class="tpl-fs__inner">
      <div class="tpl-fs__top">
        <span class="tpl-fs__chip">{{ position }} / {{ total }}</span>
      </div>
      <h4 class="tpl-fs__q">
        {{ question }}
      </h4>
      <div class="tpl-fs__answers">
        <button
          v-for="a in answers"
          :key="a"
          type="button"
          class="tpl-fs__answer"
        >
          {{ a }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { fullscreenStage } from '@shared/fullscreenStage.js';

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
  // accent slot is repurposed as content text/border color for Fullscreen.
  // Default to white — the natural choice for a dark full-bleed card.
  '--p-accent': props.colors.accent || '#FFFFFF',
  // The stage: the same definition the live template paints from.
  '--p-stage': fullscreenStage('var(--p-text)', 'var(--p-primary)'),
  '--quizably-font-scale': props.fontSizePct / 100,
}));

const hasOverlay = computed(() => (props.overlayOpacity || 0) > 0);
const overlayStyle = computed(() => ({
  background: props.overlayColor,
  opacity: Math.max(0, Math.min(100, props.overlayOpacity)) / 100,
}));
</script>

<style scoped>
.tpl-fs {
  position: relative;
  /* Explicit stacking context so every z-index here is local to the card */
  isolation: isolate;
  width: 100%;
  max-width: 620px;
  min-height: 360px;
  background: var(--p-stage);
  color: #fff;
  border-radius: var(--r-lg);
  overflow: hidden;
  box-shadow: var(--shadow-md);
}

.tpl-fs__bg {
  position: absolute;
  inset: 0;
  background-position: center;
  background-size: cover;
  z-index: 0;
}

.tpl-fs__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, color-mix(in srgb, var(--p-text) 70%, transparent), color-mix(in srgb, var(--p-primary) 60%, transparent));
  z-index: 1;
}

.tpl-fs__user-overlay {
  position: absolute;
  inset: 0;
  /* Above the decorative gradient (z-index: 1) but below content (z-index: 3) */
  z-index: 2;
  pointer-events: none;
}

.tpl-fs__inner {
  position: relative;
  /* Always above both overlay layers */
  z-index: 3;
  padding: 44px 40px 40px;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.tpl-fs__top {
  display: flex;
  justify-content: flex-end;
}

.tpl-fs__chip {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  background: color-mix(in srgb, var(--p-accent) 15%, transparent);
  backdrop-filter: blur(4px);
  padding: 4px 10px;
  border-radius: var(--r-pill);
  color: var(--p-accent);
}

.tpl-fs__q {
  font-family: var(--f-display);
  font-size: calc(36px * var(--quizably-font-scale, 1));
  line-height: 1.1;
  font-weight: 500;
  letter-spacing: -0.015em;
  margin: 0;
  color: var(--p-accent);
  max-width: 22ch;
}

.tpl-fs__answers {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 6px;
}

.tpl-fs__answer {
  border: 1px solid color-mix(in srgb, var(--p-accent) 30%, transparent);
  background: color-mix(in srgb, var(--p-accent) 8%, transparent);
  backdrop-filter: blur(6px);
  color: var(--p-accent);
  padding: 12px 16px;
  font-size: calc(16px * var(--quizably-font-scale, 1));
  font-family: inherit;
  cursor: pointer;
  text-align: start;
  transition: background 150ms, border-color 150ms;
}

.tpl-fs.is-buttons-rounded .tpl-fs__answer {
  border-radius: var(--r-md);
}

.tpl-fs.is-buttons-pill .tpl-fs__answer {
  border-radius: var(--r-pill);
}

.tpl-fs.is-buttons-sharp .tpl-fs__answer {
  border-radius: 0;
}

.tpl-fs__answer:hover {
  border-color: color-mix(in srgb, var(--p-accent) 55%, transparent);
  background: color-mix(in srgb, var(--p-accent) 16%, transparent);
}
</style>
