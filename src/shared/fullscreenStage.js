/**
 * The Full Screen template's stage gradient: the one definition of it.
 *
 * Why it is shared: the live template, the builder's question preview and the
 * Design-tab thumbnail each painted the stage with their own formula (a fixed purple
 * on the live page, two slightly different mixes in the admin), so the builder showed
 * a brown/orange stage while visitors saw purple. The Design tab labels this
 * template's colour slots "Brand" (design.colors.primary) and "Dark tone"
 * (design.colors.text): the stage is the dark tone, tinted with the brand.
 *
 * The first stop is opaque on purpose. This paints a full-viewport layer on the live
 * page, and a translucent stop would let the host theme's page colour show through.
 *
 * Inputs are CSS colour strings, so a caller can pass a hex or a var() reference and
 * let the browser resolve it where the gradient is used.
 *
 * @param {string} tone  The dark tone.
 * @param {string} brand The brand colour.
 * @returns {string} A CSS <image> for `background`.
 */
export const FULLSCREEN_DEFAULT_TONE = '#0a0a0b';

export function fullscreenStage(tone = FULLSCREEN_DEFAULT_TONE, brand = 'var(--brand)') {
  return `linear-gradient(135deg, ${tone}, color-mix(in srgb, ${brand} 60%, ${tone}))`;
}
