<template>
  <section
    class="quizably-resume"
    aria-labelledby="quizably-resume-title"
    aria-describedby="quizably-resume-desc"
  >
    <p class="eyebrow">
      {{ __('Welcome back') }}
    </p>
    <h2
      id="quizably-resume-title"
      class="quizably-resume__title"
    >
      {{ __('Resume where you left off?') }}
    </h2>
    <p
      id="quizably-resume-desc"
      class="quizably-resume__desc"
    >
      {{ description }}
    </p>
    <div class="quizably-resume__actions">
      <QuizButton
        ref="resumeBtn"
        variant="primary"
        size="lg"
        :disabled="busy"
        @click="$emit('resume')"
      >
        {{ __('Resume') }}
      </QuizButton>
      <QuizButton
        variant="outline"
        size="lg"
        :disabled="busy"
        @click="$emit('start-over')"
      >
        {{ __('Start over') }}
      </QuizButton>
    </div>
  </section>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
/**
 * Resume prompt - shown by Quiz.vue in place of the template when a saved,
 * still-valid session exists. Rendered at the Quiz.vue level (not per
 * template) so it looks right in all templates, Full Screen and
 * Conversational included, and inherits the quiz's colours, fonts and
 * background through the same CSS variables the other screens use.
 */
import { computed, nextTick, onMounted, ref } from 'vue';
import QuizButton from '../components/QuizButton.vue';

const props = defineProps({
  // Saved position: { screen: 'question'|'form', index: number }
  offer: { type: Object, default: null },
  total: { type: Number, default: 0 },
  busy: { type: Boolean, default: false },
});

defineEmits(['resume', 'start-over']);

const resumeBtn = ref(null);

const description = computed(() => {
  const o = props.offer;
  if (o?.screen === 'question' && props.total > 0) {
    // translators: 1: current question number, 2: total number of questions.
    return sprintf(__('You were on question %1$d of %2$d. Pick up from there, or begin again.'), o.index + 1, props.total);
  }
  return __('You have a quiz in progress. Pick up from there, or begin again.');
});

// Move keyboard / screen-reader focus to the primary action so the choice is
// announced and reachable without tabbing through the page.
onMounted(async () => {
  await nextTick();
  const el = resumeBtn.value?.$el;
  if (el && typeof el.focus === 'function') el.focus({ preventScroll: true });
});
</script>

<style scoped>
.quizably-resume {
  display: flex;
  flex-direction: column;
  gap: 14px;
  align-items: flex-start;
  width: 100%;
  max-width: var(--quizably-inner-max-width, 640px);
  margin: 0 auto;
  padding: 32px 24px;
  box-sizing: border-box;
  font-family: var(--f-sans);
}

.quizably-resume__title {
  font-family: var(--f-display);
  font-size: 32px;
  line-height: 1.1;
  letter-spacing: -0.02em;
  margin: 0;
}

.quizably-resume__desc {
  color: var(--quizably-quiz-text-muted, var(--ink-2));
  font-size: 15px;
  margin: 0;
  max-width: 48ch;
}

.quizably-resume__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 6px;
}
</style>
