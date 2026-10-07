<template>
  <div class="quizably-classic">
    <div class="quizably-classic__card">
      <header
        v-if="progressBarComponent || timerComponent"
        class="quizably-classic__header"
      >
        <component
          :is="progressBarComponent"
          v-if="progressBarComponent"
          :quiz="quiz"
          :state="state"
          :current="state.progress.value"
        />
        <component
          :is="timerComponent"
          v-if="timerComponent"
          :quiz="quiz"
          :state="state"
          @expired="onTimerExpired"
        />
      </header>

      <transition
        :name="transitionName ?? 'quizably-classic-fade'"
        mode="out-in"
      >
        <div
          :key="state.screen.value + ':' + state.currentIndex.value"
          class="quizably-screen-wrap"
        >
          <component
            :is="screenComponent"
            :quiz="quiz"
            :result="state.result.value"
            :score="state.score.value"
            :breakdown="state.breakdown.value"
            :answers="state.answers"
            @nav="emitNav"
          />
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Intro from '../screens/Intro.vue';
import Question from '../screens/Question.vue';
import Form from '../screens/Form.vue';
import Result from '../screens/Result.vue';
import Completed from '../screens/Completed.vue';
import { useQuizTransition } from '../composables/useQuizTransition.js';

const props = defineProps({
  quiz: { type: Object, default: null },
  state: { type: Object, required: true },
  emitNav: { type: Function, required: true },
});

const { transitionName } = useQuizTransition(computed(() => props.quiz));

const screenComponent = computed(() => {
  switch (props.state.screen.value) {
    case 'question':
      return Question;
    case 'form':
      return Form;
    case 'result':
      return Result;
    case 'completed':
      return Completed;
    case 'intro':
    default:
      return Intro;
  }
});

// Progress bar + timer are Pro-only â€” Pro registers components at
// window.Quizably._progressBar / _timer. Free shows just a small counter.
const progressBarComponent = computed(() =>
  typeof window !== 'undefined' ? (window.Quizably?._progressBar ?? null) : null
);
const timerComponent = computed(() =>
  typeof window !== 'undefined' ? (window.Quizably?._timer ?? null) : null
);

function onTimerExpired() {
  props.emitNav('complete');
}
</script>

<style scoped>
.quizably-classic {
  width: 100%;
  /* Fill the height given by .quizably-quiz__content so the card can be
     vertically centred when a background image / valign is set. */
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  /* Set by Quiz.vue's rootStyle — 32px/16px only on the standalone embed
     page, 0 everywhere else (shortcode/block/popup/slide-in). */
  padding: var(--quizably-quiz-outer-padding, 0);
}

.quizably-classic__card {
  width: 100%;
  max-width: var(--quizably-card-max-width, 640px);
  min-height: var(--quizably-card-min-height, auto);
  /* Background colour always applied to the card surface — the outer .quizably-quiz
     wrapper stays transparent for card-based templates so the host page shows
     through around the card. */
  background: var(--quizably-quiz-bg, #fff);
  /* --quizably-card-border → transparent when background image is active OR when
     the author turns off the border in the Design tab → show_border: false */
  border: 1px solid var(--quizably-card-border, var(--border-1));
  border-radius: var(--r-xl);
  padding: 40px;
  position: relative;
  overflow: hidden;
  --quizably-cover-bleed: 40px;
}

/* Background image layer — sits behind all card content */
.quizably-classic__card::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background-image: var(--quizably-quiz-bg-image, none);
  background-size: cover;
  background-position: center;
}

/* Overlay layer — tint on top of the image for legibility */
.quizably-classic__card::after {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background: var(--quizably-quiz-overlay-color, #000);
  opacity: var(--quizably-quiz-overlay-opacity-frac, 0);
}

/* All direct card children must sit above the image/overlay layers */
.quizably-classic__card > * {
  position: relative;
  z-index: 2;
}

/* Inner content column — respects --quizably-inner-max-width for readable-text control */
.quizably-screen-wrap {
  width: 100%;
  max-width: var(--quizably-inner-max-width, 100%);
  margin: 0 auto;
}

.quizably-classic__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 28px;
  min-height: 18px;
}

.quizably-classic__counter {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-4);
}

/* Screen transition */
.quizably-classic-fade-enter-active,
.quizably-classic-fade-leave-active {
  transition: opacity 200ms ease, transform 200ms ease;
}
.quizably-classic-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.quizably-classic-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Classic â€” clean branded answer options */
.quizably-classic__card :deep(.quizably-single__option:hover),
.quizably-classic__card :deep(.quizably-multi__option:hover) {
  border-color: var(--quizably-quiz-brand);
  background: color-mix(in srgb, var(--quizably-quiz-brand) 5%, transparent);
}

.quizably-classic__card :deep(.quizably-single__option--active),
.quizably-classic__card :deep(.quizably-multi__option--active) {
  border-color: var(--quizably-quiz-brand);
  background: color-mix(in srgb, var(--quizably-quiz-brand) 10%, transparent);
  box-shadow: 0 0 0 1px var(--quizably-quiz-brand);
}

.quizably-classic__card :deep(.quizably-single__option--active .quizably-single__letter),
.quizably-classic__card :deep(.quizably-multi__option--active .quizably-multi__letter) {
  background: var(--quizably-quiz-brand);
  color: #fff;
}

/* All screen title sizes â€” consistent editorial scale */
.quizably-classic__card :deep(.quizably-question__title) {
  font-size: calc(26px * var(--quizably-font-scale, 1));
  line-height: 1.15;
  letter-spacing: -0.015em;
}

.quizably-classic__card :deep(.quizably-intro__title) {
  font-size: calc(38px * var(--quizably-font-scale, 1));
  line-height: 1.06;
  letter-spacing: -0.02em;
}

.quizably-classic__card :deep(.quizably-result__title) {
  font-size: calc(40px * var(--quizably-font-scale, 1));
  line-height: 1.06;
  letter-spacing: -0.02em;
}

.quizably-classic__card :deep(.quizably-form__title) {
  font-size: calc(30px * var(--quizably-font-scale, 1));
  line-height: 1.1;
  letter-spacing: -0.018em;
}

.quizably-classic__card :deep(.quizably-completed__title) {
  font-size: calc(30px * var(--quizably-font-scale, 1));
  line-height: 1.1;
  letter-spacing: -0.018em;
}

@media (max-width: 640px) {
  .quizably-classic__card {
    padding: 28px 22px;
    border-radius: var(--r-lg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-classic-fade-enter-active,
  .quizably-classic-fade-leave-active {
    transition-duration: 0ms;
  }
  .quizably-classic-fade-enter-from,
  .quizably-classic-fade-leave-to {
    transform: none;
  }
}
</style>

