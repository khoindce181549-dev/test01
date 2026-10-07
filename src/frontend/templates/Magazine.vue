<template>
  <div class="quizably-magazine">
    <header class="quizably-mag__masthead">
      <div class="quizably-mag__masthead-left">
        <!-- Always-visible editorial breadcrumb -->
        <span class="quizably-mag__breadcrumb">{{ mastheadBreadcrumb }}</span>
        <!-- Pro progress bar overrides the breadcrumb position on question screen -->
        <component
          :is="progressBarComponent"
          v-if="progressBarComponent && state.screen.value === 'question'"
          :quiz="quiz"
          :state="state"
          :current="state.progress.value"
          variant="thin"
          class="quizably-mag__pro-bar"
        />
      </div>
      <component
        :is="timerComponent"
        v-if="timerComponent"
        class="quizably-mag__timer"
        :quiz="quiz"
        :state="state"
        @expired="onTimerExpired"
      />
    </header>

    <main class="quizably-mag__stage">
      <transition
        :name="transitionName ?? 'quizably-mag-wipe'"
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

const mastheadBreadcrumb = computed(() => {
  const screen = props.state.screen.value;
  const total = props.state.totalQuestions?.value ?? 0;
  const current = (props.state.currentIndex?.value ?? 0) + 1;
  switch (screen) {
    case 'question': return `QUESTION ${current} OF ${total}`;
    case 'form':     return 'FORM · RESULTS AHEAD';
    case 'result':   return 'YOUR RESULT';
    case 'completed':return 'COMPLETED';
    default:         return `WELCOME · ${total} QUESTION${total !== 1 ? 'S' : ''}`;
  }
});

function onTimerExpired() {
  props.emitNav('complete');
}
</script>

<style scoped>
.quizably-magazine {
  width: 100%;
  position: relative;
}

.quizably-mag__masthead {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 32px;
  background: var(--quizably-quiz-text, #1a1a1a);
  border-bottom: 4px solid var(--quizably-quiz-brand);
  color: #fff;
}

.quizably-mag__masthead-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex: 1;
  min-width: 0;
}

.quizably-mag__breadcrumb {
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.85);
  white-space: nowrap;
}

.quizably-mag__pro-bar {
  flex: 1;
  min-width: 0;
}

.quizably-mag__timer {
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.85);
}

.quizably-mag__stage {
  max-width: var(--quizably-card-max-width, 720px);
  min-height: var(--quizably-card-min-height, auto);
  margin: 0 auto;
  padding: 48px 32px 80px;
}

.quizably-screen-wrap {
  width: 100%;
  max-width: var(--quizably-inner-max-width, 100%);
  margin: 0 auto;
}

/* Cover / result image â€” full-width editorial banner, no bleed needed */
.quizably-mag__stage :deep(.quizably-intro__cover),
.quizably-mag__stage :deep(.quizably-result__image) {
  width: 100%;
  margin: 0 0 24px;
  border-radius: 0;
  height: 280px;
}

/* Form screen inputs - editorial border style */
.quizably-mag__stage :deep(.quizably-form__field input) {
  border-radius: 0;
  border: none;
  border-bottom: 1.5px solid var(--quizably-quiz-text, var(--ink-1));
  background: transparent;
  padding-inline: 0;
}
.quizably-mag__stage :deep(.quizably-form__field input:focus) {
  box-shadow: none;
  border-color: var(--quizably-quiz-brand);
}

/* Magazine â€” editorial typography scale */
.quizably-mag__stage :deep(.quizably-question__title),
.quizably-mag__stage :deep(.quizably-intro__title),
.quizably-mag__stage :deep(.quizably-result__title),
.quizably-mag__stage :deep(.quizably-form__title),
.quizably-mag__stage :deep(.quizably-completed__title) {
  font-size: calc(40px * var(--quizably-font-scale, 1));
  line-height: 1.08;
  letter-spacing: -0.02em;
  font-weight: 500;
}

/* Editorial list layout for answers in Magazine style */
.quizably-mag__stage :deep(.quizably-question) {
  gap: 24px;
}

.quizably-mag__stage :deep(.quizably-single),
.quizably-mag__stage :deep(.quizably-multi) {
  border: 1.5px solid var(--quizably-quiz-text, var(--ink-1));
  border-radius: 0;
  overflow: hidden;
  gap: 0;
}

.quizably-mag__stage :deep(.quizably-single__option),
.quizably-mag__stage :deep(.quizably-multi__option) {
  border-radius: 0;
  border: none;
  border-bottom: 1.5px solid var(--quizably-quiz-text, var(--ink-1));
  background: transparent;
  padding: 18px 20px;
  font-size: calc(16px * var(--quizably-font-scale, 1));
  font-weight: 500;
  box-shadow: none;
  transition: background 120ms, color 120ms;
}

.quizably-mag__stage :deep(.quizably-single__option:last-child),
.quizably-mag__stage :deep(.quizably-multi__option:last-child) {
  border-bottom: none;
}

.quizably-mag__stage :deep(.quizably-single__option:hover),
.quizably-mag__stage :deep(.quizably-multi__option:hover) {
  background: var(--quizably-quiz-text, var(--ink-1));
  color: var(--quizably-quiz-bg, #fff);
}

.quizably-mag__stage :deep(.quizably-single__option--active),
.quizably-mag__stage :deep(.quizably-multi__option--active) {
  background: var(--quizably-quiz-brand);
  color: #fff;
  box-shadow: none;
  border-color: var(--quizably-quiz-brand);
}

.quizably-mag__stage :deep(.quizably-single__letter),
.quizably-mag__stage :deep(.quizably-multi__letter) {
  font-family: var(--f-display);
  font-size: calc(14px * var(--quizably-font-scale, 1));
  background: transparent;
  color: inherit;
  opacity: 0.5;
}

.quizably-mag__stage :deep(.quizably-single__option--active .quizably-single__letter),
.quizably-mag__stage :deep(.quizably-multi__option--active .quizably-multi__letter) {
  opacity: 0.8;
  color: #fff;
  background: transparent;
}

/* Screen transition â€” wipe */
.quizably-mag-wipe-enter-active,
.quizably-mag-wipe-leave-active {
  transition: opacity 200ms ease, transform 200ms ease;
}
.quizably-mag-wipe-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.quizably-mag-wipe-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

@media (max-width: 640px) {
  .quizably-mag__masthead {
    padding: 12px 20px;
  }
  .quizably-mag__breadcrumb {
    font-size: 10px;
  }
  .quizably-mag__stage {
    padding: 32px 20px 60px;
  }
  .quizably-mag__stage :deep(.quizably-question__title),
  .quizably-mag__stage :deep(.quizably-intro__title),
  .quizably-mag__stage :deep(.quizably-result__title),
  .quizably-mag__stage :deep(.quizably-form__title),
  .quizably-mag__stage :deep(.quizably-completed__title) {
    font-size: calc(28px * var(--quizably-font-scale, 1));
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-mag-wipe-enter-active,
  .quizably-mag-wipe-leave-active {
    transition-duration: 0ms;
  }
  .quizably-mag-wipe-enter-from,
  .quizably-mag-wipe-leave-to {
    transform: none;
  }
}
</style>
