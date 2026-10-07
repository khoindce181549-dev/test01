/**
 * API wrapper for the preset import endpoint.
 *
 * All other preset data (gallery metadata) lives in
 * src/admin/data/presets.js and is bundled statically.
 */
import { api } from './client.js';

/**
 * Import a predefined preset, creating a full quiz with all content.
 *
 * @param {string} presetId  The preset slug (e.g. 'coffee-personality')
 * @param {{ title?: string }} [opts]
 * @returns {Promise<object>} The newly created quiz object
 */
export function importPreset(presetId, opts = {}) {
  const body = {};
  if (opts.title) body.title = opts.title;
  return api.post(`presets/${presetId}/import`, body);
}
