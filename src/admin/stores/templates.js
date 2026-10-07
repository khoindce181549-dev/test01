import { defineStore } from 'pinia';
import { api } from '@admin/api/client';

/**
 * Template catalog. Fetched once per session and cached — callers can
 * pass `force: true` to refetch after a template is added or changed.
 *
 * The REST endpoint returns each template as `{key, label, thumbnail, pro}`.
 * Views consume `id` / `slug` / `name` for consistency with quiz fields, so
 * we normalize at the store boundary.
 */
function normalize(raw) {
  if (!raw) return null;
  return {
    id: raw.key,
    slug: raw.key,
    key: raw.key,
    name: raw.label ?? raw.name ?? raw.key,
    label: raw.label,
    description: raw.description ?? '',
    thumbnail: raw.thumbnail ?? null,
    pro: Boolean(raw.pro),
    configured: Boolean(raw.configured),
    provider: raw.provider ?? null,
  };
}

export const useTemplatesStore = defineStore('templates', {
  state: () => ({ items: [], loaded: false }),
  actions: {
    async fetch(force = false) {
      if (this.loaded && !force) return;
      const resp = await api.get('templates');
      const list = Array.isArray(resp) ? resp : resp?.items ?? [];
      this.items = list.map(normalize).filter(Boolean);
      this.loaded = true;
    },
  },
});
