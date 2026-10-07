<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

final class AnswerController extends BaseController
{
    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/questions/(?P<id>\d+)/answers', [
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

        register_rest_route(RestBootstrap::NAMESPACE, '/questions/(?P<id>\d+)/answers/reorder', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'reorder'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/answers/(?P<id>\d+)', [
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
        $question_id = (int) $req['id'];
        $repos       = $this->repos();
        if ( ! $repos['questions']->find($question_id) ) {
            return $this->not_found('Question');
        }
        $rows  = $repos['answers']->find_by_question($question_id);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->decode_json_fields($row, ['weights']);
        }
        return $this->ok(['items' => $items, 'total' => count($items)]);
    }

    public function create(\WP_REST_Request $req)
    {
        $question_id = (int) $req['id'];
        $repos       = $this->repos();
        if ( ! $repos['questions']->find($question_id) ) {
            return $this->not_found('Question');
        }
        $label = (string) $req->get_param('label');
        if (trim($label) === '') {
            return $this->error('quizably_bad_request', __('Label is required', 'quizably'), 400);
        }

        $data = [
            'question_id' => $question_id,
            'label'       => $label,
            'value'       => (string) ($req->get_param('value') ?: ''),
            'is_correct'  => $req->get_param('is_correct') ? 1 : 0,
            'points'      => (int) ($req->get_param('points') ?: 0),
            'position'    => (int) ($req->get_param('position') ?: 0),
        ];
        $weights = $req->get_param('weights');
        if (is_array($weights)) {
            $data['weights'] = $weights;
        }
        $prid = $req->get_param('personality_result_id');
        if ($prid !== null && $prid !== '') {
            $data['personality_result_id'] = (int) $prid;
        }
        $media = $req->get_param('media_url');
        if ($media !== null) {
            $data['media_url'] = (string) $media;
        }

        try {
            $aid = $repos['answers']->insert($data);
        } catch (\RuntimeException $e) {
            return $this->error('quizably_db_error', $e->getMessage(), 500);
        }
        return $this->ok(
            $this->decode_json_fields($repos['answers']->find($aid), ['weights']),
            201
        );
    }

    public function update(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['answers']->find($id) ) {
            return $this->not_found('Answer');
        }
        $allowed = ['label', 'value', 'is_correct', 'points', 'weights', 'personality_result_id', 'media_url', 'position'];
        $data    = [];
        foreach ($allowed as $k) {
            $v = $req->get_param($k);
            if ($v === null) {
                continue;
            }
            if ($k === 'is_correct') {
                $data[$k] = $v ? 1 : 0;
            } else {
                $data[$k] = $v;
            }
        }
        $repos['answers']->update($id, $data);
        return $this->ok(
            $this->decode_json_fields($repos['answers']->find($id), ['weights'])
        );
    }

    public function delete(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['answers']->find($id) ) {
            return $this->not_found('Answer');
        }
        $repos['answers']->delete($id);
        return $this->ok(['deleted' => true]);
    }

    public function reorder(\WP_REST_Request $req)
    {
        $question_id = (int) $req['id'];
        $repos       = $this->repos();
        if ( ! $repos['questions']->find($question_id) ) {
            return $this->not_found('Question');
        }
        $order = $req->get_param('order');
        if ( ! is_array($order) ) {
            return $this->error('quizably_bad_request', __('order must be an array of IDs', 'quizably'), 400);
        }
        $order = array_map('intval', $order);

        $existing  = $repos['answers']->find_by_question($question_id);
        $valid_ids = [];
        foreach ($existing as $row) {
            $valid_ids[(int) $row['id']] = true;
        }
        foreach ($order as $aid) {
            if ( ! isset($valid_ids[$aid]) ) {
                return $this->error(
                    'quizably_bad_request',
                    __('One or more IDs do not belong to the question', 'quizably'),
                    400
                );
            }
        }

        $repos['answers']->reorder($question_id, $order);

        $rows  = $repos['answers']->find_by_question($question_id);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->decode_json_fields($row, ['weights']);
        }
        return $this->ok(['items' => $items]);
    }
}
