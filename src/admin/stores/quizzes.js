import { defineStore } from 'pinia';
import { api } from '@admin/api/client';

/**
 * Quiz list + filters + CRUD actions.
 *
 * The backend `/quizzes` list endpoint returns `{ items, total }` (see
 * Phase 2 REST controller). Filters are merged shallowly on each fetch
 * so views can update individual keys (e.g. set `search`) without
 * stomping on pagination. Actions mutate the in-memory list optimistically
 * enough to keep dashboards responsive — Phase 4 will refine this.
 */
export const useQuizzesStore = defineStore('quizzes', {
  state: () => ({
    items: [],
    total: 0,
    filters: {
      status: '',
      type: '',
      search: '',
      orderby: 'updated_at',
      order: 'DESC',
      limit: 20,
      offset: 0,
    },
    loading: false,
    error: null,
  }),
  actions: {
    async fetchList(partial = {}) {
      this.filters = { ...this.filters, ...partial };
      this.loading = true;
      this.error = null;
      try {
        const { items, total } = await api.get('quizzes', this.filters);
        this.items = items;
        this.total = total;
      } catch (e) {
        this.error = e.message;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async createQuiz(data) {
      const quiz = await api.post('quizzes', data);
      this.items.unshift(quiz);
      this.total++;
      return quiz;
    },
    async duplicate(id) {
      const quiz = await api.post(`quizzes/${id}/duplicate`);
      this.items.unshift(quiz);
      this.total++;
      return quiz;
    },
    async archive(id) {
      await api.delete(`quizzes/${id}`);
      const row = this.items.find((i) => i.id === id);
      if (row) row.status = 'archived';
    },
    async remove(id) {
      await api.delete(`quizzes/${id}`, { force: 1 });
      this.items = this.items.filter((i) => i.id !== id);
      this.total--;
    },
    async publish(id, published = true) {
      const quiz = await api.post(`quizzes/${id}/publish`, { published });
      const idx = this.items.findIndex((i) => i.id === id);
      if (idx !== -1) this.items[idx] = quiz;
    },
    /**
     * Restore an archived quiz by putting it back into draft status.
     * The REST controller doesn't expose a dedicated restore route —
     * we simply PUT status=draft, which the Quizzes controller accepts
     * for any editable row regardless of its current status.
     */
    async restore(id) {
      const quiz = await api.put(`quizzes/${id}`, { status: 'draft' });
      const idx = this.items.findIndex((i) => i.id === id);
      if (idx !== -1) this.items[idx] = quiz;
      return quiz;
    },
  },
});
