<template>
  <div class="results-split results-tab">
    <aside class="results-left">
      <div class="r-list-head">
        <span>{{ __('Results') }}</span>
        <span>{{ totalLabel }}</span>
      </div>

      <div
        v-if="!store.results.length"
        class="results-left__empty"
      >
        <p>{{ __('No results yet.') }}</p>
        <p class="results-left__empty-hint">
          {{ __('Click the button below to add your first one.') }}
        </p>
      </div>

      <div class="results-left__list">
        <ResultListItem
          v-for="(r, i) in store.results"
          :key="r.id"
          :result="r"
          :position="i + 1"
          :active="store.activeResultId === r.id"
          :quiz-type="quizType"
          :mapped-count="mappedCountFor(r.id)"
          @select="store.setActiveResult(r.id)"
          @delete="onDelete(r)"
        />
      </div>

      <div class="results-left__add">
        <button
          type="button"
          class="r-add"
          :disabled="creating || atSingleResultLimit"
          @click="onAddResult"
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M12 5v14M5 12h14" />
          </svg>
          {{ creating ? __('Adding…') : addButtonLabel }}
        </button>
      </div>
    </aside>

    <section class="results-right">
      <div
        v-if="!store.activeResult"
        class="r-hero"
      >
        <div class="r-hero__card">
          <div class="r-hero__content">
            <div class="r-hero__eyebrow">
              <span>{{ __('Results') }}</span>
            </div>

            <h2 class="r-hero__title">
              {{ heroCopy.title }}
            </h2>

            <p class="r-hero__lede">
              {{ heroCopy.lede }}
            </p>

            <ul class="r-hero__list">
              <li>
                <span
                  class="r-hero__check"
                  aria-hidden="true"
                />
                {{ heroCopy.bullet1 }}
              </li>
              <li>
                <span
                  class="r-hero__check"
                  aria-hidden="true"
                />
                {{ heroCopy.bullet2 }}
              </li>
              <li>
                <span
                  class="r-hero__check"
                  aria-hidden="true"
                />
                {{ heroCopy.bullet3 }}
              </li>
            </ul>

            <div class="r-hero__cta">
              <Button
                variant="primary"
                :loading="creating"
                :disabled="atSingleResultLimit"
                @click="onAddResult"
              >
                {{ heroCopy.cta }}
              </Button>
              <span class="r-hero__sub">{{ heroCopy.sub }}</span>
            </div>
          </div>

          <div class="r-hero__visual">
            <svg
              viewBox="0 0 280 200"
              aria-hidden="true"
            >
              <rect
                x="10"
                y="10"
                width="260"
                height="180"
                rx="12"
                class="r-hero__card-bg"
              />
              <rect
                x="22"
                y="24"
                width="60"
                height="14"
                rx="3"
                class="r-hero__chip"
              />
              <text
                x="52"
                y="34"
                text-anchor="middle"
                class="r-hero__chip-label"
              >{{ __('Result') }}</text>

              <text
                x="22"
                y="62"
                class="r-hero__heading"
              >{{ __("You're an Adventurer") }}</text>

              <rect
                x="22"
                y="76"
                width="236"
                height="6"
                rx="3"
                class="r-hero__line"
              />
              <rect
                x="22"
                y="88"
                width="200"
                height="6"
                rx="3"
                class="r-hero__line"
              />
              <rect
                x="22"
                y="100"
                width="160"
                height="6"
                rx="3"
                class="r-hero__line"
              />

              <rect
                x="22"
                y="124"
                width="100"
                height="32"
                rx="8"
                class="r-hero__cta-shape"
              />
              <text
                x="72"
                y="144"
                text-anchor="middle"
                class="r-hero__cta-label"
              >{{ __('Shop my match') }}</text>

              <rect
                x="22"
                y="170"
                width="120"
                height="4"
                rx="2"
                class="r-hero__line r-hero__line--soft"
              />
            </svg>
          </div>
        </div>
      </div>
      <ResultEditor
        v-else
        :key="store.activeResult.id"
        :result="store.activeResult"
        :quiz-type="quizType"
        :questions="store.questions"
        :position="activePosition"
        :total="store.results.length"
      />
    </section>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { __, _n, sprintf } from '@shared/i18n';
import { Button, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import ResultListItem from './ResultListItem.vue';
import ResultEditor from './ResultEditor.vue';

const store = useQuizBuilderStore();
const toast = useToast();

const creating = ref(false);

const quizType = computed(() => store.quiz?.type ?? 'personality');

// Survey + poll only need a single result screen.
const atSingleResultLimit = computed(
  () => ['survey', 'poll'].includes(quizType.value) && store.results.length >= 1
);

const totalLabel = computed(() => {
  const n = store.results.length;
  // translators: %d: total number of results.
  return sprintf(__('%d total'), n);
});

const activePosition = computed(() => {
  const id = store.activeResultId;
  const idx = store.results.findIndex((r) => r.id === id);
  return idx === -1 ? 1 : idx + 1;
});

const addButtonLabel = computed(() => {
  if (atSingleResultLimit.value) return __('Single result');
  return __('Add result');
});

const heroCopy = computed(() => {
  const noResults = store.results.length === 0;
  if (quizType.value === 'trivia') {
    return noResults
      ? {
          title: __('Add your first score tier'),
          lede: __('Trivia quizzes tier visitors by score. Add one result per band — e.g. Beginner 0–3, Pro 7–10.'),
          bullet1: __('Each tier shows different copy, image, and CTA'),
          bullet2: __('Set min/max score in the right rail'),
          bullet3: __('Visitors land in the tier their score falls into'),
          cta: __('Create first tier'),
          sub: __('You can add as many tiers as you need'),
        }
      : {
          title: __('Pick a tier to edit'),
          lede: __('Click any tier on the left to customize its copy, image, and CTA, or add a new tier.'),
          bullet1: __('Customize each tier independently'),
          bullet2: __('Cover the full score range without gaps'),
          bullet3: __('Drag to reorder, click to edit'),
          cta: __('Add another tier'),
          // translators: %d: number of tiers.
          sub: sprintf(_n('%d tier so far', '%d tiers so far', store.results.length), store.results.length),
        };
  }
  if (quizType.value === 'personality') {
    return noResults
      ? {
          title: __('Add your first result'),
          lede: __('Personality quizzes match each visitor to one of several results based on their answers. Create one result per personality.'),
          bullet1: __('Each result has its own title, content, image, and CTA'),
          bullet2: __('Map answers in the Questions tab to point at results'),
          bullet3: __('Add as many results as your quiz needs'),
          cta: __('Create first result'),
          sub: __('Add as many results as your quiz needs'),
        }
      : {
          title: __('Pick a result to edit'),
          lede: __('Click any result on the left to customize its content, or add a new one.'),
          bullet1: __('Customize content, image, and CTA per result'),
          bullet2: __('Track which answers map to each result'),
          bullet3: __('Drag to reorder, click to edit'),
          cta: __('Add another result'),
          // translators: %d: number of results.
          sub: sprintf(_n('%d result so far', '%d results so far', store.results.length), store.results.length),
        };
  }
  return noResults
    ? {
        title: __('Add the thank-you screen'),
        lede: __('Surveys and polls show a single thank-you screen after submission. Customize what visitors see when they finish.'),
        bullet1: __('One screen for all visitors'),
        bullet2: __('Optional CTA, redirect, or hero image'),
        bullet3: __('Skip this if you only need a confirmation'),
        cta: __('Add thank-you screen'),
        sub: __('A single screen is enough for surveys and polls'),
      }
    : {
        title: __('Edit your thank-you screen'),
        lede: __('Click the screen on the left to customize its copy, image, and CTA.'),
        bullet1: __('One screen shown to every visitor'),
        bullet2: __('Add an image, CTA, or redirect'),
        bullet3: __('Update copy any time'),
        cta: __('Edit screen'),
        sub: __('Single thank-you screen for all visitors'),
      };
});

function mappedCountFor(resultId) {
  let count = 0;
  (store.questions || []).forEach((q) => {
    (q.answers || []).forEach((a) => {
      if (a.personality_result_id === resultId) count += 1;
    });
  });
  return count;
}

function seedResultPayload() {
  const count = store.results.length;
  const position = count + 1;
  if (quizType.value === 'trivia') {
    return {
      // translators: %d: tier number.
      title: sprintf(__('Tier %d'), position),
      content: '',
      position,
      score_min: 0,
      score_max: 0,
    };
  }
  return {
    // translators: %d: result number.
    title: sprintf(__('Result %d'), position),
    content: '',
    position,
  };
}

async function onAddResult() {
  if (atSingleResultLimit.value) {
    toast.push({
      variant: 'info',
      title: __('Single result'),
      message: __('Surveys and polls only need one result screen.'),
    });
    return;
  }
  creating.value = true;
  try {
    const r = await store.createResult(seedResultPayload());
    if (r?.id) store.setActiveResult(r.id);
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not add result'),
      message: e.message || __('Please try again.'),
    });
  } finally {
    creating.value = false;
  }
}

function stripHtml(html) {
  return (html || '').replace(/<[^>]*>/g, '').trim();
}

async function onDelete(result) {
  const label = stripHtml(result.title) || __('this result');
  // translators: %s: result title.
  if (!window.confirm(sprintf(__('Delete “%s”? This cannot be undone.'), label))) return;
  try {
    await store.deleteResult(result.id);
    toast.push({ variant: 'info', title: __('Result deleted') });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not delete'),
      message: e.message || __('Please try again.'),
    });
  }
}
</script>

<style scoped>
.results-split {
  display: grid;
  grid-template-columns: 320px 1fr;
  grid-template-rows: minmax(0, 1fr);
  height: 100%;
  min-height: 0;
}

.results-left {
  background: var(--bg-subtle);
  border-inline-end: 1px solid var(--border-1);
  padding: 16px 14px 24px;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
}

.results-left__list {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

.results-left__empty {
  padding: 24px 12px;
  text-align: center;
  color: var(--ink-4);
  font-size: 13px;
}

.results-left__empty p {
  margin: 0;
}

.results-left__empty-hint {
  margin-top: 6px !important;
  font-size: 12px;
  color: var(--ink-4);
}

.results-left__add {
  position: relative;
  margin-top: 4px;
}

.r-list-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 8px 14px;
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
}

.r-list-head > span:last-child {
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 400;
  color: var(--ink-3);
}

.r-add {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 10px;
  background: var(--bg-surface);
  border: 1px dashed var(--border-3);
  border-radius: var(--r-sm);
  font-size: 12.5px;
  font-weight: 500;
  color: var(--ink-3);
  cursor: pointer;
  transition: all 150ms ease;
  font-family: inherit;
}

.r-add:hover:not(:disabled) {
  border-color: var(--brand);
  color: var(--brand);
  background: var(--brand-tint);
}

.r-add:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.r-add svg {
  width: 14px;
  height: 14px;
}

.results-right {
  background: var(--bg-canvas);
  overflow: hidden;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

/* Hero — same shape as the Questions hero but tinted indigo. */
.r-hero {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  overflow-y: auto;
}

.r-hero__card {
  width: 100%;
  max-width: 880px;
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  align-items: center;
  gap: 24px;
  padding: 22px 26px;
  background:
    linear-gradient(135deg, var(--brand-tint) 0%, var(--bg-surface) 65%);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-sm);
}

.r-hero__content {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.r-hero__eyebrow {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-3);
  font-weight: 500;
  margin-bottom: 2px;
}

.r-hero__title {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 22px;
  line-height: 1.2;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
}

.r-hero__lede {
  font-family: var(--f-sans);
  font-size: 13.5px;
  line-height: 1.55;
  color: var(--ink-2);
  margin: 0;
  max-width: 52ch;
}

.r-hero__list {
  list-style: none;
  margin: 6px 0 4px;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.r-hero__list li {
  display: flex;
  align-items: center;
  gap: 9px;
  font-family: var(--f-sans);
  font-size: 12.5px;
  line-height: 1.5;
  color: var(--ink-2);
}

.r-hero__check {
  flex-shrink: 0;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--success-bg) url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 8.5l3 3 6-7' stroke='%23059669' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round' fill='none'/%3E%3C/svg%3E")
    center / 10px no-repeat;
}

.r-hero__cta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.r-hero__sub {
  font-family: var(--f-sans);
  font-size: 11.5px;
  color: var(--ink-3);
}

.r-hero__visual {
  display: flex;
  justify-content: center;
  align-items: center;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  padding: 10px;
  min-width: 0;
}

.r-hero__visual svg {
  width: 100%;
  max-width: 280px;
  height: auto;
}

.r-hero__card-bg {
  fill: var(--bg-canvas);
  stroke: var(--border-1);
  stroke-width: 1;
}

.r-hero__chip {
  fill: var(--brand-bg);
  stroke: none;
}

.r-hero__chip-label {
  font-family: var(--f-mono);
  font-size: 8.5px;
  font-weight: 600;
  fill: var(--brand);
  letter-spacing: 0.05em;
}

.r-hero__heading {
  font-family: var(--f-display);
  font-size: 14px;
  font-weight: 500;
  fill: var(--ink-1);
}

.r-hero__line {
  fill: var(--border-2);
}

.r-hero__line--soft {
  fill: var(--border-1);
}

.r-hero__cta-shape {
  fill: var(--brand);
  stroke: none;
}

.r-hero__cta-label {
  font-family: var(--f-sans);
  font-size: 9.5px;
  font-weight: 600;
  fill: #fff;
}

@media (max-width: 880px) {
  .r-hero__card {
    grid-template-columns: 1fr;
  }
  .r-hero__visual {
    order: -1;
  }
}

@media (max-width: 820px) {
  .results-split {
    grid-template-columns: 1fr;
  }
  .results-left {
    border-inline-end: 0;
    border-bottom: 1px solid var(--border-1);
  }
}
</style>
