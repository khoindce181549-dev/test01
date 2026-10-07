<template>
  <div class="intro-shell">
    <div class="intro-shell__preview">
      <IntroPreview
        :screens="screens"
        :design="design"
        :total-questions="store.questions.length"
        :total-results="store.results.length"
        :quiz-title="store.quiz?.title || ''"
        @open-properties="propsDrawerOpen = true"
      />
    </div>
    <aside class="intro-shell__rail">
      <IntroProperties />
    </aside>

    <Drawer
      v-model="propsDrawerOpen"
      :title="__('Intro properties')"
      size="md"
    >
      <IntroProperties />
    </Drawer>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Drawer } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { __ } from '@shared/i18n';
import IntroPreview from './IntroPreview.vue';
import IntroProperties from './IntroProperties.vue';

const store = useQuizBuilderStore();

const screens = computed(() => store.quiz?.settings?.screens ?? {});
const design = computed(() => store.quiz?.design ?? {});

const propsDrawerOpen = ref(false);
</script>

<style scoped>
.intro-shell {
  display: grid;
  grid-template-columns: 1fr 340px;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.intro-shell__preview {
  overflow-y: auto;
  min-height: 0;
  background: var(--bg-canvas);
}

.intro-shell__rail {
  border-inline-start: 1px solid var(--border-1);
  background: var(--bg-surface);
  overflow: hidden;
  min-height: 0;
}

@media (max-width: 1279px) {
  .intro-shell {
    grid-template-columns: 1fr 300px;
  }
}

@media (max-width: 1023px) {
  .intro-shell {
    grid-template-columns: 1fr;
  }
  .intro-shell__rail {
    display: none;
  }
}
</style>
