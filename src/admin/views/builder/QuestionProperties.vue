<template>
  <div class="qprops">
    <div class="qprops__scroll">
      <section class="qprops-section">
        <header class="qprops-section__head">
          <span class="qprops-section__title">{{ __('Image') }}</span>
        </header>
        <div class="qprops-section__body">
          <MediaPicker
            :model-value="question.media_url || ''"
            label=""
            helper-text=""
            :button-text="__('Upload image')"
            :replace-text="__('Replace image')"
            :remove-text="__('Delete image')"
            @update:model-value="onMediaChange"
          />

          <div
            v-if="question.media_url"
            class="qprops-fit"
          >
            <span class="qprops-fit__label">{{ __('Image fit') }}</span>
            <div
              class="qprops-fit__seg"
              role="radiogroup"
              :aria-label="__('Image fit')"
            >
              <button
                v-for="opt in fitOptions"
                :key="opt.value"
                type="button"
                role="radio"
                :aria-checked="imageFit === opt.value"
                :class="['qprops-fit__opt', { 'is-active': imageFit === opt.value }]"
                @click="onFitChange(opt.value)"
              >
                {{ opt.label }}
              </button>
            </div>
            <p class="qprops-fit__hint">
              {{ activeFitHint }}
            </p>
          </div>

          <div
            v-if="question.media_url"
            class="qprops-height"
          >
            <div class="qprops-height__label">
              <span>{{ __('Height') }}</span>
              <span class="qprops-height__val">{{ imageHeight }} px</span>
            </div>
            <input
              type="range"
              min="80"
              max="800"
              step="10"
              class="qprops-height__range"
              :value="imageHeight"
              :aria-label="__('Image height in pixels')"
              @input="onImageHeightChange($event.target.value)"
            >
          </div>
        </div>
      </section>

      <!-- ── Alignment ── -->
      <section class="qprops-section">
        <header class="qprops-section__head">
          <span class="qprops-section__title">{{ __('Alignment') }}</span>
        </header>
        <div class="qprops-section__body">
          <div
            class="qprops-align"
            role="radiogroup"
            :aria-label="__('Content alignment')"
          >
            <button
              v-for="opt in ALIGN_OPTIONS"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="questionAlign === opt.value"
              :class="['qprops-align__opt', { 'is-active': questionAlign === opt.value }]"
              :aria-label="opt.label"
              :title="opt.label"
              @click="onAlignChange(opt.value)"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span
                class="qprops-align__icon"
                aria-hidden="true"
                v-html="opt.icon"
              />
            </button>
          </div>
        </div>
      </section>

      <!-- ── Vertical alignment ── -->
      <section class="qprops-section">
        <header class="qprops-section__head">
          <span class="qprops-section__title">{{ __('Vertical alignment') }}</span>
        </header>
        <div class="qprops-section__body">
          <div
            class="qprops-align"
            role="radiogroup"
            :aria-label="__('Vertical alignment')"
          >
            <button
              v-for="opt in VALIGN_OPTIONS"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="questionValign === opt.value"
              :class="['qprops-align__opt', { 'is-active': questionValign === opt.value }]"
              :aria-label="opt.label"
              :title="opt.label"
              @click="onValignChange(opt.value)"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span
                class="qprops-align__icon"
                aria-hidden="true"
                v-html="opt.icon"
              />
            </button>
          </div>
        </div>
      </section>

      <!-- ── Slider settings ── shown only for slider type -->
      <section
        v-if="question.type === 'slider'"
        class="qprops-section"
      >
        <header class="qprops-section__head">
          <span class="qprops-section__title">{{ __('Slider settings') }}</span>
        </header>
        <div class="qprops-section__body">
          <div class="qprops-row qprops-row--inline">
            <div class="qprops-row__label">
              <span>{{ __('Minimum') }}</span>
            </div>
            <div class="qprops-row__numfield">
              <Input
                :model-value="sliderSettings.min"
                type="number"
                placeholder="0"
                @update:model-value="onSliderSettingChange('min', $event)"
              />
            </div>
          </div>
          <div class="qprops-row qprops-row--inline">
            <div class="qprops-row__label">
              <span>{{ __('Maximum') }}</span>
            </div>
            <div class="qprops-row__numfield">
              <Input
                :model-value="sliderSettings.max"
                type="number"
                placeholder="100"
                @update:model-value="onSliderSettingChange('max', $event)"
              />
            </div>
          </div>
          <div class="qprops-row qprops-row--inline">
            <div class="qprops-row__label">
              <span>{{ __('Step') }}</span>
            </div>
            <div class="qprops-row__numfield">
              <Input
                :model-value="sliderSettings.step"
                type="number"
                placeholder="1"
                @update:model-value="onSliderSettingChange('step', $event)"
              />
            </div>
          </div>
          <div class="qprops-row">
            <div class="qprops-row__label">
              <span>{{ __('Show value') }}</span>
              <Tooltip :label="__('Display the numeric value above the slider thumb while answering.')">
                <span
                  class="quizably-help-dot"
                  aria-hidden="true"
                >?</span>
              </Tooltip>
            </div>
            <Toggle
              :model-value="sliderSettings.show_value"
              size="sm"
              @update:model-value="onSliderSettingChange('show_value', $event)"
            />
          </div>
        </div>
      </section>

      <!-- ── Short text settings ── shown only for short_text type -->
      <section
        v-if="question.type === 'short_text'"
        class="qprops-section"
      >
        <header class="qprops-section__head">
          <span class="qprops-section__title">{{ __('Text field settings') }}</span>
        </header>
        <div class="qprops-section__body">
          <div class="qprops-field">
            <label class="qprops-field__label">{{ __('Placeholder text') }}</label>
            <Input
              :model-value="textSettings.placeholder"
              :placeholder="__('Your answer…')"
              @update:model-value="onTextSettingChange('placeholder', $event)"
            />
          </div>
          <div class="qprops-row qprops-row--inline">
            <div class="qprops-row__label">
              <span>{{ __('Max characters') }}</span>
            </div>
            <div class="qprops-row__numfield">
              <Input
                :model-value="textSettings.max_length"
                type="number"
                placeholder="500"
                @update:model-value="onTextSettingChange('max_length', $event)"
              />
            </div>
          </div>
        </div>
      </section>

      <!-- ── Rating settings ── shown only for rating type -->
      <section
        v-if="question.type === 'rating'"
        class="qprops-section"
      >
        <header class="qprops-section__head">
          <span class="qprops-section__title">{{ __('Rating settings') }}</span>
        </header>
        <div class="qprops-section__body">
          <div class="qprops-field">
            <label class="qprops-field__label">{{ __('Max rating') }}</label>
            <Select
              :model-value="String(ratingSettings.max)"
              :options="[{ value: '5', label: __('5 stars') }, { value: '10', label: __('10 stars') }]"
              @update:model-value="onRatingSettingChange('max', $event)"
            />
          </div>
          <div class="qprops-field">
            <label class="qprops-field__label">{{ __('Display style') }}</label>
            <Select
              :model-value="ratingSettings.style"
              :options="[
                { value: 'stars', label: __('Stars (★)') },
                { value: 'emoji', label: __('Emoji (😊)') },
                { value: 'numbers', label: __('Numbers (1 2 3)') },
              ]"
              @update:model-value="onRatingSettingChange('style', $event)"
            />
          </div>
        </div>
      </section>

      <section class="qprops-section">
        <header class="qprops-section__head">
          <span class="qprops-section__title">{{ __('Display & rules') }}</span>
        </header>
        <div class="qprops-section__body">
          <div class="qprops-row">
            <div class="qprops-row__label">
              <span>{{ __('Required answer') }}</span>
              <Tooltip :label="__('User must answer to advance.')">
                <span
                  class="quizably-help-dot"
                  aria-hidden="true"
                >?</span>
              </Tooltip>
            </div>
            <Toggle
              :model-value="isRequired"
              size="sm"
              data-testid="qe-required-toggle"
              @update:model-value="onRequiredChange"
            />
          </div>

          <!-- Randomize answers — free. -->
          <div class="qprops-row">
            <div class="qprops-row__label">
              <span>{{ __('Randomize answers') }}</span>
              <Tooltip :label="__('Shuffle this question\'s answers per visitor. The order is stable for one session so back/forward keeps the same layout.')">
                <span
                  class="quizably-help-dot"
                  aria-hidden="true"
                >?</span>
              </Tooltip>
            </div>
            <Toggle
              :model-value="randomizeAnswers"
              size="sm"
              data-testid="qe-randomize-toggle"
              @update:model-value="onRandomizeChange"
            />
          </div>

          <!-- Per-question timer — free. Toggle + seconds input. -->
          <div class="qprops-row">
            <div class="qprops-row__label">
              <span>{{ __('Per-question timer') }}</span>
              <Tooltip :label="__('Counts down for this question only. Auto-advances when it hits zero.')">
                <span
                  class="quizably-help-dot"
                  aria-hidden="true"
                >?</span>
              </Tooltip>
            </div>
            <Toggle
              :model-value="timerEnabled"
              size="sm"
              data-testid="qe-timer-toggle"
              @update:model-value="onTimerEnabledChange"
            />
          </div>
          <div
            v-if="timerEnabled"
            class="qprops-row qprops-row--inline"
          >
            <div class="qprops-row__label">
              <span class="qprops-row__sublabel">{{ __('Seconds') }}</span>
            </div>
            <div class="qprops-row__seconds">
              <Input
                :model-value="timerSeconds"
                type="number"
                placeholder="30"
                data-testid="qe-timer-seconds"
                @update:model-value="onTimerSecondsChange"
              />
            </div>
          </div>

          <!-- Branching / skip-logic lives in the Logic tab — link there
               so users author all branching in one place. The Logic tab is
               Pro-tier, so the pointer is absent unless Pro is active or
               promoted. -->
          <p
            v-if="proTierVisible"
            class="qprops-logic-hint"
          >
            <span>{{ __('Need this question to skip ahead based on the answer?') }}</span>
            <a
              href="#"
              class="qprops-logic-hint__link"
              data-testid="qe-logic-tab-link"
              @click.prevent="onGoToLogicTab"
            >
              {{ __('Open the Logic tab →') }}
            </a>
          </p>
        </div>
      </section>

      <!-- ── Background ── free. -->
      <section class="qprops-section">
        <header class="qprops-section__head">
          <span class="qprops-section__title">{{ __('Background') }}</span>
        </header>
        <div class="qprops-section__body">
          <div class="qprops-row">
            <div class="qprops-row__label">
              <span>{{ __('Custom background') }}</span>
            </div>
            <Toggle
              :model-value="bgEnabled"
              size="sm"
              data-testid="question-bg-toggle"
              @update:model-value="onBgToggle"
            />
          </div>
          <template v-if="bgEnabled">
            <MediaPicker
              :model-value="questionBg.background_image || ''"
              label=""
              helper-text=""
              :button-text="__('Upload background')"
              :replace-text="__('Replace background')"
              :remove-text="__('Remove background')"
              @update:model-value="onBgImageInput"
            />
            <div class="qprops-overlay">
              <label class="qprops-overlay__label">
                {{ __('Overlay opacity') }}
                <span class="qprops-overlay__pct">{{ bgOpacity }}%</span>
              </label>
              <input
                type="range"
                min="0"
                max="100"
                step="1"
                class="qprops-overlay__range"
                :value="bgOpacity"
                data-testid="q-bg-opacity"
                :aria-label="__('Background overlay opacity')"
                @input="(e) => onBgOpacityInput(Number(e.target.value))"
              >
            </div>
          </template>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Input, MediaPicker, Select, Toggle, Tooltip, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { proFeaturesVisible } from '@admin/api/pro.js';
import { __ } from '@shared/i18n';

const props = defineProps({
  question: { type: Object, required: true },
  quizType: { type: String, default: 'personality' },
  results: { type: Array, default: () => [] },
});

const store = useQuizBuilderStore();
const toast = useToast();
const route = useRoute();
const router = useRouter();

const isRequired = computed(() => {
  const s = props.question.settings || {};
  return s.required !== false;
});

// ── Alignment ─────────────────────────────────────────────────────────────────
const ALIGN_OPTIONS = [
  { value: 'left',   label: __('Left'),   icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="2" y1="8" x2="10" y2="8"/><line x1="2" y1="12" x2="12" y2="12"/></svg>` },
  { value: 'center', label: __('Center'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="4" y1="8" x2="12" y2="8"/><line x1="3" y1="12" x2="13" y2="12"/></svg>` },
  { value: 'right',  label: __('Right'),  icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="4" x2="14" y2="4"/><line x1="6" y1="8" x2="14" y2="8"/><line x1="4" y1="12" x2="14" y2="12"/></svg>` },
];

const questionAlign = computed(() => {
  const v = props.question.settings?.align;
  return v === 'center' || v === 'right' ? v : 'left';
});

async function onAlignChange(next) {
  if (next === questionAlign.value) return;
  const settings = { ...(props.question.settings || {}), align: next };
  try {
    await store.updateQuestion(props.question.id, { settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save alignment'), message: e.message });
  }
}

// ── Vertical alignment ─────────────────────────────────────────────────────────
const VALIGN_OPTIONS = [
  { value: 'top',    label: __('Top'),    icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="2.5" x2="14" y2="2.5"/><line x1="2" y1="6.5" x2="12" y2="6.5"/><line x1="2" y1="10" x2="9" y2="10"/></svg>` },
  { value: 'center', label: __('Center'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="5.25" x2="12" y2="5.25"/><line x1="2" y1="8.75" x2="9" y2="8.75"/></svg>` },
  { value: 'bottom', label: __('Bottom'), icon: `<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="2" y1="6" x2="9" y2="6"/><line x1="2" y1="9.5" x2="12" y2="9.5"/><line x1="2" y1="13.5" x2="14" y2="13.5"/></svg>` },
];

const questionValign = computed(() => {
  const v = props.question.settings?.valign;
  return v === 'center' || v === 'bottom' ? v : 'top';
});

async function onValignChange(next) {
  if (next === questionValign.value) return;
  const settings = { ...(props.question.settings || {}), valign: next };
  try {
    await store.updateQuestion(props.question.id, { settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save vertical alignment'), message: e.message });
  }
}

const fitOptions = [
  { value: 'contain', label: __('Contain') },
  { value: 'cover', label: __('Cover') },
  { value: 'repeat', label: __('Repeat') },
];

const FIT_HINTS = {
  contain: __('Whole image visible. No cropping.'),
  cover: __('Fills the slot. May crop your image.'),
  repeat: __('Tiles at native size — best for patterns.'),
};

const imageFit = computed(() => {
  const s = props.question.settings || {};
  const v = s.image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

const activeFitHint = computed(() => FIT_HINTS[imageFit.value]);

async function onFitChange(next) {
  if (next === imageFit.value) return;
  const settings = { ...(props.question.settings || {}), image_fit: next };
  try {
    await store.updateQuestion(props.question.id, { settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save'), message: e.message });
  }
}

// ── Image height ──────────────────────────────────────────────────────────────
const DEFAULT_IMAGE_HEIGHT = 300;

const imageHeight = computed(() => {
  const h = Number(props.question.settings?.image_height);
  return Number.isFinite(h) && h > 0 ? h : DEFAULT_IMAGE_HEIGHT;
});

let heightDebounce = null;
function onImageHeightChange(rawValue) {
  const parsed = Math.max(80, Math.min(800, Math.floor(Number(rawValue) || DEFAULT_IMAGE_HEIGHT)));
  if (heightDebounce) clearTimeout(heightDebounce);
  heightDebounce = setTimeout(async () => {
    const settings = { ...(props.question.settings || {}), image_height: parsed };
    try {
      await store.updateQuestion(props.question.id, { settings });
    } catch (e) {
      toast.push({ variant: 'danger', title: __('Could not save'), message: e.message });
    }
  }, 400);
}

async function onMediaChange(next) {
  const url = (next ?? '').toString();
  if (url === (props.question.media_url ?? '')) return;
  try {
    await store.updateQuestion(props.question.id, { media_url: url });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save image'), message: e.message });
  }
}

async function onRequiredChange(next) {
  // Write `required` in both places:
  //  - settings JSON (boolean): what the frontend reads via question.settings?.required
  //  - top-level column (integer): the PHP DB column; QuestionController hydrates it as int
  const settings = { ...(props.question.settings || {}), required: Boolean(next) };
  try {
    await store.updateQuestion(props.question.id, { required: Boolean(next), settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save setting'), message: e.message });
  }
}

// proTierVisible: Pro active OR promoted -> Pro-tier sections/links (the Logic
// tab pointer) are listed. False in the default free plugin (api/pro.js).
const proTierVisible = computed(() => proFeaturesVisible());

// Randomize answers — Pro setting persisted at question.settings.randomize_answers.
const randomizeAnswers = computed(() => {
  return Boolean(props.question?.settings?.randomize_answers);
});

async function onRandomizeChange(next) {
  const settings = { ...(props.question.settings || {}), randomize_answers: Boolean(next) };
  try {
    await store.updateQuestion(props.question.id, { settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save setting'), message: e.message });
  }
}

// Per-question timer — Pro setting persisted at question.settings.timer = { enabled, seconds }.
const TIMER_DEFAULT_SECONDS = 30;

const timer = computed(() => {
  const t = props.question?.settings?.timer;
  return t && typeof t === 'object' ? t : {};
});

const timerEnabled = computed(() => Boolean(timer.value.enabled));

const timerSeconds = computed(() => {
  const n = Number(timer.value.seconds);
  return Number.isFinite(n) && n > 0 ? n : TIMER_DEFAULT_SECONDS;
});

async function onTimerEnabledChange(next) {
  const enabled = Boolean(next);
  const seconds = Number.isFinite(Number(timer.value.seconds)) && Number(timer.value.seconds) > 0
    ? Number(timer.value.seconds)
    : TIMER_DEFAULT_SECONDS;
  const settings = {
    ...(props.question.settings || {}),
    timer: { enabled, seconds },
  };
  try {
    await store.updateQuestion(props.question.id, { settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save setting'), message: e.message });
  }
}

let timerSecondsDebounce = null;
function onTimerSecondsChange(next) {
  const parsed = Math.max(1, Math.min(3600, Math.floor(Number(next) || TIMER_DEFAULT_SECONDS)));
  if (timerSecondsDebounce) clearTimeout(timerSecondsDebounce);
  timerSecondsDebounce = setTimeout(async () => {
    const settings = {
      ...(props.question.settings || {}),
      timer: { enabled: timerEnabled.value, seconds: parsed },
    };
    try {
      await store.updateQuestion(props.question.id, { settings });
    } catch (e) {
      toast.push({ variant: 'danger', title: __('Could not save setting'), message: e.message });
    }
  }, 400);
}

// ── Slider settings ───────────────────────────────────────────────────────────
const sliderSettings = computed(() => {
  const s = props.question.settings?.slider || {};
  return {
    min: Number.isFinite(Number(s.min)) ? Number(s.min) : 0,
    max: Number.isFinite(Number(s.max)) ? Number(s.max) : 100,
    step: Number.isFinite(Number(s.step)) && Number(s.step) > 0 ? Number(s.step) : 1,
    show_value: Boolean(s.show_value),
  };
});

let sliderDebounce = null;
function onSliderSettingChange(key, rawValue) {
  const parsed = key === 'show_value' ? Boolean(rawValue) : Number(rawValue);
  if (sliderDebounce) clearTimeout(sliderDebounce);
  sliderDebounce = setTimeout(async () => {
    const slider = { ...sliderSettings.value, [key]: parsed };
    const settings = { ...(props.question.settings || {}), slider };
    try {
      await store.updateQuestion(props.question.id, { settings });
    } catch (e) {
      toast.push({ variant: 'danger', title: __('Could not save slider setting'), message: e.message });
    }
  }, 400);
}

// ── Text field settings ───────────────────────────────────────────────────────
const textSettings = computed(() => {
  const s = props.question.settings?.text || {};
  return {
    placeholder: s.placeholder ?? '',
    max_length: Number.isFinite(Number(s.max_length)) ? Number(s.max_length) : 500,
  };
});

let textDebounce = null;
function onTextSettingChange(key, rawValue) {
  const parsed = key === 'max_length' ? Number(rawValue) : String(rawValue);
  if (textDebounce) clearTimeout(textDebounce);
  textDebounce = setTimeout(async () => {
    const text = { ...textSettings.value, [key]: parsed };
    const settings = { ...(props.question.settings || {}), text };
    try {
      await store.updateQuestion(props.question.id, { settings });
    } catch (e) {
      toast.push({ variant: 'danger', title: __('Could not save text setting'), message: e.message });
    }
  }, 400);
}

// ── Rating settings ───────────────────────────────────────────────────────────
const ratingSettings = computed(() => {
  const s = props.question.settings?.rating || {};
  const max = Number(s.max);
  return {
    max: Number.isFinite(max) && max > 0 ? max : 5,
    style: s.style || 'stars',
  };
});

async function onRatingSettingChange(key, rawValue) {
  const parsed = key === 'max' ? Number(rawValue) : String(rawValue);
  const rating = { ...ratingSettings.value, [key]: parsed };
  const settings = { ...(props.question.settings || {}), rating };
  try {
    await store.updateQuestion(props.question.id, { settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save rating setting'), message: e.message });
  }
}

// ── Per-question background ───────────────────────────────────────────────────
// Stored at question.settings.background = { enabled, background_image, overlay_color, overlay_opacity }
// Quiz.vue reads q.settings?.background and passes it through resolveBgStyle().
const questionBg = computed(() => {
  const bg = props.question.settings?.background;
  return bg && typeof bg === 'object' ? bg : {};
});

const bgEnabled = computed(() => Boolean(questionBg.value.enabled));
const bgOpacity = computed(() => {
  const v = Number(questionBg.value.overlay_opacity);
  return Number.isFinite(v) ? Math.max(0, Math.min(100, v)) : 0;
});

async function onBgToggle(next) {
  const bg = { ...questionBg.value, enabled: Boolean(next) };
  const settings = { ...(props.question.settings || {}), background: bg };
  try {
    await store.updateQuestion(props.question.id, { settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save background'), message: e.message });
  }
}

async function onBgImageInput(url) {
  // Persist immediately so a page reload keeps the image (same pattern as intro cover).
  const bg = { ...questionBg.value, background_image: url ?? '' };
  const settings = { ...(props.question.settings || {}), background: bg };
  try {
    await store.updateQuestion(props.question.id, { settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save background image'), message: e.message });
  }
}

let bgOpacityDebounce = null;
function onBgOpacityInput(value) {
  const clamped = Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
  if (bgOpacityDebounce) clearTimeout(bgOpacityDebounce);
  bgOpacityDebounce = setTimeout(async () => {
    const bg = { ...questionBg.value, overlay_opacity: clamped };
    const settings = { ...(props.question.settings || {}), background: bg };
    try {
      await store.updateQuestion(props.question.id, { settings });
    } catch (e) {
      toast.push({ variant: 'danger', title: __('Could not save opacity'), message: e.message });
    }
  }, 300);
}

function onGoToLogicTab() {
  const quizId = Number(route.params?.id);
  if (Number.isFinite(quizId) && quizId > 0) {
    router.push(`/quiz/${quizId}/logic`);
  }
}

</script>

<style scoped>
.qprops {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--bg-surface);
  min-height: 0;
}

.qprops__scroll {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
  padding-bottom: 32px;
}

/* Flat, always-visible sections — clear separation, no accordion. The
   strong divider between sections doubles as the bottom edge for the
   section above. The mono uppercase title makes the section purpose
   instantly readable. */
.qprops-section + .qprops-section {
  border-top: 1px solid var(--border-1);
}

.qprops-section__head {
  padding: 18px 16px 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.qprops-section__title {
  font-family: var(--f-sans);
  font-size: 14px;
  letter-spacing: 0;
  text-transform: none;
  color: var(--ink-1);
  font-weight: 600;
}

.qprops-section__body {
  padding: 4px 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.qprops-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 8px 10px;
  background: var(--bg-canvas);
  border-radius: var(--r-sm);
  font-size: 13px;
  color: var(--ink-2);
}

.qprops-row__sublabel {
  font-size: 12px;
  color: var(--ink-3);
}

.qprops-row__seconds {
  width: 88px;
  flex: 0 0 auto;
}

.qprops-row__seconds :deep(.quizably-input__control) {
  text-align: end;
  padding: 6px 10px;
  font-variant-numeric: tabular-nums;
}

.qprops-logic-hint {
  margin: 6px 2px 0;
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 6px;
  font-size: 12px;
  color: var(--ink-3);
  line-height: 1.5;
}

.qprops-logic-hint__link {
  color: var(--brand);
  text-decoration: none;
  font-weight: 500;
}

.qprops-logic-hint__link:hover,
.qprops-logic-hint__link:focus-visible {
  text-decoration: underline;
}

.qprops-row__label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
}


.qprops-fit {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.qprops-fit__label {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-3);
  font-weight: 500;
}

.qprops-fit__seg {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.qprops-fit__opt {
  padding: 7px 8px;
  border: 0;
  border-radius: var(--r-xs);
  background: transparent;
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-3);
  cursor: pointer;
  transition: background 150ms, color 150ms;
}

.qprops-fit__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.qprops-fit__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.qprops-fit__hint {
  margin: 0;
  font-size: 11.5px;
  color: var(--ink-3);
  line-height: 1.4;
}

.qprops-hint {
  font-size: 11.5px;
  color: var(--ink-3);
  margin: 2px 0 0;
  line-height: 1.45;
}

.qprops-row__numfield {
  width: 88px;
  flex: 0 0 auto;
}

.qprops-row__numfield :deep(.quizably-input__control) {
  text-align: end;
  padding: 6px 10px;
  font-variant-numeric: tabular-nums;
}

/* ── Image height slider ─────────────────────────────────────────────────────── */
.qprops-height {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
}

.qprops-height__label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
}

.qprops-height__val {
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-3);
}

.qprops-height__range {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 4px;
  background: var(--border-2);
  border-radius: var(--r-pill);
  outline: none;
  cursor: pointer;
}

.qprops-height__range::-webkit-slider-thumb {
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

.qprops-height__range::-moz-range-thumb {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--brand);
  cursor: pointer;
  border: 2px solid #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.18);
}

.qprops-field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.qprops-field__label {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-3);
  font-weight: 500;
}

.qprops-overlay {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.qprops-overlay__label {
  display: flex;
  justify-content: space-between;
  font-size: 11.5px;
  font-weight: 500;
  color: var(--ink-3);
}

.qprops-overlay__pct {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-2);
}

.qprops-overlay__range {
  width: 100%;
  accent-color: var(--brand);
}

:deep(.quizably-help-dot) {
  display: inline-grid;
  place-items: center;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--bg-subtle, #f1f5f9);
  color: var(--ink-3, #64748b);
  font-size: 10px;
  font-weight: 600;
  cursor: help;
  user-select: none;
  border: 1px solid var(--border-1, #e2e8f0);
  margin-inline-start: 4px;
  vertical-align: middle;
}

/* ── Alignment segmented control ─────────────────────────────────────────────── */
.qprops-align {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.qprops-align__opt {
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

.qprops-align__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.qprops-align__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.qprops-align__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
}

.qprops-align__icon :deep(svg) {
  width: 16px;
  height: 16px;
}
</style>
