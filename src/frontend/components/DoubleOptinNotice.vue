<template>
  <div
    v-if="notice && notice.pending.value"
    class="quizably-doi-notice"
    role="status"
  >
    <strong>{{ __('Check your inbox.') }}</strong>
    {{ sprintf(__('We sent a confirmation link to %s. Click it to finish subscribing.'), notice.email.value) }}
  </div>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { inject } from 'vue';

// Provided by Quiz.vue once the lead form was submitted on a quiz with double opt-in.
// It lives inside the result / thank-you screen so every template shows it, including the
// Full Screen overlay that sits above the rest of the page.
const notice = inject('doubleOptinNotice', null);
</script>

<style>
.quizably-doi-notice {
  margin: 0 0 16px;
  padding: 12px 16px;
  background: var(--quizably-quiz-option-bg, var(--bg-subtle));
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  border-radius: var(--r-md, 8px);
  font-size: 14px;
  line-height: 1.5;
  color: var(--quizably-quiz-text, inherit);
  text-align: start;
}
</style>
