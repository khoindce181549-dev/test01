<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Question Bank REST surface.
 *
 * Bank rows are templates. Insert copies them into a quiz (per-quiz
 * quizably_questions + quizably_answers) and bumps usage_count — see the §6 plan
 * doc for why we don't reference instead of copy.
 *
 * The Pro plugin extends this surface in two ways:
 *  1. apply_filters('quizably_question_bank_allowed_types', [...]) prepends Pro
 *     question types (image_choice, dropdown, slider, rating)
 *  2. do_action('quizably_question_bank_register_routes', $this) lets Pro
 *     register additional routes (bulk import / export / multi-insert)
 */
final class QuestionBankController extends BaseController
{
    /** Free question types allowed in the bank. Pro filters add more. */
    private const FREE_TYPES = ['single', 'multi', 'truefalse'];

    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/question-bank', [
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

        register_rest_route(RestBootstrap::NAMESPACE, '/question-bank/tags', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'tags'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/question-bank/(?P<id>\d+)', [
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

        register_rest_route(RestBootstrap::NAMESPACE, '/question-bank/(?P<id>\d+)/duplicate', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'duplicate'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/question-bank/(?P<id>\d+)/insert', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'insert_into_quiz'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // Pro extension point: lets Quizably Pro register
        // /question-bank/import, /question-bank/export, etc.
        do_action('quizably_question_bank_register_routes', $this);
    }

    public function list(\WP_REST_Request $req)
    {
        $args = [
            'search'  => (string) ($req->get_param('search') ?: ''),
            'type'    => (string) ($req->get_param('type') ?: ''),
            'tags'    => self::tags_param($req->get_param('tags')),
            'orderby' => (string) ($req->get_param('orderby') ?: 'updated_at'),
            'order'   => (string) ($req->get_param('order') ?: 'DESC'),
            'limit'   => (int) ($req->get_param('limit') ?: 50),
            'offset'  => (int) ($req->get_param('offset') ?: 0),
        ];
        $repos  = $this->repos();
        $result = $repos['question_bank']->search($args);
        $items  = [];
        foreach ($result['items'] as $row) {
            $items[] = $this->hydrate_row($row);
        }
        return $this->ok([
            'items' => $items,
            'total' => $result['total'],
        ]);
    }

    public function get(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        $row   = $repos['question_bank']->find($id);
        if ( ! $row ) {
            return $this->not_found('Bank question');
        }
        return $this->ok($this->hydrate_row($row));
    }

    public function create(\WP_REST_Request $req)
    {
        $title = (string) $req->get_param('title');
        if (trim($title) === '') {
            return $this->error('quizably_bad_request', __('Title is required', 'quizably'), 400);
        }

        $type = (string) ($req->get_param('type') ?: 'single');
        if ( ! in_array($type, self::allowed_types(), true) ) {
            return $this->error('quizably_bad_request', __('Unsupported question type', 'quizably'), 400);
        }

        $data = [
            'type'        => $type,
            'title'       => $title,
            'description' => $this->nullable_string($req->get_param('description')),
            'media_url'   => $this->nullable_string($req->get_param('media_url')),
            'media_type'  => $this->nullable_string($req->get_param('media_type')),
            'tags'        => $req->get_param('tags'),
            'author_id'   => (int) get_current_user_id(),
        ];

        $settings = $req->get_param('settings');
        if (is_array($settings)) {
            $data['settings'] = $settings;
        }
        $answers = $req->get_param('answers');
        if (is_array($answers)) {
            $data['answers'] = self::sanitize_answers($answers);
        }

        $repos = $this->repos();
        try {
            $id = $repos['question_bank']->insert($data);
        } catch (\RuntimeException $e) {
            return $this->error('quizably_db_error', $e->getMessage(), 500);
        }
        return $this->ok($this->hydrate_row($repos['question_bank']->find($id)), 201);
    }

    public function update(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['question_bank']->find($id) ) {
            return $this->not_found('Bank question');
        }

        $allowed = ['type', 'title', 'description', 'media_url', 'media_type', 'settings', 'answers', 'tags'];
        $data    = [];
        foreach ($allowed as $k) {
            $v = $req->get_param($k);
            if ($v === null) {
                continue;
            }
            if ('answers' === $k && is_array($v)) {
                $data[$k] = self::sanitize_answers($v);
                continue;
            }
            $data[$k] = $v;
        }

        if (isset($data['type']) && ! in_array($data['type'], self::allowed_types(), true)) {
            return $this->error('quizably_bad_request', __('Unsupported question type', 'quizably'), 400);
        }

        $repos['question_bank']->update($id, $data);
        return $this->ok($this->hydrate_row($repos['question_bank']->find($id)));
    }

    public function delete(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['question_bank']->find($id) ) {
            return $this->not_found('Bank question');
        }
        $repos['question_bank']->delete($id);
        return $this->ok(['deleted' => true]);
    }

    public function duplicate(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        $src   = $repos['question_bank']->find($id);
        if ( ! $src ) {
            return $this->not_found('Bank question');
        }
        $copy = [
            'type'        => $src['type'],
            /* translators: %s: title of the bank question being duplicated */
            'title'       => sprintf(__('%s (copy)', 'quizably'), $src['title']),
            'description' => $src['description'],
            'media_url'   => $src['media_url'],
            'media_type'  => $src['media_type'],
            'settings'    => json_decode((string) $src['settings'], true),
            'answers'     => json_decode((string) $src['answers'], true),
            'tags'        => $src['tags'],
            'author_id'   => (int) get_current_user_id(),
        ];
        $newId = $repos['question_bank']->insert($copy);
        return $this->ok($this->hydrate_row($repos['question_bank']->find($newId)), 201);
    }

    /**
     * Copy a bank question into a quiz: creates one quizably_questions row and
     * one quizably_answers row per stored answer, sets bank_origin_id, and
     * increments the bank row's usage_count. Returns the newly-created
     * quiz question (hydrated) so the client can append it to its list.
     */
    public function insert_into_quiz(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        $bank  = $repos['question_bank']->find($id);
        if ( ! $bank ) {
            return $this->not_found('Bank question');
        }

        $quiz_id = (int) $req->get_param('quiz_id');
        if ($quiz_id <= 0 || ! $repos['quizzes']->find($quiz_id)) {
            return $this->error('quizably_bad_request', __('Valid quiz_id is required', 'quizably'), 400);
        }

        $position = (int) ($req->get_param('position') ?: 0);
        if ($position <= 0) {
            $existing  = $repos['questions']->find_by_quiz($quiz_id);
            $position  = count($existing) + 1;
        }

        // Copy core question fields. The new quizably_questions row has its own
        // identity; subsequent edits don't propagate back to the bank.
        $questionData = [
            'quiz_id'        => $quiz_id,
            'bank_origin_id' => $id,
            'type'           => $bank['type'],
            'title'          => $bank['title'],
            'description'    => $bank['description'],
            'media_url'      => $bank['media_url'],
            'media_type'     => $bank['media_type'],
            'required'       => 0,
            'position'       => $position,
        ];

        $bankSettings = json_decode((string) $bank['settings'], true);
        if (is_array($bankSettings)) {
            $questionData['settings'] = $bankSettings;
        }

        try {
            $newQid = $repos['questions']->insert($questionData);
        } catch (\RuntimeException $e) {
            return $this->error('quizably_db_error', $e->getMessage(), 500);
        }

        // Now copy each stored answer into quizably_answers under the new question.
        $bankAnswers = json_decode((string) $bank['answers'], true);
        if (is_array($bankAnswers)) {
            $i = 1;
            foreach ($bankAnswers as $a) {
                if ( ! is_array($a) || ! isset($a['label']) ) {
                    continue;
                }
                $answerData = [
                    'question_id' => $newQid,
                    'label'       => (string) $a['label'],
                    'value'       => (string) ($a['value'] ?? ''),
                    'is_correct'  => ! empty($a['is_correct']) ? 1 : 0,
                    'points'      => (int) ($a['points'] ?? 0),
                    'position'    => (int) ($a['position'] ?? $i),
                ];
                if (isset($a['weights']) && is_array($a['weights'])) {
                    $answerData['weights'] = $a['weights'];
                }
                if (isset($a['media_url'])) {
                    $answerData['media_url'] = (string) $a['media_url'];
                }
                $repos['answers']->insert($answerData);
                $i++;
            }
        }

        // Bump usage so the bank list reflects "Used in N quizzes".
        $repos['question_bank']->increment_usage($id);

        // Return the new quiz question hydrated like QuestionController does
        // so the client can splice it directly into its store.
        $newRow     = $repos['questions']->find($newQid);
        $newRow     = $this->decode_json_fields($newRow, ['settings', 'logic']);
        $newAnswers = $repos['answers']->find_by_question($newQid);
        $decodedA   = [];
        foreach ($newAnswers as $a) {
            $decodedA[] = $this->decode_json_fields($a, ['weights']);
        }
        $newRow['answers'] = $decodedA;
        return $this->ok($newRow, 201);
    }

    public function tags(\WP_REST_Request $req)
    {
        $repos = $this->repos();
        return $this->ok(['items' => $repos['question_bank']->tags_index()]);
    }

    /**
     * Filter-extensible question-type allowlist. Pro plugin prepends its
     * own types here; Free always includes single/multi/truefalse.
     *
     * @return array<int,string>
     */
    public static function allowed_types(): array
    {
        $types = apply_filters('quizably_question_bank_allowed_types', self::FREE_TYPES);
        if ( ! is_array($types) ) {
            $types = self::FREE_TYPES;
        }
        return array_values(array_unique(array_filter(array_map('strval', $types))));
    }

    /**
     * Hydrate a stored bank row for the API: decode JSON columns and
     * convert the comma-separated tag string into a plain array.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function hydrate_row(array $row): array
    {
        $row = $this->decode_json_fields($row, ['settings', 'answers']);
        $row['tags'] = empty($row['tags'])
            ? []
            : array_values(array_filter(array_map('trim', explode(',', (string) $row['tags']))));
        $row['usage_count'] = isset($row['usage_count']) ? (int) $row['usage_count'] : 0;
        return $row;
    }

    /**
     * Strip unsupported keys from each incoming answer record. We accept
     * the same shape AnswerRepository::insert() takes, but in JSON form.
     *
     * @param array<int,mixed> $answers
     * @return array<int,array<string,mixed>>
     */
    private static function sanitize_answers(array $answers): array
    {
        $out = [];
        foreach ($answers as $i => $a) {
            if ( ! is_array($a) || ! isset($a['label']) ) {
                continue;
            }
            $clean = [
                'label'      => (string) $a['label'],
                'value'      => (string) ($a['value'] ?? ''),
                'is_correct' => ! empty($a['is_correct']),
                'points'     => (int) ($a['points'] ?? 0),
                'position'   => (int) ($a['position'] ?? ($i + 1)),
            ];
            if (isset($a['weights']) && is_array($a['weights'])) {
                $clean['weights'] = $a['weights'];
            }
            if (isset($a['media_url'])) {
                $clean['media_url'] = (string) $a['media_url'];
            }
            $out[] = $clean;
        }
        return $out;
    }

    /**
     * Tags param accepts either an array (`tags[]=foo&tags[]=bar`) or a
     * single comma-separated string. Normalize to array.
     *
     * @param mixed $value
     * @return array<int,string>
     */
    private static function tags_param($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }
        return [];
    }

    /** @param mixed $value */
    private function nullable_string($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = (string) $value;
        return $s === '' ? null : $s;
    }
}
