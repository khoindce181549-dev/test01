<template>
  <Card
    padding="none"
    interactive
    class="quizably-quizcard"
    @click="onCardClick"
  >
    <div
      class="quizably-quizcard__cover"
      :class="coverClass"
    >
      <img
        v-if="templateUrl"
        :src="templateUrl"
        :alt="''"
        class="quizably-quizcard__thumb"
        loading="lazy"
        @error="onThumbError"
      >
      <div
        v-else
        class="quizably-quizcard__thumb quizably-quizcard__thumb--fallback"
        aria-hidden="true"
      >
        <div class="quizably-quizcard__thumb-bar" />
        <div class="quizably-quizcard__thumb-line" />
        <div class="quizably-quizcard__thumb-line quizably-quizcard__thumb-line--short" />
      </div>
      <span class="quizably-quizcard__chip">{{ typeLabel }}</span>
    </div>

    <div class="quizably-quizcard__body">
      <div class="quizably-quizcard__meta">
        <Badge :variant="typeBadgeVariant">
          {{ typeLabel }}
        </Badge>
        <Badge :variant="statusBadgeVariant">
          {{ statusLabel }}
        </Badge>
        <Badge
          v-if="isProTemplate"
          variant="pro"
        >
          {{ __('Pro') }}
        </Badge>
      </div>

      <h3 class="quizably-quizcard__title">
        {{ quiz.title || __('Untitled quiz') }}
      </h3>

      <div class="quizably-quizcard__stats">
        <div class="quizably-quizcard__stat">
          <strong>{{ formatCount(quiz.submission_count) }}</strong>
          <span>{{ __('submissions') }}</span>
        </div>
        <span class="quizably-quizcard__stat-sep" />
        <div class="quizably-quizcard__stat">
          <strong>{{ formatPercent(quiz.completion_rate) }}</strong>
          <span>{{ __('completion') }}</span>
        </div>
      </div>

      <div class="quizably-quizcard__foot">
        <span class="quizably-quizcard__time">{{ editedText }}</span>
        <QuizActionsMenu
          :quiz="quiz"
          @edit="emit('edit', $event)"
          @duplicate="emit('duplicate', $event)"
          @archive="emit('archive', $event)"
          @restore="emit('restore', $event)"
          @remove="emit('remove', $event)"
          @publish="(id, next) => emit('publish', id, next)"
        />
      </div>
    </div>
  </Card>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { shortRelativeTime } from '@admin/utils/relativeTime';
import { computed, ref } from 'vue';
import { Badge, Card } from '@admin/ui';
import QuizActionsMenu from './QuizActionsMenu.vue';

const props = defineProps({
  quiz: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['edit', 'duplicate', 'archive', 'restore', 'remove', 'publish']);

const thumbFailed = ref(false);

const PRO_TEMPLATES = new Set([
  'weighted',
  'branching',
  'assessment',
  'quiz-funnel',
  'recommendation',
  'advanced-lead',
]);
const PRO_TYPES = new Set(['weighted', 'branching']);

const typeLabel = computed(() => {
  const map = {
    personality: __('Personality'),
    trivia: __('Trivia'),
    survey: __('Survey'),
    poll: __('Poll'),
    weighted: __('Weighted'),
    branching: __('Branching'),
  };
  return map[props.quiz.type] || capitalize(props.quiz.type) || __('Quiz');
});

const statusLabel = computed(() => {
  const map = {
    published: __('Published'),
    draft: __('Draft'),
    archived: __('Archived'),
  };
  return map[props.quiz.status] || capitalize(props.quiz.status) || __('Draft');
});

const typeBadgeVariant = computed(() => {
  switch (props.quiz.type) {
    case 'personality':
      return 'info';
    case 'trivia':
      return 'default';
    case 'survey':
      return 'success';
    case 'weighted':
      return 'danger';
    case 'branching':
      return 'neutral';
    case 'poll':
    default:
      return 'neutral';
  }
});

const statusBadgeVariant = computed(() => {
  switch (props.quiz.status) {
    case 'published':
      return 'success';
    case 'archived':
      return 'neutral';
    case 'draft':
    default:
      return 'default';
  }
});

const isProTemplate = computed(
  () =>
    PRO_TYPES.has(props.quiz.type) ||
    (props.quiz.template && PRO_TEMPLATES.has(props.quiz.template))
);

const coverClass = computed(() => {
  // Tint the cover background per type for visual variety without
  // hard-coding per-quiz colors — keeps grid readable at a glance.
  const byType = {
    personality: 'quizably-quizcard__cover--indigo',
    trivia: 'quizably-quizcard__cover--amber',
    survey: 'quizably-quizcard__cover--emerald',
    weighted: 'quizably-quizcard__cover--rose',
    branching: 'quizably-quizcard__cover--ink',
    poll: 'quizably-quizcard__cover--slate',
  };
  return byType[props.quiz.type] || 'quizably-quizcard__cover--indigo';
});

const templateUrl = computed(() => {
  if (thumbFailed.value) return '';
  if (!props.quiz.template) return '';
  const base = window.QUIZABLY_ADMIN?.pluginUrl;
  if (!base) return '';
  const trimmed = base.endsWith('/') ? base : `${base}/`;
  return `${trimmed}assets/templates/${props.quiz.template}.svg`;
});

const relativeUpdated = computed(() => formatRelative(props.quiz.updated_at));
// translators: %s is a relative time such as "3d ago".
const editedText = computed(() => sprintf(__('Edited %s'), relativeUpdated.value));

function capitalize(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
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
  // Backend returns 0..1 or 0..100 depending on controller; normalize.
  const pct = n <= 1 ? Math.round(n * 100) : Math.round(n);
  return `${pct}%`;
}

function formatRelative(isoish) {
  return shortRelativeTime(isoish, __('recently'));
}

function onThumbError() {
  thumbFailed.value = true;
}

function onCardClick() {
  // Card click = edit. The actions menu stops its own clicks from reaching here.
  emit('edit', props.quiz.id);
}
</script>

<style scoped>
.quizably-quizcard {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.quizably-quizcard__cover {
  position: relative;
  /* Auto-height so the SVG can dictate its own aspect ratio. We set a
     sensible min-height so the chip + corner badges still have breathing
     room when the thumbnail is missing. */
  min-height: 160px;
  padding: 0;
  display: block;
  overflow: hidden;
  background: linear-gradient(135deg, var(--brand-tint) 0%, var(--brand-bg) 100%);
  border-bottom: 1px solid var(--border-1);
}

.quizably-quizcard__cover--indigo {
  background: linear-gradient(135deg, #F5F3FF 0%, #E0E7FF 100%);
}
.quizably-quizcard__cover--amber {
  background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
}
.quizably-quizcard__cover--emerald {
  background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
}
.quizably-quizcard__cover--rose {
  background: linear-gradient(135deg, #FFF1F2 0%, #FECDD3 100%);
}
.quizably-quizcard__cover--ink {
  background: linear-gradient(135deg, #1F2937 0%, #0F172A 100%);
  color: #fff;
}
.quizably-quizcard__cover--slate {
  background: linear-gradient(135deg, #F4F3EE 0%, #E5E4DC 100%);
}

.quizably-quizcard__thumb {
  display: block;
  width: 100%;
  height: auto;
  /* `contain` ensures the full SVG (with its borders / decorations)
     stays visible — never crop. Background fill matches the cover so
     letterboxed regions blend seamlessly. */
  object-fit: contain;
  pointer-events: none;
}

.quizably-quizcard__thumb--fallback {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: flex-start;
  justify-content: flex-end;
  padding: 18px;
  width: auto;
  height: auto;
}

.quizably-quizcard__thumb-bar {
  width: 48px;
  height: 6px;
  background: currentColor;
  opacity: 0.35;
  border-radius: var(--r-pill);
}

.quizably-quizcard__thumb-line {
  width: 90px;
  height: 4px;
  background: currentColor;
  opacity: 0.22;
  border-radius: var(--r-pill);
}

.quizably-quizcard__thumb-line--short {
  width: 60px;
}

.quizably-quizcard__chip {
  position: absolute;
  bottom: 12px;
  inset-inline-start: 12px;
  display: inline-flex;
  align-self: flex-start;
  padding: 4px 10px;
  border-radius: var(--r-pill);
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  font-family: var(--f-mono);
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-2);
  box-shadow: var(--shadow-xs);
  z-index: 2;
}

.quizably-quizcard__cover--ink .quizably-quizcard__chip {
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.quizably-quizcard__body {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 16px 18px 14px;
  flex: 1;
}

.quizably-quizcard__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
}

.quizably-quizcard__title {
  font-family: var(--f-display);
  font-weight: 600;
  font-size: 18px;
  line-height: 1.25;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.quizably-quizcard__stats {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--ink-3);
}

.quizably-quizcard__stat {
  display: inline-flex;
  align-items: baseline;
  gap: 4px;
}

.quizably-quizcard__stat strong {
  font-family: var(--f-mono);
  font-weight: 600;
  font-size: 13px;
  color: var(--ink-1);
}

.quizably-quizcard__stat-sep {
  width: 2px;
  height: 2px;
  background: var(--ink-4);
  border-radius: 50%;
}

.quizably-quizcard__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding-top: 10px;
  border-top: 1px solid var(--border-1);
  margin-top: auto;
}

.quizably-quizcard__time {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.04em;
  color: var(--ink-4);
  text-transform: uppercase;
}
</style>
