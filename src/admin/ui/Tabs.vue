<template>
  <div class="quizably-tabs">
    <div
      class="quizably-tabs__strip"
      role="tablist"
    >
      <button
        v-for="tab in tabs"
        :id="`quizably-tab-${tab.key}`"
        :key="tab.key"
        type="button"
        role="tab"
        :class="[
          'quizably-tabs__tab',
          {
            'quizably-tabs__tab--active': tab.key === modelValue,
            'quizably-tabs__tab--disabled': tab.disabled,
          },
        ]"
        :aria-selected="tab.key === modelValue ? 'true' : 'false'"
        :aria-controls="`quizably-tabpanel-${tab.key}`"
        :tabindex="tab.key === modelValue ? 0 : -1"
        :disabled="tab.disabled"
        @click="select(tab)"
        @keydown="onKeyDown($event, tab)"
      >
        <span
          v-if="tab.icon"
          class="quizably-tabs__icon"
          aria-hidden="true"
        >
          <component
            :is="tab.icon"
            v-if="typeof tab.icon !== 'string'"
          />
          <template v-else>{{ tab.icon }}</template>
        </span>
        <span class="quizably-tabs__label">{{ tab.label }}</span>
        <span
          v-if="tab.badge != null"
          class="quizably-tabs__badge"
        >{{ tab.badge }}</span>
      </button>
    </div>
    <div
      v-if="$slots.default"
      :id="`quizably-tabpanel-${modelValue}`"
      role="tabpanel"
      :aria-labelledby="`quizably-tab-${modelValue}`"
      class="quizably-tabs__panel"
    >
      <slot :active-key="modelValue" />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: [String, Number], required: true },
  tabs: {
    type: Array,
    required: true,
    validator: (arr) => Array.isArray(arr) && arr.every((t) => t && 'key' in t && 'label' in t),
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

function select(tab) {
  if (tab.disabled) return;
  if (tab.key === props.modelValue) return;
  emit('update:modelValue', tab.key);
  emit('change', tab.key);
}

function onKeyDown(event, tab) {
  const enabled = props.tabs.filter((t) => !t.disabled);
  if (enabled.length === 0) return;
  const currentIdx = enabled.findIndex((t) => t.key === tab.key);
  let nextIdx = currentIdx;

  // Arrow keys follow the reading direction: in RTL, ArrowLeft advances.
  const rtl = getComputedStyle(event.currentTarget).direction === 'rtl';
  const nextKey = rtl ? 'ArrowLeft' : 'ArrowRight';
  const prevKey = rtl ? 'ArrowRight' : 'ArrowLeft';

  if (event.key === nextKey) {
    nextIdx = (currentIdx + 1) % enabled.length;
  } else if (event.key === prevKey) {
    nextIdx = (currentIdx - 1 + enabled.length) % enabled.length;
  } else if (event.key === 'Home') {
    nextIdx = 0;
  } else if (event.key === 'End') {
    nextIdx = enabled.length - 1;
  } else {
    return;
  }

  event.preventDefault();
  const nextTab = enabled[nextIdx];
  select(nextTab);
}
</script>

<style scoped>
.quizably-tabs {
  display: flex;
  flex-direction: column;
}

.quizably-tabs__strip {
  display: flex;
  gap: 4px;
  padding: 0 8px;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-1);
}

.quizably-tabs__tab {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 12px 14px;
  background: transparent;
  border: 0;
  cursor: pointer;
  color: var(--ink-3);
  font-size: 13.5px;
  font-weight: 500;
  font-family: inherit;
  transition: color 150ms ease;
}

.quizably-tabs__tab:hover:not(.quizably-tabs__tab--disabled):not(.quizably-tabs__tab--active) {
  color: var(--ink-1);
}

.quizably-tabs__tab--active {
  color: var(--ink-1);
}

.quizably-tabs__tab--active::after {
  content: '';
  position: absolute;
  inset-inline-start: 10px;
  inset-inline-end: 10px;
  bottom: -1px;
  height: 2px;
  background: var(--brand);
  border-start-start-radius: var(--r-pill);
  border-start-end-radius: var(--r-pill);
}

.quizably-tabs__tab--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quizably-tabs__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--ink-3);
}
.quizably-tabs__icon :deep(svg) {
  width: 14px;
  height: 14px;
  stroke-width: 1.75;
}

.quizably-tabs__tab--active .quizably-tabs__icon {
  color: var(--ink-1);
}

.quizably-tabs__badge {
  padding: 1px 6px;
  background: var(--bg-subtle);
  border-radius: var(--r-xs);
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-3);
  min-width: 18px;
  text-align: center;
}

.quizably-tabs__tab--active .quizably-tabs__badge {
  background: var(--brand);
  color: #fff;
}

.quizably-tabs__panel {
  padding: 16px 0;
}
</style>
