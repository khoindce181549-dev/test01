<template>
  <div
    class="tpl-game"
    :style="cssVars"
    :class="`is-buttons-${buttonStyle}`"
  >
    <div class="tpl-game__head">
      <div
        v-if="backgroundImage"
        class="tpl-game__head-bg"
        :style="{ backgroundImage: `url(${backgroundImage})` }"
        aria-hidden="true"
      />
      <div
        v-if="hasOverlay"
        class="tpl-game__head-overlay"
        :style="overlayStyle"
        aria-hidden="true"
      />
      <div class="tpl-game__lvl">
        <span class="tpl-game__lvl-num">{{ position }}</span>
        <span class="tpl-game__lvl-label">{{ __('Level') }}</span>
      </div>
      <div class="tpl-game__pips">
        <span
          v-for="i in total"
          :key="i"
          :class="['tpl-game__pip', { 'is-done': i <= position, 'is-current': i === position }]"
        />
      </div>
      <div class="tpl-game__score">
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
        <span>120</span>
      </div>
    </div>

    <div class="tpl-game__card">
      <h4 class="tpl-game__q">
        {{ question }}
      </h4>
      <div class="tpl-game__answers">
        <button
          v-for="(a, i) in answers"
          :key="a"
          type="button"
          class="tpl-game__answer"
        >
          <span class="tpl-game__answer-letter">{{ letterFor(i) }}</span>
          <span class="tpl-game__answer-text">{{ a }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { __ } from '@shared/i18n';

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
.tpl-game {
  width: 100%;
  max-width: 560px;
  background: var(--p-background);
  color: var(--p-text);
  border-radius: var(--r-lg);
  border: 1px solid var(--border-1);
  overflow: hidden;
  box-shadow: var(--shadow-md);
}

.tpl-game__head {
  position: relative;
  isolation: isolate;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  background: linear-gradient(135deg, var(--p-primary), var(--p-accent));
  color: #fff;
  overflow: hidden;
}

.tpl-game__head-bg {
  position: absolute;
  inset: 0;
  background-position: center;
  background-size: cover;
  z-index: 0;
}

.tpl-game__head-overlay {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.tpl-game__head > *:not(.tpl-game__head-bg):not(.tpl-game__head-overlay) {
  position: relative;
  z-index: 1;
}

.tpl-game__lvl {
  display: flex;
  align-items: baseline;
  gap: 6px;
}

.tpl-game__lvl-num {
  font-family: var(--f-display);
  font-size: calc(26px * var(--quizably-font-scale, 1));
  font-weight: 600;
  line-height: 1;
}

.tpl-game__lvl-label {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.85);
}

.tpl-game__pips {
  flex: 1;
  display: flex;
  gap: 4px;
  align-items: center;
}

.tpl-game__pip {
  flex: 1;
  height: 6px;
  border-radius: var(--r-pill);
  background: rgba(255, 255, 255, 0.25);
}

.tpl-game__pip.is-done {
  background: #fff;
}

.tpl-game__pip.is-current {
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.4);
}

.tpl-game__score {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  background: rgba(0, 0, 0, 0.18);
  border-radius: var(--r-pill);
  font-family: var(--f-mono);
  font-size: 12px;
  font-weight: 600;
}

.tpl-game__score svg {
  width: 13px;
  height: 13px;
  color: #FCD34D;
  fill: #FCD34D;
}

.tpl-game__card {
  padding: 26px 24px 28px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.tpl-game__q {
  font-family: var(--f-display);
  font-size: calc(26px * var(--quizably-font-scale, 1));
  line-height: 1.2;
  font-weight: 500;
  letter-spacing: -0.01em;
  margin: 0;
  color: var(--p-text);
}

.tpl-game__answers {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.tpl-game__answer {
  display: flex;
  align-items: center;
  gap: 10px;
  border: 2px solid color-mix(in srgb, var(--p-primary) 14%, transparent);
  background: color-mix(in srgb, var(--p-primary) 4%, transparent);
  color: var(--p-text);
  padding: 12px;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  font-family: inherit;
  cursor: pointer;
  text-align: start;
  transition: border-color 150ms, background 150ms, transform 150ms;
}

.tpl-game.is-buttons-rounded .tpl-game__answer {
  border-radius: var(--r-md);
}

.tpl-game.is-buttons-pill .tpl-game__answer {
  border-radius: var(--r-lg);
}

.tpl-game.is-buttons-sharp .tpl-game__answer {
  border-radius: 0;
}

.tpl-game__answer:hover {
  border-color: var(--p-primary);
  background: color-mix(in srgb, var(--p-primary) 10%, transparent);
  transform: translateY(-1px);
}

.tpl-game__answer-letter {
  display: inline-grid;
  place-items: center;
  width: 26px;
  height: 26px;
  border-radius: var(--r-sm);
  background: var(--p-primary);
  color: #fff;
  font-family: var(--f-mono);
  font-size: 11.5px;
  font-weight: 700;
  flex-shrink: 0;
}

.tpl-game__answer-text {
  flex: 1;
}
</style>
