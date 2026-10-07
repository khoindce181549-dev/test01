<template>
  <div class="placeholder-view">
    <div class="placeholder-view__card">
      <span class="placeholder-view__eyebrow">{{ phaseText }}</span>
      <h1 class="placeholder-view__title">
        {{ title }}
      </h1>
      <p
        v-if="description"
        class="placeholder-view__desc"
      >
        {{ description }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { __, sprintf } from '@shared/i18n';

const props = defineProps({
  title: { type: String, required: true },
  phase: { type: [String, Number], required: true },
  description: { type: String, default: '' },
});

// translators: %s is a roadmap phase number or name.
const phaseText = computed(() => sprintf(__('Phase %s'), props.phase));
</script>

<style scoped>
.placeholder-view {
  display: flex;
  justify-content: center;
  padding: 48px 0;
}

.placeholder-view__card {
  width: 100%;
  max-width: 640px;
  padding: 48px 40px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-sm);
  text-align: center;
}

.placeholder-view__eyebrow {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--brand);
  background: var(--brand-bg);
  padding: 4px 10px;
  border-radius: var(--r-pill);
  margin-bottom: 16px;
}

.placeholder-view__title {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 32px;
  line-height: 1.15;
  color: var(--ink-1);
  margin: 0 0 12px;
  letter-spacing: -0.01em;
}

.placeholder-view__desc {
  font-family: var(--f-sans);
  font-size: 15px;
  line-height: 1.55;
  color: var(--ink-3);
  margin: 0;
}
</style>
