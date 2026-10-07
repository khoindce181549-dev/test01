<template>
  <section :class="['quizably-form', `quizably-form--align-${formAlign}`]">
    <!-- Per-screen custom background is applied at the quiz root level by
         Quiz.vue (via .quizably-quiz__bg / .quizably-quiz__bg-overlay). Rendering it
         again here would double the image inside the form card. -->

    <!-- Eyebrow + title are one heading unit: they get their own tight gap
         instead of the section's looser inter-block rhythm. -->
    <div class="quizably-form__head">
      <p class="eyebrow quizably-form__eyebrow">
        {{ copy.eyebrow }}
      </p>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <h2
        class="quizably-form__title"
        v-html="title || ''"
      />
    </div>

    <!-- Image slot — inline after title, three-branch fit rendering -->
    <figure
      v-if="coverUrl && imageFit === 'contain'"
      class="quizably-form__image"
      :style="imageHeightStyle"
    >
      <img :src="coverUrl" :alt="__('Form image')" class="quizably-form__image-img">
    </figure>
    <div
      v-else-if="coverUrl && imageFit === 'cover'"
      class="quizably-form__image quizably-form__image--bg"
      :style="{ ...imageHeightStyle, backgroundImage: `url(${coverUrl})` }"
      role="img"
      :aria-label="__('Form image')"
    />
    <div
      v-else-if="coverUrl && imageFit === 'repeat'"
      class="quizably-form__image quizably-form__image--repeat"
      :style="{ ...imageHeightStyle, backgroundImage: `url(${coverUrl})` }"
      role="img"
      :aria-label="__('Form image')"
    />
    <!-- Description is author-authored HTML from the admin contenteditable.
         Sanitization happens in the admin editor (wp_kses on save). -->
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div
      v-if="description"
      class="quizably-form__desc"
      v-html="description"
    />

    <form
      class="quizably-form__form"
      @submit.prevent="onSubmit"
    >
      <label
        v-if="fields.includes('name')"
        class="quizably-form__field"
      >
        <span class="quizably-form__label">{{ __('Name') }}</span>
        <input
          v-model="form.name"
          type="text"
          autocomplete="name"
          :required="requiredFields.includes('name')"
        >
      </label>

      <label
        v-if="fields.includes('email')"
        class="quizably-form__field"
      >
        <span class="quizably-form__label">{{ __('Email') }}</span>
        <input
          v-model="form.email"
          type="email"
          autocomplete="email"
          :required="requiredFields.includes('email')"
        >
      </label>

      <label
        v-if="gdprRequired"
        class="quizably-form__consent"
      >
        <span class="quizably-form__checkbox">
          <input
            v-model="form.consent"
            class="quizably-form__checkbox-input"
            type="checkbox"
            :required="gdprRequired"
          >
          <span class="quizably-form__checkbox-box" aria-hidden="true">
            <svg viewBox="0 0 10 8" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="1,4 3.5,6.5 9,1" />
            </svg>
          </span>
        </span>
        <span>{{ consentLabel }}</span>
      </label>

      <p
        v-if="error"
        class="quizably-form__error"
        role="alert"
      >
        {{ error }}
      </p>

      <div class="quizably-form__actions">
        <QuizButton
          variant="ghost"
          @click="$emit('nav', 'prev')"
        >
          {{ __('Back') }}
        </QuizButton>
        <div class="quizably-form__actions-end">
          <!-- Only an "optional" form can be declined. A gate form has no skip. -->
          <QuizButton
            v-if="skippable"
            variant="outline"
            class="quizably-form__skip"
            @click="$emit('nav', 'skip')"
          >
            {{ __('Skip') }}
          </QuizButton>
          <QuizButton
            variant="primary"
            size="lg"
            type="submit"
          >
            {{ submitLabel }}
          </QuizButton>
        </div>
      </div>
    </form>
  </section>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed, reactive, ref } from 'vue';
import QuizButton from '../components/QuizButton.vue';
import { formCopyFor, resolveFormPlacement, resolveFormSkippable } from '@shared/formPlacement.js';

const props = defineProps({
  quiz: { type: Object, required: true },
});

const emit = defineEmits(['nav']);

const settings = computed(() => props.quiz?.settings?.optin ?? {});
const screens = computed(() => props.quiz?.settings?.screens ?? {});

// An optional form offers a Skip: the visitor can carry on without filling it
// in (to the first question if the form is at the start, to the result if it's
// at the end). Same rule the flow enforces in useQuizFlow.skipForm().
const skippable = computed(() => resolveFormSkippable(props.quiz?.settings?.optin));

// Default wording depends on where the form sits: a form shown BEFORE the quiz
// must not say "Get your result" / "See my result" - the quiz hasn't started.
// The author's own title / button label always wins.
const copy = computed(() => formCopyFor(resolveFormPlacement(props.quiz?.settings?.optin)));

// ── Alignment — set by FormProperties (formerly OptinProperties) sidebar ──────
// `screens.optin_align` is the actual stored key name (kept for compatibility
// with every already-saved quiz — see FormProperties.vue / Schema.php).
const formAlign = computed(() => {
  const v = screens.value.optin_align;
  return v === 'left' || v === 'right' ? v : 'center';
});

const fields = computed(() => {
  const raw = settings.value.fields ?? ['name', 'email'];
  const arr = Array.isArray(raw) ? raw : ['name', 'email'];
  // email is always present, matching admin behaviour
  return Array.from(new Set([...arr, 'email']));
});

// Derive required fields from field_config (matches admin data structure).
// Legacy quizzes may store a required_fields array — prefer that if present.
const requiredFields = computed(() => {
  if (Array.isArray(settings.value.required_fields)) {
    return settings.value.required_fields;
  }
  const cfg = settings.value.field_config ?? {};
  return fields.value.filter((id) => {
    if (id === 'email') return true; // email is always required
    return cfg[id]?.required !== false; // all other fields default to required
  });
});

// A blank title (the admin editor stores an empty string when the heading was
// never edited) reads as "not set", so the default for this position applies.
const title = computed(() => {
  const t = settings.value.title;
  return String(t ?? '').replace(/<[^>]*>|&nbsp;/g, '').trim() ? t : copy.value.title;
});
// Description is stored as HTML from the admin's contenteditable field.
const description = computed(() => settings.value.description ?? '');
const coverUrl = computed(() => settings.value.image_url ?? '');

const imageFit = computed(() => {
  const v = settings.value.image_fit;
  return v === 'cover' || v === 'repeat' ? v : 'contain';
});

const DEFAULT_FORM_IMAGE_HEIGHT = 220;
const imageHeightStyle = computed(() => {
  const h = Number(settings.value.image_height);
  return Number.isFinite(h) && h > 0
    ? { height: `${h}px` }
    : { height: `${DEFAULT_FORM_IMAGE_HEIGHT}px` };
});

// Per-screen background is rendered by Quiz.vue (.quizably-quiz__bg) — no local layers needed.
const submitLabel = computed(() => settings.value.submit_label || copy.value.submit);

// Admin stores GDPR text in `gdpr_text`; fall back to legacy `consent_label` key.
const consentText = computed(() => settings.value.gdpr_text ?? settings.value.consent_label ?? '');
const consentLabel = computed(() => consentText.value || __('I agree to receive the result and related emails.'));
// Show the checkbox whenever consent text is configured — matches admin hint:
// "Empty = checkbox hidden on the live form."
const gdprRequired = computed(() => consentText.value.trim().length > 0);

const form = reactive({ name: '', email: '', consent: false });
const error = ref('');

function onSubmit() {
  error.value = '';
  if (requiredFields.value.includes('email') && !form.email) {
    error.value = __('Please enter your email.');
    return;
  }
  if (requiredFields.value.includes('name') && !form.name) {
    error.value = __('Please enter your name.');
    return;
  }
  if (gdprRequired.value && !form.consent) {
    error.value = __('Please accept consent to continue.');
    return;
  }
  // Send both `consent` (for the local flow.leadData) and `consent_gdpr`
  // (the field name the public REST endpoint expects) so the lead can be
  // captured server-side without further plumbing.
  emit('nav', 'form', {
    name: form.name,
    email: form.email,
    consent: form.consent,
    consent_gdpr: form.consent,
  });
}
</script>

<style scoped>
.quizably-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
  position: relative;
  isolation: isolate;
}

/* ── Alignment variants ──────────────────────────────────────────────────────── */
.quizably-form--align-left   { text-align: start;  align-items: flex-start; }
.quizably-form--align-center { text-align: center; align-items: center; }
.quizably-form--align-right  { text-align: end;    align-items: flex-end; }

/* Inline image slot — after title */
/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.quizably-form__image {
  width: 100%;
  border-radius: var(--r-md);
  border: 1px solid var(--border-1);
  overflow: hidden;
  flex-shrink: 0;
  margin: 0;
  align-self: stretch;
}

.quizably-form__image-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  background: var(--bg-subtle);
}

.quizably-form__image--bg {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-color: var(--bg-subtle);
}

.quizably-form__image--repeat {
  background-size: auto;
  background-repeat: repeat;
  background-position: top left; /* rtl-ok: tiled image pattern anchor; the artwork is never mirrored */
  background-color: var(--bg-subtle);
}

/* Heading unit (eyebrow + title). align-items: inherit hands the section's
   left/center/right alignment down to the children, so a template that
   shrink-wraps the title (e.g. Conversational's chat bubble) still does. */
.quizably-form__head {
  display: flex;
  flex-direction: column;
  align-items: inherit;
  gap: 6px;
  align-self: stretch;
  width: 100%;
}

.quizably-form__eyebrow {
  margin: 0;
}

.quizably-form__title {
  font-family: var(--f-display);
  font-size: 32px;
  line-height: 1.1;
  letter-spacing: -0.02em;
  margin: 0;
}

/* align-self: stretch keeps this full-width regardless of the parent's align-items */
.quizably-form__desc {
  color: var(--quizably-quiz-text-muted, var(--ink-2));
  font-size: 15px;
  margin: 0;
  align-self: stretch;
  width: 100%;
}
.quizably-form__desc :deep(p) { margin: 0 0 8px; }
.quizably-form__desc :deep(p:last-child) { margin-bottom: 0; }
.quizably-form__desc :deep(strong) { font-weight: 700; }
.quizably-form__desc :deep(em) { font-style: italic; }
.quizably-form__desc :deep(u) { text-decoration: underline; }
.quizably-form__desc :deep(a) { color: var(--quizably-quiz-brand); text-decoration: underline; }

/* align-self: stretch keeps this full-width regardless of the parent's align-items.
   text-align: start ensures labels, inputs, and consent text always align to the reading-start side
   (left in LTR, right in RTL) even when the parent section uses center or end alignment for the title/description. */
.quizably-form__form {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-top: 8px;
  align-self: stretch;
  width: 100%;
  text-align: start;
}

.quizably-form__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.quizably-form__label {
  font-family: var(--f-mono);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--quizably-quiz-text-subtle, var(--ink-3));
}

.quizably-form__field input {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid var(--quizably-quiz-option-border, var(--border-2));
  border-radius: var(--r-md);
  background: var(--quizably-quiz-option-bg, var(--bg-surface));
  color: var(--quizably-quiz-text);
  font-size: 15px;
  transition: border-color 150ms ease, box-shadow 150ms ease;
}
.quizably-form__field input:focus {
  outline: none;
  border-color: var(--quizably-quiz-brand);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.quizably-form__consent {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 13px;
  color: var(--quizably-quiz-text-muted, var(--ink-2));
  line-height: 1.5;
  cursor: pointer;
}

/* Custom checkbox */
.quizably-form__checkbox {
  position: relative;
  flex-shrink: 0;
  margin-top: 1px;
}

.quizably-form__checkbox-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
  pointer-events: none;
}

.quizably-form__checkbox-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 17px;
  height: 17px;
  border-radius: 4px;
  border: 1.5px solid var(--border-3, #d1d5db);
  background: transparent;
  transition: background 150ms ease, border-color 150ms ease;
}

.quizably-form__checkbox-box svg {
  width: 10px;
  height: 8px;
  opacity: 0;
  transition: opacity 150ms ease;
}

.quizably-form__checkbox-input:checked ~ .quizably-form__checkbox-box {
  background: var(--quizably-quiz-brand, #4f46e5);
  border-color: var(--quizably-quiz-brand, #4f46e5);
}

.quizably-form__checkbox-input:checked ~ .quizably-form__checkbox-box svg {
  opacity: 1;
}

.quizably-form__error {
  color: var(--danger);
  font-size: 13px;
  margin: 0;
}

.quizably-form__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 12px;
  margin-top: 8px;
}

/* Skip + submit stay together at the inline-end; Back stays at the inline-start. */
.quizably-form__actions-end {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 12px;
  margin-inline-start: auto;
}
</style>
