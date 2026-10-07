/**
 * useSubmission — real public submission API client.
 *
 * Wires to the Phase 6 REST endpoints under /quizably/v1/public/*:
 *   POST /public/submissions/start                → { submission_uuid }
 *   POST /public/submissions/:uuid/answer         (debounced per-question save)
 *   POST /public/submissions/:uuid/complete       → { result, score, breakdown }
 *   POST /public/submissions/:uuid/optin          → { lead_id } (route name kept
 *                                                    for wire compatibility with
 *                                                    existing installs — the
 *                                                    feature is the "Form" screen)
 *
 * Rate-limited per IP server-side; call errors surface on `error.value` so
 * the host component can decide whether to retry or show a message. When
 * no `quizUuid` is supplied we act as a no-op shim for demo/fixture mode.
 */
import { ref } from 'vue';
import { api } from '@frontend/api/client';

const ANSWER_DEBOUNCE_MS = 400;

export function useSubmission(quizUuid = null) {
  const submissionUuid = ref(null);
  const submitting = ref(false);
  const error = ref(null);
  const saveTimers = {};
  const savePending = {};
  const questionStartTime = {};

  // Settles when the latest start() has (successfully or not) finished, so
  // callers - resume replays saved answers - can wait for a submission uuid.
  let startPromise = Promise.resolve(null);
  function whenStarted() {
    return startPromise;
  }

  function start() {
    startPromise = doStart();
    return startPromise;
  }

  async function doStart() {
    if (!quizUuid) return null;
    try {
      const searchParams = new URLSearchParams(window.location.search);
      const utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
      const utm_data = {};
      for (const key of utmKeys) {
        const val = searchParams.get(key);
        if (val) utm_data[key] = val;
      }

      const payload = { quiz_uuid: quizUuid };
      if (Object.keys(utm_data).length > 0) {
        Object.assign(payload, utm_data);
      }

      const r = await api.post('submissions/start', payload);
      submissionUuid.value = r?.submission_uuid ?? null;
      return submissionUuid.value;
    } catch (e) {
      error.value = e.message;
      return null;
    }
  }

  function recordQuestionStart(question_id) {
    questionStartTime[question_id] = Date.now();
  }

  function saveAnswerDebounced(question_id, answer_ids, text_value, answer_order = null) {
    if (saveTimers[question_id]) clearTimeout(saveTimers[question_id]);
    savePending[question_id] = { question_id, answer_ids, text_value, answer_order };
    saveTimers[question_id] = setTimeout(() => {
      delete savePending[question_id];
      delete saveTimers[question_id];
      doSaveAnswer(question_id, answer_ids, text_value, answer_order);
    }, ANSWER_DEBOUNCE_MS);
  }

  async function flushPendingAnswers() {
    const entries = Object.values(savePending);
    if (!entries.length) return;
    for (const qId of Object.keys(saveTimers)) {
      clearTimeout(saveTimers[qId]);
      delete saveTimers[qId];
    }
    for (const key of Object.keys(savePending)) delete savePending[key];
    await Promise.all(
      entries.map(({ question_id, answer_ids, text_value, answer_order }) =>
        doSaveAnswer(question_id, answer_ids, text_value, answer_order)
      )
    );
  }

  async function doSaveAnswer(question_id, answer_ids, text_value, answer_order = null) {
    if (!submissionUuid.value) return;
    try {
      const elapsed_ms = questionStartTime[question_id]
        ? Math.round(Date.now() - questionStartTime[question_id])
        : null;
      const body = {
        question_id,
        answer_ids: answer_ids ?? [],
        text_value: text_value ?? null,
      };
      if (elapsed_ms !== null) body.elapsed_ms = elapsed_ms;
      if (Array.isArray(answer_order) && answer_order.length > 0) {
        body.answer_order = answer_order;
      }
      await api.post(`submissions/${submissionUuid.value}/answer`, body);
    } catch (e) {
      error.value = e.message;
    }
  }

  async function complete() {
    if (!submissionUuid.value) return null;
    submitting.value = true;
    try {
      return await api.post(
        `submissions/${submissionUuid.value}/complete`,
        {}
      );
    } catch (e) {
      error.value = e.message;
      return null;
    } finally {
      submitting.value = false;
    }
  }

  async function submitForm(data) {
    if (!submissionUuid.value) return null;
    try {
      // Route path is `.../optin`, not `.../form` — kept as-is so existing
      // installs' bookmarked/cached requests and any external callers keep
      // working. See Schema.php / PublicController.php for the same call.
      return await api.post(
        `submissions/${submissionUuid.value}/optin`,
        data
      );
    } catch (e) {
      error.value = e.message;
      return null;
    }
  }

  return {
    submissionUuid,
    submitting,
    error,
    start,
    whenStarted,
    recordQuestionStart,
    saveAnswerDebounced,
    flushPendingAnswers,
    complete,
    submitForm,
  };
}
