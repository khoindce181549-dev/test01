<template>
  <div class="quizably-media-picker">
    <label
      v-if="label"
      class="quizably-media-picker__label"
    >{{ label }}</label>

    <!-- Compact horizontal row: thumb on left, name + actions on right. -->
    <div
      v-if="modelValue"
      class="quizably-media-picker__row"
    >
      <div class="quizably-media-picker__thumb">
        <img
          v-if="isImage"
          :src="modelValue"
          alt=""
          loading="lazy"
        >
        <svg
          v-else
          viewBox="0 0 24 24"
          width="20"
          height="20"
          fill="none"
          stroke="currentColor"
          stroke-width="1.6"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
          <path d="M14 2v6h6" />
        </svg>
      </div>
      <div class="quizably-media-picker__meta">
        <span class="quizably-media-picker__filename">{{ filename }}</span>
      </div>
      <div class="quizably-media-picker__actions">
        <button
          type="button"
          class="quizably-media-picker__btn"
          :disabled="disabled"
          @click="open"
        >
          {{ replaceText }}
        </button>
        <button
          type="button"
          class="quizably-media-picker__btn quizably-media-picker__btn--ghost"
          :disabled="disabled"
          :aria-label="__('Remove media')"
          @click="$emit('update:modelValue', '')"
        >
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>
    </div>

    <button
      v-else
      type="button"
      class="quizably-media-picker__dropzone"
      :disabled="disabled"
      @click="open"
    >
      <span class="quizably-media-picker__dropzone-icon">
        <svg
          viewBox="0 0 24 24"
          width="18"
          height="18"
          fill="none"
          stroke="currentColor"
          stroke-width="1.6"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <rect x="3" y="3" width="18" height="18" rx="2" />
          <circle cx="9" cy="9" r="2" />
          <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
        </svg>
      </span>
      <span class="quizably-media-picker__cta">{{ buttonText || __('Choose from media library') }}</span>
      <span class="quizably-media-picker__hint">{{ __('PNG · JPG · SVG') }}</span>
    </button>

    <p
      v-if="helperText"
      class="quizably-media-picker__help"
    >
      {{ helperText }}
    </p>
  </div>
</template>

<script setup>
import { __ } from '@shared/i18n';
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  helperText: { type: String, default: '' },
  type: { type: String, default: 'image' }, // 'image' | 'any'
  multiple: { type: Boolean, default: false },
  buttonText: { type: String, default: '' },
  replaceText: { type: String, default: () => __('Replace') },
  removeText: { type: String, default: () => __('Remove') },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const isImage = computed(() => {
  if (!props.modelValue) return false;
  return /\.(png|jpe?g|gif|webp|svg|avif)(\?|#|$)/i.test(props.modelValue);
});

const filename = computed(() => {
  if (!props.modelValue) return '';
  try {
    const url = new URL(props.modelValue, window.location.origin);
    return url.pathname.split('/').filter(Boolean).pop() || props.modelValue;
  } catch {
    return props.modelValue;
  }
});

let frame = null;

/**
 * Opens the WP media modal (Backbone). Falls back to a window.prompt when
 * `wp.media` isn't loaded — tests run under jsdom without WP scripts, and
 * we don't want the picker to crash there.
 */
function open() {
  if (props.disabled) return;
  if (typeof window === 'undefined' || !window.wp || !window.wp.media) {
    const url = window.prompt(__('Enter media URL'), props.modelValue || '');
    if (url !== null) emit('update:modelValue', url);
    return;
  }
  if (!frame) {
    frame = window.wp.media({
      title: props.label || __('Select media'),
      button: { text: props.buttonText || __('Use this') },
      library: props.type === 'any' ? {} : { type: 'image' },
      multiple: props.multiple,
    });
    frame.on('select', () => {
      const att = frame.state().get('selection').first().toJSON();
      emit('update:modelValue', att.url);
    });
  }
  frame.open();
}
</script>

<style scoped>
.quizably-media-picker {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-width: 420px;
}

.quizably-media-picker__label {
  font-size: 12.5px;
  font-weight: 500;
  color: var(--ink-2);
}

/* Compact horizontal row when a value is set. */
.quizably-media-picker__row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-block: 6px 6px;
  padding-inline: 6px 8px;
  background: var(--bg-surface);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
}

.quizably-media-picker__thumb {
  flex: 0 0 auto;
  width: 44px;
  height: 44px;
  border-radius: var(--r-sm);
  overflow: hidden;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  display: grid;
  place-items: center;
  color: var(--ink-3);
}

.quizably-media-picker__thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  background: var(--bg-surface);
}

.quizably-media-picker__meta {
  flex: 1;
  min-width: 0;
}

.quizably-media-picker__filename {
  display: block;
  font-family: var(--f-mono);
  font-size: 11.5px;
  color: var(--ink-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.quizably-media-picker__actions {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.quizably-media-picker__btn {
  height: 28px;
  padding: 0 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border-2);
  background: var(--bg-surface);
  color: var(--ink-1);
  border-radius: var(--r-sm);
  font: inherit;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: background 120ms, border-color 120ms, color 120ms;
}

.quizably-media-picker__btn:hover:not(:disabled) {
  background: var(--bg-canvas);
  border-color: var(--brand);
  color: var(--brand-hover);
}

.quizably-media-picker__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quizably-media-picker__btn--ghost {
  width: 28px;
  padding: 0;
  border-color: transparent;
  background: transparent;
  color: var(--ink-3);
}

.quizably-media-picker__btn--ghost:hover:not(:disabled) {
  background: var(--bg-canvas);
  color: var(--danger);
  border-color: transparent;
}

/* Dropzone (no value): compact horizontal click target. */
.quizably-media-picker__dropzone {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  background: var(--bg-surface);
  border: 1.5px dashed var(--border-2);
  border-radius: var(--r-md);
  color: var(--ink-3);
  cursor: pointer;
  transition: border-color 150ms, background 150ms, color 150ms;
  font: inherit;
  text-align: start;
}

.quizably-media-picker__dropzone:hover:not(:disabled) {
  border-color: var(--brand);
  background: var(--brand-tint, color-mix(in srgb, var(--brand) 5%, var(--bg-surface)));
  color: var(--brand-hover);
}

.quizably-media-picker__dropzone:focus-visible {
  outline: 2px solid var(--brand);
  outline-offset: 2px;
}

.quizably-media-picker__dropzone:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.quizably-media-picker__dropzone-icon {
  flex: 0 0 auto;
  width: 32px;
  height: 32px;
  border-radius: var(--r-sm);
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  display: grid;
  place-items: center;
  color: var(--ink-3);
}

.quizably-media-picker__dropzone:hover:not(:disabled) .quizably-media-picker__dropzone-icon {
  border-color: var(--brand);
  color: var(--brand);
}

.quizably-media-picker__cta {
  font-size: 13px;
  font-weight: 500;
  color: var(--ink-1);
  flex: 1;
}
.quizably-media-picker__dropzone:hover:not(:disabled) .quizably-media-picker__cta {
  color: var(--brand-hover);
}

.quizably-media-picker__hint {
  font-family: var(--f-mono);
  font-size: 10.5px;
  letter-spacing: 0.04em;
  color: var(--ink-4);
  flex: 0 0 auto;
}

.quizably-media-picker__help {
  font-size: 11.5px;
  color: var(--ink-4);
  margin: 0;
}
</style>
