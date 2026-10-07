<template>
  <span
    class="quizably-tooltip"
    :data-label="label"
    :aria-label="label"
  >
    <slot />
  </span>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
});
</script>

<style scoped>
.quizably-tooltip {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.quizably-tooltip::after {
  content: attr(data-label);
  position: absolute;
  bottom: 100%;
  left: 50%; /* rtl-ok: centring pair with translateX(-50%), symmetric in both directions */
  transform: translate(-50%, -8px);
  background: var(--bg-inverse, #0f172a);
  color: var(--ink-on-inverse, #f8fafc);
  padding: 6px 10px;
  border-radius: var(--r-sm, 4px);
  font-size: 12px;
  line-height: 1.35;
  font-weight: 500;
  white-space: normal;
  width: max-content;
  max-width: 280px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 150ms ease-out, transform 150ms ease-out;
  z-index: 10;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.quizably-tooltip:hover::after,
.quizably-tooltip:focus-within::after {
  opacity: 1;
  transform: translate(-50%, -6px);
}

/* Arrow. */
.quizably-tooltip::before {
  content: '';
  position: absolute;
  bottom: 100%;
  left: 50%; /* rtl-ok: centring pair with translateX(-50%), symmetric in both directions */
  transform: translate(-50%, 0);
  border: 4px solid transparent;
  border-top-color: var(--bg-inverse, #0f172a);
  opacity: 0;
  pointer-events: none;
  transition: opacity 150ms ease-out;
  z-index: 10;
}

.quizably-tooltip:hover::before,
.quizably-tooltip:focus-within::before {
  opacity: 1;
}
</style>
