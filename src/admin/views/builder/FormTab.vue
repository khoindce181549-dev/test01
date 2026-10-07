<template>
  <div class="form-shell">
    <div class="form-shell__preview">
      <FormPreview
        :optin="optin"
        :design="design"
        @open-properties="propsDrawerOpen = true"
      />
    </div>
    <aside class="form-shell__rail">
      <FormProperties />
    </aside>

    <Drawer
      v-model="propsDrawerOpen"
      :title="__('Form properties')"
      size="md"
    >
      <FormProperties />
    </Drawer>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Drawer } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { __ } from '@shared/i18n';
import FormPreview from './FormPreview.vue';
import FormProperties from './FormProperties.vue';

const store = useQuizBuilderStore();
const optin = computed(() => store.quiz?.settings?.optin ?? {});
const design = computed(() => store.quiz?.design ?? {});

const propsDrawerOpen = ref(false);
</script>

<style scoped>
.form-shell {
  display: grid;
  grid-template-columns: 1fr 360px;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.form-shell__preview {
  overflow-y: auto;
  min-height: 0;
  background: var(--bg-canvas);
}

.form-shell__rail {
  border-inline-start: 1px solid var(--border-1);
  background: var(--bg-surface);
  overflow: hidden;
  min-height: 0;
}

@media (max-width: 1279px) {
  .form-shell {
    grid-template-columns: 1fr 320px;
  }
}

@media (max-width: 1023px) {
  .form-shell {
    grid-template-columns: 1fr;
  }
  .form-shell__rail {
    display: none;
  }
}
</style>
