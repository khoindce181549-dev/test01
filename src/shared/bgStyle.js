/**
 * Resolve the background to render for a given screen.
 *
 * Per-screen wins when `enabled === true` AND there's an image; otherwise
 * inherit the quiz-level Design background. Overlay color and opacity come
 * from the same source as the resolved image (per-screen or design) so the
 * tint always matches the chosen image.
 *
 * Returns:
 *   { backgroundImage: string, overlayColor: string, overlayOpacity: number }
 *
 * Used by both admin previews (IntroPreview / FormPreview /
 * QuestionPreview / ResultPreview) and the live frontend (Quiz.vue) so the
 * fallback chain is identical in both places.
 *
 * Note: CSS `background-size: cover` is the correct fit for a decorative
 * backdrop. The CLAUDE.md no-crop rule applies to content `<img>` tags only.
 *
 * @param {object} screenBg  - per-screen settings { enabled, background_image, overlay_color, overlay_opacity }
 * @param {object} design    - quiz design { background_image, overlay_color, overlay_opacity }
 */
export function resolveBgStyle(screenBg, design) {
  const sb = isObject(screenBg) ? screenBg : {};
  const d = isObject(design) ? design : {};
  const useScreen =
    sb.enabled === true && typeof sb.background_image === 'string' && sb.background_image.length > 0;
  const src = useScreen ? sb : d;
  return {
    backgroundImage: useScreen
      ? sb.background_image
      : (typeof d.background_image === 'string' ? d.background_image : ''),
    overlayColor: typeof src.overlay_color === 'string' && src.overlay_color
      ? src.overlay_color
      : '#000000',
    overlayOpacity: clampPct(src.overlay_opacity),
  };
}

function clampPct(n) {
  const v = Number(n);
  if (!Number.isFinite(v)) return 0;
  return Math.max(0, Math.min(100, Math.round(v)));
}

function isObject(v) {
  return v !== null && typeof v === 'object';
}
