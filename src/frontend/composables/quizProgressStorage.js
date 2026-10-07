/**
 * quizProgressStorage - persistence for "resume where you left off".
 *
 * Pure helpers (no Vue) so the rules are testable on their own. One record per
 * quiz, keyed by quiz uuid. Every storage access is wrapped: with storage
 * blocked (private mode, cookies disabled, sandboxed iframe) nothing is saved
 * and nothing throws - the quiz just has no resume.
 *
 * What is stored (see buildRecord): quiz uuid, a fingerprint of the quiz
 * content, the screen + question index, choice/rating answers, the shuffle
 * seed, whether the lead form was already completed, and when the current
 * question started (for the per-question timer). What is deliberately NOT
 * stored: anything typed into the lead form (name, email, consent) and
 * free-text answers - only the fact that the form was completed.
 */

export const PROGRESS_VERSION = 1;
export const PROGRESS_TTL_MS = 7 * 24 * 60 * 60 * 1000;
const KEY_PREFIX = 'quizably_progress_';

/** Free-text answers are never persisted (could hold personal data). */
function isPersistableType(type) {
  return type !== 'short_text';
}

function keyFor(uuid) {
  return KEY_PREFIX + uuid;
}

/** Usable storages in preference order: localStorage, then sessionStorage. */
function storages() {
  const out = [];
  for (const name of ['localStorage', 'sessionStorage']) {
    try {
      const s = typeof window !== 'undefined' ? window[name] : undefined;
      if (s) out.push(s);
    } catch (e) {
      // Accessing the property itself can throw when storage is blocked.
    }
  }
  return out;
}

/** Small, stable string hash (djb2) - a change detector, not a security measure. */
function hash(str) {
  let h = 5381;
  for (let i = 0; i < str.length; i++) {
    h = ((h << 5) + h + str.charCodeAt(i)) | 0;
  }
  return (h >>> 0).toString(36);
}

/**
 * Fingerprint of everything that changes what resuming would mean: the quiz's
 * updated stamp (when the server sends one) plus its structure - question and
 * answer ids, types, logic, timers, randomisation and form placement.
 */
export function quizFingerprint(quiz) {
  if (!quiz) return '';
  const shape = {
    u: quiz.updated_at ?? quiz.updated ?? quiz.version ?? '',
    t: quiz.type ?? '',
    r: quiz.settings?.randomize_questions ? 1 : 0,
    o: quiz.settings?.optin ?? null,
    q: (quiz.questions ?? []).map((q) => ({
      i: q.id,
      t: q.type,
      l: q.logic ?? null,
      s: q.settings?.timer ?? null,
      a: (q.answers ?? []).map((a) => a.id),
    })),
  };
  return hash(JSON.stringify(shape));
}

function sanitizeValue(v) {
  if (typeof v === 'number') return Number.isFinite(v) ? v : undefined;
  if (typeof v === 'string' || typeof v === 'boolean') return v;
  if (Array.isArray(v)) {
    const arr = v.filter((x) => typeof x === 'number' || typeof x === 'string');
    return arr;
  }
  return undefined;
}

/**
 * Build the record to persist from live flow state. Returns null when there is
 * nothing worth resuming (no answers, still on the first question, no form).
 */
export function buildRecord({ quiz, uuid, screen, index, answers, formCleared, wasBranched, seed, questionStartedAt, now = Date.now() }) {
  if (screen !== 'question' && screen !== 'form') return null;
  const byId = new Map((quiz?.questions ?? []).map((q) => [String(q.id), q]));
  const kept = {};
  for (const [qid, value] of Object.entries(answers ?? {})) {
    const q = byId.get(String(qid));
    if (!q || !isPersistableType(q.type)) continue;
    const clean = sanitizeValue(value);
    if (clean !== undefined) kept[qid] = clean;
  }
  const hasProgress = screen === 'form' || index > 0 || Object.keys(kept).length > 0 || formCleared;
  if (!hasProgress) return null;
  return {
    v: PROGRESS_VERSION,
    uuid,
    fp: quizFingerprint(quiz),
    savedAt: now,
    screen,
    index,
    answers: kept,
    formCleared: Boolean(formCleared),
    wasBranched: Boolean(wasBranched),
    seed,
    qStartedAt: Number.isFinite(questionStartedAt) ? questionStartedAt : null,
  };
}

/** Write a record. Returns true when some storage accepted it. */
export function saveProgress(record) {
  if (!record?.uuid) return false;
  const payload = JSON.stringify(record);
  for (const s of storages()) {
    try {
      s.setItem(keyFor(record.uuid), payload);
      return true;
    } catch (e) {
      // Quota / blocked - try the next storage.
    }
  }
  return false;
}

/** Remove the record from every storage. */
export function clearProgress(uuid) {
  if (!uuid) return;
  for (const s of storages()) {
    try {
      s.removeItem(keyFor(uuid));
    } catch (e) {
      // Nothing to do.
    }
  }
}

/**
 * Read and validate saved progress for this quiz. Returns the record, or null
 * when there is none, it is corrupt, expired, for a changed quiz, or points
 * at a question that no longer exists. Invalid records are cleared.
 */
export function loadProgress(quiz, uuid, now = Date.now()) {
  if (!uuid || !quiz) return null;
  let raw = null;
  for (const s of storages()) {
    try {
      raw = s.getItem(keyFor(uuid));
      if (raw) break;
    } catch (e) {
      // Try the next storage.
    }
  }
  if (!raw) return null;

  let rec = null;
  try {
    rec = JSON.parse(raw);
  } catch (e) {
    rec = null;
  }
  const questions = quiz.questions ?? [];
  const valid =
    rec &&
    typeof rec === 'object' &&
    rec.v === PROGRESS_VERSION &&
    rec.uuid === uuid &&
    rec.fp === quizFingerprint(quiz) &&
    Number.isFinite(rec.savedAt) &&
    now - rec.savedAt <= PROGRESS_TTL_MS &&
    now - rec.savedAt >= -60000 &&
    (rec.screen === 'question' || rec.screen === 'form') &&
    Number.isInteger(rec.index) &&
    rec.index >= 0 &&
    (questions.length === 0 ? rec.index === 0 : rec.index < questions.length) &&
    rec.answers &&
    typeof rec.answers === 'object' &&
    !Array.isArray(rec.answers);
  if (!valid) {
    clearProgress(uuid);
    return null;
  }
  return rec;
}
