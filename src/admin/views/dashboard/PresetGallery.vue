<template>
  <div class="preset-gallery">
    <!-- ── Filter bar ─────────────────────────────────────────────────── -->
    <div class="preset-gallery__filters">
      <!-- Type chips -->
      <div class="preset-gallery__filter-row">
        <span class="preset-gallery__filter-label">{{ __('Type') }}</span>
        <div class="preset-gallery__chips">
          <button
            :class="['preset-gallery__chip', { 'is-active': activeType === null }]"
            @click="activeType = null"
          >{{ __('All') }}</button>
          <button
            v-for="t in types"
            :key="t.id"
            :class="[
              'preset-gallery__chip',
              { 'is-active': activeType === t.id, 'is-pro': t.pro },
            ]"
            @click="activeType = t.id"
          >
            {{ t.label }}
            <span v-if="t.pro" class="preset-gallery__chip-pro" :aria-label="__('Pro')">✦</span>
          </button>
        </div>
      </div>

      <!-- Template chips -->
      <div class="preset-gallery__filter-row">
        <span class="preset-gallery__filter-label">{{ __('Template') }}</span>
        <div class="preset-gallery__chips">
          <button
            :class="['preset-gallery__chip', { 'is-active': activeTemplate === null }]"
            @click="activeTemplate = null"
          >{{ __('All') }}</button>
          <button
            v-for="tpl in templates"
            :key="tpl.id"
            :class="[
              'preset-gallery__chip',
              { 'is-active': activeTemplate === tpl.id, 'is-pro': tpl.pro },
            ]"
            @click="activeTemplate = tpl.id"
          >
            {{ tpl.label }}
            <span v-if="tpl.pro" class="preset-gallery__chip-pro" :aria-label="__('Pro')">✦</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ── Results count ───────────────────────────────────────────────── -->
    <p
      v-if="filtered.length === 0"
      class="preset-gallery__empty"
    >
      {{ __('No presets match your filters.') }}
    </p>

    <!-- ── Grid ───────────────────────────────────────────────────────── -->
    <div
      v-else
      class="preset-gallery__grid"
      role="list"
    >
      <div
        v-for="preset in filtered"
        :key="preset.id"
        :class="[
          'preset-gallery__card',
          { 'is-selected': selected === preset.id, 'is-pro': preset.pro },
        ]"
        role="listitem"
        tabindex="0"
        :aria-pressed="selected === preset.id"
        @click="onSelect(preset)"
        @keydown.enter.prevent="onSelect(preset)"
        @keydown.space.prevent="onSelect(preset)"
      >
        <!-- Thumbnail -->
        <div
          class="preset-gallery__thumb"
          :style="thumbStyle(preset)"
        >
          <img
            v-if="preset.thumbnail"
            :src="preset.thumbnail"
            :alt="previewAlt(preset)"
            loading="lazy"
            @error="onImgError($event)"
          >

          <!-- Selected check pip -->
          <span
            v-if="selected === preset.id"
            class="preset-gallery__check"
            aria-hidden="true"
          >
            <svg viewBox="0 0 16 16" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 8.5 6.5 12 13 4.5"/>
            </svg>
          </span>

          <!-- Pro badge -->
          <span
            v-if="preset.pro"
            class="preset-gallery__pro-badge"
            :aria-label="__('Pro required')"
          >{{ __('Pro ✦') }}</span>
        </div>

        <!-- Card meta -->
        <div class="preset-gallery__meta">
          <h4 class="preset-gallery__title">{{ preset.title }}</h4>
          <p class="preset-gallery__desc">{{ preset.description }}</p>

          <div class="preset-gallery__badges">
            <span :class="['preset-gallery__type-badge', `is-${preset.type}`]">
              {{ typeLabelMap[preset.type] ?? preset.type }}
            </span>
            <span class="preset-gallery__template-badge">
              {{ templateLabelMap[preset.template] ?? preset.template }}
            </span>
            <span class="preset-gallery__q-count">
              {{ questionCountText(preset.questionCount) }}
            </span>
          </div>
        </div>

        <!-- Use template overlay button (appears when selected) -->
        <button
          v-if="selected === preset.id"
          class="preset-gallery__use-btn"
          @click.stop="emit('select', preset)"
        >
          {{ __('Use this template →') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { __, _n, sprintf } from '@shared/i18n';
import { computed, ref } from 'vue';
import {
  PRESET_TYPES,
  PRESET_TEMPLATES,
  availablePresets,
  availablePresetTemplates,
  availablePresetTypes,
} from '@admin/data/presets.js';

const emit = defineEmits(['select']);

// translators: %d is the number of questions in a preset quiz ("Q" = question).
const questionCountText = (n) => sprintf(_n('%d Q', '%d Qs', n), n);

// translators: %s is the title of a preset quiz.
const previewAlt = (preset) => sprintf(__('%s preview'), preset.title);

// ── Filter state ─────────────────────────────────────────────────────────────

const activeType     = ref(null);  // null = All
const activeTemplate = ref(null);  // null = All
const selected       = ref(null);  // selected preset id

// ── Computed ──────────────────────────────────────────────────────────────────

// Pro-tier types, templates and presets are left out entirely (not greyed) while
// the free plugin isn't promoting Pro — see the `available*()` helpers.
const types     = computed(() => availablePresetTypes());
const templates = computed(() => availablePresetTemplates());

const filtered = computed(() => {
  return availablePresets().filter((p) => {
    if (activeType.value && p.type !== activeType.value) return false;
    if (activeTemplate.value && p.template !== activeTemplate.value) return false;
    return true;
  });
});

const typeLabelMap = computed(() => {
  const m = {};
  for (const t of PRESET_TYPES) m[t.id] = t.label;
  return m;
});

const templateLabelMap = computed(() => {
  const m = {};
  for (const t of PRESET_TEMPLATES) m[t.id] = t.label;
  return m;
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function thumbStyle(preset) {
  // Only apply gradient when there's no thumbnail (or it errored).
  // The img element renders over this background when it loads successfully.
  const [c1, c2] = preset.gradient ?? ['#6366f1', '#ec4899'];
  return { background: `linear-gradient(135deg, ${c1}, ${c2})` };
}

function onImgError(evt) {
  // Hide broken image; the gradient background shows through.
  evt.target.style.display = 'none';
}

function onSelect(preset) {
  selected.value = preset.id;
}

/** Reset selection (called by parent when modal resets). */
function reset() {
  selected.value = null;
  activeType.value = null;
  activeTemplate.value = null;
}

defineExpose({ reset });
</script>

<style scoped>
/* ── Filter bar ────────────────────────────────────────────────────────── */

.preset-gallery__filters {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-1);
  margin-bottom: 16px;
}

.preset-gallery__filter-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.preset-gallery__filter-label {
  font-family: var(--f-mono);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--ink-4);
  min-width: 64px;
  flex-shrink: 0;
}

.preset-gallery__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.preset-gallery__chip {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 4px 10px;
  border-radius: 99px;
  border: 1.5px solid var(--border-2);
  background: var(--bg-surface);
  color: var(--ink-2);
  font-family: var(--f-sans);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: border-color 120ms ease, background 120ms ease, color 120ms ease;
  white-space: nowrap;
}

.preset-gallery__chip:hover {
  border-color: var(--brand);
  color: var(--brand);
}

.preset-gallery__chip.is-active {
  border-color: var(--brand);
  background: var(--brand-tint);
  color: var(--brand-hover);
}

.preset-gallery__chip.is-pro {
  border-style: dashed;
}

.preset-gallery__chip-pro {
  font-size: 9px;
  color: var(--accent);
  vertical-align: super;
}

/* ── Empty state ───────────────────────────────────────────────────────── */

.preset-gallery__empty {
  text-align: center;
  padding: 48px 24px;
  color: var(--ink-4);
  font-size: 14px;
}

/* ── Grid ──────────────────────────────────────────────────────────────── */

.preset-gallery__grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

@media (max-width: 900px) {
  .preset-gallery__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 680px) {
  .preset-gallery__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 400px) {
  .preset-gallery__grid {
    grid-template-columns: 1fr;
  }
}

/* ── Card ──────────────────────────────────────────────────────────────── */

.preset-gallery__card {
  position: relative;
  display: flex;
  flex-direction: column;
  border-radius: var(--r-lg);
  border: 2px solid var(--border-1);
  background: var(--bg-surface);
  overflow: hidden;
  cursor: pointer;
  outline: none;
  transition:
    border-color 150ms ease,
    box-shadow 150ms ease,
    transform 150ms ease;
}

.preset-gallery__card:hover {
  border-color: var(--border-3);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.preset-gallery__card:focus-visible {
  box-shadow: var(--shadow-focus);
  border-color: var(--brand);
}

.preset-gallery__card.is-selected {
  border-color: var(--brand);
  box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2), var(--shadow-lg);
  transform: translateY(-3px);
}

.preset-gallery__card.is-pro {
  background: linear-gradient(180deg, var(--bg-surface) 60%, var(--bg-subtle));
}

/* ── Thumbnail ─────────────────────────────────────────────────────────── */

.preset-gallery__thumb {
  position: relative;
  aspect-ratio: 16 / 10;
  overflow: hidden;
  flex-shrink: 0;
  background: var(--bg-subtle);
}

.preset-gallery__thumb img {
  width: 100%;
  height: 100%;
  /* no-crop rule: full thumbnail must stay visible — letterbox on neutral bg, never cover */
  object-fit: contain;
  display: block;
  transition: transform 300ms ease;
}

.preset-gallery__card:hover .preset-gallery__thumb img {
  transform: scale(1.04);
}

/* Selected check pip */
.preset-gallery__check {
  position: absolute;
  top: 8px;
  inset-inline-start: 8px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--brand);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.6);
}

/* Pro badge */
.preset-gallery__pro-badge {
  position: absolute;
  top: 8px;
  inset-inline-end: 8px;
  padding: 2px 8px;
  border-radius: 99px;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
  color: #fbbf24;
  font-family: var(--f-mono);
  font-size: 9px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

/* ── Meta ──────────────────────────────────────────────────────────────── */

.preset-gallery__meta {
  padding: 10px 12px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.preset-gallery__title {
  font-family: var(--f-display);
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-1);
  margin: 0;
  line-height: 1.3;
  letter-spacing: -0.005em;
}

.preset-gallery__desc {
  font-family: var(--f-sans);
  font-size: 11px;
  line-height: 1.5;
  color: var(--ink-4);
  margin: 0;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.preset-gallery__badges {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
  margin-top: 6px;
}

/* Type badge — color-coded per type */
.preset-gallery__type-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 6px;
  border-radius: 4px;
  font-family: var(--f-mono);
  font-size: 9px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  font-weight: 600;
}

.preset-gallery__type-badge.is-personality { background: #ede9fe; color: #6d28d9; }
.preset-gallery__type-badge.is-trivia      { background: #fef3c7; color: #92400e; }
.preset-gallery__type-badge.is-survey      { background: #dbeafe; color: #1d4ed8; }
.preset-gallery__type-badge.is-poll        { background: #dcfce7; color: #166534; }
.preset-gallery__type-badge.is-weighted    { background: #fce7f3; color: #9d174d; }
.preset-gallery__type-badge.is-branching   { background: #ffedd5; color: #9a3412; }

.preset-gallery__template-badge {
  display: inline-flex;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--bg-subtle);
  color: var(--ink-3);
  font-family: var(--f-mono);
  font-size: 9px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.preset-gallery__q-count {
  margin-inline-start: auto;
  font-family: var(--f-mono);
  font-size: 10px;
  color: var(--ink-4);
  white-space: nowrap;
}

/* ── Use button overlay ────────────────────────────────────────────────── */

.preset-gallery__use-btn {
  display: block;
  width: 100%;
  padding: 9px 12px;
  background: var(--brand);
  color: #fff;
  font-family: var(--f-sans);
  font-size: 13px;
  font-weight: 600;
  text-align: center;
  border: none;
  cursor: pointer;
  letter-spacing: -0.01em;
  transition: background 120ms ease;
}

.preset-gallery__use-btn:hover {
  background: var(--brand-hover);
}

@media (prefers-reduced-motion: reduce) {
  .preset-gallery__card,
  .preset-gallery__thumb img {
    transition: none;
  }
}
</style>
