import { __ } from '@shared/i18n';

/**
 * Helpers for the notification-email body editor (Settings > Email).
 *
 * The body is written in a rich-text editor and stored as HTML. Templates saved
 * before the editor existed are plain text; they keep working (the server still
 * sends them as plain text) and are simply shown as paragraphs when opened.
 */

/**
 * The details an author can insert, in the order they are listed. Kept in step
 * with EmailTemplate::TOKENS in PHP (a test compares the two), and each is
 * accepted by the server as {name}.
 */
export const EMAIL_TOKENS = [
  { token: '{quiz_title}', hint: __('The name of the quiz that was taken') },
  { token: '{result_title}', hint: __('The result the visitor got') },
  { token: '{score}', hint: __('Their score') },
  { token: '{user_name}', hint: __('The name they entered in the form') },
  { token: '{user_email}', hint: __('The email they entered in the form') },
  { token: '{submitted_at}', hint: __('When they finished the quiz') },
  { token: '{site_title}', hint: __('The name of your site') },
  { token: '{admin_url}', hint: __('A link to your Leads page') },
];

/** Does this stored body contain real HTML tags? (Same test the server uses.) */
export function isHtmlBody(body) {
  // "<p>", "</p>", "<br/>", "<a href=...>" - but not "<{user_email}>" or
  // "<john@example.com>", which a plain-text template may contain.
  return /<\/?[a-z][a-z0-9]*(?:\s[^>]*)?\/?>/i.test(String(body ?? ''));
}

const escapeHtml = (s) =>
  s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

/**
 * What to load into the editor for a stored body.
 *
 * HTML is used as-is. Plain text becomes paragraphs (blank line = new
 * paragraph, single newline = line break), escaped so an address in angle
 * brackets isn't swallowed as a tag. Empty stays empty.
 */
export function bodyToEditorHtml(body) {
  const text = String(body ?? '');
  if (text.trim() === '') return '';
  if (isHtmlBody(text)) return text;

  return text
    .trim()
    .split(/\r?\n[ \t]*\r?\n/)
    .map((para) => `<p>${escapeHtml(para).replace(/\r?\n/g, '<br>')}</p>`)
    .join('');
}
