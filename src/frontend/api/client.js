import { __ } from '@shared/i18n';
/**
 * Frontend (public) REST API client.
 *
 * Mirrors src/admin/api/client.js but reads from window.QUIZABLY_FRONTEND and
 * omits the X-WP-Nonce header — public endpoints under /quizably/v1/public/
 * are unauthenticated so that anonymous site visitors can start and
 * submit quizzes.
 */

function getRoot() {
  if (window.QUIZABLY_FRONTEND?.apiRoot) return window.QUIZABLY_FRONTEND.apiRoot;
  const el = document.querySelector('.quizably-quiz-root[data-api-root]');
  if (el?.dataset.apiRoot) return el.dataset.apiRoot;
  return window.location.origin + '/wp-json/quizably/v1/public/';
}

export class ApiError extends Error {
  constructor(status, code, message, data) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.data = data;
  }
}

// Handles both pretty (`/wp-json/quizably/v1/public/`) and plain
// (`/?rest_route=/quizably/v1/public/`) permalink shapes when joining paths +
// query params. See admin/api/client.js for the full discussion.
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
  const url = joinPath(getRoot(), path, params);
  const headers = { 'Content-Type': 'application/json' };

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
  delete: (path, params) => request('DELETE', path, undefined, params),
};

export function apiUrl(path, params) {
  return joinPath(getRoot(), path, params);
}
