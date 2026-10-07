<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

final class LeadController extends BaseController
{
    public function register_routes(): void
    {
        // Global leads list — quiz_id is an optional query param; omit to get all leads.
        register_rest_route(RestBootstrap::NAMESPACE, '/leads', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'list_all'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/leads', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'list'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/quizzes/(?P<id>\d+)/leads/export', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'export'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // Site-wide export — quiz_id is an optional query param, so this
        // covers both "export everything currently filtered" (All quizzes)
        // and "export this one quiz" without needing the path-scoped route.
        register_rest_route(RestBootstrap::NAMESPACE, '/leads/export', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'export_all'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/leads/(?P<id>\d+)/detail', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'detail'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/leads/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'delete'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    public function list_all(\WP_REST_Request $req)
    {
        $filters = [];

        $quiz_id = (int) ($req->get_param('quiz_id') ?: 0);
        if ($quiz_id > 0) {
            $filters['quiz_id'] = $quiz_id;
        }

        $search = (string) ($req->get_param('search') ?: '');
        if ($search !== '') {
            $filters['email'] = $search;
        }
        $df = (string) ($req->get_param('date_from') ?: '');
        if ($df !== '') {
            $filters['date_from'] = $df;
        }
        $dt = (string) ($req->get_param('date_to') ?: '');
        if ($dt !== '') {
            $filters['date_to'] = $dt;
        }
        $filters['limit']  = (int) ($req->get_param('limit') ?: 20);
        $filters['offset'] = (int) ($req->get_param('offset') ?: 0);

        $repos = $this->repos();
        $rows  = $repos['leads']->list($filters);
        $items = $this->hydrate_leads($rows);

        $count_filters = $filters;
        unset($count_filters['limit'], $count_filters['offset']);
        $total = $repos['leads']->count($count_filters);

        return $this->ok(['items' => $items, 'total' => $total]);
    }

    public function list(\WP_REST_Request $req)
    {
        $quiz_id = (int) $req['id'];
        $repos   = $this->repos();
        if ( ! $repos['quizzes']->find($quiz_id) ) {
            return $this->not_found('Quiz');
        }
        $filters           = $this->build_common_lead_filters($req, $quiz_id);
        $filters['limit']  = (int) ($req->get_param('limit') ?: 20);
        $filters['offset'] = (int) ($req->get_param('offset') ?: 0);
        $search = (string) ($req->get_param('search') ?: '');
        if ($search !== '') {
            $filters['email'] = $search;
        }

        $rows  = $repos['leads']->list($filters);
        $items = $this->hydrate_leads($rows);

        $count_filters = $filters;
        unset($count_filters['limit'], $count_filters['offset']);
        $total = $repos['leads']->count($count_filters);

        return $this->ok(['items' => $items, 'total' => $total]);
    }

    public function export(\WP_REST_Request $req)
    {
        $quiz_id = (int) $req['id'];
        $repos   = $this->repos();
        if ( ! $repos['quizzes']->find($quiz_id) ) {
            return $this->not_found('Quiz');
        }

        $filters = $this->build_common_lead_filters($req, $quiz_id);
        $this->apply_search_filter($req, $filters);

        return $this->stream_leads_csv($filters, sprintf('leads-%s-%d.csv', gmdate('Y-m-d'), $quiz_id));
    }

    /**
     * Site-wide export — mirrors list_all()'s filters (quiz_id optional) so
     * exporting "whatever is showing in the list" works whether the leads
     * table is scoped to one quiz or showing every quiz.
     */
    public function export_all(\WP_REST_Request $req)
    {
        $filters = [];

        $quiz_id = (int) ($req->get_param('quiz_id') ?: 0);
        if ($quiz_id > 0) {
            $filters['quiz_id'] = $quiz_id;
        }
        $df = (string) ($req->get_param('date_from') ?: '');
        if ($df !== '') {
            $filters['date_from'] = $df;
        }
        $dt = (string) ($req->get_param('date_to') ?: '');
        if ($dt !== '') {
            $filters['date_to'] = $dt;
        }
        $this->apply_search_filter($req, $filters);

        $filename = $quiz_id > 0
            ? sprintf('leads-%s-%d.csv', gmdate('Y-m-d'), $quiz_id)
            : sprintf('leads-%s-all.csv', gmdate('Y-m-d'));

        return $this->stream_leads_csv($filters, $filename);
    }

    /**
     * @param array<string,mixed> $filters
     */
    private function apply_search_filter(\WP_REST_Request $req, array &$filters): void
    {
        $search = (string) ($req->get_param('search') ?: '');
        if ($search !== '') {
            $filters['email'] = $search;
        }
    }

    /**
     * Pages through every lead matching $filters (repo caps list() at 200 per
     * call) and streams the result as a CSV download — or, under
     * QUIZABLY_TESTING, returns it as a JSON-wrapped response body so tests can
     * assert on the content without a real HTTP response stream.
     *
     * @param array<string,mixed> $filters
     */
    private function stream_leads_csv(array $filters, string $filename)
    {
        $repos = $this->repos();

        $export_filters            = $filters;
        $export_filters['orderby'] = 'id';
        $export_filters['order']   = 'ASC';

        $all    = [];
        $offset = 0;
        $limit  = 200;
        do {
            $page = $repos['leads']->list(array_merge($export_filters, [
                'limit'  => $limit,
                'offset' => $offset,
            ]));
            $all    = array_merge($all, $page);
            $offset += count($page);
        } while (count($page) === $limit);

        // Columns are documented on LeadCsvExporter (stable fixed prefix + per-quiz extras/questions).
        $table   = (new LeadCsvExporter($repos))->build($all);
        $columns = $table['header'];

        if ( ! defined('QUIZABLY_TESTING') ) {
            // Production path: stream CSV directly to output and exit.
            if ( ! headers_sent() ) {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            }
            // php://output is not a filesystem file — WP_Filesystem has no
            // equivalent for streaming to the HTTP response body, and
            // fputcsv() requires a stream resource with no string-based
            // alternative in PHP.
            $fp = fopen('php://output', 'w'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
            fputcsv($fp, $columns);
            foreach ($table['rows'] as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            exit;
        }

        // Testing path: build CSV in-memory and return it as a response.
        // php://temp is not a filesystem file — same reasoning as php://output above.
        $fp = fopen('php://temp', 'w+'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        fputcsv($fp, $columns);
        foreach ($table['rows'] as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

        $resp = $this->ok([
            'csv'      => $csv,
            'filename' => $filename,
            'count'    => count($all),
        ]);
        $resp->header('Content-Type', 'text/csv; charset=utf-8');
        $resp->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $resp;
    }

    public function detail(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        $lead  = $repos['leads']->find($id);
        if ( ! $lead ) {
            return $this->not_found('Lead');
        }

        $lead = $this->decode_json_fields($lead, ['extra_fields', 'integration_sync_status']);

        // Quiz info.
        $quiz_info = null;
        if ( ! empty($lead['quiz_id']) ) {
            $q = $repos['quizzes']->find((int) $lead['quiz_id']);
            if ($q) {
                $quiz_info = [
                    'id'    => $q['id'],
                    'title' => $q['title'],
                    'type'  => $q['type'] ?? 'trivia',
                ];
            }
        }

        // Latest completed submission for this lead.
        $sub_out = null;
        $sub = $repos['submissions']->find_latest_by_lead_id($id);
        if ($sub) {
            $sub = $this->decode_json_fields($sub, ['answers', 'result_breakdown', 'utm']);

            // Result info.
            $result_info = null;
            if ( ! empty($sub['result_id']) ) {
                $res = $repos['results']->find((int) $sub['result_id']);
                if ($res) {
                    $result_info = [
                        'title'       => $res['title'] ?? '',
                        'description' => $res['description'] ?? '',
                    ];
                }
            }

            // Enrich raw answers with question title, type, and chosen answer labels + is_correct.
            $enriched = [];
            foreach ( (is_array($sub['answers']) ? $sub['answers'] : []) as $ans ) {
                $qid      = (int) ($ans['question_id'] ?? 0);
                $question = $repos['questions']->find($qid);
                $opts     = $repos['answers']->find_by_question($qid);

                $opt_map = [];
                $has_correct = false;
                foreach ($opts as $o) {
                    $opt_map[(int) $o['id']] = [
                        'label'      => $o['label'],
                        'is_correct' => (bool) $o['is_correct'],
                    ];
                    if ($o['is_correct']) {
                        $has_correct = true;
                    }
                }

                $chosen = [];
                foreach (array_map('intval', $ans['answer_ids'] ?? []) as $aid) {
                    if ( isset($opt_map[$aid]) ) {
                        $chosen[] = [
                            'id'         => $aid,
                            'label'      => $opt_map[$aid]['label'],
                            'is_correct' => $has_correct ? $opt_map[$aid]['is_correct'] : null,
                        ];
                    }
                }

                // Overall correctness: null when quiz type doesn't use it.
                $correct_flag = null;
                if ($has_correct && count($chosen) > 0) {
                    $correct_flag = count(array_filter($chosen, fn($c) => $c['is_correct'])) === count($chosen);
                }

                $enriched[] = [
                    'question_id'    => $qid,
                    'question_title' => $question ? $question['title'] : "Question #$qid",
                    'question_type'  => $question ? ($question['type'] ?? 'single') : 'single',
                    'text_value'     => $ans['text_value'] ?? null,
                    'time_spent_ms'  => $ans['time_spent_ms'] ?? null,
                    'chosen_answers' => $chosen,
                    'is_correct'     => $correct_flag,
                ];
            }

            $sub_out = [
                'id'               => $sub['id'],
                'score'            => $sub['score'],
                'result_id'        => $sub['result_id'],
                'result'           => $result_info,
                'result_breakdown' => $sub['result_breakdown'],
                'utm'              => $sub['utm'],
                'completed_at'     => $sub['completed_at'],
                'answers'          => $enriched,
            ];
        }

        return $this->ok([
            'lead'       => $lead,
            'quiz'       => $quiz_info,
            'submission' => $sub_out,
        ]);
    }

    public function delete(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        if ( ! $repos['leads']->find($id) ) {
            return $this->not_found('Lead');
        }
        $repos['leads']->delete($id);
        return $this->ok(['deleted' => true]);
    }

    /**
     * Decode JSON columns and surface utm_data from the submission JOIN row.
     *
     * @param array<int,array> $rows
     * @return array<int,array>
     */
    private function hydrate_leads(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $item    = $this->decode_json_fields($row, ['extra_fields', 'integration_sync_status']);
            $raw_utm = $item['submission_utm'] ?? null;
            unset($item['submission_utm']);
            if ($raw_utm) {
                $decoded_utm = json_decode((string) $raw_utm, true);
                if (is_array($decoded_utm) && ! empty($decoded_utm)) {
                    $item['utm_data'] = $decoded_utm;
                }
            }
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Build the filter array shared by list() and export().
     *
     * Populates quiz_id, date_from, date_to, result_id, score_min, score_max.
     * Pagination (limit/offset), search, and ordering are caller-specific.
     *
     * @return array<string,mixed>
     */
    private function build_common_lead_filters(\WP_REST_Request $req, int $quiz_id): array
    {
        $filters = ['quiz_id' => $quiz_id];

        $df = (string) ($req->get_param('date_from') ?: '');
        if ($df !== '') {
            $filters['date_from'] = $df;
        }
        $dt = (string) ($req->get_param('date_to') ?: '');
        if ($dt !== '') {
            $filters['date_to'] = $dt;
        }
        $rid = $req->get_param('result_id');
        if ($rid !== null && $rid !== '') {
            $filters['result_id'] = (int) $rid;
        }
        $smin = $req->get_param('score_min');
        if ($smin !== null && $smin !== '') {
            $filters['score_min'] = (int) $smin;
        }
        $smax = $req->get_param('score_max');
        if ($smax !== null && $smax !== '') {
            $filters['score_max'] = (int) $smax;
        }

        return $filters;
    }
}
