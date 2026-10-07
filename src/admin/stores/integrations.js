import { defineStore } from 'pinia';
import { api } from '@admin/api/client';

/**
 * Integration catalog (Mailchimp, ConvertKit, webhooks, etc.). Fetched
 * once per session; pass `force: true` to refetch when a connection
 * is added or removed.
 *
 * REST returns each item as `{key, label, pro, configured, provider?}`.
 * Views consume `id` / `name`; we normalize at the store boundary.
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
    pro: Boolean(raw.pro),
    configured: Boolean(raw.configured),
    provider: raw.provider ?? null,
  };
}

export const useIntegrationsStore = defineStore('integrations', {
  state: () => ({ items: [], loaded: false }),
  actions: {
    async fetch(force = false) {
      if (this.loaded && !force) return;
      const resp = await api.get('integrations');
      const list = Array.isArray(resp) ? resp : resp?.items ?? [];
      this.items = list.map(normalize).filter(Boolean);
      this.loaded = true;
    },
  },
});
