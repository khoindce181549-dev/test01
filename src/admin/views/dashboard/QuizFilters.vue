<template>
  <div class="quizably-filters">
    <div class="quizably-filters__search">
      <span
        class="quizably-filters__search-icon"
        aria-hidden="true"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <circle
            cx="11"
            cy="11"
            r="7"
          />
          <path d="m20 20-3.5-3.5" />
        </svg>
      </span>
      <input
        v-model="searchLocal"
        type="search"
        class="quizably-filters__search-input"
        :placeholder="searchPlaceholder"
        :aria-label="__('Search quizzes')"
      >
    </div>

    <Select
      :model-value="modelValue.status"
      :options="statusOptions"
      class="quizably-filters__select"
      :aria-label="__('Filter by status')"
      @update:model-value="update('status', $event)"
    />

    <Select
      :model-value="modelValue.type"
      :options="typeOptions"
      class="quizably-filters__select"
      :aria-label="__('Filter by type')"
      @update:model-value="update('type', $event)"
    />

    <Select
      :model-value="sortKey"
      :options="sortOptions"
      class="quizably-filters__select"
      :aria-label="__('Sort quizzes')"
      @update:model-value="onSortChange"
    />
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Select } from '@admin/ui';
import { proFeaturesVisible } from '@admin/api/pro.js';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  searchPlaceholder: {
    type: String,
    default: () => __('Search by title or tag…'),
  },
});

const emit = defineEmits(['update:modelValue']);

const searchLocal = ref(props.modelValue.search || '');
let searchTimer = null;

// Keep local search mirror in sync if the parent resets filters externally
// (e.g. "Clear filters" in a future empty-state refinement).
watch(
  () => props.modelValue.search,
  (v) => {
    if ((v || '') !== searchLocal.value) searchLocal.value = v || '';
  }
);

watch(searchLocal, (v) => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    update('search', v);
  }, 300);
});

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer);
});

const statusOptions = [
  { value: '', label: __('All statuses') },
  { value: 'draft', label: __('Draft') },
  { value: 'published', label: __('Published') },
  { value: 'archived', label: __('Archived') },
];

// Pro-tier types get a bracketed label instead of an inline badge —
// <select> can't render rich content. They are Pro-only options, so they are
// left out entirely while the free plugin isn't promoting Pro (see
// proFeaturesVisible()) rather than shown greyed or labelled.
const ALL_TYPE_OPTIONS = [
  { value: '', label: __('All types') },
  { value: 'personality', label: __('Personality') },
  { value: 'trivia', label: __('Trivia') },
  { value: 'survey', label: __('Survey') },
  { value: 'poll', label: __('Poll') },
  { value: 'weighted', label: __('Weighted · Pro'), pro: true },
  { value: 'branching', label: __('Branching · Pro'), pro: true },
];

const typeOptions = computed(() =>
  proFeaturesVisible() ? ALL_TYPE_OPTIONS : ALL_TYPE_OPTIONS.filter((o) => !o.pro)
);

const sortOptions = [
  { value: 'updated_at:DESC', label: __('Last updated') },
  { value: 'created_at:DESC', label: __('Recently created') },
  { value: 'title:ASC', label: __('Title A–Z') },
];

const sortKey = computed(
  () => `${props.modelValue.orderby || 'updated_at'}:${props.modelValue.order || 'DESC'}`
);

function update(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value });
}

function onSortChange(next) {
  const [orderby, order] = String(next).split(':');
  emit('update:modelValue', {
    ...props.modelValue,
    orderby: orderby || 'updated_at',
    order: order || 'DESC',
  });
}
</script>

<style scoped>
.quizably-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-xs);
}

.quizably-filters__search {
  position: relative;
  flex: 1 1 260px;
  min-width: 220px;
  max-width: 360px;
  display: flex;
  align-items: center;
}

.quizably-filters__search-icon {
  position: absolute;
  inset-inline-start: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ink-4);
  pointer-events: none;
  display: inline-flex;
}

.quizably-filters__search-icon svg {
  width: 14px;
  height: 14px;
}

.quizably-filters__search-input {
  width: 100%;
  padding-block: 9px 9px;
  padding-inline: 34px 14px;
  font-family: inherit;
  font-size: 14px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-2);
  border-radius: var(--r-md);
  color: var(--ink-1);
  outline: none;
  transition: border-color 150ms, box-shadow 150ms, background 150ms;
}

.quizably-filters__search-input::placeholder {
  color: var(--ink-4);
}

.quizably-filters__search-input:hover {
  border-color: var(--border-3);
}

.quizably-filters__search-input:focus {
  background: var(--bg-surface);
  border-color: var(--brand);
  box-shadow: var(--shadow-focus);
}

.quizably-filters__select {
  min-width: 150px;
}
</style>
