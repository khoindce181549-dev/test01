<template>
  <span
    v-if="!hidden"
    :class="['quizably-badge', `quizably-badge--${variant}`, `quizably-badge--${size}`]"
  >
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { isPro, proFeaturesVisible } from '@admin/api/pro.js';

/**
 * Generic pill badge. `variant="pro"` is the project-wide "Pro" marker and
 * manages its own visibility, so call sites never wrap it in a `v-if`:
 *
 *  - Hidden when the Pro plugin is active (the feature is already theirs).
 *    Authors who DO want the badge to remain after Pro activation (e.g.
 *    template lists that preserve the original tier label) can pass
 *    `:keep-when-pro="true"` to opt out.
 *  - Hidden while Pro promotion is off (see showProPromo() in api/pro.js) —
 *    the free plugin doesn't mention Pro until there's a paid product.
 */
const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (v) =>
      ['default', 'pro', 'success', 'danger', 'info', 'neutral'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md'].includes(v),
  },
  keepWhenPro: {
    type: Boolean,
    default: false,
  },
});

const hidden = computed(() => {
  if (props.variant !== 'pro') return false;
  // Nothing to advertise: no Pro product, and Pro isn't active either.
  if (!proFeaturesVisible()) return true;
  if (props.keepWhenPro) return false;
  return isPro();
});
</script>

<style scoped>
.quizably-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border-radius: var(--r-pill);
  font-family: var(--f-mono);
  font-weight: 500;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  line-height: 1;
  white-space: nowrap;
}

.quizably-badge--sm {
  padding: 2px 8px;
  font-size: 10px;
}

.quizably-badge--md {
  padding: 4px 10px;
  font-size: 11px;
}

.quizably-badge--default {
  background: var(--bg-muted);
  color: var(--ink-2);
}

.quizably-badge--neutral {
  background: var(--bg-surface);
  color: var(--ink-2);
  border: 1px solid var(--border-2);
}

.quizably-badge--pro {
  background: var(--accent-bg);
  color: var(--accent);
  font-weight: 700;
}

.quizably-badge--success {
  background: var(--success-bg);
  color: var(--success);
}

.quizably-badge--danger {
  background: var(--danger-bg);
  color: var(--danger);
}

.quizably-badge--info {
  background: var(--info-bg);
  color: var(--info);
}
</style>
