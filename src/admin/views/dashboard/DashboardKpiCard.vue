<template>
  <component
    :is="to ? RouterLink : 'div'"
    :to="to ?? undefined"
    class="quizably-kpi-wrap"
  >
    <Card
      padding="md"
      class="quizably-kpi"
      :interactive="Boolean(to)"
    >
      <div
        v-if="$slots.icon"
        class="quizably-kpi__icon"
      >
        <slot name="icon" />
      </div>
      <div class="quizably-kpi__body">
        <span class="quizably-kpi__label">{{ label }}</span>
        <span class="quizably-kpi__value">{{ value }}</span>
        <span
          v-if="hint"
          class="quizably-kpi__hint"
        >{{ hint }}</span>
        <div
          v-if="trend"
          class="quizably-kpi__trend"
          :class="`is-${trend.direction}`"
        >
          <svg
            viewBox="0 0 12 12"
            aria-hidden="true"
          >
            <path
              v-if="trend.direction === 'up'"
              d="M2 8l3-3 2 2 3-4"
              fill="none"
              stroke="currentColor"
              stroke-width="1.75"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <path
              v-else
              d="M2 4l3 3 2-2 3 4"
              fill="none"
              stroke="currentColor"
              stroke-width="1.75"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          {{ trend.value }}
        </div>
      </div>
    </Card>
  </component>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import { Card } from '@admin/ui';

defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  hint: { type: String, default: '' },
  /**
   * Optional trend chip: `{ direction: 'up' | 'down', value: '+28%' }`.
   * Up uses --success, down uses --danger.
   */
  trend: {
    type: Object,
    default: null,
    validator: (t) =>
      t === null ||
      (t && ['up', 'down'].includes(t.direction) && typeof t.value === 'string'),
  },
  /**
   * Optional route target. When set, the whole card becomes a real
   * `<RouterLink>` (native focus/keyboard/middle-click support for free)
   * instead of a static tile.
   */
  to: { type: [String, Object], default: null },
});
</script>

<style scoped>
.quizably-kpi-wrap {
  display: block;
  height: 100%;
  color: inherit;
  text-decoration: none;
}

.quizably-kpi {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  height: 100%;
  box-sizing: border-box;
}

.quizably-kpi__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--bg-subtle);
  color: var(--ink-3);
}

.quizably-kpi__icon :deep(svg) {
  width: 18px;
  height: 18px;
}

.quizably-kpi__body {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex: 1;
}

.quizably-kpi__label {
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-3);
}

.quizably-kpi__value {
  font-family: var(--f-display);
  font-weight: 500;
  font-size: 32px;
  line-height: 1.05;
  letter-spacing: -0.015em;
  color: var(--ink-1);
  font-variation-settings: "opsz" 48;
}

.quizably-kpi__hint {
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--ink-3);
}

.quizably-kpi__trend {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 2px;
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.04em;
}

.quizably-kpi__trend svg {
  width: 10px;
  height: 10px;
}

.quizably-kpi__trend.is-up {
  color: var(--success);
}

.quizably-kpi__trend.is-down {
  color: var(--danger);
}
</style>
