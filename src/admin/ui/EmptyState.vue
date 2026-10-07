<template>
  <div class="quizably-empty">
    <div
      v-if="$slots.icon"
      class="quizably-empty__icon"
    >
      <slot name="icon" />
    </div>
    <h3 class="quizably-empty__title">
      {{ title }}
    </h3>
    <p
      v-if="description"
      class="quizably-empty__desc"
    >
      {{ description }}
    </p>
    <div
      v-if="$slots.actions"
      class="quizably-empty__actions"
    >
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>

<style scoped>
.quizably-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 12px;
  padding: 48px 32px;
  background: var(--bg-surface);
  border: 1px dashed var(--border-2);
  border-radius: var(--r-lg);
}

.quizably-empty__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--bg-subtle);
  color: var(--ink-3);
  margin-bottom: 4px;
}

.quizably-empty__icon :deep(svg) {
  width: 28px;
  height: 28px;
}

.quizably-empty__title {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 22px;
  line-height: 1.2;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
}

.quizably-empty__desc {
  font-family: var(--f-sans);
  font-size: 14px;
  line-height: 1.55;
  color: var(--ink-3);
  margin: 0;
  max-width: 46ch;
}

.quizably-empty__actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 12px;
}
</style>
