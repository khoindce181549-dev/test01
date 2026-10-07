<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

use Quizably\Quiz\NewQuizDefaults;

final class QuizController extends BaseController
{
    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'list'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        // Import route must be registered before the (?P<id>\d+) catch-all.
        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/import', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'import_quiz'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/duplicate', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'duplicate'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/publish', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'publish'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/test-webhook', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'test_webhook'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // Export route — must come before the (?P<id>\d+) catch-all would
        // match "export" as an id, but since "export" isn't numeric it won't.
        // Registered here for clarity; the regex is safely distinct.
        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/export', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'export_quiz'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    public function list(\WP_REST_Request $req)
    {
        $filters = [
            'status'  => (string) $req->get_param('status'),
            'type'    => (string) $req->get_param('type'),
            'search'  => (string) $req->get_param('search'),
            'orderby' => (string) $req->get_param('orderby'),
            'order'   => (string) $req->get_param('order'),
            'limit'   => (int) ($req->get_param('limit') ?: 20),
            'offset'  => (int) ($req->get_param('offset') ?: 0),
        ];
        $repos = $this->repos();
        $rows  = $repos['quizzes']->list($filters);
        $total = $repos['quizzes']->count($filters);

        $quiz_ids   = array_map(fn($r) => (int) $r['id'], $rows);
        $sub_counts = $repos['submissions']->count_by_quiz_batch($quiz_ids);

        $items = array_map(function (array $row) use ($sub_counts) {
            $row              = $this->decode_json_fields($row, ['settings', 'design']);
            $quiz_id          = (int) $row['id'];
            $counts           = $sub_counts[$quiz_id] ?? ['total' => 0, 'completed' => 0];
            $row['submission_count'] = $counts['total'];
            $row['completion_rate']  = $counts['total'] > 0
                ? round($counts['completed'] / $counts['total'], 3)
                : 0.0;
            return $row;
        }, $rows);

        return $this->ok(['items' => $items, 'total' => $total]);
    }

    public function get(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        $quiz  = $repos['quizzes']->find($id);
        if ( ! $quiz ) {
            return $this->not_found('Quiz');
        }
        $quiz = $this->decode_json_fields($quiz, ['settings', 'design']);

        $questions = $repos['questions']->find_by_quiz($id);
        foreach ($questions as &$q) {
            $q       = $this->decode_json_fields($q, ['settings', 'logic']);
            $answers = $repos['answers']->find_by_question((int) $q['id']);
            $decoded = [];
            foreach ($answers as $a) {
                $decoded[] = $this->decode_json_fields($a, ['weights']);
            }
            $q['answers'] = $decoded;
        }
        unset($q);

        $results = $repos['results']->find_by_quiz($id);
        $decoded_results = [];
        foreach ($results as $r) {
            $decoded_results[] = $this->decode_json_fields($r, ['conditions', 'settings']);
        }

        $quiz['questions'] = $questions;
        $quiz['results']   = $decoded_results;

        return $this->ok($quiz);
    }

    public function create(\WP_REST_Request $req)
    {
        $title = trim((string) $req->get_param('title'));
        if ('' === $title) {
            return $this->error('quizably_bad_request', __('Title is required', 'quizably'), 400);
        }
        $slug     = sanitize_title((string) ($req->get_param('slug') ?: $title));
        $type     = (string) ($req->get_param('type') ?: 'personality');

        $repos = $this->repos();

        // Settings -> Branding / Defaults: the template (when the caller did not pick one), brand
        // colours, typeface, button shape and form position a new quiz starts with.
        $defaults = NewQuizDefaults::build(
            isset($repos['settings']) ? $repos['settings']->all() : [],
            (string) $req->get_param('template')
        );
        $template = $defaults['template'];

        if ( $repos['quizzes']->find_by_slug($slug) ) {
            return $this->error('quizably_slug_conflict', __('Slug already in use', 'quizably'), 409);
        }
        $row = [
            'title'     => $title,
            'slug'      => $slug,
            'type'      => $type,
            'template'  => $template,
            'status'    => 'published',
            'author_id' => get_current_user_id(),
        ];
        if (null !== $defaults['design']) {
            $row['design'] = $defaults['design'];
        }
        if (null !== $defaults['settings']) {
            $row['settings'] = $defaults['settings'];
        }
        try {
            // New quizzes ship Published by default — the user can flip a
            // single quiz back to Draft from the listing page (Set-to-draft
            // icon on the row, or kebab menu on the card). The repository
            // default stays 'draft' so duplicates are not auto-published.
            $id = $repos['quizzes']->insert($row);
        } catch (\RuntimeException $e) {
            return $this->error('quizably_db_error', $e->getMessage(), 500);
        }
        return $this->ok(
            $this->decode_json_fields($repos['quizzes']->find($id), ['settings', 'design']),
            201
        );
    }

    public function update(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['quizzes']->find($id) ) {
            return $this->not_found('Quiz');
        }
        $allowed = ['title', 'slug', 'type', 'status', 'template', 'settings', 'design'];
        $data    = [];
        foreach ($allowed as $k) {
            $v = $req->get_param($k);
            if (null !== $v) {
                if ('settings' === $k && is_array($v)) {
                    // Defence-in-depth: drop Pro-only quiz-level setting keys
                    // when Pro isn't active so a stale UI can't smuggle them
                    // into the row after Pro deactivation. 'timer' here is the
                    // quiz-level/global timer (settings.timer) — free ships no
                    // runtime for it (unlike the per-question timer on each
                    // question, which is free); Pro's window.Quizably._timer is the
                    // only thing that renders it. randomize_questions was freed:
                    // useRandomizedAnswers.js already runs unconditionally.
                    $data[$k] = \Quizably\Pro\Gate::strip_pro_keys($v, [
                        'timer',
                        'progress_bar',
                    ]);
                } else {
                    $data[$k] = $v;
                }
            }
        }
        $repos['quizzes']->update($id, $data);
        return $this->ok(
            $this->decode_json_fields($repos['quizzes']->find($id), ['settings', 'design'])
        );
    }

    public function delete(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $force = in_array((string) $req->get_param('force'), ['1', 'true', 'yes'], true);
        $repos = $this->repos();
        if ( ! $repos['quizzes']->find($id) ) {
            return $this->not_found('Quiz');
        }
        if ($force) {
            $repos['answers']->delete_by_quiz($id);
            $repos['questions']->delete_by_quiz($id);
            $repos['results']->delete_by_quiz($id);
            $repos['submissions']->delete_by_quiz($id);
            $repos['leads']->delete_by_quiz($id);
            $repos['quizzes']->delete($id);
            return $this->ok(['deleted' => true, 'force' => true]);
        }
        $repos['quizzes']->update($id, ['status' => 'archived']);
        return $this->ok(['archived' => true]);
    }

    public function duplicate(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        $src   = $repos['quizzes']->find($id);
        if ( ! $src ) {
            return $this->not_found('Quiz');
        }

        // Find a unique slug (append -copy, -copy-2, ...).
        $base_slug = $src['slug'] . '-copy';
        $slug      = $base_slug;
        $i         = 2;
        while ($repos['quizzes']->find_by_slug($slug)) {
            $slug = $base_slug . '-' . $i;
            $i++;
        }

        $new_id = $repos['quizzes']->insert([
            'title'     => $src['title'] . ' (Copy)',
            'slug'      => $slug,
            'type'      => $src['type'],
            'template'  => $src['template'],
            'settings'  => $this->decode_json_string($src['settings'] ?? null),
            'design'    => $this->decode_json_string($src['design'] ?? null),
            'author_id' => get_current_user_id(),
        ]);

        // Copy results first so we can remap personality_result_id on answers.
        $result_map = [];
        foreach ($repos['results']->find_by_quiz($id) as $r) {
            $new_rid = $repos['results']->insert([
                'quiz_id'      => $new_id,
                'title'        => (string) $r['title'],
                'content'      => $r['content'] ?? null,
                'image_url'    => $r['image_url'] ?? null,
                'cta_label'    => $r['cta_label'] ?? null,
                'cta_url'      => $r['cta_url'] ?? null,
                'redirect_url' => $r['redirect_url'] ?? null,
                'score_min'    => isset($r['score_min']) && $r['score_min'] !== null ? (int) $r['score_min'] : null,
                'score_max'    => isset($r['score_max']) && $r['score_max'] !== null ? (int) $r['score_max'] : null,
                'conditions'   => $this->decode_json_string($r['conditions'] ?? null),
                'settings'     => $this->decode_json_string($r['settings'] ?? null), // background, alignment, chart
                'position'     => (int) ($r['position'] ?? 0),
            ]);
            $result_map[(int) $r['id']] = $new_rid;
        }

        // Copy questions + answers, remapping personality_result_id.
        foreach ($repos['questions']->find_by_quiz($id) as $q) {
            $new_qid = $repos['questions']->insert([
                'quiz_id'     => $new_id,
                'type'        => (string) ($q['type'] ?? 'single'),
                'title'       => (string) $q['title'],
                'description' => $q['description'] ?? null,
                'media_url'   => $q['media_url'] ?? null,
                'media_type'  => $q['media_type'] ?? null,
                'required'    => ! empty($q['required']) ? 1 : 0,
                'position'    => (int) ($q['position'] ?? 0),
                'settings'    => $this->decode_json_string($q['settings'] ?? null),
                'logic'       => $this->decode_json_string($q['logic'] ?? null),
            ]);

            foreach ($repos['answers']->find_by_question((int) $q['id']) as $a) {
                $mapped_result = null;
                if ( ! empty($a['personality_result_id'])
                    && isset($result_map[(int) $a['personality_result_id']]) ) {
                    $mapped_result = $result_map[(int) $a['personality_result_id']];
                }
                $repos['answers']->insert([
                    'question_id'           => $new_qid,
                    'label'                 => (string) $a['label'],
                    'value'                 => (string) ($a['value'] ?? ''),
                    'is_correct'            => ! empty($a['is_correct']) ? 1 : 0,
                    'points'                => (int) ($a['points'] ?? 0),
                    'weights'               => $this->decode_json_string($a['weights'] ?? null),
                    'personality_result_id' => $mapped_result,
                    'media_url'             => $a['media_url'] ?? null,
                    'position'              => (int) ($a['position'] ?? 0),
                ]);
            }
        }

        return $this->ok(
            $this->decode_json_fields($repos['quizzes']->find($new_id), ['settings', 'design']),
            201
        );
    }

    public function publish(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['quizzes']->find($id) ) {
            return $this->not_found('Quiz');
        }
        $published = $req->get_param('published');
        $unpublish = (false === $published
            || 'false' === $published
            || '0' === (string) $published);
        $status = $unpublish ? 'draft' : 'published';
        $repos['quizzes']->update($id, ['status' => $status]);
        return $this->ok(
            $this->decode_json_fields($repos['quizzes']->find($id), ['settings', 'design'])
        );
    }

    public function test_webhook(\WP_REST_Request $req)
    {
        $id  = (int) $req['id'];
        $repos = $this->repos();
        $quiz  = $repos['quizzes']->find($id);
        if ( ! $quiz ) {
            return $this->not_found('Quiz');
        }

        $webhook_url    = trim((string) ($req->get_param('webhook_url') ?? ''));
        $webhook_secret = trim((string) ($req->get_param('webhook_secret') ?? ''));

        if ( '' === $webhook_url ) {
            return $this->error('quizably_bad_request', __('webhook_url is required.', 'quizably'), 400);
        }

        // wp_http_validate_url rejects non-HTTP(S) URLs and some unsafe forms.
        // Admin-only endpoint so full SSRF protection is not required; the
        // built-in validator is sufficient.
        if ( ! wp_http_validate_url($webhook_url) ) {
            return $this->error('quizably_bad_request', __('webhook_url is not a valid HTTP/HTTPS URL.', 'quizably'), 400);
        }

        $payload = [
            'event'       => 'test',
            'quiz_uuid'   => $quiz['uuid'] ?? '',
            'quiz_title'  => $quiz['title'] ?? '',
            'timestamp'   => gmdate('c'),
        ];

        $body    = wp_json_encode($payload);
        $headers = [ 'Content-Type' => 'application/json' ];

        if ( '' !== $webhook_secret ) {
            $headers['X-Quizably-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $webhook_secret);
        }

        $response = wp_remote_post($webhook_url, [
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 6,
        ]);

        if ( is_wp_error($response) ) {
            return $this->error(
                'quizably_webhook_error',
                $response->get_error_message(),
                500
            );
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ( $code >= 200 && $code < 300 ) {
            return $this->ok([
                'ok'          => true,
                'http_status' => $code,
                'message'     => 'Test delivered',
            ]);
        }

        return $this->ok([
            'ok'          => false,
            'http_status' => $code,
            'message'     => 'HTTP ' . $code,
        ]);
    }

    public function export_quiz(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        $quiz  = $repos['quizzes']->find($id);
        if ( ! $quiz ) {
            return $this->not_found('Quiz');
        }

        $quiz = $this->decode_json_fields($quiz, ['settings', 'design']);

        $questions = $repos['questions']->find_by_quiz($id);
        foreach ($questions as &$q) {
            $q        = $this->decode_json_fields($q, ['settings', 'logic']);
            $answers  = $repos['answers']->find_by_question((int) $q['id']);
            $decoded  = [];
            foreach ($answers as $a) {
                $decoded[] = $this->decode_json_fields($a, ['weights']);
            }
            $q['answers'] = $decoded;
        }
        unset($q);

        $results = $repos['results']->find_by_quiz($id);
        $decoded_results = [];
        foreach ($results as $r) {
            $decoded_results[] = $this->decode_json_fields($r, ['conditions', 'settings']);
        }

        return $this->ok([
            'version'     => '1.0',
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'quiz'        => $quiz,
            'questions'   => $questions,
            'results'     => $decoded_results,
        ]);
    }

    public function import_quiz(\WP_REST_Request $req)
    {
        $body = $req->get_json_params();
        if ( empty($body) || ! is_array($body) ) {
            return $this->error('quizably_bad_request', __('Request body must be a valid JSON export.', 'quizably'), 400);
        }

        $src_quiz      = $body['quiz'] ?? null;

        if ( ! is_array($src_quiz) || empty($src_quiz['title']) ) {
            return $this->error('quizably_bad_request', __('Export payload is missing quiz data.', 'quizably'), 400);
        }

        // Accept questions/results at the top level (full export envelope) or
        // nested inside src_quiz (legacy single-object format).
        $src_questions = $body['questions'] ?? ($src_quiz['questions'] ?? []);
        $src_results   = $body['results']   ?? ($src_quiz['results']   ?? []);

        $repos = $this->repos();

        // Find a unique slug for the imported quiz.
        $base_slug = sanitize_title((string) ($src_quiz['slug'] ?? $src_quiz['title'] ?? 'imported-quiz'));
        $slug      = $base_slug;
        $i         = 2;
        while ($repos['quizzes']->find_by_slug($slug)) {
            $slug = $base_slug . '-' . $i;
            $i++;
        }

        try {
            $new_quiz_id = $repos['quizzes']->insert([
                'title'     => (string) $src_quiz['title'],
                'slug'      => $slug,
                'type'      => (string) ($src_quiz['type'] ?? 'personality'),
                'template'  => (string) ($src_quiz['template'] ?? 'classic'),
                'status'    => 'draft',
                'settings'  => $this->decode_json_string($src_quiz['settings'] ?? null),
                'design'    => $this->decode_json_string($src_quiz['design'] ?? null),
                'author_id' => get_current_user_id(),
            ]);
        } catch (\RuntimeException $e) {
            return $this->error('quizably_db_error', $e->getMessage(), 500);
        }

        // Import results first so we can remap personality_result_id on answers.
        $result_id_map = [];
        foreach ((array) $src_results as $r) {
            if ( ! is_array($r) ) {
                continue;
            }
            try {
                $new_rid = $repos['results']->insert([
                    'quiz_id'      => $new_quiz_id,
                    'title'        => (string) ($r['title'] ?? ''),
                    'content'      => $r['content'] ?? null,
                    'image_url'    => $r['image_url'] ?? null,
                    'cta_label'    => $r['cta_label'] ?? null,
                    'cta_url'      => $r['cta_url'] ?? null,
                    'redirect_url' => $r['redirect_url'] ?? null,
                    'score_min'    => isset($r['score_min']) && $r['score_min'] !== null ? (int) $r['score_min'] : null,
                    'score_max'    => isset($r['score_max']) && $r['score_max'] !== null ? (int) $r['score_max'] : null,
                    'conditions'   => $this->decode_json_string($r['conditions'] ?? null),
                    'settings'     => $this->decode_json_string($r['settings'] ?? null), // background, alignment, chart
                    'position'     => (int) ($r['position'] ?? 0),
                ]);
                if ( ! empty($r['id']) ) {
                    $result_id_map[(string) $r['id']] = $new_rid;
                }
            } catch (\RuntimeException $e) {
                // Swallow individual result errors — partial import is better than none.
            }
        }

        // Import questions + answers, remapping old IDs to new ones.
        foreach ((array) $src_questions as $q) {
            if ( ! is_array($q) ) {
                continue;
            }
            try {
                $new_qid = $repos['questions']->insert([
                    'quiz_id'     => $new_quiz_id,
                    'type'        => (string) ($q['type'] ?? 'single'),
                    'title'       => (string) ($q['title'] ?? ''),
                    'description' => $q['description'] ?? null,
                    'media_url'   => $q['media_url'] ?? null,
                    'media_type'  => $q['media_type'] ?? null,
                    'required'    => ! empty($q['required']) ? 1 : 0,
                    'position'    => (int) ($q['position'] ?? 0),
                    'settings'    => $this->decode_json_string($q['settings'] ?? null),
                    'logic'       => $this->decode_json_string($q['logic'] ?? null),
                ]);
            } catch (\RuntimeException $e) {
                continue;
            }

            foreach ((array) ($q['answers'] ?? []) as $a) {
                if ( ! is_array($a) ) {
                    continue;
                }
                $mapped_result = null;
                if ( ! empty($a['personality_result_id']) ) {
                    $old_rid = (string) $a['personality_result_id'];
                    if ( isset($result_id_map[$old_rid]) ) {
                        $mapped_result = $result_id_map[$old_rid];
                    }
                }
                try {
                    $repos['answers']->insert([
                        'question_id'           => $new_qid,
                        'label'                 => (string) ($a['label'] ?? ''),
                        'value'                 => (string) ($a['value'] ?? ''),
                        'is_correct'            => ! empty($a['is_correct']) ? 1 : 0,
                        'points'                => (int) ($a['points'] ?? 0),
                        'weights'               => $this->decode_json_string($a['weights'] ?? null),
                        'personality_result_id' => $mapped_result,
                        'media_url'             => $a['media_url'] ?? null,
                        'position'              => (int) ($a['position'] ?? 0),
                    ]);
                } catch (\RuntimeException $e) {
                    // Swallow individual answer errors.
                }
            }
        }

        return $this->ok(
            $this->decode_json_fields($repos['quizzes']->find($new_quiz_id), ['settings', 'design']),
            201
        );
    }

    /**
     * Decode a JSON string column into a PHP array. Returns null for null/empty
     * values or invalid JSON. If the value is already an array (e.g. already
     * decoded elsewhere), it is returned as-is.
     *
     * @param mixed $value
     * @return array|null
     */
    protected function decode_json_string($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);
        if (JSON_ERROR_NONE !== json_last_error() || ! is_array($decoded)) {
            return null;
        }
        return $decoded;
    }
}
