<template>
  <div
    :class="['q-item', { 'is-active': active, 'is-drag-over': dragOver }]"
    role="button"
    tabindex="0"
    :aria-selected="active"
    :aria-grabbed="dragging || undefined"
    draggable="true"
    @click="$emit('select')"
    @keydown.enter.prevent="$emit('select')"
    @keydown.space.prevent="$emit('select')"
    @dragstart="onDragStart"
    @dragend="onDragEnd"
    @dragover.prevent="onDragOver"
    @dragleave="onDragLeave"
    @drop.prevent="onDrop"
  >
    <span
      class="q-item__grip"
      aria-hidden="true"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
      >
        <circle
          cx="9"
          cy="5"
          r="1"
        />
        <circle
          cx="9"
          cy="12"
          r="1"
        />
        <circle
          cx="9"
          cy="19"
          r="1"
        />
        <circle
          cx="15"
          cy="5"
          r="1"
        />
        <circle
          cx="15"
          cy="12"
          r="1"
        />
        <circle
          cx="15"
          cy="19"
          r="1"
        />
      </svg>
    </span>
    <div class="q-item__num">
      {{ position }}
    </div>
    <div class="q-item__body">
      <div class="q-item__title">
        {{ plainTitle }}
      </div>
      <div class="q-item__meta">
        <span class="q-item__type">{{ typeMeta }}</span>
      </div>
    </div>
    <div class="q-item__actions">
      <button
        type="button"
        :aria-label="__('Duplicate question')"
        :title="__('Duplicate')"
        @click.stop="$emit('duplicate')"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <rect
            x="9"
            y="9"
            width="13"
            height="13"
            rx="2"
          />
          <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
        </svg>
      </button>
      <button
        type="button"
        class="q-item__actions-delete"
        :aria-label="__('Delete question')"
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
import { computed, ref } from 'vue';
import { __, _n, sprintf } from '@shared/i18n';

function stripHtml(html) {
  return (html || '').replace(/<[^>]*>/g, '').trim();
}

const props = defineProps({
  question: { type: Object, required: true },
  position: { type: Number, required: true },
  active: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'duplicate', 'delete', 'dragstart', 'dragend', 'drop']);

const dragging = ref(false);
const dragOver = ref(false);

const plainTitle = computed(() => stripHtml(props.question.title) || __('Untitled question'));

const TYPE_LABELS = {
  single: __('Single choice'),
  multiple: __('Multiple choice'),
  true_false: __('True / False'),
  image_choice: __('Image choice'),
  short_text: __('Short text'),
  dropdown: __('Dropdown'),
  slider: __('Slider'),
  rating: __('Rating'),
};

const typeMeta = computed(() => {
  const label = TYPE_LABELS[props.question.type] || props.question.type || __('Question');
  const count = Array.isArray(props.question.answers) ? props.question.answers.length : 0;
  if (props.question.type === 'short_text' || props.question.type === 'slider') {
    return label;
  }
  if (props.question.type === 'true_false') {
    return label;
  }
  // translators: 1: question type label, 2: number of answers
  return count ? sprintf(_n('%1$s · %2$d answer', '%1$s · %2$d answers', count), label, count) : label;
});

function onDragStart(event) {
  dragging.value = true;
  try {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(props.question.id));
  } catch {
    // Some browsers (jsdom) don't implement dataTransfer fully.
  }
  emit('dragstart', props.question.id);
}

function onDragEnd() {
  dragging.value = false;
  dragOver.value = false;
  emit('dragend');
}

function onDragOver(event) {
  dragOver.value = true;
  try {
    event.dataTransfer.dropEffect = 'move';
  } catch {
    /* ignore */
  }
}

function onDragLeave() {
  dragOver.value = false;
}

function onDrop() {
  dragOver.value = false;
  emit('drop', props.question.id);
}
</script>

<style scoped>
.q-item {
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

.q-item:hover {
  background: var(--bg-surface);
  border-color: var(--border-3);
  box-shadow: var(--shadow-sm);
}

.q-item:focus-visible {
  box-shadow: var(--shadow-focus);
}

.q-item.is-active {
  background: var(--brand-tint);
  border-color: var(--brand);
  box-shadow: var(--shadow-sm), inset 0 0 0 1px var(--brand);
}

.q-item.is-drag-over {
  box-shadow: inset 0 2px 0 0 var(--brand);
}

.q-item__grip {
  color: var(--ink-4);
  cursor: grab;
  display: inline-flex;
}

.q-item__grip svg {
  width: 14px;
  height: 14px;
}

.q-item__num {
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

.q-item.is-active .q-item__num {
  background: var(--brand);
  color: #fff;
}

.q-item__body {
  flex: 1;
  min-width: 0;
}

.q-item__title {
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-1);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}

.q-item__meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}

.q-item__type {
  font-family: var(--f-mono);
  font-size: 9.5px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-4);
}

.q-item__actions {
  display: flex;
  gap: 2px;
  opacity: 0;
  transition: opacity 150ms ease;
}

.q-item:hover .q-item__actions,
.q-item.is-active .q-item__actions,
.q-item:focus-within .q-item__actions {
  opacity: 1;
}

.q-item__actions button {
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

.q-item__actions button:hover {
  background: var(--bg-muted);
  color: var(--ink-1);
}

.q-item__actions .q-item__actions-delete:hover {
  background: var(--danger-bg);
  color: var(--danger);
}

.q-item__actions svg {
  width: 13px;
  height: 13px;
}
</style>
