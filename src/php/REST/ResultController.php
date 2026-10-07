<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

final class ResultController extends BaseController
{
    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/results', [
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

        register_rest_route(RestBootstrap::NAMESPACE, '/results/(?P<id>\d+)', [
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
        $rows  = $repos['results']->find_by_quiz($quiz_id);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->decode_json_fields($row, ['conditions', 'settings']);
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
            'quiz_id'      => $quiz_id,
            'title'        => $title,
            'content'      => $this->nullable_html($req->get_param('content')),
            'image_url'    => $this->pass_nullable($req->get_param('image_url')),
            'cta_label'    => $this->pass_nullable($req->get_param('cta_label')),
            'cta_url'      => $this->pass_nullable($req->get_param('cta_url')),
            'redirect_url' => $this->pass_nullable($req->get_param('redirect_url')),
            'position'     => (int) ($req->get_param('position') ?: 0),
        ];
        $score_min = $req->get_param('score_min');
        if ($score_min !== null && $score_min !== '') {
            $data['score_min'] = (int) $score_min;
        }
        $score_max = $req->get_param('score_max');
        if ($score_max !== null && $score_max !== '') {
            $data['score_max'] = (int) $score_max;
        }
        $conditions = $req->get_param('conditions');
        if (is_array($conditions)) {
            $data['conditions'] = $conditions;
        }
        $settings = $req->get_param('settings');
        if (is_array($settings)) {
            $data['settings'] = $settings;
        }

        try {
            $rid = $repos['results']->insert($data);
        } catch (\RuntimeException $e) {
            return $this->error('quizably_db_error', $e->getMessage(), 500);
        }
        return $this->ok(
            $this->decode_json_fields($repos['results']->find($rid), ['conditions', 'settings']),
            201
        );
    }

    public function update(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['results']->find($id) ) {
            return $this->not_found('Result');
        }
        $allowed = ['title', 'content', 'image_url', 'cta_label', 'cta_url', 'redirect_url', 'score_min', 'score_max', 'conditions', 'settings', 'position'];
        $data    = [];
        foreach ($allowed as $k) {
            $v = $req->get_param($k);
            if ($v === null) {
                continue;
            }
            if ($k === 'content') {
                // Result content is rich-text HTML — sanitize via
                // wp_kses_post so script/iframe/etc. can't slip through.
                $data[$k] = $this->nullable_html($v);
            } elseif ($k === 'settings' && is_array($v)) {
                $data[$k] = $v;
            } else {
                $data[$k] = $v;
            }
        }
        $repos['results']->update($id, $data);
        return $this->ok(
            $this->decode_json_fields($repos['results']->find($id), ['conditions', 'settings'])
        );
    }

    public function delete(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['results']->find($id) ) {
            return $this->not_found('Result');
        }
        $repos['results']->delete($id);
        return $this->ok(['deleted' => true]);
    }

    /**
     * @param mixed $v
     * @return string|null
     */
    private function pass_nullable($v): ?string
    {
        if ($v === null) {
            return null;
        }
        return (string) $v;
    }

    /**
     * Sanitize a nullable rich-text HTML field. Allows the same tag set
     * WordPress permits in post content (p, strong, em, ul, ol, li,
     * blockquote, a, etc.) and strips anything not on that allow-list.
     *
     * @param mixed $v
     */
    private function nullable_html($v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = wp_kses_post((string) $v);
        return $s === '' ? null : $s;
    }
}
