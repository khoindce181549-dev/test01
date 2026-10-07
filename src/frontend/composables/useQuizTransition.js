/**
 * useQuizTransition — maps quiz.design.animation to a Vue <transition> name.
 *
 * Returns:
 *   - `null`  → use the template's own default transition name
 *   - `''`    → no transition (animation = 'none')
 *   - `'quizably-screen-slide'` → horizontal slide (CSS in base.css)
 *
 * Usage in each template:
 *   const { transitionName } = useQuizTransition(quiz);
 *   <transition :name="transitionName ?? 'quizably-template-default-fade'">
 */
import { computed } from 'vue';

export function useQuizTransition(quiz) {
  const transitionName = computed(() => {
    const a = quiz.value?.design?.animation;
    if (a === 'none') return '';              // empty name = instant (no CSS classes)
    if (a === 'slide') return 'quizably-screen-slide'; // global CSS in base.css
    // 'fade', 'zoom' (Pro), or unset → keep the template's own default
    return null;
  });

  return { transitionName };
}
