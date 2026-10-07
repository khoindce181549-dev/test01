<template>
  <div
    class="builder-tabs"
    role="tablist"
  >
    <button
      v-for="t in tabs"
      :key="t.key"
      type="button"
      role="tab"
      :aria-selected="modelValue === t.key"
      :class="['builder-tab', { 'is-active': modelValue === t.key }]"
      @click="onSelect(t.key)"
    >
      <span
        class="builder-tab__icon"
        aria-hidden="true"
      >
        <component :is="t.icon" />
      </span>
      <span>{{ t.label }}</span>
      <span
        v-if="typeof t.count === 'number'"
        class="builder-tab__num"
      >
        {{ t.count }}
      </span>
      <span
        v-if="t.pro"
        class="builder-tab__pro"
        :aria-label="__('Pro feature')"
      >
        Pro
      </span>
    </button>
  </div>
</template>

<script setup>
import { computed, h, onBeforeUnmount, onMounted, shallowRef } from 'vue';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { showProPromo } from '@admin/api/pro.js';
import { __ } from '@shared/i18n';
import { tabOrderFor, resultsLabelFor, TAB_LABELS } from './tabOrder.js';

defineProps({
  modelValue: { type: String, default: 'questions' },
});

const emit = defineEmits(['update:modelValue']);

const store = useQuizBuilderStore();

const iconAttrs = {
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': 1.75,
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round',
};

const QuestionsIcon = () =>
  h('svg', iconAttrs, [
    h('circle', { cx: 12, cy: 12, r: 10 }),
    h('path', { d: 'M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3' }),
    h('path', { d: 'M12 17h.01' }),
  ]);

const LogicIcon = () =>
  h('svg', iconAttrs, [
    h('circle', { cx: 6, cy: 6, r: 2.5 }),
    h('circle', { cx: 18, cy: 6, r: 2.5 }),
    h('circle', { cx: 12, cy: 18, r: 2.5 }),
    h('path', { d: 'M6 8.5v1A4 4 0 0 0 10 13.5h4A4 4 0 0 0 18 9.5v-1' }),
    h('path', { d: 'M12 13.5v2' }),
  ]);

const ResultsIcon = () =>
  h('svg', iconAttrs, [
    h('path', {
      d: 'M12 2 15.09 8.26 22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
    }),
  ]);

const DesignIcon = () =>
  h('svg', iconAttrs, [
    h('circle', { cx: 13.5, cy: 6.5, r: 0.6 }),
    h('circle', { cx: 17.5, cy: 10.5, r: 0.6 }),
    h('circle', { cx: 8.5, cy: 7.5, r: 0.6 }),
    h('circle', { cx: 6.5, cy: 12.5, r: 0.6 }),
    h('path', {
      d: 'M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c3.31 0 6-2.69 6-6 0-5.52-4.48-10-10-10z',
    }),
  ]);

const IntegrationsIcon = () =>
  h('svg', iconAttrs, [
    h('polyline', { points: '16 18 22 12 16 6' }),
    h('polyline', { points: '8 6 2 12 8 18' }),
  ]);

const SettingsIcon = () =>
  h('svg', iconAttrs, [
    h('circle', { cx: 12, cy: 12, r: 3 }),
    h('path', {
      d: 'M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z',
    }),
  ]);

const IntroIcon = () =>
  h('svg', iconAttrs, [
    h('rect', { x: 3, y: 5, width: 18, height: 14, rx: 2 }),
    h('polygon', { points: '10 9 16 12 10 15 10 9', fill: 'currentColor' }),
  ]);

const FormIcon = () =>
  h('svg', iconAttrs, [
    h('path', { d: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2' }),
    h('circle', { cx: 9, cy: 7, r: 4 }),
    h('path', { d: 'M19 8v6M22 11h-6' }),
  ]);

const OverviewIcon = () =>
  h('svg', iconAttrs, [
    h('rect', { x: 3, y: 3, width: 7, height: 7, rx: 1.5 }),
    h('rect', { x: 14, y: 3, width: 7, height: 7, rx: 1.5 }),
    h('rect', { x: 3, y: 14, width: 7, height: 7, rx: 1.5 }),
    h('rect', { x: 14, y: 14, width: 7, height: 7, rx: 1.5 }),
  ]);

const SummaryIcon = () =>
  h('svg', iconAttrs, [
    h('path', { d: 'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z' }),
  ]);

const ICONS = {
  overview: OverviewIcon,
  questions: QuestionsIcon,
  logic: LogicIcon,
  results: ResultsIcon,
  intro: IntroIcon,
  form: FormIcon,
  design: DesignIcon,
  integrations: IntegrationsIcon,
  settings: SettingsIcon,
  summary: SummaryIcon,
};

// Logic is Pro-gated — the Pro plugin can replace the LogicTab placeholder
// by writing to window.Quizably.adminHooks.builderTab.logic. When that hook is
// present the Pro chip on the tab is hidden. The tab itself is only listed
// while Pro is active or promoted (tabOrderFor drops it otherwise), and the
// chip is a teaser, so it additionally needs showProPromo().
//
// Window state isn't reactive, so we mirror it into a shallowRef and
// refresh on the `quizably:pro-ready` event the Pro bundle dispatches once
// it has finished registering. This means activating Pro mid-session
// flips the chip off without a page reload.
const isLogicProRegistered = shallowRef(
  typeof window !== 'undefined' && Boolean(window.Quizably?.adminHooks?.builderTab?.logic)
);

function refreshLogicProState() {
  isLogicProRegistered.value =
    typeof window !== 'undefined' && Boolean(window.Quizably?.adminHooks?.builderTab?.logic);
}

onMounted(() => {
  refreshLogicProState();
  if (typeof document !== 'undefined') {
    document.addEventListener('quizably:pro-ready', refreshLogicProState);
  }
});

onBeforeUnmount(() => {
  if (typeof document !== 'undefined') {
    document.removeEventListener('quizably:pro-ready', refreshLogicProState);
  }
});

// Counts surface tab progress at a glance — only shown for tabs with
// quantifiable content (questions / results).
function countFor(key) {
  if (key === 'questions') return store.questions.length;
  if (key === 'results') return store.results.length;
  return null;
}

function labelFor(key) {
  if (key === 'results') return resultsLabelFor(store.quiz?.type);
  return TAB_LABELS[key] ?? key;
}

const tabs = computed(() => {
  const order = tabOrderFor(store.quiz?.type);
  return order.map((key) => ({
    key,
    label: labelFor(key),
    icon: ICONS[key] ?? QuestionsIcon,
    count: countFor(key),
    pro: key === 'logic' ? !isLogicProRegistered.value && showProPromo() : false,
  }));
});

function onSelect(key) {
  emit('update:modelValue', key);
}
</script>

<style scoped>
.builder-tabs {
  display: flex;
  padding: 4px 24px 0;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-1);
  gap: 2px;
}

.builder-tab {
  position: relative;
  padding: 10px 14px 12px;
  background: transparent;
  border: 0;
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-3);
  display: inline-flex;
  align-items: center;
  gap: 7px;
  cursor: pointer;
  transition: color 150ms ease;
  line-height: 1;
}

.builder-tab:hover {
  color: var(--ink-1);
}

.builder-tab.is-active {
  color: var(--ink-1);
}

.builder-tab.is-active::after {
  content: '';
  position: absolute;
  inset-inline-start: 10px;
  inset-inline-end: 10px;
  bottom: -1px;
  height: 2px;
  background: var(--ink-1);
  border-radius: var(--r-pill) var(--r-pill) 0 0;
}

.builder-tab__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  color: currentColor;
}

.builder-tab__icon :deep(svg) {
  width: 14px;
  height: 14px;
}

.builder-tab__num {
  padding: 1px 6px;
  background: var(--bg-subtle);
  border-radius: var(--r-xs);
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-3);
  line-height: 1.4;
}

.builder-tab.is-active .builder-tab__num {
  background: var(--ink-1);
  color: #fff;
}

.builder-tab__pro {
  padding: 1px 7px;
  background: var(--accent-bg);
  color: var(--accent);
  border-radius: var(--r-pill);
  font-family: var(--f-mono);
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  line-height: 1.5;
}
</style>
