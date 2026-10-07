<?php
namespace Quizably\Pro;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for "is the Pro plugin active and licensed".
 * The Pro plugin (sibling `quizably-pro`) is expected to hook
 * into `quizably/is_pro` and return true when its license checks pass. The free
 * plugin never returns true on its own.
 *
 * Free as of 2026-09-23: per-question/per-result backgrounds, answer/question
 * randomization, and the per-question timer. Free as of 2026-09-26 (found
 * during WordPress.org's first-submission review): quiz export/import
 * (QuizController), question analytics and analytics CSV export
 * (AnalyticsController), double opt-in (PublicController), and the
 * popup/slide-in embed shortcodes. All of these were fully implemented in
 * free's own runtime and gated only by a single is_pro() check guarding an
 * otherwise-working method — the WordPress.org Trialware guideline's
 * forbidden pattern (restricting functionality the plugin's own code
 * already has), not a genuine Pro-only implementation.
 *
 * Still used for: the "Logic & Branching" admin nav item (Menu — a screen
 * with no free equivalent at all), the QUIZABLY_ADMIN.isPro flag surfaced to the
 * admin SPA (Admin\Assets — read by proFeaturesVisible()/showProPromo() for
 * things genuinely absent without Pro, e.g. mid-quiz form placement, the
 * extra templates/question types, and the quiz-level timer/progress bar,
 * which exist only as components Pro's own JS bundle registers), and
 * Pro-tagged entries in the preset gallery (PresetController).
 */
final class Gate
{
    public static function is_pro(): bool
    {
        if ( ! function_exists('apply_filters') ) {
            return false;
        }
        // Filter is canonical truth when registered. Default of `null` lets
        // us tell "no callback registered" apart from "callback explicitly
        // said false" — so tests can opt into either with a single filter.
        $filtered = apply_filters('quizably/is_pro', null);
        if (is_bool($filtered)) {
            return $filtered;
        }
        // Backstop — older Pro plugin builds don't know about the
        // `quizably/is_pro` filter (introduced 2026-05-02). If the Pro plugin's
        // bootstrap class is loaded, treat that as a positive signal so the
        // gate doesn't silently lock features for users running mismatched
        // plugin versions.
        return class_exists('\\QuizablyPro\\Core\\Plugin');
    }

    /**
     * Strip Pro-only sub-keys from a settings array. Used by REST controllers
     * before persisting question / result settings — silently dropping the
     * keys (rather than erroring) so a stale UI on free can't accidentally
     * blow up the save when the Pro plugin is deactivated.
     *
     * @param mixed $settings
     * @param array<int,string> $proKeys
     * @return mixed
     */
    public static function strip_pro_keys($settings, array $proKeys)
    {
        if ( ! is_array($settings) ) {
            return $settings;
        }
        if (self::is_pro()) {
            return $settings;
        }
        foreach ($proKeys as $key) {
            unset($settings[$key]);
        }
        return $settings;
    }
}
