<template>
  <div class="quizably-cardstack">
    <div class="quizably-cardstack__stack" aria-hidden="true">
      <div class="quizably-cardstack__card quizably-cardstack__card--back2" />
      <div class="quizably-cardstack__card quizably-cardstack__card--back1" />
    </div>

    <div class="quizably-cardstack__main">
      <header
        v-if="progressBarComponent || timerComponent"
        class="quizably-cardstack__header"
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
      <div
        v-else-if="state.screen.value === 'question' && state.totalQuestions.value > 0"
        class="quizably-cardstack__counter"
      >
        {{ state.currentIndex.value + 1 }} / {{ state.totalQuestions.value }}
      </div>

      <transition
        :name="transitionName ?? 'quizably-cardstack-slide'"
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
    case 'question': return Question;
    case 'form': return Form;
    case 'result': return Result;
    case 'completed': return Completed;
    case 'intro':
    default: return Intro;
  }
});

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
.quizably-cardstack {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  /* Set by Quiz.vue's rootStyle — 32px/16px only on the standalone embed
     page, 0 everywhere else (shortcode/block/popup/slide-in). Same
     --quizably-quiz-outer-padding var Classic.vue reads; see its comment. */
  padding: var(--quizably-quiz-outer-padding, 0);
  position: relative;
}

/* Ghost cards underneath for depth */
.quizably-cardstack__stack {
  position: absolute;
  inset: 0;
  pointer-events: none;
  display: flex;
  justify-content: center;
  padding-top: 40px;
}

.quizably-cardstack__card {
  position: absolute;
  /* Ghost cards track the main card's max-width so the stagger looks right */
  width: min(var(--quizably-card-max-width, 640px), calc(100% - 48px));
  min-height: 200px;
  border-radius: var(--r-xl);
  border: 1px solid var(--border-1);
  /* Ghost cards reflect the background colour only — no image, so the depth
     effect stays subtle and doesn't compete with the main card's image. */
  background: var(--quizably-quiz-bg, #fff);
}

.quizably-cardstack__card--back2 {
  top: 60px;
  transform: rotate(-2.5deg) scale(0.95);
  opacity: 0.45;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.quizably-cardstack__card--back1 {
  top: 50px;
  transform: rotate(1.5deg) scale(0.975);
  opacity: 0.65;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.quizably-cardstack__main {
  width: 100%;
  max-width: var(--quizably-card-max-width, 640px);
  min-height: var(--quizably-card-min-height, auto);
  position: relative;
  z-index: 2;
  background: var(--quizably-quiz-bg, #fff);
  border: 1px solid var(--quizably-card-border, var(--border-1));
  border-radius: var(--r-xl);
  padding: 40px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  --quizably-cover-bleed: 40px;
}

/* Background image layer inside the main card */
.quizably-cardstack__main::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background-image: var(--quizably-quiz-bg-image, none);
  background-size: cover;
  background-position: center;
}

/* Overlay layer */
.quizably-cardstack__main::after {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background: var(--quizably-quiz-overlay-color, #000);
  opacity: var(--quizably-quiz-overlay-opacity-frac, 0);
}

/* Main card children above the image/overlay layers */
.quizably-cardstack__main > * {
  position: relative;
  z-index: 2;
}

.quizably-screen-wrap {
  width: 100%;
  max-width: var(--quizably-inner-max-width, 100%);
  margin: 0 auto;
}

.quizably-cardstack__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 28px;
  min-height: 18px;
}

.quizably-cardstack__counter {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-4);
  text-align: end;
  margin-bottom: 24px;
}

/* Screen transition â€” slides in from inline-end, exits inline-start (--quizably-dir flips it in RTL) */
.quizably-cardstack-slide-enter-active,
.quizably-cardstack-slide-leave-active {
  transition: opacity 200ms ease, transform 200ms ease;
}
.quizably-cardstack-slide-enter-from {
  opacity: 0;
  transform: translateX(calc(24px * var(--quizably-dir, 1)));
}
.quizably-cardstack-slide-leave-to {
  opacity: 0;
  transform: translateX(calc(-24px * var(--quizably-dir, 1)));
}

/* Cardstack â€” hover/active states with a slight lift */
.quizably-cardstack__main :deep(.quizably-single__option:hover),
.quizably-cardstack__main :deep(.quizably-multi__option:hover) {
  border-color: var(--quizably-quiz-brand);
  background: color-mix(in srgb, var(--quizably-quiz-brand) 5%, transparent);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.quizably-cardstack__main :deep(.quizably-single__option--active),
.quizably-cardstack__main :deep(.quizably-multi__option--active) {
  border-color: var(--quizably-quiz-brand);
  background: color-mix(in srgb, var(--quizably-quiz-brand) 10%, transparent);
  box-shadow: 0 0 0 1px var(--quizably-quiz-brand), 0 4px 12px rgba(0, 0, 0, 0.08);
}

.quizably-cardstack__main :deep(.quizably-single__option--active .quizably-single__letter),
.quizably-cardstack__main :deep(.quizably-multi__option--active .quizably-multi__letter) {
  background: var(--quizably-quiz-brand);
  color: #fff;
}

/* All screen title sizes */
.quizably-cardstack__main :deep(.quizably-question__title) {
  font-size: calc(26px * var(--quizably-font-scale, 1));
  line-height: 1.15;
  letter-spacing: -0.015em;
}

.quizably-cardstack__main :deep(.quizably-intro__title) {
  font-size: calc(38px * var(--quizably-font-scale, 1));
  line-height: 1.06;
  letter-spacing: -0.02em;
}

.quizably-cardstack__main :deep(.quizably-result__title) {
  font-size: calc(40px * var(--quizably-font-scale, 1));
  line-height: 1.06;
  letter-spacing: -0.02em;
}

.quizably-cardstack__main :deep(.quizably-form__title) {
  font-size: calc(30px * var(--quizably-font-scale, 1));
  line-height: 1.1;
  letter-spacing: -0.018em;
}

.quizably-cardstack__main :deep(.quizably-completed__title) {
  font-size: calc(30px * var(--quizably-font-scale, 1));
  line-height: 1.1;
  letter-spacing: -0.018em;
}

@media (max-width: 640px) {
  .quizably-cardstack {
    padding: 24px 16px;
  }
  .quizably-cardstack__main {
    padding: 28px 22px;
  }
  .quizably-cardstack__card--back2 {
    width: calc(100% - 32px);
  }
  .quizably-cardstack__card--back1 {
    width: calc(100% - 32px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-cardstack-slide-enter-active,
  .quizably-cardstack-slide-leave-active {
    transition-duration: 0ms;
  }
  .quizably-cardstack-slide-enter-from,
  .quizably-cardstack-slide-leave-to {
    transform: none;
  }
  .quizably-cardstack__card--back1,
  .quizably-cardstack__card--back2 {
    transform: none;
  }
}
</style>
