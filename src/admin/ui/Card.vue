<template>
  <div
    class="quizably-card"
    :class="[
      `quizably-card--${padding}`,
      { 'is-interactive': interactive },
    ]"
  >
    <slot />
  </div>
</template>

<script setup>
defineProps({
  /**
   * Controls inner padding. `none` lets consumers own their own layout
   * (e.g. media-first cards with an edge-to-edge cover on top).
   */
  padding: {
    type: String,
    default: 'md',
    validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v),
  },
  /**
   * When true the card reads as clickable — pointer cursor and
   * hover elevation. Interactivity itself (click/keyboard) is the
   * caller's responsibility.
   */
  interactive: { type: Boolean, default: false },
});
</script>

<style scoped>
.quizably-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-xs);
  transition: box-shadow 150ms ease, border-color 150ms ease, transform 150ms ease;
}

.quizably-card--none {
  padding: 0;
}
.quizably-card--sm {
  padding: 12px;
}
.quizably-card--md {
  padding: 20px;
}
.quizably-card--lg {
  padding: 28px;
}

.quizably-card.is-interactive {
  cursor: pointer;
}

.quizably-card.is-interactive:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--border-2);
}

@media (prefers-reduced-motion: reduce) {
  .quizably-card {
    transition-duration: 0ms;
  }
}
</style>
