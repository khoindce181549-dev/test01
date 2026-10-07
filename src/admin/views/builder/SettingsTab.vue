<template>
  <div class="settings-scroll settings-tab">
    <div class="settings">
      <header class="settings__head">
        <div>
          <h2 class="settings__title settings-tab__title">
            {{ __('Settings') }}
          </h2>
          <p class="settings__desc">
            {{ settingsDesc }}
          </p>
        </div>
      </header>

      <PerQuizAnalytics />

      <!-- Main settings panel with flat sections -->
      <div class="settings__panel">
        <!-- ───────── Quiz behavior ───────── -->
        <section class="settings-block">
          <header class="settings-block__head">
            <div>
              <span class="settings-block__title">{{ __('Quiz behavior') }}</span>
              <span class="settings-block__sub">{{ behaviorSub }}</span>
            </div>
          </header>
          <div class="settings-block__body">
            <!-- Global timer — Pro-tier control: absent unless Pro is active or promoted -->
            <div
              v-if="proTierVisible"
              :class="['settings-row', 'settings-pro-toggle', { 'is-pro': !isProUser }]"
            >
              <div class="settings-row__label">
                <span>{{ __('Global timer') }}</span>
                <Tooltip :label="__('Auto-submits the quiz when the timer runs out.')">
                  <span class="quizably-help-dot" aria-hidden="true">?</span>
                </Tooltip>
                <Badge v-if="!isProUser" variant="pro" size="sm">{{ __('Pro') }}</Badge>
              </div>
              <Toggle
                v-if="isProUser"
                :model-value="globalTimerEnabled"
                size="sm"
                data-testid="settings-global-timer-toggle"
                @update:model-value="onGlobalTimerToggle"
              />
              <Toggle
                v-else
                :model-value="false"
                size="sm"
                @update:model-value="() => proToast(__('Global timer'))"
              />
            </div>
            <div
              v-if="isProUser && globalTimerEnabled"
              class="settings-row settings-row--inline"
            >
              <div class="settings-row__label">
                <span class="settings-row__sublabel">{{ __('Seconds') }}</span>
              </div>
              <div class="settings-row__seconds">
                <Input
                  :model-value="globalTimerSeconds"
                  type="number"
                  placeholder="600"
                  data-testid="settings-global-timer-seconds"
                  @update:model-value="onGlobalTimerSeconds"
                />
              </div>
            </div>

            <!-- Per-question timer mode flag — overrides global counting
                 to "reset every question" when on. The per-question
                 seconds live on each question via QuestionProperties. -->
            <div
              v-if="proTierVisible"
              :class="['settings-row', 'settings-pro-toggle', { 'is-pro': !isProUser }]"
            >
              <div class="settings-row__label">
                <span>{{ __('Per-question timer mode') }}</span>
                <Tooltip :label="perQuestionTimerTip">
                  <span class="quizably-help-dot" aria-hidden="true">?</span>
                </Tooltip>
                <Badge v-if="!isProUser" variant="pro" size="sm">{{ __('Pro') }}</Badge>
              </div>
              <Toggle
                v-if="isProUser"
                :model-value="perQuestionTimerMode"
                size="sm"
                data-testid="settings-pq-timer-toggle"
                @update:model-value="onPerQuestionTimerToggle"
              />
              <Toggle
                v-else
                :model-value="false"
                size="sm"
                @update:model-value="() => proToast(__('Per-question timer'))"
              />
            </div>

            <!-- Randomize quiz-level question order — free. -->
            <div class="settings-row">
              <div class="settings-row__label">
                <span>{{ __('Randomize questions') }}</span>
                <Tooltip :label="__('Show the questions in a different order for every visitor. Stable for one session so back/forward keeps the same order.')">
                  <span class="quizably-help-dot" aria-hidden="true">?</span>
                </Tooltip>
              </div>
              <Toggle
                :model-value="randomizeQuestions"
                size="sm"
                data-testid="settings-randomize-questions-toggle"
                @update:model-value="onRandomizeQuestionsToggle"
              />
            </div>

            <!-- Auto-advance — free feature: selecting a single/true-false answer
                 moves to the next question automatically.
                 Stored at settings.question.auto_advance. -->
            <div class="settings-row">
              <div class="settings-row__label">
                <span>{{ __('Auto-advance on answer') }}</span>
                <Tooltip :label="__('Single-choice and True/False questions automatically skip to the next question as soon as the visitor picks an answer. Turn off if you want them to review before clicking Next.')">
                  <span class="quizably-help-dot" aria-hidden="true">?</span>
                </Tooltip>
              </div>
              <Toggle
                :model-value="autoAdvance"
                size="sm"
                data-testid="settings-auto-advance-toggle"
                @update:model-value="onAutoAdvanceToggle"
              />
            </div>

            <!-- Progress bar (Pro-tier control) -->
            <div
              v-if="proTierVisible"
              :class="['settings-row', 'settings-pro-toggle', { 'is-pro': !isProUser }]"
            >
              <div class="settings-row__label">
                <span>{{ __('Progress bar') }}</span>
                <Tooltip :label="__('Bar / percentage / step-dot styles. Free quizzes show a simple text counter.')">
                  <span class="quizably-help-dot" aria-hidden="true">?</span>
                </Tooltip>
                <Badge v-if="!isProUser" variant="pro" size="sm">{{ __('Pro') }}</Badge>
              </div>
              <Toggle
                v-if="isProUser"
                :model-value="progressBarEnabled"
                size="sm"
                data-testid="settings-progress-bar-toggle"
                @update:model-value="onProgressBarToggle"
              />
              <Toggle
                v-else
                :model-value="false"
                size="sm"
                @update:model-value="() => proToast(__('Progress bar'))"
              />
            </div>
          </div>
        </section>

        <!-- ───────── Result screen ───────── -->
        <section class="settings-block">
          <header class="settings-block__head">
            <div>
              <span class="settings-block__title">{{ __('Result screen') }}</span>
              <span class="settings-block__sub">{{ __('Controls for the result card shown after the quiz') }}</span>
            </div>
          </header>
          <div class="settings-block__body">
            <div class="settings-row">
              <div class="settings-row__label">
                <span>{{ __('Show answer review (trivia)') }}</span>
                <Tooltip :label="reviewAnswersTip">
                  <span class="quizably-help-dot" aria-hidden="true">?</span>
                </Tooltip>
              </div>
              <Toggle
                :model-value="reviewAnswers"
                size="sm"
                data-testid="settings-review-answers-toggle"
                @update:model-value="onReviewAnswersToggle"
              />
            </div>
          </div>
        </section>

        <!-- ───────── Intro screens ───────── -->
        <section class="settings-block">
          <header class="settings-block__head">
            <div>
              <span class="settings-block__title">{{ __('Intro screens') }}</span>
              <span class="settings-block__sub">{{ __('Control what appears on the intro screen') }}</span>
            </div>
          </header>
          <div class="settings-block__body">
            <div class="settings-row">
              <div class="settings-row__label">
                <span>{{ __('Show estimated time') }}</span>
                <Tooltip :label="__('Display question count, estimated completion time, and result count on the intro screen.')">
                  <span class="quizably-help-dot" aria-hidden="true">?</span>
                </Tooltip>
              </div>
              <Toggle
                :model-value="introShowMeta"
                size="sm"
                data-testid="intro-show-meta-toggle"
                @update:model-value="onIntroShowMetaToggle"
              />
            </div>
          </div>
        </section>

      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Badge, Input, Toggle, Tooltip, useToast } from '@admin/ui';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';
import { isPro, proFeaturesVisible } from '@admin/api/pro.js';
import PerQuizAnalytics from './PerQuizAnalytics.vue';
import { __, sprintf } from '@shared/i18n';

const store = useQuizBuilderStore();
const toast = useToast();


const isProUser = computed(() => isPro());

// Timer / randomize / progress-bar rows are Pro-tier controls: listed for Pro
// users, and for free users only while Pro promotion is on (see api/pro.js).
const proTierVisible = computed(() => proFeaturesVisible());

// With those rows absent the block only holds the auto-advance switch, so its
// copy stops advertising time limits / ordering / progress.
const behaviorSub = computed(() =>
  proTierVisible.value
    ? __('Time limits, order, and progress affordances')
    : __('How visitors move between questions')
);
const settingsDesc = computed(() =>
  proTierVisible.value
    ? __('Per-quiz behavior — form, timing, intro/outro screens, and lifecycle.')
    : __('Per-quiz behavior — question flow, result and intro screens.')
);

// Tooltips containing apostrophes are kept in the script so the literals can
// use double quotes (no escapes, which the POT extractor would not unescape).
const perQuestionTimerTip = computed(() => __("When on, the global timer resets every question (using each question's own seconds when set, falling back to the global value)."));
const reviewAnswersTip = computed(() => __("For trivia quizzes — adds a collapsible 'Review answers' section on the result screen so visitors can see which questions they got right or wrong."));

const screens = computed(() => store.quiz?.settings?.screens ?? {});
const introShowMeta = computed(() => screens.value.intro_show_meta !== false);

// Auto-advance — single/true-false questions advance immediately when answered.
// Stored at settings.question.auto_advance; defaults to on (matches frontend default).
const questionSettings = computed(() => store.quiz?.settings?.question ?? {});
const autoAdvance = computed(() => questionSettings.value.auto_advance !== false);

function onIntroShowMetaToggle(next) {
  store.stageSettings({ screens: { ...screens.value, intro_show_meta: !!next } });
}

function onAutoAdvanceToggle(next) {
  store.stageSettings({ question: { ...questionSettings.value, auto_advance: Boolean(next) } });
}

// Review answers — shown on result screen for trivia quizzes.
// Stored at settings.result.review_answers; defaults to on (matches frontend default).
const resultSettings = computed(() => store.quiz?.settings?.result ?? {});
const reviewAnswers = computed(() => resultSettings.value.review_answers !== false);

function onReviewAnswersToggle(next) {
  store.stageSettings({ result: { ...resultSettings.value, review_answers: Boolean(next) } });
}

function proToast(label) {
  toast.push({
    variant: 'info',
    title: __('Pro feature'),
    // translators: %s is the name of the Pro feature, e.g. "Progress bar".
    message: sprintf(__('%s requires Quizably Pro.'), label),
  });
}

// Pro-gated quiz settings — read from store.quiz.settings when Pro is on,
// fall back to safe defaults so the toggles don't flash incorrect state.
const quizTimer = computed(() => {
  const t = store.quiz?.settings?.timer;
  return t && typeof t === 'object' ? t : {};
});
const globalTimerEnabled = computed(() => Boolean(quizTimer.value.enabled));
const globalTimerSeconds = computed(() => {
  const n = Number(quizTimer.value.seconds);
  return Number.isFinite(n) && n > 0 ? n : 600;
});
const perQuestionTimerMode = computed(() => quizTimer.value.mode === 'per_question');

const randomizeQuestions = computed(() => Boolean(store.quiz?.settings?.randomize_questions));

const quizProgress = computed(() => {
  const p = store.quiz?.settings?.progress_bar;
  return p && typeof p === 'object' ? p : {};
});
const progressBarEnabled = computed(() => Boolean(quizProgress.value.enabled));

function onGlobalTimerToggle(next) {
  store.stageSettings({
    timer: {
      enabled: Boolean(next),
      mode: quizTimer.value.mode || 'global',
      seconds: globalTimerSeconds.value,
    },
  });
}

function onGlobalTimerSeconds(next) {
  const parsed = Math.max(5, Math.min(3600 * 4, Math.floor(Number(next) || 600)));
  store.stageSettings({
    timer: {
      enabled: globalTimerEnabled.value,
      mode: quizTimer.value.mode || 'global',
      seconds: parsed,
    },
  });
}

function onPerQuestionTimerToggle(next) {
  store.stageSettings({
    timer: {
      enabled: globalTimerEnabled.value,
      mode: next ? 'per_question' : 'global',
      seconds: globalTimerSeconds.value,
    },
  });
}

function onRandomizeQuestionsToggle(next) {
  store.stageSettings({ randomize_questions: Boolean(next) });
}

function onProgressBarToggle(next) {
  store.stageSettings({
    progress_bar: {
      enabled: Boolean(next),
      style: quizProgress.value.style || 'bar',
    },
  });
}

</script>

<style scoped>
.settings-scroll {
  height: 100%;
  overflow-y: auto;
  min-height: 0;
}

.settings {
  max-width: 760px;
  margin: 0 auto;
  padding: 28px 32px 48px;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.settings__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.settings__title {
  font-family: var(--f-display);
  font-size: 22px;
  font-weight: 500;
  letter-spacing: -0.01em;
  color: var(--ink-1);
  margin: 0;
  line-height: 1.2;
}

.settings__desc {
  font-size: 13.5px;
  line-height: 1.55;
  color: var(--ink-3);
  margin: 4px 0 0;
  max-width: 60ch;
}

/* Single panel hosts every block; they're separated by hairlines so
   the page reads as one coherent surface, not a list of bordered cards. */
.settings__panel {
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-xs);
}

.settings-block + .settings-block {
  border-top: 1px solid var(--border-1);
}

.settings-block__head {
  padding: 18px 22px 8px;
}

.settings-block__title {
  display: block;
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
  letter-spacing: 0;
}

.settings-block__sub {
  display: block;
  margin-top: 2px;
  font-size: 12.5px;
  color: var(--ink-3);
  line-height: 1.5;
}

.settings-block__body {
  padding: 4px 22px 22px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.settings-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.settings-field__label {
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
}

.settings-hint {
  margin: 4px 0 0;
  font-size: 11.5px;
  color: var(--ink-3);
  line-height: 1.4;
}

/* Segmented placement selector. Includes a Pro option that shows a
   pill instead of being disabled — clicking it surfaces the upgrade
   toast (matches the "available with upgrade" treatment elsewhere). */
.settings-seg {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  padding: 2px;
  gap: 2px;
}

.settings-seg__opt {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 6px;
  border: 0;
  border-radius: var(--r-xs);
  background: transparent;
  font-family: inherit;
  font-size: 12.5px;
  font-weight: 500;
  color: var(--ink-3);
  cursor: pointer;
  transition: background 150ms, color 150ms;
}

.settings-seg__opt:hover:not(.is-active) {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

.settings-seg__opt.is-active {
  background: var(--bg-surface);
  color: var(--ink-1);
  box-shadow: var(--shadow-xs);
}

.settings-seg__opt.is-pro {
  color: var(--ink-3);
}

.settings-seg__pill {
  font-size: 9px;
}

/* Field chips — checkboxes wrapped in pills */
.settings-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.settings-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-pill);
  font-size: 13px;
  color: var(--ink-2);
  cursor: pointer;
  transition: border-color 150ms, background 150ms;
}

.settings-chip:hover {
  border-color: var(--border-3);
}

.settings-chip.is-pro {
  background: transparent;
  border-style: dashed;
  border-color: var(--border-2);
  color: var(--ink-3);
  cursor: default;
}

/* Generic row used for toggles + Pro rows */
.settings-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  background: var(--bg-canvas);
  border-radius: var(--r-sm);
  font-size: 13px;
  color: var(--ink-2);
}

.settings-row.is-pro {
  background: transparent;
  border: 1px dashed var(--border-2);
  color: var(--ink-3);
}

.settings-row--inline {
  padding: 4px 12px 8px;
  background: transparent;
}

.settings-row__sublabel {
  font-size: 12px;
  color: var(--ink-3);
}

.settings-row__seconds {
  width: 110px;
  flex: 0 0 auto;
}

.settings-row__seconds :deep(.quizably-input__control) {
  text-align: end;
  padding: 6px 10px;
  font-variant-numeric: tabular-nums;
}

.settings-row__label {
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

@media (max-width: 640px) {
  .settings-seg {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
