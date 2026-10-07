/**
 * Stable per-session answer shuffle for questions that have
 * `settings.randomize_answers` set (a Pro feature).
 *
 * Stable means: the same question id, in the same browser session, always
 * produces the same shuffled order — so navigating forward/back or
 * refreshing the page doesn't reshuffle and confuse the user. Two different
 * sessions get different orders.
 *
 * Implementation: one session-scoped seed lives in sessionStorage, plus a
 * Mulberry32 PRNG keyed by `seed XOR question.id`. Falls back to an
 * in-memory seed when sessionStorage is blocked (private mode, server-side
 * render, Vitest jsdom without storage shim, etc.).
 */

let memorySeed = null;
const SEED_KEY = 'quizably_quiz_seed';

function getSessionSeed() {
  if (memorySeed !== null) return memorySeed;
  try {
    if (typeof sessionStorage !== 'undefined') {
      const stored = sessionStorage.getItem(SEED_KEY);
      if (stored) {
        const parsed = parseInt(stored, 10);
        if (Number.isFinite(parsed)) {
          memorySeed = parsed | 0;
          return memorySeed;
        }
      }
      const fresh = Math.floor(Math.random() * 0xffffffff) | 0;
      sessionStorage.setItem(SEED_KEY, String(fresh));
      memorySeed = fresh;
      return memorySeed;
    }
  } catch (e) {
    // Storage blocked — fall through.
  }
  memorySeed = Math.floor(Math.random() * 0xffffffff) | 0;
  return memorySeed;
}

/** The current session seed (creating it if needed) - saved with quiz progress. */
export function getQuizSeed() {
  return getSessionSeed();
}

/**
 * Adopt a seed from saved quiz progress so a resumed visit shuffles questions
 * and answers exactly as the visitor saw them. Must run before the first
 * shuffle of the page; keeps the sessionStorage copy in step when it is usable.
 */
export function setQuizSeed(seed) {
  const n = Number(seed);
  if (!Number.isFinite(n)) return;
  memorySeed = n | 0;
  try {
    if (typeof sessionStorage !== 'undefined') {
      sessionStorage.setItem(SEED_KEY, String(memorySeed));
    }
  } catch (e) {
    // Storage blocked - the in-memory seed still holds for this page.
  }
}

// Mulberry32 — small, fast, good enough for shuffling answer rows.
function prngFor(seed) {
  let state = seed | 0;
  return () => {
    state = (state + 0x6d2b79f5) | 0;
    let t = state;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 0x100000000;
  };
}

/**
 * Return the question's answers, shuffled deterministically when the
 * question has `settings.randomize_answers` enabled. Returns the original
 * array reference when shuffle is off, so identity-based memoization in
 * callers continues to work.
 *
 * @param {object|null|undefined} question
 * @returns {Array}
 */
export function shuffledAnswers(question) {
  const answers = question?.answers ?? [];
  if (!question?.settings?.randomize_answers) return answers;
  if (answers.length < 2) return answers;
  const qid = Number(question.id) || 0;
  const rng = prngFor(getSessionSeed() ^ qid);
  const out = answers.slice();
  for (let i = out.length - 1; i > 0; i--) {
    const j = Math.floor(rng() * (i + 1));
    const tmp = out[i];
    out[i] = out[j];
    out[j] = tmp;
  }
  return out;
}

/**
 * Quiz-level question shuffle. Same session-stable approach as
 * `shuffledAnswers` — the quiz id seeds an independent stream so two
 * quizzes on the same site get different orders.
 *
 * Returns the original `quiz.questions` reference when the setting is
 * off, so identity-based memoization in callers continues to work.
 *
 * @param {object|null|undefined} quiz
 * @returns {Array}
 */
export function shuffledQuestions(quiz) {
  const questions = quiz?.questions ?? [];
  if (!quiz?.settings?.randomize_questions) return questions;
  if (questions.length < 2) return questions;
  const qid = Number(quiz.id) || 0;
  // Different XOR constant from shuffledAnswers so the same session seed
  // produces independent permutations for "questions in this quiz" and
  // "answers within a question" — otherwise both would correlate.
  const rng = prngFor((getSessionSeed() ^ qid ^ 0x9e3779b9) | 0);
  const out = questions.slice();
  for (let i = out.length - 1; i > 0; i--) {
    const j = Math.floor(rng() * (i + 1));
    const tmp = out[i];
    out[i] = out[j];
    out[j] = tmp;
  }
  return out;
}

// Test-only — drops the cached seed so a spec can re-roll.
export function _resetSeedForTests() {
  memorySeed = null;
  try {
    if (typeof sessionStorage !== 'undefined') {
      sessionStorage.removeItem(SEED_KEY);
    }
  } catch (e) {
    /* noop */
  }
}
