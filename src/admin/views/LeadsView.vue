<template>
  <div class="leads-view">
    <!-- Header -->
    <header class="leads-view__header">
      <div>
        <h1 class="leads-view__title">{{ __('Leads') }}</h1>
        <p class="leads-view__sub">{{ __('Every form submission captured across your quizzes.') }}</p>
      </div>
      <div class="leads-view__count" v-if="!leadsStore.loading">
        <span class="leads-view__count-num">{{ leadsStore.total.toLocaleString() }}</span>
        <span class="leads-view__count-label">{{ __('leads total') }}</span>
      </div>
    </header>

    <!-- Filter bar -->
    <div class="leads-filters">
      <!-- Search -->
      <div class="leads-filters__field leads-filters__field--search">
        <span class="leads-filters__icon" aria-hidden="true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
        </span>
        <input
          class="leads-filters__input"
          :value="search"
          type="search"
          :placeholder="__('Search by email…')"
          @input="onSearchInput($event.target.value)"
        />
      </div>

      <!-- Searchable quiz combobox -->
      <div class="leads-filters__field leads-filters__field--quiz" ref="quizComboRef">
        <button
          class="leads-filters__quiz-trigger"
          :class="{ 'is-active': !!selectedQuizId }"
          type="button"
          @click="toggleQuizDrop"
        >
          <span class="leads-filters__quiz-label">{{ selectedQuizLabel }}</span>
          <svg class="leads-filters__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>
        <div v-if="quizDropOpen" class="leads-filters__quiz-panel">
          <div class="leads-filters__quiz-search">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input
              ref="quizSearchRef"
              v-model="quizQuery"
              class="leads-filters__quiz-search-input"
              :placeholder="__('Search quizzes…')"
              @keydown.escape="quizDropOpen = false"
            />
          </div>
          <ul class="leads-filters__quiz-list" role="listbox">
            <li
              class="leads-filters__quiz-item"
              :class="{ 'is-selected': !selectedQuizId }"
              role="option"
              :aria-selected="!selectedQuizId"
              @click="selectQuiz('')"
            >
              <svg v-if="!selectedQuizId" class="leads-filters__quiz-check" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <span v-else class="leads-filters__quiz-check"></span>
              {{ __('All quizzes') }}
            </li>
            <li
              v-for="q in filteredQuizOptions"
              :key="q.value"
              class="leads-filters__quiz-item"
              :class="{ 'is-selected': selectedQuizId === q.value }"
              role="option"
              :aria-selected="selectedQuizId === q.value"
              @click="selectQuiz(q.value)"
            >
              <svg v-if="selectedQuizId === q.value" class="leads-filters__quiz-check" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <span v-else class="leads-filters__quiz-check"></span>
              {{ q.label }}
            </li>
            <li v-if="filteredQuizOptions.length === 0 && quizQuery" class="leads-filters__quiz-empty">
              {{ noQuizMatchText }}
            </li>
          </ul>
        </div>
      </div>

      <!-- Date From -->
      <div class="leads-filters__field leads-filters__field--date">
        <label class="leads-filters__date-label">{{ __('From') }}</label>
        <input
          class="leads-filters__input leads-filters__input--date"
          type="date"
          :value="dateFrom"
          @change="onDateFromChange($event.target.value)"
        />
      </div>

      <!-- Date To -->
      <div class="leads-filters__field leads-filters__field--date">
        <label class="leads-filters__date-label">{{ __('To') }}</label>
        <input
          class="leads-filters__input leads-filters__input--date"
          type="date"
          :value="dateTo"
          @change="onDateToChange($event.target.value)"
        />
      </div>

      <!-- Export -->
      <button
        class="leads-filters__export"
        :class="{ 'is-loading': exporting }"
        :disabled="exporting || !leadsStore.items.length"
        :title="leadsStore.items.length ? __('Export the current filtered list as CSV') : __('No leads to export')"
        type="button"
        @click="onExportCsv"
      >
        <svg v-if="!exporting" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        <span class="leads-filters__export-spinner" v-else aria-hidden="true"/>
        {{ __('Export CSV') }}
      </button>
    </div>

    <!-- Active filter chips -->
    <div v-if="activeFilters.length" class="leads-chips">
      <button
        v-for="chip in activeFilters"
        :key="chip.key"
        class="leads-chips__chip"
        type="button"
        @click="clearFilter(chip.key)"
      >
        {{ chip.label }}
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
      <button class="leads-chips__clear-all" type="button" @click="clearAllFilters">
        {{ __('Clear all') }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="leadsStore.loading" class="leads-view__loading" aria-live="polite">
      <span class="leads-view__spinner" aria-hidden="true"/>
      <span>{{ __('Loading leads…') }}</span>
    </div>

    <!-- Empty -->
    <EmptyState
      v-else-if="!leadsStore.items.length"
      :title="activeFilters.length ? __('No leads match your filters') : __('No leads yet')"
      :description="emptyDescription"
    >
      <template #icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>
        </svg>
      </template>
    </EmptyState>

    <!-- Table -->
    <LeadsTable
      v-else
      :leads="leadsStore.items"
      :show-quiz="!selectedQuizId"
      :quiz-map="quizMap"
      @view="onView"
      @remove="onRemove"
    />

    <!-- Pagination -->
    <div v-if="leadsStore.total > pageSize && !leadsStore.loading" class="leads-pagination">
      <span class="leads-pagination__info">
        {{ pagerInfoText }}
      </span>
      <div class="leads-pagination__controls">
        <button
          class="leads-pagination__btn"
          :disabled="currentPage === 0"
          type="button"
          @click="goPage(currentPage - 1)"
        >
          <svg class="q-flip-rtl" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button
          class="leads-pagination__btn"
          :disabled="pageEnd >= leadsStore.total"
          type="button"
          @click="goPage(currentPage + 1)"
        >
          <svg class="q-flip-rtl" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Lead detail panel -->
  <LeadDetailPanel
    :lead="selectedLead"
    @close="selectedLead = null"
  />
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { EmptyState, useToast } from '@admin/ui';
import { useQuizzesStore } from '@admin/stores/quizzes';
import { useLeadsStore } from '@admin/stores/leads';
import { apiUrl } from '@admin/api/client';
import LeadsTable from './leads/LeadsTable.vue';
import LeadDetailPanel from './leads/LeadDetailPanel.vue';

const quizzesStore = useQuizzesStore();
const leadsStore = useLeadsStore();
const toast = useToast();

const selectedQuizId = ref('');
const search = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const exporting = ref(false);

const emptyDescription = computed(() =>
  activeFilters.value.length
    ? __('Try adjusting or clearing the filters above.')
    : __("Once visitors opt in through a quiz, they'll appear here.")
);

// translators: %s is the text the admin typed into the quiz search box.
const noQuizMatchText = computed(() => sprintf(__('No quizzes match "%s"'), quizQuery.value));
const quizDropOpen = ref(false);
const quizQuery = ref('');
const quizComboRef = ref(null);
const quizSearchRef = ref(null);
const currentPage = ref(0);
const pageSize = 20;
const selectedLead = ref(null);

let searchTimer = null;
let dateTimer = null;

// ── quiz combobox ──────────────────────────────────────────────────────────

const quizOptions = computed(() =>
  quizzesStore.items.map((q) => ({
    value: String(q.id),
    // translators: %d is a quiz ID, used when a quiz has no title.
    label: q.title || sprintf(__('Quiz #%d'), q.id),
  }))
);

const filteredQuizOptions = computed(() => {
  if (!quizQuery.value) return quizOptions.value;
  const q = quizQuery.value.toLowerCase();
  return quizOptions.value.filter((o) => o.label.toLowerCase().includes(q));
});

const selectedQuizLabel = computed(() => {
  if (!selectedQuizId.value) return __('All quizzes');
  return quizOptions.value.find((o) => o.value === selectedQuizId.value)?.label || __('All quizzes');
});

const quizMap = computed(() => {
  const map = {};
  quizzesStore.items.forEach((q) => {
    // translators: %d is a quiz ID, used when a quiz has no title.
    map[String(q.id)] = q.title || sprintf(__('Quiz #%d'), q.id);
  });
  return map;
});

async function toggleQuizDrop() {
  quizDropOpen.value = !quizDropOpen.value;
  if (quizDropOpen.value) {
    quizQuery.value = '';
    await nextTick();
    quizSearchRef.value?.focus();
  }
}

function selectQuiz(id) {
  selectedQuizId.value = id;
  quizDropOpen.value = false;
  quizQuery.value = '';
  currentPage.value = 0;
  loadLeads();
}

function handleOutsideClick(e) {
  if (quizComboRef.value && !quizComboRef.value.contains(e.target)) {
    quizDropOpen.value = false;
  }
}

// ── active filter chips ────────────────────────────────────────────────────

const activeFilters = computed(() => {
  const chips = [];
  // translators: %s is the email search text entered by the admin (filter chip).
  if (search.value) chips.push({ key: 'search', label: sprintf(__('Email: %s'), search.value) });
  // translators: %s is the name of the selected quiz (filter chip).
  if (selectedQuizId.value) chips.push({ key: 'quiz', label: sprintf(__('Quiz: %s'), selectedQuizLabel.value) });
  // translators: %s is a date (filter chip).
  if (dateFrom.value) chips.push({ key: 'dateFrom', label: sprintf(__('From: %s'), dateFrom.value) });
  // translators: %s is a date (filter chip).
  if (dateTo.value) chips.push({ key: 'dateTo', label: sprintf(__('To: %s'), dateTo.value) });
  return chips;
});

function clearFilter(key) {
  if (key === 'search') search.value = '';
  else if (key === 'quiz') selectedQuizId.value = '';
  else if (key === 'dateFrom') dateFrom.value = '';
  else if (key === 'dateTo') dateTo.value = '';
  currentPage.value = 0;
  loadLeads();
}

function clearAllFilters() {
  search.value = '';
  selectedQuizId.value = '';
  dateFrom.value = '';
  dateTo.value = '';
  currentPage.value = 0;
  loadLeads();
}

// ── pagination ─────────────────────────────────────────────────────────────

const pageStart = computed(() => currentPage.value * pageSize + 1);
const pageEnd = computed(() => Math.min((currentPage.value + 1) * pageSize, leadsStore.total));
const pagerInfoText = computed(() =>
  // translators: 1: first lead number on this page, 2: last lead number on this page, 3: total number of leads.
  sprintf(__('%1$s–%2$s of %3$s'), pageStart.value, pageEnd.value, leadsStore.total.toLocaleString())
);

function goPage(page) {
  currentPage.value = page;
  loadLeads();
}

// ── data loading ───────────────────────────────────────────────────────────

async function loadLeads() {
  try {
    await leadsStore.fetchList({
      quiz_id: selectedQuizId.value,
      search: search.value,
      date_from: dateFrom.value,
      date_to: dateTo.value,
      offset: currentPage.value * pageSize,
      limit: pageSize,
    });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not load leads'), message: e.message });
  }
}

function onSearchInput(next) {
  search.value = next;
  currentPage.value = 0;
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(loadLeads, 320);
}

function onDateFromChange(next) {
  dateFrom.value = next;
  currentPage.value = 0;
  if (dateTimer) clearTimeout(dateTimer);
  dateTimer = setTimeout(loadLeads, 200);
}

function onDateToChange(next) {
  dateTo.value = next;
  currentPage.value = 0;
  if (dateTimer) clearTimeout(dateTimer);
  dateTimer = setTimeout(loadLeads, 200);
}

// ── export ─────────────────────────────────────────────────────────────────

// Mirrors loadLeads()'s params exactly, so the export always matches
// whatever the table currently shows — including the cross-quiz "All
// quizzes" view, which has no single quiz_id to scope a URL to.
function buildExportUrl() {
  const params = {};
  if (selectedQuizId.value) params.quiz_id = selectedQuizId.value;
  if (search.value) params.search = search.value;
  if (dateFrom.value) params.date_from = dateFrom.value;
  if (dateTo.value) params.date_to = dateTo.value;
  return apiUrl('leads/export', params);
}

function triggerBlobDownload(blob, filename) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

async function onExportCsv() {
  if (!leadsStore.items.length) return;
  exporting.value = true;
  try {
    const url = buildExportUrl();
    const headers = {};
    const nonce = window.QUIZABLY_ADMIN?.nonce;
    if (nonce) headers['X-WP-Nonce'] = nonce;
    const res = await fetch(url, { headers, credentials: 'same-origin' });
    if (!res.ok) {
      // translators: %s is an HTTP status code.
      let message = sprintf(__('Export failed (%s)'), res.status);
      try { const j = await res.json(); if (j?.message) message = j.message; } catch (_) {}
      throw new Error(message);
    }
    const contentType = res.headers.get('content-type') || '';
    const today = new Date().toISOString().slice(0, 10);
    const fallback = `leads-${selectedQuizId.value || 'all'}-${today}.csv`;
    if (contentType.includes('application/json')) {
      const payload = await res.json();
      const blob = new Blob([payload?.csv ?? ''], { type: 'text/csv;charset=utf-8' });
      triggerBlobDownload(blob, payload?.filename || fallback);
      toast.push({ variant: 'success', title: __('Export ready'),
        // translators: %d is the number of leads exported.
        message: sprintf(__('Downloaded %d lead(s).'), payload?.count ?? 0) });
      return;
    }
    const disposition = res.headers.get('content-disposition') || '';
    const match = /filename="?([^";]+)"?/.exec(disposition);
    triggerBlobDownload(await res.blob(), match?.[1] || fallback);
    toast.push({ variant: 'success', title: __('Export started'), message: __('Your CSV download has begun.') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Export failed'), message: e.message });
  } finally {
    exporting.value = false;
  }
}

// ── detail panel ───────────────────────────────────────────────────────────

function onView(lead) {
  selectedLead.value = lead;
}

// ── remove ─────────────────────────────────────────────────────────────────

async function onRemove(id) {
  if (!window.confirm(__('Delete this lead? This cannot be undone.'))) return;
  try {
    await leadsStore.remove(id);
    toast.push({ variant: 'info', title: __('Lead deleted') });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Delete failed'), message: e.message });
  }
}

// ── lifecycle ──────────────────────────────────────────────────────────────

onMounted(() => {
  quizzesStore.fetchList({ limit: 100, offset: 0 });
  loadLeads();
  document.addEventListener('click', handleOutsideClick, true);
});

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer);
  if (dateTimer) clearTimeout(dateTimer);
  document.removeEventListener('click', handleOutsideClick, true);
});
</script>

<style scoped>
/* ── page shell ─────────────────────────────────────────────────────────── */
.leads-view {
  max-width: 1200px;
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.leads-view__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding: 16px 20px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-xs);
}

.leads-view__title {
  font-family: var(--f-display);
  font-weight: 600;
  font-size: 18px;
  letter-spacing: -0.005em;
  color: var(--ink-1);
  margin: 0 0 2px;
}

.leads-view__sub {
  font-family: var(--f-sans);
  font-size: 12.5px;
  color: var(--ink-3);
  margin: 0;
}

.leads-view__count {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 1px;
}

.leads-view__count-num {
  font-family: var(--f-display);
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--ink-1);
  line-height: 1;
}

.leads-view__count-label {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: var(--ink-4);
}

/* ── filter bar ──────────────────────────────────────────────────────────── */
.leads-filters {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  padding: 10px 14px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-xs);
}

.leads-filters__field {
  position: relative;
  display: flex;
  align-items: center;
}

.leads-filters__field--search {
  flex: 1 1 200px;
  min-width: 160px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 0 10px;
  gap: 7px;
  transition: border-color 120ms;
}

.leads-filters__field--search:focus-within {
  border-color: var(--brand);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--brand) 15%, transparent);
}

.leads-filters__icon {
  color: var(--ink-4);
  flex-shrink: 0;
  display: flex;
}

.leads-filters__input {
  flex: 1;
  border: none;
  background: transparent;
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-1);
  padding: 8px 0;
  outline: none;
  min-width: 0;
}

.leads-filters__input::placeholder {
  color: var(--ink-4);
}

/* date fields */
.leads-filters__field--date {
  gap: 6px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 0 10px;
  flex-shrink: 0;
  transition: border-color 120ms;
}

.leads-filters__field--date:focus-within {
  border-color: var(--brand);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--brand) 15%, transparent);
}

.leads-filters__date-label {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-4);
  flex-shrink: 0;
  user-select: none;
}

.leads-filters__input--date {
  padding: 8px 0;
  font-size: 12.5px;
  color: var(--ink-2);
  width: 116px;
}

/* ── quiz combobox ───────────────────────────────────────────────────────── */
.leads-filters__field--quiz {
  position: relative;
  flex-shrink: 0;
}

.leads-filters__quiz-trigger {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 10px;
  height: 36px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-2);
  cursor: pointer;
  transition: border-color 120ms, background 120ms;
  white-space: nowrap;
  min-width: 130px;
  max-width: 220px;
}

.leads-filters__quiz-trigger:hover {
  border-color: var(--border-2);
  background: var(--bg-subtle);
}

.leads-filters__quiz-trigger.is-active {
  border-color: var(--brand);
  color: var(--ink-1);
  background: color-mix(in srgb, var(--brand) 6%, var(--bg-canvas));
}

.leads-filters__quiz-label {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: start;
}

.leads-filters__chevron {
  flex-shrink: 0;
  color: var(--ink-4);
  transition: transform 150ms;
}

.leads-filters__field--quiz.is-open .leads-filters__chevron {
  transform: rotate(180deg);
}

.leads-filters__quiz-panel {
  position: absolute;
  top: calc(100% + 6px);
  inset-inline-start: 0;
  z-index: 200;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-md, 0 8px 24px rgba(0,0,0,.12));
  min-width: 240px;
  max-width: 320px;
  overflow: hidden;
}

.leads-filters__quiz-search {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-bottom: 1px solid var(--border-1);
  color: var(--ink-4);
}

.leads-filters__quiz-search-input {
  flex: 1;
  border: none;
  background: transparent;
  font-family: var(--f-sans);
  font-size: 12.5px;
  color: var(--ink-1);
  outline: none;
}

.leads-filters__quiz-search-input::placeholder {
  color: var(--ink-4);
}

.leads-filters__quiz-list {
  list-style: none;
  margin: 0;
  padding: 4px 0;
  max-height: 240px;
  overflow-y: auto;
}

.leads-filters__quiz-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-2);
  cursor: pointer;
  transition: background 80ms;
}

.leads-filters__quiz-item:hover {
  background: var(--bg-subtle);
}

.leads-filters__quiz-item.is-selected {
  color: var(--ink-1);
  font-weight: 500;
}

.leads-filters__quiz-check {
  flex-shrink: 0;
  width: 12px;
  color: var(--brand);
}

.leads-filters__quiz-empty {
  padding: 10px 12px;
  font-family: var(--f-sans);
  font-size: 12.5px;
  color: var(--ink-4);
  font-style: italic;
}

/* ── export button ───────────────────────────────────────────────────────── */
.leads-filters__export {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 14px;
  height: 36px;
  background: var(--brand);
  color: #fff;
  border: none;
  border-radius: var(--r-sm);
  font-family: var(--f-sans);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 140ms;
  white-space: nowrap;
  flex-shrink: 0;
}

.leads-filters__export:hover:not(:disabled) {
  opacity: 0.88;
}

.leads-filters__export:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.leads-filters__export-spinner {
  display: inline-block;
  width: 13px;
  height: 13px;
  border: 2px solid rgba(255,255,255,.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: quizably-leads-spin 700ms linear infinite;
}

/* ── active filter chips ─────────────────────────────────────────────────── */
.leads-chips {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.leads-chips__chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  background: color-mix(in srgb, var(--brand) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--brand) 30%, transparent);
  border-radius: var(--r-pill);
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--brand);
  cursor: pointer;
  transition: background 100ms;
}

.leads-chips__chip:hover {
  background: color-mix(in srgb, var(--brand) 16%, transparent);
}

.leads-chips__clear-all {
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--ink-4);
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 6px;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.leads-chips__clear-all:hover {
  color: var(--ink-2);
}

/* ── loading ─────────────────────────────────────────────────────────────── */
.leads-view__loading {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 32px 24px;
  color: var(--ink-3);
  font-size: 14px;
}

.leads-view__spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-leads-spin 700ms linear infinite;
}

@keyframes quizably-leads-spin {
  to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
  .leads-view__spinner,
  .leads-filters__export-spinner { animation-duration: 0ms; }
}

/* ── pagination ──────────────────────────────────────────────────────────── */
.leads-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
}

.leads-pagination__info {
  font-family: var(--f-mono);
  font-size: 11.5px;
  color: var(--ink-4);
  letter-spacing: 0.03em;
}

.leads-pagination__controls {
  display: flex;
  gap: 4px;
}

.leads-pagination__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  background: var(--bg-canvas);
  color: var(--ink-2);
  cursor: pointer;
  transition: background 100ms, border-color 100ms;
}

.leads-pagination__btn:hover:not(:disabled) {
  background: var(--bg-subtle);
  border-color: var(--border-2);
}

.leads-pagination__btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* ── responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .leads-filters { gap: 6px; }
  .leads-filters__field--search { min-width: 140px; }
  .leads-filters__field--date { display: none; }
}

@media (max-width: 600px) {
  .leads-filters { flex-direction: column; align-items: stretch; }
  .leads-filters__field--search { flex: 1; }
  .leads-filters__quiz-trigger { max-width: 100%; }
  .leads-filters__export { width: 100%; justify-content: center; }
}
</style>
