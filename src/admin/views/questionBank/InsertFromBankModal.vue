<template>
  <Modal
    :model-value="modelValue"
    :title="__('Insert from question bank')"
    size="md"
    @update:model-value="onToggle"
  >
    <div class="ifb">
      <Input
        v-model="search"
        :placeholder="__('Search bank questions…')"
        class="ifb__search"
        autofocus
      />

      <div
        v-if="store.tags.length"
        class="ifb__tags"
      >
        <button
          v-for="t in store.tags"
          :key="t.name"
          type="button"
          :class="['ifb__tag', { 'is-on': activeTags.includes(t.name) }]"
          @click="toggleTag(t.name)"
        >
          {{ t.name }}
        </button>
        <button
          v-if="activeTags.length"
          type="button"
          class="ifb__tag-clear"
          @click="activeTags = []"
        >
          {{ __('Clear') }}
        </button>
      </div>

      <div
        v-if="store.loading && !store.items.length"
        class="ifb__loading"
      >
        <span class="ifb__spinner" />
        {{ __('Loading…') }}
      </div>

      <ul
        v-else-if="store.items.length"
        class="ifb__list"
      >
        <li
          v-for="row in store.items"
          :key="row.id"
          :class="['ifb__row', { 'is-busy': insertingId === row.id }]"
          tabindex="0"
          role="button"
          @click="onPick(row)"
          @keydown.enter.prevent="onPick(row)"
        >
          <div class="ifb__row-main">
            <span class="ifb__row-title">{{ row.title }}</span>
            <div class="ifb__row-meta">
              <Badge
                variant="neutral"
                size="sm"
              >
                {{ humanType(row.type) }}
              </Badge>
              <span
                v-for="tag in row.tags.slice(0, 3)"
                :key="tag"
                class="ifb__row-tag"
              >
                {{ tag }}
              </span>
            </div>
          </div>
          <span class="ifb__row-cta">
            {{ insertingId === row.id ? __('Inserting…') : __('Insert') }}
          </span>
        </li>
      </ul>

      <div
        v-else
        class="ifb__empty"
      >
        <p v-if="hasFilters">
          {{ __('No matches. Try clearing search or tags.') }}
        </p>
        <p v-else>
          {{ __('The bank is empty. Add questions in the Question Bank page to reuse them here.') }}
        </p>
        <RouterLink
          to="/question-bank"
          class="ifb__empty-link"
        >
          {{ __('Open Question Bank →') }}
        </RouterLink>
      </div>
    </div>

    <template #footer>
      <span class="ifb__footer-spacer" />
      <Button
        variant="outline"
        @click="close"
      >
        {{ __('Close') }}
      </Button>
    </template>
  </Modal>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { Badge, Button, Input, Modal, useToast } from '@admin/ui';
import { useQuestionBankStore } from '@admin/stores/questionBank';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const store = useQuestionBankStore();
const builder = useQuizBuilderStore();
const toast = useToast();

const search = ref('');
const activeTags = ref([]);
const insertingId = ref(null);

function humanType(t) {
  const labels = {
    single: __('Single'),
    multi: __('Multi'),
    truefalse: __('True/False'),
  };
  return labels[t] || t || '—';
}

const hasFilters = computed(() => Boolean(search.value || activeTags.value.length));

let fetchTimer = null;
function scheduleFetch() {
  if (fetchTimer) clearTimeout(fetchTimer);
  fetchTimer = setTimeout(() => {
    store.fetchList({ search: search.value, tags: activeTags.value, offset: 0 }).catch(() => {});
  }, 250);
}

watch([search, activeTags], () => scheduleFetch(), { deep: true });

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      // Refresh every time the modal opens so newly-authored bank questions
      // appear without a manual reload, but reuse cached tags index.
      store.fetchList({ search: '', tags: [], offset: 0 }).catch(() => {});
      if (!store.tags.length) store.fetchTags().catch(() => {});
    }
  }
);

function toggleTag(tag) {
  const i = activeTags.value.indexOf(tag);
  if (i === -1) activeTags.value = [...activeTags.value, tag];
  else activeTags.value = activeTags.value.filter((t) => t !== tag);
}

function close() {
  emit('update:modelValue', false);
}

function onToggle(open) {
  emit('update:modelValue', open);
}

async function onPick(row) {
  if (!builder.quiz) {
    toast.push({
      variant: 'danger',
      title: __('No quiz open'),
      message: __('Open a quiz in the builder before inserting from the bank.'),
    });
    return;
  }
  if (insertingId.value) return;
  insertingId.value = row.id;
  try {
    const newQ = await store.insertInto(row.id, builder.quiz.id, builder.questions.length + 1);
    builder.appendQuestion(newQ);
    toast.push({
      variant: 'success',
      title: __('Inserted from bank'),
      message: row.title || __('Question added'),
    });
    close();
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Insert failed'), message: e.message });
  } finally {
    insertingId.value = null;
  }
}
</script>

<style scoped>
.ifb {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.ifb__search {
  width: 100%;
}

.ifb__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.ifb__tag {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.04em;
  padding: 4px 9px;
  border-radius: var(--r-pill);
  border: 1px solid var(--border-2);
  background: var(--bg-surface);
  color: var(--ink-2);
  cursor: pointer;
  transition: border-color 150ms, background 150ms, color 150ms;
}

.ifb__tag:hover {
  border-color: var(--brand);
  color: var(--brand);
}

.ifb__tag.is-on {
  background: var(--brand);
  color: #fff;
  border-color: var(--brand);
}

.ifb__tag-clear {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--ink-3);
  background: transparent;
  border: 0;
  padding: 4px 6px;
  cursor: pointer;
}

.ifb__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 50vh;
  overflow-y: auto;
}

.ifb__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  cursor: pointer;
  transition: border-color 150ms, background 150ms, transform 150ms;
}

.ifb__row:hover {
  border-color: var(--brand);
  background: var(--brand-tint);
  transform: translateY(-1px);
}

.ifb__row:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  border-color: var(--brand);
}

.ifb__row.is-busy {
  pointer-events: none;
  opacity: 0.7;
}

.ifb__row-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.ifb__row-title {
  font-family: var(--f-sans);
  font-size: 13.5px;
  font-weight: 600;
  color: var(--ink-1);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ifb__row-meta {
  display: flex;
  align-items: center;
  gap: 6px;
}

.ifb__row-tag {
  font-family: var(--f-mono);
  font-size: 10.5px;
  padding: 1px 7px;
  border-radius: var(--r-xs);
  background: var(--bg-surface);
  color: var(--ink-3);
}

.ifb__row-cta {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--brand);
  font-weight: 500;
  white-space: nowrap;
}

.ifb__loading,
.ifb__empty {
  padding: 32px 16px;
  text-align: center;
  color: var(--ink-3);
  font-size: 13px;
}

.ifb__loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.ifb__spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-ifb-spin 700ms linear infinite;
}

@keyframes quizably-ifb-spin {
  to { transform: rotate(360deg); }
}

.ifb__empty p {
  margin: 0 0 12px;
}

.ifb__empty-link {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--brand);
  text-decoration: none;
}

.ifb__empty-link:hover {
  text-decoration: underline;
}

.ifb__footer-spacer {
  flex: 1;
}

@media (prefers-reduced-motion: reduce) {
  .ifb__row,
  .ifb__tag,
  .ifb__spinner {
    transition: none;
    animation: none;
  }
}
</style>
