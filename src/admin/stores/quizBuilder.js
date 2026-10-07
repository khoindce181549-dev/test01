import { defineStore } from 'pinia';
import { api } from '@admin/api/client';
import { __, sprintf } from '@shared/i18n';

// Default answer rows seeded when the admin creates a new question.
// Keeps the frontend from rendering an empty option list while the author
// is still building. Non-choice types (text/slider/rating) skip this.
function defaultAnswersFor(type) {
  if (type === 'true_false' || type === 'truefalse') {
    return [
      { label: __('True'), value: 'true', position: 1 },
      { label: __('False'), value: 'false', position: 2 },
    ];
  }
  if (
    type === 'single' ||
    type === 'multi' ||
    type === 'multiple' ||
    type === 'dropdown' ||
    type === 'image_choice'
  ) {
    return [
      { label: __('Answer A'), value: 'a', position: 1 },
      { label: __('Answer B'), value: 'b', position: 2 },
    ];
  }
  return [];
}

/**
 * Quiz Builder store — owns the full tree for the quiz currently open in
 * the builder (quiz row + questions + answers + results). The list view
 * uses `useQuizzesStore`; this one is scoped to a single quiz's editing
 * session and is reset on route teardown.
 *
 * All CRUD actions call the REST API and sync the in-memory tree so the
 * UI stays responsive without a full reload. `saving` and `lastSavedAt`
 * drive the topbar save indicator; `dirty` is set by autosave debouncers
 * in the editor so the topbar can show "Unsaved changes" while the
 * debounce timer is pending.
 */
export const useQuizBuilderStore = defineStore('quizBuilder', {
  state: () => ({
    quiz: null,
    loading: false,
    saving: false,
    dirty: false,
    lastSavedAt: null,
    error: null,
    activeQuestionId: null,
    activeResultId: null,
    // Staging: in-memory-only changes that have not yet been persisted.
    // Flushed to the API when the user explicitly clicks Save.
    _settingsDirty: false,
    _dirtyResultIds: null, // Set<string> of result IDs with unstaged changes
  }),
  getters: {
    questions: (s) => s.quiz?.questions ?? [],
    results: (s) => s.quiz?.results ?? [],
    activeQuestion(s) {
      return s.quiz?.questions?.find((q) => q.id === s.activeQuestionId) ?? null;
    },
    activeResult(s) {
      return s.quiz?.results?.find((r) => r.id === s.activeResultId) ?? null;
    },
  },
  actions: {
    async load(id) {
      this.loading = true;
      this.error = null;
      try {
        this.quiz = await api.get('quizzes/' + id);
        this.activeQuestionId = this.quiz?.questions?.[0]?.id ?? null;
        this.activeResultId = this.quiz?.results?.[0]?.id ?? null;
      } catch (e) {
        this.error = e.message;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async savePatch(patch) {
      this.saving = true;
      try {
        const updated = await api.put('quizzes/' + this.quiz.id, patch);
        this.quiz = { ...this.quiz, ...updated };
        this.lastSavedAt = Date.now();
        this.dirty = false;
      } finally {
        this.saving = false;
      }
    },
    /**
     * @param {object} data
     * @param {{seedAnswers?: boolean}} [opts] Pass seedAnswers: false when the caller adds the
     *   answers itself (duplicate question), so default blank answers are not created first.
     */
    async createQuestion(data, { seedAnswers = true } = {}) {
      const q = await api.post('quizzes/' + this.quiz.id + '/questions', data);
      this.quiz.questions = [...this.questions, q];
      this.activeQuestionId = q.id;

      // Seed default answers so the new question isn't empty in the frontend.
      // single/multiple/dropdown/image_choice get 2 default options; true_false
      // gets True + False; non-choice types (text/slider/rating) skip this.
      const seed = seedAnswers ? defaultAnswersFor(q.type) : [];
      if (seed.length) {
        try {
          const created = await Promise.all(seed.map((a) => api.post(`questions/${q.id}/answers`, a)));
          q.answers = [...(q.answers ?? []), ...created];
        } catch (e) {
          // Swallow — author can add answers manually if seeding fails.
        }
      }

      return q;
    },
    /**
     * Copy a question with its answers (labels, correct flags, points, result mapping, media).
     * The copy is added to the end of the quiz and becomes the active question.
     */
    async duplicateQuestion(source) {
      const q = await this.createQuestion(
        {
          type: source.type,
          // translators: %s is the title of the question being duplicated.
          title: source.title ? sprintf(__('%s (copy)'), source.title) : '',
          description: source.description ?? '',
          position: this.questions.length + 1,
          settings: source.settings ? { ...source.settings } : {},
        },
        { seedAnswers: false }
      );
      const answers = [...(source.answers ?? [])].sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
      // One at a time so the copies keep the original order.
      for (const a of answers) {
        await this.createAnswer(q.id, {
          label: a.label ?? '',
          value: a.value ?? '',
          is_correct: a.is_correct ? 1 : 0,
          points: a.points ?? 0,
          weights: a.weights ?? undefined,
          personality_result_id: a.personality_result_id ?? undefined,
          media_url: a.media_url ?? undefined,
          position: a.position ?? 0,
        });
      }
      return q;
    },
    /**
     * Append a fully-hydrated question (including answers) to the open
     * quiz. Used by the "Insert from bank" flow — the bank controller
     * returns the new quizably_questions row already populated with answers,
     * so we don't need a follow-up fetch. Sets it as the active question
     * so the user lands on the new row in the editor.
     */
    appendQuestion(q) {
      if (!this.quiz || !q) return;
      this.quiz.questions = [...this.questions, q];
      this.activeQuestionId = q.id;
    },
    async updateQuestion(id, data) {
      this.saving = true;
      try {
        const updated = await api.put('questions/' + id, data);
        const i = this.quiz.questions.findIndex((q) => q.id === id);
        if (i !== -1) {
          this.quiz.questions[i] = { ...this.quiz.questions[i], ...updated };
        }
      } finally {
        this.saving = false;
      }
    },
    async deleteQuestion(id) {
      await api.delete('questions/' + id);
      this.quiz.questions = this.quiz.questions.filter((q) => q.id !== id);
      if (this.activeQuestionId === id) {
        this.activeQuestionId = this.questions[0]?.id ?? null;
      }
    },
    async reorderQuestions(orderedIds) {
      await api.post('quizzes/' + this.quiz.id + '/questions/reorder', { order: orderedIds });
      this.quiz.questions = orderedIds.map((id, i) => {
        const q = this.quiz.questions.find((x) => x.id === id);
        return { ...q, position: i + 1 };
      });
    },
    async createAnswer(questionId, data) {
      const a = await api.post('questions/' + questionId + '/answers', data);
      const q = this.quiz.questions.find((x) => x.id === questionId);
      if (q) q.answers = [...(q.answers ?? []), a];
      return a;
    },
    async updateAnswer(questionId, answerId, data) {
      this.saving = true;
      try {
        const updated = await api.put('answers/' + answerId, data);
        const q = this.quiz.questions.find((x) => x.id === questionId);
        if (q) {
          const i = q.answers.findIndex((a) => a.id === answerId);
          if (i !== -1) q.answers[i] = { ...q.answers[i], ...updated };
        }
      } finally {
        this.saving = false;
      }
    },
    async deleteAnswer(questionId, answerId) {
      await api.delete('answers/' + answerId);
      const q = this.quiz.questions.find((x) => x.id === questionId);
      if (q) q.answers = q.answers.filter((a) => a.id !== answerId);
    },
    async reorderAnswers(questionId, orderedIds) {
      await api.post('questions/' + questionId + '/answers/reorder', { order: orderedIds });
      const q = this.quiz.questions.find((x) => x.id === questionId);
      if (q) {
        q.answers = orderedIds.map((id, i) => {
          const a = q.answers.find((x) => x.id === id);
          return { ...a, position: i + 1 };
        });
      }
    },
    async publish(published = true) {
      const updated = await api.post('quizzes/' + this.quiz.id + '/publish', { published });
      this.quiz = { ...this.quiz, ...updated };
    },
    async createResult(data) {
      const r = await api.post('quizzes/' + this.quiz.id + '/results', data);
      this.quiz.results = [...this.results, r];
      this.activeResultId = r.id;
      return r;
    },
    async updateResult(id, data) {
      this.saving = true;
      try {
        const updated = await api.put('results/' + id, data);
        const i = this.quiz.results.findIndex((r) => r.id === id);
        if (i !== -1) {
          this.quiz.results[i] = { ...this.quiz.results[i], ...updated };
        }
      } finally {
        this.saving = false;
      }
    },
    async deleteResult(id) {
      await api.delete('results/' + id);
      this.quiz.results = this.quiz.results.filter((r) => r.id !== id);
      if (this.activeResultId === id) {
        this.activeResultId = this.results[0]?.id ?? null;
      }
    },
    // Stage a question change: updates in-memory state immediately so the
    // preview stays in sync, but does NOT call the API. Flushed on Save.
    stageQuestion(id, patch) {
      if (!this.quiz) return;
      const i = this.quiz.questions.findIndex((q) => String(q.id) === String(id));
      if (i === -1) return;
      this.quiz.questions[i] = { ...this.quiz.questions[i], ...patch };
      if (!this._dirtyQuestionIds) this._dirtyQuestionIds = new Set();
      this._dirtyQuestionIds.add(String(id));
      this.dirty = true;
    },
    // Stage a settings change: updates in-memory state immediately so the
    // preview stays in sync, but does NOT call the API. Flushed on Save.
    stageSettings(patch) {
      if (!this.quiz) return;
      this.quiz.settings = { ...(this.quiz.settings ?? {}), ...patch };
      this._settingsDirty = true;
      this.dirty = true;
    },
    // Stage a result change: updates the in-memory result without API call.
    stageResult(id, patch) {
      const i = this.quiz?.results?.findIndex((r) => String(r.id) === String(id));
      if (i !== -1) this.quiz.results[i] = { ...this.quiz.results[i], ...patch };
      if (!this._dirtyResultIds) this._dirtyResultIds = new Set();
      this._dirtyResultIds.add(String(id));
      this.dirty = true;
    },
    async flushStagedSettings() {
      if (!this._settingsDirty || !this.quiz) return;
      this._settingsDirty = false;
      await this.savePatch({ settings: this.quiz.settings });
    },
    async flushStagedResults() {
      if (!this._dirtyResultIds?.size || !this.quiz) return;
      const ids = Array.from(this._dirtyResultIds);
      this._dirtyResultIds = new Set();
      this.saving = true;
      try {
        await Promise.all(ids.map((id) => {
          const result = this.quiz.results.find((r) => String(r.id) === id);
          if (!result) return Promise.resolve();
          return api.put('results/' + id, result).then((updated) => {
            const idx = this.quiz.results.findIndex((r) => String(r.id) === id);
            if (idx !== -1) this.quiz.results[idx] = { ...this.quiz.results[idx], ...updated };
          });
        }));
        this.lastSavedAt = Date.now();
      } finally {
        this.saving = false;
      }
    },
    async flushStagedQuestions() {
      if (!this._dirtyQuestionIds?.size || !this.quiz) return;
      const ids = Array.from(this._dirtyQuestionIds);
      this._dirtyQuestionIds = new Set();
      this.saving = true;
      try {
        await Promise.all(ids.map((id) => {
          const q = this.quiz.questions.find((q) => String(q.id) === id);
          if (!q) return Promise.resolve();
          return api.put('questions/' + id, { title: q.title, description: q.description })
            .then((updated) => {
              const idx = this.quiz.questions.findIndex((q) => String(q.id) === id);
              if (idx !== -1) this.quiz.questions[idx] = { ...this.quiz.questions[idx], ...updated };
            });
        }));
      } finally {
        this.saving = false;
      }
    },
    async flushAllStaged() {
      await Promise.all([
        this.flushStagedSettings(),
        this.flushStagedResults(),
        this.flushStagedQuestions(),
      ]);
    },
    async updateSettings(patch) {
      const mergedSettings = { ...(this.quiz?.settings ?? {}), ...patch };
      await this.savePatch({ settings: mergedSettings });
    },
    async updateDesign(patch) {
      const mergedDesign = { ...(this.quiz?.design ?? {}), ...patch };
      await this.savePatch({ design: mergedDesign });
    },
    async updateTemplate(template) {
      await this.savePatch({ template });
    },
    setActiveQuestion(id) {
      this.activeQuestionId = id;
    },
    setActiveResult(id) {
      this.activeResultId = id;
    },
    reset() {
      this.quiz = null;
      this.activeQuestionId = null;
      this.activeResultId = null;
      this.dirty = false;
      this.error = null;
      this.lastSavedAt = null;
      this._settingsDirty = false;
      this._dirtyResultIds = null;
    },
  },
});
