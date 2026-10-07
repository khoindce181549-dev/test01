/**
 * Admin REST API client.
 *
 * Reads the API root + nonce from window.QUIZABLY_ADMIN (wp_localize_script).
 * All requests send JSON bodies and include the WP nonce so authenticated
 * admin endpoints accept them. Non-2xx responses throw ApiError with the
 * parsed WP_Error `code`/`message`/`data` attached for UI consumption.
 */

import { __ } from '@shared/i18n';

const root = window.QUIZABLY_ADMIN?.apiRoot ?? '/wp-json/quizably/v1/';
const nonce = window.QUIZABLY_ADMIN?.nonce ?? '';

export class ApiError extends Error {
  constructor(status, code, message, data) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.data = data;
  }
}

// `apiRoot` from wp_localize_script ends with a trailing `/`. Two shapes:
//   pretty permalinks: "https://site/wp-json/quizably/v1/"
//   plain  permalinks: "https://site/?rest_route=/quizably/v1/"
// Concatenating "quizzes" works in both cases. Adding query params needs
// the right separator: `?` for pretty, `&` for plain (which already has `?`).
function joinPath(rootUrl, path, params) {
  let url = rootUrl.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
  if (!params || !Object.keys(params).length) return url;

  const qs = new URLSearchParams(
    Object.entries(params).filter(
      ([, v]) => v !== undefined && v !== null && v !== ''
    )
  );
  if (!qs.toString()) return url;

  const sep = url.includes('?') ? '&' : '?';
  return url + sep + qs.toString();
}

async function request(method, path, body, params) {
  const url = joinPath(root, path, params);
  const headers = { 'Content-Type': 'application/json' };
  if (nonce) headers['X-WP-Nonce'] = nonce;

  const opts = { method, headers };
  if (body !== undefined) opts.body = JSON.stringify(body);

  const res = await fetch(url, opts);
  const contentType = res.headers.get('content-type') || '';
  const payload = contentType.includes('application/json')
    ? await res.json().catch(() => null)
    : null;

  if (!res.ok) {
    const code = payload?.code ?? 'quizably_http_error';
    const message = payload?.message ?? res.statusText ?? __('Request failed');
    throw new ApiError(res.status, code, message, payload?.data ?? null);
  }
  return payload;
}

export const api = {
  get: (path, params) => request('GET', path, undefined, params),
  post: (path, body) => request('POST', path, body),
  put: (path, body) => request('PUT', path, body),
  patch: (path, body) => request('PATCH', path, body),
  delete: (path, params) => request('DELETE', path, undefined, params),
};

// Exposed for unit tests + LeadsView's CSV download which uses raw fetch.
export function apiUrl(path, params) {
  return joinPath(root, path, params);
}
