<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

final class SubmissionController extends BaseController
{
    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/submissions', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'list'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/submissions/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    public function list(\WP_REST_Request $req)
    {
        $quiz_id = (int) $req['id'];
        $repos   = $this->repos();
        if ( ! $repos['quizzes']->find($quiz_id) ) {
            return $this->not_found('Quiz');
        }
        $filters = [
            'status'    => (string) ($req->get_param('status') ?: ''),
            'date_from' => (string) ($req->get_param('date_from') ?: ''),
            'date_to'   => (string) ($req->get_param('date_to') ?: ''),
            'limit'     => (int) ($req->get_param('limit') ?: 20),
            'offset'    => (int) ($req->get_param('offset') ?: 0),
        ];

        $rows  = $repos['submissions']->list_by_quiz($quiz_id, $filters);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->decode_json_fields($row, ['answers', 'result_breakdown', 'utm']);
        }

        // Total count respects status filter but not limit/offset.
        $total = $repos['submissions']->count_by_quiz(
            $quiz_id,
            ! empty($filters['status']) ? $filters['status'] : null
        );

        return $this->ok(['items' => $items, 'total' => $total]);
    }

    public function get(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        $row   = $repos['submissions']->find($id);
        if ($row === null) {
            return $this->not_found('Submission');
        }
        $row = $this->decode_json_fields($row, ['answers', 'result_breakdown', 'utm']);

        if ( ! empty($row['lead_id']) ) {
            $lead = $repos['leads']->find((int) $row['lead_id']);
            if ($lead !== null) {
                $lead = $this->decode_json_fields($lead, ['extra_fields', 'integration_sync_status']);
                $row['lead'] = $lead;
            }
        }

        return $this->ok($row);
    }
}
