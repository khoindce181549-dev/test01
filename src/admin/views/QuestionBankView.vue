<template>
  <div class="qbank-view">
    <header class="qbank-view__header">
      <div>
        <h1 class="qbank-view__title">
          <!-- eslint-disable-next-line vue/no-v-html -- static, developer-controlled markup only -->
          <span v-html="__('Question <em>bank</em>')" />
        </h1>
        <p class="qbank-view__sub">
          {{ subtitle }}
        </p>
      </div>
      <div class="qbank-view__cta">
        <Button
          variant="primary"
          @click="openCreate"
        >
          <template #icon-left>
            <svg
              viewBox="0 0 24 24"
              width="14"
              height="14"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <line
                x1="12"
                y1="5"
                x2="12"
                y2="19"
              />
              <line
                x1="5"
                y1="12"
                x2="19"
                y2="12"
              />
            </svg>
          </template>
          {{ __('Add question') }}
        </Button>
      </div>
    </header>

    <Card
      padding="md"
      class="qbank-view__toolbar"
    >
      <Input
        v-model="searchInput"
        :placeholder="__('Search question text…')"
        class="qbank-view__search"
      />
      <Select
        v-model="typeFilter"
        class="qbank-view__type"
        :options="typeFilterOptions"
        :aria-label="__('Filter by type')"
      />
      <div
        v-if="store.tags.length"
        class="qbank-view__tags"
      >
        <button
          v-for="t in store.tags"
          :key="t.name"
          type="button"
          :class="['qbank-view__tag', { 'is-on': activeTags.includes(t.name) }]"
          @click="toggleTag(t.name)"
        >
          {{ t.name }}
          <span class="qbank-view__tag-count">{{ t.count }}</span>
        </button>
        <button
          v-if="activeTags.length"
          type="button"
          class="qbank-view__tag-clear"
          @click="activeTags = []"
        >
          {{ __('Clear tags') }}
        </button>
      </div>
    </Card>

    <div
      v-if="store.loading && !store.items.length"
      class="qbank-view__loading"
    >
      <span class="qbank-view__spinner" />
      {{ __('Loading…') }}
    </div>

    <ul
      v-else-if="store.items.length"
      class="qbank-view__list"
    >
      <li
        v-for="row in store.items"
        :key="row.id"
        class="qbank-row"
        tabindex="0"
        role="button"
        @click="openEdit(row)"
        @keydown.enter.prevent="openEdit(row)"
      >
        <div class="qbank-row__main">
          <span class="qbank-row__title">{{ row.title || __('Untitled question') }}</span>
          <div class="qbank-row__meta">
            <Badge
              variant="neutral"
              size="sm"
            >
              {{ humanType(row.type) }}
            </Badge>
            <span
              v-for="tag in row.tags.slice(0, 3)"
              :key="tag"
              class="qbank-row__tag"
            >
              {{ tag }}
            </span>
            <span
              v-if="row.tags.length > 3"
              class="qbank-row__tag-more"
            >
              {{ moreTagsText(row) }}
            </span>
            <span class="qbank-row__usage">
              <svg
                viewBox="0 0 16 16"
                width="11"
                height="11"
                aria-hidden="true"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M2 5.5l6 4 6-4" />
                <rect
                  x="2"
                  y="2.5"
                  width="12"
                  height="11"
                  rx="1.5"
                />
              </svg>
              {{ usedInText(row) }}
            </span>
            <span class="qbank-row__time">{{ updatedText(row.updated_at) }}</span>
          </div>
        </div>
        <div
          class="qbank-row__actions"
          @click.stop
        >
          <button
            type="button"
            class="qbank-row__btn"
            :title="__('Duplicate')"
            :aria-label="__('Duplicate question')"
            @click="onDuplicate(row.id)"
          >
            <svg
              viewBox="0 0 24 24"
              width="15"
              height="15"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect
                x="8"
                y="8"
                width="12"
                height="12"
                rx="2"
              />
              <path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" />
            </svg>
          </button>
          <button
            type="button"
            class="qbank-row__btn qbank-row__btn--danger"
            :title="__('Delete')"
            :aria-label="__('Delete question')"
            @click="onDelete(row.id)"
          >
            <svg
              viewBox="0 0 24 24"
              width="15"
              height="15"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <polyline points="3 6 5 6 21 6" />
              <path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6" />
              <path d="M10 11v6" />
              <path d="M14 11v6" />
            </svg>
          </button>
        </div>
      </li>
    </ul>

    <div
      v-else
      class="qbank-view__empty"
    >
      <h3>{{ __('No reusable questions yet') }}</h3>
      <p v-if="hasFilters">
        {{ __('No matches for the current filters. Try clearing search or tags.') }}
      </p>
      <p v-else>
        {{ __("Write your first question — you'll be able to drop it into any quiz.") }}
      </p>
      <Button
        variant="primary"
        size="sm"
        @click="openCreate"
      >
        {{ __('Add your first question') }}
      </Button>
    </div>

    <Card
      v-if="showPromo"
      padding="md"
      class="qbank-view__upsell"
    >
      <div class="qbank-view__upsell-body">
        <Badge
          variant="pro"
          size="sm"
        >
          {{ __('Pro') }}
        </Badge>
        <h3>{{ __('Want more from your bank?') }}</h3>
        <p>
          {{ __('Pro adds folders, bulk import / export, multi-question insert, AI-assisted generation, and the Pro question types (image-choice, dropdown, slider, rating) inside the bank.') }}
        </p>
      </div>
    </Card>

    <QuestionBankEditor
      v-model="editorOpen"
      :question="editing"
      @saved="onSaved"
    />
  </div>
</template>

<script setup>
import { __, _n, sprintf } from '@shared/i18n';
import { computed, onMounted, ref, watch } from 'vue';
import { Badge, Button, Card, Input, Select, useToast } from '@admin/ui';
import { useQuestionBankStore } from '@admin/stores/questionBank';
import { showProPromo } from '@admin/api/pro.js';
import QuestionBankEditor from './questionBank/QuestionBankEditor.vue';

const store = useQuestionBankStore();
const toast = useToast();

// The bank itself is a free feature; only the "Want more from your bank?"
// teaser is Pro marketing, so it's shown only while Pro promotion is on.
const showPromo = computed(() => showProPromo());

const searchInput = ref('');
const typeFilter = ref('');
const activeTags = ref([]);
const editorOpen = ref(false);
const editing = ref(null);

const typeFilterOptions = [
  { value: '', label: __('All types') },
  { value: 'single', label: __('Single choice') },
  { value: 'multi', label: __('Multi choice') },
  { value: 'truefalse', label: __('True / false') },
];

function humanType(t) {
  const labels = {
    single: __('Single'),
    multi: __('Multi'),
    truefalse: __('True/False'),
  };
  return labels[t] || t || '—';
}

// translators: %d is the number of extra tags not shown on the row.
const moreTagsText = (row) => sprintf(__('+%d'), row.tags.length - 3);

// translators: %d is how many quizzes use this bank question.
const usedInText = (row) => sprintf(__('Used in %d'), row.usage_count || 0);

// translators: %s is a relative time such as "3 days ago".
const updatedText = (iso) => sprintf(__('Updated %s'), relativeTime(iso));

const subtitle = computed(() => {
  if (!store.items.length && !store.total) {
    return __('Reusable questions you can drop into any quiz.');
  }
  return sprintf(
    // translators: %d is the number of questions in the bank.
    _n('%d reusable question ready to go.', '%d reusable questions ready to go.', store.total),
    store.total
  );
});

const hasFilters = computed(
  () => Boolean(searchInput.value || typeFilter.value || activeTags.value.length)
);

const RTF = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
const RT_UNITS = [
  ['year', 60 * 60 * 24 * 365],
  ['month', 60 * 60 * 24 * 30],
  ['week', 60 * 60 * 24 * 7],
  ['day', 60 * 60 * 24],
  ['hour', 60 * 60],
  ['minute', 60],
];
function relativeTime(iso) {
  if (!iso) return '—';
  const then = Date.parse(iso);
  if (!Number.isFinite(then)) return '—';
  const diff = Math.round((then - Date.now()) / 1000);
  for (const [unit, sec] of RT_UNITS) {
    if (Math.abs(diff) >= sec) {
      return RTF.format(Math.round(diff / sec), unit);
    }
  }
  return RTF.format(diff, 'second');
}

let fetchTimer = null;
function scheduleFetch() {
  if (fetchTimer) clearTimeout(fetchTimer);
  fetchTimer = setTimeout(() => {
    store
      .fetchList({
        search: searchInput.value,
        type: typeFilter.value,
        tags: activeTags.value,
        offset: 0,
      })
      .catch((e) =>
        toast.push({
          variant: 'danger',
          title: __('Could not load bank'),
          message: e.message,
        })
      );
  }, 250);
}

watch([searchInput, typeFilter, activeTags], () => scheduleFetch(), { deep: true });

function toggleTag(tag) {
  const idx = activeTags.value.indexOf(tag);
  if (idx === -1) activeTags.value = [...activeTags.value, tag];
  else activeTags.value = activeTags.value.filter((t) => t !== tag);
}

function openCreate() {
  editing.value = null;
  editorOpen.value = true;
}

function openEdit(row) {
  editing.value = row;
  editorOpen.value = true;
}

async function onSaved() {
  // Refresh tag index in case the user added a new tag value.
  store.fetchTags().catch(() => {});
}

async function onDuplicate(id) {
  try {
    await store.duplicate(id);
    toast.push({ variant: 'success', title: __('Duplicated') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Duplicate failed'), message: e.message });
  }
}

async function onDelete(id) {
  if (!window.confirm(__('Delete this question from the bank? Quizzes that already use it will keep their copy.'))) {
    return;
  }
  try {
    await store.remove(id);
    toast.push({ variant: 'info', title: __('Question deleted') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Delete failed'), message: e.message });
  }
}

onMounted(() => {
  store.fetchList().catch((e) =>
    toast.push({ variant: 'danger', title: __('Could not load bank'), message: e.message })
  );
  store.fetchTags().catch(() => {});
});
</script>

<style scoped>
.qbank-view {
  max-width: 1200px;
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.qbank-view__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding: 14px 20px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-xs);
}

.qbank-view__title {
  font-family: var(--f-display);
  font-weight: 600;
  font-size: 18px;
  line-height: 1.25;
  letter-spacing: -0.005em;
  color: var(--ink-1);
  margin: 0;
}

.qbank-view__title :deep(em) {
  font-style: italic;
  color: var(--brand);
}

.qbank-view__sub {
  font-family: var(--f-sans);
  font-size: 12.5px;
  line-height: 1.45;
  color: var(--ink-3);
  margin: 2px 0 0;
}

.qbank-view__toolbar {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}

.qbank-view__search {
  flex: 1;
  min-width: 220px;
}

.qbank-view__type {
  width: 160px;
}

.qbank-view__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
  width: 100%;
  padding-top: 4px;
}

.qbank-view__tag {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.04em;
  padding: 5px 10px;
  border-radius: var(--r-pill);
  border: 1px solid var(--border-2);
  background: var(--bg-surface);
  color: var(--ink-2);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: border-color 150ms, background 150ms, color 150ms;
}

.qbank-view__tag:hover {
  border-color: var(--brand);
  color: var(--brand);
}

.qbank-view__tag.is-on {
  background: var(--brand);
  color: #fff;
  border-color: var(--brand);
}

.qbank-view__tag-count {
  font-size: 10px;
  color: var(--ink-4);
}

.qbank-view__tag.is-on .qbank-view__tag-count {
  color: rgba(255, 255, 255, 0.75);
}

.qbank-view__tag-clear {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--ink-3);
  background: transparent;
  border: 0;
  padding: 4px 8px;
  cursor: pointer;
}

.qbank-view__tag-clear:hover {
  color: var(--brand);
}

.qbank-view__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.qbank-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  cursor: pointer;
  transition: border-color 150ms, box-shadow 150ms, transform 150ms;
}

.qbank-row:hover {
  border-color: var(--border-3);
  box-shadow: var(--shadow-sm);
  transform: translateY(-1px);
}

.qbank-row:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  border-color: var(--brand);
}

.qbank-row__main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.qbank-row__title {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.qbank-row__meta {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--ink-3);
}

.qbank-row__tag {
  font-family: var(--f-mono);
  font-size: 10.5px;
  padding: 2px 7px;
  border-radius: var(--r-xs);
  background: var(--brand-tint);
  color: var(--brand);
}

.qbank-row__tag-more {
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-4);
}

.qbank-row__usage {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
}

.qbank-row__time {
  color: var(--ink-4);
  font-size: 11.5px;
}

.qbank-row__actions {
  display: flex;
  gap: 4px;
}

.qbank-row__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: var(--r-sm);
  background: transparent;
  border: 1px solid transparent;
  color: var(--ink-3);
  cursor: pointer;
  transition: background 150ms, color 150ms, border-color 150ms;
}

.qbank-row__btn:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
  border-color: var(--border-1);
}

.qbank-row__btn--danger:hover {
  background: var(--danger-bg);
  color: var(--danger);
  border-color: transparent;
}

.qbank-view__loading,
.qbank-view__empty {
  padding: 48px 16px;
  text-align: center;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  color: var(--ink-3);
  font-family: var(--f-sans);
  font-size: 13.5px;
}

.qbank-view__loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.qbank-view__spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-qbank-spin 700ms linear infinite;
}

@keyframes quizably-qbank-spin {
  to { transform: rotate(360deg); }
}

.qbank-view__empty h3 {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 20px;
  color: var(--ink-1);
  margin: 0 0 8px;
}

.qbank-view__empty p {
  margin: 0 0 16px;
}

.qbank-view__upsell {
  background:
    linear-gradient(160deg, var(--accent-tint) 0%, var(--bg-surface) 65%);
}

.qbank-view__upsell-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: flex-start;
}

.qbank-view__upsell h3 {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 18px;
  color: var(--ink-1);
  margin: 4px 0 0;
}

.qbank-view__upsell p {
  font-family: var(--f-sans);
  font-size: 13px;
  line-height: 1.55;
  color: var(--ink-2);
  margin: 0;
  max-width: 64ch;
}

@media (prefers-reduced-motion: reduce) {
  .qbank-row,
  .qbank-row__btn,
  .qbank-view__tag,
  .qbank-view__spinner {
    transition: none;
    animation: none;
  }
}
</style>
