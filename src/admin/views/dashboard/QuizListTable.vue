<template>
  <Card padding="none">
    <div class="quizably-quiztable__scroll">
      <table class="quizably-quiztable">
        <thead>
          <tr>
            <th scope="col">
              {{ __('Title') }}
            </th>
            <th scope="col">
              {{ __('Type') }}
            </th>
            <th scope="col">
              {{ __('Status') }}
            </th>
            <th
              scope="col"
              class="is-numeric"
            >
              {{ __('Submissions') }}
            </th>
            <th
              scope="col"
              class="is-numeric"
            >
              {{ __('Completion') }}
            </th>
            <th scope="col">
              {{ __('Updated') }}
            </th>
            <th
              scope="col"
              class="quizably-quiztable__actions-col"
            >
              <span class="quizably-quiztable__sr">{{ __('Actions') }}</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="quiz in quizzes"
            :key="quiz.id"
            class="quizably-quiztable__row"
            @click="emit('edit', quiz.id)"
          >
            <td class="quizably-quiztable__title">
              {{ quiz.title || __('Untitled quiz') }}
            </td>
            <td>
              <Badge :variant="typeVariant(quiz.type)">
                {{ labelFor(quiz.type, __('Quiz')) }}
              </Badge>
            </td>
            <td>
              <Badge :variant="statusVariant(quiz.status)">
                {{ labelFor(quiz.status, __('Draft')) }}
              </Badge>
            </td>
            <td class="is-numeric quizably-quiztable__num">
              {{ formatCount(quiz.submission_count) }}
            </td>
            <td class="is-numeric quizably-quiztable__num">
              {{ formatPercent(quiz.completion_rate) }}
            </td>
            <td class="quizably-quiztable__time">
              {{ formatRelative(quiz.updated_at) }}
            </td>
            <td
              class="quizably-quiztable__actions"
              @click.stop
            >
              <QuizActionsMenu
                :quiz="quiz"
                @edit="emit('edit', $event)"
                @duplicate="emit('duplicate', $event)"
                @archive="emit('archive', $event)"
                @restore="emit('restore', $event)"
                @remove="emit('remove', $event)"
                @publish="(id, next) => emit('publish', id, next)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </Card>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { shortRelativeTime } from '@admin/utils/relativeTime';
import { Badge, Card } from '@admin/ui';
import QuizActionsMenu from './QuizActionsMenu.vue';

defineProps({
  quizzes: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits(['edit', 'duplicate', 'archive', 'restore', 'remove', 'publish']);

// Display label for a quiz type or status. Known values are translated; an
// unknown one (e.g. a type added by another add-on) falls back to its raw
// value with the first letter upper-cased.
function labelFor(value, fallback = '') {
  if (!value) return fallback;
  const known = {
    personality: __('Personality'),
    trivia: __('Trivia'),
    survey: __('Survey'),
    poll: __('Poll'),
    weighted: __('Weighted'),
    branching: __('Branching'),
    published: __('Published'),
    draft: __('Draft'),
    archived: __('Archived'),
  };
  return known[value] || value.charAt(0).toUpperCase() + value.slice(1);
}

function typeVariant(type) {
  switch (type) {
    case 'personality':
      return 'info';
    case 'survey':
      return 'success';
    case 'weighted':
      return 'danger';
    case 'branching':
      return 'neutral';
    case 'trivia':
    case 'poll':
    default:
      return 'default';
  }
}

function statusVariant(status) {
  switch (status) {
    case 'published':
      return 'success';
    case 'archived':
      return 'neutral';
    case 'draft':
    default:
      return 'default';
  }
}

function formatCount(value) {
  if (value === null || value === undefined || value === '') return '—';
  const n = Number(value);
  if (!Number.isFinite(n)) return '—';
  if (n >= 1000) return `${(n / 1000).toFixed(n >= 10000 ? 0 : 1)}k`;
  return String(n);
}

function formatPercent(value) {
  if (value === null || value === undefined || value === '') return '—';
  const n = Number(value);
  if (!Number.isFinite(n)) return '—';
  const pct = n <= 1 ? Math.round(n * 100) : Math.round(n);
  return `${pct}%`;
}

function formatRelative(isoish) {
  return shortRelativeTime(isoish, '—');
}
</script>

<style scoped>
.quizably-quiztable__scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.quizably-quiztable {
  width: 100%;
  min-width: 720px;
  border-collapse: collapse;
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-2);
}

.quizably-quiztable thead th {
  text-align: start;
  font-family: var(--f-mono);
  font-weight: 500;
  font-size: 11px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-4);
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-1);
  background: var(--bg-canvas);
  white-space: nowrap;
}

.quizably-quiztable thead th.is-numeric {
  text-align: end;
}

.quizably-quiztable tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-1);
  vertical-align: middle;
}

.quizably-quiztable tbody tr:last-child td {
  border-bottom: 0;
}

.quizably-quiztable__row {
  transition: background 150ms;
  cursor: pointer;
}

.quizably-quiztable__row:hover {
  background: var(--bg-subtle);
}

.quizably-quiztable__title {
  font-weight: 500;
  color: var(--ink-1);
  max-width: 360px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.quizably-quiztable__num {
  font-family: var(--f-mono);
  font-size: 12px;
  color: var(--ink-2);
  text-align: end;
}

.quizably-quiztable__time {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.04em;
  color: var(--ink-4);
  text-transform: uppercase;
  white-space: nowrap;
}

.quizably-quiztable__actions {
  display: flex;
  justify-content: flex-end;
  gap: 4px;
  white-space: nowrap;
}

.quizably-quiztable__actions-col {
  width: 1%;
  text-align: end;
}

.quizably-quiztable__sr {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
