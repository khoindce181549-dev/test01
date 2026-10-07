import { defineStore } from 'pinia';
import { api } from '@admin/api/client';

export const useLeadsStore = defineStore('leads', {
  state: () => ({
    items: [],
    total: 0,
    filters: {
      quiz_id: '',
      search: '',
      date_from: '',
      date_to: '',
      limit: 20,
      offset: 0,
    },
    loading: false,
    error: null,
  }),
  actions: {
    /**
     * Fetch leads from the global /leads endpoint.
     * quiz_id in partial is optional — omit it to load all quizzes.
     */
    async fetchList(partial = {}) {
      this.filters = { ...this.filters, ...partial };
      this.loading = true;
      this.error = null;
      try {
        const params = {
          limit: this.filters.limit,
          offset: this.filters.offset,
        };
        if (this.filters.quiz_id) params.quiz_id = this.filters.quiz_id;
        if (this.filters.search) params.search = this.filters.search;
        if (this.filters.date_from) params.date_from = this.filters.date_from;
        if (this.filters.date_to) params.date_to = this.filters.date_to;

        const { items, total } = await api.get('leads', params);
        this.items = items;
        this.total = total;
      } catch (e) {
        this.error = e.message;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async remove(id) {
      await api.delete(`leads/${id}`);
      this.items = this.items.filter((i) => i.id !== id);
      this.total = Math.max(0, this.total - 1);
    },
    async exportCsv(quizId) {
      return api.get(`quizzes/${quizId}/leads/export`, this.filters);
    },
  },
});
