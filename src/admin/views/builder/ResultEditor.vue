<template>
  <div class="re-shell">
    <div class="re-shell__preview">
      <ResultPreview
        :result="result"
        :quiz-type="quizType"
        :questions="questions"
        :position="position"
        :total="total"
        @open-properties="propsDrawerOpen = true"
      />
    </div>
    <aside class="re-shell__rail">
      <ResultProperties
        :result="result"
        :quiz-type="quizType"
      />
    </aside>

    <Drawer
      v-model="propsDrawerOpen"
      :title="__('Result properties')"
      size="md"
    >
      <ResultProperties
        :result="result"
        :quiz-type="quizType"
      />
    </Drawer>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { ref } from 'vue';
import { Drawer } from '@admin/ui';
import ResultPreview from './ResultPreview.vue';
import ResultProperties from './ResultProperties.vue';

defineProps({
  result: { type: Object, required: true },
  quizType: { type: String, default: 'personality' },
  questions: { type: Array, default: () => [] },
  position: { type: Number, required: true },
  total: { type: Number, required: true },
});

const propsDrawerOpen = ref(false);
</script>

<style scoped>
.re-shell {
  display: grid;
  grid-template-columns: 1fr 340px;
  grid-template-rows: minmax(0, 1fr);
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

.re-shell__preview {
  overflow-y: auto;
  min-height: 0;
}

.re-shell__rail {
  border-inline-start: 1px solid var(--border-1);
  overflow: hidden;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

@media (max-width: 1279px) {
  .re-shell {
    grid-template-columns: 1fr 300px;
  }
}

@media (max-width: 1023px) {
  .re-shell {
    grid-template-columns: 1fr;
  }
  .re-shell__rail {
    display: none;
  }
}
</style>
