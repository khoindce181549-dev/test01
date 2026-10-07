<template>
  <div class="qe-shell">
    <div class="qe-shell__preview">
      <QuestionPreview
        :question="question"
        :quiz-type="quizType"
        :results="results"
        :position="position"
        :total="total"
        @open-properties="propsDrawerOpen = true"
      />
    </div>
    <aside class="qe-shell__rail">
      <QuestionProperties
        :question="question"
        :quiz-type="quizType"
        :results="results"
      />
    </aside>

    <Drawer
      v-model="propsDrawerOpen"
      :title="__('Question properties')"
      size="md"
    >
      <QuestionProperties
        :question="question"
        :quiz-type="quizType"
        :results="results"
      />
    </Drawer>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Drawer } from '@admin/ui';
import { __ } from '@shared/i18n';
import QuestionPreview from './QuestionPreview.vue';
import QuestionProperties from './QuestionProperties.vue';

defineProps({
  question: { type: Object, required: true },
  quizType: { type: String, default: 'personality' },
  results: { type: Array, default: () => [] },
  position: { type: Number, required: true },
  total: { type: Number, required: true },
});

const propsDrawerOpen = ref(false);
</script>

<style scoped>
.qe-shell {
  display: grid;
  grid-template-columns: 1fr 340px;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.qe-shell__preview {
  overflow-y: auto;
  min-height: 0;
}

.qe-shell__rail {
  border-inline-start: 1px solid var(--border-1);
  background: var(--bg-surface);
  overflow: hidden;
  min-height: 0;
}

@media (max-width: 1279px) {
  .qe-shell {
    grid-template-columns: 1fr 300px;
  }
}

@media (max-width: 1023px) {
  .qe-shell {
    grid-template-columns: 1fr;
  }
  .qe-shell__rail {
    display: none;
  }
}
</style>
