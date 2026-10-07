<template>
  <div class="qp" :style="rootVars">

    <div class="qp-head">
      <div class="qp-crumb">
        <span class="chip">Q{{ position }}</span>
        {{ questionOfTotal }}
        <span class="qp-tpl-badge">{{ preview.templateSlug }}</span>
      </div>
      <div class="qp-head__actions">
        <div class="qp-type">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <circle cx="12" cy="12" r="10" />
            <circle cx="12" cy="12" r="3" />
          </svg>
          {{ typeLabel }}
        </div>
        <button
          type="button"
          class="qp-properties-btn"
          :aria-label="__('Open question properties')"
          @click="emit('open-properties')"
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <line x1="4" y1="6" x2="20" y2="6" />
            <line x1="4" y1="12" x2="20" y2="12" />
            <line x1="4" y1="18" x2="14" y2="18" />
          </svg>
          <span>Properties</span>
        </button>
      </div>
    </div>

    <!-- Magazine: editorial hero strip — outside card so it spans the full 760px preview width -->
    <div
      v-if="preview.templateSlug === 'magazine' && !isSplitscreen"
      class="qp-mag-hero"
      aria-hidden="true"
    >
      <div class="qp-mag-hero-meta">
        <span class="qp-mag-hero-issue">Q{{ position }} · {{ total }} questions</span>
      </div>
    </div>

    <div
      class="qp-card"
      :class="[...cardClass, `is-align-${questionAlign}`]"
      :style="cardStyle"
    >
      <!-- ── Standard (non-splitscreen) layout ────────────────────────────── -->
      <template v-if="!isSplitscreen">

        <!-- Gamified: gradient header with level + pips + score badge -->
        <div
          v-if="preview.templateSlug === 'gamified'"
          class="qp-game-head"
          aria-hidden="true"
        >
          <div class="qp-game-lvl">
            <span class="qp-game-lvl-num">{{ position }}</span>
            <span class="qp-game-lvl-label">{{ __('Level') }}</span>
          </div>
          <div class="qp-game-pips">
            <span
              v-for="i in total"
              :key="i"
              :class="['qp-game-pip', { 'is-done': i <= position, 'is-current': i === position }]"
            />
          </div>
          <div class="qp-game-score">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <span>0</span>
          </div>
        </div>

        <!-- Classic: thin progress bar + accent stepper — key identity markers -->
        <template v-if="preview.templateSlug === 'classic'">
          <div class="qp-classic-bar" aria-hidden="true">
            <div
              class="qp-classic-bar-fill"
              :style="{ width: `${Math.max(5, Math.round((position / total) * 100))}%` }"
            />
          </div>
          <div class="qp-classic-stepper">Question {{ position }} of {{ total }}</div>
        </template>

        <!-- Cardstack: accent count badge — key identity marker -->
        <div
          v-if="preview.templateSlug === 'cardstack'"
          class="qp-stack-count"
          aria-hidden="true"
        >{{ position }}/{{ total }}</div>

        <!-- Conversational: avatar circle + step counter header, then question as chat bubble -->
        <div
          v-if="preview.templateSlug === 'conversational'"
          class="qp-conv-head"
          aria-hidden="true"
        >
          <div class="qp-conv-avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
            </svg>
          </div>
          <span class="qp-conv-step">{{ positionOfTotal }}</span>
        </div>

        <div
          v-if="bg.overlayOpacity > 0"
          class="qp-card-overlay"
          :style="cardOverlayStyle"
          aria-hidden="true"
        />

        <!-- Per-question timer — static bar shown when timer is enabled in Properties -->
        <div
          v-if="timerEnabled"
          class="qp-timer"
          :aria-label="timerAriaLabel"
        >
          <div class="qp-timer-track">
            <div class="qp-timer-fill" />
          </div>
          <span class="qp-timer-count">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" aria-hidden="true">
              <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
            </svg>
            {{ timerSeconds }}s
          </span>
        </div>

        <!-- Title contenteditable with floating toolbar -->
        <div
          ref="titleEl"
          class="qp-title qp-title-ce"
          contenteditable="true"
          :data-placeholder="__('What do you want to ask?')"
          :aria-label="titleAriaLabel"
          @input="onTitleInput"
          @blur="flushTitle"
          @keydown.enter.prevent="flushTitle"
          @paste="onTitlePaste"
        />

        <Teleport to="body">
          <div
            v-if="titleBar.visible"
            class="qp-title-bar"
            :style="titleBar.posStyle"
            @mousedown.prevent
          >
            <!-- Alignment -->
            <button type="button" :class="['qp-title-bar__btn', { 'is-active': titleBar.align === 'left' }]" :title="__('Align left')" @click="applyTitleAlign('left')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
            </button>
            <button type="button" :class="['qp-title-bar__btn', { 'is-active': titleBar.align === 'center' }]" :title="__('Align center')" @click="applyTitleAlign('center')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            </button>
            <button type="button" :class="['qp-title-bar__btn', { 'is-active': titleBar.align === 'right' }]" :title="__('Align right')" @click="applyTitleAlign('right')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="qp-title-bar__sep" />
            <!-- Bold / Italic / Underline -->
            <button type="button" :class="['qp-title-bar__btn', { 'is-active': titleBar.bold }]" :title="__('Bold')" @click="execTitle('bold')"><b>B</b></button>
            <button type="button" :class="['qp-title-bar__btn', { 'is-active': titleBar.italic }]" :title="__('Italic')" @click="execTitle('italic')"><i>I</i></button>
            <button type="button" :class="['qp-title-bar__btn', { 'is-active': titleBar.underline }]" :title="__('Underline')" @click="execTitle('underline')"><u>U</u></button>
            <div class="qp-title-bar__sep" />
            <!-- Color picker -->
            <label class="qp-title-bar__color" :title="__('Text color')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M9 7l6 10H3z"/><line x1="20" y1="17" x2="20" y2="22"/><line x1="17.5" y1="19.5" x2="22.5" y2="19.5"/></svg>
              <span class="qp-title-bar__color-swatch" :style="{ background: titleBar.color }" />
              <input ref="titleColorInputRef" type="color" class="qp-title-bar__color-input" :value="titleBar.color" @input="applyTitleColor($event.target.value)">
            </label>
          </div>
        </Teleport>

        <!-- Description editor (RichTextEditor with toolbarless bubble: align + B/I/U + color) -->
        <RichTextEditor
          v-model="draftDescription"
          class="qp-desc-rte"
          :toolbarless="true"
          :placeholder="__('Add a description or hint…')"
          :aria-label="descriptionAriaLabel"
          @update:model-value="onDescriptionUpdate"
        />

        <!-- Image preview (CLAUDE.md: always object-fit contain, never crop).
             Clicking the placeholder — or the replace button on an existing image —
             opens the WP media library directly. No sidebar required. -->
        <div
          v-if="question.media_url"
          :class="['qp-image', `qp-image--${imageFit}`]"
          :style="imageHeightStyle"
        >
          <div
            v-if="imageFit === 'repeat'"
            class="qp-image__tile"
            :style="{ backgroundImage: `url(${question.media_url})` }"
            role="img"
            :aria-label="question.title || __('Question image')"
          />
          <!-- Cover: background-size cover fills the slot (CLAUDE.md: no object-fit:cover on <img>) -->
          <div
            v-else-if="imageFit === 'cover'"
            class="qp-image__cover-bg"
            :style="{ backgroundImage: `url(${question.media_url})` }"
            role="img"
            :aria-label="question.title || __('Question image')"
          />
          <img
            v-else
            :src="question.media_url"
            :alt="question.title || __('Question image')"
          >
          <!-- Replace / remove overlay — revealed on hover -->
          <div class="qp-image__overlay" aria-hidden="true">
            <button type="button" class="qp-image__overlay-btn" @click.stop="openMediaPicker">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              {{ __('Replace') }}
            </button>
            <button type="button" class="qp-image__overlay-btn qp-image__overlay-btn--remove" @click.stop="removeImage">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              {{ __('Remove') }}
            </button>
          </div>
        </div>
        <button
          v-else
          type="button"
          class="qp-image-placeholder"
          :aria-label="__('Add question image')"
          @click="openMediaPicker"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2" />
            <circle cx="8.5" cy="8.5" r="1.5" />
            <path d="M21 15l-5-5L5 21" />
          </svg>
          <span>{{ __('Add image') }}</span>
        </button>

        <div
          v-if="hasAnswers"
          class="qp-answers"
        >
          <div class="qp-answer-list">
            <AnswerRow
              v-for="(a, i) in answers"
              :key="a.id"
              :answer="a"
              :index="i"
              :quiz-type="quizType"
              :results="results"
              :question-type="question.type"
              @update="(patch) => onAnswerUpdate(a.id, patch)"
              @delete="() => onAnswerDelete(a.id)"
              @dragstart="(id) => onAnswerDragStart(id)"
              @dragend="onAnswerDragEnd"
              @drop="(id) => onAnswerDrop(id)"
            />
          </div>
          <button
            type="button"
            class="q-add"
            :disabled="creatingAnswer"
            @click="onAddAnswer"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 5v14M5 12h14" />
            </svg>
            {{ creatingAnswer ? __('Adding…') : __('Add answer') }}
          </button>
        </div>

        <!-- Slider settings panel -->
        <div
          v-if="question.type === 'slider'"
          class="qp-type-settings"
        >
          <div class="qp-type-settings__row">
            <label class="qp-type-settings__label">{{ __('Min') }}</label>
            <input
              class="qp-type-settings__num"
              type="number"
              :value="sliderSettings.min"
              step="1"
              @change="onSliderChange('min', $event.target.value)"
            >
          </div>
          <div class="qp-type-settings__row">
            <label class="qp-type-settings__label">{{ __('Max') }}</label>
            <input
              class="qp-type-settings__num"
              type="number"
              :value="sliderSettings.max"
              step="1"
              @change="onSliderChange('max', $event.target.value)"
            >
          </div>
          <div class="qp-type-settings__row">
            <label class="qp-type-settings__label">{{ __('Step') }}</label>
            <input
              class="qp-type-settings__num"
              type="number"
              :value="sliderSettings.step"
              min="1"
              step="1"
              @change="onSliderChange('step', $event.target.value)"
            >
          </div>
          <div class="qp-type-settings__row">
            <label class="qp-type-settings__label">{{ __('Show value') }}</label>
            <input
              type="checkbox"
              :checked="sliderSettings.show_value"
              @change="onSliderChange('show_value', $event.target.checked)"
            >
          </div>
          <!-- Live preview of the slider -->
          <div class="qp-slider-preview">
            <input
              type="range"
              class="qp-slider-preview__track"
              :min="sliderSettings.min"
              :max="sliderSettings.max"
              :step="sliderSettings.step"
              :value="Math.floor((sliderSettings.min + sliderSettings.max) / 2)"
              readonly
              tabindex="-1"
            >
            <span
              v-if="sliderSettings.show_value"
              class="qp-slider-preview__val"
            >{{ Math.floor((sliderSettings.min + sliderSettings.max) / 2) }}</span>
          </div>
        </div>

        <!-- Short text settings panel -->
        <div
          v-if="question.type === 'short_text'"
          class="qp-type-settings"
        >
          <div class="qp-text-preview">
            <input
              type="text"
              class="qp-text-preview__input"
              :placeholder="textSettings.placeholder || __('Your answer…')"
              disabled
            >
          </div>
          <div class="qp-type-settings__hint">
            {{ __('Configure placeholder and max length in the Properties panel.') }}
          </div>
        </div>

        <!-- Rating settings panel -->
        <div
          v-if="question.type === 'rating'"
          class="qp-type-settings"
        >
          <div class="qp-rating-preview">
            <span
              v-for="n in ratingSettings.max"
              :key="n"
              class="qp-rating-preview__star"
              :class="{ 'is-filled': n <= Math.ceil(ratingSettings.max / 2) }"
            >
              <template v-if="ratingSettings.style === 'numbers'">{{ n }}</template>
              <template v-else-if="ratingSettings.style === 'emoji'">{{ ratingEmoji(n, ratingSettings.max) }}</template>
              <template v-else>&#9733;</template>
            </span>
          </div>
          <div class="qp-type-settings__hint">
            {{ __('Configure max rating and style in the Properties panel.') }}
          </div>
        </div>
      </template>

      <!-- ── Splitscreen layout ────────────────────────────────────────────── -->
      <template v-else>
        <!-- Left image panel — question.media_url fills as CSS background -->
        <div class="qp-split-panel" :style="splitPanelStyle">
          <div
            v-if="!question.media_url"
            class="qp-split-placeholder"
            aria-hidden="true"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <path d="M21 15l-5-5L5 21" />
            </svg>
          </div>
        </div>

        <!-- Right content panel -->
        <div class="qp-split-right">
          <!-- Per-question timer (splitscreen) -->
          <div
            v-if="timerEnabled"
            class="qp-timer"
            :aria-label="timerAriaLabel"
          >
            <div class="qp-timer-track">
              <div class="qp-timer-fill" />
            </div>
            <span class="qp-timer-count">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" aria-hidden="true">
                <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
              </svg>
              {{ timerSeconds }}s
            </span>
          </div>

          <div
            ref="titleEl"
            class="qp-title qp-title-ce"
            contenteditable="true"
            :data-placeholder="__('What do you want to ask?')"
            :aria-label="titleAriaLabel"
            @input="onTitleInput"
            @blur="flushTitle"
            @keydown.enter.prevent="flushTitle"
          />

          <!-- Description editor (splitscreen) -->
          <RichTextEditor
            v-model="draftDescription"
            class="qp-desc-rte"
            :toolbarless="true"
            :placeholder="__('Add a description or hint…')"
            :aria-label="descriptionAriaLabel"
            @update:model-value="onDescriptionUpdate"
          />

          <div
            v-if="hasAnswers"
            class="qp-answers"
          >
            <div class="qp-answer-list">
              <AnswerRow
                v-for="(a, i) in answers"
                :key="a.id"
                :answer="a"
                :index="i"
                :quiz-type="quizType"
                :results="results"
                :question-type="question.type"
                @update="(patch) => onAnswerUpdate(a.id, patch)"
                @delete="() => onAnswerDelete(a.id)"
                @dragstart="(id) => onAnswerDragStart(id)"
                @dragend="onAnswerDragEnd"
                @drop="(id) => onAnswerDrop(id)"
              />
            </div>
            <button
              type="button"
              class="q-add"
              :disabled="creatingAnswer"
              @click="onAddAnswer"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14" />
              </svg>
              {{ creatingAnswer ? __('Adding…') : __('Add answer') }}
            </button>
          </div>

          <!-- Slider settings panel (splitscreen) -->
          <div
            v-if="question.type === 'slider'"
            class="qp-type-settings"
          >
            <div class="qp-slider-preview">
              <input
                type="range"
                class="qp-slider-preview__track"
                :min="sliderSettings.min"
                :max="sliderSettings.max"
                :step="sliderSettings.step"
                :value="Math.floor((sliderSettings.min + sliderSettings.max) / 2)"
                readonly
                tabindex="-1"
              >
              <span
                v-if="sliderSettings.show_value"
                class="qp-slider-preview__val"
              >{{ Math.floor((sliderSettings.min + sliderSettings.max) / 2) }}</span>
            </div>
          </div>

          <!-- Short text settings panel (splitscreen) -->
          <div
            v-if="question.type === 'short_text'"
            class="qp-type-settings"
          >
            <div class="qp-text-preview">
              <input
                type="text"
                class="qp-text-preview__input"
                :placeholder="textSettings.placeholder || __('Your answer…')"
                disabled
              >
            </div>
          </div>

          <!-- Rating settings panel (splitscreen) -->
          <div
            v-if="question.type === 'rating'"
            class="qp-type-settings"
          >
            <div class="qp-rating-preview">
              <span
                v-for="n in ratingSettings.max"
                :key="n"
                class="qp-rating-preview__star"
                :class="{ 'is-filled': n <= Math.ceil(ratingSettings.max / 2) }"
              >
                <template v-if="ratingSettings.style === 'numbers'">{{ n }}</template>
                <template v-else-if="ratingSettings.style === 'emoji'">{{ ratingEmoji(n, ratingSettings.max) }}</template>
                <template v-else>&#9733;</template>
              </span>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RichTextEditor, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { resolveBgStyle } from '@shared/bgStyle.js';
import { hexToRgba } from '@shared/colorUtils.js';
import { fullscreenStage } from '@shared/fullscreenStage.js';
import { isValueQuestion, ratingEmoji } from '@shared/questionTypes.js';
import { useTabPreview } from '@admin/composables/useTabPreview.js';
import AnswerRow from './AnswerRow.vue';
import { __, sprintf } from '@shared/i18n';

const TYPE_LABELS = {
  single: __('Single choice'),
  multiple: __('Multiple choice'),
  true_false: __('True / False'),
  image_choice: __('Image choice'),
  short_text: __('Short text'),
  dropdown: __('Dropdown'),
  slider: __('Slider'),
  rating: __('Rating'),
};

const props = defineProps({
  question: { type: Object, required: true },
  quizType: { type: String, default: 'personality' },
  results: { type: Array, default: () => [] },
  position: { type: Number, required: true },
  total: { type: Number, required: true },
});

const emit = defineEmits(['open-properties']);

const store = useQuizBuilderStore();
const toast = useToast();
const preview = reactive(useTabPreview());

const titleEl = ref(null);
const draftTitle = ref(props.question.title ?? '');
const draftDescription = ref(props.question.description ?? '');
const creatingAnswer = ref(false);
const draggingAnswerId = ref(null);

// ── Title floating toolbar ────────────────────────────────────────────────────
const titleBar = reactive({
  visible: false,
  bold: false,
  italic: false,
  underline: false,
  align: 'left',   // 'left' | 'center' | 'right'
  color: '#000000',
  posStyle: { left: '0px', top: '0px', transform: 'translateX(-50%)' },
});

const titleColorInputRef = ref(null);

function updateTitleBar() {
  const sel = window.getSelection();
  if (!sel || sel.isCollapsed || !titleEl.value?.contains(sel.anchorNode)) {
    titleBar.visible = false;
    return;
  }
  const rect = sel.getRangeAt(0).getBoundingClientRect();
  titleBar.visible   = true;
  titleBar.bold      = document.queryCommandState('bold');
  titleBar.italic    = document.queryCommandState('italic');
  titleBar.underline = document.queryCommandState('underline');
  // Detect current alignment from computed style
  const el = sel.anchorNode?.parentElement;
  const align = el ? window.getComputedStyle(el).textAlign : 'left';
  titleBar.align = (align === 'center' || align === 'right') ? align : 'left';
  titleBar.posStyle = {
    left:      `${rect.left + rect.width / 2 + window.scrollX}px`,
    top:       `${rect.top  + window.scrollY - 54}px`,
    transform: 'translateX(-50%)',
  };
}

function execTitle(cmd, value = null) {
  document.execCommand(cmd, false, value);
  titleEl.value?.focus();
  updateTitleBar();
}

function applyTitleAlign(align) {
  const cmd = align === 'center' ? 'justifyCenter' : align === 'right' ? 'justifyRight' : 'justifyLeft';
  execTitle(cmd);
  titleBar.align = align;
}

function applyTitleColor(color) {
  titleBar.color = color;
  execTitle('foreColor', color);
}

const isSplitscreen = computed(() => preview.templateSlug === 'splitscreen');

// ── Per-question timer badge (admin preview — static, no countdown) ───────────
const timerEnabled = computed(() => Boolean(props.question?.settings?.timer?.enabled));
const timerSeconds = computed(() => Number(props.question?.settings?.timer?.seconds) || 30);

// ── Card appearance — apply template's color palette + background ─────────────
const fontScale = computed(() => Math.min(1.5, Math.max(0.7, (Number(store.quiz?.design?.font_size) || 100) / 100)));

const rootVars = computed(() => {
  const c = preview.colors;
  return {
    '--qp-primary': c.primary || 'var(--brand)',
    '--qp-text':    c.text    || 'var(--ink-1)',
    '--qp-accent':  c.accent  || 'var(--accent)',
    '--qp-bg':      hexToRgba(c.background || '#ffffff', preview.backgroundOpacity),
    // Full Screen stage: the same definition the live template paints from.
    '--qp-stage':   fullscreenStage('var(--qp-text)', 'var(--qp-primary)'),
    '--quizably-font-scale': fontScale.value,
  };
});

const cardStyle = computed(() => {
  if (isSplitscreen.value) return {};
  return bg.value.backgroundImage
    ? { backgroundImage: `url("${bg.value.backgroundImage}")` }
    : {};
});

const splitPanelStyle = computed(() => {
  if (!isSplitscreen.value || !props.question.media_url) return {};
  return { backgroundImage: `url("${props.question.media_url}")` };
});

const cardClass = computed(() => [
  `qp-tpl--${preview.templateSlug}`,
  `qp-btn--${preview.buttonStyle}`,
  isSplitscreen.value ? `is-content-${preview.splitContentAlign}` : '',
]);

const bg = computed(() => resolveBgStyle(props.question.settings?.background ?? null, store.quiz?.design));
const cardOverlayStyle = computed(() => ({
  background: bg.value.overlayColor,
  opacity: String(bg.value.overlayOpacity / 100),
}));

// ── Question meta ──────────────────────────────────────────────────────────────
const answers  = computed(() => props.question.answers ?? []);
const hasAnswers = computed(() => !isValueQuestion(props.question.type));
const typeLabel  = computed(() => TYPE_LABELS[props.question.type] || props.question.type || 'Question');

// ── Type-specific settings computeds ──────────────────────────────────────────
const sliderSettings = computed(() => {
  const s = props.question.settings?.slider || {};
  return {
    min: Number.isFinite(Number(s.min)) ? Number(s.min) : 0,
    max: Number.isFinite(Number(s.max)) ? Number(s.max) : 100,
    step: Number.isFinite(Number(s.step)) && Number(s.step) > 0 ? Number(s.step) : 1,
    show_value: Boolean(s.show_value),
  };
});

const textSettings = computed(() => {
  const s = props.question.settings?.text || {};
  return {
    placeholder: s.placeholder ?? '',
    max_length: Number.isFinite(Number(s.max_length)) ? Number(s.max_length) : 500,
  };
});

const ratingSettings = computed(() => {
  const s = props.question.settings?.rating || {};
  const max = Number(s.max);
  return {
    max: Number.isFinite(max) && max > 0 ? max : 5,
    style: s.style || 'stars',
  };
});

async function onSliderChange(key, rawValue) {
  const parsed = key === 'show_value' ? Boolean(rawValue) : Number(rawValue);
  const slider = { ...sliderSettings.value, [key]: parsed };
  const settings = { ...(props.question.settings || {}), slider };
  try {
    await store.updateQuestion(props.question.id, { settings });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save slider setting'), message: e.message });
  }
}
// ── Alignment — driven by QuestionProperties sidebar ─────────────────────────
const questionAlign = computed(() => {
  const v = props.question.settings?.align;
  return v === 'center' || v === 'right' ? v : 'left';
});

// translators: 1: current question number, 2: total number of questions
const questionOfTotal = computed(() => sprintf(__('Question %1$d of %2$d'), props.position, props.total));
// translators: 1: current question number, 2: total number of questions
const positionOfTotal = computed(() => sprintf(__('%1$d of %2$d'), props.position, props.total));
// translators: %d: number of seconds
const timerAriaLabel = computed(() => sprintf(__('Per-question timer: %d seconds'), timerSeconds.value));
// translators: %d: question number
const titleAriaLabel = computed(() => sprintf(__('Question %d title'), props.position));
// translators: %d: question number
const descriptionAriaLabel = computed(() => sprintf(__('Question %d description'), props.position));

const imageFit   = computed(() => {
  const v = props.question.settings?.image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

const imageHeightStyle = computed(() => {
  const h = Number(props.question.settings?.image_height);
  return Number.isFinite(h) && h > 0 ? { height: `${h}px` } : { height: '300px' };
});

// ── WP media picker — open directly from the preview without needing the sidebar ─
let _wpFrame = null;

function openMediaPicker() {
  if (typeof window === 'undefined' || !window.wp?.media) {
    // Fallback for environments without WP scripts (e.g. jsdom in tests)
    const url = window.prompt(__('Enter image URL'), props.question.media_url || '');
    if (url !== null) saveMediaUrl(url);
    return;
  }
  if (!_wpFrame) {
    _wpFrame = window.wp.media({
      title: __('Select question image'),
      button: { text: __('Use this image') },
      library: { type: 'image' },
      multiple: false,
    });
    _wpFrame.on('select', () => {
      const att = _wpFrame.state().get('selection').first().toJSON();
      saveMediaUrl(att.url);
    });
  }
  _wpFrame.open();
}

async function saveMediaUrl(url) {
  const next = (url ?? '').toString();
  if (next === (props.question.media_url ?? '')) return;
  try {
    await store.updateQuestion(props.question.id, { media_url: next });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not save image'), message: e.message });
  }
}

async function removeImage() {
  try {
    await store.updateQuestion(props.question.id, { media_url: '' });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not remove image'), message: e.message });
  }
}

watch(() => props.question.title, (v) => {
  if (v !== draftTitle.value) draftTitle.value = v ?? '';
});
watch(() => props.question.id, () => {
  draftTitle.value = props.question.title ?? '';
  draftDescription.value = props.question.description ?? '';
});

// ── Title (contenteditable) ────────────────────────────────────────────────────

function onTitleInput() {
  draftTitle.value = titleEl.value?.innerHTML ?? '';
  store.stageQuestion(props.question.id, { title: draftTitle.value });
}

// Strip all formatting on paste — insert plain text only so the title stays clean.
function onTitlePaste(e) {
  e.preventDefault();
  const text = (e.clipboardData || window.clipboardData).getData('text/plain');
  document.execCommand('insertText', false, text);
}

function flushTitle() {
  // Commit current DOM value on blur/enter — stages locally, no API call.
  const next = (titleEl.value?.innerHTML ?? '').trim();
  draftTitle.value = next;
  store.stageQuestion(props.question.id, { title: next });
}

function initTitleEl() {
  if (titleEl.value && props.question.title !== undefined) {
    titleEl.value.innerHTML = props.question.title ?? '';
  }
}

// ── Description (RichTextEditor v-model) ──────────────────────────────────────

function onDescriptionUpdate(html) {
  draftDescription.value = html;
  store.stageQuestion(props.question.id, { description: html });
}

// Called by quizably:flush-pending-saves (Save button) to ensure the latest
// contenteditable value is staged before flushAllStaged() runs.
function flushAllPending() {
  store.stageQuestion(props.question.id, {
    title: (titleEl.value?.innerHTML ?? '').trim(),
    description: draftDescription.value ?? '',
  });
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
  initTitleEl();
  document.addEventListener('selectionchange', updateTitleBar);
  window.addEventListener('quizably:flush-pending-saves', flushAllPending);
});

onBeforeUnmount(() => {
  document.removeEventListener('selectionchange', updateTitleBar);
  window.removeEventListener('quizably:flush-pending-saves', flushAllPending);
});

watch(
  () => props.question.id,
  () => {
    initTitleEl();
    draftDescription.value = props.question.description ?? '';
  }
);

// When switching layouts Vue recreates the contenteditable — re-seed it.
watch(isSplitscreen, () => nextTick(initTitleEl));

watch(
  () => props.question.description,
  (v) => { if (v !== draftDescription.value) draftDescription.value = v ?? ''; }
);

// ── Answers ────────────────────────────────────────────────────────────────────
async function onAnswerUpdate(answerId, patch) {
  try { await store.updateAnswer(props.question.id, answerId, patch); }
  catch (e) { toast.push({ variant: 'danger', title: __('Could not save answer'), message: e.message }); }
}

async function onAnswerDelete(answerId) {
  if (!window.confirm(__('Delete this answer?'))) return;
  try { await store.deleteAnswer(props.question.id, answerId); }
  catch (e) { toast.push({ variant: 'danger', title: __('Could not delete answer'), message: e.message }); }
}

async function onAddAnswer() {
  creatingAnswer.value = true;
  try {
    const pos    = (props.question.answers ?? []).length + 1;
    const letter = String.fromCharCode(64 + Math.min(pos, 26));
    await store.createAnswer(props.question.id, { label: sprintf(__('Answer %s'), letter), position: pos });
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Could not add answer'), message: e.message });
  } finally {
    creatingAnswer.value = false;
  }
}

function onAnswerDragStart(id) { draggingAnswerId.value = id; }
function onAnswerDragEnd()     { draggingAnswerId.value = null; }

async function onAnswerDrop(targetId) {
  const sourceId = draggingAnswerId.value;
  draggingAnswerId.value = null;
  if (!sourceId || sourceId === targetId) return;
  const ids  = answers.value.map((a) => a.id);
  const from = ids.indexOf(sourceId);
  const to   = ids.indexOf(targetId);
  if (from === -1 || to === -1) return;
  ids.splice(from, 1);
  ids.splice(to, 0, sourceId);
  try { await store.reorderAnswers(props.question.id, ids); }
  catch (e) { toast.push({ variant: 'danger', title: __('Could not reorder'), message: e.message }); }
}
</script>

<style scoped>
.qp {
  max-width: 760px;
  min-width: 0;
  width: 100%;
  margin: 0 auto;
  padding: 28px 32px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}


/* ── Head bar ──────────────────────────────────────────────────────────────── */
.qp-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.qp-crumb {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-3);
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.qp-crumb .chip {
  padding: 3px 8px;
  background: var(--brand);
  color: #fff;
  border-radius: var(--r-xs);
  font-weight: 600;
}

.qp-tpl-badge {
  padding: 2px 7px;
  background: var(--brand-tint);
  color: var(--brand);
  border-radius: var(--r-xs);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: lowercase;
}

.qp-head__actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.qp-type {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  font-size: 12.5px;
  color: var(--ink-2);
  box-shadow: var(--shadow-xs);
}

.qp-type svg {
  width: 13px;
  height: 13px;
  color: var(--ink-3);
}

.qp-properties-btn {
  display: none;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: var(--bg-surface);
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  font-family: inherit;
  font-size: 12.5px;
  font-weight: 500;
  color: var(--ink-2);
  cursor: pointer;
}

.qp-properties-btn:hover { border-color: var(--brand); color: var(--brand); }
.qp-properties-btn svg { width: 14px; height: 14px; }

/* ── Card — base ────────────────────────────────────────────────────────────── */
.qp-card {
  --qp-primary: var(--brand);
  --qp-text:    var(--ink-1);
  --qp-accent:  var(--accent);
  --qp-bg:      var(--bg-surface);

  background-color: var(--qp-bg);
  background-size: cover;
  background-position: center;
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  padding: 28px 32px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  position: relative;
  overflow: hidden;
  transition: background-color 300ms, box-shadow 300ms;
}

.qp-card-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.qp-card > *:not(.qp-card-overlay) {
  position: relative;
  z-index: 1;
}

/* ── Template variants ──────────────────────────────────────────────────────── */

/* Minimal: no border, flush edges */
.qp-card.qp-tpl--minimal {
  border-color: transparent;
  box-shadow: none;
}

/* Fullscreen: dark card matching the full-bleed dark template */
.qp-card.qp-tpl--fullscreen {
  /* Same stage as the live template: src/shared/fullscreenStage.js */
  background: var(--qp-stage);
  border-color: transparent;
  color: #fff;
}

/* Cardstack: layered shadow */
.qp-card.qp-tpl--cardstack {
  box-shadow: 0 8px 0 -4px color-mix(in srgb, var(--border-1) 60%, transparent),
              0 16px 0 -8px color-mix(in srgb, var(--border-1) 30%, transparent),
              var(--shadow-md);
}

/* Conversational: colored top strip, rounded */
.qp-card.qp-tpl--conversational {
  border-top: 4px solid var(--qp-primary);
  border-radius: var(--r-xl);
}

/* Gamified: gradient header strip drives the identity; keep subtle border+glow */
.qp-card.qp-tpl--gamified {
  padding-top: 0;
  border-color: var(--qp-primary);
  border-width: 2px;
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--qp-primary) 12%, transparent),
              var(--shadow-sm);
}

/* Splitscreen: two-column layout */
.qp-card.qp-tpl--splitscreen {
  flex-direction: row;
  padding: 0;
  gap: 0;
  border-inline-start: none;
  min-height: 380px;
  max-height: 70vh;
}

.qp-split-panel {
  display: none; /* hidden unless splitscreen */
}

.qp-tpl--splitscreen .qp-split-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42%;
  flex-shrink: 0;
  align-self: stretch;
  background-color: color-mix(in srgb, var(--qp-primary) 85%, #000);
  background-size: cover;
  background-position: center;
  border-start-start-radius: calc(var(--r-lg) - 1px);
  border-start-end-radius: 0;
  border-end-end-radius: 0;
  border-end-start-radius: calc(var(--r-lg) - 1px);
  position: relative;
  overflow: hidden;
}

.qp-split-placeholder {
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(255,255,255,.12);
  color: rgba(255,255,255,.5);
  position: relative;
  z-index: 1;
}

.qp-split-placeholder svg {
  width: 28px;
  height: 28px;
}

.qp-split-right {
  display: none;
}

.qp-tpl--splitscreen .qp-split-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 28px 32px;
  min-width: 0;
  overflow-x: hidden;
  overflow-y: auto;
  justify-content: flex-start;
  align-self: stretch;
}

/* Prevent flex from shrinking children so overflow-y scroll actually fires */
.qp-tpl--splitscreen .qp-split-right > * {
  flex-shrink: 0;
}

.qp-card.is-content-top    .qp-split-right { justify-content: flex-start; }
.qp-card.is-content-center .qp-split-right { justify-content: center; }
.qp-card.is-content-bottom .qp-split-right { justify-content: flex-end; }

/* justify-content other than flex-start breaks overflow scroll — force flex-start for splitscreen */
.qp-card.qp-tpl--splitscreen.is-content-center .qp-split-right,
.qp-card.qp-tpl--splitscreen.is-content-bottom .qp-split-right { justify-content: flex-start; }

/* Magazine: hero is outside the card; card pulls up flush to the strip with negative margin */
.qp-card.qp-tpl--magazine {
  border-top: none;
  border-radius: 0;
  margin-top: -14px;
}

/* ── Question title (contenteditable) ───────────────────────────────────────── */
.qp-title {
  font-family: var(--f-display);
  font-size: calc(26px * var(--quizably-font-scale, 1));
  font-weight: 400;
  letter-spacing: -0.01em;
  color: var(--qp-text);
  line-height: 1.25;
  margin: 0;
  word-break: break-word;
  width: 100%;
  background: transparent;
  border: 0;
  outline: none;
  padding: 0;
  min-height: 1.3em;
}

.qp-title-ce:empty::before {
  content: attr(data-placeholder);
  color: color-mix(in srgb, var(--qp-text, var(--ink-4)) 30%, transparent);
  font-style: italic;
  pointer-events: none;
}

.qp-title-ce:focus {
  border-bottom: 1px dashed color-mix(in srgb, var(--qp-text, var(--ink-3)) 30%, transparent);
}

.qp-tpl--minimal    .qp-title { font-size: calc(34px * var(--quizably-font-scale, 1)); }
.qp-tpl--fullscreen .qp-title { color: #fff; }
.qp-tpl--conversational .qp-title {
  background: color-mix(in srgb, var(--qp-text, var(--ink-1)) 7%, transparent);
  color: var(--qp-text);
  padding: 12px 16px;
  border-start-start-radius: 14px;
  border-start-end-radius: 14px;
  border-end-end-radius: 14px;
  border-end-start-radius: 4px;
  font-size: calc(16px * var(--quizably-font-scale, 1));
  align-self: flex-start;
  max-width: 88%;
  width: auto;
  line-height: 1.45;
}
.qp-tpl--magazine .qp-title { font-size: calc(32px * var(--quizably-font-scale, 1)); letter-spacing: -0.02em; }
</style>

<style>
/* Global — Teleport renders outside scoped component */
.qp-title-bar {
  position: absolute;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 1px;
  background: #1c1c1e;
  border-radius: 8px;
  padding: 5px 7px;
  box-shadow: 0 4px 20px rgba(0,0,0,.4), 0 1px 3px rgba(0,0,0,.2);
  pointer-events: auto;
  user-select: none;
}
.qp-title-bar::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%; /* rtl-ok: centring pair with translateX(-50%), symmetric in both directions */
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #1c1c1e;
}
.qp-title-bar__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 26px;
  min-width: 26px;
  padding: 0 5px;
  border: 0;
  border-radius: 5px;
  background: transparent;
  color: rgba(255,255,255,.65);
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 100ms, color 100ms;
  line-height: 1;
}
.qp-title-bar__btn:hover { background: rgba(255,255,255,.1); color: #fff; }
.qp-title-bar__btn.is-active { background: rgba(255,255,255,.18); color: #fff; }
.qp-title-bar__sep { width: 1px; height: 16px; background: rgba(255,255,255,.15); margin: 0 4px; flex-shrink: 0; }
/* Color picker inline in the toolbar */
.qp-title-bar__color {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 2px;
  height: 26px;
  width: 30px;
  padding: 0 3px;
  border-radius: 5px;
  cursor: pointer;
  color: rgba(255,255,255,.65);
  transition: background 100ms, color 100ms;
}
.qp-title-bar__color:hover { background: rgba(255,255,255,.1); color: #fff; }
.qp-title-bar__color-swatch {
  display: block;
  width: 16px;
  height: 3px;
  border-radius: 2px;
  margin-top: -1px;
}
.qp-title-bar__color-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}
</style>

<style scoped>
/* ── Description RichTextEditor (toolbarless) ────────────────────────────────── */
.qp-desc-rte {
  font-family: var(--f-sans);
  font-size: calc(15px * var(--quizably-font-scale, 1));
  line-height: 1.6;
  color: var(--qp-text);
  opacity: 0.8;
}

.qp-desc-rte:focus-within { opacity: 1; }

.qp-desc-rte :deep(.quizably-rte__doc) {
  font-size: calc(15px * var(--quizably-font-scale, 1));
  font-family: var(--f-sans);
  line-height: 1.6;
  color: var(--qp-text);
}

.qp-desc-rte :deep(.quizably-rte__doc p) { margin: 0 0 6px; }
.qp-desc-rte :deep(.quizably-rte__doc p:last-child) { margin-bottom: 0; }
.qp-desc-rte :deep(.quizably-rte__doc strong) { font-weight: 700; }
.qp-desc-rte :deep(.quizably-rte__doc em) { font-style: italic; }
.qp-desc-rte :deep(.quizably-rte__doc u) { text-decoration: underline; }
.qp-desc-rte :deep(.quizably-rte__doc ul),
.qp-desc-rte :deep(.quizably-rte__doc ol) { margin: 4px 0 8px; padding-inline-start: 22px; }
.qp-desc-rte :deep(.quizably-rte__doc li) { margin-bottom: 3px; }

.qp-tpl--fullscreen .qp-desc-rte { color: rgba(255,255,255,.7); }
.qp-tpl--fullscreen .qp-desc-rte :deep(.quizably-rte__doc) { color: rgba(255,255,255,.7); }

/* ── Image preview — height controlled via inline style from image_height setting ──
   Width must be explicit: .qp-card is a column flex container and .qp-image
   itself is display:flex with align-items:center (not stretch), so as a flex
   item it has no sizing signal to fall back on. The <img> in contain mode
   masks this (an <img> has its own intrinsic size); the cover/repeat variant
   is a bare background-image <div> with nothing to establish a size, so it
   collapsed to 0 width and rendered nothing despite backgroundImage being
   set correctly. Same root cause fixed in the frontend Question.vue. */
.qp-image {
  width: 100%;
  margin-top: 8px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qp-image--contain img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

/* Cover: background-div fills the slot — height driven by inline style */
.qp-image--cover { }
.qp-image__cover-bg {
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}
.qp-image--repeat { }
.qp-image__tile {
  width: 100%;
  height: 100%;
  background-repeat: repeat;
  background-position: 0 0;
  background-size: auto;
}

/* Replace / remove overlay — appears on hover over an existing image */
.qp-image {
  position: relative;
}

.qp-image__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(0, 0, 0, 0.45);
  opacity: 0;
  transition: opacity 150ms;
  border-radius: inherit;
}

.qp-image:hover .qp-image__overlay {
  opacity: 1;
}

.qp-image__overlay-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 13px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: var(--r-sm);
  color: #fff;
  font: inherit;
  font-size: 12.5px;
  font-weight: 500;
  cursor: pointer;
  transition: background 120ms;
  backdrop-filter: blur(4px);
}

.qp-image__overlay-btn:hover {
  background: rgba(255, 255, 255, 0.25);
}

.qp-image__overlay-btn--remove:hover {
  background: rgba(220, 38, 38, 0.55);
  border-color: rgba(255, 255, 255, 0.4);
}

.qp-image__overlay-btn svg {
  width: 13px;
  height: 13px;
  flex-shrink: 0;
}

/* Add image placeholder — same visual as intro/result/form placeholder buttons */
.qp-image-placeholder {
  width: 100%;
  min-height: 80px;
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  border: 1.5px dashed var(--border-2);
  border-radius: var(--r-md);
  background: var(--bg-canvas);
  color: var(--ink-3);
  font-size: 12px;
  font-weight: 500;
  font-family: var(--f-mono);
  cursor: pointer;
  transition: border-color 150ms, background 150ms, color 150ms;
}

.qp-image-placeholder:hover {
  border-color: var(--brand);
  background: color-mix(in srgb, var(--brand) 5%, var(--bg-canvas));
  color: var(--brand);
}

.qp-image-placeholder svg {
  width: 20px;
  height: 20px;
}

/* ── Answers ─────────────────────────────────────────────────────────────────── */
/* align-self: stretch overrides the card's align-items (flex-start/center/flex-end)
   that the alignment control sets, so the answer list always fills the full card width. */
.qp-answers { margin-top: 6px; width: 100%; align-self: stretch; }

.qp-answer-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* ── Answer rows — apply design colors ───────────────────────────────────────── */
:deep(.answer-row) {
  background-color: color-mix(in srgb, var(--qp-bg, var(--bg-surface)) 88%, var(--qp-primary));
  border-color: color-mix(in srgb, var(--qp-primary) 28%, var(--border-2));
  transition: border-color 150ms, background-color 150ms;
}

:deep(.answer-row:focus-within) {
  border-color: var(--qp-primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--qp-primary) 12%, transparent);
}

:deep(.answer-row__text) { color: var(--qp-text); }

:deep(.answer-row__letter) {
  background: color-mix(in srgb, var(--qp-primary) 14%, transparent);
  color: var(--qp-primary);
}

/* Button-style shape overrides */
.qp-btn--pill  :deep(.answer-row) { border-radius: var(--r-pill) !important; }
.qp-btn--sharp :deep(.answer-row) { border-radius: 0 !important; }

/* Conversational */
.qp-tpl--conversational :deep(.answer-row) { border-radius: var(--r-xl) !important; }

/* Gamified */
.qp-tpl--gamified :deep(.answer-row) { border-inline-start: 3px solid var(--qp-primary); }

/* Magazine: outer border box + zero gap matches frontend Magazine.vue deep CSS */
.qp-tpl--magazine .qp-answer-list {
  border: 1.5px solid var(--qp-text, var(--ink-1));
  border-radius: 0;
  overflow: hidden;
  gap: 0;
}

.qp-tpl--magazine :deep(.answer-row) {
  border-radius: 0 !important;
  border-inline-start: none;
  border-inline-end: none;
  border-top: none;
  border-bottom: 1.5px solid color-mix(in srgb, var(--qp-text, var(--ink-1)) 25%, transparent);
  box-shadow: none;
  background-color: transparent;
}

.qp-tpl--magazine :deep(.answer-row:last-child) {
  border-bottom: none;
}

/* Fullscreen: transparent rows over the dark gradient/image */
.qp-tpl--fullscreen :deep(.answer-row) {
  background-color: rgba(255,255,255,.08);
  border-color: rgba(255,255,255,.2);
}
.qp-tpl--fullscreen :deep(.answer-row__text)   { color: rgba(255,255,255,.9); }
.qp-tpl--fullscreen :deep(.answer-row__letter) { background: rgba(255,255,255,.12); color: rgba(255,255,255,.8); }

/* ── Add answer button ────────────────────────────────────────────────────────── */
.q-add {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  margin-top: 12px;
  padding: 10px;
  background: var(--bg-surface);
  border: 1.5px dashed color-mix(in srgb, var(--qp-primary) 30%, var(--border-2));
  border-radius: var(--r-sm);
  font-size: 12.5px;
  font-weight: 500;
  color: var(--ink-3);
  transition: all 150ms ease;
  cursor: pointer;
  font-family: inherit;
}

.qp-btn--pill  .q-add { border-radius: var(--r-pill); }
.qp-btn--sharp .q-add { border-radius: 0; }

.q-add:hover:not(:disabled) {
  border-color: var(--qp-primary);
  color: var(--qp-primary);
  background: color-mix(in srgb, var(--qp-primary) 6%, var(--bg-surface));
}

.q-add:disabled { opacity: 0.6; cursor: not-allowed; }
.q-add svg { width: 14px; height: 14px; }

.qp-tpl--fullscreen .q-add {
  border-color: rgba(255,255,255,.25);
  color: rgba(255,255,255,.6);
  background: rgba(255,255,255,.05);
}

/* ── Type-specific settings panels ───────────────────────────────────────────── */
.qp-type-settings {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 8px;
  padding: 16px;
  background: color-mix(in srgb, var(--qp-bg, var(--bg-surface)) 80%, var(--bg-canvas));
  border: 1px dashed color-mix(in srgb, var(--qp-primary) 28%, var(--border-2));
  border-radius: var(--r-md);
}

.qp-type-settings__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.qp-type-settings__label {
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--qp-text);
  font-weight: 500;
}

.qp-type-settings__num {
  width: 80px;
  padding: 5px 8px;
  border: 1px solid var(--border-2);
  border-radius: var(--r-xs);
  font-family: var(--f-mono);
  font-size: 13px;
  color: var(--ink-1);
  background: var(--bg-surface);
  outline: none;
  text-align: end;
}
.qp-type-settings__num:focus { border-color: var(--brand); }

.qp-type-settings__hint {
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--ink-3);
  font-style: italic;
}

/* Slider preview */
.qp-slider-preview {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
}

.qp-slider-preview__track {
  flex: 1;
  accent-color: var(--qp-primary, var(--brand));
  height: 6px;
  cursor: default;
}

.qp-slider-preview__val {
  font-family: var(--f-mono);
  font-size: 14px;
  font-weight: 600;
  color: var(--qp-primary, var(--brand));
  min-width: 36px;
  text-align: end;
}

/* Short text preview */
.qp-text-preview__input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid color-mix(in srgb, var(--qp-primary) 30%, var(--border-2));
  border-radius: var(--r-sm);
  font-size: 14px;
  font-family: inherit;
  color: var(--qp-text);
  background: transparent;
  outline: none;
  cursor: default;
}
.qp-text-preview__input::placeholder { color: var(--ink-4); font-style: italic; }

/* Rating preview */
.qp-rating-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
  padding: 4px 0;
}

.qp-rating-preview__star {
  font-size: 28px;
  line-height: 1;
  color: var(--border-3);
  transition: color 120ms;
  cursor: default;
  user-select: none;
}

.qp-rating-preview__star.is-filled {
  color: var(--qp-primary, var(--brand));
}

/* ── Responsive ──────────────────────────────────────────────────────────────── */
@media (max-width: 1023px) {
  .qp-properties-btn { display: inline-flex; }
}

/* ── Gamified header strip ──────────────────────────────────────────────────── */
.qp-game-head {
  /* bust out of the card's 28px/32px padding so the strip is flush */
  margin: 0 -32px;
  padding: 14px 20px;
  background: linear-gradient(135deg, var(--qp-primary, #4F46E5), var(--qp-accent, #F59E0B));
  color: #fff;
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  z-index: 2;
  overflow: hidden;
}

.qp-game-lvl {
  display: flex;
  align-items: baseline;
  gap: 5px;
  flex-shrink: 0;
}

.qp-game-lvl-num {
  font-family: var(--f-display);
  font-size: 22px;
  font-weight: 600;
  line-height: 1;
}

.qp-game-lvl-label {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, .8);
}

.qp-game-pips {
  flex: 1;
  display: flex;
  gap: 4px;
  align-items: center;
}

.qp-game-pip {
  flex: 1;
  height: 5px;
  border-radius: var(--r-pill);
  background: rgba(255, 255, 255, .25);
}

.qp-game-pip.is-done    { background: #fff; }
.qp-game-pip.is-current { background: rgba(255, 255, 255, .85); box-shadow: 0 0 0 2px rgba(255, 255, 255, .4); }

.qp-game-score {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  background: rgba(0, 0, 0, .18);
  border-radius: var(--r-pill);
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 600;
  flex-shrink: 0;
}

.qp-game-score svg { width: 12px; height: 12px; fill: #FCD34D; color: #FCD34D; }

/* ── Magazine hero strip ────────────────────────────────────────────────────── */
.qp-mag-hero {
  margin: 0 -32px;
  height: 72px;
  background: linear-gradient(135deg,
    color-mix(in srgb, var(--qp-primary, #4F46E5) 30%, var(--qp-text, #0a0a0b)),
    var(--qp-text, #0a0a0b)
  );
  border-bottom: 4px solid var(--qp-accent, #F59E0B);
  position: relative;
  z-index: 2;
  display: flex;
  align-items: flex-end;
  padding: 0 22px 10px;
  overflow: hidden;
}

.qp-mag-hero-issue {
  font-family: var(--f-mono);
  font-size: 10px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, .9);
  background: rgba(0, 0, 0, .3);
  padding: 3px 8px;
}

/* ── Classic identity markers ───────────────────────────────────────────────── */
/* Matches ClassicPreview.vue: progress bar inside padding + accent stepper     */
.qp-classic-bar {
  width: 100%;
  height: 4px;
  background: color-mix(in srgb, var(--qp-text, var(--ink-1)) 8%, transparent);
  border-radius: var(--r-pill);
  overflow: hidden;
}

.qp-classic-bar-fill {
  height: 100%;
  background: var(--qp-primary, var(--brand));
  border-radius: var(--r-pill);
}

.qp-classic-stepper {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--qp-accent, #F59E0B);
}

/* ── Cardstack identity markers ─────────────────────────────────────────────── */
/* Matches CardstackPreview.vue: accent count badge in top-left               */
.qp-stack-count {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--qp-accent, #F59E0B);
  padding: 3px 8px;
  background: color-mix(in srgb, var(--qp-accent, #F59E0B) 14%, transparent);
  border-radius: var(--r-xs);
  align-self: flex-start;
}

/* ── Conversational identity markers ────────────────────────────────────────── */
/* Matches ConversationalPreview.vue: avatar + step counter before the bubble  */
.qp-conv-head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid color-mix(in srgb, var(--qp-text, var(--ink-1)) 8%, transparent);
}

.qp-conv-avatar {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: color-mix(in srgb, var(--qp-primary) 14%, transparent);
  color: var(--qp-primary);
  flex-shrink: 0;
}

.qp-conv-avatar svg {
  width: 16px;
  height: 16px;
}

.qp-conv-step {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.04em;
  color: color-mix(in srgb, var(--qp-text, var(--ink-1)) 60%, transparent);
}

/* ── Alignment variants on the card ─────────────────────────────────────────── */
.qp-card.is-align-left   { text-align: left;   align-items: flex-start; } /* rtl-ok: author-chosen physical alignment setting ("left"/"right" is the stored value) */
.qp-card.is-align-center { text-align: center; align-items: center; }
.qp-card.is-align-right  { text-align: right;  align-items: flex-end; } /* rtl-ok: author-chosen physical alignment setting ("left"/"right" is the stored value) */

/* Per-question timer (admin preview — static bar at 100%) ────────────────────── */
.qp-timer {
  display: flex;
  align-items: center;
  gap: 10px;
}

.qp-timer-track {
  flex: 1;
  height: 4px;
  background: var(--border-2, #e5e7eb);
  border-radius: var(--r-pill);
  overflow: hidden;
}

.qp-timer-fill {
  height: 100%;
  width: 100%;
  background: var(--qp-primary, var(--brand));
  border-radius: var(--r-pill);
}

.qp-timer-count {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-family: var(--f-mono);
  font-size: 12px;
  font-weight: 600;
  color: var(--qp-primary, var(--brand));
  flex-shrink: 0;
  min-width: 3ch;
}
</style>
