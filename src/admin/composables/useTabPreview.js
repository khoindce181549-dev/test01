import { computed } from 'vue';
import { useQuizBuilderStore } from '@admin/stores/quizBuilder';

// Must match DesignTab's DEFAULT_COLORS so previews and the color inputs
// always show the same value when no colors have been explicitly saved.
const DEFAULT_COLORS = {
  primary:    '#4F46E5',
  background: '#FFFFFF',
  text:       '#0A0A0B',
  accent:     '#F59E0B',
};

/**
 * Returns the design props needed to render a TemplatePreview card inside
 * any builder tab (Questions, Results, Intro, Form).
 *
 * Usage:
 *   const { templateSlug, colors, buttonStyle, fontFamily, splitLayout,
 *           backgroundImage, overlayColor, overlayOpacity, totalQuestions } = useTabPreview()
 *
 * Then optionally override backgroundImage/overlayColor/overlayOpacity with
 * resolveBgStyle(perScreenBg, design) if the tab has a per-screen bg.
 */
export function useTabPreview() {
  const store = useQuizBuilderStore();

  const design = computed(() => store.quiz?.design ?? {});

  const templateSlug = computed(() => store.quiz?.template || 'classic');

  // Always return a complete color object — fall back to DEFAULT_COLORS for
  // any key that hasn't been explicitly saved yet. This keeps previews in sync
  // with the DesignTab's color inputs, which use the same defaults.
  const colors = computed(() => {
    const stored = design.value.colors ?? {};
    return {
      primary:    stored.primary    || DEFAULT_COLORS.primary,
      background: stored.background || DEFAULT_COLORS.background,
      text:       stored.text       || DEFAULT_COLORS.text,
      accent:     stored.accent     || DEFAULT_COLORS.accent,
    };
  });

  const buttonStyle = computed(() => design.value.button_style || 'rounded');
  const fontFamily = computed(() => design.value.font_family || 'default');
  const splitLayout = computed(() => design.value.split_layout || 'image-left');
  const splitImageWidth   = computed(() => Math.min(70, Math.max(10, Number(design.value.split_image_width) || 42)));
  const splitContentAlign = computed(() => design.value.split_content_align || 'center');
  const splitHeightValue  = computed(() => Number(design.value.split_height_value) || 100);
  const splitHeightUnit   = computed(() => design.value.split_height_unit  || 'vh');
  const splitWidthValue   = computed(() => Number(design.value.split_width_value)  || 100);
  const splitWidthUnit    = computed(() => design.value.split_width_unit   || 'vw');
  const totalQuestions = computed(() => store.questions.length);

  const designBg = computed(() => ({
    backgroundImage: design.value.background_image || '',
    overlayColor: design.value.overlay_color || '#000000',
    overlayOpacity: Number(design.value.overlay_opacity) || 0,
  }));

  const backgroundOpacity = computed(() => {
    const v = design.value.background_opacity;
    const n = Number(v);
    return Number.isFinite(n) ? Math.max(0, Math.min(100, Math.round(n))) : 100;
  });

  return {
    templateSlug,
    design,
    colors,
    buttonStyle,
    fontFamily,
    splitLayout,
    splitImageWidth,
    splitContentAlign,
    splitHeightValue,
    splitHeightUnit,
    splitWidthValue,
    splitWidthUnit,
    totalQuestions,
    designBg,
    backgroundOpacity,
  };
}
