<template>
  <div class="logic-tab">
    <component
      v-if="injected"
      :is="injected"
      :questions="cleanQuestions"
      :quiz-id="store.quiz?.id ?? null"
      @update-logic="onUpdateLogic"
    />
    <LogicProUpsell v-else />
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, shallowRef } from 'vue';
import LogicProUpsell from './LogicProUpsell.vue';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { useToast } from '@admin/ui';
import { __ } from '@shared/i18n';

function stripHtml(html) {
  return (html || '').replace(/<[^>]*>/g, '').trim();
}

/**
 * Strip HTML from question titles before passing to the Pro editor so
 * that contenteditable-formatted titles (e.g. center-aligned <div>
 * wrappers) don't leak raw markup into the node graph labels.
 */
const cleanQuestions = computed(() =>
  store.questions.map((q) => ({ ...q, title: stripHtml(q.title) }))
);

/**
 * The Logic tab is Pro-gated. The Pro plugin replaces the placeholder
 * upsell by registering a Vue component on:
 *
 *   window.Quizably.adminHooks.builderTab.logic = MyProLogicEditor
 *
 * The injected component receives the questions list + quiz id as props
 * and emits `update-logic` with `{ questionId, logic }` when the user
 * edits a rule. Persistence happens here so the Pro plugin doesn't need
 * to import the free plugin's Pinia store (separate bundle scope).
 *
 * The Pro bundle normally registers synchronously during script eval, but
 * we still listen for `quizably:pro-ready` to swap in the editor if the bundle
 * loads after this component mounts (e.g. an admin who activated Pro
 * mid-session would otherwise see the upsell until refresh).
 */

const store = useQuizBuilderStore();
const toast = useToast();

function readInjected() {
  return (
    (typeof window !== 'undefined' && window.Quizably?.adminHooks?.builderTab?.logic) || null
  );
}

const injected = shallowRef(readInjected());

function refreshInjected() {
  injected.value = readInjected();
}

onMounted(() => {
  refreshInjected();
  if (typeof document !== 'undefined') {
    document.addEventListener('quizably:pro-ready', refreshInjected);
  }
});

onBeforeUnmount(() => {
  if (typeof document !== 'undefined') {
    document.removeEventListener('quizably:pro-ready', refreshInjected);
  }
});

async function onUpdateLogic(payload) {
  if (!payload || typeof payload !== 'object') return;
  const { questionId, logic } = payload;
  if (!questionId) return;
  try {
    await store.updateQuestion(questionId, { logic: logic ?? null });
  } catch (e) {
    toast.push({
      variant: 'danger',
      title: __('Could not save logic rule'),
      message: e?.message ?? __('Unknown error'),
    });
  }
}
</script>

<style scoped>
.logic-tab {
  /* Provides the positioning context for the Pro LogicEditor, which
     pins itself to inset:0 to claim the whole tab area for its
     full-bleed node-graph workflow. */
  position: relative;
  min-height: 70vh;
  height: 100%;
  background: var(--bg-canvas);
}
</style>
