import { defineStore } from 'pinia';
import { api } from '@admin/api/client';

/**
 * Question Bank store — list/search/CRUD against /quizably/v1/question-bank.
 *
 * Mirrors the QuizzesStore filter-merge pattern: `fetchList(partial)`
 * shallow-merges into the existing filter state so views can update one
 * key (search, tag, type) without resetting the rest. Tag index is
 * fetched separately because the toolbar needs it before the user
 * applies any filter.
 */
export const useQuestionBankStore = defineStore('questionBank', {
  state: () => ({
    items: [],
    total: 0,
    tags: [],
    filters: {
      search: '',
      type: '',
      tags: [],
      orderby: 'updated_at',
      order: 'DESC',
      limit: 50,
      offset: 0,
    },
    loading: false,
    loadingTags: false,
    error: null,
  }),
  actions: {
    async fetchList(partial = {}) {
      this.filters = { ...this.filters, ...partial };
      this.loading = true;
      this.error = null;
      try {
        // The REST controller accepts tags as repeated query params; the
        // joinPath helper in client.js URL-encodes arrays via URLSearchParams,
        // which serializes a single key — so we flatten arrays to a CSV
        // string here and let the controller split on the server side.
        const params = {
          ...this.filters,
          tags: this.filters.tags && this.filters.tags.length ? this.filters.tags.join(',') : '',
        };
        const { items, total } = await api.get('question-bank', params);
        this.items = items;
        this.total = total;
      } catch (e) {
        this.error = e.message;
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async fetchTags() {
      this.loadingTags = true;
      try {
        const { items } = await api.get('question-bank/tags');
        this.tags = items || [];
      } finally {
        this.loadingTags = false;
      }
    },

    async create(data) {
      const row = await api.post('question-bank', data);
      this.items.unshift(row);
      this.total++;
      return row;
    },

    async update(id, data) {
      const row = await api.put(`question-bank/${id}`, data);
      const idx = this.items.findIndex((i) => i.id === id);
      if (idx !== -1) this.items[idx] = row;
      return row;
    },

    async remove(id) {
      await api.delete(`question-bank/${id}`);
      this.items = this.items.filter((i) => i.id !== id);
      this.total = Math.max(0, this.total - 1);
    },

    async duplicate(id) {
      const row = await api.post(`question-bank/${id}/duplicate`);
      this.items.unshift(row);
      this.total++;
      return row;
    },

    /**
     * Insert a bank question into a quiz. Returns the newly-created quiz
     * question (already hydrated with its answers) so the caller can
     * splice it into the QuizBuilder store directly.
     */
    async insertInto(id, quizId, position) {
      const body = { quiz_id: quizId };
      if (typeof position === 'number') body.position = position;
      const newQuestion = await api.post(`question-bank/${id}/insert`, body);
      // Bump usage_count locally so the list reflects the change without
      // re-fetching. The server is authoritative on the next fetchList.
      const row = this.items.find((i) => i.id === id);
      if (row) row.usage_count = (row.usage_count || 0) + 1;
      return newQuestion;
    },
  },
});
