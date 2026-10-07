/**
 * The Form (opt-in) has two independent settings, and everything that needs to
 * know about either — the admin sidebar, the admin preview and the live quiz —
 * reads them through the helpers below so they can never drift apart.
 *
 *   position   WHERE the form appears
 *                'start' before the first question (visitors see it first)
 *                'end'   after the last question, before the result
 *                'mid'   between two questions (Pro)
 *                'none'  no form at all
 *   skippable  CAN the visitor skip it?  true → a Skip button; false → they
 *              have to fill it in to continue.
 *
 * Storage: `settings.optin` is the literal stored key name — kept for
 * compatibility with every already-saved quiz (see PublicController.php) even
 * though the feature is labelled "Form" in the UI now. The two settings live in
 * `settings.optin.placement` and `settings.optin.skippable`.
 *
 * Legacy values. Before position and "skippable" were separate, `placement`
 * mixed the two ideas: 'gate' meant "before the quiz, compulsory" and
 * 'optional' meant "after the quiz, skippable". Those rows are still in
 * people's databases, so they are read as gate → start and optional → end
 * (with skippable defaulting to true) and never need migrating. New saves
 * write the canonical values.
 */

import { __ } from './i18n.js';

export const FORM_POSITIONS = ['start', 'end', 'mid', 'none'];

const LEGACY_PLACEMENTS = { gate: 'start', optional: 'end' };

/** Map any stored/legacy placement value to a canonical position. */
export function canonicalPlacement(value) {
  return LEGACY_PLACEMENTS[value] ?? value;
}

/**
 * Resolve the effective position for a quiz.
 *
 * Rule: a quiz whose `settings.optin` has never been saved at all — e.g. fresh
 * from a preset, before the admin has touched the Form tab — has no live form
 * no matter what a UI default would otherwise suggest, so it must resolve to
 * 'none'. A quiz that DOES have saved optin content but no explicit
 * `placement` (e.g. set up before the placement selector existed) defaults to
 * 'start' (formerly 'gate').
 *
 * That drift (admin showing a position pre-selected for a quiz that had never
 * actually saved anything, while the live quiz correctly showed no form at all)
 * was a real, reported bug this shared rule replaces.
 *
 * @param {object|null|undefined} optin - quiz.settings?.optin
 * @returns {'start'|'end'|'mid'|'none'}
 */
export function resolveFormPlacement(optin) {
  if (!optin || typeof optin !== 'object' || Object.keys(optin).length === 0) {
    return 'none';
  }
  return canonicalPlacement(optin.placement || 'gate');
}

/**
 * Can the visitor skip the form? An explicit yes/no wins; a legacy row that
 * only has `placement: 'optional'` was skippable by definition.
 *
 * @param {object|null|undefined} optin - quiz.settings?.optin
 * @returns {boolean}
 */
export function resolveFormSkippable(optin) {
  if (!optin || typeof optin !== 'object') return false;
  if (typeof optin.skippable === 'boolean') return optin.skippable;
  return optin.placement === 'optional';
}

/**
 * Default wording per position, used whenever the author hasn't written their
 * own. The copy has to fit where the form sits: a form shown BEFORE the quiz
 * must not say "Get your result" / "See my result" — the quiz hasn't started.
 * An author's saved title / button label always wins over these.
 */
function formCopyTable() {
  return {
    start: { eyebrow: __('Before you start'), title: __('Tell us about you'), submit: __('Start quiz') },
    end: { eyebrow: __('Almost there'), title: __('Get your result'), submit: __('See my result') },
    mid: { eyebrow: __('One moment'), title: __('Stay in touch'), submit: __('Continue') },
  };
}

/**
 * @param {string} position - a value from resolveFormPlacement()
 * @returns {{eyebrow: string, title: string, submit: string}}
 */
export function formCopyFor(position) {
  const copy = formCopyTable();
  return copy[canonicalPlacement(position)] ?? copy.end;
}
