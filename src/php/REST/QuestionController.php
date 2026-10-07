<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

final class QuestionController extends BaseController
{
    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/questions', [
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

        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/questions/reorder', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'reorder'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/questions/(?P<id>\d+)', [
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
    }

    public function list(\WP_REST_Request $req)
    {
        $quiz_id = (int) $req['id'];
        $repos   = $this->repos();
        if ( ! $repos['quizzes']->find($quiz_id) ) {
            return $this->not_found('Quiz');
        }
        $rows  = $repos['questions']->find_by_quiz($quiz_id);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->decode_json_fields($row, ['settings', 'logic']);
        }
        return $this->ok(['items' => $items, 'total' => count($items)]);
    }

    public function create(\WP_REST_Request $req)
    {
        $quiz_id = (int) $req['id'];
        $repos   = $this->repos();
        if ( ! $repos['quizzes']->find($quiz_id) ) {
            return $this->not_found('Quiz');
        }

        $title = (string) $req->get_param('title');
        if (trim($title) === '') {
            return $this->error('quizably_bad_request', __('Title is required', 'quizably'), 400);
        }

        $data = [
            'quiz_id'     => $quiz_id,
            'type'        => (string) ($req->get_param('type') ?: 'single'),
            'title'       => $title,
            'description' => $this->nullable_html($req->get_param('description')),
            'media_url'   => $this->nullable_string($req->get_param('media_url')),
            'media_type'  => $this->nullable_string($req->get_param('media_type')),
            'required'    => $req->get_param('required') ? 1 : 0,
            'position'    => (int) ($req->get_param('position') ?: 0),
        ];
        $settings = $req->get_param('settings');
        if (is_array($settings)) {
            $data['settings'] = $settings;
        }
        $logic = $req->get_param('logic');
        if (is_array($logic)) {
            $data['logic'] = $logic;
        }

        try {
            $qid = $repos['questions']->insert($data);
        } catch (\RuntimeException $e) {
            return $this->error('quizably_db_error', $e->getMessage(), 500);
        }

        // Handle nested answers on create.
        $answers = $req->get_param('answers');
        if (is_array($answers)) {
            foreach ($answers as $i => $a) {
                if ( ! is_array($a) || ! isset($a['label']) ) {
                    continue;
                }
                $answer_data = [
                    'question_id' => $qid,
                    'label'       => (string) $a['label'],
                    'value'       => (string) ($a['value'] ?? ''),
                    'is_correct'  => ! empty($a['is_correct']) ? 1 : 0,
                    'points'      => (int) ($a['points'] ?? 0),
                    'position'    => (int) ($a['position'] ?? ($i + 1)),
                ];
                if (isset($a['weights']) && is_array($a['weights'])) {
                    $answer_data['weights'] = $a['weights'];
                }
                if (isset($a['personality_result_id'])) {
                    $answer_data['personality_result_id'] = (int) $a['personality_result_id'];
                }
                if (isset($a['media_url'])) {
                    $answer_data['media_url'] = (string) $a['media_url'];
                }
                $repos['answers']->insert($answer_data);
            }
        }

        return $this->ok($this->hydrate_question($qid), 201);
    }

    public function get(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['questions']->find($id) ) {
            return $this->not_found('Question');
        }
        return $this->ok($this->hydrate_question($id));
    }

    public function update(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['questions']->find($id) ) {
            return $this->not_found('Question');
        }
        $allowed = ['type', 'title', 'description', 'media_url', 'media_type', 'required', 'position', 'settings', 'logic'];
        $data    = [];
        foreach ($allowed as $k) {
            $v = $req->get_param($k);
            if ($v === null) {
                continue;
            }
            if ($k === 'required') {
                $data[$k] = $v ? 1 : 0;
            } elseif ($k === 'description') {
                // Description is rich-text HTML — sanitize via wp_kses_post
                // so script/iframe/etc. tags can't slip through, even
                // though TipTap's output is already structured.
                $data[$k] = $this->nullable_html($v);
            } elseif ($k === 'settings' && is_array($v)) {
                $data[$k] = $v;
            } else {
                $data[$k] = $v;
            }
        }
        $repos['questions']->update($id, $data);
        return $this->ok($this->hydrate_question($id));
    }

    public function delete(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['questions']->find($id) ) {
            return $this->not_found('Question');
        }
        $repos['answers']->delete_by_question($id);
        $repos['questions']->delete($id);
        return $this->ok(['deleted' => true]);
    }

    public function reorder(\WP_REST_Request $req)
    {
        $quiz_id = (int) $req['id'];
        $repos   = $this->repos();
        if ( ! $repos['quizzes']->find($quiz_id) ) {
            return $this->not_found('Quiz');
        }
        $order = $req->get_param('order');
        if ( ! is_array($order) ) {
            return $this->error('quizably_bad_request', __('order must be an array of IDs', 'quizably'), 400);
        }
        $order = array_map('intval', $order);

        // Validate every ID belongs to this quiz.
        $existing  = $repos['questions']->find_by_quiz($quiz_id);
        $valid_ids = [];
        foreach ($existing as $row) {
            $valid_ids[(int) $row['id']] = true;
        }
        foreach ($order as $qid) {
            if ( ! isset($valid_ids[$qid]) ) {
                return $this->error(
                    'quizably_bad_request',
                    __('One or more IDs do not belong to the quiz', 'quizably'),
                    400
                );
            }
        }

        $repos['questions']->reorder($quiz_id, $order);

        $rows  = $repos['questions']->find_by_quiz($quiz_id);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->decode_json_fields($row, ['settings', 'logic']);
        }
        return $this->ok(['items' => $items]);
    }

    /**
     * @return array<string,mixed>
     */
    private function hydrate_question(int $id): array
    {
        $repos = $this->repos();
        $q     = $repos['questions']->find($id);
        if ($q === null) {
            return [];
        }
        $q         = $this->decode_json_fields($q, ['settings', 'logic']);
        $answers   = $repos['answers']->find_by_question($id);
        $decoded_a = [];
        foreach ($answers as $a) {
            $decoded_a[] = $this->decode_json_fields($a, ['weights']);
        }
        $q['answers'] = $decoded_a;
        return $q;
    }

    /**
     * @param mixed $value
     */
    private function nullable_string($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = (string) $value;
        return $s === '' ? null : $s;
    }

    /**
     * Sanitize a nullable rich-text HTML field. Allows the same tag set
     * WordPress permits in post content (p, strong, em, ul, ol, li,
     * blockquote, a, etc.) and strips anything not on that allow-list.
     *
     * @param mixed $value
     */
    private function nullable_html($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = wp_kses_post((string) $value);
        return $s === '' ? null : $s;
    }
}
