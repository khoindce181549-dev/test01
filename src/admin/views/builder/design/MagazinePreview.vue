<template>
  <div
    class="tpl-mag"
    :style="cssVars"
    :class="`is-buttons-${buttonStyle}`"
  >
    <div class="tpl-mag__hero">
      <div
        v-if="backgroundImage"
        class="tpl-mag__hero-bg"
        :style="{ backgroundImage: `url(${backgroundImage})` }"
      />
      <div
        v-if="hasOverlay"
        class="tpl-mag__hero-overlay"
        :style="overlayStyle"
        aria-hidden="true"
      />
      <div class="tpl-mag__hero-meta">
        <span class="tpl-mag__hero-issue">{{ issueLabel }}</span>
      </div>
    </div>
    <div class="tpl-mag__body">
      <div class="tpl-mag__main">
        <span class="tpl-mag__kicker">{{ __('Question') }}</span>
        <h4 class="tpl-mag__q">
          {{ question }}
        </h4>
        <p class="tpl-mag__deck">
          {{ __("Choose the option that resonates most. There are no wrong answers — just the one that's most you.") }}
        </p>
      </div>
      <ul class="tpl-mag__answers">
        <li
          v-for="(a, i) in answers"
          :key="a"
          class="tpl-mag__answer"
        >
          <span class="tpl-mag__answer-num">{{ (i + 1).toString().padStart(2, '0') }}</span>
          <span class="tpl-mag__answer-text">{{ a }}</span>
          <span
            class="tpl-mag__answer-arrow"
            aria-hidden="true"
          >→</span>
        </li>
      </ul>
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

// translators: 1: current issue (question) number, 2: total number of questions
const issueLabel = computed(() => sprintf(__('Issue %1$d · %2$d questions'), props.position, props.total));
const hasOverlay = computed(() => (props.overlayOpacity || 0) > 0);
const overlayStyle = computed(() => ({
  background: props.overlayColor,
  opacity: Math.max(0, Math.min(100, props.overlayOpacity)) / 100,
}));
</script>

<style scoped>
.tpl-mag {
  width: 100%;
  max-width: 620px;
  background: var(--p-background);
  color: var(--p-text);
  border-radius: var(--r-md);
  overflow: hidden;
  border: 1px solid var(--border-1);
  box-shadow: var(--shadow-md);
}

.tpl-mag__hero {
  position: relative;
  isolation: isolate;
  height: 120px;
  background: linear-gradient(135deg, color-mix(in srgb, var(--p-primary) 30%, var(--p-text)), var(--p-text));
  border-bottom: 4px solid var(--p-accent);
  overflow: hidden;
}

.tpl-mag__hero-bg {
  position: absolute;
  inset: 0;
  background-position: center;
  background-size: cover;
}

.tpl-mag__hero-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 1;
}

.tpl-mag__hero-meta {
  position: absolute;
  z-index: 2;
  bottom: 14px;
  inset-inline-start: 22px;
  inset-inline-end: 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.tpl-mag__hero-issue {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.92);
  background: rgba(0, 0, 0, 0.3);
  padding: 4px 10px;
  border-radius: 0;
}

.tpl-mag__body {
  padding: 26px 28px 28px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  align-items: start;
}

.tpl-mag__main {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.tpl-mag__kicker {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--p-accent);
  font-weight: 600;
}

.tpl-mag__q {
  font-family: var(--f-display);
  font-size: calc(32px * var(--quizably-font-scale, 1));
  line-height: 1.05;
  font-weight: 500;
  letter-spacing: -0.02em;
  margin: 0;
  color: var(--p-text);
}

.tpl-mag__deck {
  font-family: var(--f-display);
  font-style: italic;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  line-height: 1.5;
  color: color-mix(in srgb, var(--p-text) 65%, transparent);
  margin: 6px 0 0;
}

.tpl-mag__answers {
  list-style: none;
  margin: 0;
  padding: 0;
  border-top: 2px solid var(--p-text);
}

.tpl-mag__answer {
  display: grid;
  grid-template-columns: 32px 1fr 16px;
  gap: 10px;
  padding: 12px 4px;
  border-bottom: 1px solid color-mix(in srgb, var(--p-text) 10%, transparent);
  align-items: baseline;
  cursor: pointer;
  transition: background 150ms, padding 150ms;
}

.tpl-mag__answer:hover {
  background: color-mix(in srgb, var(--p-primary) 5%, transparent);
  padding-inline-start: 8px;
}

.tpl-mag__answer-num {
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 600;
  color: var(--p-accent);
  letter-spacing: 0.04em;
}

.tpl-mag__answer-text {
  font-family: var(--f-display);
  font-size: calc(16px * var(--quizably-font-scale, 1));
  line-height: 1.35;
  color: var(--p-text);
}

.tpl-mag__answer-arrow {
  font-size: calc(16px * var(--quizably-font-scale, 1));
  color: color-mix(in srgb, var(--p-text) 40%, transparent);
}
</style>
