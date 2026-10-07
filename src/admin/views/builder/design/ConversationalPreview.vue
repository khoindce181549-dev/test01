<template>
  <div
    class="tpl-conv"
    :style="cssVars"
    :class="`is-buttons-${buttonStyle}`"
  >
    <div
      v-if="backgroundImage"
      class="tpl-conv__bg"
      :style="{ backgroundImage: `url(${backgroundImage})` }"
      aria-hidden="true"
    />
    <div
      v-if="hasOverlay"
      class="tpl-conv__overlay"
      :style="overlayStyle"
      aria-hidden="true"
    />
    <div class="tpl-conv__inner">
    <div class="tpl-conv__head">
      <div
        class="tpl-conv__avatar"
        aria-hidden="true"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
        </svg>
      </div>
      <span class="tpl-conv__step">{{ stepLabel }}</span>
    </div>

    <div class="tpl-conv__bubble tpl-conv__bubble--from">
      {{ question }}
    </div>

    <div class="tpl-conv__answers">
      <button
        v-for="a in answers"
        :key="a"
        type="button"
        class="tpl-conv__answer"
      >
        {{ a }}
      </button>
    </div>

    <div class="tpl-conv__hint">
      {{ __('Tap an option, or type to send a custom answer…') }}
    </div>
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
const stepLabel = computed(() => sprintf(__('%1$d of %2$d'), props.position, props.total));
const hasOverlay = computed(() => (props.overlayOpacity || 0) > 0);
const overlayStyle = computed(() => ({
  background: props.overlayColor,
  opacity: Math.max(0, Math.min(100, props.overlayOpacity)) / 100,
}));
</script>

<style scoped>
.tpl-conv {
  position: relative;
  width: 100%;
  max-width: 480px;
  background: var(--p-background);
  color: var(--p-text);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  padding: 18px 20px 22px;
  box-shadow: var(--shadow-md);
  overflow: hidden;
}

.tpl-conv__bg {
  position: absolute;
  inset: 0;
  background-position: center;
  background-size: cover;
  z-index: 0;
}

.tpl-conv__overlay {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.tpl-conv__inner {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.tpl-conv__head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid color-mix(in srgb, var(--p-text) 8%, transparent);
}

.tpl-conv__avatar {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: color-mix(in srgb, var(--p-primary) 14%, transparent);
  color: var(--p-primary);
}

.tpl-conv__avatar svg {
  width: 16px;
  height: 16px;
}

.tpl-conv__step {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.04em;
  color: color-mix(in srgb, var(--p-text) 60%, transparent);
}

.tpl-conv__bubble {
  display: inline-block;
  padding: 12px 16px;
  font-size: calc(16px * var(--quizably-font-scale, 1));
  line-height: 1.45;
  max-width: 88%;
}

.tpl-conv__bubble--from {
  align-self: flex-start;
  background: color-mix(in srgb, var(--p-text) 6%, transparent);
  color: var(--p-text);
  border-start-start-radius: 14px;
  border-start-end-radius: 14px;
  border-end-end-radius: 14px;
  border-end-start-radius: 4px;
  font-family: var(--f-display);
  font-size: calc(16px * var(--quizably-font-scale, 1));
}

.tpl-conv__answers {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 6px;
}

.tpl-conv__answer {
  background: var(--p-primary);
  color: #fff;
  border: 0;
  padding: 9px 14px;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  font-family: inherit;
  cursor: pointer;
  transition: opacity 150ms;
}

.tpl-conv.is-buttons-rounded .tpl-conv__answer {
  border-start-start-radius: 14px;
  border-start-end-radius: 14px;
  border-end-end-radius: 4px;
  border-end-start-radius: 14px;
}

.tpl-conv.is-buttons-pill .tpl-conv__answer {
  border-radius: var(--r-pill);
}

.tpl-conv.is-buttons-sharp .tpl-conv__answer {
  border-radius: 0;
}

.tpl-conv__answer:hover {
  opacity: 0.9;
}

.tpl-conv__hint {
  font-size: 11.5px;
  color: color-mix(in srgb, var(--p-text) 50%, transparent);
  font-style: italic;
  margin-top: 4px;
  padding-top: 10px;
  border-top: 1px dashed color-mix(in srgb, var(--p-text) 12%, transparent);
}
</style>
