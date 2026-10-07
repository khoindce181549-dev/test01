<template>
  <Teleport to="body">
    <Transition name="ldp-overlay">
      <div v-if="lead" class="ldp-overlay" @click.self="emit('close')" />
    </Transition>
    <Transition name="ldp-panel">
      <aside v-if="lead" class="ldp-panel" role="dialog" aria-modal="true" :aria-label="__('Lead detail')">
        <!-- Header -->
        <div class="ldp-panel__header">
          <div class="ldp-panel__header-info">
            <span class="ldp-panel__avatar" aria-hidden="true">{{ initials }}</span>
            <div>
              <p class="ldp-panel__name">{{ lead.name || lead.email || '—' }}</p>
              <p class="ldp-panel__email">{{ lead.email || '—' }}</p>
            </div>
          </div>
          <button class="ldp-panel__close" type="button" :aria-label="__('Close')" @click="emit('close')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <!-- Loading overlay -->
        <div v-if="loading" class="ldp-panel__loading">
          <span class="ldp-panel__spinner" aria-hidden="true" />
          <span>{{ __('Loading submission…') }}</span>
        </div>

        <div v-else-if="error" class="ldp-panel__error">
          {{ error }}
        </div>

        <div v-else class="ldp-panel__body">
          <!-- Contact info section -->
          <section class="ldp-section">
            <h3 class="ldp-section__title">{{ __('Contact') }}</h3>
            <dl class="ldp-fields">
              <div class="ldp-fields__row">
                <dt>{{ __('Email') }}</dt>
                <dd>{{ detail?.lead?.email || lead.email || '—' }}</dd>
              </div>
              <div class="ldp-fields__row">
                <dt>{{ __('Name') }}</dt>
                <dd>{{ detail?.lead?.name || lead.name || '—' }}</dd>
              </div>
              <div v-if="detail?.lead?.phone" class="ldp-fields__row">
                <dt>{{ __('Phone') }}</dt>
                <dd>{{ detail.lead.phone }}</dd>
              </div>
              <div class="ldp-fields__row">
                <dt>{{ __('Submitted') }}</dt>
                <dd>{{ formatTime(detail?.lead?.created_at || lead.created_at) }}</dd>
              </div>
              <div v-if="detail?.lead?.consent_gdpr != null" class="ldp-fields__row">
                <dt>{{ __('GDPR consent') }}</dt>
                <dd>{{ detail.lead.consent_gdpr ? __('Yes') : __('No') }}</dd>
              </div>
              <div v-if="detail?.lead?.double_optin_verified != null" class="ldp-fields__row">
                <dt>{{ __('Double opt-in') }}</dt>
                <dd>{{ detail.lead.double_optin_verified ? __('Verified') : __('Pending') }}</dd>
              </div>
            </dl>

            <!-- Extra custom fields -->
            <template v-if="extraFields.length">
              <h4 class="ldp-section__subtitle">{{ __('Custom fields') }}</h4>
              <dl class="ldp-fields">
                <div v-for="f in extraFields" :key="f.key" class="ldp-fields__row">
                  <dt>{{ f.key }}</dt>
                  <dd>{{ f.value }}</dd>
                </div>
              </dl>
            </template>

            <!-- Integration sync -->
            <template v-if="syncEntries.length">
              <h4 class="ldp-section__subtitle">{{ __('Integration sync') }}</h4>
              <div class="ldp-syncs">
                <Badge
                  v-for="s in syncEntries"
                  :key="s.integration"
                  :variant="syncVariant(s.status)"
                  size="sm"
                >
                  {{ s.integration }}: {{ s.status }}
                </Badge>
              </div>
            </template>
          </section>

          <!-- Quiz + result section -->
          <section v-if="detail" class="ldp-section">
            <h3 class="ldp-section__title">{{ __('Quiz result') }}</h3>
            <dl class="ldp-fields">
              <div v-if="detail.quiz" class="ldp-fields__row">
                <dt>{{ __('Quiz') }}</dt>
                <dd>{{ detail.quiz.title }} <span class="ldp-fields__tag">{{ detail.quiz.type }}</span></dd>
              </div>
              <template v-if="detail.submission">
                <div v-if="detail.submission.score != null" class="ldp-fields__row">
                  <dt>{{ __('Score') }}</dt>
                  <dd class="ldp-fields__score">{{ detail.submission.score }}</dd>
                </div>
                <div v-if="detail.submission.result" class="ldp-fields__row">
                  <dt>{{ __('Result') }}</dt>
                  <dd>
                    <strong>{{ detail.submission.result.title }}</strong>
                    <p v-if="detail.submission.result.description" class="ldp-fields__result-desc">
                      {{ detail.submission.result.description }}
                    </p>
                  </dd>
                </div>
                <div class="ldp-fields__row">
                  <dt>{{ __('Completed') }}</dt>
                  <dd>{{ formatTime(detail.submission.completed_at) }}</dd>
                </div>
              </template>
            </dl>

            <!-- UTM -->
            <template v-if="utmEntries.length">
              <h4 class="ldp-section__subtitle">{{ __('UTM parameters') }}</h4>
              <dl class="ldp-fields">
                <div v-for="u in utmEntries" :key="u.key" class="ldp-fields__row">
                  <dt>{{ u.key }}</dt>
                  <dd class="ldp-fields__mono">{{ u.value }}</dd>
                </div>
              </dl>
            </template>

            <!-- No submission -->
            <p v-if="!detail.submission" class="ldp-panel__empty">
              {{ __('No completed submission found for this lead.') }}
            </p>
          </section>

          <!-- Answers section -->
          <section v-if="detail?.submission?.answers?.length" class="ldp-section">
            <h3 class="ldp-section__title">{{ __('Answers') }} <span class="ldp-section__count">{{ detail.submission.answers.length }}</span></h3>
            <div class="ldp-answers">
              <div
                v-for="(ans, i) in detail.submission.answers"
                :key="ans.question_id"
                class="ldp-answer"
                :class="{
                  'ldp-answer--correct': ans.is_correct === true,
                  'ldp-answer--wrong':   ans.is_correct === false,
                }"
              >
                <div class="ldp-answer__meta">
                  <span class="ldp-answer__num">{{ i + 1 }}</span>
                  <span class="ldp-answer__type">{{ formatType(ans.question_type) }}</span>
                  <span v-if="ans.is_correct === true" class="ldp-answer__flag ldp-answer__flag--correct" :title="__('Correct')">✓</span>
                  <span v-else-if="ans.is_correct === false" class="ldp-answer__flag ldp-answer__flag--wrong" :title="__('Incorrect')">✗</span>
                </div>
                <p class="ldp-answer__question">{{ ans.question_title }}</p>

                <!-- Text answer -->
                <p v-if="ans.text_value" class="ldp-answer__text-value">
                  {{ quotedText(ans.text_value) }}
                </p>

                <!-- Choice answers -->
                <ul v-if="ans.chosen_answers?.length" class="ldp-answer__choices">
                  <li
                    v-for="c in ans.chosen_answers"
                    :key="c.id"
                    class="ldp-answer__choice"
                    :class="{
                      'ldp-answer__choice--correct': c.is_correct === true,
                      'ldp-answer__choice--wrong':   c.is_correct === false,
                    }"
                  >
                    <span class="ldp-answer__choice-dot" aria-hidden="true"/>
                    {{ c.label }}
                    <span v-if="c.is_correct === true" class="ldp-answer__choice-mark" aria-label="correct">✓</span>
                    <span v-else-if="c.is_correct === false" class="ldp-answer__choice-mark" aria-label="incorrect">✗</span>
                  </li>
                </ul>

                <p v-if="ans.time_spent_ms" class="ldp-answer__time">
                  {{ formatDuration(ans.time_spent_ms) }}
                </p>
              </div>
            </div>
          </section>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup>
import { __, sprintf } from '@shared/i18n';
import { computed, ref, watch } from 'vue';
import { Badge } from '@admin/ui';
import { api } from '@admin/api/client';

const props = defineProps({
  lead: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const loading = ref(false);
const error = ref(null);
const detail = ref(null);

watch(() => props.lead, async (lead) => {
  if (!lead) { detail.value = null; error.value = null; return; }
  loading.value = true;
  error.value = null;
  detail.value = null;
  try {
    detail.value = await api.get(`leads/${lead.id}/detail`);
  } catch (e) {
    error.value = e.message || __('Failed to load submission detail.');
  } finally {
    loading.value = false;
  }
}, { immediate: true });

const initials = computed(() => {
  const src = props.lead?.name || props.lead?.email || '?';
  return src.slice(0, 2).toUpperCase();
});

const extraFields = computed(() => {
  const raw = detail.value?.lead?.extra_fields;
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return [];
  return Object.entries(raw).map(([key, value]) => ({
    key,
    value: typeof value === 'object' ? JSON.stringify(value) : String(value ?? ''),
  }));
});

const syncEntries = computed(() => {
  const raw = detail.value?.lead?.integration_sync_status || props.lead?.integration_sync_status;
  if (!raw || typeof raw !== 'object') return [];
  return Object.entries(raw).map(([integration, status]) => ({
    integration,
    status: typeof status === 'string' ? status : String(status),
  }));
});

const utmEntries = computed(() => {
  const utm = detail.value?.submission?.utm;
  if (!utm || typeof utm !== 'object') return [];
  return Object.entries(utm)
    .filter(([, v]) => v)
    .map(([key, value]) => ({ key, value: String(value) }));
});

function syncVariant(status) {
  if (['synced', 'ok', 'success'].includes(status)) return 'success';
  if (['failed', 'error'].includes(status)) return 'danger';
  if (['pending', 'queued'].includes(status)) return 'info';
  return 'neutral';
}

function formatTime(isoish) {
  if (!isoish) return '—';
  const d = new Date(isoish);
  if (!Number.isFinite(d.getTime())) return '—';
  return `${d.toLocaleDateString()} ${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
}

// translators: %s is the text a visitor typed as their answer; it is shown in quotation marks.
const quotedText = (text) => sprintf(__('"%s"'), text);

function formatDuration(ms) {
  if (!ms) return '';
  const s = Math.round(ms / 1000);
  if (s < 60) return `${s}s`;
  return `${Math.floor(s / 60)}m ${s % 60}s`;
}

function formatType(type) {
  const map = {
    single: __('Single choice'),
    multiple: __('Multiple choice'),
    text: __('Text'),
    image: __('Image choice'),
    dropdown: __('Dropdown'),
    slider: __('Slider'),
    rating: __('Rating'),
    boolean: __('Yes / No'),
  };
  return map[type] || type;
}
</script>

<style scoped>
/* ── overlay ─────────────────────────────────────────────────────────────── */
.ldp-overlay {
  position: fixed;
  top: 32px;
  inset-inline-end: 0;
  bottom: 0;
  inset-inline-start: 0;
  z-index: 9000;
  background: rgba(0, 0, 0, 0.35);
  backdrop-filter: blur(2px);
}

.ldp-overlay-enter-active,
.ldp-overlay-leave-active { transition: opacity 200ms ease; }
.ldp-overlay-enter-from,
.ldp-overlay-leave-to { opacity: 0; }

/* ── panel ───────────────────────────────────────────────────────────────── */
.ldp-panel {
  position: fixed;
  top: 32px;
  inset-inline-end: 0;
  bottom: 0;
  z-index: 9001;
  width: 480px;
  max-width: 100vw;
  background: var(--bg-surface);
  border-inline-start: 1px solid var(--border-1);
  box-shadow: -8px 0 32px rgba(0, 0, 0, 0.14);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.ldp-panel-enter-active,
.ldp-panel-leave-active { transition: transform 240ms cubic-bezier(0.4, 0, 0.2, 1); }
.ldp-panel-enter-from,
.ldp-panel-leave-to { transform: translateX(100%); }
.ldp-panel:dir(rtl) { box-shadow: 8px 0 32px rgba(0, 0, 0, 0.14); }
.ldp-panel-enter-from:dir(rtl),
.ldp-panel-leave-to:dir(rtl) { transform: translateX(-100%); }

/* ── header ──────────────────────────────────────────────────────────────── */
.ldp-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-1);
  background: var(--bg-canvas);
  flex-shrink: 0;
}

.ldp-panel__header-info {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.ldp-panel__avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: color-mix(in srgb, var(--brand) 15%, transparent);
  color: var(--brand);
  font-family: var(--f-mono);
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  letter-spacing: 0.04em;
}

.ldp-panel__name {
  font-family: var(--f-sans);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-1);
  margin: 0 0 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ldp-panel__email {
  font-family: var(--f-mono);
  font-size: 11.5px;
  color: var(--ink-4);
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ldp-panel__close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid var(--border-1);
  border-radius: var(--r-sm);
  background: var(--bg-canvas);
  color: var(--ink-3);
  cursor: pointer;
  flex-shrink: 0;
  transition: background 100ms, color 100ms;
}

.ldp-panel__close:hover {
  background: var(--bg-subtle);
  color: var(--ink-1);
}

/* ── loading / error / empty ─────────────────────────────────────────────── */
.ldp-panel__loading {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 32px 24px;
  color: var(--ink-3);
  font-size: 13px;
}

.ldp-panel__spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid var(--border-2);
  border-top-color: var(--brand);
  border-radius: 50%;
  animation: ldp-spin 700ms linear infinite;
}

@keyframes ldp-spin { to { transform: rotate(360deg); } }

@media (prefers-reduced-motion: reduce) {
  .ldp-panel__spinner { animation-duration: 0ms; }
}

.ldp-panel__error {
  padding: 24px 20px;
  color: var(--danger, #dc2626);
  font-size: 13px;
}

.ldp-panel__empty {
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-4);
  font-style: italic;
  margin: 8px 0 0;
}

/* ── body ────────────────────────────────────────────────────────────────── */
.ldp-panel__body {
  flex: 1;
  overflow-y: auto;
  padding: 0;
}

/* ── section ─────────────────────────────────────────────────────────────── */
.ldp-section {
  padding: 18px 20px;
  border-bottom: 1px solid var(--border-1);
}

.ldp-section:last-child {
  border-bottom: 0;
}

.ldp-section__title {
  font-family: var(--f-mono);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-4);
  margin: 0 0 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.ldp-section__subtitle {
  font-family: var(--f-mono);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: var(--ink-4);
  margin: 14px 0 8px;
}

.ldp-section__count {
  background: var(--bg-subtle);
  border: 1px solid var(--border-1);
  border-radius: var(--r-pill);
  font-size: 10px;
  color: var(--ink-3);
  padding: 1px 6px;
  font-weight: 500;
}

/* ── fields ──────────────────────────────────────────────────────────────── */
.ldp-fields {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin: 0;
}

.ldp-fields__row {
  display: grid;
  grid-template-columns: 110px 1fr;
  gap: 8px;
  padding: 7px 0;
  border-bottom: 1px solid var(--border-1);
  align-items: baseline;
}

.ldp-fields__row:last-child {
  border-bottom: 0;
}

.ldp-fields__row dt {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-4);
  font-weight: 500;
  white-space: nowrap;
}

.ldp-fields__row dd {
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-1);
  margin: 0;
  overflow-wrap: break-word;
  min-width: 0;
}

.ldp-fields__mono {
  font-family: var(--f-mono) !important;
  font-size: 11.5px !important;
}

.ldp-fields__score {
  font-family: var(--f-display) !important;
  font-size: 18px !important;
  font-weight: 700 !important;
  color: var(--brand) !important;
  letter-spacing: -0.01em;
}

.ldp-fields__tag {
  display: inline-block;
  margin-inline-start: 6px;
  font-family: var(--f-mono);
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-4);
  background: var(--bg-subtle);
  border: 1px solid var(--border-1);
  border-radius: 3px;
  padding: 1px 4px;
  vertical-align: middle;
}

.ldp-fields__result-desc {
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--ink-3);
  margin: 4px 0 0;
  line-height: 1.5;
}

/* ── syncs ───────────────────────────────────────────────────────────────── */
.ldp-syncs {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

/* ── answers ─────────────────────────────────────────────────────────────── */
.ldp-answers {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ldp-answer {
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
  padding: 12px 14px;
  transition: border-color 100ms;
}

.ldp-answer--correct {
  border-color: color-mix(in srgb, #16a34a 35%, transparent);
  background: color-mix(in srgb, #16a34a 4%, var(--bg-canvas));
}

.ldp-answer--wrong {
  border-color: color-mix(in srgb, #dc2626 30%, transparent);
  background: color-mix(in srgb, #dc2626 3%, var(--bg-canvas));
}

.ldp-answer__meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
}

.ldp-answer__num {
  font-family: var(--f-mono);
  font-size: 10px;
  font-weight: 700;
  color: var(--ink-4);
  background: var(--bg-subtle);
  border: 1px solid var(--border-1);
  border-radius: 3px;
  padding: 1px 5px;
  line-height: 1.5;
}

.ldp-answer__type {
  font-family: var(--f-mono);
  font-size: 10px;
  color: var(--ink-4);
  letter-spacing: 0.03em;
}

.ldp-answer__flag {
  margin-inline-start: auto;
  font-size: 13px;
  font-weight: 700;
  line-height: 1;
}

.ldp-answer__flag--correct { color: #16a34a; }
.ldp-answer__flag--wrong   { color: #dc2626; }

.ldp-answer__question {
  font-family: var(--f-sans);
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-1);
  margin: 0 0 8px;
  line-height: 1.4;
}

.ldp-answer__text-value {
  font-family: var(--f-sans);
  font-size: 13px;
  color: var(--ink-2);
  font-style: italic;
  margin: 0 0 4px;
  padding: 6px 10px;
  background: var(--bg-subtle);
  border-radius: var(--r-sm);
  border-inline-start: 3px solid var(--brand);
}

.ldp-answer__choices {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.ldp-answer__choice {
  display: flex;
  align-items: center;
  gap: 7px;
  font-family: var(--f-sans);
  font-size: 12.5px;
  color: var(--ink-2);
  padding: 4px 8px;
  border-radius: var(--r-sm);
  background: var(--bg-subtle);
}

.ldp-answer__choice--correct {
  background: color-mix(in srgb, #16a34a 8%, transparent);
  color: #15803d;
}

.ldp-answer__choice--wrong {
  background: color-mix(in srgb, #dc2626 7%, transparent);
  color: #b91c1c;
}

.ldp-answer__choice-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
  opacity: 0.5;
}

.ldp-answer__choice-mark {
  margin-inline-start: auto;
  font-size: 12px;
  font-weight: 700;
}

.ldp-answer__time {
  font-family: var(--f-mono);
  font-size: 10.5px;
  color: var(--ink-4);
  margin: 6px 0 0;
  text-align: end;
}
</style>
