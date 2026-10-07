<template>
  <div
    class="tpl-stack"
    :style="cssVars"
    :class="`is-buttons-${buttonStyle}`"
  >
    <div
      v-if="backgroundImage"
      class="tpl-stack__bg"
      :style="{ backgroundImage: `url(${backgroundImage})` }"
      aria-hidden="true"
    />
    <div
      v-if="hasOverlay"
      class="tpl-stack__overlay"
      :style="overlayStyle"
      aria-hidden="true"
    />
    <div
      class="tpl-stack__card tpl-stack__card--back tpl-stack__card--back-2"
      aria-hidden="true"
    />
    <div
      class="tpl-stack__card tpl-stack__card--back tpl-stack__card--back-1"
      aria-hidden="true"
    />
    <div class="tpl-stack__card tpl-stack__card--top">
      <div class="tpl-stack__top">
        <span class="tpl-stack__count">{{ position }}/{{ total }}</span>
      </div>
      <h4 class="tpl-stack__q">
        {{ question }}
      </h4>
      <div class="tpl-stack__answers">
        <button
          v-for="a in answers"
          :key="a"
          type="button"
          class="tpl-stack__answer"
        >
          {{ a }}
        </button>
      </div>
      <div class="tpl-stack__nav">
        <button
          type="button"
          class="tpl-stack__nav-btn"
        >
          {{ __('← Skip') }}
        </button>
        <button
          type="button"
          class="tpl-stack__next"
        >
          {{ __('Continue') }}
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
</script>

<style scoped>
.tpl-stack {
  position: relative;
  width: 100%;
  max-width: 540px;
  padding: 30px 30px 52px;
  border-radius: var(--r-lg);
  overflow: hidden;
}

.tpl-stack__bg {
  position: absolute;
  inset: 0;
  background-position: center;
  background-size: cover;
  z-index: 0;
  border-radius: var(--r-lg);
}

.tpl-stack__overlay {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  border-radius: var(--r-lg);
}

.tpl-stack__card {
  background: var(--p-background);
  color: var(--p-text);
  border-radius: var(--r-lg);
  border: 1px solid color-mix(in srgb, var(--p-text) 8%, transparent);
}

.tpl-stack__card--back {
  position: absolute;
  left: 50%; /* rtl-ok: centring pair with translateX(-50%), symmetric in both directions */
  width: 88%;
  height: 100%;
  background: color-mix(in srgb, var(--p-text) 4%, var(--p-background));
}

.tpl-stack__card--back-1 {
  top: 14px;
  transform: translateX(-50%) scale(0.96);
  opacity: 0.6;
  z-index: 0;
}

.tpl-stack__card--back-2 {
  top: 26px;
  transform: translateX(-50%) scale(0.92);
  opacity: 0.35;
  z-index: 0;
}

.tpl-stack__card--top {
  position: relative;
  z-index: 1;
  padding: 28px 32px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: var(--shadow-md);
}

.tpl-stack__top {
  display: flex;
  align-items: center;
}

.tpl-stack__count {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--p-accent);
  padding: 3px 8px;
  background: color-mix(in srgb, var(--p-accent) 14%, transparent);
  border-radius: var(--r-xs);
}

.tpl-stack__q {
  font-family: var(--f-display);
  font-size: calc(26px * var(--quizably-font-scale, 1));
  line-height: 1.2;
  font-weight: 500;
  letter-spacing: -0.01em;
  margin: 0;
  color: var(--p-text);
}

.tpl-stack__answers {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.tpl-stack__answer {
  border: 1px solid color-mix(in srgb, var(--p-text) 12%, transparent);
  background: color-mix(in srgb, var(--p-primary) 4%, transparent);
  color: var(--p-text);
  padding: 11px 14px;
  font-size: calc(16px * var(--quizably-font-scale, 1));
  font-family: inherit;
  cursor: pointer;
  text-align: start;
  transition: border-color 150ms, background 150ms;
}

.tpl-stack.is-buttons-rounded .tpl-stack__answer,
.tpl-stack.is-buttons-rounded .tpl-stack__next,
.tpl-stack.is-buttons-rounded .tpl-stack__nav-btn {
  border-radius: var(--r-md);
}

.tpl-stack.is-buttons-pill .tpl-stack__answer,
.tpl-stack.is-buttons-pill .tpl-stack__next,
.tpl-stack.is-buttons-pill .tpl-stack__nav-btn {
  border-radius: var(--r-pill);
}

.tpl-stack.is-buttons-sharp .tpl-stack__answer,
.tpl-stack.is-buttons-sharp .tpl-stack__next,
.tpl-stack.is-buttons-sharp .tpl-stack__nav-btn {
  border-radius: 0;
}

.tpl-stack__answer:hover {
  border-color: var(--p-primary);
  background: color-mix(in srgb, var(--p-primary) 10%, transparent);
}

.tpl-stack__nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 4px;
}

.tpl-stack__nav-btn {
  background: transparent;
  border: 1px solid color-mix(in srgb, var(--p-text) 14%, transparent);
  color: color-mix(in srgb, var(--p-text) 70%, transparent);
  padding: 8px 14px;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  font-family: inherit;
  cursor: pointer;
}

.tpl-stack__next {
  background: var(--p-primary);
  color: #fff;
  border: 0;
  padding: 9px 18px;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
}
</style>
