<template>
  <div class="quizzes-view">
    <header class="quizzes-view__header">
      <div class="quizzes-view__heading">
        <h1 class="quizzes-view__title">
          <!-- eslint-disable-next-line vue/no-v-html -- static, developer-controlled markup only -->
          <span v-html="__('Your <em>quizzes</em>')" />
        </h1>
        <p class="quizzes-view__sub">
          {{ totalHint }}
        </p>
      </div>
      <div class="quizzes-view__actions">
        <!-- Hidden file input for JSON import -->
        <input
          ref="importFileRef"
          type="file"
          accept=".json"
          class="quizzes-view__import-input"
          @change="onImportFileSelected"
        >
        <Button
          variant="outline"
          :loading="importing"
          @click="openImportPicker"
        >
          <template #icon-left>
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
              <polyline points="17 8 12 3 7 8" />
              <line
                x1="12"
                y1="3"
                x2="12"
                y2="15"
              />
            </svg>
          </template>
          {{ __('Import quiz') }}
        </Button>
        <Button
          variant="outline"
          :aria-pressed="viewMode === 'list'"
          @click="toggleViewMode"
        >
          <template #icon-left>
            <svg
              v-if="viewMode === 'grid'"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <line
                x1="8"
                y1="6"
                x2="21"
                y2="6"
              />
              <line
                x1="8"
                y1="12"
                x2="21"
                y2="12"
              />
              <line
                x1="8"
                y1="18"
                x2="21"
                y2="18"
              />
              <line
                x1="3"
                y1="6"
                x2="3.01"
                y2="6"
              />
              <line
                x1="3"
                y1="12"
                x2="3.01"
                y2="12"
              />
              <line
                x1="3"
                y1="18"
                x2="3.01"
                y2="18"
              />
            </svg>
            <svg
              v-else
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect
                x="3"
                y="3"
                width="7"
                height="7"
              />
              <rect
                x="14"
                y="3"
                width="7"
                height="7"
              />
              <rect
                x="3"
                y="14"
                width="7"
                height="7"
              />
              <rect
                x="14"
                y="14"
                width="7"
                height="7"
              />
            </svg>
          </template>
          {{ viewMode === 'grid' ? __('List view') : __('Card view') }}
        </Button>
        <Button
          variant="primary"
          @click="openNewQuizModal"
        >
          <template #icon-left>
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M12 5v14M5 12h14" />
            </svg>
          </template>
          {{ __('New quiz') }}
        </Button>
      </div>
    </header>

    <div class="quizzes-view__kpis">
      <DashboardKpiCard
        :label="__('Total quizzes')"
        :value="totalQuizzes"
        :hint="publishedHint"
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
              y="3"
              width="7"
              height="7"
              rx="1"
            />
            <rect
              x="14"
              y="3"
              width="7"
              height="7"
              rx="1"
            />
            <rect
              x="3"
              y="14"
              width="7"
              height="7"
              rx="1"
            />
            <rect
              x="14"
              y="14"
              width="7"
              height="7"
              rx="1"
            />
          </svg>
        </template>
      </DashboardKpiCard>

      <DashboardKpiCard
        :label="__('Submissions · 30d')"
        :value="submissions30d"
        :hint="submissionsHint"
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
            <path d="M22 4 12 14.01l-3-3" />
          </svg>
        </template>
      </DashboardKpiCard>

      <DashboardKpiCard
        :label="__('Leads · 30d')"
        :value="leads30d"
        :hint="leadsHint"
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
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle
              cx="9"
              cy="7"
              r="4"
            />
            <path d="M22 11h-6M19 8v6" />
          </svg>
        </template>
      </DashboardKpiCard>

      <DashboardKpiCard
        :label="__('Avg completion')"
        :value="avgCompletion"
        :hint="__('Across published quizzes')"
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
            <circle
              cx="12"
              cy="12"
              r="10"
            />
            <path d="M12 6v6l4 2" />
          </svg>
        </template>
      </DashboardKpiCard>
    </div>

    <QuizFilters v-model="filters" />

    <div
      v-if="store.loading"
      class="quizzes-view__loading"
      aria-live="polite"
    >
      <span
        class="quizzes-view__spinner"
        aria-hidden="true"
      />
      <span>{{ __('Loading quizzes…') }}</span>
    </div>

    <EmptyState
      v-else-if="!store.items.length && !hasActiveFilters"
      :title="__('No quizzes yet')"
      :description="__('Create your first quiz to start capturing leads and delighting visitors.')"
    >
      <template #icon>
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <rect
            x="3"
            y="3"
            width="7"
            height="7"
            rx="1"
          />
          <rect
            x="14"
            y="3"
            width="7"
            height="7"
            rx="1"
          />
          <rect
            x="3"
            y="14"
            width="7"
            height="7"
            rx="1"
          />
          <rect
            x="14"
            y="14"
            width="7"
            height="7"
            rx="1"
          />
        </svg>
      </template>
      <template #actions>
        <Button
          variant="primary"
          @click="openNewQuizModal"
        >
          {{ __('Create your first quiz') }}
        </Button>
      </template>
    </EmptyState>

    <EmptyState
      v-else-if="!store.items.length"
      :title="__('No quizzes match those filters')"
      :description="__('Try a different status, type, or search term.')"
    >
      <template #icon>
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
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
      </template>
      <template #actions>
        <Button
          variant="outline"
          @click="clearFilters"
        >
          {{ __('Clear filters') }}
        </Button>
      </template>
    </EmptyState>

    <div
      v-else-if="viewMode === 'grid'"
      class="quizzes-view__grid"
    >
      <QuizCard
        v-for="quiz in store.items"
        :key="quiz.id"
        :quiz="quiz"
        @edit="onEdit"
        @duplicate="requestDuplicate"
        @archive="onArchive"
        @restore="onRestore"
        @remove="requestRemove"
        @publish="onPublish"
      />
    </div>

    <QuizListTable
      v-else
      :quizzes="store.items"
      @edit="onEdit"
      @duplicate="requestDuplicate"
      @archive="onArchive"
      @restore="onRestore"
      @remove="requestRemove"
      @publish="onPublish"
    />

    <div
      v-if="!store.loading && store.items.length && totalPages > 1"
      class="quizzes-view__pager"
    >
      <span class="quizzes-view__pager-summary">{{ pagerSummary }}</span>
      <div class="quizzes-view__pager-controls">
        <Button
          variant="outline"
          size="sm"
          :disabled="currentPage <= 1"
          @click="goToPage(currentPage - 1)"
        >
          {{ __('Previous') }}
        </Button>
        <span class="quizzes-view__pager-pos">{{ pagerPageText }}</span>
        <Button
          variant="outline"
          size="sm"
          :disabled="currentPage >= totalPages"
          @click="goToPage(currentPage + 1)"
        >
          {{ __('Next') }}
        </Button>
      </div>
    </div>

    <!-- New Quiz modal — 3-step setup (type → template → title/slug). -->
    <NewQuizModal
      v-model="showNewQuizModal"
      @created="onQuizCreated"
    />

    <!-- Duplicate + Delete both ask first (see requestDuplicate / requestRemove). -->
    <ConfirmDialog
      v-model="confirmOpen"
      :title="confirmCopy.title"
      :message="confirmCopy.message"
      :confirm-label="confirmCopy.confirmLabel"
      :danger="confirmCopy.danger"
      @confirm="runConfirmed"
    />
  </div>
</template>

<script setup>
import { __, _n, sprintf } from '@shared/i18n';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { Button, ConfirmDialog, EmptyState, useToast } from '@admin/ui';
import { useQuizzesStore } from '@admin/stores/quizzes';
import { useAnalyticsStore } from '@admin/stores/analytics';
import { api } from '@admin/api/client';
import DashboardKpiCard from './dashboard/DashboardKpiCard.vue';
import NewQuizModal from './dashboard/NewQuizModal.vue';
import QuizCard from './dashboard/QuizCard.vue';
import QuizFilters from './dashboard/QuizFilters.vue';
import QuizListTable from './dashboard/QuizListTable.vue';

const store = useQuizzesStore();
const analyticsStore = useAnalyticsStore();
const toast = useToast();
const router = useRouter();

const PAGE_SIZE = 20;

const showNewQuizModal = ref(false);

// Duplicate and Delete confirm before they run. `pending` is what the dialog is
// about; it is deliberately left set after confirm/cancel so the dialog copy
// doesn't blank out while it fades away — the next request overwrites it.
const confirmOpen = ref(false);
const pending = ref(null); // { kind: 'duplicate' | 'remove', id, title }
// Table is the default view — a dense list with pagination scales better
// than a card grid once someone has more than a handful of quizzes.
const viewMode = ref('list');
const currentPage = ref(1);
const importFileRef = ref(null);
const importing = ref(false);

// Local filters mirror — the store owns the authoritative filter state
// but this component funnels edits through a debounced fetch so each
// keystroke in the search field doesn't fire a request.
const filters = ref({
  status: store.filters.status,
  type: store.filters.type,
  search: store.filters.search,
  orderby: store.filters.orderby,
  order: store.filters.order,
});

let fetchTimer = null;

function openNewQuizModal() {
  showNewQuizModal.value = true;
}

function onQuizCreated(id) {
  router.push(`/quiz/${id}/questions`);
}

function openImportPicker() {
  if (importFileRef.value) {
    // Reset the value so selecting the same file again fires the change event.
    importFileRef.value.value = '';
    importFileRef.value.click();
  }
}

async function onImportFileSelected(event) {
  const file = event.target?.files?.[0];
  if (!file) return;
  importing.value = true;
  try {
    const text = await file.text();
    let payload;
    try {
      payload = JSON.parse(text);
    } catch {
      toast.push({ variant: 'danger', title: __('Invalid file'), message: __('The selected file is not valid JSON.') });
      return;
    }
    const quiz = await api.post('quizzes/import', payload);
    store.items.unshift(quiz);
    store.total++;
    toast.push({
      variant: 'success',
      title: __('Quiz imported'),
      // translators: %s is the title of the imported quiz.
      message: sprintf(__('"%s" was imported successfully.'), quiz?.title || __('Quiz')),
    });
  } catch (e) {
    const msg = e?.message ?? __('Could not import quiz.');
    toast.push({ variant: 'danger', title: __('Import failed'), message: msg });
  } finally {
    importing.value = false;
  }
}

function toggleViewMode() {
  viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid';
}

const hasActiveFilters = computed(
  () => Boolean(filters.value.status || filters.value.type || filters.value.search)
);

const totalPages = computed(() => Math.max(1, Math.ceil((store.total || 0) / PAGE_SIZE)));

// translators: 1: current page number, 2: total number of pages.
const pagerPageText = computed(() => sprintf(__('Page %1$d of %2$d'), currentPage.value, totalPages.value));

const pagerSummary = computed(() => {
  if (!store.total) return '';
  const start = (currentPage.value - 1) * PAGE_SIZE + 1;
  const end = Math.min(store.total, currentPage.value * PAGE_SIZE);
  // translators: 1: first item number on this page, 2: last item number on this page, 3: total number of items.
  return sprintf(__('%1$s–%2$s of %3$s'), start, end, store.total);
});

function goToPage(page) {
  const next = Math.min(Math.max(1, page), totalPages.value);
  if (next === currentPage.value) return;
  currentPage.value = next;
  doFetch();
}

// Numeric count: drives pluralisation. Prefer the analytics aggregate (accurate
// across pages) when loaded; fall back to the list store's total so the card
// isn't blank on mount.
const totalQuizzesCount = computed(() => {
  const serverTotal = analyticsStore.site?.total_quizzes;
  if (Number.isFinite(serverTotal)) return serverTotal;
  return Number.isFinite(store.total) ? store.total : 0;
});

// Locale-formatted display value for the stat card and the hint.
const totalQuizzes = computed(() => totalQuizzesCount.value.toLocaleString());

const publishedCount = computed(
  () => store.items.filter((q) => q.status === 'published').length
);

const publishedHint = computed(() => {
  const site = analyticsStore.site;
  if (site) {
    const parts = [
      // translators: %d is a number of published quizzes.
      sprintf(__('%d published'), site.published_quizzes),
      // translators: %d is a number of draft quizzes.
      sprintf(__('%d draft'), site.draft_quizzes),
    ];
    // translators: %d is a number of archived quizzes.
    if (site.archived_quizzes) parts.push(sprintf(__('%d archived'), site.archived_quizzes));
    return parts.join(' · ');
  }
  if (!store.items.length) return __('No published quizzes yet');
  // translators: 1: number of published quizzes, 2: number of draft quizzes.
  return sprintf(__('%1$d published · %2$d draft'), publishedCount.value, store.items.length - publishedCount.value);
});

const totalHint = computed(() => {
  if (store.loading && !store.items.length) return __('Loading…');
  if (!totalQuizzesCount.value) return __('Build quizzes, capture leads, and route responses.');
  return sprintf(
    // translators: 1: number of quizzes, 2: number of published quizzes.
    _n('%1$s quiz · %2$d published', '%1$s quizzes · %2$d published', totalQuizzesCount.value),
    totalQuizzes.value,
    publishedCount.value
  );
});

// Site-wide analytics come from GET /analytics. While the request is in
// flight we keep em-dash placeholders so the layout doesn't jump.
function formatCount(value) {
  if (value === null || value === undefined) return '—';
  const n = Number(value);
  if (!Number.isFinite(n)) return '—';
  return n.toLocaleString();
}

const submissions30d = computed(() => {
  if (analyticsStore.loading && !analyticsStore.site) return '—';
  return formatCount(analyticsStore.site?.total_submissions_30d);
});

const leads30d = computed(() => {
  if (analyticsStore.loading && !analyticsStore.site) return '—';
  return formatCount(analyticsStore.site?.total_leads_30d);
});

const submissionsHint = computed(() => {
  if (!analyticsStore.site) return __('Last 30 days');
  return __('Completed submissions, last 30d');
});

const leadsHint = computed(() => {
  if (!analyticsStore.site) return __('Last 30 days');
  return __('Forms captured, last 30d');
});

const avgCompletion = computed(() => {
  // Prefer the server-side aggregate; fall back to the per-quiz average of
  // whatever's loaded in the list when analytics haven't landed yet.
  const serverRate = analyticsStore.site?.avg_completion_rate;
  if (typeof serverRate === 'number' && Number.isFinite(serverRate)) {
    if (serverRate === 0 && !store.items.length) return '—';
    return `${Math.round(serverRate * 100)}%`;
  }
  const published = store.items.filter(
    (q) => q.status === 'published' && q.completion_rate !== null && q.completion_rate !== undefined
  );
  if (!published.length) return '—';
  const sum = published.reduce((acc, q) => {
    const n = Number(q.completion_rate);
    if (!Number.isFinite(n)) return acc;
    return acc + (n <= 1 ? n * 100 : n);
  }, 0);
  return `${Math.round(sum / published.length)}%`;
});

function clearFilters() {
  filters.value = {
    status: '',
    type: '',
    search: '',
    orderby: 'updated_at',
    order: 'DESC',
  };
}

function onEdit(id) {
  router.push(`/quiz/${id}/questions`);
}

function quizTitle(id) {
  const quiz = store.items.find((q) => String(q.id) === String(id));
  return quiz?.title || __('Untitled quiz');
}

function requestDuplicate(id) {
  pending.value = { kind: 'duplicate', id, title: quizTitle(id) };
  confirmOpen.value = true;
}

function requestRemove(id) {
  pending.value = { kind: 'remove', id, title: quizTitle(id) };
  confirmOpen.value = true;
}

const confirmCopy = computed(() => {
  const { kind, title } = pending.value ?? {};
  if (kind === 'remove') {
    return {
      title: __('Delete this quiz?'),
      // translators: %s is the title of the quiz.
      message: sprintf(__('“%s” and all of its questions, results, submissions and leads will be permanently deleted. This cannot be undone.'), title),
      confirmLabel: __('Delete'),
      danger: true,
    };
  }
  return {
    title: __('Duplicate this quiz?'),
    // translators: 1: the title of the quiz being copied, 2: the title of the copy.
    message: sprintf(__('“%1$s” will be copied — with its questions, results and settings — as a new draft called “%2$s”.'), title, sprintf(__('%s (Copy)'), title)),
    confirmLabel: __('Duplicate'),
    danger: false,
  };
});

async function runConfirmed() {
  const action = pending.value;
  if (!action) return;
  if (action.kind === 'remove') await onRemove(action.id);
  else await onDuplicate(action.id);
}

async function onDuplicate(id) {
  try {
    const quiz = await store.duplicate(id);
    toast.push({
      variant: 'success',
      title: __('Quiz duplicated'),
      // translators: %s is the title of the quiz that was copied.
      message: quiz?.title ? sprintf(__('Copied “%s”'), quiz.title) : __('A copy was created.'),
    });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Duplicate failed'), message: e.message });
  }
}

async function onArchive(id) {
  try {
    await store.archive(id);
    toast.push({ variant: 'info', title: __('Quiz archived') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Archive failed'), message: e.message });
  }
}

async function onPublish(id, next) {
  try {
    await store.publish(id, next);
    toast.push({
      variant: next ? 'success' : 'info',
      title: next ? __('Quiz published') : __('Quiz set to draft'),
      message: next
        ? __('Visitors can now see and take this quiz.')
        : __('The quiz is now a draft — it will not show on the site.'),
    });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not update status'), message: e.message });
  }
}

async function onRestore(id) {
  try {
    await store.restore(id);
    toast.push({ variant: 'success', title: __('Quiz restored'), message: __('Moved back to Draft.') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Restore failed'), message: e.message });
  }
}

async function onRemove(id) {
  try {
    await store.remove(id);
    toast.push({ variant: 'info', title: __('Quiz deleted') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Delete failed'), message: e.message });
  }
}

async function doFetch() {
  try {
    await store.fetchList({
      ...filters.value,
      limit: PAGE_SIZE,
      offset: (currentPage.value - 1) * PAGE_SIZE,
    });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not load quizzes'), message: e.message });
  }
}

watch(
  filters,
  () => {
    // Any filter change invalidates the current page's offset.
    currentPage.value = 1;
    if (fetchTimer) clearTimeout(fetchTimer);
    fetchTimer = setTimeout(doFetch, 300);
  },
  { deep: true }
);

onMounted(() => {
  doFetch();
  // Site analytics are independent of the quiz-list query — fire them in
  // parallel and swallow errors so a bad aggregate doesn't block the list.
  analyticsStore.fetchSite().catch(() => {});
});

onBeforeUnmount(() => {
  if (fetchTimer) clearTimeout(fetchTimer);
});
</script>

<style scoped>
.quizzes-view {
  max-width: 1200px;
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.quizzes-view__header {
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

.quizzes-view__heading {
  min-width: 0;
}

.quizzes-view__title {
  font-family: var(--f-display);
  font-weight: 600;
  font-size: 18px;
  line-height: 1.25;
  letter-spacing: -0.005em;
  color: var(--ink-1);
  margin: 0 0 2px;
}

.quizzes-view__title :deep(em) {
  font-style: italic;
  font-weight: 500;
  color: var(--brand);
}

.quizzes-view__sub {
  font-family: var(--f-sans);
  font-size: 12.5px;
  line-height: 1.45;
  color: var(--ink-3);
  margin: 0;
  max-width: 64ch;
}

.quizzes-view__actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.quizzes-view__import-input {
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

.quizzes-view__kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

@media (max-width: 960px) {
  .quizzes-view__kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 520px) {
  .quizzes-view__kpis {
    grid-template-columns: 1fr;
  }
}

.quizzes-view__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

.quizzes-view__pager {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 4px 4px 0;
}

.quizzes-view__pager-summary {
  font-family: var(--f-mono);
  font-size: 12px;
  color: var(--ink-3);
}

.quizzes-view__pager-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.quizzes-view__pager-pos {
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-2);
  white-space: nowrap;
}

@media (max-width: 520px) {
  .quizzes-view__pager {
    justify-content: center;
    text-align: center;
  }
}

.quizzes-view__loading {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 24px;
  color: var(--ink-3);
  font-size: 14px;
}

.quizzes-view__spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-quizzes-spin 700ms linear infinite;
}

@keyframes quizably-quizzes-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .quizzes-view__spinner {
    animation-duration: 0ms;
  }
}

</style>
