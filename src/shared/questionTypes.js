/**
 * Question types, as the builder, the quiz screens and the flow logic all need
 * to agree on them.
 *
 * Two kinds of answer:
 *
 *  - CHOICE  (single, multiple, true_false, dropdown, image_choice): the visitor
 *            picks one or more of the question's own answer rows; the server
 *            scores by answer id.
 *  - VALUE   (short_text, rating, slider): the visitor types or picks a value
 *            that is NOT one of the question's answers. It is recorded as text
 *            (`text_value`), never as an answer id - a rating of 4 must not be
 *            read as "answer row #4" - and it takes no part in scoring.
 *
 * Keep VALUE_QUESTION_TYPES in step with UNGRADED_TYPES in
 * src/php/Scoring/TriviaScorer.php.
 */

export const VALUE_QUESTION_TYPES = ['short_text', 'rating', 'slider'];

/** Is this a question whose answer is a typed/picked value rather than a chosen answer? */
export function isValueQuestion(type) {
  return VALUE_QUESTION_TYPES.includes(type);
}

/**
 * Longest text answer the server keeps (it truncates beyond this). The
 * per-question limit an author sets can be lower, never higher.
 * Keep in step with PublicController::MAX_TEXT_ANSWER_LENGTH.
 */
export const MAX_TEXT_ANSWER_LENGTH = 2000;

/** Default per-question limit for a short-text answer. */
export const DEFAULT_TEXT_ANSWER_LENGTH = 500;

/** How a rating is drawn. Chosen per question in the editor. */
export const RATING_STYLES = ['stars', 'numbers', 'emoji'];

/**
 * The face for step `n` of a `total`-step rating: angry -> grinning.
 * Shared by the editor's preview and the visitor's rating, so the two match.
 */
export function ratingEmoji(n, total) {
  const pct = n / total;
  if (pct <= 0.2) return '\u{1F620}'; // angry
  if (pct <= 0.4) return '\u{1F641}'; // slightly frowning
  if (pct <= 0.6) return '\u{1F610}'; // neutral
  if (pct <= 0.8) return '\u{1F642}'; // slightly smiling
  return '\u{1F604}'; // grinning
}
