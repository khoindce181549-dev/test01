<template>
  <Drawer
    :model-value="modelValue"
    :title="isEditing ? __('Edit bank question') : __('New bank question')"
    size="md"
    @update:model-value="onToggle"
  >
    <div class="qbe">
      <Input
        v-model="form.title"
        :label="__('Question text')"
        :placeholder="__('e.g. Which of these best describes your morning routine?')"
        required
        :error-text="errors.title"
      />

      <Textarea
        v-model="form.description"
        :label="__('Helper text (optional)')"
        :placeholder="__('A short hint shown under the question.')"
        rows="2"
      />

      <Select
        v-model="form.type"
        :label="__('Type')"
        :options="typeOptions"
        :helper-text="typeHelper"
      />

      <div class="qbe__answers">
        <div class="qbe__answers-head">
          <span>{{ __('Answers') }}</span>
          <span class="qbe__answers-meta">{{ answersCountText }}</span>
        </div>

        <div
          v-for="(a, i) in form.answers"
          :key="i"
          class="qbe__answer"
        >
          <span class="qbe__answer-num">{{ i + 1 }}</span>
          <Input
            v-model="a.label"
            :placeholder="answerPlaceholder(i)"
          />
          <label
            v-if="form.type !== 'multi'"
            class="qbe__answer-correct"
          >
            <input
              type="radio"
              :checked="a.is_correct"
              :name="`qbe-correct-${form.type}`"
              @change="markCorrect(i)"
            >
            <span>{{ __('Correct') }}</span>
          </label>
          <label
            v-else
            class="qbe__answer-correct"
          >
            <input
              v-model="a.is_correct"
              type="checkbox"
            >
            <span>{{ __('Correct') }}</span>
          </label>
          <button
            type="button"
            class="qbe__answer-del"
            :disabled="!canRemoveAnswer"
            :aria-label="__('Remove answer')"
            @click="removeAnswer(i)"
          >
            <svg
              viewBox="0 0 16 16"
              width="13"
              height="13"
              fill="none"
              stroke="currentColor"
              stroke-width="1.75"
              stroke-linecap="round"
            ><path d="M3.5 3.5l9 9M12.5 3.5l-9 9" /></svg>
          </button>
        </div>

        <button
          v-if="form.type !== 'truefalse'"
          type="button"
          class="qbe__answer-add"
          @click="addAnswer"
        >
          {{ __('+ Add answer') }}
        </button>
      </div>

      <div class="qbe__tags">
        <label
          for="qbe-tags-input"
          class="qbe__label"
        >{{ __('Tags') }}</label>
        <div class="qbe__tags-input">
          <span
            v-for="tag in form.tags"
            :key="tag"
            class="qbe__tag"
          >
            {{ tag }}
            <button
              type="button"
              :aria-label="__('Remove tag')"
              @click="removeTag(tag)"
            >×</button>
          </span>
          <input
            id="qbe-tags-input"
            v-model="tagInput"
            type="text"
            :placeholder="__('Add tag…')"
            @keydown.enter.prevent="commitTag"
            @keydown.,.prevent="commitTag"
            @blur="commitTag"
          >
        </div>
        <span class="qbe__hint">{{ __('Up to 10 tags. Press Enter or comma to add.') }}</span>
      </div>
    </div>

    <template #footer>
      <Button
        v-if="isEditing"
        variant="ghost"
        :disabled="saving"
        @click="onDelete"
      >
        {{ __('Delete') }}
      </Button>
      <span class="qbe__footer-spacer" />
      <Button
        variant="outline"
        :disabled="saving"
        @click="close"
      >
        {{ __('Cancel') }}
      </Button>
      <Button
        variant="primary"
        :loading="saving"
        :disabled="!canSave"
        @click="onSave"
      >
        {{ isEditing ? __('Save changes') : __('Add to bank') }}
      </Button>
    </template>
  </Drawer>
</template>

<script setup>
import { __, _n, sprintf } from '@shared/i18n';
import { computed, reactive, ref, watch } from 'vue';
import { Button, Drawer, Input, Select, Textarea, useToast } from '@admin/ui';
import { useQuestionBankStore } from '@admin/stores/questionBank';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  question: { type: Object, default: null },
});
const emit = defineEmits(['update:modelValue', 'saved']);

const store = useQuestionBankStore();
const toast = useToast();

const typeOptions = [
  { value: 'single', label: __('Single choice') },
  { value: 'multi', label: __('Multi choice') },
  { value: 'truefalse', label: __('True / false') },
];

const TYPE_HELPERS = {
  single: __('One correct answer (or no correct, for personality quizzes).'),
  multi: __('Any number of answers can be marked correct.'),
  truefalse: __('Two fixed options: True and False.'),
};

const isEditing = computed(() => Boolean(props.question?.id));
const typeHelper = computed(() => TYPE_HELPERS[form.type] || '');

const form = reactive(emptyForm());

const answersCountText = computed(() =>
  sprintf(
    // translators: %d is the number of answers on the question.
    _n('%d answer', '%d answers', form.answers.length),
    form.answers.length
  )
);

// translators: %d is the position of the answer in the list, starting at 1.
const answerPlaceholder = (i) => sprintf(__('Answer %d'), i + 1);
const errors = reactive({ title: '' });
const tagInput = ref('');
const saving = ref(false);

function emptyForm() {
  return {
    title: '',
    description: '',
    type: 'single',
    answers: [
      { label: '', is_correct: false },
      { label: '', is_correct: false },
    ],
    tags: [],
  };
}

function loadFromQuestion(q) {
  if (!q) {
    Object.assign(form, emptyForm());
    return;
  }
  form.title = q.title || '';
  form.description = q.description || '';
  form.type = q.type || 'single';
  if (q.type === 'truefalse') {
    form.answers = [
      { label: __('True'), is_correct: !!q.answers?.[0]?.is_correct },
      { label: __('False'), is_correct: !!q.answers?.[1]?.is_correct },
    ];
  } else {
    form.answers = Array.isArray(q.answers) && q.answers.length
      ? q.answers.map((a) => ({
        label: a.label || '',
        is_correct: !!a.is_correct,
        value: a.value || '',
      }))
      : emptyForm().answers;
  }
  form.tags = Array.isArray(q.tags) ? [...q.tags] : [];
}

watch(() => props.modelValue, (open) => {
  if (open) {
    loadFromQuestion(props.question);
    errors.title = '';
    tagInput.value = '';
  }
});

watch(() => form.type, (next, prev) => {
  if (next === prev) return;
  if (next === 'truefalse') {
    form.answers = [
      { label: __('True'), is_correct: false },
      { label: __('False'), is_correct: false },
    ];
  } else if (prev === 'truefalse') {
    form.answers = [
      { label: '', is_correct: false },
      { label: '', is_correct: false },
    ];
  }
  if (next === 'single') {
    // Ensure at most one is_correct survives a multi → single switch.
    let seen = false;
    form.answers = form.answers.map((a) => {
      if (a.is_correct && !seen) {
        seen = true;
        return a;
      }
      return { ...a, is_correct: false };
    });
  }
});

const canRemoveAnswer = computed(() => form.type !== 'truefalse' && form.answers.length > 2);

const canSave = computed(() => {
  if (!form.title.trim()) return false;
  const validAnswers = form.answers.filter((a) => (a.label || '').trim().length > 0);
  if (validAnswers.length < 2) return false;
  return true;
});

function addAnswer() {
  if (form.answers.length >= 12) return;
  form.answers.push({ label: '', is_correct: false });
}

function removeAnswer(i) {
  if (!canRemoveAnswer.value) return;
  form.answers.splice(i, 1);
}

function markCorrect(i) {
  form.answers = form.answers.map((a, idx) => ({ ...a, is_correct: idx === i }));
}

function commitTag() {
  const raw = (tagInput.value || '').trim().replace(/,$/, '');
  if (!raw) return;
  const slug = raw.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  if (!slug) {
    tagInput.value = '';
    return;
  }
  if (form.tags.length >= 10) {
    tagInput.value = '';
    return;
  }
  if (!form.tags.includes(slug)) form.tags.push(slug);
  tagInput.value = '';
}

function removeTag(tag) {
  form.tags = form.tags.filter((t) => t !== tag);
}

function close() {
  emit('update:modelValue', false);
}

function onToggle(open) {
  emit('update:modelValue', open);
}

async function onSave() {
  if (!canSave.value) {
    if (!form.title.trim()) errors.title = __('Question text is required.');
    return;
  }
  saving.value = true;
  errors.title = '';
  try {
    const payload = {
      title: form.title.trim(),
      description: form.description?.trim() || null,
      type: form.type,
      tags: form.tags,
      answers: form.answers
        .filter((a) => (a.label || '').trim().length > 0)
        .map((a, i) => ({
          label: a.label.trim(),
          value: a.value || '',
          is_correct: !!a.is_correct,
          position: i + 1,
        })),
    };
    if (isEditing.value) {
      await store.update(props.question.id, payload);
      toast.push({ variant: 'success', title: __('Question updated') });
    } else {
      await store.create(payload);
      toast.push({ variant: 'success', title: __('Added to bank') });
    }
    emit('saved');
    close();
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Save failed'), message: e.message });
  } finally {
    saving.value = false;
  }
}

async function onDelete() {
  if (!isEditing.value) return;
  if (!window.confirm(__('Delete this bank question? Quizzes that already use it will keep their copy.'))) {
    return;
  }
  saving.value = true;
  try {
    await store.remove(props.question.id);
    toast.push({ variant: 'info', title: __('Question deleted') });
    emit('saved');
    close();
  } catch (e) {
    toast.push({ variant: 'danger', title: __('Delete failed'), message: e.message });
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.qbe {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.qbe__answers {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
  background: var(--bg-canvas);
  border: 1px solid var(--border-1);
  border-radius: var(--r-md);
}

.qbe__answers-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-3);
  padding: 0 2px 4px;
}

.qbe__answers-meta {
  color: var(--ink-4);
}

.qbe__answer {
  display: grid;
  grid-template-columns: 24px 1fr auto auto;
  gap: 8px;
  align-items: center;
}

.qbe__answer-num {
  font-family: var(--f-mono);
  font-size: 11px;
  color: var(--ink-4);
  text-align: center;
}

.qbe__answer-correct {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--f-sans);
  font-size: 12px;
  color: var(--ink-3);
  cursor: pointer;
  white-space: nowrap;
}

.qbe__answer-del {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: var(--r-sm);
  background: transparent;
  border: 1px solid transparent;
  color: var(--ink-4);
  cursor: pointer;
  transition: background 150ms, color 150ms, border-color 150ms;
}

.qbe__answer-del:hover:not(:disabled) {
  background: var(--danger-bg);
  color: var(--danger);
}

.qbe__answer-del:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.qbe__answer-add {
  align-self: flex-start;
  margin-top: 4px;
  font-family: var(--f-sans);
  font-size: 12.5px;
  font-weight: 500;
  color: var(--brand);
  background: transparent;
  border: 1px dashed var(--border-2);
  padding: 6px 10px;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: border-color 150ms, color 150ms, background 150ms;
}

.qbe__answer-add:hover {
  border-color: var(--brand);
  background: var(--brand-tint);
}

.qbe__tags {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.qbe__label {
  font-family: var(--f-sans);
  font-size: 12px;
  font-weight: 500;
  color: var(--ink-2);
}

.qbe__tags-input {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  min-height: 38px;
  padding: 6px 8px;
  border: 1px solid var(--border-2);
  border-radius: var(--r-sm);
  background: var(--bg-surface);
}

.qbe__tags-input:focus-within {
  border-color: var(--brand);
  box-shadow: var(--shadow-focus);
}

.qbe__tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: var(--brand-tint);
  color: var(--brand);
  font-family: var(--f-mono);
  font-size: 10.5px;
  padding: 3px 8px;
  border-radius: var(--r-pill);
}

.qbe__tag button {
  background: transparent;
  border: 0;
  color: inherit;
  font-size: 14px;
  line-height: 1;
  cursor: pointer;
  padding: 0;
}

.qbe__tags-input input {
  flex: 1;
  min-width: 100px;
  border: 0;
  background: transparent;
  font-family: var(--f-sans);
  font-size: 13px;
  outline: none;
  padding: 4px 2px;
}

.qbe__hint {
  font-family: var(--f-sans);
  font-size: 11.5px;
  color: var(--ink-4);
}

.qbe__footer-spacer {
  flex: 1;
}
</style>
