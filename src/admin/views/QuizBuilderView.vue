<template>
  <div
    v-if="store.loading"
    class="builder-loading"
    aria-live="polite"
  >
    <span
      class="builder-loading__spinner"
      aria-hidden="true"
    />
    <span>{{ __('Loading quiz…') }}</span>
  </div>
  <div
    v-else-if="!store.quiz"
    class="builder-error"
  >
    <EmptyState
      :title="__('Quiz not found')"
      :description="notFoundDescription"
    >
      <template #actions>
        <Button
          variant="primary"
          @click="router.push('/quizzes')"
        >
          {{ __('Back to quizzes') }}
        </Button>
      </template>
    </EmptyState>
  </div>
  <div
    v-else
    class="builder"
  >
    <BuilderTopbar @open-design="designOpen = true" />
    <BuilderTabs
      :model-value="tab"
      @update:model-value="switchTab"
    />
    <div class="builder__body">
      <OverviewTab v-if="tab === 'overview'" />
      <QuestionsTab v-else-if="tab === 'questions'" />
      <LogicTab v-else-if="tab === 'logic'" />
      <ResultsTab v-else-if="tab === 'results'" />
      <IntroTab v-else-if="tab === 'intro'" />
      <FormTab v-else-if="tab === 'form'" />
      <IntegrationsTab v-else-if="tab === 'integrations'" />
      <SettingsTab v-else-if="tab === 'settings'" />
      <SummaryTab v-else-if="tab === 'summary'" />
    </div>
    <DesignDrawer :open="designOpen" @close="designOpen = false" />
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { Button, EmptyState } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { useUiStore } from '@admin/stores/ui';
import BuilderTopbar from './builder/BuilderTopbar.vue';
import BuilderTabs from './builder/BuilderTabs.vue';
import OverviewTab from './builder/OverviewTab.vue';
import QuestionsTab from './builder/QuestionsTab.vue';
import LogicTab from './builder/LogicTab.vue';
import ResultsTab from './builder/ResultsTab.vue';
import IntroTab from './builder/IntroTab.vue';
import FormTab from './builder/FormTab.vue';
import DesignDrawer from './builder/DesignDrawer.vue';
import IntegrationsTab from './builder/IntegrationsTab.vue';
import SettingsTab from './builder/SettingsTab.vue';
import SummaryTab from './builder/SummaryTab.vue';
import { isTabVisible } from './builder/tabOrder.js';

const props = defineProps({
  quizId: { type: Number, required: true },
  tab: { type: String, default: 'questions' },
});

const router = useRouter();
const store = useQuizBuilderStore();
const ui = useUiStore();

const VALID_TABS = ['overview', 'questions', 'logic', 'results', 'intro', 'form', 'integrations', 'settings', 'summary'];

// Design settings are accessed via the topbar icon button (DesignDrawer),
// not as a tab. `designOpen` drives the slide-in drawer.
const designOpen = ref(false);

const notFoundDescription = __("The quiz you're looking for doesn't exist or was deleted.");

// When a quiz first loads, tab defaults to whatever's in the URL. If
// it's an unknown tab we fall back to Overview so the author lands on
// a contextual explanation rather than an empty Questions list.
//
// Pro-tier tabs (Logic) aren't listed in the tab strip unless Pro is active
// or promoted, so a stale bookmark / typed URL for one must land on a visible
// tab instead of an empty pane: it resolves to Overview here and the URL is
// rewritten to match below.
const tab = computed(() =>
  VALID_TABS.includes(props.tab) && isTabVisible(props.tab) ? props.tab : 'overview'
);

watch(
  () => [props.quizId, props.tab],
  () => {
    if (VALID_TABS.includes(props.tab) && !isTabVisible(props.tab)) {
      router.replace('/quiz/' + props.quizId + '/overview');
    }
  },
  { immediate: true }
);

// Snapshot the user's sidebar preference on entry so we can restore it
// when they leave the builder. Inside the builder we always default to
// collapsed for maximum canvas.
let prevSidebarOpen = true;

function switchTab(next) {
  router.push('/quiz/' + props.quizId + '/' + next);
}

onMounted(() => {
  // Full-screen mode: hides WP's admin bar, left admin menu, and the
  // plugin's own brand bar so the builder owns the viewport. Paired CSS
  // lives in src/shared/base.css under `body.quizably-builder-fullscreen`.
  // We also flag <html> so we can reset WordPress's `margin-top: 32px`
  // without relying on :has().
  document.body.classList.add('quizably-builder-fullscreen');
  document.documentElement.classList.add('quizably-builder-fullscreen');
  prevSidebarOpen = ui.sidebarOpen;
  ui.sidebarOpen = false;
  if (Number.isFinite(props.quizId)) {
    store.load(props.quizId).catch(() => {
      // The store captures error state — the template renders a not-found card.
    });
  }
});

watch(
  () => props.quizId,
  (id, prev) => {
    if (id !== prev && Number.isFinite(id)) {
      store.load(id).catch(() => {});
    }
  }
);

onBeforeUnmount(() => {
  document.body.classList.remove('quizably-builder-fullscreen');
  document.documentElement.classList.remove('quizably-builder-fullscreen');
  ui.sidebarOpen = prevSidebarOpen;
  store.reset();
});
</script>

<style scoped>
.builder {
  position: relative;
  margin: -32px;
  min-height: calc(100vh - 64px);
  background: var(--bg-canvas);
  display: flex;
  flex-direction: column;
}

.builder__body {
  flex: 1;
  min-height: 0;
  /* Each tab pane owns its own scroll so height:100% on tab roots
     resolves against a definite flex height, not a scroll container.
     overflow:hidden clips nothing — it just stops this box from
     creating a scroll context that confuses percentage heights. */
  overflow: hidden;
  /* Positioning context for absolute-positioned panes (Logic editor). */
  position: relative;
}

.builder__stub {
  margin: 24px 32px;
  padding: 60px 40px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  text-align: center;
  color: var(--ink-3);
  font-size: 14px;
}

.builder-loading,
.builder-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 60px;
  color: var(--ink-3);
  font-size: 14px;
}

.builder-error {
  max-width: 520px;
  margin: 60px auto;
}

.builder-loading__spinner {
  display: inline-block;
  width: 18px;
  height: 18px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-builder-spin 700ms linear infinite;
}

@keyframes quizably-builder-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .builder-loading__spinner {
    animation-duration: 0ms;
  }
}
</style>
