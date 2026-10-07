/**
 * Tab order + labels per quiz type.
 *
 * Each quiz type has a different "happy path" through the builder. For
 * scored types (personality, weighted) the author needs to define the
 * results BEFORE writing answer mappings — putting Results ahead of
 * Questions saves a back-and-forth. For Trivia the score bands live on
 * results too but the questions are authored first because the
 * is_correct/points data lives on each answer. Branching needs Logic
 * up front because the editor IS the value of the type.
 *
 * The Overview tab is always tab 1 — it explains how the chosen quiz
 * type works and tells the author what to do next.
 */

import { proFeaturesVisible } from '@admin/api/pro.js';
import { __ } from '@shared/i18n';

// Functions (not strings) so __() runs at call time, after wp.i18n is ready.
const RESULTS_LABEL_BY_TYPE = {
  personality: () => __('Results'),
  weighted: () => __('Results'),
  trivia: () => __('Score bands'),
  survey: () => __('Result'),
  poll: () => __('Tally'),
  branching: () => __('Endpoints'),
};

const ORDER_BY_TYPE = {
  personality: ['overview', 'results', 'questions', 'logic', 'intro', 'form', 'integrations', 'settings', 'summary'],
  weighted:    ['overview', 'results', 'questions', 'logic', 'intro', 'form', 'integrations', 'settings', 'summary'],
  trivia:      ['overview', 'questions', 'results', 'logic', 'intro', 'form', 'integrations', 'settings', 'summary'],
  survey:      ['overview', 'questions', 'results', 'logic', 'intro', 'form', 'integrations', 'settings', 'summary'],
  poll:        ['overview', 'questions', 'results', 'intro', 'form', 'integrations', 'settings', 'summary'],
  branching:   ['overview', 'questions', 'logic', 'results', 'intro', 'form', 'integrations', 'settings', 'summary'],
};

const DEFAULT_ORDER = ['overview', 'questions', 'results', 'logic', 'intro', 'form', 'integrations', 'settings', 'summary'];

export const TAB_LABELS = {
  overview: __('Overview'),
  questions: __('Questions'),
  results: __('Results'), // overridden per type via resultsLabelFor()
  logic: __('Logic'),
  intro: __('Intro'),
  form: __('Form'),
  design: __('Design'),
  integrations: __('Integrations'),
  settings: __('Settings'),
  summary: __('Summary'),
};

// Tabs that only work with the Pro add-on. They belong in the tab strip (and
// in Next/Back navigation) only while Pro is active or Pro promotion is on;
// otherwise the free builder must not show a tab that leads to an upsell.
const PRO_TABS = ['logic'];

/** May the author open this tab right now? (Pro-tier tabs are conditional.) */
export function isTabVisible(tab) {
  return !PRO_TABS.includes(tab) || proFeaturesVisible();
}

export function tabOrderFor(type) {
  const key = String(type ?? '').toLowerCase();
  const order = ORDER_BY_TYPE[key] ?? DEFAULT_ORDER;
  return order.filter(isTabVisible);
}

export function resultsLabelFor(type) {
  const key = String(type ?? '').toLowerCase();
  const label = RESULTS_LABEL_BY_TYPE[key];
  return label ? label() : __('Results');
}

export function nextTab(currentTab, type) {
  const order = tabOrderFor(type);
  const idx = order.indexOf(currentTab);
  if (idx === -1 || idx === order.length - 1) return null;
  return order[idx + 1];
}

export function prevTab(currentTab, type) {
  const order = tabOrderFor(type);
  const idx = order.indexOf(currentTab);
  if (idx <= 0) return null;
  return order[idx - 1];
}
