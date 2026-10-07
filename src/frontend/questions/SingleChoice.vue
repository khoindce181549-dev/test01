<template>
  <div
    v-if="answers.length === 0"
    class="quizably-single quizably-single--empty"
    role="status"
  >
    {{ __('No answer options have been configured for this question yet.') }}
  </div>
  <div
    v-else
    class="quizably-single"
    role="radiogroup"
    :aria-label="question.title"
    @keydown="onKeydown"
  >
    <button
      v-for="(answer, i) in answers"
      :key="answer.id"
      ref="buttonEls"
      type="button"
      role="radio"
      :aria-checked="value === answer.id"
      :class="[
        'quizably-single__option',
        {
          'quizably-single__option--active': value === answer.id,
          'quizably-single__option--has-image': !!answer.image_url,
        },
      ]"
      :tabindex="isFocusable(answer.id, i) ? 0 : -1"
      @click="select(answer.id)"
    >
      <span class="quizably-single__letter">{{ letter(i) }}</span>
      <span
        v-if="answer.image_url"
        class="quizably-single__image"
        :style="{ backgroundImage: `url(${answer.image_url})` }"
        aria-hidden="true"
      />
      <span class="quizably-single__label">{{ answer.label }}</span>
    </button>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed, ref, nextTick } from 'vue';
import { arrowStep } from '@shared/direction.js';
import { shuffledAnswers } from '../composables/useRandomizedAnswers.js';

const props = defineProps({
  question: { type: Object, required: true },
  value: { type: [String, Number, null], default: null },
  autoAdvance: { type: Boolean, default: true },
});

const emit = defineEmits(['update:value', 'advance']);

const buttonEls = ref([]);
const answers = computed(() => shuffledAnswers(props.question));

function letter(i) {
  return String.fromCharCode(65 + i) + '.';
}

function isFocusable(id, i) {
  if (props.value != null) return props.value === id;
  return i === 0;
}

function select(id) {
  emit('update:value', id);
  if (props.autoAdvance) {
    nextTick(() => emit('advance'));
  }
}

function onKeydown(event) {
  const list = answers.value;
  if (list.length === 0) return;

  // Number keys 1-9 → select Nth answer.
  if (/^[1-9]$/.test(event.key)) {
    const idx = Number(event.key) - 1;
    if (idx < list.length) {
      event.preventDefault();
      select(list[idx].id);
    }
    return;
  }

  const currentIdx = list.findIndex((a) => a.id === props.value);
  let nextIdx = null;

  // ArrowLeft/ArrowRight follow the reading direction (RTL: ArrowLeft = next).
  const step = arrowStep(event);
  if (step === 1) {
    nextIdx = currentIdx === -1 ? 0 : Math.min(list.length - 1, currentIdx + 1);
  } else if (step === -1) {
    nextIdx = currentIdx === -1 ? 0 : Math.max(0, currentIdx - 1);
  } else if (event.key === 'Enter' || event.key === ' ') {
    if (currentIdx === -1) {
      event.preventDefault();
      select(list[0].id);
    }
    return;
  }

  if (nextIdx != null) {
    event.preventDefault();
    emit('update:value', list[nextIdx].id);
    nextTick(() => {
      const el = buttonEls.value[nextIdx];
      el?.focus();
    });
  }
}
</script>

<style scoped>
.quizably-single {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

.quizably-single--empty {
  padding: 16px 18px;
  background: var(--quizably-quiz-option-bg, var(--bg-subtle));
  border: 1px dashed var(--quizably-quiz-option-border, var(--border-2));
  border-radius: var(--r-md);
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
  font-size: 14px;
  text-align: center;
}

.quizably-single__option {
  display: flex;
  align-items: center;
  gap: 14px;
  text-align: start;
  padding: 16px 18px;
  background: var(--quizably-quiz-option-bg, var(--bg-surface));
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  /* Respects design.button_style from the Design tab.
     Default falls back to --r-lg to keep a card-like feel for answer rows. */
  border-radius: var(--quizably-btn-radius, var(--r-lg));
  font-size: 15px;
  color: var(--quizably-quiz-text);
  transition:
    background-color 140ms ease,
    border-color 140ms ease,
    box-shadow 140ms ease,
    transform 140ms ease;
  cursor: pointer;
  width: 100%;
}

.quizably-single__option:hover {
  background: var(--quizably-quiz-option-bg-hover, var(--bg-subtle));
  border-color: var(--quizably-quiz-option-border, var(--border-3));
}

/* Tinted from the brand rather than a fixed pale orange: the option's text follows
   the quiz theme, and white text on a pale fill is unreadable on a dark quiz. */
.quizably-single__option--active {
  background: color-mix(in srgb, var(--quizably-quiz-brand) 10%, transparent);
  border-color: var(--quizably-quiz-brand);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.quizably-single__letter {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  border-radius: var(--r-pill);
  background: var(--quizably-quiz-option-letter-bg, var(--bg-subtle));
  font-family: var(--f-mono);
  font-size: 12px;
  font-weight: 600;
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
  flex-shrink: 0;
}

.quizably-single__option--active .quizably-single__letter {
  background: var(--quizably-quiz-brand);
  color: #fff;
}

.quizably-single__label {
  flex: 1;
  line-height: 1.45;
}

.quizably-single__image {
  width: 56px;
  height: 56px;
  background-size: cover;
  background-position: center;
  background-color: var(--bg-muted);
  border-radius: var(--r-md);
  flex-shrink: 0;
}

@media (min-width: 640px) {
  .quizably-single:has(.quizably-single__option--has-image) {
    grid-template-columns: 1fr 1fr;
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-single__option {
    transition-duration: 0ms;
  }
}
</style>
