/**
 * Convert a 6-digit hex color + opacity percentage to an rgba() string.
 * Returns the hex unchanged when opacity is 100 or not finite (safe default).
 *
 * @param {string} hex        e.g. '#ff4444'
 * @param {number} opacityPct 0–100 (100 = fully opaque)
 * @returns {string}
 */
export function hexToRgba(hex, opacityPct) {
  const pct = opacityPct == null ? 100 : Number(opacityPct);
  if (!Number.isFinite(pct) || pct >= 100) return hex;
  const h = (hex || '#ffffff').replace('#', '').padEnd(6, '0');
  const r = parseInt(h.substring(0, 2), 16);
  const g = parseInt(h.substring(2, 4), 16);
  const b = parseInt(h.substring(4, 6), 16);
  return `rgba(${r}, ${g}, ${b}, ${Math.max(0, Math.min(1, pct / 100)).toFixed(2)})`;
}
