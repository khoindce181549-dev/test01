/**
 * Reading-direction helpers for the public player.
 *
 * The player never sets `dir` itself: it follows whatever the embedding theme
 * put on <html> / an ancestor (RTL for Arabic, Hebrew, Persian, Urdu). Anything
 * that cannot be expressed with CSS logical properties goes through here.
 */

/** True when `el` is laid out right-to-left (computed, so it follows inherited `dir`). */
export function isRtl(el) {
  if (!el || typeof getComputedStyle !== 'function') return false;
  return getComputedStyle(el).direction === 'rtl';
}

/**
 * Map a keydown to a step along the reading direction.
 * ArrowRight advances in LTR but goes back in RTL (and vice versa), matching how
 * native radio groups behave. Vertical arrows are direction independent.
 *
 * @param {KeyboardEvent} event
 * @param {Element} [el] element whose computed direction decides (defaults to event.currentTarget)
 * @returns {1|-1|null} +1 = next, -1 = previous, null = not an arrow key
 */
export function arrowStep(event, el) {
  switch (event.key) {
    case 'ArrowDown':
      return 1;
    case 'ArrowUp':
      return -1;
    case 'ArrowRight':
      return isRtl(el || event.currentTarget) ? -1 : 1;
    case 'ArrowLeft':
      return isRtl(el || event.currentTarget) ? 1 : -1;
    default:
      return null;
  }
}
