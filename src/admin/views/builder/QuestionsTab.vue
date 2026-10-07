<template>
  <div class="builder-split">
    <aside class="builder-left">
      <div class="q-list-head">
        <span>{{ __('Questions') }}</span>
        <span>{{ totalLabel }}</span>
      </div>

      <div
        v-if="!store.questions.length"
        class="builder-left__empty"
      >
        <p>{{ __('No questions yet.') }}</p>
        <p class="builder-left__empty-hint">
          {{ __('Click the button below to add your first one.') }}
        </p>
      </div>

      <div class="builder-left__list">
        <QuestionListItem
          v-for="(q, i) in store.questions"
          :key="q.id"
          :question="q"
          :position="i + 1"
          :active="store.activeQuestionId === q.id"
          @select="store.setActiveQuestion(q.id)"
          @duplicate="onDuplicate(q)"
          @delete="onDelete(q)"
          @dragstart="(id) => (draggingId = id)"
          @dragend="draggingId = null"
          @drop="(id) => onDrop(id)"
        />
      </div>

      <div class="builder-left__add">
        <button
          type="button"
          class="q-add"
          :aria-expanded="addMenuOpen"
          :disabled="creating"
          @click.stop="toggleAddMenu"
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
          {{ creating ? __('Adding…') : __('Add question') }}
        </button>
        <button
          type="button"
          class="q-add-bank"
          :disabled="creating"
          :title="__('Insert a reusable question from your bank')"
          @click.stop="insertFromBankOpen = true"
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M4 6a2 2 0 0 1 2-2h11l3 3v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
            <path d="M9 12c0-1.1.9-2 2-2s2 .9 2 2-2 1.5-2 3" />
            <line
              x1="11"
              y1="18"
              x2="11.01"
              y2="18"
            />
          </svg>
          {{ __('Insert from bank') }}
        </button>
        <div
          v-if="addMenuOpen"
          class="builder-left__menu"
          role="menu"
        >
          <button
            v-for="opt in questionTypes"
            :key="opt.type"
            type="button"
            role="menuitem"
            :class="['builder-left__menu-item', { 'is-pro': opt.pro && !isProUser }]"
            :disabled="creating"
            @click.stop="onPickType(opt)"
          >
            <span class="builder-left__menu-label">{{ opt.label }}</span>
            <Badge
              v-if="opt.pro"
              variant="pro"
              size="sm"
            >
              {{ __('Pro') }}
            </Badge>
          </button>
        </div>
      </div>
    </aside>
    <InsertFromBankModal v-model="insertFromBankOpen" />

    <section class="builder-right">
      <div
        v-if="!store.activeQuestion"
        class="q-hero"
      >
        <div class="q-hero__card">
          <div class="q-hero__content">
            <div class="q-hero__eyebrow">
              <span>{{ __('Getting started') }}</span>
            </div>

            <h2 class="q-hero__title">
              {{ heroCopy.title }}
            </h2>

            <p class="q-hero__lede">
              {{ heroCopy.lede }}
            </p>

            <ul class="q-hero__list">
              <li>
                <span
                  class="q-hero__check"
                  aria-hidden="true"
                />
                {{ typePickHint }}
              </li>
              <li>
                <span
                  class="q-hero__check"
                  aria-hidden="true"
                />
                {{ __('Add images, descriptions, result mapping per question') }}
              </li>
              <li>
                <span
                  class="q-hero__check"
                  aria-hidden="true"
                />
                {{ __('Drag to reorder, duplicate, or delete from the left rail') }}
              </li>
            </ul>

            <div class="q-hero__cta">
              <Button
                variant="primary"
                :loading="creating"
                @click="onPickType({ type: defaultType, pro: false })"
              >
                {{ heroCopy.cta }}
              </Button>
              <span class="q-hero__sub">{{ heroCopy.sub }}</span>
            </div>
          </div>

          <div class="q-hero__visual">
            <svg
              viewBox="0 0 280 200"
              aria-hidden="true"
            >
              <!-- Sample question card -->
              <rect
                x="10"
                y="10"
                width="260"
                height="180"
                rx="12"
                class="q-hero__card-bg"
              />
              <text
                x="26"
                y="40"
                class="q-hero__card-title"
              >{{ __('What\'s your dream') }}</text>
              <text
                x="26"
                y="58"
                class="q-hero__card-title"
              >{{ __('vacation?') }}</text>

              <rect
                x="22"
                y="78"
                width="236"
                height="28"
                rx="7"
                class="q-hero__answer q-hero__answer--active"
              />
              <circle
                cx="36"
                cy="92"
                r="5"
                class="q-hero__radio q-hero__radio--active"
              />
              <text
                x="50"
                y="96"
                class="q-hero__answer-label"
              >{{ __('Beach') }}</text>

              <rect
                x="22"
                y="112"
                width="236"
                height="28"
                rx="7"
                class="q-hero__answer"
              />
              <circle
                cx="36"
                cy="126"
                r="5"
                class="q-hero__radio"
              />
              <text
                x="50"
                y="130"
                class="q-hero__answer-label"
              >{{ __('Mountains') }}</text>

              <rect
                x="22"
                y="146"
                width="236"
                height="28"
                rx="7"
                class="q-hero__answer"
              />
              <circle
                cx="36"
                cy="160"
                r="5"
                class="q-hero__radio"
              />
              <text
                x="50"
                y="164"
                class="q-hero__answer-label"
              >{{ __('Roadtrip') }}</text>
            </svg>
          </div>
        </div>
      </div>
      <QuestionEditor
        v-else
        :key="store.activeQuestion.id"
        :question="store.activeQuestion"
        :quiz-type="store.quiz?.type ?? 'personality'"
        :results="store.results"
        :position="activePosition"
        :total="store.questions.length"
      />
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { __, _n, sprintf } from '@shared/i18n';
import { Badge, Button, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { isPro, proFeaturesVisible } from '@admin/api/pro.js';
import QuestionListItem from './QuestionListItem.vue';
import QuestionEditor from './QuestionEditor.vue';
import InsertFromBankModal from '../questionBank/InsertFromBankModal.vue';

const store = useQuizBuilderStore();
const toast = useToast();

const addMenuOpen = ref(false);
const insertFromBankOpen = ref(false);
const creating = ref(false);
const draggingId = ref(null);

// Free types show normally. Pro types are listed only while Pro is active or
// Pro promotion is on (see api/pro.js): with promotion on but Pro inactive they
// stay visible and clicking surfaces an upgrade toast (matches the NewQuizModal
// Pro-gating treatment); with promotion off they are absent altogether.
const ALL_QUESTION_TYPES = [
  { type: 'single', label: __('Single choice'), pro: false },
  { type: 'multiple', label: __('Multiple choice'), pro: false },
  { type: 'true_false', label: __('True / False'), pro: false },
  { type: 'dropdown', label: __('Dropdown'), pro: false },
  { type: 'short_text', label: __('Short text'), pro: false },
  { type: 'rating', label: __('Rating'), pro: false },
  { type: 'image_choice', label: __('Image choice'), pro: true },
  { type: 'slider', label: __('Slider'), pro: true },
];

const proTierVisible = computed(() => proFeaturesVisible());

const questionTypes = computed(() =>
  ALL_QUESTION_TYPES.filter((t) => !t.pro || proTierVisible.value)
);

const defaultType = 'single';

// Hero bullet: the "plus N Pro types" tail only exists while Pro types are listed.
const FREE_TYPE_HINT = __('Single, Multiple, True/False, Dropdown, Short text, Rating');
const typePickHint = computed(() => {
  const proCount = questionTypes.value.filter((t) => t.pro).length;
  return proCount > 0
    // translators: 1: comma-separated list of free question types, 2: number of Pro question types
    ? sprintf(_n('Pick a type \u2014 %1$s, plus %2$d Pro type', 'Pick a type \u2014 %1$s, plus %2$d Pro types', proCount), FREE_TYPE_HINT, proCount)
    // translators: %s: comma-separated list of free question types
    : sprintf(__('Pick a type \u2014 %s'), FREE_TYPE_HINT);
});

const totalLabel = computed(() => {
  const n = store.questions.length;
  // translators: %d: total number of questions
  return sprintf(__('%d total'), n);
});

// Hero card copy switches based on whether the quiz has any questions
// yet. Same visual treatment in both cases — only the words change.
const heroCopy = computed(() => {
  if (store.questions.length === 0) {
    return {
      title: __('Build your first question'),
      lede: __('Pick a question type from the left rail or click below to start with a single-choice question.'),
      cta: __('Add your first question'),
      // "Free types" only makes sense while there are Pro types beside them.
      sub: proTierVisible.value
        ? __('Free types: Single \u00B7 Multiple \u00B7 True / False \u00B7 Dropdown \u00B7 Short text \u00B7 Rating')
        : __('Question types: Single \u00B7 Multiple \u00B7 True / False \u00B7 Dropdown \u00B7 Short text \u00B7 Rating'),
    };
  }
  return {
    title: __('Pick a question to edit'),
    lede: __('Click any question on the left to open it in the editor, or add a new one to keep building.'),
    cta: __('Add another question'),
    // translators: %d: number of questions
    sub: sprintf(_n('%d question so far', '%d questions so far', store.questions.length), store.questions.length),
  };
});

const activePosition = computed(() => {
  const id = store.activeQuestionId;
  const idx = store.questions.findIndex((q) => q.id === id);
  return idx === -1 ? 1 : idx + 1;
});

function toggleAddMenu() {
  addMenuOpen.value = !addMenuOpen.value;
}

function closeAddMenu() {
  addMenuOpen.value = false;
}

const isProUser = computed(() => isPro());

async function onPickType(opt) {
  if (opt.pro && !isProUser.value) {
    toast.push({
      variant: 'info',
      title: __('Pro question type'),
      // translators: %s: question type name (e.g. Slider)
      message: sprintf(__('%s requires Quizably Pro. Upgrade coming soon.'), opt.label),
    });
    return;
  }
  addMenuOpen.value = false;
  creating.value = true;
  try {
    await store.createQuestion({
      type: opt.type,
      title: opt.type === 'true_false' ? __('True or false?') : __('New question'),
      position: store.questions.length + 1,
    });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not add question'),
      message: e.message || __('Please try again.'),
    });
  } finally {
    creating.value = false;
  }
}

async function onDuplicate(q) {
  creating.value = true;
  try {
    await store.duplicateQuestion(q);
    toast.push({ variant: 'success', title: __('Question duplicated') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not duplicate'), message: e.message });
  } finally {
    creating.value = false;
  }
}

function stripHtml(html) {
  return (html || '').replace(/<[^>]*>/g, '').trim();
}

async function onDelete(q) {
  const label = stripHtml(q.title) || __('this question');
  // translators: %s: question title
  if (!window.confirm(sprintf(__('Delete "%s"? This cannot be undone.'), label))) return;
  try {
    await store.deleteQuestion(q.id);
    toast.push({ variant: 'info', title: __('Question deleted') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not delete'), message: e.message });
  }
}

async function onDrop(targetId) {
  const sourceId = draggingId.value;
  draggingId.value = null;
  if (!sourceId || sourceId === targetId) return;
  const ids = store.questions.map((q) => q.id);
  const from = ids.indexOf(sourceId);
  const to = ids.indexOf(targetId);
  if (from === -1 || to === -1) return;
  ids.splice(from, 1);
  ids.splice(to, 0, sourceId);
  try {
    await store.reorderQuestions(ids);
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not reorder'), message: e.message });
  }
}

// Outside click closes the Add-question menu so it behaves like a dropdown.
function handleDocClick() {
  closeAddMenu();
}
onMounted(() => document.addEventListener('click', handleDocClick));
onBeforeUnmount(() => document.removeEventListener('click', handleDocClick));
</script>

<style scoped>
.builder-split {
  display: grid;
  grid-template-columns: 320px 1fr;
  grid-template-rows: minmax(0, 1fr);
  height: 100%;
  min-height: 0;
}

/*
 * Left pane stretches to fill the full split height; the question list
 * inside it scrolls independently while the right canvas scrolls its
 * own content. Same dual-pane affordance as Typeform / Linear / Notion.
 */
.builder-left {
  background: var(--bg-subtle);
  border-inline-end: 1px solid var(--border-1);
  padding: 16px 14px 24px;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
}

.builder-left__list {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

.builder-left__empty {
  padding: 24px 12px;
  text-align: center;
  color: var(--ink-4);
  font-size: 13px;
}

.builder-left__empty p {
  margin: 0;
}

.builder-left__empty-hint {
  margin-top: 6px !important;
  font-size: 12px;
  color: var(--ink-4);
}

.builder-left__add {
  position: relative;
  margin-top: 4px;
}

.builder-left__menu {
  position: absolute;
  /* The Add button sits at the bottom of a full-height rail now, so
     opening downward would render off-screen. Open upward instead —
     the question list above always leaves room for the menu. */
  bottom: calc(100% + 4px);
  inset-inline-start: 0;
  inset-inline-end: 0;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-lg);
  padding: 4px;
  z-index: 20;
  max-height: 320px;
  overflow-y: auto;
}

.builder-left__menu-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 8px 10px;
  background: transparent;
  border: 0;
  border-radius: var(--r-xs);
  text-align: start;
  font-size: 13px;
  color: var(--ink-2);
  cursor: pointer;
  gap: 8px;
  font-family: inherit;
}

.builder-left__menu-item:hover:not(:disabled) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.builder-left__menu-item.is-pro {
  color: var(--ink-3);
}

.builder-left__menu-label {
  flex: 1;
}

.q-list-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 8px 14px;
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0;
  text-transform: none;
  color: var(--ink-1);
}

/* Total count keeps the muted mono tone so the heading stands alone. */
.q-list-head > span:last-child {
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 400;
  color: var(--ink-3);
}

.q-add {
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

.q-add:hover:not(:disabled) {
  border-color: var(--brand);
  color: var(--brand);
  background: var(--brand-tint);
}

.q-add:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.q-add svg {
  width: 14px;
  height: 14px;
}

.q-add-bank {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  margin-top: 6px;
  padding: 8px 10px;
  background: transparent;
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-3);
  cursor: pointer;
  transition: all 150ms ease;
  font-family: inherit;
}

.q-add-bank:hover:not(:disabled) {
  border-color: var(--brand);
  color: var(--brand);
  background: var(--brand-tint);
}

.q-add-bank:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.q-add-bank svg {
  width: 13px;
  height: 13px;
}

.builder-right {
  background: var(--bg-canvas);
  overflow-y: auto;
  min-height: 0;
}

/* Empty hero — same two-column treatment as the Logic Pro upsell.
   Vertically centered in the canvas; padding stops it touching edges. */
.q-hero {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.q-hero__card {
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

.q-hero__content {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.q-hero__eyebrow {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-3);
  font-weight: 500;
  margin-bottom: 2px;
}

.q-hero__title {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 22px;
  line-height: 1.2;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
}

.q-hero__lede {
  font-family: var(--f-sans);
  font-size: 13.5px;
  line-height: 1.55;
  color: var(--ink-2);
  margin: 0;
  max-width: 52ch;
}

.q-hero__list {
  list-style: none;
  margin: 6px 0 4px;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.q-hero__list li {
  display: flex;
  align-items: center;
  gap: 9px;
  font-family: var(--f-sans);
  font-size: 12.5px;
  line-height: 1.5;
  color: var(--ink-2);
}

.q-hero__check {
  flex-shrink: 0;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--success-bg) url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 8.5l3 3 6-7' stroke='%23059669' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round' fill='none'/%3E%3C/svg%3E")
    center / 10px no-repeat;
}

.q-hero__cta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.q-hero__sub {
  font-family: var(--f-sans);
  font-size: 11.5px;
  color: var(--ink-3);
}

.q-hero__visual {
  display: flex;
  justify-content: center;
  align-items: center;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  padding: 10px;
  min-width: 0;
}

.q-hero__visual svg {
  width: 100%;
  max-width: 280px;
  height: auto;
}

.q-hero__card-bg {
  fill: var(--bg-canvas);
  stroke: var(--border-1);
  stroke-width: 1;
}

.q-hero__card-title {
  font-family: var(--f-display);
  font-size: 15px;
  font-weight: 500;
  fill: var(--ink-1);
}

.q-hero__answer {
  fill: var(--bg-surface);
  stroke: var(--border-1);
  stroke-width: 1;
}

.q-hero__answer--active {
  fill: var(--brand-tint);
  stroke: var(--brand);
  stroke-width: 1.4;
}

.q-hero__radio {
  fill: var(--bg-surface);
  stroke: var(--border-3);
  stroke-width: 1.2;
}

.q-hero__radio--active {
  fill: var(--brand);
  stroke: var(--brand);
}

.q-hero__answer-label {
  font-family: var(--f-sans);
  font-size: 11px;
  fill: var(--ink-2);
}

@media (max-width: 880px) {
  .q-hero__card {
    grid-template-columns: 1fr;
  }
  .q-hero__visual {
    order: -1;
  }
}

@media (max-width: 820px) {
  .builder-split {
    grid-template-columns: 1fr;
  }
  .builder-left {
    border-inline-end: 0;
    border-bottom: 1px solid var(--border-1);
  }
}
</style>
