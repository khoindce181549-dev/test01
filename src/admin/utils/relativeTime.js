import { __, sprintf } from '@shared/i18n';

/**
 * Compact "how long ago" label used by the quiz cards and the quiz table:
 * "just now", "5m ago", "3h ago", "2d ago", "1w ago", then the locale date
 * once a timestamp is 30+ days old.
 *
 * @param {string} isoish   Timestamp the server returned.
 * @param {string} fallback What to show when the timestamp is missing or unparseable.
 */
export function shortRelativeTime(isoish, fallback = '—') {
  if (!isoish) return fallback;
  const then = new Date(isoish).getTime();
  if (!Number.isFinite(then)) return fallback;
  const diff = Date.now() - then;
  const minute = 60 * 1000;
  const hour = 60 * minute;
  const day = 24 * hour;
  if (diff < minute) return __('just now');
  // translators: %d is a number of minutes; "m" is the short unit, e.g. "5m ago".
  if (diff < hour) return sprintf(__('%dm ago'), Math.round(diff / minute));
  // translators: %d is a number of hours; "h" is the short unit, e.g. "3h ago".
  if (diff < day) return sprintf(__('%dh ago'), Math.round(diff / hour));
  // translators: %d is a number of days; "d" is the short unit, e.g. "2d ago".
  if (diff < 7 * day) return sprintf(__('%dd ago'), Math.round(diff / day));
  // translators: %d is a number of weeks; "w" is the short unit, e.g. "1w ago".
  if (diff < 30 * day) return sprintf(__('%dw ago'), Math.round(diff / (7 * day)));
  return new Date(isoish).toLocaleDateString();
}
