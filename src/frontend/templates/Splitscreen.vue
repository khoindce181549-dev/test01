<template>
  <div
    class="quizably-splitscreen"
    :class="[`is-layout-${splitLayout}`, isCustomWidth ? 'is-width-custom' : '', `is-content-${splitContentAlign}`, panelHasScreenImage ? 'is-panel-image-active' : '']"
    :style="rootStyle"
  >
    <!-- Image panel — background-image applied inline so getComputedStyle returns
         url(...) when set and 'none' when absent, matching test expectations. -->
    <div class="quizably-split__image" :style="imagePanelStyle">
      <!-- Brand gradient shown when no photo; sits behind the dark readability overlay -->
      <div v-if="!backgroundImage" class="quizably-split__gradient" aria-hidden="true" />
      <div class="quizably-split__gradient-overlay" aria-hidden="true" />
      <div
        v-if="state.screen.value === 'question' && state.totalQuestions.value > 0"
        class="quizably-split__chip"
      >
        <component
          :is="progressBarComponent"
          v-if="progressBarComponent"
          :quiz="quiz"
          :state="state"
          :current="state.progress.value"
          variant="minimal"
        />
        <span
          v-else
          class="quizably-split__counter"
        >{{ state.currentIndex.value + 1 }} / {{ state.totalQuestions.value }}</span>
      </div>
      <component
        :is="timerComponent"
        v-if="timerComponent"
        class="quizably-split__timer"
        :quiz="quiz"
        :state="state"
        @expired="onTimerExpired"
      />
    </div><!-- /.quizably-split__image -->

    <!-- Content panel -->
    <div class="quizably-split__content">
      <transition
        :name="transitionName ?? 'quizably-split-fade'"
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

const design = computed(() => props.quiz?.design ?? {});
const splitLayout = computed(() => design.value.split_layout || 'image-left');

// Per-screen image — changes with each screen/question transition.
// Drives the panel background and determines whether to suppress the
// duplicate inline image rendered by each screen component.
const screenImage = computed(() => {
  switch (props.state.screen.value) {
    case 'question': return props.state.currentQuestion?.value?.media_url || '';
    case 'intro': {
      const ic = props.quiz?.settings?.screens?.intro_cover || '';
      // No intro cover set — borrow the first question's image so the panel
      // shows something relevant rather than the plain brand gradient.
      return ic || props.state.questions?.value?.[0]?.media_url || '';
    }
    case 'form':     return props.quiz?.settings?.optin?.image_url        || '';
    case 'result':   return props.state.result?.value?.image_url          || '';
    default:         return '';
  }
});

// Falls back to the global quiz background when no per-screen image exists.
const backgroundImage = computed(() => screenImage.value || design.value.background_image || '');

// True when a per-screen image occupies the panel so we can hide the same
// image that each screen component also renders inline.
const panelHasScreenImage = computed(() => !!screenImage.value);

const splitImageWidth   = computed(() => Math.min(70, Math.max(10, Number(design.value.split_image_width) || 42)));
const splitContentAlign = computed(() => design.value.split_content_align || 'center');

const splitHeightValue = computed(() => Number(design.value.split_height_value) || 100);
const splitHeightUnit  = computed(() => design.value.split_height_unit  || 'vh');
const splitWidthValue  = computed(() => Number(design.value.split_width_value)  || 100);
const splitWidthUnit   = computed(() => design.value.split_width_unit   || 'vw');

/* Full-width means 100vw â€” use the breakout CSS; anything else = centered container */
const isCustomWidth = computed(() =>
  !(splitWidthUnit.value === 'vw' && splitWidthValue.value === 100)
);

// Layout vars (--quizably-split-*) are emitted by Quiz.vue on .quizably-quiz and
// cascade down. rootStyle only needs the local gradient colour tokens.
const rootStyle = computed(() => ({
  '--split-primary': design.value.colors?.primary || 'var(--quizably-quiz-brand)',
  '--split-accent':  design.value.colors?.accent  || 'var(--quizably-quiz-accent)',
}));

// Apply background-image directly on .quizably-split__image so that
// getComputedStyle('.quizably-split__image').backgroundImage returns url(...) when
// a photo is set and 'none' when absent (what the E2E tests check).
const imagePanelStyle = computed(() => {
  if (!backgroundImage.value) return {};
  return {
    backgroundImage: `url('${backgroundImage.value}')`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
  };
});

const screenComponent = computed(() => {
  switch (props.state.screen.value) {
    case 'question':  return Question;
    case 'form':      return Form;
    case 'result':    return Result;
    case 'completed': return Completed;
    case 'intro':
    default:          return Intro;
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
/* â”€â”€ Base: full-viewport breakout â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.quizably-splitscreen {
  position: relative;
  inset-inline: 50%;
  margin-inline: -50vw;
  width: 100vw;
  height: var(--quizably-split-height, 100vh);
  overflow: hidden;
  display: grid;
  grid-template-columns: var(--quizably-split-image-width, 42%) 1fr;
  grid-template-rows: 1fr; /* definite row height so height:100% on children resolves correctly */
}

/* Custom width â€” remove breakout, center the container */
.quizably-splitscreen.is-width-custom {
  inset-inline: auto;
  margin-inline: auto;
  width: 100%;
  max-width: var(--quizably-split-max-width, 100%);
  grid-template-rows: 1fr;
}

/* Image-right layout */
.quizably-splitscreen.is-layout-image-right {
  grid-template-columns: 1fr var(--quizably-split-image-width, 42%);
}
.quizably-splitscreen.is-layout-image-right .quizably-split__image { order: 2; }
.quizably-splitscreen.is-layout-image-right .quizably-split__content { order: 1; }

/* â”€â”€ Image panel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.quizably-split__image {
  position: relative;
  height: 100%;
  isolation: isolate;
  overflow: hidden;
  /* background-image applied via inline style (imagePanelStyle) so
     getComputedStyle().backgroundImage accurately reflects presence/absence */
}

/* Brand gradient rendered as absolute child when no photo is set */
.quizably-split__gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg,
    var(--split-primary, var(--quizably-quiz-brand)),
    var(--split-accent,  var(--quizably-quiz-accent)));
  z-index: 0;
}

.quizably-split__gradient-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,.1), rgba(0,0,0,.4));
  z-index: 1;
}

.quizably-split__chip {
  position: absolute;
  bottom: 32px;
  inset-inline-start: 32px;
  z-index: 2;
}

.quizably-split__counter {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 12px;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: rgba(255,255,255,.8);
  background: rgba(0,0,0,.3);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  padding: 6px 12px;
  border-radius: var(--r-pill);
}

.quizably-split__timer {
  position: absolute;
  top: 24px;
  inset-inline-start: 24px;
  z-index: 2;
}

/* â”€â”€ Content panel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.quizably-split__content {
  height: var(--quizably-split-height, 100vh);
  min-height: var(--quizably-card-min-height, auto);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 56px;
  /* Deliberately reads --quizably-quiz-bg, NOT --quizably-quiz-surface. The surface
     variable becomes transparent when a background image is active; the
     content panel must always stay opaque — only the image panel shows the bg. */
  background: var(--quizably-quiz-bg, #fff);
}

.quizably-splitscreen.is-content-top    .quizably-split__content { justify-content: flex-start; }
.quizably-splitscreen.is-content-center .quizably-split__content { justify-content: center; }
.quizably-splitscreen.is-content-bottom .quizably-split__content { justify-content: flex-end; }

.quizably-screen-wrap {
  width: 100%;
  max-width: var(--quizably-inner-max-width, 100%);
  margin: 0 auto;
}

.quizably-split__content :deep(.quizably-question),
.quizably-split__content :deep(.quizably-intro),
.quizably-split__content :deep(.quizably-result),
.quizably-split__content :deep(.quizably-form),
.quizably-split__content :deep(.quizably-completed) {
  max-width: 520px;
  width: 100%;
}

/* When the left panel shows a per-screen image, hide the duplicate inline
   render inside the right content for all screen types. */
.quizably-splitscreen.is-panel-image-active .quizably-split__content :deep(.quizably-question__media),
.quizably-splitscreen.is-panel-image-active .quizably-split__content :deep(.quizably-intro__cover),
.quizably-splitscreen.is-panel-image-active .quizably-split__content :deep(.quizably-form__image),
.quizably-splitscreen.is-panel-image-active .quizably-split__content :deep(.quizably-result__image) {
  display: none;
}

/* Cover / result image */
.quizably-split__content :deep(.quizably-intro__cover),
.quizably-split__content :deep(.quizably-result__image) {
  width: 100%;
  margin: 0 0 20px;
  border-radius: var(--r-lg);
  height: 200px;
}

/* Typography */
.quizably-split__content :deep(.quizably-question__title),
.quizably-split__content :deep(.quizably-intro__title),
.quizably-split__content :deep(.quizably-result__title),
.quizably-split__content :deep(.quizably-form__title),
.quizably-split__content :deep(.quizably-completed__title) {
  font-size: calc(32px * var(--quizably-font-scale, 1));
  line-height: 1.1;
  letter-spacing: -.018em;
}

/* Answer options */
.quizably-split__content :deep(.quizably-single__option:hover),
.quizably-split__content :deep(.quizably-multi__option:hover) {
  border-color: var(--quizably-quiz-brand);
  background: color-mix(in srgb, var(--quizably-quiz-brand) 5%, transparent);
}
.quizably-split__content :deep(.quizably-single__option--active),
.quizably-split__content :deep(.quizably-multi__option--active) {
  border-color: var(--quizably-quiz-brand);
  background: color-mix(in srgb, var(--quizably-quiz-brand) 10%, transparent);
  box-shadow: 0 0 0 1px var(--quizably-quiz-brand);
}
.quizably-split__content :deep(.quizably-single__option--active .quizably-single__letter),
.quizably-split__content :deep(.quizably-multi__option--active .quizably-multi__letter) {
  background: var(--quizably-quiz-brand);
  color: #fff;
}

/* â”€â”€ Transitions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.quizably-split-fade-enter-active,
.quizably-split-fade-leave-active {
  transition: opacity 200ms ease, transform 200ms ease;
}
.quizably-split-fade-enter-from { opacity: 0; transform: translateX(calc(12px * var(--quizably-dir, 1))); }
.quizably-split-fade-leave-to   { opacity: 0; transform: translateX(calc(-12px * var(--quizably-dir, 1))); }

/* â”€â”€ Mobile â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media (max-width: 900px) {
  .quizably-splitscreen,
  .quizably-splitscreen.is-width-custom,
  .quizably-splitscreen.is-layout-image-right {
    grid-template-columns: 1fr;
    inset-inline: auto;
    margin-inline: 0;
    width: 100% !important;
    max-width: none !important;
    height: auto !important;
    overflow: visible !important;
  }

  .quizably-split__image {
    height: 240px !important;
    order: 0 !important;
  }

  .quizably-split__content {
    height: auto !important;
    overflow-y: visible !important;
    padding: 40px 24px;
    order: 1 !important;
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizably-split-fade-enter-active,
  .quizably-split-fade-leave-active { transition-duration: 0ms; }
  .quizably-split-fade-enter-from,
  .quizably-split-fade-leave-to { transform: none; }
}
</style>
