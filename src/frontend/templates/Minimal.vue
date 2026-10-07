<template>
  <div class="quizably-minimal">
    <div
      v-if="progressBarComponent || (state.totalQuestions.value > 0 && state.screen.value === 'question')"
      class="quizably-minimal__progress"
    >
      <component
        :is="progressBarComponent"
        v-if="progressBarComponent"
        :quiz="quiz"
        :state="state"
        :current="state.progress.value"
        variant="thin"
      />
      <span
        v-else
        class="quizably-minimal__counter"
      >{{ state.currentIndex.value + 1 }} / {{ state.totalQuestions.value }}</span>
    </div>
    <component
      :is="timerComponent"
      v-if="timerComponent"
      class="quizably-minimal__timer"
      :quiz="quiz"
      :state="state"
      @expired="onTimerExpired"
    />

    <main class="quizably-minimal__stage">
      <transition
        :name="transitionName ?? 'quizably-min-fade'"
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
    </main>
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
.quizably-minimal {
  width: 100%;
  flex: 1; /* fill height when .quizably-quiz__content is flex column */
  /* --quizably-quiz-surface is set to transparent by Quiz.vue when a background
     image is active, letting the quizably-quiz__bg layer show through. */
  background: var(--quizably-quiz-surface, var(--quizably-quiz-bg, #fff));
  color: var(--quizably-quiz-text);
  position: relative;
}

.quizably-minimal__progress {
  position: fixed;
  top: 0;
  inset-inline: 0;
  z-index: 10;
  padding: 16px 32px;
}

.quizably-minimal__counter {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-4);
}

.quizably-minimal__timer {
  position: fixed;
  top: 16px;
  inset-inline-end: 32px;
  z-index: 11;
}

.quizably-minimal__stage {
  max-width: var(--quizably-card-max-width, 720px);
  min-height: var(--quizably-card-min-height, auto);
  margin: 0 auto;
  padding: 120px 32px 80px;
}

.quizably-screen-wrap {
  width: 100%;
  max-width: var(--quizably-inner-max-width, 100%);
  margin: 0 auto;
}

/* Make screen typography much bolder in Minimal */
.quizably-minimal__stage :deep(.quizably-question__title),
.quizably-minimal__stage :deep(.quizably-intro__title),
.quizably-minimal__stage :deep(.quizably-result__title),
.quizably-minimal__stage :deep(.quizably-form__title),
.quizably-minimal__stage :deep(.quizably-completed__title) {
  font-size: 52px;
  line-height: 1.02;
  letter-spacing: -0.025em;
}

.quizably-minimal__stage :deep(.quizably-question) {
  gap: 24px;
}

.quizably-minimal__stage :deep(.quizably-single__option),
.quizably-minimal__stage :deep(.quizably-multi__option) {
  background: transparent;
  border: none;
  border-bottom: 1px solid var(--border-1);
  border-radius: 0;
  padding: 18px 0;
  font-size: 20px;
  box-shadow: none;
}

.quizably-minimal__stage :deep(.quizably-single__option:hover),
.quizably-minimal__stage :deep(.quizably-multi__option:hover) {
  background: transparent;
  color: var(--quizably-quiz-brand);
}

.quizably-minimal__stage :deep(.quizably-single__option--active),
.quizably-minimal__stage :deep(.quizably-multi__option--active) {
  background: transparent;
  border-color: var(--quizably-quiz-brand);
  color: var(--quizably-quiz-brand);
  box-shadow: none;
}

.quizably-minimal__stage :deep(.quizably-single__letter),
.quizably-minimal__stage :deep(.quizably-multi__letter) {
  background: transparent;
  color: var(--ink-3);
  font-size: 16px;
  font-family: var(--f-display);
  min-width: 28px;
}

.quizably-minimal__stage :deep(.quizably-single__option--active .quizably-single__letter) {
  background: transparent;
  color: var(--quizably-quiz-brand);
}

.quizably-minimal__stage :deep(.quizably-intro__cover),
.quizably-minimal__stage :deep(.quizably-result__image) {
  width: 100%;
  margin: 0 0 20px;
  border-radius: var(--r-md);
  height: 220px;
}

/* Screen transition */
.quizably-min-fade-enter-active,
.quizably-min-fade-leave-active {
  transition: opacity 220ms ease, transform 220ms ease;
}
.quizably-min-fade-enter-from {
  opacity: 0;
  transform: translateY(12px);
}
.quizably-min-fade-leave-to {
  opacity: 0;
  transform: translateY(-12px);
}

@media (max-width: 640px) {
  .quizably-minimal__stage {
    padding: 80px 22px 60px;
  }
  .quizably-minimal__stage :deep(.quizably-question__title),
  .quizably-minimal__stage :deep(.quizably-intro__title),
  .quizably-minimal__stage :deep(.quizably-result__title),
  .quizably-minimal__stage :deep(.quizably-form__title),
  .quizably-minimal__stage :deep(.quizably-completed__title) {
    font-size: 34px;
  }
  .quizably-minimal__stage :deep(.quizably-single__option),
  .quizably-minimal__stage :deep(.quizably-multi__option) {
    font-size: 17px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-min-fade-enter-active,
  .quizably-min-fade-leave-active {
    transition-duration: 0ms;
  }
  .quizably-min-fade-enter-from,
  .quizably-min-fade-leave-to {
    transform: none;
  }
}
</style>
