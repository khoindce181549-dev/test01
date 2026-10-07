<template>
  <div
    class="quizably-rating"
    :class="`quizably-rating--${displayStyle}`"
    role="radiogroup"
    :aria-label="question.title"
    @keydown="onKeydown"
  >
    <button
      v-for="n in max"
      :key="n"
      ref="itemEls"
      type="button"
      role="radio"
      :aria-checked="current === n"
      :aria-label="sprintf(_x('%1$d of %2$d', 'rating value out of maximum'), n, max)"
      :class="[
        'quizably-rating__item',
        { 'quizably-rating__item--filled': isFilled(n), 'quizably-rating__item--selected': current === n },
      ]"
      :tabindex="isFocusable(n) ? 0 : -1"
      @mouseenter="hover = n"
      @mouseleave="hover = 0"
      @click="select(n)"
    >
      <svg
        v-if="displayStyle === 'stars'"
        class="quizably-rating__star"
        viewBox="0 0 24 24"
        width="34"
        height="34"
        aria-hidden="true"
      >
        <path
          d="M12 2.5l3 6.4 7 .9-5.1 4.7 1.3 7L12 17.9l-6.2 3.6 1.3-7L2 9.8l7-.9z"
          fill="currentColor"
        />
      </svg>
      <span
        v-else-if="displayStyle === 'emoji'"
        class="quizably-rating__emoji"
        aria-hidden="true"
      >{{ emojiFor(n) }}</span>
      <span
        v-else
        class="quizably-rating__number"
        aria-hidden="true"
      >{{ n }}</span>
    </button>
  </div>
</template>

<script setup>
import { _x, sprintf } from '@shared/i18n';
import { computed, nextTick, ref } from 'vue';
import { arrowStep } from '@shared/direction.js';
import { RATING_STYLES, ratingEmoji } from '@shared/questionTypes.js';

/**
 * Rating - the visitor picks a score from 1 to N as stars, numbers or faces.
 *
 * The answer is a VALUE (a number saved as text): it is not one of the
 * question's answers and does not affect the score or the result. It does not
 * auto-advance, so a mis-click on a 10-step scale can be corrected before the
 * visitor presses Next.
 *
 * Reads what the editor saves: `settings.rating.max` (default 5) and
 * `settings.rating.style` ('stars' | 'numbers' | 'emoji', default 'stars').
 * Keyboard: arrows, Home/End, or a digit key (0 = 10).
 */
const props = defineProps({
  question: { type: Object, required: true },
  value: { type: [Number, String, null], default: null },
  // Accepted for the shared question-component contract; intentionally unused.
  autoAdvance: { type: Boolean, default: true },
});

const emit = defineEmits(['update:value', 'advance']);

const itemEls = ref([]);
const hover = ref(0); // cosmetic only: previews the fill under the mouse

const config = computed(() => {
  const s = props.question?.settings?.rating;
  return s && typeof s === 'object' ? s : {};
});

const max = computed(() => {
  const n = Math.floor(Number(config.value.max));
  return Number.isFinite(n) && n >= 2 && n <= 10 ? n : 5;
});

const displayStyle = computed(() => (RATING_STYLES.includes(config.value.style) ? config.value.style : 'stars'));

const current = computed(() => {
  const n = Number(props.value);
  return Number.isInteger(n) && n >= 1 && n <= max.value ? n : null;
});

// Stars fill up to the pick (or the hovered star); numbers and faces mark
// only the one picked.
function isFilled(n) {
  if (displayStyle.value === 'stars') return n <= (hover.value || current.value || 0);
  return current.value === n;
}

// One tab stop for the whole group (roving tabindex): the picked item, else the first.
function isFocusable(n) {
  return current.value === null ? n === 1 : current.value === n;
}

const emojiFor = (n) => ratingEmoji(n, max.value);

function select(n) {
  emit('update:value', n);
}

function onKeydown(event) {
  const cur = current.value ?? 0;
  let next = null;

  // ArrowLeft/ArrowRight follow the reading direction (RTL: ArrowLeft = higher).
  const step = event.key === 'ArrowUp' ? 1 : event.key === 'ArrowDown' ? -1 : arrowStep(event);
  if (step === 1) next = Math.min(max.value, cur + 1);
  else if (step === -1) next = Math.max(1, cur - 1);
  else if (event.key === 'Home') next = 1;
  else if (event.key === 'End') next = max.value;
  else if (/^[0-9]$/.test(event.key)) {
    const digit = event.key === '0' ? 10 : Number(event.key);
    if (digit >= 1 && digit <= max.value) next = digit;
  }

  if (next === null) return;
  event.preventDefault();
  select(next);
  // Keep the single tab stop on the newly picked item.
  nextTick(() => itemEls.value[next - 1]?.focus());
}
</script>

<style scoped>
.quizably-rating {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.quizably-rating__item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  background: transparent;
  border: 0;
  color: var(--quizably-quiz-option-border, var(--border-3));
  cursor: pointer;
  transition: color 120ms ease, transform 120ms ease, background-color 140ms ease, border-color 140ms ease;
}

.quizably-rating__item:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  border-radius: var(--r-md);
}

/* ── Stars ── */
.quizably-rating--stars .quizably-rating__item {
  width: 40px;
  height: 40px;
  border-radius: var(--r-md);
}

.quizably-rating--stars .quizably-rating__item--filled {
  color: var(--quizably-quiz-rating, #f5a623);
}

.quizably-rating--stars .quizably-rating__item:hover {
  transform: scale(1.12);
}

/* ── Numbers and faces: a tile per step, the picked one highlighted ── */
.quizably-rating--numbers .quizably-rating__item,
.quizably-rating--emoji .quizably-rating__item {
  min-width: 46px;
  height: 46px;
  color: var(--quizably-quiz-text);
  background: var(--quizably-quiz-option-bg, var(--bg-surface));
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  border-radius: var(--quizably-btn-radius, var(--r-lg));
}

.quizably-rating--numbers .quizably-rating__item:hover,
.quizably-rating--emoji .quizably-rating__item:hover {
  border-color: var(--quizably-quiz-brand);
  background: var(--quizably-quiz-option-bg-hover, var(--bg-subtle));
}

.quizably-rating--numbers .quizably-rating__item--selected,
.quizably-rating--emoji .quizably-rating__item--selected {
  border-color: var(--quizably-quiz-brand);
  /* Brand-tinted, not a fixed pale orange: see SingleChoice.vue. */
  background: color-mix(in srgb, var(--quizably-quiz-brand) 10%, transparent);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.quizably-rating__number {
  font-family: var(--f-mono);
  font-size: 15px;
  font-weight: 600;
}

.quizably-rating__emoji {
  font-size: 24px;
  line-height: 1;
}

@media (prefers-reduced-motion: reduce) {
  .quizably-rating__item {
    transition-duration: 0ms;
  }

  .quizably-rating--stars .quizably-rating__item:hover {
    transform: none;
  }
}
</style>
