<template>
  <Modal
    :model-value="modelValue"
    :title="modalTitle"
    :size="modalSize"
    @update:model-value="onModalToggle"
    @close="reset"
  >
    <!-- ── Step indicator (blank flow only) ──────────────────────────── -->
    <div
      v-if="mode === 'blank'"
      class="new-quiz__steps"
      role="list"
      :aria-label="__('Setup progress')"
    >
      <div
        v-for="n in 3"
        :key="n"
        :class="[
          'new-quiz__step',
          { 'is-active': step === n, 'is-done': step > n },
        ]"
        role="listitem"
        :aria-current="step === n ? 'step' : undefined"
      >
        <span class="new-quiz__step-dot" aria-hidden="true" />
        <span class="new-quiz__step-label">{{ stepLabels[n - 1] }}</span>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- Step 0 — Mode select (Choose a Template vs Start Blank)        -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    <div
      v-if="mode === null"
      class="new-quiz__step-body new-quiz__mode-select"
    >
      <h3 class="new-quiz__heading">{{ __('How do you want to start?') }}</h3>
      <p class="new-quiz__desc">
        {{ __('Choose a ready-made quiz template or build your own from scratch.') }}
      </p>

      <div class="new-quiz__mode-grid">
        <!-- Preset option -->
        <button
          class="new-quiz__mode-card"
          data-mode="preset"
          @click="selectMode('preset')"
        >
          <span class="new-quiz__mode-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
              <rect x="3" y="3" width="8" height="8" rx="1.5"/>
              <rect x="13" y="3" width="8" height="8" rx="1.5"/>
              <rect x="3" y="13" width="8" height="8" rx="1.5"/>
              <rect x="13" y="13" width="8" height="8" rx="1.5"/>
            </svg>
          </span>
          <span class="new-quiz__mode-title">{{ __('Choose a Template') }}</span>
          <span class="new-quiz__mode-desc">{{ browsePresetsText }}</span>
          <span class="new-quiz__mode-cta">{{ __('Browse templates →') }}</span>
        </button>

        <!-- Blank option -->
        <button
          class="new-quiz__mode-card"
          data-mode="blank"
          @click="selectMode('blank')"
        >
          <span class="new-quiz__mode-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
              <path d="M12 5v14M5 12h14"/>
            </svg>
          </span>
          <span class="new-quiz__mode-title">{{ __('Start Blank') }}</span>
          <span class="new-quiz__mode-desc">{{ __('Pick a quiz type, choose a template, and write your own questions from scratch.') }}</span>
          <span class="new-quiz__mode-cta">{{ __('Start from scratch →') }}</span>
        </button>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- Preset mode — gallery                                          -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    <div
      v-else-if="mode === 'preset' && presetStep === 'gallery'"
      class="new-quiz__step-body"
    >
      <div class="new-quiz__preset-header">
        <h3 class="new-quiz__heading">{{ __('Choose a starter quiz') }}</h3>
        <p class="new-quiz__desc">
          {{ __('Click a template to select it, then click "Use this template" to continue.') }}
        </p>
      </div>
      <PresetGallery
        ref="galleryRef"
        @select="onPresetSelected"
      />
    </div>

    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- Preset mode — name step                                        -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    <div
      v-else-if="mode === 'preset' && presetStep === 'name'"
      class="new-quiz__step-body new-quiz__preset-name"
    >
      <!-- Preset preview card -->
      <div
        v-if="selectedPreset"
        class="new-quiz__preset-preview"
      >
        <div
          class="new-quiz__preset-preview-thumb"
          :style="presetThumbStyle"
        >
          <img
            v-if="selectedPreset.thumbnail"
            :src="selectedPreset.thumbnail"
            :alt="selectedPreset.title"
            loading="lazy"
            @error="$event.target.style.display='none'"
          >
        </div>
        <div class="new-quiz__preset-preview-meta">
          <span class="new-quiz__preset-preview-title">{{ selectedPreset.title }}</span>
          <span class="new-quiz__preset-preview-badges">
            <span :class="['pv-type-badge', `is-${selectedPreset.type}`]">{{ selectedPreset.type }}</span>
            <span class="pv-tpl-badge">{{ selectedPreset.template }}</span>
            <span class="pv-q-badge">{{ presetQuestionsText }}</span>
          </span>
        </div>
      </div>

      <h3 class="new-quiz__heading" style="margin-top:20px">{{ __('Name your quiz') }}</h3>
      <p class="new-quiz__desc">
        {{ __('The title is pre-filled from the template — rename it to anything you like.') }}
      </p>

      <div class="new-quiz__fields">
        <Input
          ref="titleInputRef"
          v-model="title"
          :label="__('Quiz title')"
          :placeholder="titlePlaceholder"
          required
          autofocus
          :error-text="titleError"
        />
        <Input
          v-model="slug"
          ltr
          :label="__('Slug')"
          :helper-text="__('Auto-generated from the title — edit to customize.')"
          placeholder="whats-your-coffee-personality"
          :error-text="slugError"
          @input="onSlugManualEdit"
        />
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- Blank flow — Step 1: Pick type                                 -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    <div
      v-else-if="mode === 'blank' && step === 1"
      class="new-quiz__step-body"
    >
      <h3 class="new-quiz__heading">{{ __('What kind of quiz are you building?') }}</h3>
      <p class="new-quiz__desc">
        {{ __('Pick a type. Design and behavior are editable later — type determines how results are calculated.') }}
      </p>
      <div class="new-quiz__type-grid">
        <Card
          v-for="t in visibleTypes"
          :key="t.id"
          interactive
          padding="md"
          :class="[
            'new-quiz__type-card',
            { 'is-selected': selectedType === t.id, 'is-pro': t.pro && !isProUser },
          ]"
          role="button"
          tabindex="0"
          :aria-pressed="selectedType === t.id"
          :data-type="t.id"
          @click="onPickType(t)"
          @keydown.enter.prevent="onPickType(t)"
          @keydown.space.prevent="onPickType(t)"
        >
          <Badge
            v-if="t.pro"
            variant="pro"
            size="sm"
            class="new-quiz__type-badge"
          >{{ __('Pro') }}</Badge>
          <span class="new-quiz__type-icon" aria-hidden="true">
            <TypeIcon :name="t.id" />
          </span>
          <span class="new-quiz__type-title">{{ t.title }}</span>
          <span class="new-quiz__type-desc">{{ t.description }}</span>
        </Card>
      </div>
    </div>

    <!-- Blank flow — Step 2: Pick template -->
    <div
      v-else-if="mode === 'blank' && step === 2"
      class="new-quiz__step-body"
    >
      <h3 class="new-quiz__heading">{{ __('Choose a template') }}</h3>
      <p class="new-quiz__desc">
        {{ __('Starting designs — colors, fonts, and layout are all editable afterward in the Design tab.') }}
      </p>

      <div
        v-if="templatesStore.items.length === 0 && loadingTemplates"
        class="new-quiz__templates-loading"
        aria-live="polite"
      >
        <span class="new-quiz__spinner" aria-hidden="true" />
        <span>{{ __('Loading templates…') }}</span>
      </div>

      <div v-else class="new-quiz__template-grid">
        <div
          v-for="tpl in visibleTemplates"
          :key="tpl.id"
          :class="[
            'new-quiz__template-card',
            { 'is-selected': selectedTemplate === tpl.id, 'is-pro': tpl.pro && !isProUser },
          ]"
          role="button"
          tabindex="0"
          :aria-pressed="selectedTemplate === tpl.id"
          :data-template="tpl.id"
          @click="onPickTemplate(tpl)"
          @keydown.enter.prevent="onPickTemplate(tpl)"
          @keydown.space.prevent="onPickTemplate(tpl)"
        >
          <div class="new-quiz__template-thumb" :style="thumbStyle(tpl)">
            <img
              v-if="tpl.thumbnail"
              :src="tpl.thumbnail"
              :alt="templatePreviewAlt(tpl)"
              loading="lazy"
            >
            <span v-if="selectedTemplate === tpl.id" class="new-quiz__template-check" aria-hidden="true">
              <svg viewBox="0 0 16 16" width="12" height="12"><path d="M3 8.5 6.5 12 13 4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              {{ __('Selected') }}
            </span>
          </div>
          <div class="new-quiz__template-meta">
            <span class="new-quiz__template-name">{{ tpl.name }}</span>
            <!-- Free/Pro tier tag — only meaningful when there is a Pro tier to show. -->
            <Badge
              v-if="proTierVisible"
              :variant="tpl.pro ? 'pro' : 'neutral'"
              size="sm"
            >
              {{ tpl.pro ? __('Pro') : __('Free') }}
            </Badge>
          </div>
        </div>
      </div>
    </div>

    <!-- Blank flow — Step 3: Title + slug -->
    <div
      v-else-if="mode === 'blank' && step === 3"
      class="new-quiz__step-body"
    >
      <h3 class="new-quiz__heading">{{ __('Name your quiz') }}</h3>
      <p class="new-quiz__desc">
        {{ __('You can change these later. The slug is used in the URL if you enable a dedicated quiz page.') }}
      </p>
      <div class="new-quiz__fields">
        <Input
          ref="titleInputRef"
          v-model="title"
          :label="__('Quiz title')"
          :placeholder="titlePlaceholder"
          required
          autofocus
          :error-text="titleError"
        />
        <Input
          v-model="slug"
          ltr
          :label="__('Slug')"
          :helper-text="__('Auto-generated from the title — edit to customize.')"
          placeholder="whats-your-coffee-personality"
          :error-text="slugError"
          @input="onSlugManualEdit"
        />
      </div>
    </div>

    <!-- ── Footer ─────────────────────────────────────────────────────── -->
    <template #footer>
      <!-- Back button — shown in all sub-states except mode select -->
      <Button
        v-if="mode !== null"
        variant="ghost"
        :disabled="submitting"
        @click="goBack"
      >
        {{ __('Back') }}
      </Button>

      <!-- Cancel always visible -->
      <Button
        variant="outline"
        :disabled="submitting"
        @click="closeModal"
      >
        {{ __('Cancel') }}
      </Button>

      <!-- Preset gallery: no primary action (user clicks card button instead) -->

      <!-- Preset name step: Create -->
      <Button
        v-if="mode === 'preset' && presetStep === 'name'"
        variant="primary"
        :loading="submitting"
        :disabled="!canSubmit"
        @click="onCreate"
      >
        {{ __('Create quiz') }}
      </Button>

      <!-- Blank flow: Next / Create -->
      <Button
        v-if="mode === 'blank' && step < 3"
        variant="primary"
        :disabled="!canAdvance"
        @click="goNext"
      >
        {{ __('Next') }}
      </Button>
      <Button
        v-if="mode === 'blank' && step === 3"
        variant="primary"
        :loading="submitting"
        :disabled="!canSubmit"
        @click="onCreate"
      >
        {{ __('Create quiz') }}
      </Button>
    </template>
  </Modal>
</template>

<script setup>
import { __, _n, sprintf } from '@shared/i18n';
import { computed, h, nextTick, ref, watch } from 'vue';
import { Badge, Button, Card, Input, Modal, useToast } from '@admin/ui';
import { useQuizzesStore } from '@admin/stores/quizzes';
import { useTemplatesStore } from '@admin/stores/templates';
import { useSettingsStore } from '@admin/stores/settings';
import { slugify } from '@admin/utils/slug';
import { isPro, proFeaturesVisible } from '@admin/api/pro.js';
import { importPreset } from '@admin/api/presets.js';
import { availablePresets } from '@admin/data/presets.js';
import PresetGallery from './PresetGallery.vue';

// ── Type icons (same inline render functions as before) ────────────────────

const SVG_ATTRS = {
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': 2,
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round',
};
const ICONS = {
  personality: () =>
    h('svg', SVG_ATTRS, [
      h('circle', { cx: 12, cy: 12, r: 10 }),
      h('path', { d: 'M8 14s1.5 2 4 2 4-2 4-2' }),
      h('path', { d: 'M9 9h.01' }),
      h('path', { d: 'M15 9h.01' }),
    ]),
  trivia: () =>
    h('svg', SVG_ATTRS, [
      h('path', { d: 'M12 2 15.09 8.26 22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z' }),
    ]),
  survey: () =>
    h('svg', SVG_ATTRS, [
      h('path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z' }),
      h('path', { d: 'M14 2v6h6' }),
      h('path', { d: 'M16 13H8' }),
      h('path', { d: 'M16 17H8' }),
      h('path', { d: 'M10 9H8' }),
    ]),
  poll: () =>
    h('svg', SVG_ATTRS, [
      h('rect', { x: 3, y: 3, width: 18, height: 18, rx: 2 }),
      h('path', { d: 'M8 17V9' }),
      h('path', { d: 'M12 17v-5' }),
      h('path', { d: 'M16 17v-3' }),
    ]),
  weighted: () =>
    h('svg', SVG_ATTRS, [
      h('path', { d: 'M12 3v18' }),
      h('path', { d: 'M5 8h14' }),
      h('path', { d: 'm5 8-3 7a4 4 0 0 0 6 0z' }),
      h('path', { d: 'm19 8-3 7a4 4 0 0 0 6 0z' }),
    ]),
  branching: () =>
    h('svg', SVG_ATTRS, [
      h('circle', { cx: 6, cy: 6, r: 3 }),
      h('circle', { cx: 18, cy: 6, r: 3 }),
      h('circle', { cx: 12, cy: 18, r: 3 }),
      h('path', { d: 'M6 9v1a4 4 0 0 0 4 4h4a4 4 0 0 0 4-4V9' }),
      h('path', { d: 'M12 15v-1' }),
    ]),
};
const TypeIcon = {
  name: 'TypeIcon',
  props: { name: { type: String, required: true } },
  setup(props) {
    return () => (ICONS[props.name] ? ICONS[props.name]() : null);
  },
};

// ── Props / emits ──────────────────────────────────────────────────────────

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'created']);

// ── Stores / services ──────────────────────────────────────────────────────

const toast = useToast();
const quizzesStore = useQuizzesStore();
const templatesStore = useTemplatesStore();
const settingsStore = useSettingsStore();

// ── Quiz types definition ──────────────────────────────────────────────────

const types = [
  { id: 'personality', title: __('Personality / Result'),      description: __('Which X are you? Answers map to results.'),            pro: false },
  { id: 'trivia',      title: __('Trivia / Score-based'),      description: __('Right/wrong questions with a final score.'),           pro: false },
  { id: 'survey',      title: __('Survey / Form'),             description: __('Collect responses, show a tailored result.'),          pro: false },
  { id: 'poll',        title: __('Poll / Single question'),    description: __('One question, live vote results.'),                    pro: false },
  { id: 'weighted',    title: __('Weighted Assessment'),       description: __('Multi-category scoring across results.'),              pro: true  },
  { id: 'branching',   title: __('Branching / Lead Qualifier'), description: __('Conditional paths based on answers.'),                pro: true  },
];

const stepLabels = [__('Type'), __('Template'), __('Details')];

const titlePlaceholder = __("e.g. What's Your Coffee Personality?");

// translators: %d is the number of questions in the selected preset quiz.
const presetQuestionsText = computed(() => sprintf(
  _n('%d question', '%d questions', selectedPreset.value?.questionCount ?? 0),
  selectedPreset.value?.questionCount ?? 0
));

// translators: %s is the name of a quiz template.
const templatePreviewAlt = (tpl) => sprintf(__('%s preview'), tpl.name);

// ── State ──────────────────────────────────────────────────────────────────

/** Null = mode-select screen, 'blank' = manual 3-step flow, 'preset' = gallery flow. */
const mode           = ref(null);
/** Preset gallery sub-step: 'gallery' | 'name'. */
const presetStep     = ref('gallery');
/** The preset object selected in the gallery. */
const selectedPreset = ref(null);
/** Ref to <PresetGallery> so we can call reset(). */
const galleryRef     = ref(null);

// Blank flow state
const step             = ref(1);
const selectedType     = ref('');
const selectedTemplate = ref(null);

// Shared name step state
const title          = ref('');
const slug           = ref('');
const slugTouched    = ref(false);
const slugError      = ref('');
const submitting     = ref(false);
const loadingTemplates = ref(false);
const titleInputRef  = ref(null);

// ── Computed ───────────────────────────────────────────────────────────────

const isProUser = computed(() => isPro());

// Pro-tier types/templates/presets are Pro-only controls: absent (not greyed)
// while the free plugin isn't promoting Pro. Free users then only ever see
// choices that work, so none of the "requires Pro" toasts below are reachable.
const proTierVisible = computed(() => proFeaturesVisible());

const visibleTypes = computed(() =>
  proTierVisible.value ? types : types.filter((t) => !t.pro)
);
const visibleTemplates = computed(() =>
  proTierVisible.value ? templatesStore.items : templatesStore.items.filter((t) => !t.pro)
);
const presetCountLabel = computed(() => `${availablePresets().length}+`);
// translators: %s is a count such as "24+".
const browsePresetsText = computed(() =>
  sprintf(__('Browse %s ready-made quizzes with real questions, results, and content.'), presetCountLabel.value)
);

const selectedTypeMeta = computed(() =>
  types.find((t) => t.id === selectedType.value) || null
);
const selectedTemplateMeta = computed(() =>
  templatesStore.items.find((t) => t.id === selectedTemplate.value) || null
);

const modalTitle = computed(() => {
  if (mode.value === null) return __('New quiz');
  if (mode.value === 'preset') {
    if (presetStep.value === 'gallery') return __('New quiz · Choose a template');
    // translators: %s is the title of the selected preset quiz.
    if (selectedPreset.value) return sprintf(__('New quiz · %s'), selectedPreset.value.title);
    return __('New quiz');
  }
  // Blank mode
  const parts = [__('New quiz')];
  if (selectedTypeMeta.value) parts.push(selectedTypeMeta.value.title);
  if (step.value === 3 && selectedTemplateMeta.value) parts.push(selectedTemplateMeta.value.name);
  return parts.join(' · ');
});

/**
 * Use xl modal for the preset gallery (needs space for the 4-col grid),
 * lg for everything else.
 */
const modalSize = computed(() => {
  if (mode.value === 'preset' && presetStep.value === 'gallery') return 'xl';
  return 'lg';
});

const titleError = computed(() => {
  if (!title.value) return '';
  if (title.value.trim().length < 2) return __('Title must be at least 2 characters.');
  return '';
});

const isSlugValid = computed(() =>
  /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug.value) && slug.value.length >= 2
);
const isTitleValid = computed(() => title.value.trim().length >= 2);

const canAdvance = computed(() => {
  if (step.value === 1) {
    if (!selectedType.value) return false;
    return isProUser.value || !selectedTypeMeta.value?.pro;
  }
  if (step.value === 2) {
    if (!selectedTemplate.value) return false;
    return isProUser.value || !selectedTemplateMeta.value?.pro;
  }
  return false;
});

const canSubmit = computed(() =>
  isTitleValid.value && isSlugValid.value && !submitting.value
);

const presetThumbStyle = computed(() => {
  const p = selectedPreset.value;
  if (!p) return {};
  const [c1, c2] = p.gradient ?? ['#6366f1', '#ec4899'];
  return { background: `linear-gradient(135deg, ${c1}, ${c2})` };
});

// ── Watchers ───────────────────────────────────────────────────────────────

let slugTimer = null;
watch(title, (next) => {
  if (slugTouched.value) return;
  if (slugTimer) clearTimeout(slugTimer);
  slugTimer = setTimeout(() => {
    slug.value = slugify(next);
    slugError.value = '';
  }, 150);
});

// ── Methods ────────────────────────────────────────────────────────────────

function selectMode(m) {
  mode.value = m;
  if (m === 'blank') {
    step.value = 1;
  }
}

function onPresetSelected(preset) {
  selectedPreset.value = preset;
  presetStep.value = 'name';

  // Pre-fill title (and auto-slug) from preset if the user hasn't typed anything.
  if (!title.value) {
    title.value = preset.title;
    slugTouched.value = false;
  }

  nextTick(() => {
    const el = titleInputRef.value?.$el?.querySelector('input');
    if (el) el.focus();
  });
}

function goBack() {
  if (mode.value === 'preset') {
    if (presetStep.value === 'name') {
      presetStep.value = 'gallery';
      // Don't clear title/slug — user may want to come back and re-select.
      return;
    }
    // Back from gallery → mode select
    mode.value = null;
    return;
  }
  if (mode.value === 'blank') {
    if (step.value > 1) {
      step.value -= 1;
      return;
    }
    mode.value = null;
  }
}

function goNext() {
  if (!canAdvance.value) return;
  if (step.value === 1) {
    step.value = 2;
    loadTemplates();
  } else if (step.value === 2) {
    step.value = 3;
    nextTick(() => {
      const el = titleInputRef.value?.$el?.querySelector('input');
      if (el) el.focus();
    });
  }
}

function onSlugManualEdit() {
  slugTouched.value = true;
  slugError.value = '';
}

function thumbStyle(tpl) {
  if (tpl.thumbnail) return {};
  const palettes = [
    ['#6366F1', '#EC4899'],
    ['#1E1B4B', '#4338CA'],
    ['#F87171', '#FBBF24'],
    ['#7C3AED', '#EC4899'],
    ['#0EA5E9', '#22D3EE'],
    ['#10B981', '#34D399'],
    ['#F59E0B', '#F97316'],
    ['#EF4444', '#F472B6'],
  ];
  const key = String(tpl.id || tpl.slug || tpl.name || '').length;
  const pair = palettes[key % palettes.length];
  return { background: `linear-gradient(135deg, ${pair[0]}, ${pair[1]})` };
}

function onPickType(t) {
  if (t.pro && !isProUser.value) {
    toast.push({
      variant: 'info',
      title: __('Pro feature'),
      // translators: %s is a quiz type name, e.g. "Weighted Assessment".
      message: sprintf(__('%s quizzes require Quizably Pro. Upgrade coming soon.'), t.title),
    });
    return;
  }
  selectedType.value = t.id;
}

function onPickTemplate(tpl) {
  if (tpl.pro && !isProUser.value) {
    toast.push({
      variant: 'info',
      title: __('Pro template'),
      // translators: %s is a quiz template name.
      message: sprintf(__('%s requires Quizably Pro. Upgrade coming soon.'), tpl.name),
    });
    return;
  }
  selectedTemplate.value = tpl.id;
}

async function loadTemplates() {
  if (!templatesStore.loaded) {
    loadingTemplates.value = true;
    try {
      await templatesStore.fetch();
    } catch (e) {
      toast.push({ variant: 'danger', title: __('Could not load templates'), message: e.message });
      return;
    } finally {
      loadingTemplates.value = false;
    }
  }
  await preselectDefaultTemplate();
}

// Settings -> Defaults -> "Default template": pre-select it on the template step. Never
// overrides a choice the visitor already made, and skips a template this site can't use
// (a Pro template without Pro, or one that no longer exists).
async function preselectDefaultTemplate() {
  if (selectedTemplate.value) return;
  try {
    if (!settingsStore.loaded) await settingsStore.fetch();
  } catch {
    return; // the default is a convenience; the picker still works without it
  }
  const wanted = String(settingsStore.map?.default_template ?? '');
  if (!wanted || selectedTemplate.value) return;
  const tpl = visibleTemplates.value.find((t) => String(t.id) === wanted);
  if (tpl && (!tpl.pro || isProUser.value)) selectedTemplate.value = tpl.id;
}

async function onCreate() {
  if (!canSubmit.value) return;
  slugError.value = '';
  submitting.value = true;

  try {
    let quiz;

    if (mode.value === 'preset') {
      // ── Preset import ────────────────────────────────────────────────
      try {
        quiz = await importPreset(selectedPreset.value.id, { title: title.value.trim() });
      } catch (e) {
        // Only while Pro is promoted (or active): otherwise Pro presets are not
        // offered, so a 403 is some other failure and gets the generic error toast.
        if (proTierVisible.value && (e?.status === 403 || e?.code === 'quizably_pro_required')) {
          toast.push({
            variant: 'info',
            title: __('Pro preset'),
            // translators: %s is the title of a preset quiz.
            message: sprintf(__('"%s" requires Quizably Pro. Upgrade to import it.'), selectedPreset.value.title),
          });
          submitting.value = false;
          return;
        }
        throw e;
      }
    } else {
      // ── Manual creation ──────────────────────────────────────────────
      quiz = await quizzesStore.createQuiz({
        title:    title.value.trim(),
        slug:     slug.value,
        type:     selectedType.value,
        template: selectedTemplate.value,
      });
    }

    toast.push({
      variant: 'success',
      title: __('Quiz created'),
      // translators: %s is the title of the new quiz.
      message: quiz?.title ? sprintf(__('"%s" is ready to edit.'), quiz.title) : __('Your new quiz is ready.'),
    });

    const newId = quiz?.id;
    closeModal();
    reset();
    if (newId) emit('created', newId);

  } catch (e) {
    if (e?.status === 409 || e?.code === 'quizably_slug_conflict') {
      // translators: %s is a suggested alternative slug.
      slugError.value = sprintf(__('Slug already in use — try: %s'), `${slug.value}-2`);
    } else if (e?.status === 400) {
      toast.push({ variant: 'danger', title: __('Could not create quiz'), message: e.message || __('Please check the fields and try again.') });
    } else {
      toast.push({ variant: 'danger', title: __('Could not create quiz'), message: e?.message || __('Unexpected error.') });
    }
  } finally {
    submitting.value = false;
  }
}

function reset() {
  mode.value           = null;
  presetStep.value     = 'gallery';
  selectedPreset.value = null;
  step.value           = 1;
  selectedType.value   = '';
  selectedTemplate.value = null;
  title.value          = '';
  slug.value           = '';
  slugTouched.value    = false;
  slugError.value      = '';
  submitting.value     = false;
  galleryRef.value?.reset();
}

function closeModal() {
  emit('update:modelValue', false);
}

function onModalToggle(open) {
  emit('update:modelValue', open);
}

watch(
  () => props.modelValue,
  (open, wasOpen) => {
    if (open && !wasOpen) reset();
  }
);
</script>

<style scoped>
/* ── Step indicator ─────────────────────────────────────────────────────── */

.new-quiz__steps {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 20px;
}

.new-quiz__step {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--ink-4);
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  position: relative;
}

.new-quiz__step + .new-quiz__step::before {
  content: '';
  display: block;
  width: 32px;
  height: 1px;
  background: var(--border-2);
  margin-inline-end: 4px;
}

.new-quiz__step-dot {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: var(--bg-surface);
  border: 1.5px solid var(--border-2);
  transition: background-color 150ms ease, border-color 150ms ease, box-shadow 150ms ease;
}

.new-quiz__step.is-active { color: var(--brand); }
.new-quiz__step.is-active .new-quiz__step-dot {
  background: var(--brand);
  border-color: var(--brand);
  box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
}
.new-quiz__step.is-done { color: var(--ink-3); }
.new-quiz__step.is-done .new-quiz__step-dot {
  background: var(--ink-3);
  border-color: var(--ink-3);
}

/* ── Shared body/heading ─────────────────────────────────────────────────── */

.new-quiz__step-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.new-quiz__heading {
  font-family: var(--f-display);
  font-size: 24px;
  font-weight: 500;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
  line-height: 1.2;
}

.new-quiz__desc {
  font-family: var(--f-sans);
  font-size: 13px;
  line-height: 1.55;
  color: var(--ink-3);
  margin: 0 0 4px;
  max-width: 60ch;
}

/* ── Mode select ─────────────────────────────────────────────────────────── */

.new-quiz__mode-select {
  align-items: center;
  text-align: center;
}

.new-quiz__mode-select .new-quiz__desc {
  margin: 0 auto 4px;
}

.new-quiz__mode-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  width: 100%;
  max-width: 560px;
  margin: 0 auto;
}

@media (max-width: 540px) {
  .new-quiz__mode-grid {
    grid-template-columns: 1fr;
  }
}

.new-quiz__mode-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  padding: 22px 20px;
  border-radius: var(--r-lg);
  border: 2px solid var(--border-2);
  background: var(--bg-surface);
  cursor: pointer;
  text-align: start;
  transition:
    border-color 150ms ease,
    background 150ms ease,
    box-shadow 150ms ease,
    transform 150ms ease;
}

.new-quiz__mode-card:hover {
  border-color: var(--brand);
  background: var(--brand-tint);
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.new-quiz__mode-card:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  border-color: var(--brand);
}

.new-quiz__mode-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: var(--r-md);
  background: var(--bg-subtle);
  color: var(--brand);
  margin-bottom: 4px;
  transition: background 150ms ease, color 150ms ease;
}

.new-quiz__mode-card:hover .new-quiz__mode-icon {
  background: var(--brand);
  color: #fff;
}

.new-quiz__mode-title {
  font-family: var(--f-display);
  font-size: 17px;
  font-weight: 600;
  color: var(--ink-1);
  letter-spacing: -0.01em;
}

.new-quiz__mode-desc {
  font-family: var(--f-sans);
  font-size: 12px;
  line-height: 1.6;
  color: var(--ink-3);
  flex: 1;
}

.new-quiz__mode-cta {
  font-family: var(--f-sans);
  font-size: 12px;
  font-weight: 600;
  color: var(--brand);
  margin-top: 4px;
}

/* ── Preset gallery header ───────────────────────────────────────────────── */

.new-quiz__preset-header {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* ── Preset name step ────────────────────────────────────────────────────── */

.new-quiz__preset-name {
  gap: 0;
}

.new-quiz__preset-preview {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  border-radius: var(--r-md);
  border: 1.5px solid var(--border-1);
  background: var(--bg-subtle);
}

.new-quiz__preset-preview-thumb {
  width: 80px;
  height: 52px;
  border-radius: var(--r-sm);
  overflow: hidden;
  flex-shrink: 0;
  background: var(--bg-subtle);
}

.new-quiz__preset-preview-thumb img {
  width: 100%;
  height: 100%;
  /* no-crop rule: full thumbnail must stay visible — letterbox on neutral bg, never cover */
  object-fit: contain;
  display: block;
}

.new-quiz__preset-preview-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.new-quiz__preset-preview-title {
  font-family: var(--f-display);
  font-size: 15px;
  font-weight: 600;
  color: var(--ink-1);
}

.new-quiz__preset-preview-badges {
  display: flex;
  align-items: center;
  gap: 5px;
  flex-wrap: wrap;
}

.pv-type-badge, .pv-tpl-badge, .pv-q-badge {
  display: inline-flex;
  padding: 2px 7px;
  border-radius: 4px;
  font-family: var(--f-mono);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.pv-type-badge { background: var(--brand-tint); color: var(--brand-hover); }
.pv-tpl-badge  { background: var(--bg-surface); border: 1px solid var(--border-2); color: var(--ink-3); }
.pv-q-badge    { color: var(--ink-4); background: transparent; }

/* ── Type grid (blank step 1) ────────────────────────────────────────────── */

.new-quiz__type-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

@media (max-width: 720px) { .new-quiz__type-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 480px) { .new-quiz__type-grid { grid-template-columns: 1fr; } }

.new-quiz__type-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-height: 120px;
  outline: none;
  border: 2px solid var(--border-1);
  background: var(--bg-surface);
  transition: border-color 150ms ease, background-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;
}

.new-quiz__type-card:hover { border-color: var(--border-3); }
.new-quiz__type-card:focus-visible { box-shadow: var(--shadow-focus); border-color: var(--brand); }

.new-quiz__type-card.is-selected {
  border-color: var(--brand);
  background: var(--brand-tint);
  box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.18), var(--shadow-md);
  transform: translateY(-2px);
}

.new-quiz__type-card.is-selected .new-quiz__type-icon { background: var(--brand); color: #fff; }
.new-quiz__type-card.is-selected .new-quiz__type-title { color: var(--brand-hover); }

.new-quiz__type-card.is-selected::before {
  content: '';
  position: absolute;
  top: 10px; inset-inline-start: 10px;
  width: 22px; height: 22px;
  border-radius: 50%;
  background: var(--brand) url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 8.5l3 3 6-7' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' fill='none'/%3E%3C/svg%3E") center / 14px no-repeat;
  box-shadow: 0 0 0 3px var(--brand-tint), var(--shadow-sm);
}

.new-quiz__type-card.is-pro { background: linear-gradient(180deg, var(--bg-surface), var(--bg-subtle)); }

.new-quiz__type-badge { position: absolute; top: 10px; inset-inline-end: 10px; }

.new-quiz__type-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px; height: 34px;
  border-radius: var(--r-md);
  background: var(--bg-subtle);
  color: var(--brand);
}

.new-quiz__type-icon :deep(svg) { width: 18px; height: 18px; }
.new-quiz__type-card.is-pro .new-quiz__type-icon { color: var(--accent); }

.new-quiz__type-title {
  font-family: var(--f-display);
  font-size: 15px;
  font-weight: 500;
  color: var(--ink-1);
  letter-spacing: -0.005em;
}

.new-quiz__type-desc {
  font-family: var(--f-sans);
  font-size: 12px;
  line-height: 1.5;
  color: var(--ink-3);
}

/* ── Template grid (blank step 2) ────────────────────────────────────────── */

.new-quiz__template-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

@media (max-width: 720px) { .new-quiz__template-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.new-quiz__template-card {
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 8px;
  outline: none;
  border-radius: var(--r-md);
}

.new-quiz__template-card:focus-visible { box-shadow: var(--shadow-focus); }

.new-quiz__template-thumb {
  position: relative;
  aspect-ratio: 640 / 400;
  border-radius: var(--r-md);
  border: 2px solid var(--border-1);
  overflow: hidden;
  background: var(--bg-subtle);
  transition: border-color 150ms ease, box-shadow 150ms ease;
}

.new-quiz__template-thumb img {
  width: 100%; height: 100%;
  object-fit: contain;
  display: block;
  background: var(--bg-surface);
}

.new-quiz__template-card.is-selected .new-quiz__template-thumb {
  border-color: var(--brand);
  box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.22), var(--shadow-md);
  transform: translateY(-2px);
}

.new-quiz__template-card.is-selected .new-quiz__template-name {
  color: var(--brand-hover);
  font-weight: 600;
}

.new-quiz__template-check {
  position: absolute;
  top: 6px; inset-inline-end: 6px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 7px;
  background: var(--brand);
  color: #fff;
  border-radius: var(--r-xs);
  font-family: var(--f-mono);
  font-size: 9px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.new-quiz__template-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.new-quiz__template-name {
  font-family: var(--f-sans);
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-1);
}

.new-quiz__templates-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 48px;
  color: var(--ink-3);
  font-size: 13px;
}

.new-quiz__spinner {
  display: inline-block;
  width: 14px; height: 14px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: quizably-new-quiz-spin 700ms linear infinite;
}

@keyframes quizably-new-quiz-spin { to { transform: rotate(360deg); } }

/* ── Name step fields ────────────────────────────────────────────────────── */

.new-quiz__fields {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* ── Footer centering ────────────────────────────────────────────────────── */

:deep(.quizably-modal__footer) { justify-content: center; }

/* ── Reduced motion ──────────────────────────────────────────────────────── */

@media (prefers-reduced-motion: reduce) {
  .new-quiz__type-card,
  .new-quiz__template-card,
  .new-quiz__mode-card { transition: none; }
  .new-quiz__type-card.is-selected,
  .new-quiz__template-card.is-selected,
  .new-quiz__mode-card:hover { transform: none; }
  .new-quiz__spinner { animation-duration: 0ms; }
}
</style>
