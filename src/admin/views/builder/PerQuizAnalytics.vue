<template>
  <section class="pqa-section">
    <header class="pqa-section__head">
      <h3>{{ __('Analytics') }}</h3>
      <p class="pqa-section__desc">
        {{ __('Lifetime performance for this quiz. Updates when new submissions land.') }}
      </p>
    </header>

    <div
      v-if="loading"
      class="pqa-loading"
      aria-live="polite"
    >
      <span
        class="pqa-spinner"
        aria-hidden="true"
      />
      <span>{{ __('Loading analytics…') }}</span>
    </div>

    <div
      v-else-if="!data"
      class="pqa-empty"
    >
      <p>{{ __("Analytics couldn't be loaded yet. Try again after a submission lands.") }}</p>
    </div>

    <div
      v-else
      class="pqa-body"
    >
      <div class="pqa-stats">
        <div class="pqa-stat">
          <span class="pqa-stat__label">{{ __('Submissions') }}</span>
          <span class="pqa-stat__value">{{ formatCount(data.total_submissions) }}</span>
          <span class="pqa-stat__hint">{{ completedHint }}</span>
        </div>
        <div class="pqa-stat">
          <span class="pqa-stat__label">{{ __('Completion') }}</span>
          <span class="pqa-stat__value">{{ formatPercent(data.completion_rate) }}</span>
          <span class="pqa-stat__hint">{{ __('of started submissions') }}</span>
        </div>
        <div class="pqa-stat">
          <span class="pqa-stat__label">{{ __('Leads') }}</span>
          <span class="pqa-stat__value">{{ formatCount(data.leads_captured) }}</span>
          <span class="pqa-stat__hint">{{ conversionHint }}</span>
        </div>
        <div
          v-if="showAvgScore"
          class="pqa-stat"
        >
          <span class="pqa-stat__label">{{ __('Avg score') }}</span>
          <span class="pqa-stat__value">{{ data.avg_score }}</span>
          <span class="pqa-stat__hint">{{ __('points per completion') }}</span>
        </div>
      </div>

      <div
        v-if="distributionRows.length"
        class="pqa-dist"
      >
        <h4 class="pqa-dist__title">
          {{ __('Result distribution') }}
        </h4>
        <ul class="pqa-dist__list">
          <li
            v-for="row in distributionRows"
            :key="row.result_id"
            class="pqa-dist__row"
          >
            <span class="pqa-dist__label">{{ row.label }}</span>
            <span
              class="pqa-dist__bar"
              :style="{ width: row.pct + '%' }"
              :aria-label="`${row.pct}%`"
            />
            <span class="pqa-dist__count">{{ row.count }} · {{ row.pct }}%</span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Question analytics -->
  <section class="pqa-section pqa-section--questions">
    <header class="pqa-section__head">
      <h3>{{ __('Questions') }}</h3>
      <p class="pqa-section__desc">
        {{ __('Drop-off funnel and answer distribution per question.') }}
      </p>
    </header>

    <!-- Export CSV button (data loaded) -->
    <div
      v-if="qItems.length > 0"
      class="pqa-q-export-row"
    >
      <button
        type="button"
        class="pqa-btn-export"
        :disabled="exporting"
        @click="exportCsv"
      >
        {{ exporting ? __('Exporting…') : __('Export CSV') }}
      </button>
    </div>

    <!-- Loading -->
    <div
      v-if="qLoading"
      class="pqa-loading"
      aria-live="polite"
    >
      <span
        class="pqa-spinner"
        aria-hidden="true"
      />
      <span>{{ __('Loading question analytics…') }}</span>
    </div>

    <!-- Error -->
    <div
      v-else-if="qError"
      class="pqa-empty"
    >
      <p>{{ qError }}</p>
    </div>

    <!-- No questions yet -->
    <div
      v-else-if="qItems.length === 0"
      class="pqa-empty"
    >
      <p>{{ __('No question data yet — analytics will appear after the first completed submission.') }}</p>
    </div>

    <!-- Table -->
    <div
      v-else-if="qItems.length > 0"
      class="pqa-q-body"
    >
      <div class="pqa-q-table">
        <!-- Header row -->
        <div class="pqa-q-head">
          <span class="pqa-q-cell pqa-q-cell--title">{{ __('Question') }}</span>
          <span class="pqa-q-cell pqa-q-cell--num">{{ __('Reached') }}</span>
          <span class="pqa-q-cell pqa-q-cell--num">{{ __('Answered') }}</span>
          <span class="pqa-q-cell pqa-q-cell--num">{{ __('Drop-off') }}</span>
          <span class="pqa-q-cell pqa-q-cell--num">{{ __('Avg time') }}</span>
        </div>

        <!-- Data rows -->
        <div
          v-for="(item, idx) in qItems"
          :key="item.question_id"
          class="pqa-q-group"
        >
          <!-- Question summary row -->
          <div
            class="pqa-q-row"
            :class="{ 'pqa-q-row--expanded': expandedQ === item.question_id }"
          >
            <button
              type="button"
              class="pqa-q-cell pqa-q-cell--title pqa-q-expand-btn"
              :aria-expanded="expandedQ === item.question_id"
              :aria-controls="`pqa-answers-${item.question_id}`"
              @click="toggleExpand(item.question_id)"
            >
              <span class="pqa-q-num">{{ idx + 1 }}</span>
              <span class="pqa-q-title">{{ truncate(item.question_title, 60) }}</span>
              <span
                v-if="item.answers.length"
                class="pqa-q-chevron"
                aria-hidden="true"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12">
                  <polyline :points="expandedQ === item.question_id ? '18 15 12 9 6 15' : '6 9 12 15 18 9'" />
                </svg>
              </span>
            </button>
            <span class="pqa-q-cell pqa-q-cell--num">{{ formatCount(item.reached) }}</span>
            <span class="pqa-q-cell pqa-q-cell--num">{{ formatCount(item.answered) }}</span>
            <span
              class="pqa-q-cell pqa-q-cell--num"
              :class="dropOffClass(item.drop_off_rate)"
            >
              {{ item.drop_off_rate.toFixed(1) }}%
            </span>
            <span class="pqa-q-cell pqa-q-cell--num pqa-q-cell--time">
              {{ formatAvgTime(item.avg_time_ms) }}
            </span>
          </div>

          <!-- Expandable answer distribution -->
          <div
            v-if="expandedQ === item.question_id && item.answers.length"
            :id="`pqa-answers-${item.question_id}`"
            class="pqa-a-list"
          >
            <div
              v-for="ans in item.answers"
              :key="ans.answer_id"
              class="pqa-a-row"
            >
              <span class="pqa-a-text">{{ truncate(ans.answer_text || __('(no label)'), 55) }}</span>
              <span class="pqa-a-bar-wrap">
                <span
                  class="pqa-a-bar"
                  :style="{ width: ans.pct + '%', opacity: 0.4 + (ans.pct / 100) * 0.6 }"
                />
              </span>
              <span class="pqa-a-pct">{{ ans.count }} · {{ ans.pct }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useAnalyticsStore } from '@admin/stores/analytics';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { api } from '@admin/api/client';
import { useToast } from '@admin/ui';
import { __, sprintf } from '@shared/i18n';

const analyticsStore = useAnalyticsStore();
const builderStore = useQuizBuilderStore();
const toast = useToast();

const exporting = ref(false);


const quizId = computed(() => builderStore.quiz?.id ?? null);
const data = computed(() => (quizId.value ? analyticsStore.perQuiz[quizId.value] : null));
const loading = computed(() =>
  quizId.value ? Boolean(analyticsStore.loadingQuiz[quizId.value]) && !data.value : false
);

// ---- Question analytics state ----
const qItems = ref([]);
const qLoading = ref(false);
const qError = ref(null);
const expandedQ = ref(null);

const showAvgScore = computed(() => {
  if (!data.value) return false;
  // Only trivia-style quizzes produce a numeric score; hide for personality
  // results where avg_score would always be null.
  if (data.value.avg_score === null || data.value.avg_score === undefined) return false;
  return true;
});

const distributionRows = computed(() => {
  if (!data.value?.result_distribution?.length) return [];
  const results = builderStore.results ?? [];
  const total = data.value.result_distribution.reduce((acc, r) => acc + r.count, 0);
  return data.value.result_distribution
    .map((r) => {
      const result = results.find((x) => x.id === r.result_id);
      return {
        result_id: r.result_id,
        // translators: %d is the numeric result ID.
        label: result?.title || sprintf(__('Result #%d'), r.result_id),
        count: r.count,
        pct: total > 0 ? Math.round((r.count / total) * 100) : 0,
      };
    })
    .sort((a, b) => b.count - a.count);
});

// translators: 1: number of completed submissions, 2: number still in progress.
const completedHint = computed(() => sprintf(__('%1$s completed · %2$s in progress'), data.value?.completed, data.value?.abandoned));
// translators: %s is a percentage such as 42%.
const conversionHint = computed(() => sprintf(__('%s conversion'), formatPercent(data.value?.lead_conversion_rate)));

function formatCount(value) {
  if (value === null || value === undefined) return '—';
  const n = Number(value);
  if (!Number.isFinite(n)) return '—';
  return n.toLocaleString();
}

function formatPercent(value) {
  if (value === null || value === undefined) return '—';
  const n = Number(value);
  if (!Number.isFinite(n)) return '—';
  const pct = n <= 1 ? Math.round(n * 100) : Math.round(n);
  return `${pct}%`;
}

function truncate(str, maxLen) {
  if (!str) return '';
  return str.length > maxLen ? str.slice(0, maxLen) + '…' : str;
}

function dropOffClass(rate) {
  if (rate >= 20) return 'pqa-q-cell--danger';
  if (rate >= 10) return 'pqa-q-cell--warn';
  return 'pqa-q-cell--ok';
}

function formatAvgTime(ms) {
  if (ms === null || ms === undefined) return '—';
  const n = Number(ms);
  if (!Number.isFinite(n) || n < 0) return '—';
  return (n / 1000).toFixed(1) + 's';
}

async function exportCsv() {
  if (!quizId.value || exporting.value) return;
  exporting.value = true;
  try {
    const payload = await api.get(`analytics/${quizId.value}/export`, { include_questions: '1' });
    const today = new Date().toISOString().slice(0, 10);
    const filename = payload?.filename || `analytics-${quizId.value}-${today}.csv`;
    const blob = new Blob([payload?.csv ?? ''], { type: 'text/csv;charset=utf-8' });
    const objUrl = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = objUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(objUrl);
    toast.push({ variant: 'success', title: __('Export ready'), message: __('Analytics CSV downloaded.') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Export failed'), message: e.message });
  } finally {
    exporting.value = false;
  }
}

function toggleExpand(questionId) {
  expandedQ.value = expandedQ.value === questionId ? null : questionId;
}

async function loadQuestionAnalytics() {
  if (!quizId.value) return;
  qLoading.value = true;
  qError.value = null;
  try {
    const res = await api.get(`analytics/questions/${quizId.value}`);
    qItems.value = res?.items ?? [];
  } catch (e) {
    qError.value = e.message || __('Could not load question analytics.');
  } finally {
    qLoading.value = false;
  }
}

function load() {
  if (quizId.value) {
    analyticsStore.fetchQuiz(quizId.value).catch(() => {});
    loadQuestionAnalytics();
  }
}

onMounted(load);

// Re-fetch if the user navigates between two builders without unmounting.
watch(quizId, (id, prev) => {
  if (id && id !== prev) load();
});
</script>

<style scoped>
.pqa-section {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  padding: 20px 22px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.pqa-section__head h3 {
  font-family: var(--f-display);
  font-size: 15px;
  font-weight: 500;
  letter-spacing: -0.005em;
  margin: 0;
  color: var(--ink-1);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.pqa-section__desc {
  font-size: 12.5px;
  color: var(--ink-3);
  margin: 4px 0 0;
  line-height: 1.5;
}

.pqa-loading {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--ink-3);
  font-size: 13px;
  padding: 12px 0;
}

.pqa-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: pqa-spin 700ms linear infinite;
}

@keyframes pqa-spin {
  to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
  .pqa-spinner { animation-duration: 0ms; }
}

.pqa-empty p {
  font-size: 13px;
  color: var(--ink-3);
  margin: 0;
}

.pqa-body {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.pqa-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
}

.pqa-stat {
  background: var(--bg-subtle);
  border-radius: var(--r-sm);
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.pqa-stat__label {
  font-family: var(--f-mono);
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-3);
}

.pqa-stat__value {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 22px;
  line-height: 1.1;
  letter-spacing: -0.01em;
  color: var(--ink-1);
}

.pqa-stat__hint {
  font-size: 11px;
  color: var(--ink-3);
}

.pqa-dist__title {
  font-family: var(--f-display);
  font-size: 13px;
  font-weight: 500;
  margin: 0 0 10px;
  color: var(--ink-2);
}

.pqa-dist__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pqa-dist__row {
  display: grid;
  grid-template-columns: minmax(80px, 1.2fr) minmax(0, 3fr) minmax(80px, auto);
  gap: 10px;
  align-items: center;
}

.pqa-dist__label {
  font-size: 13px;
  color: var(--ink-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.pqa-dist__bar {
  display: block;
  height: 8px;
  min-width: 2px;
  border-radius: var(--r-pill);
  background: var(--brand);
  transition: width 300ms ease;
}

.pqa-dist__count {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  text-align: end;
}

/* ---- Export button ---- */
.pqa-q-export-row {
  display: flex;
  justify-content: flex-end;
}

.pqa-btn-export {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  background: transparent;
  color: var(--ink-2);
  font-family: var(--f-sans);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: background 150ms ease, border-color 150ms ease;
}

.pqa-btn-export:hover {
  background: var(--bg-subtle);
  border-color: var(--border-3, var(--border-2));
}

.pqa-btn-export:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus, 0 0 0 3px color-mix(in srgb, var(--brand) 30%, transparent));
  border-radius: var(--r-xs);
}

/* ---- Question analytics table ---- */
.pqa-q-body {
  overflow-x: auto;
}

.pqa-q-table {
  min-width: 480px;
}

.pqa-q-head,
.pqa-q-row {
  display: grid;
  grid-template-columns: 1fr 80px 80px 80px 80px;
  gap: 0;
  align-items: center;
  border-bottom: 1px solid var(--border-1);
}

.pqa-q-head {
  padding: 6px 0;
}

.pqa-q-cell {
  padding: 0 10px;
  font-size: 12px;
  color: var(--ink-3);
  font-family: var(--f-mono);
  letter-spacing: 0.03em;
  text-transform: uppercase;
  font-size: 10px;
}

.pqa-q-cell--title {
  font-family: var(--f-sans);
  text-transform: none;
  font-size: 12px;
  letter-spacing: 0;
  font-weight: 600;
}

.pqa-q-cell--num {
  text-align: end;
}

.pqa-q-row {
  padding: 2px 0;
  min-height: 36px;
}

.pqa-q-row .pqa-q-cell {
  font-size: 13px;
  color: var(--ink-2);
  text-transform: none;
  font-family: var(--f-sans);
  letter-spacing: 0;
  font-weight: 400;
}

.pqa-q-expand-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: 0;
  padding: 8px 10px;
  cursor: pointer;
  text-align: start;
  width: 100%;
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-2);
}

.pqa-q-expand-btn:hover {
  color: var(--ink-1);
}

.pqa-q-expand-btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus, 0 0 0 3px color-mix(in srgb, var(--brand) 30%, transparent));
  border-radius: var(--r-xs);
}

.pqa-q-num {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--bg-subtle);
  border: 1px solid var(--border-1);
  font-family: var(--f-mono);
  font-size: 10px;
  font-weight: 600;
  color: var(--ink-3);
}

.pqa-q-title {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pqa-q-chevron {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  color: var(--ink-3);
}

/* Drop-off colour coding */
.pqa-q-cell--ok {
  color: var(--success, #16a34a);
  font-weight: 500;
}

.pqa-q-cell--warn {
  color: var(--warning, #d97706);
  font-weight: 500;
}

.pqa-q-cell--danger {
  color: var(--danger, #dc2626);
  font-weight: 500;
}

.pqa-q-cell--time {
  color: var(--ink-3);
  font-family: var(--f-mono);
  font-size: 11px;
}

/* Answer distribution inside an expanded question */
.pqa-a-list {
  background: var(--bg-subtle);
  border-bottom: 1px solid var(--border-1);
  padding-block: 10px 12px;

  padding-inline: 38px 16px;
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.pqa-a-row {
  display: grid;
  grid-template-columns: minmax(100px, 1.5fr) minmax(0, 3fr) 90px;
  gap: 10px;
  align-items: center;
}

.pqa-a-text {
  font-size: 12.5px;
  color: var(--ink-2);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pqa-a-bar-wrap {
  height: 6px;
  background: var(--bg-surface);
  border-radius: var(--r-pill);
  overflow: hidden;
  border: 1px solid var(--border-1);
}

.pqa-a-bar {
  display: block;
  height: 100%;
  min-width: 2px;
  border-radius: var(--r-pill);
  background: var(--color-primary, var(--brand));
  transition: width 280ms ease;
}

.pqa-a-pct {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  text-align: end;
}
</style>
