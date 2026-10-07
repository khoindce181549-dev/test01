/**
 * Translation helpers for the admin SPA and the frontend quiz player.
 *
 * Thin wrappers over WordPress's `wp.i18n` (the `wp-i18n` script, declared as a
 * dependency of both bundles in Admin\Assets / Frontend\Assets, with
 * wp_set_script_translations() loading the site's language JSON). Using the
 * WordPress global keeps ~0 KB in our bundle and lets translators use the same
 * workflow as for PHP strings.
 *
 * Every helper falls back to the English source string when `wp.i18n` is not
 * present (unit tests, the static demo, a page where another plugin dequeued
 * wp-i18n), so a missing translation can never break rendering.
 *
 * The text domain is always 'quizably'. Call sites pass only the source
 * strings, as literals, so scripts/generate-pot.mjs can extract them:
 *
 *   __( 'Save' )                       _x( 'Post', 'verb', )
 *   _n( '%d lead', '%d leads', n )     sprintf( __( 'Hi %s' ), name )
 */

const DOMAIN = 'quizably';

function wpI18n() {
  return typeof window !== 'undefined' && window.wp && window.wp.i18n ? window.wp.i18n : null;
}

export function __(text) {
  const i = wpI18n();
  return i ? i.__(text, DOMAIN) : text;
}

export function _x(text, context) {
  const i = wpI18n();
  return i ? i._x(text, context, DOMAIN) : text;
}

export function _n(single, plural, number) {
  const i = wpI18n();
  if (i) return i._n(single, plural, number, DOMAIN);
  return Number(number) === 1 ? single : plural;
}

/** printf-style replace: %s, %d and positional %1$s. */
export function sprintf(format, ...args) {
  const i = wpI18n();
  if (i) return i.sprintf(format, ...args);
  let next = 0;
  return String(format).replace(/%(?:(\d+)\$)?[sd]/g, (_m, pos) => {
    const v = args[pos ? Number(pos) - 1 : next++];
    return v === undefined ? '' : String(v);
  });
}
