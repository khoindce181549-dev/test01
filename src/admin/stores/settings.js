import { defineStore } from 'pinia';
import { api } from '@admin/api/client';

/**
 * Global plugin settings. Stored as a key → value map so future
 * settings pages can read/write individual keys without needing a
 * full schema migration in the store.
 *
 * `update(key, value)` sends a partial PUT and merges the server's
 * authoritative response back into `map`.
 */
export const useSettingsStore = defineStore('settings', {
  state: () => ({ map: {}, loaded: false, loading: false }),
  actions: {
    async fetch() {
      this.loading = true;
      try {
        this.map = (await api.get('settings')) || {};
        this.loaded = true;
      } finally {
        this.loading = false;
      }
    },
    async update(key, value) {
      const next = await api.put('settings', { [key]: value });
      this.map = { ...this.map, ...next };
    },
  },
});
