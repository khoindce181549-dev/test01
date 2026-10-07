<template>
  <section
    v-if="visible"
    class="review-prompt"
    data-testid="review-prompt"
    :aria-label="__('Review request')"
  >
    <span
      class="review-prompt__icon"
      aria-hidden="true"
    >
      <svg
        viewBox="0 0 24 24"
        width="20"
        height="20"
        fill="currentColor"
      >
        <path d="M12 2.5l2.94 5.96 6.56.95-4.75 4.63 1.12 6.54L12 17.5l-5.87 3.08 1.12-6.54L2.5 9.41l6.56-.95z" />
      </svg>
    </span>
    <div class="review-prompt__body">
      <p class="review-prompt__title">
        {{ headline }}
      </p>
      <p class="review-prompt__text">
        {{ __('If Quizably has been useful, a quick review on WordPress.org helps other site owners find it — and tells us what to build next.') }}
      </p>
    </div>
    <div class="review-prompt__actions">
      <Button
        variant="primary"
        size="sm"
        :href="prompt.reviewUrl"
        target="_blank"
        rel="noopener"
        @click="answer('reviewed')"
      >
        {{ __('Leave a review') }}
      </Button>
      <Button
        variant="ghost"
        size="sm"
        @click="answer('later')"
      >
        {{ __('Maybe later') }}
      </Button>
      <Button
        variant="ghost"
        size="sm"
        @click="answer('dismiss')"
      >
        {{ __("Don't ask again") }}
      </Button>
    </div>
  </section>
</template>

<script setup>
/**
 * Dashboard review request. Whether it shows, and why, is decided
 * server-side by Quizably\Admin\ReviewPrompt and handed over as
 * QUIZABLY_ADMIN.reviewPrompt (null when it shouldn't show). Any answer hides
 * it immediately; the server stores the answer per user.
 */
import { computed, ref } from 'vue';
import { Button } from '@admin/ui';
import { api } from '@admin/api/client';
import { __, _n, sprintf } from '@shared/i18n';

const prompt = window.QUIZABLY_ADMIN?.reviewPrompt ?? null;
const visible = ref(Boolean(prompt));

const headline = computed(() => {
  if (!prompt) return '';
  if (prompt.trigger === 'leads') {
    return sprintf(
      // translators: %s is the number of leads captured.
      _n(
        'Your quizzes have captured %s lead so far.',
        'Your quizzes have captured %s leads so far.',
        prompt.leads
      ),
      Number(prompt.leads).toLocaleString()
    );
  }
  if (prompt.trigger === 'submissions') {
    return sprintf(
      // translators: %s is the number of completed quiz submissions.
      _n(
        'Your quizzes have been completed %s time so far.',
        'Your quizzes have been completed %s times so far.',
        prompt.submissions
      ),
      Number(prompt.submissions).toLocaleString()
    );
  }
  return __("You've been building with Quizably for a while now.");
});

function answer(action) {
  visible.value = false;
  // Fire-and-forget: if saving fails, the prompt simply shows again on a
  // later visit, which is the safe outcome.
  api.post('review-prompt', { action }).catch(() => {});
}
</script>

<style scoped>
.review-prompt {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  padding: 14px 20px;
  background: var(--brand-tint);
  border: 1px solid var(--brand-bg);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-xs);
}

.review-prompt__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  width: 36px;
  height: 36px;
  border-radius: var(--r-pill);
  background: var(--brand-bg);
  color: var(--brand-hover);
}

.review-prompt__body {
  flex: 1 1 320px;
  min-width: 0;
}

.review-prompt__title {
  font-family: var(--f-display);
  font-weight: 600;
  font-size: 15px;
  line-height: 1.3;
  color: var(--ink-1);
  margin: 0;
}

.review-prompt__text {
  font-family: var(--f-sans);
  font-size: 12.5px;
  line-height: 1.45;
  color: var(--ink-3);
  margin: 2px 0 0;
  max-width: 72ch;
}

.review-prompt__actions {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
</style>
