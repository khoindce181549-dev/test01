<template>
  <div
    :class="[
      'settings-row',
      inline ? 'settings-row--inline' : 'settings-row--stacked',
      { 'is-pro': pro },
    ]"
    role="group"
    :aria-labelledby="titleId"
    :aria-describedby="description ? descId : undefined"
  >
    <div class="settings-row__label">
      <div
        :id="titleId"
        class="settings-row__title"
      >
        {{ title }}
      </div>
      <p
        v-if="description"
        :id="descId"
        class="settings-row__desc"
      >
        {{ description }}
      </p>
    </div>
    <div class="settings-row__control">
      <slot />
    </div>
  </div>
</template>

<script>
// Shared across instances so every row gets its own ids.
let uid = 0;
</script>

<script setup>
/**
 * SettingsRow — one setting: a label, a sub-heading that explains it, and the
 * control. Every field on the page has this same anatomy, so the label, its
 * explanation and the control always sit in the same relative positions.
 *
 *   stacked (default)   label + sub-heading above, control below. For inputs,
 *                       selects, textareas and anything wider than a switch.
 *   inline              label + sub-heading on the left, control on the right.
 *                       For toggles, badges and single short buttons.
 *
 * The row is a labelled group, so a screen reader announces the label and the
 * sub-heading together when focus enters the control.
 */
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  pro: { type: Boolean, default: false },
  inline: { type: Boolean, default: false },
});

const n = uid++;
const titleId = `quizably-srow-title-${n}`;
const descId = `quizably-srow-desc-${n}`;
</script>

<style scoped>
/* Vertical rhythm lives here, not on the card: 18px above and below every
   row, with a hairline between neighbours. That gives the same gap under a
   card header, between fields, and above the card's bottom edge. */
.settings-row {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 18px 0;
  border-top: 1px solid var(--border-1);
}

.settings-row:first-child {
  border-top: none;
}

.settings-row__label {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}

.settings-row__title {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  line-height: 1.35;
  letter-spacing: -0.005em;
  color: var(--ink-1);
}

.settings-row__desc {
  margin: 0;
  max-width: 64ch;
  font-family: var(--f-sans);
  font-size: 13px;
  line-height: 1.5;
  color: var(--ink-3);
}

.settings-row__control {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 8px;
  min-width: 0;
}

/* Stacked controls stay a readable width instead of stretching across the
   whole card on a wide screen. */
.settings-row--stacked .settings-row__control {
  max-width: 640px;
}

/* Inline: label on the left, control on the right. */
.settings-row--inline {
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.settings-row--inline .settings-row__label {
  flex: 1;
}

.settings-row--inline .settings-row__control {
  flex: 0 0 auto;
  align-items: flex-end;
}

.settings-row.is-pro .settings-row__title {
  color: var(--ink-2);
}

@media (max-width: 720px) {
  /* Inline rows fall back to stacked so a toggle never squeezes the label. */
  .settings-row--inline {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .settings-row--inline .settings-row__control {
    align-items: flex-start;
  }
}
</style>
