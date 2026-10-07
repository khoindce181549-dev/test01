import { defineStore } from 'pinia';
import { api } from '@admin/api/client';

/**
 * Analytics store — aggregates for the dashboard and per-quiz builder view.
 *
 * `site` mirrors GET /analytics (site-wide KPIs used by DashboardKpiCard).
 * `perQuiz[id]` mirrors GET /quizzes/{id}/analytics and is fetched lazily
 * when the builder's Settings tab mounts. Both reads are idempotent — the
 * store never writes aggregates locally; it just re-fetches.
 */
export const useAnalyticsStore = defineStore('analytics', {
  state: () => ({
    site: null,
    perQuiz: {},
    loading: false,
    loadingQuiz: {},
    error: null,
  }),
  actions: {
    async fetchSite() {
      this.loading = true;
      this.error = null;
      try {
        this.site = await api.get('analytics');
        return this.site;
      } catch (e) {
        this.error = e.message;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async fetchQuiz(id) {
      this.loadingQuiz = { ...this.loadingQuiz, [id]: true };
      try {
        const data = await api.get(`quizzes/${id}/analytics`);
        this.perQuiz = { ...this.perQuiz, [id]: data };
        return data;
      } finally {
        this.loadingQuiz = { ...this.loadingQuiz, [id]: false };
      }
    },
  },
});
