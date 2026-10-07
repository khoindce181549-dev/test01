/**
 * Pro detection — reads the flag injected by wp_localize_script in
 * `Admin/Assets.php`. The PHP side resolves it via the `quizably/is_pro` filter
 * which the Pro plugin hooks into when its license is valid.
 *
 * Exposed as a function (not a constant) so test setups can override
 * window.QUIZABLY_ADMIN before the import is called. Cached on first read since
 * it can't change within a page lifetime.
 */
let cached = null;

export function isPro() {
  if (cached !== null) return cached;
  cached = Boolean(window.QUIZABLY_ADMIN?.isPro);
  return cached;
}

/**
 * Promotion switch — may the free plugin mention Pro at all?
 *
 * Set from PHP (`QUIZABLY_ADMIN.proPromo`, filter `quizably/show_pro_promo`, default
 * OFF). While it is off and Pro isn't active, the admin shows no Pro badges,
 * locked toggles, "Upgrade" prompts, teaser tiles or license screens: every
 * control a free user sees works. Flip it on later (once a Pro product and
 * site exist) to bring all of that back without touching any component.
 *
 * Two questions, so call sites say which one they mean:
 *
 *  - showProPromo()       — "is this a *teaser* for a non-Pro user?" Use for
 *                           badges, upsell panels, "Upgrade" buttons, locked
 *                           previews: things that exist only to advertise Pro.
 *  - proFeaturesVisible() — "should this Pro-tier control appear at all?" Use
 *                           for a control that really works once Pro is active
 *                           (a Pro-only option, toggle, tab, tile): visible to
 *                           Pro users, and to free users only while promoting.
 *
 * Read live (not cached) so a test can flip window.QUIZABLY_ADMIN between mounts.
 */
export function showProPromo() {
  return !isPro() && Boolean(window.QUIZABLY_ADMIN?.proPromo);
}

export function proFeaturesVisible() {
  return isPro() || Boolean(window.QUIZABLY_ADMIN?.proPromo);
}

// Test-only — drops the cache so a spec can flip the flag mid-suite.
export function _resetProCache() {
  cached = null;
}
