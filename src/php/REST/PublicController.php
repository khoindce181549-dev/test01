<?php
namespace Quizably\REST;

use Quizably\Optin\DoubleOptin;
use Quizably\Polls\PollResults;
use Quizably\RateLimit\TokenBucket;
use Quizably\Scoring\Registry as ScorerRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Public quiz-taking endpoints. Anonymous access, rate-limited by IP hash.
 * Only exposes fields safe to ship to the browser — admin-only columns
 * (answer scoring metadata, branching logic, result conditions, author IDs)
 * are stripped before serializing.
 */
final class PublicController extends BaseController
{
    public function register_routes(): void
    {
        $ns = RestBootstrap::NAMESPACE;
        $perm = [$this, 'public_permission'];

        register_rest_route($ns, '/public/quiz/(?P<uuid>[a-f0-9\-]{36})', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_quiz'],
            'permission_callback' => $perm,
        ]);
        register_rest_route($ns, '/public/quiz/(?P<uuid>[a-f0-9\-]{36})/poll-results', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_poll_results'],
            'permission_callback' => $perm,
        ]);
        register_rest_route($ns, '/public/submissions/start', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'start_submission'],
            'permission_callback' => $perm,
        ]);
        register_rest_route($ns, '/public/submissions/(?P<uuid>[a-f0-9\-]{36})/answer', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'save_answer'],
            'permission_callback' => $perm,
        ]);
        register_rest_route($ns, '/public/submissions/(?P<uuid>[a-f0-9\-]{36})/complete', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'complete_submission'],
            'permission_callback' => $perm,
        ]);
        register_rest_route($ns, '/public/submissions/(?P<uuid>[a-f0-9\-]{36})/optin', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'submit_optin'],
            'permission_callback' => $perm,
        ]);
        register_rest_route($ns, '/public/confirm-optin', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'confirm_optin'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * @param \WP_REST_Request $req
     * @return bool|\WP_Error
     */
    public function public_permission($req)
    {
        $bucket = new TokenBucket();
        $id = TokenBucket::identifier_from_ip();
        if (!$bucket->check($id)) {
            return new \WP_Error(
                'quizably_rate_limited',
                __('Too many attempts. Please slow down.', 'quizably'),
                ['status' => 429]
            );
        }
        return true;
    }

    public function get_quiz(\WP_REST_Request $req)
    {
        $uuid = (string) $req['uuid'];
        $repos = $this->repos();
        $quiz = $repos['quizzes']->find_by_uuid($uuid);
        if (!$quiz || ($quiz['status'] ?? '') !== 'published') {
            return $this->not_found('Quiz');
        }

        $quiz = $this->decode_json_fields($quiz, ['settings', 'design']);
        unset($quiz['author_id']);

        $questionRows = $repos['questions']->find_by_quiz((int) $quiz['id']);
        $questions = [];
        foreach ($questionRows as $q) {
            $questions[] = $this->public_question($q, $repos);
        }

        $resultRows = $repos['results']->find_by_quiz((int) $quiz['id']);
        $results = [];
        foreach ($resultRows as $r) {
            $results[] = $this->public_result($r);
        }

        $quiz['questions'] = $questions;
        $quiz['results'] = $results;
        return $this->ok($quiz);
    }

    /**
     * @param array<string,mixed> $q
     * @param array<string,object> $repos
     * @return array<string,mixed>
     */
    private function public_question(array $q, array $repos): array
    {
        // Branching logic is navigational (answer_id → go_to_question_id) and
        // contains no sensitive scoring data, so it is safe to ship to the client.
        // The frontend's nextQuestionIndexFor() evaluates it to decide which question
        // to show next — without this, all configured logic rules are silently ignored.
        $q = $this->decode_json_fields($q, ['settings', 'logic']);

        $answers = [];
        foreach ($repos['answers']->find_by_question((int) $q['id']) as $a) {
            // Strip any hint of correctness / scoring before handing to the browser.
            unset($a['is_correct'], $a['points'], $a['weights']);
            $answers[] = $a;
        }
        $q['answers'] = $answers;
        return $q;
    }

    /**
     * @param array<string,mixed> $r
     * @return array<string,mixed>
     */
    private function public_result(array $r): array
    {
        $r = $this->decode_json_fields($r, ['conditions', 'settings']);
        // Conditions are server-side-evaluated only; never ship them out.
        unset($r['conditions']);
        return $r;
    }

    /**
     * Vote counts for a poll, for the results screen shown after a visitor votes.
     */
    public function get_poll_results(\WP_REST_Request $req)
    {
        $repos = $this->repos();
        $quiz  = $repos['quizzes']->find_by_uuid((string) $req['uuid']);
        if (!$quiz || ($quiz['status'] ?? '') !== 'published') {
            return $this->not_found('Quiz');
        }

        $results = PollResults::for_quiz($quiz, $repos);
        if (null === $results) {
            return $this->error('quizably_not_a_poll', __('This quiz is not a poll', 'quizably'), 400);
        }

        return $this->ok($results);
    }

    public function start_submission(\WP_REST_Request $req)
    {
        $quiz_uuid = (string) $req->get_param('quiz_uuid');
        $repos = $this->repos();
        $quiz = $repos['quizzes']->find_by_uuid($quiz_uuid);
        if (!$quiz || ($quiz['status'] ?? '') !== 'published') {
            return $this->error('quizably_quiz_unavailable', __('Quiz not available', 'quizably'), 410);
        }

        $id = $repos['submissions']->insert([
            'quiz_id'    => (int) $quiz['id'],
            'answers'    => [],
            'ip_hash'    => TokenBucket::identifier_from_ip(),
            'user_agent' => substr(
                isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : '',
                0,
                255
            ),
            'utm'        => $this->parse_utm($req),
        ]);
        $sub = $repos['submissions']->find((int) $id);
        if (!$sub) {
            return $this->error('quizably_submission_failed', __('Submission could not be created', 'quizably'), 500);
        }

        return $this->ok(['submission_uuid' => $sub['uuid']], 201);
    }

    public function save_answer(\WP_REST_Request $req)
    {
        $uuid = (string) $req['uuid'];
        $repos = $this->repos();
        $sub = $repos['submissions']->find_by_uuid($uuid);
        if (!$sub) {
            return $this->not_found('Submission');
        }
        if (($sub['status'] ?? '') !== 'in_progress') {
            return $this->error('quizably_locked', __('Submission already completed', 'quizably'), 409);
        }

        $question_id = (int) $req->get_param('question_id');
        $answer_ids = $req->get_param('answer_ids');
        $answer_ids = is_array($answer_ids) ? $answer_ids : [];
        $text_value = $req->get_param('text_value');
        $time_spent_ms = (int) $req->get_param('time_spent_ms');
        $elapsed_raw = $req->get_param('elapsed_ms');
        $elapsed_ms = ($elapsed_raw !== null && is_numeric($elapsed_raw)) ? (int) $elapsed_raw : null;
        // answer_order stores the displayed sequence of answer IDs when
        // randomize_answers is on, enabling analytics to reconstruct the
        // view each participant saw (F-005 fix).
        $answer_order_raw = $req->get_param('answer_order');
        $answer_order = is_array($answer_order_raw)
            ? array_values(array_map('intval', $answer_order_raw))
            : null;

        $existing = [];
        if (!empty($sub['answers'])) {
            $decoded = json_decode((string) $sub['answers'], true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        // Upsert by question_id — always keep the latest submission per question.
        $filtered = [];
        foreach ($existing as $a) {
            if ((int) ($a['question_id'] ?? 0) !== $question_id) {
                $filtered[] = $a;
            }
        }
        $entry = [
            'question_id'   => $question_id,
            'answer_ids'    => array_map('intval', $answer_ids),
            'text_value'    => $this->sanitize_text_answer($text_value),
            'time_spent_ms' => $time_spent_ms,
        ];
        if ($elapsed_ms !== null) {
            $entry['elapsed_ms'] = $elapsed_ms;
        }
        if ($answer_order !== null) {
            $entry['answer_order'] = $answer_order;
        }
        $filtered[] = $entry;

        $repos['submissions']->update_answers($uuid, $filtered);
        return $this->ok(['saved' => true]);
    }

    public function complete_submission(\WP_REST_Request $req)
    {
        $uuid = (string) $req['uuid'];
        $repos = $this->repos();
        $sub = $repos['submissions']->find_by_uuid($uuid);
        if (!$sub) {
            return $this->not_found('Submission');
        }
        if (($sub['status'] ?? '') === 'completed') {
            // Idempotent: return the stored result rather than re-scoring.
            $result = null;
            if (!empty($sub['result_id'])) {
                $r = $repos['results']->find((int) $sub['result_id']);
                if ($r) {
                    $result = $this->public_result($r);
                }
            }
            $breakdown = null;
            if (!empty($sub['result_breakdown'])) {
                $decoded = json_decode((string) $sub['result_breakdown'], true);
                $breakdown = is_array($decoded) ? $decoded : null;
            }
            return $this->ok([
                'result'    => $result,
                'score'     => $sub['score'] !== null ? (int) $sub['score'] : null,
                'breakdown' => $breakdown,
            ]);
        }

        $quiz = $repos['quizzes']->find((int) $sub['quiz_id']);
        if (!$quiz || ($quiz['status'] ?? '') !== 'published') {
            return $this->error('quizably_quiz_unavailable', __('Quiz unavailable', 'quizably'), 410);
        }

        // Hydrate questions with their answers and load results.
        $questions = $repos['questions']->find_by_quiz((int) $quiz['id']);
        foreach ($questions as &$q) {
            $q['answers'] = $repos['answers']->find_by_question((int) $q['id']);
        }
        unset($q);
        $results = $repos['results']->find_by_quiz((int) $quiz['id']);

        $answers = [];
        if (!empty($sub['answers'])) {
            $decoded = json_decode((string) $sub['answers'], true);
            if (is_array($decoded)) {
                $answers = $decoded;
            }
        }

        $scorer = ScorerRegistry::get((string) $quiz['type']);
        if (!$scorer) {
            return $this->error(
                'quizably_no_scorer',
                sprintf(
                    /* translators: %s: quiz type slug */
                    __('No scorer for type %s', 'quizably'),
                    (string) $quiz['type']
                ),
                500
            );
        }
        $score = $scorer->score($quiz, $questions, $results, $answers);

        $repos['submissions']->complete($uuid, [
            'score'            => $score['score'],
            'result_id'        => $score['result_id'],
            'result_breakdown' => $score['breakdown'],
        ]);

        $result = null;
        if (!empty($score['result_id'])) {
            $result = $repos['results']->find((int) $score['result_id']);
            if ($result) {
                $result = $this->public_result($result);
            }
        }

        do_action('quizably_submission_completed', $uuid, $quiz, $result, $score);

        return $this->ok([
            'result' => $result,
            'score' => $score['score'],
            'breakdown' => $score['breakdown'],
        ]);
    }

    public function submit_optin(\WP_REST_Request $req)
    {
        $uuid = (string) $req['uuid'];
        $repos = $this->repos();
        $sub = $repos['submissions']->find_by_uuid($uuid);
        if (!$sub) {
            return $this->not_found('Submission');
        }

        $email = sanitize_email((string) $req->get_param('email'));
        if (!is_email($email)) {
            return $this->error('quizably_bad_email', __('Valid email required', 'quizably'), 400);
        }

        $extra = $req->get_param('extra_fields');
        // Accept either `consent_gdpr` (canonical) or `consent` (the field
        // name the Form screen, formerly OptIn.vue, historically used) so
        // older builds and the current frontend both work.
        $consent_param = $req->get_param('consent_gdpr');
        if ($consent_param === null) {
            $consent_param = $req->get_param('consent');
        }
        $lead_id = $repos['leads']->insert_or_update([
            'quiz_id'      => (int) $sub['quiz_id'],
            'email'        => $email,
            'name'         => sanitize_text_field((string) $req->get_param('name')),
            'phone'        => sanitize_text_field((string) $req->get_param('phone')),
            'extra_fields' => $this->sanitize_extra_fields($extra),
            'consent_gdpr' => $consent_param ? 1 : 0,
        ]);

        $repos['submissions']->attach_lead($uuid, (int) $lead_id);

        // Double opt-in: the lead is stored as pending and everything that counts a lead (the
        // lead-captured webhook, the owner's new-lead email, analytics) waits for the click.
        // A lead that already confirmed on this quiz is not asked again.
        $lead = $repos['leads']->find((int) $lead_id);
        $already_confirmed = $lead && 1 === (int) ($lead['double_optin_verified'] ?? 0);
        $quiz = $repos['quizzes']->find((int) $sub['quiz_id']);

        if (DoubleOptin::is_enabled($quiz) && ! $already_confirmed) {
            $repos['leads']->set_double_optin_status((int) $lead_id, 0);
            DoubleOptin::send_confirmation($lead ?: ['id' => (int) $lead_id, 'email' => $email], $quiz);

            return $this->ok(['lead_id' => (int) $lead_id, 'double_optin_pending' => true]);
        }

        do_action('quizably_lead_captured', $lead_id, $sub, $req);

        return $this->ok(['lead_id' => (int) $lead_id]);
    }

    public function confirm_optin(\WP_REST_Request $req)
    {
        $lead_id = DoubleOptin::lead_id_from_token((string) $req->get_param('token'));
        if (null === $lead_id) {
            return new \WP_Error('quizably_invalid_token', __('Invalid confirmation token', 'quizably'), ['status' => 400]);
        }

        $outcome = DoubleOptin::confirm($lead_id);
        if ('missing' === $outcome) {
            return new \WP_Error('quizably_invalid_token', __('Invalid confirmation token', 'quizably'), ['status' => 400]);
        }

        return $this->ok(['confirmed' => true, 'lead_id' => $lead_id, 'already_confirmed' => 'already' === $outcome]);
    }

    /**
     * Longest free-text answer we keep. The builder lets an author set a lower
     * limit per question, never a higher one. Keep in step with
     * MAX_TEXT_ANSWER_LENGTH in src/shared/questionTypes.js.
     */
    private const MAX_TEXT_ANSWER_LENGTH = 2000;

    /**
     * A typed or rated answer arriving from the public internet. Only plain
     * scalar text is kept: tags are stripped, and it is cut to a sane length so
     * one visitor cannot store an essay - or markup - in the database. Empty
     * or non-text input is treated as "no text answer".
     *
     * @param mixed $value
     */
    private function sanitize_text_answer($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $text = trim(sanitize_textarea_field((string) $value));
        if ($text === '') {
            return null;
        }

        return function_exists('mb_substr')
            ? mb_substr($text, 0, self::MAX_TEXT_ANSWER_LENGTH, 'UTF-8')
            : substr($text, 0, self::MAX_TEXT_ANSWER_LENGTH);
    }

    /**
     * @param mixed $extra
     * @return array<string,string>|null
     */
    private function sanitize_extra_fields($extra): ?array
    {
        if (!is_array($extra)) {
            return null;
        }
        $sanitized = [];
        foreach ($extra as $field_name => $field_value) {
            $key = sanitize_key((string) $field_name);
            $val = sanitize_text_field((string) $field_value);
            if ($key !== '' && strlen($val) <= 1000) {
                $sanitized[$key] = $val;
            }
        }
        $sanitized = array_slice($sanitized, 0, 20);
        return !empty($sanitized) ? $sanitized : null;
    }

    /**
     * @return array<string,string>|null
     */
    private function parse_utm(\WP_REST_Request $req): ?array
    {
        $keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        $out = [];
        foreach ($keys as $k) {
            $v = $req->get_param($k);
            if ($v !== null && $v !== '') {
                $out[$k] = sanitize_text_field((string) $v);
            }
        }
        return $out ?: null;
    }
}
