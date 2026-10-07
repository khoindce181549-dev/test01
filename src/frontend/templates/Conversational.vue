<template>
  <div class="quizably-conversational">
    <div class="quizably-conv__progress-bar">
      <component
        :is="progressBarComponent"
        v-if="progressBarComponent"
        :quiz="quiz"
        :state="state"
        :current="state.progress.value"
        variant="thin"
      />
      <div
        v-else-if="state.screen.value === 'question' && state.totalQuestions.value > 0"
        class="quizably-conv__progress-track"
      >
        <div
          class="quizably-conv__progress-fill"
          :style="{ width: progressWidth }"
        />
      </div>
    </div>
    <component
      :is="timerComponent"
      v-if="timerComponent"
      class="quizably-conv__timer"
      :quiz="quiz"
      :state="state"
      @expired="onTimerExpired"
    />

    <main class="quizably-conv__stage">
      <transition
        :name="transitionName ?? 'quizably-conv-fade'"
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

const progressWidth = computed(() => {
  const total = props.state.totalQuestions?.value ?? 0;
  const current = props.state.currentIndex?.value ?? 0;
  if (total === 0) return '0%';
  return `${Math.round(((current + 1) / total) * 100)}%`;
});

function onTimerExpired() {
  props.emitNav('complete');
}
</script>

<style scoped>
.quizably-conversational {
  width: 100%;
  position: relative;
}

.quizably-conv__progress-bar {
  position: fixed;
  top: 0;
  inset-inline: 0;
  z-index: 10;
}

.quizably-conv__progress-track {
  height: 3px;
  background: var(--border-1);
}

.quizably-conv__progress-fill {
  height: 100%;
  background: var(--quizably-quiz-brand);
  transition: width 300ms ease;
}

.quizably-conv__timer {
  position: fixed;
  top: 14px;
  inset-inline-end: 24px;
  z-index: 11;
}

.quizably-conv__stage {
  max-width: var(--quizably-card-max-width, 640px);
  min-height: var(--quizably-card-min-height, auto);
  margin: 0 auto;
  padding: 64px 24px 80px;
}

.quizably-screen-wrap {
  width: 100%;
  max-width: var(--quizably-inner-max-width, 100%);
  margin: 0 auto;
}

/* Conversational style â€” all screen headings look like chat message bubbles */
.quizably-conv__stage :deep(.quizably-question__title),
.quizably-conv__stage :deep(.quizably-intro__title),
.quizably-conv__stage :deep(.quizably-result__title),
.quizably-conv__stage :deep(.quizably-form__title),
.quizably-conv__stage :deep(.quizably-completed__title) {
  position: relative;
  display: inline-block;
  background: var(--quizably-quiz-brand);
  color: #fff;
  padding: 20px 24px;
  border-radius: var(--r-xl);
  border-start-start-radius: 0;
  margin-bottom: 8px;
  font-size: calc(20px * var(--quizably-font-scale, 1));
  line-height: 1.4;
  letter-spacing: -0.005em;
  max-width: 90%;
  box-shadow: 0 4px 16px color-mix(in srgb, var(--quizably-quiz-brand) 30%, transparent);
}

/* Cover / result image â€” contained, no bleed */
.quizably-conv__stage :deep(.quizably-intro__cover),
.quizably-conv__stage :deep(.quizably-result__image) {
  width: 100%;
  margin: 0 0 20px;
  border-radius: var(--r-lg);
  height: 200px;
}

/* Form screen inputs styled to match conversational theme */
.quizably-conv__stage :deep(.quizably-form__field input) {
  border-radius: var(--r-xl);
  border: 1.5px solid var(--border-2);
  background: var(--quizably-quiz-surface, var(--quizably-quiz-bg, #fff));
}
.quizably-conv__stage :deep(.quizably-form__field input:focus) {
  border-color: var(--quizably-quiz-brand);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--quizably-quiz-brand) 15%, transparent);
}

.quizably-conv__stage :deep(.quizably-question) {
  gap: 20px;
}

/* Answer choices as reply chips */
.quizably-conv__stage :deep(.quizably-single__option),
.quizably-conv__stage :deep(.quizably-multi__option) {
  border-radius: var(--r-xl);
  border: 1.5px solid var(--border-2);
  background: var(--quizably-quiz-surface, var(--quizably-quiz-bg, #fff));
  padding: 14px 20px;
  font-size: calc(15px * var(--quizably-font-scale, 1));
  transition: border-color 120ms, background 120ms, transform 120ms;
}

.quizably-conv__stage :deep(.quizably-single__option:hover),
.quizably-conv__stage :deep(.quizably-multi__option:hover) {
  border-color: var(--quizably-quiz-brand);
  transform: translateX(calc(4px * var(--quizably-dir, 1)));
}

.quizably-conv__stage :deep(.quizably-single__option--active),
.quizably-conv__stage :deep(.quizably-multi__option--active) {
  border-color: var(--quizably-quiz-brand);
  background: color-mix(in srgb, var(--quizably-quiz-brand) 8%, transparent);
  transform: translateX(calc(4px * var(--quizably-dir, 1)));
}

.quizably-conv__stage :deep(.quizably-single__letter),
.quizably-conv__stage :deep(.quizably-multi__letter) {
  display: none;
}

/* Screen transition */
.quizably-conv-fade-enter-active,
.quizably-conv-fade-leave-active {
  transition: opacity 220ms ease, transform 220ms ease;
}
.quizably-conv-fade-enter-from {
  opacity: 0;
  transform: translateY(16px);
}
.quizably-conv-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@media (max-width: 640px) {
  .quizably-conv__stage {
    padding: 50px 16px 60px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-conv-fade-enter-active,
  .quizably-conv-fade-leave-active {
    transition-duration: 0ms;
  }
  .quizably-conv-fade-enter-from,
  .quizably-conv-fade-leave-to {
    transform: none;
  }
  .quizably-conv__stage :deep(.quizably-single__option:hover),
  .quizably-conv__stage :deep(.quizably-multi__option:hover),
  .quizably-conv__stage :deep(.quizably-single__option--active),
  .quizably-conv__stage :deep(.quizably-multi__option--active) {
    transform: none;
  }
}
</style>
