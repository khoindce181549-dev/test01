<template>
  <div
    :class="['r-item', 'result-card', { 'is-active': active }]"
    role="button"
    tabindex="0"
    :aria-selected="active"
    @click="$emit('select')"
    @keydown.enter.prevent="$emit('select')"
    @keydown.space.prevent="$emit('select')"
  >
    <div class="r-item__num">
      {{ position }}
    </div>
    <div class="r-item__body">
      <div class="r-item__title">
        {{ plainTitle }}
      </div>
      <div class="r-item__meta">
        <span class="r-item__type">{{ metaText }}</span>
      </div>
    </div>
    <div class="r-item__actions">
      <button
        type="button"
        class="r-item__actions-delete"
        :aria-label="__('Delete result')"
        :title="__('Delete')"
        @click.stop="$emit('delete')"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <polyline points="3 6 5 6 21 6" />
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { computed } from 'vue';

// Strip HTML tags that may have been inserted by the contenteditable title editor
// (e.g. alignment divs from the floating toolbar) so the listing shows plain text.
function stripHtml(html) {
  return (html || '').replace(/<[^>]*>/g, '').trim();
}

const props = defineProps({
  result: { type: Object, required: true },
  position: { type: Number, required: true },
  active: { type: Boolean, default: false },
  quizType: { type: String, default: 'personality' },
  mappedCount: { type: Number, default: 0 },
});

defineEmits(['select', 'delete']);

// translators: %d: result position number.
const plainTitle = computed(() => stripHtml(props.result.title) || sprintf(__('Result %d'), props.position));

const metaText = computed(() => {
  if (props.quizType === 'trivia') {
    const min = Number(props.result.score_min ?? props.result.min_score ?? 0);
    const max = Number(props.result.score_max ?? props.result.max_score ?? 0);
    // translators: 1: minimum score, 2: maximum score.
    return sprintf(__('Score %1$s–%2$s'), min, max);
  }
  if (props.quizType === 'personality') {
    // translators: %d: number of answers mapped to this result.
    return sprintf(__('%d mapped'), props.mappedCount);
  }
  return __('Result');
});
</script>

<style scoped>
.r-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: background 150ms ease, border-color 150ms ease, box-shadow 150ms ease;
  margin-bottom: 6px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  box-shadow: var(--shadow-xs);
  text-align: start;
  outline: none;
}

.r-item:hover {
  background: var(--bg-surface);
  border-color: var(--border-3);
  box-shadow: var(--shadow-sm);
}

.r-item:focus-visible {
  box-shadow: var(--shadow-focus);
}

.r-item.is-active {
  background: var(--brand-tint);
  border-color: var(--brand);
  box-shadow: var(--shadow-sm), inset 0 0 0 1px var(--brand);
}

.r-item__num {
  width: 22px;
  height: 22px;
  background: var(--bg-muted);
  border-radius: var(--r-xs);
  font-family: var(--f-mono);
  font-size: 10.5px;
  font-weight: 600;
  color: var(--ink-2);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.r-item.is-active .r-item__num {
  background: var(--brand);
  color: #fff;
}

.r-item__body {
  flex: 1;
  min-width: 0;
}

.r-item__title {
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-1);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}

.r-item__meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}

.r-item__type {
  font-family: var(--f-mono);
  font-size: 9.5px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-4);
}

.r-item__actions {
  display: flex;
  gap: 2px;
  opacity: 0;
  transition: opacity 150ms ease;
}

.r-item:hover .r-item__actions,
.r-item.is-active .r-item__actions,
.r-item:focus-within .r-item__actions {
  opacity: 1;
}

.r-item__actions button {
  width: 22px;
  height: 22px;
  border-radius: var(--r-xs);
  color: var(--ink-4);
  display: grid;
  place-items: center;
  background: transparent;
  border: 0;
  cursor: pointer;
}

.r-item__actions button:hover {
  background: var(--bg-muted);
  color: var(--ink-1);
}

.r-item__actions .r-item__actions-delete:hover {
  background: var(--danger-bg);
  color: var(--danger);
}

.r-item__actions svg {
  width: 13px;
  height: 13px;
}
</style>
