<template>
  <div class="dashboard-view">
    <header class="dashboard-view__header">
      <div>
        <h1 class="dashboard-view__title">
          {{ greeting }}<em>.</em>
        </h1>
        <p class="dashboard-view__sub">
          {{ subtitle }}
        </p>
      </div>
      <div class="dashboard-view__cta">
        <Button
          variant="primary"
          size="md"
          @click="openNewQuizModal"
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
          {{ __('New quiz') }}
        </Button>
      </div>
    </header>

    <ReviewPrompt />

    <!--
      Every KPI tile links out — dashboards that dead-end on a number are a
      bad pattern. "Total quizzes" and "Leads captured" have dedicated list
      views (/quizzes, /leads). "Submissions" and "Completion rate" don't
      have a site-wide analytics view yet, so they route to /quizzes too —
      each quiz card there shows its own submission count and completion
      rate, which is the closest existing breakdown.
    -->
    <section
      class="dashboard-view__kpis"
      :aria-label="__('Site-wide KPIs')"
    >
      <DashboardKpiCard
        :label="__('Total quizzes')"
        :value="totalQuizzes"
        :hint="quizzesHint"
        to="/quizzes"
      >
        <template #icon>
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <rect
              x="3"
              y="4"
              width="18"
              height="16"
              rx="2"
            />
            <path d="M7 9h10" />
            <path d="M7 13h7" />
            <path d="M7 17h4" />
          </svg>
        </template>
      </DashboardKpiCard>

      <DashboardKpiCard
        :label="__('Submissions')"
        :value="submissions30d"
        :hint="__('Completed, last 30 days')"
        to="/quizzes"
      >
        <template #icon>
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
            <polyline points="22 4 12 14.01 9 11.01" />
          </svg>
        </template>
      </DashboardKpiCard>

      <DashboardKpiCard
        :label="__('Leads captured')"
        :value="leads30d"
        :hint="__('Forms, last 30 days')"
        to="/leads"
      >
        <template #icon>
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle
              cx="9"
              cy="7"
              r="4"
            />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
        </template>
      </DashboardKpiCard>

      <DashboardKpiCard
        :label="__('Completion rate')"
        :value="avgCompletion"
        :hint="__('Started → Finished, all-time')"
        to="/quizzes"
      >
        <template #icon>
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M22 12A10 10 0 1 1 12 2" />
            <polyline points="22 4 12 14 9 11" />
          </svg>
        </template>
      </DashboardKpiCard>
    </section>

    <section class="dashboard-view__charts">
      <SubmissionsChart :series="submissionsByDay" />
      <TopQuizzesChart :quizzes="topQuizzes" />
    </section>

    <section class="dashboard-view__grid">
      <Card
        padding="none"
        class="dashboard-view__panel dashboard-view__panel--recent"
      >
        <header class="dashboard-view__panel-head">
          <div>
            <h2 class="dashboard-view__panel-title">
              {{ __('Recent quizzes') }}
            </h2>
            <p class="dashboard-view__panel-sub">
              {{ recentEditsText }}
            </p>
          </div>
          <RouterLink
            to="/quizzes"
            class="dashboard-view__link"
          >
            {{ __('View all') }}
            <svg
              class="q-flip-rtl"
              viewBox="0 0 24 24"
              width="12"
              height="12"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            ><polyline points="9 18 15 12 9 6" /></svg>
          </RouterLink>
        </header>

        <div
          v-if="loadingRecent && !recentQuizzes.length"
          class="dashboard-view__loading"
        >
          <span class="dashboard-view__spinner" />
          {{ __('Loading…') }}
        </div>

        <ul
          v-else-if="recentQuizzes.length"
          class="dashboard-view__list"
        >
          <li
            v-for="quiz in recentQuizzes"
            :key="quiz.id"
            class="dashboard-view__row"
            tabindex="0"
            role="button"
            @click="openQuiz(quiz.id)"
            @keydown.enter.prevent="openQuiz(quiz.id)"
            @keydown.space.prevent="openQuiz(quiz.id)"
          >
            <div class="dashboard-view__row-main">
              <span class="dashboard-view__row-title">{{ quiz.title || __('Untitled quiz') }}</span>
              <span class="dashboard-view__row-meta">
                <Badge
                  :variant="statusVariant(quiz.status)"
                  size="sm"
                >
                  {{ statusLabel(quiz.status) }}
                </Badge>
                <span class="dashboard-view__row-type">{{ humanType(quiz.type) }}</span>
                <span class="dashboard-view__row-dot">·</span>
                <span class="dashboard-view__row-time">
                  {{ updatedText(quiz.updated_at) }}
                </span>
              </span>
            </div>
            <span
              class="dashboard-view__row-arrow"
              aria-hidden="true"
            >
              <svg
                viewBox="0 0 24 24"
                width="16"
                height="16"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              ><polyline points="9 18 15 12 9 6" /></svg>
            </span>
          </li>
        </ul>

        <div
          v-else
          class="dashboard-view__empty"
        >
          <h3>{{ __('No quizzes yet') }}</h3>
          <p>{{ __('Start with a template — it takes 2 minutes.') }}</p>
          <Button
            variant="primary"
            size="sm"
            @click="openNewQuizModal"
          >
            {{ __('Build your first quiz') }}
          </Button>
        </div>
      </Card>

      <div class="dashboard-view__side">
        <Card
          padding="md"
          class="dashboard-view__panel dashboard-view__panel--actions"
        >
          <h2 class="dashboard-view__panel-title">
            {{ __('Quick actions') }}
          </h2>
          <p class="dashboard-view__panel-sub">
            {{ __('Jump straight to where you were going.') }}
          </p>
          <div class="dashboard-view__actions">
            <RouterLink
              to="/leads"
              class="dashboard-view__action"
            >
              <span
                class="dashboard-view__action-icon dashboard-view__action-icon--leads"
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
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                  <circle
                    cx="9"
                    cy="7"
                    r="4"
                  />
                </svg>
              </span>
              <span class="dashboard-view__action-body">
                <span class="dashboard-view__action-title">{{ __('Review leads') }}</span>
                <span class="dashboard-view__action-desc">{{ __('Export, filter, and follow up.') }}</span>
              </span>
            </RouterLink>

            <RouterLink
              to="/integrations"
              class="dashboard-view__action"
            >
              <span
                class="dashboard-view__action-icon dashboard-view__action-icon--integrations"
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
                  <polyline points="16 18 22 12 16 6" />
                  <polyline points="8 6 2 12 8 18" />
                </svg>
              </span>
              <span class="dashboard-view__action-body">
                <span class="dashboard-view__action-title">{{ __('Connect a tool') }}</span>
                <span class="dashboard-view__action-desc">{{ __('Send results to your CRM via webhook.') }}</span>
              </span>
            </RouterLink>

            <RouterLink
              to="/question-bank"
              class="dashboard-view__action"
            >
              <span
                class="dashboard-view__action-icon dashboard-view__action-icon--bank"
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
                  <path d="M4 6a2 2 0 0 1 2-2h11l3 3v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
                  <path d="M9 12c0-1.1.9-2 2-2s2 .9 2 2-2 1.5-2 3" />
                  <line
                    x1="11"
                    y1="18"
                    x2="11.01"
                    y2="18"
                  />
                </svg>
              </span>
              <span class="dashboard-view__action-body">
                <span class="dashboard-view__action-title">{{ __('Browse question bank') }}</span>
                <span class="dashboard-view__action-desc">{{ __('Reuse questions across quizzes.') }}</span>
              </span>
            </RouterLink>
          </div>
        </Card>

        <Card
          padding="md"
          class="dashboard-view__panel dashboard-view__panel--tip"
        >
          <span class="dashboard-view__tip-eyebrow">{{ __('Tip') }}</span>
          <h2 class="dashboard-view__panel-title">
            {{ __('Embed anywhere') }}
          </h2>
          <!-- eslint-disable-next-line vue/no-v-html -- static, developer-controlled markup only -->
          <p
            class="dashboard-view__tip-body"
            v-html="tipBody"
          />
          <RouterLink
            to="/settings"
            class="dashboard-view__link"
          >
            {{ __('Configure defaults') }}
            <svg
              class="q-flip-rtl"
              viewBox="0 0 24 24"
              width="12"
              height="12"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            ><polyline points="9 18 15 12 9 6" /></svg>
          </RouterLink>
        </Card>
      </div>
    </section>

    <NewQuizModal v-model="showNewQuizModal" @created="openQuiz" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { Badge, Button, Card } from '@admin/ui';
import DashboardKpiCard from './dashboard/DashboardKpiCard.vue';
import NewQuizModal from './dashboard/NewQuizModal.vue';
import ReviewPrompt from './dashboard/ReviewPrompt.vue';
import SubmissionsChart from './dashboard/SubmissionsChart.vue';
import TopQuizzesChart from './dashboard/TopQuizzesChart.vue';
import { useQuizzesStore } from '@admin/stores/quizzes';
import { useAnalyticsStore } from '@admin/stores/analytics';
import { __, _n, sprintf } from '@shared/i18n';

const router = useRouter();
const quizzesStore = useQuizzesStore();
const analyticsStore = useAnalyticsStore();

const showNewQuizModal = ref(false);
const loadingRecent = ref(false);

/**
 * The "Recent quizzes" panel uses a separate, lightweight read so it doesn't
 * collide with the filters/pagination on /quizzes. We sort by updated_at and
 * cap at 5 — enough to show momentum without taking over the dashboard.
 */
const RECENT_LIMIT = 5;

const recentQuizzes = computed(() => quizzesStore.items.slice(0, RECENT_LIMIT));

function formatCount(value) {
  if (value === null || value === undefined) return '—';
  const n = Number(value);
  if (!Number.isFinite(n)) return '—';
  return n.toLocaleString();
}

const totalQuizzes = computed(() => formatCount(analyticsStore.site?.total_quizzes));
const submissions30d = computed(() => formatCount(analyticsStore.site?.total_submissions_30d));
const leads30d = computed(() => formatCount(analyticsStore.site?.total_leads_30d));

const avgCompletion = computed(() => {
  const rate = analyticsStore.site?.avg_completion_rate;
  if (typeof rate !== 'number' || !Number.isFinite(rate)) return '—';
  return `${Math.round(rate * 100)}%`;
});

const quizzesHint = computed(() => {
  const site = analyticsStore.site;
  if (!site) return __('Live across all statuses');
  const parts = [
    // translators: %d is a number of published quizzes.
    sprintf(__('%d published'), site.published_quizzes),
    // translators: %d is a number of draft quizzes.
    sprintf(__('%d draft'), site.draft_quizzes),
  ];
  // translators: %d is a number of archived quizzes.
  if (site.archived_quizzes) parts.push(sprintf(__('%d archived'), site.archived_quizzes));
  return parts.join(' · ');
});

const submissionsByDay = computed(() => analyticsStore.site?.submissions_by_day ?? []);
const topQuizzes = computed(() => analyticsStore.site?.top_quizzes ?? []);

const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 5) return __('Still up');
  if (hour < 12) return __('Good morning');
  if (hour < 18) return __('Good afternoon');
  return __('Good evening');
});

const subtitle = computed(() => {
  const site = analyticsStore.site;
  if (!site) return __('Here’s what’s happening across your quizzes.');
  if (!site.total_quizzes) return __('Build your first quiz to start collecting responses.');
  if (!site.total_submissions_30d) {
    return __('Your quizzes are live but quiet — share the link to drive responses.');
  }
  return sprintf(
    // translators: %s is a formatted number of submissions.
    _n('%s submission in the last 30 days.', '%s submissions in the last 30 days.', site.total_submissions_30d),
    formatCount(site.total_submissions_30d)
  );
});

const recentEditsText = computed(() =>
  // translators: %d is how many recent quizzes are listed.
  sprintf(__('Your last %d edits'), recentQuizzes.value.length || 5)
);

// translators: %s is a relative time such as "3 days ago".
const updatedText = (iso) => sprintf(__('Updated %s'), relativeTime(iso));

// Static, developer-controlled markup only (no user data) so v-html is safe.
const tipBody = computed(() =>
  sprintf(
    // translators: %s is the shortcode, shown as code.
    __('Use the %s shortcode or the Quiz Builder Gutenberg block to drop a quiz into any post or page.'),
    '<code>[quizably_quiz id="…"]</code>'
  )
);

function openNewQuizModal() {
  showNewQuizModal.value = true;
}

function openQuiz(id) {
  router.push(`/quiz/${id}/questions`);
}

function humanType(type) {
  const labels = {
    personality: __('Personality'),
    trivia: __('Trivia'),
    survey: __('Survey'),
    poll: __('Poll'),
    weighted: __('Weighted'),
    branching: __('Branching'),
  };
  return labels[type] || type || '—';
}

// Display label for a quiz status (the badge upper-cases it).
function statusLabel(status) {
  const labels = {
    published: __('Published'),
    draft: __('Draft'),
    archived: __('Archived'),
  };
  return labels[status] || status;
}

function statusVariant(status) {
  if (status === 'published') return 'success';
  if (status === 'archived') return 'neutral';
  return 'info';
}

/**
 * Compact relative-time formatter. Intl.RelativeTimeFormat handles
 * pluralization & locale; we just pick the largest unit that fits.
 */
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

onMounted(async () => {
  loadingRecent.value = true;
  // Fire both reads in parallel — neither blocks the other and a failed
  // analytics aggregate shouldn't stop the recent-quizzes list rendering.
  Promise.all([
    quizzesStore.fetchList({
      orderby: 'updated_at',
      order: 'DESC',
      limit: RECENT_LIMIT,
      offset: 0,
    }).finally(() => {
      loadingRecent.value = false;
    }),
    analyticsStore.fetchSite(),
  ]).catch(() => {});
});
</script>

<style scoped>
.dashboard-view {
  max-width: 1200px;
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.dashboard-view__header {
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

.dashboard-view__title {
  font-family: var(--f-display);
  font-weight: 600;
  font-size: 18px;
  line-height: 1.25;
  letter-spacing: -0.005em;
  color: var(--ink-1);
  margin: 0;
}

.dashboard-view__title em {
  font-style: italic;
  color: var(--brand);
}

.dashboard-view__sub {
  font-family: var(--f-sans);
  font-size: 12.5px;
  line-height: 1.45;
  color: var(--ink-3);
  margin: 2px 0 0;
  max-width: 64ch;
}

.dashboard-view__kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.dashboard-view__charts {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
  gap: 16px;
}

@media (max-width: 980px) {
  .dashboard-view__charts {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 980px) {
  .dashboard-view__kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 520px) {
  .dashboard-view__kpis {
    grid-template-columns: 1fr;
  }
}

.dashboard-view__grid {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
  gap: 20px;
  align-items: start;
}

@media (max-width: 980px) {
  .dashboard-view__grid {
    grid-template-columns: 1fr;
  }
}

.dashboard-view__panel {
  background: var(--bg-surface);
}

.dashboard-view__panel-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px 14px;
  border-bottom: 1px solid var(--border-1);
}

.dashboard-view__panel-title {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 18px;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
}

.dashboard-view__panel-sub {
  font-family: var(--f-sans);
  font-size: 12.5px;
  color: var(--ink-3);
  margin: 4px 0 0;
}

.dashboard-view__link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--brand);
  text-decoration: none;
  transition: color 150ms;
}

.dashboard-view__link:hover {
  color: var(--brand-hover);
}

.dashboard-view__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.dashboard-view__row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 20px;
  cursor: pointer;
  border-bottom: 1px solid var(--border-1);
  transition: background 150ms ease;
  outline: none;
}

.dashboard-view__row:last-child {
  border-bottom: none;
}

.dashboard-view__row:hover {
  background: var(--bg-subtle);
}

.dashboard-view__row:focus-visible {
  background: var(--brand-tint);
  box-shadow: var(--shadow-focus);
}

.dashboard-view__row-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dashboard-view__row-title {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dashboard-view__row-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--ink-3);
}

.dashboard-view__row-type {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--ink-4);
}

.dashboard-view__row-dot {
  color: var(--ink-5);
}

.dashboard-view__row-arrow {
  color: var(--ink-4);
  transition: color 150ms, transform 150ms;
}

.dashboard-view__row:hover .dashboard-view__row-arrow {
  color: var(--brand);
  transform: translateX(2px);
}

.dashboard-view__row-arrow:dir(rtl) {
  transform: scaleX(-1);
}

.dashboard-view__row:hover .dashboard-view__row-arrow:dir(rtl) {
  transform: scaleX(-1) translateX(2px);
}

.dashboard-view__loading,
.dashboard-view__empty {
  padding: 40px 20px;
  text-align: center;
  color: var(--ink-3);
  font-family: var(--f-sans);
  font-size: 13px;
}

.dashboard-view__empty h3 {
  font-family: var(--f-display);
  font-size: 18px;
  font-weight: 500;
  color: var(--ink-1);
  margin: 0 0 6px;
}

.dashboard-view__empty p {
  margin: 0 0 16px;
}

.dashboard-view__loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.dashboard-view__spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-dash-spin 700ms linear infinite;
}

@keyframes quizably-dash-spin {
  to { transform: rotate(360deg); }
}

.dashboard-view__side {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Keep the side column (Quick actions + Tip) in view while the Recent
   quizzes list scrolls past on tall pages. Scoped to the desktop grid
   layout — below the 980px breakpoint the grid collapses to a single
   column, where sticky would awkwardly hover the sidebar over content. */
@media (min-width: 981px) {
  .dashboard-view__side {
    position: sticky;
    top: 16px;
    align-self: start;
  }
}

.dashboard-view__actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 16px;
}

.dashboard-view__action {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: var(--r-md);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  text-decoration: none;
  transition:
    background 150ms ease,
    border-color 150ms ease,
    transform 150ms ease;
}

.dashboard-view__action:hover {
  background: var(--bg-surface);
  border-color: var(--border-3);
  transform: translateY(-1px);
}

.dashboard-view__action:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.dashboard-view__action-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: var(--r-sm);
  background: var(--bg-subtle);
  color: var(--ink-3);
  flex-shrink: 0;
}

.dashboard-view__action-icon :deep(svg) {
  width: 16px;
  height: 16px;
}

.dashboard-view__action-icon--leads {
  background: #FEF3F2;
  color: #B23B2C;
}

.dashboard-view__action-icon--integrations {
  background: #ECFDF5;
  color: #047857;
}

.dashboard-view__action-icon--bank {
  background: var(--brand-tint);
  color: var(--brand);
}

.dashboard-view__action-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.dashboard-view__action-title {
  font-family: var(--f-sans);
  font-size: 13.5px;
  font-weight: 600;
  color: var(--ink-1);
}

.dashboard-view__action-desc {
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--ink-3);
}

.dashboard-view__panel--tip {
  background:
    linear-gradient(160deg, var(--brand-tint) 0%, var(--bg-surface) 80%);
  border-color: var(--border-1);
}

.dashboard-view__tip-eyebrow {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--brand);
  background: var(--bg-surface);
  padding: 3px 8px;
  border-radius: var(--r-pill);
  margin-bottom: 12px;
  border: 1px solid var(--border-1);
}

.dashboard-view__tip-body {
  font-family: var(--f-sans);
  font-size: 13px;
  line-height: 1.55;
  color: var(--ink-2);
  margin: 8px 0 12px;
}

.dashboard-view__tip-body :deep(code) {
  font-family: var(--f-mono);
  font-size: 11.5px;
  background: var(--bg-surface);
  padding: 2px 6px;
  border-radius: var(--r-xs);
  border: 1px solid var(--border-1);
  color: var(--ink-2);
}

@media (prefers-reduced-motion: reduce) {
  .dashboard-view__row,
  .dashboard-view__row-arrow,
  .dashboard-view__action,
  .dashboard-view__spinner {
    transition: none;
    animation: none;
  }
}
</style>
