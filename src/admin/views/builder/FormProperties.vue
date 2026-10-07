<template>
  <div class="oprops">
    <div class="oprops__scroll">
      <!-- Image -->
      <section class="oprops-section">
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Image') }}</span>
        </header>
        <div class="oprops-section__body">
          <MediaPicker
            :model-value="optin.image_url || ''"
            label=""
            helper-text=""
            :button-text="__('Upload image')"
            :replace-text="__('Replace image')"
            :remove-text="__('Delete image')"
            @update:model-value="onImageChange"
          />
          <template v-if="optin.image_url">
            <!-- Image Fit segmented control -->
            <div class="oprops-fit" role="radiogroup" :aria-label="__('Image fit')">
              <button
                v-for="opt in FIT_OPTIONS"
                :key="opt.value"
                type="button"
                role="radio"
                :aria-checked="imageFit === opt.value"
                :class="['oprops-fit__opt', { 'is-active': imageFit === opt.value }]"
                @click="onFitChange(opt.value)"
              >
                {{ opt.label }}
              </button>
            </div>
            <p class="oprops-hint">{{ activeFitHint }}</p>
            <!-- Height slider -->
            <div class="oprops-overlay">
              <label class="oprops-overlay__label">
                {{ __('Height') }}
                <span class="oprops-overlay__pct">{{ imageHeight }} px</span>
              </label>
              <input
                type="range"
                min="80"
                max="800"
                step="10"
                class="oprops-overlay__range"
                :value="imageHeight"
                :aria-label="__('Image height in pixels')"
                @input="onImageHeightChange($event.target.value)"
              >
            </div>
          </template>
        </div>
      </section>

      <!-- Alignment -->
      <section class="oprops-section">
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Alignment') }}</span>
        </header>
        <div class="oprops-section__body">
          <div class="oprops-align" role="radiogroup" :aria-label="__('Content alignment')">
            <button
              v-for="opt in ALIGN_OPTIONS"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="formAlign === opt.value"
              :class="['oprops-align__opt', { 'is-active': formAlign === opt.value }]"
              :aria-label="opt.label"
              :title="opt.label"
              @click="onAlignChange(opt.value)"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span class="oprops-align__icon" aria-hidden="true" v-html="opt.icon" />
            </button>
          </div>
        </div>
      </section>

      <section class="oprops-section">
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Vertical alignment') }}</span>
        </header>
        <div class="oprops-section__body">
          <div class="oprops-align" role="radiogroup" :aria-label="__('Vertical alignment')">
            <button
              v-for="opt in VALIGN_OPTIONS"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="formValign === opt.value"
              :class="['oprops-align__opt', { 'is-active': formValign === opt.value }]"
              :aria-label="opt.label"
              :title="opt.label"
              @click="onValignChange(opt.value)"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span class="oprops-align__icon" aria-hidden="true" v-html="opt.icon" />
            </button>
          </div>
        </div>
      </section>

      <!-- Position: WHERE the form appears. Whether visitors may skip it is a
           separate setting, in the "Optional form" section below. -->
      <section class="oprops-section">
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Show the form') }}</span>
        </header>
        <div class="oprops-section__body">
          <div
            class="oprops-seg"
            role="radiogroup"
            :aria-label="__('Where to show the form')"
          >
            <button
              v-for="p in placementOptions"
              :key="p.value"
              type="button"
              role="radio"
              :aria-checked="placement === p.value"
              :class="[
                'oprops-seg__opt',
                {
                  'is-active': placement === p.value,
                  'is-pro': p.pro,
                },
              ]"
              @click="onPlacementChange(p)"
            >
              <span>{{ p.label }}</span>
              <Badge
                v-if="p.pro"
                variant="pro"
                size="sm"
                class="oprops-seg__pill"
              >
                Pro
              </Badge>
            </button>
          </div>
          <p class="oprops-hint">
            {{ placementHint }}
          </p>
        </div>
      </section>

      <!-- Optional form: Yes = visitors can skip it, No = they must fill it in. -->
      <section
        v-if="placement !== 'none'"
        class="oprops-section"
      >
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Optional form') }}</span>
        </header>
        <div class="oprops-section__body">
          <div
            class="oprops-seg"
            role="radiogroup"
            :aria-label="__('Can visitors skip the form?')"
          >
            <button
              v-for="o in SKIPPABLE_OPTIONS"
              :key="o.label"
              type="button"
              role="radio"
              :aria-checked="skippable === o.value"
              :class="['oprops-seg__opt', { 'is-active': skippable === o.value }]"
              @click="onSkippableChange(o.value)"
            >
              <span>{{ o.label }}</span>
            </button>
          </div>
          <p class="oprops-hint">
            {{ skippableHint }}
          </p>
        </div>
      </section>

      <!-- Labels -->
      <section
        v-if="placement !== 'none'"
        class="oprops-section"
      >
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Labels') }}</span>
        </header>
        <div class="oprops-section__body">
          <Input
            :model-value="optin.submit_label || ''"
            :label="__('Submit button label')"
            :placeholder="defaultSubmitLabel"
            @update:model-value="scheduleSubmitLabelSave"
            @blur="flushSubmitLabelSave"
          />
        </div>
      </section>

      <!-- Fields -->
      <section
        v-if="placement !== 'none'"
        class="oprops-section"
      >
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Fields') }}</span>
          <span class="oprops-section__sub">{{ sprintf(__('%d active'), enabledCount) }}</span>
        </header>
        <div class="oprops-section__body oprops-section__body--tight">
          <div
            v-for="def in fieldDefs"
            :key="def.id"
            :class="['field-card', {
              'is-on': isFieldOn(def.id),
              'is-pro': def.pro,
            }]"
          >
            <header class="field-card__head">
              <span class="field-card__name">
                {{ def.label }}
                <Badge
                  v-if="def.pro"
                  variant="pro"
                  size="sm"
                >
                  Pro
                </Badge>
              </span>
              <Toggle
                :model-value="isFieldOn(def.id)"
                size="sm"
                :disabled="isFieldRequired(def.id)"
                :aria-label="toggleAriaLabel(def)"
                @update:model-value="(v) => onFieldToggle(def.id, v, def)"
              />
            </header>

            <div
              v-if="isFieldOn(def.id) && !def.pro"
              class="field-card__body"
            >
              <Input
                :model-value="fieldConfig(def.id).label || ''"
                :label="__('Label')"
                :placeholder="def.label"
                @update:model-value="(v) => scheduleFieldConfigSave(def.id, 'label', v)"
                @blur="flushFieldConfigSave"
              />
              <Input
                :model-value="fieldConfig(def.id).placeholder || ''"
                :label="__('Placeholder')"
                :placeholder="def.placeholderHint"
                @update:model-value="(v) => scheduleFieldConfigSave(def.id, 'placeholder', v)"
                @blur="flushFieldConfigSave"
              />
              <div class="field-card__row">
                <span>{{ __('Required') }}</span>
                <Toggle
                  :model-value="fieldConfig(def.id).required !== false"
                  size="sm"
                  @update:model-value="(v) => onFieldRequired(def.id, v)"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Consent -->
      <section
        v-if="placement !== 'none'"
        class="oprops-section"
      >
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Consent') }}</span>
        </header>
        <div class="oprops-section__body">
          <Textarea
            :model-value="optin.gdpr_text || ''"
            :label="__('GDPR consent text')"
            :placeholder="__('I agree to receive follow-up emails about my results.')"
            rows="3"
            @update:model-value="scheduleGdprSave"
            @blur="flushGdprSave"
          />
          <p class="oprops-hint">
            {{ __('Empty = checkbox hidden on the live form. Required by GDPR for EU visitors.') }}
          </p>
        </div>
      </section>

      <!-- Background -->
      <section
        v-if="placement !== 'none'"
        class="oprops-section"
      >
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Background') }}</span>
        </header>
        <div class="oprops-section__body">
          <div class="oprops-row">
            <div class="oprops-row__label">
              <span>{{ __('Custom background') }}</span>
            </div>
            <Toggle
              :model-value="formBgEnabled"
              size="sm"
              data-testid="form-bg-toggle"
              @update:model-value="onFormBgToggle"
            />
          </div>
          <template v-if="formBgEnabled">
            <MediaPicker
              :model-value="optin.bg_image || ''"
              label=""
              helper-text=""
              :button-text="__('Upload background')"
              :replace-text="__('Replace background')"
              :remove-text="__('Remove background')"
              @update:model-value="onFormBgImageChange"
            />
            <div class="oprops-overlay">
              <label class="oprops-overlay__label">
                {{ __('Overlay opacity') }}
                <span class="oprops-overlay__pct">{{ formBgOpacity }}%</span>
              </label>
              <input
                type="range"
                min="0"
                max="100"
                step="1"
                class="oprops-overlay__range"
                :value="formBgOpacity"
                data-testid="form-bg-opacity"
                :aria-label="__('Background overlay opacity')"
                @input="(e) => onFormBgOpacityInput(Number(e.target.value))"
              >
            </div>
          </template>
        </div>
      </section>

      <!-- Advanced — its only control is double opt-in, which is free. -->
      <section
        v-if="placement !== 'none'"
        class="oprops-section"
      >
        <header class="oprops-section__head">
          <span class="oprops-section__title">{{ __('Advanced') }}</span>
        </header>
        <div class="oprops-section__body">
          <div class="oprops-row">
            <div class="oprops-row__label">
              <span>{{ __('Double opt-in') }}</span>
              <Tooltip :label="__('Send a confirmation email and only mark the lead as confirmed once they click the link.')">
                <span class="quizably-help-dot" aria-hidden="true">?</span>
              </Tooltip>
            </div>
            <Toggle
              :model-value="doubleOptin"
              size="sm"
              data-testid="form-double-optin-toggle"
              @update:model-value="onDoubleOptinToggle"
            />
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { __, sprintf } from '@shared/i18n';
import { Badge, Input, MediaPicker, Textarea, Toggle, Tooltip, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { isPro, proFeaturesVisible } from '@admin/api/pro.js';
import { formCopyFor, resolveFormPlacement, resolveFormSkippable } from '@shared/formPlacement.js';

const store = useQuizBuilderStore();
const toast = useToast();

const settings = computed(() => store.quiz?.settings ?? {});
const optin = computed(() => settings.value.optin ?? {});
const screens = computed(() => settings.value.screens ?? {});

// ── Alignment ─────────────────────────────────────────────────────────────────
const ALIGN_OPTIONS = [
  { value: 'left',   label: __('Left'),   icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="2" y1="8" x2="10" y2="8"/><line x1="2" y1="12" x2="12" y2="12"/></svg>` },
  { value: 'center', label: __('Center'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="4" y1="8" x2="12" y2="8"/><line x1="3" y1="12" x2="13" y2="12"/></svg>` },
  { value: 'right',  label: __('Right'),  icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="6" y1="8" x2="14" y2="8"/><line x1="4" y1="12" x2="14" y2="12"/></svg>` },
];

const formAlign = computed(() => {
  const v = screens.value.optin_align;
  return v === 'left' || v === 'right' ? v : 'center';
});

function onAlignChange(next) {
  if (next === formAlign.value) return;
  store.stageSettings({ screens: { ...screens.value, optin_align: next } });
}

// ── Vertical alignment ─────────────────────────────────────────────────────────
const VALIGN_OPTIONS = [
  { value: 'top',    label: __('Top'),    icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="2.5" x2="14" y2="2.5"/><line x1="2" y1="6.5" x2="12" y2="6.5"/><line x1="2" y1="10" x2="9" y2="10"/></svg>` },
  { value: 'center', label: __('Center'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="5.25" x2="12" y2="5.25"/><line x1="2" y1="8.75" x2="9" y2="8.75"/></svg>` },
  { value: 'bottom', label: __('Bottom'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="6" x2="9" y2="6"/><line x1="2" y1="9.5" x2="12" y2="9.5"/><line x1="2" y1="13.5" x2="14" y2="13.5"/></svg>` },
];

const formValign = computed(() => {
  const v = optin.value.valign;
  return v === 'center' || v === 'bottom' ? v : 'top';
});

function onValignChange(next) {
  if (next === formValign.value) return;
  store.stageSettings({ optin: { ...optin.value, valign: next } });
}
// Shared with useQuizFlow.js's getFormPlacement() so the admin selector
// and the live quiz can never independently drift out of sync again — see
// resolveFormPlacement()'s doc comment for why an unsaved settings.optin
// must resolve to 'none', not to a position.
const placement = computed(() => resolveFormPlacement(optin.value));
// Independent of position: can the visitor skip the form? (Legacy rows that
// only stored placement 'optional' read as skippable - see formPlacement.js.)
const skippable = computed(() => resolveFormSkippable(optin.value));
// The button label shown when the author hasn't typed one - depends on where
// the form sits ("Start quiz" before the quiz, "See my result" at the end).
const defaultSubmitLabel = computed(() => formCopyFor(placement.value).submit);

// Pro-tier options/fields are only listed when Pro is active or Pro promotion
// is on (see api/pro.js). Otherwise they're absent, not disabled.
const proTierVisible = computed(() => proFeaturesVisible());

const ALL_PLACEMENT_OPTIONS = [
  { value: 'start', label: __('Before quiz'), pro: false },
  { value: 'end', label: __('End of quiz'), pro: false },
  { value: 'mid', label: __('Mid-quiz'), pro: true },
  { value: 'none', label: __('Off'), pro: false },
];

const SKIPPABLE_OPTIONS = [
  { value: true, label: __('Yes') },
  { value: false, label: __('No') },
];

const placementOptions = computed(() =>
  ALL_PLACEMENT_OPTIONS.filter((p) => !p.pro || proTierVisible.value)
);

const placementHint = computed(() => {
  switch (placement.value) {
    case 'start':
      return __('Shown first, before the quiz begins.');
    case 'end':
      return __('Shown after the last question, right before the result.');
    case 'mid':
      // A saved 'mid' can outlive Pro (deactivated / imported quiz): keep the
      // description, drop the tier prefix while Pro isn't on offer.
      return proTierVisible.value
        ? __('Pro — shown between two questions to break the flow.')
        : __('Shown between two questions to break the flow.');
    default:
      return __('The form is off. No leads will be collected for this quiz.');
  }
});

const skippableHint = computed(() =>
  skippable.value
    ? __('Yes — visitors get a Skip button and can carry on without filling it in.')
    : __('No — visitors have to fill it in to continue.')
);

const ALL_FIELD_DEFS = [
  { id: 'name', label: __('Name'), pro: false, placeholderHint: __('Jane Doe') },
  { id: 'email', label: __('Email'), pro: false, placeholderHint: __('you@example.com') },
  { id: 'phone', label: __('Phone'), pro: true, placeholderHint: '(555) 123-4567' },
  { id: 'custom', label: __('Custom field'), pro: true, placeholderHint: '' },
];

const fieldDefs = computed(() =>
  ALL_FIELD_DEFS.filter((f) => !f.pro || proTierVisible.value)
);

function toggleAriaLabel(def) {
  if (isFieldRequired(def.id)) {
    // translators: %s is a form field name, e.g. "Email".
    return sprintf(__('%s is always required'), def.label);
  }
  return isFieldOn(def.id)
    // translators: %s is a form field name, e.g. "Phone".
    ? sprintf(__('Disable %s'), def.label)
    // translators: %s is a form field name, e.g. "Phone".
    : sprintf(__('Enable %s'), def.label);
}

function isFieldRequired(id) {
  return id === 'email';
}

function isFieldOn(id) {
  if (isFieldRequired(id)) return true;
  const arr = Array.isArray(optin.value.fields) ? optin.value.fields : ['name', 'email'];
  return arr.includes(id);
}

function fieldConfig(id) {
  return (optin.value.field_config || {})[id] || {};
}

const enabledCount = computed(() =>
  fieldDefs.value.filter((f) => !f.pro && isFieldOn(f.id)).length
);

const isProUser = computed(() => isPro());

function onPlacementChange(opt) {
  if (opt.pro && !isProUser.value) { proToast(opt.label); return; }
  // Persist the *resolved* skippable value alongside the new position: a legacy
  // row that was skippable only because its placement was 'optional' would
  // otherwise silently become compulsory the moment its position is changed.
  store.stageSettings({
    optin: { ...optin.value, placement: opt.value, skippable: skippable.value },
  });
}

function onSkippableChange(next) {
  if (next === skippable.value) return;
  // Also writes the canonical position, so a legacy 'gate' / 'optional' value is
  // replaced the first time the author touches either setting.
  store.stageSettings({
    optin: { ...optin.value, placement: placement.value, skippable: next },
  });
}

const formBgEnabled = computed(() => Boolean(optin.value.bg_enabled));
const formBgOpacity = computed(() => {
  const v = Number(optin.value.bg_opacity);
  return Number.isFinite(v) ? Math.max(0, Math.min(100, v)) : 0;
});

function onFormBgToggle(next) {
  store.stageSettings({ optin: { ...optin.value, bg_enabled: Boolean(next) } });
  // Toggles persist immediately so a reload reflects the new state right away.
  store.flushStagedSettings();
}
function onFormBgImageChange(url) {
  store.stageSettings({ optin: { ...optin.value, bg_image: (url ?? '').toString() } });
  // Image uploads persist immediately so a reload keeps the chosen image.
  store.flushStagedSettings();
}
function onFormBgOpacityInput(value) {
  store.stageSettings({ optin: { ...optin.value, bg_opacity: Math.max(0, Math.min(100, Number(value) || 0)) } });
}

const doubleOptin = computed(() => Boolean(optin.value.double_optin));

function onDoubleOptinToggle(next) {
  store.stageSettings({ optin: { ...optin.value, double_optin: Boolean(next) } });
}

function onFieldToggle(id, enabled, def) {
  if (isFieldRequired(id)) return;
  if (def?.pro && !isProUser.value) { proToast(def.label); return; }
  const current = Array.isArray(optin.value.fields) ? [...optin.value.fields] : ['name', 'email'];
  const next = enabled ? Array.from(new Set([...current, id])) : current.filter((f) => f !== id);
  store.stageSettings({ optin: { ...optin.value, fields: Array.from(new Set([...next, 'email'])) } });
}

function onFieldRequired(id, required) {
  const cfgMap = { ...(optin.value.field_config || {}) };
  cfgMap[id] = { ...(cfgMap[id] || {}), required: !!required };
  store.stageSettings({ optin: { ...optin.value, field_config: cfgMap } });
}

function scheduleFieldConfigSave(id, key, value) {
  const merged = { ...(optin.value.field_config || {}) };
  merged[id] = { ...(merged[id] || {}), [key]: value };
  store.stageSettings({ optin: { ...optin.value, field_config: merged } });
}

function scheduleGdprSave(value) {
  store.stageSettings({ optin: { ...optin.value, gdpr_text: value } });
}

function scheduleSubmitLabelSave(value) {
  store.stageSettings({ optin: { ...optin.value, submit_label: value } });
}

// These are called on @blur but scheduleXxxSave already persists on every input event,
// so no deferred buffer needs flushing — these are intentional no-ops.
function flushFieldConfigSave() {}
function flushSubmitLabelSave() {}

function onImageChange(url) {
  store.stageSettings({ optin: { ...optin.value, image_url: (url ?? '').toString() } });
  // Image uploads persist immediately so a reload keeps the chosen image.
  store.flushStagedSettings();
}

// ── Image fit + height ───────────────────────────────────────────────────────
const FIT_OPTIONS = [
  { value: 'contain', label: __('Contain') },
  { value: 'cover',   label: __('Cover') },
  { value: 'repeat',  label: __('Repeat') },
];

const FIT_HINTS = {
  contain: __('Full image visible, letterboxed if needed.'),
  cover:   __('Image fills the slot — edges may be cropped.'),
  repeat:  __('Image tiles to fill the slot.'),
};

const imageFit = computed(() => {
  const v = optin.value.image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

const activeFitHint = computed(() => FIT_HINTS[imageFit.value]);

function onFitChange(value) {
  store.stageSettings({ optin: { ...optin.value, image_fit: value } });
  store.flushStagedSettings();
}

const DEFAULT_IMAGE_HEIGHT = 240;
const imageHeight = computed(() => {
  const h = Number(optin.value.image_height);
  return Number.isFinite(h) && h > 0 ? h : DEFAULT_IMAGE_HEIGHT;
});

let heightFlushTimer = null;
function onImageHeightChange(rawValue) {
  const parsed = Math.max(80, Math.min(800, Math.floor(Number(rawValue) || DEFAULT_IMAGE_HEIGHT)));
  store.stageSettings({ optin: { ...optin.value, image_height: parsed } });
  if (heightFlushTimer) clearTimeout(heightFlushTimer);
  heightFlushTimer = setTimeout(() => store.flushStagedSettings(), 300);
}

function proToast(label) {
  // translators: %s is the name of a Pro-only option or field.
  toast.push({ variant: 'info', title: __('Pro feature'), message: sprintf(__('%s requires Quizably Pro.'), label) });
}
</script>

<style scoped>
.oprops {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--bg-surface);
  min-height: 0;
}

.oprops__scroll {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  min-height: 0;
  min-width: 0;
  padding-bottom: 32px;
}

.oprops-section + .oprops-section {
  border-top: 1px solid var(--border-1);
}

.oprops-section__head {
  padding: 18px 16px 8px;
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}

.oprops-section__title {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
}

.oprops-section__sub {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  letter-spacing: 0.02em;
}

.oprops-section__body {
  padding: 4px 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.oprops-section__body--tight {
  gap: 6px;
  min-width: 0;
  overflow-x: hidden;
}

.oprops-section__body--tight > * {
  min-width: 0;
}

.oprops-hint {
  margin: 0;
  font-size: 11.5px;
  color: var(--ink-3);
  line-height: 1.45;
}

/* Option count varies (the Pro-tier "Mid" option is only listed when Pro is
   active or promoted), so columns are auto-sized rather than fixed at four. */
.oprops-seg {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: 1fr;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.oprops-seg__opt {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 8px 4px;
  border: 0;
  border-radius: var(--r-xs);
  background: transparent;
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-3);
  cursor: pointer;
}

.oprops-seg__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.oprops-seg__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.oprops-seg__pill {
  font-size: 9px;
}

/* Field cards */
.field-card {
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  transition: border-color 150ms;
}

.field-card.is-on {
  background: var(--bg-surface);
  border-color: var(--border-2);
}

.field-card.is-pro {
  background: transparent;
  border-style: dashed;
}

.field-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
}

.field-card__name {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-2);
}

.field-card.is-pro .field-card__name {
  color: var(--ink-3);
}

.field-card__body {
  padding: 6px 12px 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  border-top: 1px solid var(--border-1);
  margin-top: 2px;
}

.field-card__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 4px 2px;
  font-size: 12.5px;
  color: var(--ink-2);
}

/* Generic Pro row */
.oprops-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  background: var(--bg-canvas);
  border-radius: var(--r-sm);
  font-size: 13px;
  color: var(--ink-2);
}

.oprops-row__label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
}

:deep(.quizably-help-dot) {
  display: inline-grid;
  place-items: center;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--bg-subtle);
  color: var(--ink-3);
  font-size: 10px;
  font-weight: 600;
  cursor: help;
  user-select: none;
  border: 1px solid var(--border-1);
}

.oprops-overlay {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
}

.oprops-overlay__label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
}

.oprops-overlay__pct {
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-3);
}

.oprops-overlay__range {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 4px;
  background: var(--border-2);
  border-radius: var(--r-pill);
  outline: none;
  cursor: pointer;
}

.oprops-overlay__range::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--brand);
  cursor: pointer;
  border: 2px solid #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.18);
}

/* ── Alignment segmented control ─────────────────────────────────────────────── */
.oprops-align {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.oprops-align__opt {
  padding: 8px;
  border: 0;
  border-radius: var(--r-xs);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ink-3);
  cursor: pointer;
  transition: background 150ms, color 150ms;
}

.oprops-align__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.oprops-align__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.oprops-align__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
}

.oprops-align__icon :deep(svg) {
  width: 16px;
  height: 16px;
}

/* Image fit segmented control */
.oprops-fit {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.oprops-fit__opt {
  padding: 7px 4px;
  border: 0;
  border-radius: var(--r-xs);
  background: transparent;
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-3);
  cursor: pointer;
  text-align: center;
}

.oprops-fit__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.oprops-fit__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}
</style>
