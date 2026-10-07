<template>
  <div
    class="quizably-fullscreen"
    :style="stageStyle"
  >
    <div
      v-if="progressBarComponent || (state.totalQuestions.value > 0 && state.screen.value === 'question')"
      class="quizably-fullscreen__nav"
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
        class="quizably-fullscreen__counter"
      >{{ state.currentIndex.value + 1 }} / {{ state.totalQuestions.value }}</span>
    </div>
    <component
      :is="timerComponent"
      v-if="timerComponent"
      class="quizably-fullscreen__timer"
      :quiz="quiz"
      :state="state"
      @expired="onTimerExpired"
    />

    <main class="quizably-fullscreen__stage">
      <transition
        :name="transitionName ?? 'quizably-fullscreen-fade'"
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
import { fullscreenStage, FULLSCREEN_DEFAULT_TONE } from '@shared/fullscreenStage.js';

const props = defineProps({
  quiz: { type: Object, default: null },
  state: { type: Object, required: true },
  emitNav: { type: Function, required: true },
});

// The stage is the quiz's "Dark tone" (design.colors.text) tinted with its "Brand"
// (--quizably-quiz-brand, which Quiz.vue sets from design.colors.primary): the same
// definition the builder preview and the Design-tab thumbnail paint from, so the
// editor and the live page cannot drift apart again.
const stageStyle = computed(() => {
  const tone = props.quiz?.design?.colors?.text || FULLSCREEN_DEFAULT_TONE;
  return {
    '--quizably-stage-tone': tone,
    '--quizably-stage': fullscreenStage(tone, 'var(--quizably-quiz-brand)'),
  };
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
.quizably-fullscreen {
  position: fixed;
  inset: 0;
  z-index: 99990;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  /* Dark gradient background â€” the defining visual of this template */
  /* Painted from the quiz's Brand + Dark tone (see stageStyle in the script). */
  background: var(--quizably-stage);
  /* Override token-based colours so child screens inherit dark-mode values */
  --quizably-quiz-bg: transparent;
  --quizably-quiz-text: #ffffff;
  --quizably-quiz-text-muted: rgba(255, 255, 255, 0.6);
  /* Re-derive the subtle token too: it's computed from --quizably-quiz-text at the
     .quizably-quiz level (the dark default), so without this override the intro meta
     labels ("QUESTIONS"/"TAKES") and other subtle text render near-black on the
     dark Fullscreen background. */
  --quizably-quiz-text-subtle: rgba(255, 255, 255, 0.6); /* AA on the dark stage; 0.45 was about 4.4:1 */
  --quizably-quiz-surface: rgba(255, 255, 255, 0.08);
  --quizably-quiz-border: rgba(255, 255, 255, 0.14);
  /* Same reason as the subtle token above: the answer-option tokens are derived
     from --quizably-quiz-text at the .quizably-quiz level too (Quiz.vue), so they still
     hold the light-theme values here - option fills and borders came out
     near-invisible on this dark stage for every choice control (single, multiple,
     dropdown, short text, rating tiles). Re-derive them from the white text. */
  --quizably-quiz-option-bg: color-mix(in srgb, var(--quizably-quiz-text) 8%, transparent);
  --quizably-quiz-option-bg-hover: color-mix(in srgb, var(--quizably-quiz-text) 14%, transparent);
  --quizably-quiz-option-border: color-mix(in srgb, var(--quizably-quiz-text) 20%, transparent);
  --quizably-quiz-option-letter-bg: color-mix(in srgb, var(--quizably-quiz-text) 12%, transparent);
  /* Override global ink tokens so child components inherit white text */
  --ink-1: #ffffff;
  --ink-2: rgba(255, 255, 255, 0.82);
  --ink-3: rgba(255, 255, 255, 0.60);
  --ink-4: rgba(255, 255, 255, 0.40);
  --border-1: rgba(255, 255, 255, 0.12);
  --border-2: rgba(255, 255, 255, 0.18);
  color: #fff;
}

.quizably-fullscreen__nav {
  position: sticky;
  top: 0;
  inset-inline: 0;
  z-index: 10;
  padding: 20px 40px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  /* Top fade in the stage's own dark tone, so it never tints the stage another hue. */
  background: linear-gradient(to bottom, color-mix(in srgb, var(--quizably-stage-tone) 70%, transparent) 0%, transparent 100%);
}

.quizably-fullscreen__counter {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: color-mix(in srgb, var(--quizably-quiz-text) 50%, transparent);
}

.quizably-fullscreen__timer {
  position: fixed;
  top: 20px;
  inset-inline-start: 40px;
  z-index: 11;
}

.quizably-fullscreen__stage {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 32px;
  max-width: var(--quizably-card-max-width, 700px);
  min-height: var(--quizably-card-min-height, auto);
  margin: 0 auto;
  width: 100%;
}

.quizably-screen-wrap {
  width: 100%;
  max-width: var(--quizably-inner-max-width, 100%);
  margin: 0 auto;
}

.quizably-fullscreen__stage :deep(.quizably-question__title),
.quizably-fullscreen__stage :deep(.quizably-intro__title),
.quizably-fullscreen__stage :deep(.quizably-result__title),
.quizably-fullscreen__stage :deep(.quizably-form__title),
.quizably-fullscreen__stage :deep(.quizably-completed__title) {
  font-size: calc(44px * var(--quizably-font-scale, 1));
  line-height: 1.08;
  letter-spacing: -0.02em;
}

.quizably-fullscreen__stage :deep(.quizably-single__option),
.quizably-fullscreen__stage :deep(.quizably-multi__option) {
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

/* Dark-mode form inputs */
.quizably-fullscreen__stage :deep(.quizably-form__field input) {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.2);
  color: #fff;
}
.quizably-fullscreen__stage :deep(.quizably-form__field input::placeholder) {
  color: rgba(255, 255, 255, 0.4);
}
.quizably-fullscreen__stage :deep(.quizably-form__field input:focus) {
  border-color: var(--quizably-quiz-brand);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--quizably-quiz-brand) 30%, transparent);
  background: rgba(255, 255, 255, 0.1);
}
.quizably-fullscreen__stage :deep(.quizably-form__consent) {
  color: rgba(255, 255, 255, 0.75);
}

/* Cover / result image â€” contained, no bleed on fullscreen dark bg */
.quizably-fullscreen__stage :deep(.quizably-intro__cover),
.quizably-fullscreen__stage :deep(.quizably-result__image) {
  width: 100%;
  margin: 0 0 20px;
  border-radius: var(--r-lg);
  height: 220px;
}

/* Force all child text to inherit white on the dark background */
.quizably-fullscreen :deep(*) {
  color: inherit;
}
.quizably-fullscreen :deep(h1),
.quizably-fullscreen :deep(h2),
.quizably-fullscreen :deep(h3),
.quizably-fullscreen :deep(p),
.quizably-fullscreen :deep(span),
.quizably-fullscreen :deep(label),
.quizably-fullscreen :deep(.quizably-result__title),
.quizably-fullscreen :deep(.quizably-intro__title),
.quizably-fullscreen :deep(.quizably-question__title),
.quizably-fullscreen :deep(.quizably-completed__title) {
  color: #fff;
}
.quizably-fullscreen :deep(.quizably-result__eyebrow),
.quizably-fullscreen :deep(.quizably-result__score),
.quizably-fullscreen :deep(.quizably-result__score-text) {
  color: var(--quizably-quiz-brand, #f97316);
}
.quizably-fullscreen :deep(.quizably-share__label) {
  color: rgba(255, 255, 255, 0.6);
}

/* Screen transition */
.quizably-fullscreen-fade-enter-active,
.quizably-fullscreen-fade-leave-active {
  transition: opacity 250ms ease, transform 250ms ease;
}
.quizably-fullscreen-fade-enter-from {
  opacity: 0;
  transform: scale(0.97);
}
.quizably-fullscreen-fade-leave-to {
  opacity: 0;
  transform: scale(1.03);
}

@media (max-width: 640px) {
  .quizably-fullscreen__nav {
    padding: 16px 24px;
  }
  .quizably-fullscreen__timer {
    inset-inline-start: 24px;
  }
  .quizably-fullscreen__stage {
    padding: 70px 22px 40px;
  }
  .quizably-fullscreen__stage :deep(.quizably-question__title),
  .quizably-fullscreen__stage :deep(.quizably-intro__title),
  .quizably-fullscreen__stage :deep(.quizably-result__title),
  .quizably-fullscreen__stage :deep(.quizably-form__title),
  .quizably-fullscreen__stage :deep(.quizably-completed__title) {
    font-size: calc(30px * var(--quizably-font-scale, 1));
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-fullscreen-fade-enter-active,
  .quizably-fullscreen-fade-leave-active {
    transition-duration: 0ms;
  }
  .quizably-fullscreen-fade-enter-from,
  .quizably-fullscreen-fade-leave-to {
    transform: none;
  }
}
</style>
