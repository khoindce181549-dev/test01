<template>
  <div class="quizably-bars">
    <header class="quizably-bars__head">
      <div>
        <h3 class="quizably-bars__title">{{ __('Top quizzes, last 30 days') }}</h3>
        <p class="quizably-bars__sub">{{ __('By completed submissions') }}</p>
      </div>
    </header>

    <div
      v-if="!quizzes.length"
      class="quizably-bars__empty"
    >
      {{ __('No completed submissions in the window.') }}
    </div>

    <ul
      v-else
      class="quizably-bars__list"
    >
      <li
        v-for="(q, i) in quizzes"
        :key="q.id"
        class="quizably-bars__row"
        @click="onClick(q.id)"
        @keydown.enter.prevent="onClick(q.id)"
        @keydown.space.prevent="onClick(q.id)"
        tabindex="0"
        role="button"
      >
        <div class="quizably-bars__row-meta">
          <span class="quizably-bars__rank">{{ i + 1 }}</span>
          <span class="quizably-bars__row-title">{{ q.title || __('Untitled') }}</span>
          <span class="quizably-bars__row-count">{{ q.count.toLocaleString() }}</span>
        </div>
        <div class="quizably-bars__track">
          <div
            class="quizably-bars__fill"
            :style="{ width: barWidthFor(q.count) + '%' }"
          />
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps({
  quizzes: { type: Array, default: () => [] }, // [{ id, title, type, count }]
});

const router = useRouter();

const maxCount = computed(() => Math.max(1, ...props.quizzes.map((q) => Number(q.count) || 0)));

function barWidthFor(count) {
  const ratio = (Number(count) || 0) / maxCount.value;
  // Min 4% so even tiny counts show a visible nub.
  return Math.max(4, Math.round(ratio * 100));
}

function onClick(id) {
  router.push(`/quiz/${id}/overview`);
}
</script>

<style scoped>
.quizably-bars {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.quizably-bars__title {
  margin: 0;
  font-family: var(--f-display, var(--f-sans));
  font-size: 16px;
  font-weight: 600;
  color: var(--ink-1);
}

.quizably-bars__sub {
  margin: 4px 0 0;
  font-size: 12.5px;
  color: var(--ink-3);
}

.quizably-bars__empty {
  padding: 32px 12px;
  text-align: center;
  color: var(--ink-3);
  font-size: 13px;
  border: 1px dashed var(--border-2);
  border-radius: var(--r-sm);
}

.quizably-bars__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.quizably-bars__row {
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 6px;
  outline: none;
  border-radius: var(--r-sm);
  padding: 4px;
  transition: background 150ms;
}

.quizably-bars__row:hover,
.quizably-bars__row:focus-visible {
  background: var(--bg-canvas);
}

.quizably-bars__row-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
}

.quizably-bars__rank {
  width: 22px;
  height: 22px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  font-weight: 600;
  flex: 0 0 auto;
}

.quizably-bars__row-title {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--ink-1);
  font-weight: 500;
}

.quizably-bars__row-count {
  font-family: var(--f-mono);
  font-size: 12px;
  color: var(--ink-2);
  font-weight: 600;
}

.quizably-bars__track {
  height: 8px;
  background: var(--bg-canvas);
  border-radius: var(--r-pill);
  overflow: hidden;
}

.quizably-bars__fill {
  height: 100%;
  background: linear-gradient(90deg, var(--brand) 0%, var(--accent, var(--brand)) 100%);
  border-radius: var(--r-pill);
  transition: width 350ms ease;
}
</style>
