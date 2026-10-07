/**
 * useQuizFlow — screen state machine + validation + scoring.
 *
 * Accepts an optional `submission` composable. When it has a live
 * `submissionUuid` the flow delegates scoring to the server via
 * `submission.complete()`. When absent (e.g. demo fixtures with
 * `_demo: true`) the stub scorer below is used so the UI still works
 * offline. The stub matches the server-side scorer's logic.
 */
import { ref, reactive, computed, watch } from 'vue';
import { shuffledQuestions, getQuizSeed, setQuizSeed } from './useRandomizedAnswers.js';
import { buildRecord, saveProgress, clearProgress, loadProgress } from './quizProgressStorage.js';
import { resolveFormPlacement, resolveFormSkippable } from '@shared/formPlacement.js';
import { isValueQuestion } from '@shared/questionTypes.js';

export function useQuizFlow(quizRef, submission = null) {
  // intro | question | form | result | completed
  const screen = ref('intro');
  const currentIndex = ref(0);
  const answers = reactive({});
  const result = ref(null);
  const score = ref(null);
  const breakdown = ref(null);
  const leadData = ref(null);
  // True once the visitor has been through the form (submitted OR skipped it).
  // Stops it being shown a second time - e.g. Back to the intro and Start
  // again after a start-of-quiz form - and lets leaveForm() tell "the form was
  // before the questions" from "the form was after them".
  const formCleared = ref(false);

  // Shared with FormProperties.vue's `placement` computed — see
  // resolveFormPlacement()'s doc comment for the exact rule and why it
  // must live in one place (a hand-duplicated copy of this rule is what
  // let the admin selector and the live quiz drift out of sync before).
  function getFormPlacement() {
    return resolveFormPlacement(quizRef.value?.settings?.optin);
  }
  // Set to true when a question's `logic` rules redirected the user away
  // from the next sequential question. Useful for analytics ("how many
  // sessions used a branch?") and so the result screen can disclose path.
  const wasBranched = ref(false);

  // ── Resume ────────────────────────────────────────────────────────────────
  // Progress is saved as the visitor moves (see persist()) and offered back on
  // the next load via `resumeOffer`. Demo fixtures and quizzes without a uuid
  // never persist. The saved shuffle seed is adopted NOW, before `questions`
  // is first computed, so a resumed visit shuffles exactly as before.
  const progressUuid = quizRef.value && !quizRef.value._demo ? quizRef.value.uuid || null : null;
  const initialRecord = progressUuid ? loadProgress(quizRef.value, progressUuid) : null;
  if (initialRecord && initialRecord.seed != null) setQuizSeed(initialRecord.seed);
  // Non-null while a saved session is waiting for the visitor to choose.
  const resumeOffer = ref(
    initialRecord ? { screen: initialRecord.screen, index: initialRecord.index } : null
  );
  let pendingRecord = initialRecord;
  // When the current question was shown, and the time already used on it by a
  // resumed visit (handed to the per-question timer once).
  let questionStartedAt = null;
  let resumedTimer = null;
  // Set once the quiz is complete so nothing is saved (or resumed) afterwards.
  let finished = false;

  function persist() {
    if (!progressUuid || finished || resumeOffer.value) return;
    const rec = buildRecord({
      quiz: quizRef.value,
      uuid: progressUuid,
      screen: screen.value,
      index: currentIndex.value,
      answers,
      formCleared: formCleared.value,
      wasBranched: wasBranched.value,
      seed: getQuizSeed(),
      questionStartedAt,
    });
    if (rec) saveProgress(rec);
  }

  // When `settings.randomize_questions` is enabled (a Pro feature), the
  // questions array is reshuffled once per session. Helper falls back to
  // the original order when the flag is off, so existing Logic-engine
  // tests that hard-code positional order continue to pass.
  const questions = computed(() => {
    const quiz = quizRef.value;
    if (!quiz) return [];
    return shuffledQuestions(quiz);
  });
  const totalQuestions = computed(() => questions.value.length);
  const currentQuestion = computed(
    () => questions.value[currentIndex.value] ?? null
  );

  const isCurrentAnswered = computed(() => {
    const q = currentQuestion.value;
    if (!q) return false;
    const v = answers[q.id];
    if (q.type === 'multi' || q.type === 'multiple') return Array.isArray(v) && v.length > 0;
    // A typed or rated answer: a number counts, text must not be just spaces.
    if (isValueQuestion(q.type)) {
      return typeof v === 'number' ? Number.isFinite(v) : String(v ?? '').trim() !== '';
    }
    return v !== undefined && v !== null && v !== '';
  });

  const progress = computed(() => {
    if (totalQuestions.value === 0) return 0;
    if (screen.value === 'intro') return 0;
    if (screen.value === 'result' || screen.value === 'completed') return 100;
    return Math.round(
      ((currentIndex.value + (isCurrentAnswered.value ? 1 : 0)) /
        totalQuestions.value) *
        100
    );
  });

  // Sync so a save always matches the state the visitor just reached.
  watch([screen, currentIndex], () => {
    if (screen.value === 'question') questionStartedAt = Date.now();
    persist();
  }, { flush: 'sync' });
  watch([answers, formCleared], persist, { flush: 'sync', deep: true });

  /**
   * Take up the saved session: jump to where the visitor left off. Waits for
   * the server submission to exist (when there is one) so the restored answers
   * can be sent to it again - the saved visit's submission is not reused.
   * Lead-form contents are not restored, only that the form was completed.
   */
  async function acceptResume() {
    const rec = pendingRecord;
    if (!rec) return;
    if (submission?.whenStarted) await submission.whenStarted();
    pendingRecord = null;
    resumeOffer.value = null;
    formCleared.value = rec.formCleared;
    wasBranched.value = rec.wasBranched;
    for (const [qid, value] of Object.entries(rec.answers)) {
      // Through setAnswer so the new server submission receives them too.
      setAnswer(qid, value);
    }
    currentIndex.value = rec.index;
    screen.value = rec.screen;
    if (rec.screen === 'question' && Number.isFinite(rec.qStartedAt)) {
      questionStartedAt = rec.qStartedAt;
      const q = questions.value[rec.index];
      if (q) {
        resumedTimer = {
          questionId: q.id,
          elapsedSeconds: Math.max(0, Math.floor((Date.now() - rec.qStartedAt) / 1000)),
        };
      }
    }
    persist();
  }

  /** "Start over": forget the saved session and carry on from the intro. */
  function declineResume() {
    pendingRecord = null;
    resumeOffer.value = null;
    if (progressUuid) clearProgress(progressUuid);
  }

  /**
   * Seconds already spent on `questionId` before the page was left, once, for
   * the per-question timer to start from instead of the full allowance.
   */
  function takeResumedElapsed(questionId) {
    if (!resumedTimer || String(resumedTimer.questionId) !== String(questionId)) return 0;
    const seconds = resumedTimer.elapsedSeconds;
    resumedTimer = null;
    return seconds;
  }

  function start() {
    // A saved session is waiting for the visitor's choice (resume / start over).
    if (resumeOffer.value) return;
    // A form positioned at the start collects details BEFORE the questions:
    // the visitor gets through it (submit, or skip when it's optional) and only
    // then does the quiz advance to the first question.
    if (getFormPlacement() === 'start' && !formCleared.value) {
      screen.value = 'form';
      return;
    }
    beginQuestions();
  }

  /** Enter the quiz proper: the first question, or straight to the result. */
  function beginQuestions() {
    if (questions.value.length === 0) {
      complete();
    } else {
      screen.value = 'question';
      currentIndex.value = 0;
    }
  }

  function setAnswer(questionId, value, answer_order = null) {
    answers[questionId] = value;
    if (submission?.saveAnswerDebounced) {
      // Text / rating / slider: the value is not one of the question's answers,
      // so it goes to the server as text. Guessing from the value's shape (below)
      // would read a rating of 4 as "answer #4", and a typed "42" as an id -
      // losing the text and polluting the score.
      const asked = questions.value.find((qq) => String(qq.id) === String(questionId));
      if (asked && isValueQuestion(asked.type)) {
        const text = value === null || value === undefined ? '' : String(value);
        submission.saveAnswerDebounced(questionId, [], text === '' ? null : text, answer_order);
        return;
      }

      const answer_ids = Array.isArray(value)
        ? value
        : value != null
          ? [value]
          : [];
      const ids = answer_ids
        .map((v) => Number(v))
        .filter((n) => Number.isFinite(n));
      const text_value =
        typeof value === 'string' && Number.isNaN(Number(value)) ? value : null;
      submission.saveAnswerDebounced(questionId, ids, text_value, answer_order);
    }
  }

  /**
   * Evaluate `question.logic` against the user's answer to determine the
   * next question's INDEX in `questions.value`, or `null` if the quiz
   * should complete immediately (terminal branch).
   *
   * Returns `undefined` when the question has no usable logic — callers
   * should fall through to sequential advancement in that case.
   *
   * Logic JSON contract (see plan docs):
   *   {
   *     rules: [
   *       { if: { answer_id: 5 }, then: { go_to_question_id: 12 } },
   *       { if: { answer_id: 7 }, then: { go_to_question_id: 15 } },
   *       { default: { go_to_question_id: null } }
   *     ]
   *   }
   *
   * - For multi-select answers, a rule matches if its answer_id is in
   *   the selected answer-id array.
   * - For single-select, matches if equal.
   * - First matching rule wins.
   * - `go_to_question_id: null` means terminate (return null).
   * - Falls through to `default` if no rule matched.
   *
   * @param {object|null} question
   * @param {*} answer  Single answer id, array of ids, or null/undefined.
   * @returns {number|null|undefined}
   */
  function nextQuestionIndexFor(question, answer) {
    if (!question) return undefined;
    const logic = question.logic;
    if (!logic || typeof logic !== 'object') return undefined;
    const rules = Array.isArray(logic.rules) ? logic.rules : null;
    if (!rules || rules.length === 0) return undefined;

    // Normalize the user's answer into an array of candidate ids so we
    // can use the same membership check for single- and multi-select.
    const selected = Array.isArray(answer)
      ? answer
      : answer != null && answer !== ''
        ? [answer]
        : [];

    let defaultBranch;
    for (const rule of rules) {
      if (!rule || typeof rule !== 'object') continue;
      if (rule.default && typeof rule.default === 'object') {
        // Remember the default branch but keep scanning specific rules.
        defaultBranch = rule.default;
        continue;
      }
      const cond = rule.if;
      const then = rule.then;
      if (!cond || !then) continue;
      const targetId = cond.answer_id;
      if (targetId === undefined || targetId === null) continue;

      const matched = selected.some((v) => {
        // Loose-ish equality — admin payloads sometimes serialize ids as
        // strings while the frontend stores them as numbers.
        // eslint-disable-next-line eqeqeq
        return v == targetId;
      });
      if (matched) {
        return resolveTarget(then.go_to_question_id);
      }
    }

    if (defaultBranch) {
      return resolveTarget(defaultBranch.go_to_question_id);
    }

    // No rule matched and no default — caller decides the fallback.
    return undefined;
  }

  /**
   * Translate a `go_to_question_id` value from a logic rule into either
   * the matching index in `questions.value`, or `null` to signal that
   * the quiz should complete.
   */
  function resolveTarget(goToQuestionId) {
    if (goToQuestionId === null || goToQuestionId === undefined) {
      // Explicit terminal branch.
      return null;
    }
    const idx = questions.value.findIndex(
      // eslint-disable-next-line eqeqeq
      (q) => q && q.id == goToQuestionId
    );
    // If the target id isn't in the question list, treat as terminal so
    // the quiz doesn't get wedged on a stale logic ref.
    return idx >= 0 ? idx : null;
  }

  function next() {
    const q = currentQuestion.value;
    if (!q) return;
    // required is stored in question.settings.required (boolean JSON) by the admin.
    // The top-level integer column q.required can be 0 (not JS false) and would break
    // this check — always read from settings for consistent boolean semantics.
    if (!isCurrentAnswered.value && q.settings?.required !== false) return;

    advanceFrom(q);
  }

  /**
   * forceNext — advances regardless of whether the current question is answered.
   * Used by the per-question timer: when time expires the user must move on
   * even if a required question was left blank.
   */
  function forceNext() {
    const q = currentQuestion.value;
    if (!q) return;
    advanceFrom(q);
  }

  /**
   * Shared advancement logic used by both next() and forceNext().
   * Evaluates branching rules then moves the index (or finishes).
   */
  function advanceFrom(q) {
    // Branching: if this question has logic, see if it overrides the
    // default sequential advancement.
    const branchTarget = nextQuestionIndexFor(q, answers[q.id]);
    if (branchTarget !== undefined) {
      wasBranched.value = true;
      if (branchTarget === null) {
        finishOrShowForm();
      } else {
        currentIndex.value = branchTarget;
      }
      return;
    }

    if (currentIndex.value < totalQuestions.value - 1) {
      currentIndex.value++;
    } else {
      finishOrShowForm();
    }
  }

  /**
   * After the final question (or a terminal branch), decide whether to
   * route to the form or finalize the quiz. A start-positioned form
   * already ran in `start()`, so we just complete; an end-positioned form
   * shows AFTER the questions.
   */
  function finishOrShowForm() {
    if (getFormPlacement() === 'end' && !formCleared.value) {
      screen.value = 'form';
      return;
    }
    complete();
  }

  function prev() {
    if (screen.value === 'form') {
      // Back goes to whatever came BEFORE the form. A start-of-quiz form sits
      // between the intro and the first question, so that is the intro -
      // sending the visitor to a question they haven't reached yet was a bug.
      // Any other form follows a question, so Back returns to it.
      screen.value = getFormPlacement() === 'start' ? 'intro' : 'question';
      return;
    }
    if (currentIndex.value > 0) {
      currentIndex.value--;
    } else {
      screen.value = 'intro';
    }
  }

  function submitForm(data) {
    leadData.value = data;
    leaveForm();
  }

  /**
   * The visitor declined the form. Only an *optional* form can be skipped: a
   * compulsory one must not be bypassable from here, so this is a no-op unless
   * the form is on screen and the author allowed skipping it. No lead data is
   * recorded.
   */
  function skipForm() {
    if (screen.value !== 'form') return;
    if (!resolveFormSkippable(quizRef.value?.settings?.optin)) return;
    leaveForm();
  }

  /**
   * Move on from the form (submitted or skipped). A start-positioned form
   * leads into the questions; an end-positioned one leads to the result.
   */
  function leaveForm() {
    const startedWithForm = getFormPlacement() === 'start' && !formCleared.value;
    formCleared.value = true;
    if (startedWithForm) {
      beginQuestions();
      return;
    }
    complete();
  }

  /**
   * Where a finished quiz lands. A result screen when there is a result, and always for a poll
   * (it shows the vote tally). A survey or quiz with no result configured ends on the thank-you
   * screen, never back on the intro.
   */
  function finishedScreen(matchedResult) {
    if (matchedResult || quizRef.value?.type === 'poll') return 'result';
    return 'completed';
  }

  async function complete() {
    // A finished quiz is never resumable.
    finished = true;
    if (progressUuid) clearProgress(progressUuid);
    // Prefer server-side scoring when a live submission is attached.
    if (submission?.submissionUuid?.value) {
      // Flush any answers still in the debounce buffer so the scorer has them.
      if (submission.flushPendingAnswers) await submission.flushPendingAnswers();
      const res = await submission.complete();
      if (res) {
        // Server may return null for result when no rule matched — fall back to
        // the first configured result so the quiz never lands on the generic
        // "completed" fallback screen when results ARE configured.
        const serverResult = res.result ?? null;
        const allResults = quizRef.value?.results ?? [];
        result.value = serverResult ?? (allResults.length > 0 ? allResults[0] : null);
        score.value = res.score ?? null;
        breakdown.value = res.breakdown ?? null;
        // Auto-redirect: if the matched result has a redirect URL, navigate
        // immediately — the result card is intentionally never shown.
        const redirectUrl = (result.value?.redirect_url ?? '').trim();
        if (redirectUrl) {
          window.location.href = redirectUrl;
          return;
        }
        screen.value = finishedScreen(result.value);
        return;
      }
      // If the API failed, fall back to local scoring so the UI isn't stuck.
    }

    // STUB SCORING — mirrors server logic for demo/fixture mode.
    const quiz = quizRef.value;
    if (!quiz) {
      screen.value = 'intro';
      return;
    }

    let matchedResult = null;
    const results = quiz.results ?? [];

    if (results.length === 0) {
      matchedResult = null;
    } else if (quiz.type === 'personality') {
      const tally = {};
      for (const q of questions.value) {
        const v = answers[q.id];
        const answerIds = Array.isArray(v) ? v : v != null ? [v] : [];
        for (const aid of answerIds) {
          const a = (q.answers ?? []).find((x) => x.id === aid);
          if (a?.personality_result_id) {
            tally[a.personality_result_id] =
              (tally[a.personality_result_id] ?? 0) + 1;
          }
        }
      }
      const winnerEntry = Object.entries(tally).sort(
        ([, a], [, b]) => b - a
      )[0];
      const winnerId = winnerEntry?.[0];
      matchedResult =
        results.find((r) => String(r.id) === String(winnerId)) ?? results[0];
    } else if (quiz.type === 'trivia') {
      let s = 0;
      for (const q of questions.value) {
        const v = answers[q.id];
        const answerIds = Array.isArray(v) ? v : v != null ? [v] : [];
        for (const aid of answerIds) {
          const a = (q.answers ?? []).find((x) => x.id === aid);
          if (a?.is_correct) s += a.points ?? 1;
        }
      }
      const match = results.find(
        (r) =>
          (r.score_min == null || s >= r.score_min) &&
          (r.score_max == null || s <= r.score_max)
      );
      matchedResult = match
        ? { ...match, _score: s }
        : { ...results[0], _score: s };
      score.value = s;
    } else {
      matchedResult = results[0];
    }

    result.value = matchedResult;
    screen.value = finishedScreen(matchedResult);
  }

  function reset() {
    finished = false;
    resumeOffer.value = null;
    pendingRecord = null;
    resumedTimer = null;
    if (progressUuid) clearProgress(progressUuid);
    screen.value = 'intro';
    currentIndex.value = 0;
    for (const k in answers) delete answers[k];
    result.value = null;
    score.value = null;
    breakdown.value = null;
    leadData.value = null;
    wasBranched.value = false;
    formCleared.value = false;
  }

  return {
    // state
    screen,
    currentIndex,
    answers,
    result,
    score,
    breakdown,
    leadData,
    wasBranched,
    resumeOffer,
    // computed
    questions,
    totalQuestions,
    currentQuestion,
    progress,
    isCurrentAnswered,
    // actions
    start,
    next,
    forceNext,
    prev,
    setAnswer,
    submitForm,
    skipForm,
    complete,
    reset,
    acceptResume,
    declineResume,
    takeResumedElapsed,
    // helpers (exposed for tests + analytics)
    nextQuestionIndexFor,
  };
}
