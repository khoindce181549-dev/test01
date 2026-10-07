<template>
  <section class="quizably-completed">
    <DoubleOptinNotice />
    <p class="eyebrow">
      {{ __('Thank you') }}
    </p>
    <h2 class="quizably-completed__title">
      {{ __('Your response has been recorded.') }}
    </h2>
    <p class="quizably-completed__desc">
      {{ __('We appreciate you taking the time. You can close this window.') }}
    </p>

    <QuizButton
      variant="outline"
      @click="$emit('nav', 'retake')"
    >
      {{ __('Take again') }}
    </QuizButton>
  </section>
</template>

<script setup>
import { __ } from '@shared/i18n';
import QuizButton from '../components/QuizButton.vue';
import DoubleOptinNotice from '../components/DoubleOptinNotice.vue';

defineProps({
  quiz: { type: Object, default: null },
});

defineEmits(['nav']);
</script>

<style scoped>
.quizably-completed {
  display: flex;
  flex-direction: column;
  gap: 14px;
  align-items: flex-start;
}

.quizably-completed__title {
  font-family: var(--f-display);
  font-size: 36px;
  line-height: 1.1;
  letter-spacing: -0.02em;
  margin: 0;
}

.quizably-completed__desc {
  color: var(--quizably-quiz-text-muted, var(--ink-2));
  font-size: 15px;
  margin: 0;
  max-width: 48ch;
}
</style>
