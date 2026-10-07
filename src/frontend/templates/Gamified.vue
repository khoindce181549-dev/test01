<template>
  <div class="quizably-gamified">
    <div class="quizably-gam__topbar">
      <!-- Level badge — always visible -->
      <div class="quizably-gam__level">
        <span class="quizably-gam__level-num">{{ currentLevel }}</span>
        <span class="quizably-gam__level-label">LEVEL</span>
      </div>

      <!-- Pip progress bar — centre -->
      <div class="quizably-gam__pips">
        <component
          :is="progressBarComponent"
          v-if="progressBarComponent"
          :quiz="quiz"
          :state="state"
          :current="state.progress.value"
          class="quizably-gam__xp-pro"
        />
        <template v-else>
          <span
            v-for="i in 5"
            :key="i"
            :class="['quizably-gam__pip', { 'is-filled': i <= filledPips }]"
          />
        </template>
      </div>

      <!-- Score badge + optional timer — right -->
      <div class="quizably-gam__right">
        <component
          :is="timerComponent"
          v-if="timerComponent"
          class="quizably-gam__timer"
          :quiz="quiz"
          :state="state"
          @expired="onTimerExpired"
        />
        <div class="quizably-gam__score">
          <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14" aria-hidden="true">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
          </svg>
          <span>{{ state.score?.value ?? 0 }}</span>
        </div>
      </div>
    </div>

    <main class="quizably-gam__stage">
      <transition
        :name="transitionName ?? 'quizably-gam-pop'"
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

const filledPips = computed(() => {
  const screen = props.state.screen.value;
  if (screen === 'result' || screen === 'completed') return 5;
  if (screen !== 'question') return 0;
  const total = props.state.totalQuestions?.value ?? 0;
  const current = (props.state.currentIndex?.value ?? 0) + 1;
  if (total === 0) return 0;
  return Math.max(1, Math.round((current / total) * 5));
});

const currentLevel = computed(() => {
  const screen = props.state.screen.value;
  if (screen === 'result' || screen === 'completed') return 3;
  if (screen !== 'question') return 1;
  const total = props.state.totalQuestions?.value ?? 0;
  const current = props.state.currentIndex?.value ?? 0;
  if (total === 0) return 1;
  return Math.max(1, Math.ceil(((current + 1) / total) * 3));
});

function onTimerExpired() {
  props.emitNav('complete');
}
</script>

<style scoped>
.quizably-gamified {
  width: 100%;
  position: relative;
}

.quizably-gam__topbar {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 20px;
  background: linear-gradient(135deg, var(--quizably-quiz-brand), var(--quizably-quiz-accent, color-mix(in srgb, var(--quizably-quiz-brand) 70%, #000)));
  box-shadow: 0 2px 12px color-mix(in srgb, var(--quizably-quiz-brand) 35%, transparent);
  color: #fff;
}

/* Level badge — left */
.quizably-gam__level {
  display: flex;
  align-items: baseline;
  gap: 5px;
  flex-shrink: 0;
}

.quizably-gam__level-num {
  font-family: var(--f-display);
  font-size: calc(22px * var(--quizably-font-scale, 1));
  font-weight: 700;
  line-height: 1;
  color: #fff;
}

.quizably-gam__level-label {
  font-family: var(--f-mono);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.75);
}

/* Pip progress — centre */
.quizably-gam__pips {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.quizably-gam__pip {
  display: block;
  width: 28px;
  height: 5px;
  border-radius: var(--r-pill);
  background: rgba(255, 255, 255, 0.25);
  transition: background 300ms ease;
}

.quizably-gam__pip.is-filled {
  background: var(--quizably-quiz-surface, #fff);
  box-shadow: 0 0 6px rgba(255, 255, 255, 0.5);
}

/* Right cluster — score badge + optional timer */
.quizably-gam__right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.quizably-gam__score {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(255, 255, 255, 0.18);
  border-radius: var(--r-pill);
  padding: 4px 10px;
  font-family: var(--f-mono);
  font-size: 12px;
  font-weight: 700;
  color: #fff;
}

.quizably-gam__timer {
  flex-shrink: 0;
  color: #fff;
}

.quizably-gam__stage {
  max-width: var(--quizably-card-max-width, 640px);
  min-height: var(--quizably-card-min-height, auto);
  margin: 0 auto;
  padding: 40px 24px 80px;
}

.quizably-screen-wrap {
  width: 100%;
  max-width: var(--quizably-inner-max-width, 100%);
  margin: 0 auto;
}

/* Gamified â€” all screen titles get a punchy editorial scale */
.quizably-gam__stage :deep(.quizably-question__title),
.quizably-gam__stage :deep(.quizably-intro__title),
.quizably-gam__stage :deep(.quizably-result__title),
.quizably-gam__stage :deep(.quizably-form__title),
.quizably-gam__stage :deep(.quizably-completed__title) {
  font-size: calc(36px * var(--quizably-font-scale, 1));
  line-height: 1.08;
  letter-spacing: -0.02em;
}

.quizably-gam__stage :deep(.quizably-result__score) {
  font-size: calc(24px * var(--quizably-font-scale, 1));
  font-weight: 700;
  color: var(--quizably-quiz-brand);
}

/* Cover / result image â€” contained badge-style */
.quizably-gam__stage :deep(.quizably-intro__cover),
.quizably-gam__stage :deep(.quizably-result__image) {
  width: 100%;
  margin: 0 0 20px;
  border-radius: var(--r-lg);
  height: 200px;
  border: 2px solid var(--quizably-quiz-brand);
}

/* Gamified answer option styling â€” bold borders, punchy hover */
.quizably-gam__stage :deep(.quizably-single__option),
.quizably-gam__stage :deep(.quizably-multi__option) {
  border: 2px solid var(--border-2);
  background: var(--quizably-quiz-surface, var(--quizably-quiz-bg, #fff));
  font-size: calc(15px * var(--quizably-font-scale, 1));
  font-weight: 500;
  transition: border-color 100ms, background 100ms, transform 100ms, box-shadow 100ms;
}

.quizably-gam__stage :deep(.quizably-single__option:hover),
.quizably-gam__stage :deep(.quizably-multi__option:hover) {
  border-color: var(--quizably-quiz-brand);
  background: color-mix(in srgb, var(--quizably-quiz-brand) 6%, transparent);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px color-mix(in srgb, var(--quizably-quiz-brand) 20%, transparent);
}

.quizably-gam__stage :deep(.quizably-single__option--active),
.quizably-gam__stage :deep(.quizably-multi__option--active) {
  border-color: var(--quizably-quiz-brand);
  background: color-mix(in srgb, var(--quizably-quiz-brand) 10%, transparent);
  transform: translateY(-2px);
  box-shadow: 0 4px 16px color-mix(in srgb, var(--quizably-quiz-brand) 25%, transparent);
}

.quizably-gam__stage :deep(.quizably-single__letter),
.quizably-gam__stage :deep(.quizably-multi__letter) {
  background: color-mix(in srgb, var(--quizably-quiz-brand) 12%, transparent);
  color: var(--quizably-quiz-brand);
  font-weight: 700;
}

.quizably-gam__stage :deep(.quizably-single__option--active .quizably-single__letter),
.quizably-gam__stage :deep(.quizably-multi__option--active .quizably-multi__letter) {
  background: var(--quizably-quiz-brand);
  color: #fff;
}

/* Screen transition â€” pop-in */
.quizably-gam-pop-enter-active,
.quizably-gam-pop-leave-active {
  transition: opacity 220ms ease, transform 220ms cubic-bezier(0.34, 1.56, 0.64, 1);
}
.quizably-gam-pop-enter-from {
  opacity: 0;
  transform: scale(0.93);
}
.quizably-gam-pop-leave-to {
  opacity: 0;
  transform: scale(1.04);
}

@media (max-width: 640px) {
  .quizably-gam__topbar {
    padding: 10px 14px;
    gap: 10px;
  }
  .quizably-gam__level-num {
    font-size: calc(18px * var(--quizably-font-scale, 1));
  }
  .quizably-gam__pip {
    width: 20px;
  }
  .quizably-gam__stage {
    padding: 24px 16px 60px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-gam-pop-enter-active,
  .quizably-gam-pop-leave-active {
    transition-duration: 0ms;
  }
  .quizably-gam-pop-enter-from,
  .quizably-gam-pop-leave-to {
    transform: none;
  }
  .quizably-gam__stage :deep(.quizably-single__option:hover),
  .quizably-gam__stage :deep(.quizably-multi__option:hover),
  .quizably-gam__stage :deep(.quizably-single__option--active),
  .quizably-gam__stage :deep(.quizably-multi__option--active) {
    transform: none;
  }
  .quizably-gam__xp-fill {
    transition-duration: 0ms;
  }
}
</style>
