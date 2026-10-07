<template>
  <section
    :class="['scard', `scard--${tone}`]"
    :aria-labelledby="titleId"
  >
    <header class="scard__head">
      <span
        v-if="icon"
        class="scard__icon"
        aria-hidden="true"
      >
        <Icon
          :name="icon"
          :size="16"
        />
      </span>
      <div class="scard__text">
        <h2
          :id="titleId"
          class="scard__title"
        >
          {{ title }}
        </h2>
        <p
          v-if="description"
          class="scard__desc"
        >
          {{ description }}
        </p>
      </div>
      <div
        v-if="$slots.actions"
        class="scard__actions"
      >
        <slot name="actions" />
      </div>
    </header>

    <div class="scard__body">
      <slot />
    </div>
  </section>
</template>

<script>
// Shared across instances so every card gets its own heading id.
let uid = 0;
</script>

<script setup>
import { Icon } from '@admin/ui';

/**
 * SettingsCard — one focused group of settings.
 *
 * A tinted header strip (icon chip, title, one-line description, optional
 * actions on the right) over a padded body. A settings section is made of a
 * few of these, each answering one question ("Who is the email from?", "What
 * does a new quiz start with?"), instead of one long undifferentiated list.
 *
 * The body carries only horizontal padding: the rows inside own their vertical
 * rhythm (see SettingsRow), so the space under the header, between fields and
 * above the card's bottom edge all come out the same.
 *
 * tone: 'brand' (default) tints the icon chip with the brand colour,
 *       'neutral' is grey, 'danger' is red for destructive groups.
 */
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  icon: { type: String, default: '' },
  tone: {
    type: String,
    default: 'brand',
    validator: (v) => ['brand', 'neutral', 'danger'].includes(v),
  },
});

const titleId = `quizably-scard-title-${uid++}`;
</script>

<style scoped>
.scard {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-xs);
  /* No overflow: hidden. The card must not clip what its rows show (a focus
     ring, a popover, a tooltip); the header rounds its own top corners below. */
}

.scard__head {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 18px 24px;
  background: var(--bg-canvas);
  border-bottom: 1px solid var(--border-1);
  /* 1px less than the card's radius so the strip sits inside its border. */
  border-radius: calc(var(--r-lg) - 1px) calc(var(--r-lg) - 1px) 0 0;
}

.scard__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 34px;
  height: 34px;
  border-radius: var(--r-md);
  background: var(--brand-tint);
  color: var(--brand);
}

.scard--neutral .scard__icon {
  background: var(--bg-subtle);
  color: var(--ink-2);
  border: 1px solid var(--border-1);
}

.scard--danger .scard__icon {
  background: var(--danger-bg);
  color: var(--danger);
}

.scard__text {
  flex: 1;
  min-width: 0;
}

.scard__title {
  margin: 0;
  font-family: var(--f-sans);
  font-size: 15px;
  font-weight: 600;
  line-height: 1.3;
  letter-spacing: -0.005em;
  color: var(--ink-1);
}

.scard--danger .scard__title {
  color: var(--danger);
}

.scard__desc {
  margin: 3px 0 0;
  max-width: 68ch;
  font-family: var(--f-sans);
  font-size: 13px;
  line-height: 1.5;
  color: var(--ink-3);
}

.scard__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.scard__body {
  padding: 0 24px;
  min-width: 0;
}

/* A danger card's row dividers pick up the tone instead of staying neutral. */
.scard--danger .scard__body :deep(.settings-row) {
  border-top-color: var(--danger-bg);
}

@media (max-width: 720px) {
  .scard__head {
    padding: 16px;
    gap: 12px;
  }

  .scard__body {
    padding: 0 16px;
  }
}
</style>
