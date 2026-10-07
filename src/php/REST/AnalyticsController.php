<?php
namespace Quizably\REST;

use Quizably\Database\Repository\LeadRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics REST endpoints.
 *
 * Three routes:
 *  - GET /analytics                        — site-wide aggregate KPIs for the dashboard
 *  - GET /quizzes/{id}/analytics           — per-quiz breakdown for the builder
 *  - GET /analytics/questions/{quiz_id}    — question-level drop-off + answer distribution (Pro)
 *
 * All aggregates are computed on-read via $wpdb->prepare()-guarded raw SQL
 * against the quizably_quizzes / quizably_submissions / quizably_leads tables. Keeping the
 * math in SQL (and out of PHP) means these endpoints stay cheap enough for
 * an always-on dashboard widget without adding a materialized-stats table.
 *
 * Every query below interpolates a table-name variable (e.g. $q, $s_table)
 * built from `$wpdb->prefix . 'quizably_...'` — a plugin-owned constant, never
 * user input — because $wpdb->prepare() has no placeholder for identifiers
 * on WP < 6.2 (the plugin's declared minimum is 6.0, so %i isn't available).
 * All VALUES in these queries do go through $wpdb->prepare()'s %d/%s.
 */
// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter -- table names only, see class docblock above
final class AnalyticsController extends BaseController
{
    public function register_routes(): void
    {
        $ns = RestBootstrap::NAMESPACE;

        register_rest_route($ns, '/analytics', [
            'methods'             => \WP_REST_Server::READABLE,
            'permission_callback' => [$this, 'permission_check'],
            'callback'            => [$this, 'site_overview'],
        ]);

        register_rest_route($ns, '/quizzes/(?P<id>\d+)/analytics', [
            'methods'             => \WP_REST_Server::READABLE,
            'permission_callback' => [$this, 'permission_check'],
            'callback'            => [$this, 'quiz_analytics'],
        ]);

        register_rest_route($ns, '/analytics/questions/(?P<quiz_id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'permission_callback' => [$this, 'permission_check'],
            'callback'            => [$this, 'question_analytics'],
        ]);

        register_rest_route($ns, '/analytics/(?P<quiz_id>\d+)/export', [
            'methods'             => \WP_REST_Server::READABLE,
            'permission_callback' => [$this, 'permission_check'],
            'callback'            => [$this, 'export_csv'],
        ]);
    }

    public function site_overview(\WP_REST_Request $req)
    {
        global $wpdb;
        $q = $wpdb->prefix . 'quizably_quizzes';
        $s = $wpdb->prefix . 'quizably_submissions';
        $l = $wpdb->prefix . 'quizably_leads';

        // Table names are plugin-owned constants, not user input — safe to
        // interpolate. No user-supplied values are concatenated below.
        // `total_quizzes` counts every row so it always matches the quizzes
        // list total (that list shows all statuses, including archived, by
        // default) — a status-filtered subtotal here previously drifted
        // from the list's own pagination count and read as a bug.
        $total_quizzes     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$q}");
        $published_quizzes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$q} WHERE status = 'published'");
        $draft_quizzes     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$q} WHERE status = 'draft'");
        $archived_quizzes  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$q} WHERE status = 'archived'");

        $submissions_30d = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$s} "
            . "WHERE status = 'completed' AND completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        $leads_30d = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$l} WHERE " . LeadRepository::COUNTED_SQL . " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        $completed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$s} WHERE status = 'completed'");
        $started   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$s}");
        $avg_completion_rate = $started > 0 ? round($completed / $started, 3) : 0.0;

        // Submissions per day for the last 30 days. Bucketed by DATE() so
        // the dashboard can draw a 30-point sparkline without per-row work
        // in JS. Days with zero submissions are filled in below so the
        // chart x-axis is contiguous.
        $rows = $wpdb->get_results(
            "SELECT DATE(completed_at) AS d, COUNT(*) AS n FROM {$s} "
            . "WHERE status = 'completed' AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) "
            . "GROUP BY DATE(completed_at)",
            ARRAY_A
        ) ?: [];
        $by_day = [];
        foreach ($rows as $r) {
            $by_day[$r['d']] = (int) $r['n'];
        }
        $submissions_by_day = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = gmdate('Y-m-d', strtotime("-{$i} days"));
            $submissions_by_day[] = [
                'date'  => $date,
                'count' => $by_day[$date] ?? 0,
            ];
        }

        // Top 5 quizzes by completed submissions in the last 30 days.
        $top = $wpdb->get_results(
            "SELECT q.id, q.title, q.type, COUNT(s.id) AS n FROM {$q} q "
            . "INNER JOIN {$s} s ON s.quiz_id = q.id "
            . "WHERE s.status = 'completed' AND s.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) "
            . "GROUP BY q.id "
            . "ORDER BY n DESC "
            . "LIMIT 5",
            ARRAY_A
        ) ?: [];
        $top_quizzes = array_map(
            static function (array $row): array {
                return [
                    'id'    => (int) $row['id'],
                    'title' => (string) $row['title'],
                    'type'  => (string) $row['type'],
                    'count' => (int) $row['n'],
                ];
            },
            $top
        );

        return $this->ok([
            'total_quizzes'         => $total_quizzes,
            'published_quizzes'     => $published_quizzes,
            'draft_quizzes'         => $draft_quizzes,
            'archived_quizzes'      => $archived_quizzes,
            'total_submissions_30d' => $submissions_30d,
            'total_leads_30d'       => $leads_30d,
            'avg_completion_rate'   => $avg_completion_rate,
            'submissions_by_day'    => $submissions_by_day,
            'top_quizzes'           => $top_quizzes,
        ]);
    }

    public function quiz_analytics(\WP_REST_Request $req)
    {
        $id    = (int) $req['id'];
        $repos = $this->repos();
        $quiz  = $repos['quizzes']->find($id);
        if ( ! $quiz ) {
            return $this->not_found('Quiz');
        }

        global $wpdb;
        $q_table = $wpdb->prefix . 'quizably_quizzes';
        $s       = $wpdb->prefix . 'quizably_submissions';
        $l       = $wpdb->prefix . 'quizably_leads';

        $view_count = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT view_count FROM {$q_table} WHERE id = %d", $id)
        );

        $total = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$s} WHERE quiz_id = %d", $id)
        );
        $completed = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$s} WHERE quiz_id = %d AND status = %s",
                $id,
                'completed'
            )
        );
        // Submissions that are started but never completed — we don't track
        // an "abandoned" status explicitly, so total - completed is the best
        // proxy available without schema changes.
        $abandoned = max(0, $total - $completed);

        $leads = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$l} WHERE quiz_id = %d AND " . LeadRepository::COUNTED_SQL, $id)
        );

        // Result distribution (personality / weighted results) — only
        // completed submissions with a result_id set.
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT result_id, COUNT(*) AS n FROM {$s} "
                . "WHERE quiz_id = %d AND status = %s AND result_id IS NOT NULL "
                . "GROUP BY result_id",
                $id,
                'completed'
            ),
            ARRAY_A
        );
        $result_distribution = array_map(
            static function (array $r) {
                return [
                    'result_id' => (int) $r['result_id'],
                    'count'     => (int) $r['n'],
                ];
            },
            $rows ?: []
        );

        // Avg score for trivia-style quizzes. Null when no scores recorded.
        $avg_score_raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(score) FROM {$s} "
                . "WHERE quiz_id = %d AND status = %s AND score IS NOT NULL",
                $id,
                'completed'
            )
        );
        $avg_score = $avg_score_raw !== null ? round((float) $avg_score_raw, 2) : null;

        return $this->ok([
            'quiz_id'              => $id,
            'view_count'           => $view_count,
            'total_submissions'    => $total,
            'completed'            => $completed,
            'abandoned'            => $abandoned,
            'completion_rate'      => $total > 0 ? round($completed / $total, 3) : 0.0,
            'start_rate'           => $view_count > 0 ? round($total / $view_count, 3) : 0.0,
            'leads_captured'       => $leads,
            'avg_score'            => $avg_score,
            'result_distribution'  => $result_distribution,
            'lead_conversion_rate' => $completed > 0 ? round($leads / $completed, 3) : 0.0,
        ]);
    }

    /**
     * Question-level drop-off funnel + answer distribution (Pro only).
     *
     * Route: GET /analytics/questions/{quiz_id}
     *
     * Answers are stored as a JSON array in quizably_submissions.answers
     * (one entry per question answered). We pull all completed submissions,
     * decode the JSON in PHP, then aggregate per-question and per-answer-id
     * counts in memory.  For most quiz sizes (< 50k submissions) this is
     * fast enough — if the table grows large the next step would be a
     * materialized summary table triggered on quizably_submission_completed.
     *
     * Funnel logic:
     *   reached[0]  = total completed submissions for this quiz
     *   reached[i]  = answered count of question (i-1)
     *   answered[i] = number of completed submissions that recorded an entry
     *                 for this question's id in their answers JSON
     */
    public function question_analytics(\WP_REST_Request $req)
    {
        $quiz_id = (int) $req['quiz_id'];
        $repos   = $this->repos();
        $quiz    = $repos['quizzes']->find($quiz_id);
        if ( ! $quiz ) {
            return $this->not_found('Quiz');
        }

        global $wpdb;
        $q_table = $wpdb->prefix . 'quizably_questions';
        $a_table = $wpdb->prefix . 'quizably_answers';
        $s_table = $wpdb->prefix . 'quizably_submissions';

        $question_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, title FROM {$q_table} WHERE quiz_id = %d ORDER BY position ASC, id ASC",
                $quiz_id
            ),
            ARRAY_A
        ) ?: [];

        if ( empty($question_rows) ) {
            return $this->ok(['items' => []]);
        }

        $answer_label_map = $this->fetch_answer_label_map($quiz_id, $q_table, $a_table);
        $submissions      = $this->fetch_completed_answers($quiz_id, $s_table);
        $items            = $this->build_question_items($question_rows, $answer_label_map, $submissions);

        return $this->ok(['items' => $items]);
    }

    /**
     * CSV export of per-quiz analytics.
     *
     * Route: GET /analytics/{quiz_id}/export
     *
     * Summary row columns:
     *   date, total_views, total_completions, completion_rate_pct, total_leads, avg_score
     *
     * When ?include_questions=1, question rows are appended after the summary:
     *   question_id, question_title, reached, answered, drop_off_pct, avg_time_ms,
     *   [answer_option_1_count, answer_option_2_count, ...]
     */
    public function export_csv(\WP_REST_Request $req)
    {
        $quiz_id = (int) $req['quiz_id'];
        $repos   = $this->repos();
        $quiz    = $repos['quizzes']->find($quiz_id);
        if ( ! $quiz ) {
            return $this->not_found('Quiz');
        }

        global $wpdb;
        $q_table = $wpdb->prefix . 'quizably_quizzes';
        $s_table = $wpdb->prefix . 'quizably_submissions';
        $l_table = $wpdb->prefix . 'quizably_leads';

        $view_count = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT view_count FROM {$q_table} WHERE id = %d", $quiz_id)
        );
        $total_completions = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$s_table} WHERE quiz_id = %d AND status = %s", $quiz_id, 'completed')
        );
        $total_started = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$s_table} WHERE quiz_id = %d", $quiz_id)
        );
        $total_leads = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$l_table} WHERE quiz_id = %d AND " . LeadRepository::COUNTED_SQL, $quiz_id)
        );
        $avg_score_raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(score) FROM {$s_table} WHERE quiz_id = %d AND status = %s AND score IS NOT NULL",
                $quiz_id,
                'completed'
            )
        );
        $avg_score = $avg_score_raw !== null ? round((float) $avg_score_raw, 2) : '';
        $completion_rate_pct = $total_started > 0
            ? round($total_completions / $total_started * 100, 2)
            : 0.0;

        $include_questions = (string) ($req->get_param('include_questions') ?: '') === '1';

        // Build question rows if requested.
        $question_rows_csv = [];
        if ($include_questions) {
            $q_def_table      = $wpdb->prefix . 'quizably_questions';
            $a_def_table      = $wpdb->prefix . 'quizably_answers';
            $question_defs    = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, title FROM {$q_def_table} WHERE quiz_id = %d ORDER BY position ASC, id ASC",
                    $quiz_id
                ),
                ARRAY_A
            ) ?: [];

            $answer_label_map = $this->fetch_answer_label_map($quiz_id, $q_def_table, $a_def_table);
            $submissions      = $this->fetch_completed_answers($quiz_id, $s_table);
            $items            = $this->build_question_items($question_defs, $answer_label_map, $submissions);

            foreach ($items as $item) {
                $ans_counts = array_column($item['answers'], 'count');
                $question_rows_csv[] = array_merge(
                    [
                        $item['question_id'],
                        $item['question_title'],
                        $item['reached'],
                        $item['answered'],
                        $item['drop_off_rate'],
                        $item['avg_time_ms'] ?? '',
                    ],
                    $ans_counts
                );
            }
        }

        $filename = sprintf('analytics-%d-%s.csv', $quiz_id, gmdate('Y-m-d'));

        // php://temp is not a filesystem file — WP_Filesystem has no equivalent
        // for an in-memory stream, and fputcsv() requires a stream resource with
        // no string-based alternative in PHP.
        $fp = fopen('php://temp', 'w+'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        fputcsv($fp, ['date', 'total_views', 'total_completions', 'completion_rate_pct', 'total_leads', 'avg_score']);
        fputcsv($fp, [gmdate('Y-m-d'), $view_count, $total_completions, $completion_rate_pct, $total_leads, $avg_score]);
        if ($include_questions && ! empty($question_rows_csv)) {
            fputcsv($fp, []);

            // Base columns are fixed; extra columns are per-answer counts.
            // Find the widest row so the header covers every data column.
            $base_cols = 6; // question_id, question_title, reached, answered, drop_off_pct, avg_time_ms
            $max_ans   = 0;
            foreach ($question_rows_csv as $qrow) {
                $extra = count($qrow) - $base_cols;
                if ($extra > $max_ans) {
                    $max_ans = $extra;
                }
            }
            $q_header = ['question_id', 'question_title', 'reached', 'answered', 'drop_off_pct', 'avg_time_ms'];
            for ($i = 1; $i <= $max_ans; $i++) {
                $q_header[] = 'answer_' . $i . '_count';
            }
            fputcsv($fp, $q_header);

            foreach ($question_rows_csv as $qrow) {
                fputcsv($fp, array_map('strval', $qrow));
            }
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

        return $this->ok(['csv' => $csv, 'filename' => $filename]);
    }

    /**
     * Build answer_id → label map for every answer belonging to $quiz_id.
     * Returns: [ question_id => [ answer_id => label, ... ], ... ]
     *
     * @param string $q_table  quizably_questions table name
     * @param string $a_table  quizably_answers table name
     * @return array<int,array<int,string>>
     */
    private function fetch_answer_label_map(int $quiz_id, string $q_table, string $a_table): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.id, a.question_id, a.label
                 FROM {$a_table} a
                 INNER JOIN {$q_table} q ON q.id = a.question_id
                 WHERE q.quiz_id = %d
                 ORDER BY a.question_id ASC, a.position ASC, a.id ASC",
                $quiz_id
            ),
            ARRAY_A
        ) ?: [];

        $map = [];
        foreach ($rows as $ar) {
            $map[(int) $ar['question_id']][(int) $ar['id']] = (string) $ar['label'];
        }
        return $map;
    }

    /**
     * Fetch completed submissions' answers JSON for $quiz_id.
     * Returns raw rows: [ ['answers' => json_string], ... ]
     *
     * @param string $s_table  quizably_submissions table name
     * @return array<int,array<string,string>>
     */
    private function fetch_completed_answers(int $quiz_id, string $s_table): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT answers FROM {$s_table}
                 WHERE quiz_id = %d AND status = %s AND answers IS NOT NULL AND answers != ''",
                $quiz_id,
                'completed'
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Compute the per-question funnel items from raw question rows, an answer
     * label map, and completed submissions.
     *
     * Returns an array of items matching the question_analytics REST shape:
     *   question_id, question_title, reached, answered, drop_off_rate, avg_time_ms, answers[]
     *
     * @param array<int,array<string,mixed>> $question_rows    Ordered questions: [['id'=>int,'title'=>string],...]
     * @param array<int,array<int,string>>   $answer_label_map As returned by fetch_answer_label_map()
     * @param array<int,array<string,string>> $submissions      As returned by fetch_completed_answers()
     * @return array<int,array<string,mixed>>
     */
    private function build_question_items(array $question_rows, array $answer_label_map, array $submissions): array
    {
        $total_completed         = count($submissions);
        $question_answered_count = [];
        $question_answer_dist    = [];
        $question_elapsed_sum    = [];
        $question_elapsed_count  = [];

        foreach ($submissions as $sub) {
            $decoded = json_decode((string) $sub['answers'], true);
            if ( ! is_array($decoded) ) {
                continue;
            }
            foreach ($decoded as $entry) {
                $qid = (int) ($entry['question_id'] ?? 0);
                if ( ! $qid ) {
                    continue;
                }
                $question_answered_count[$qid] = ($question_answered_count[$qid] ?? 0) + 1;

                if ( isset($entry['elapsed_ms']) && is_numeric($entry['elapsed_ms']) ) {
                    $question_elapsed_sum[$qid]   = ($question_elapsed_sum[$qid] ?? 0) + (int) $entry['elapsed_ms'];
                    $question_elapsed_count[$qid] = ($question_elapsed_count[$qid] ?? 0) + 1;
                }

                if ( ! empty($entry['answer_ids']) && is_array($entry['answer_ids']) ) {
                    foreach ($entry['answer_ids'] as $aid) {
                        $aid = (int) $aid;
                        if ( $aid ) {
                            $question_answer_dist[$qid][$aid] = ($question_answer_dist[$qid][$aid] ?? 0) + 1;
                        }
                    }
                }
            }
        }

        $items         = [];
        $prev_answered = $total_completed;

        foreach ($question_rows as $qi => $qrow) {
            $qid      = (int) $qrow['id'];
            $reached  = ($qi === 0) ? $total_completed : $prev_answered;
            $answered = $question_answered_count[$qid] ?? 0;

            $drop_off_rate = ($reached > 0)
                ? round(($reached - $answered) / $reached * 100, 2)
                : 0.0;

            $dist         = $question_answer_dist[$qid] ?? [];
            $answer_total = array_sum($dist);
            $answers_out  = [];

            if ( ! empty($answer_label_map[$qid]) ) {
                foreach ($answer_label_map[$qid] as $aid => $label) {
                    $count         = $dist[$aid] ?? 0;
                    $pct           = ($answer_total > 0) ? round($count / $answer_total * 100, 1) : 0.0;
                    $answers_out[] = [
                        'answer_id'   => $aid,
                        'answer_text' => $label,
                        'count'       => $count,
                        'pct'         => $pct,
                    ];
                }
            } elseif ( ! empty($dist) ) {
                foreach ($dist as $aid => $count) {
                    $pct           = ($answer_total > 0) ? round($count / $answer_total * 100, 1) : 0.0;
                    $answers_out[] = [
                        'answer_id'   => (int) $aid,
                        'answer_text' => '',
                        'count'       => $count,
                        'pct'         => $pct,
                    ];
                }
            }

            $elapsed_cnt = $question_elapsed_count[$qid] ?? 0;
            $avg_time_ms = $elapsed_cnt > 0
                ? (int) round($question_elapsed_sum[$qid] / $elapsed_cnt)
                : null;

            $items[] = [
                'question_id'    => $qid,
                'question_title' => (string) $qrow['title'],
                'reached'        => $reached,
                'answered'       => $answered,
                'drop_off_rate'  => $drop_off_rate,
                'avg_time_ms'    => $avg_time_ms,
                'answers'        => $answers_out,
            ];

            $prev_answered = $answered;
        }

        return $items;
    }
}
// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
