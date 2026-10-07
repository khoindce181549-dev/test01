<?php
namespace Quizably\Database\Repository;

use Quizably\Database\Schema;
use Quizably\Database\Uuid;

defined( 'ABSPATH' ) || exit;

final class SubmissionRepository
{
    private \wpdb $db;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = Schema::table_name('submissions', $wpdb);
    }

    /**
     * Create a new submission in `in_progress` status. Auto-sets uuid + started_at.
     *
     * @param array{quiz_id:int,user_id?:int|null,answers?:array|null,ip_hash?:string|null,user_agent?:string|null,utm?:array|null} $data
     */
    public function insert(array $data): int
    {
        $row = [
            'uuid'       => Uuid::v4(),
            'quiz_id'    => (int) $data['quiz_id'],
            'user_id'    => array_key_exists('user_id', $data) && $data['user_id'] !== null ? (int) $data['user_id'] : null,
            'lead_id'    => null,
            'answers'    => isset($data['answers']) ? wp_json_encode($data['answers']) : null,
            'score'      => null,
            'result_id'  => null,
            'ip_hash'    => isset($data['ip_hash']) ? (string) $data['ip_hash'] : null,
            'user_agent' => isset($data['user_agent']) ? (string) $data['user_agent'] : null,
            'utm'        => isset($data['utm']) ? wp_json_encode($data['utm']) : null,
            'started_at' => current_time('mysql', true),
            'status'     => 'in_progress',
        ];
        $formats = ['%s','%d','%d','%d','%s','%d','%d','%s','%s','%s','%s','%s'];

        $ok = $this->db->insert($this->table, $row, $formats);
        if (false === $ok) {
            \Quizably\Database\DbError::throw_for('SubmissionRepository::insert', $this->db);
        }
        return (int) $this->db->insert_id;
    }

    /** @return array|null */
    public function find(int $id): ?array
    {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        return $row ?: null;
    }

    /** @return array|null */
    public function find_by_uuid(string $uuid): ?array
    {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE uuid = %s", $uuid),
            ARRAY_A
        );
        return $row ?: null;
    }

    /** @param array<string,mixed> $answers */
    public function update_answers(string $uuid, array $answers): bool
    {
        $ok = $this->db->update(
            $this->table,
            ['answers' => wp_json_encode($answers)],
            ['uuid' => $uuid],
            ['%s'],
            ['%s']
        );
        return false !== $ok;
    }

    /**
     * Mark a submission as completed and store the scoring result.
     *
     * @param array{score?:int|null,result_id?:int|null,result_breakdown?:array|null} $data
     */
    public function complete(string $uuid, array $data): bool
    {
        $row = [
            'status'       => 'completed',
            'completed_at' => current_time('mysql', true),
        ];
        $formats = ['%s', '%s'];

        if (array_key_exists('score', $data)) {
            $row['score'] = $data['score'] === null ? null : (int) $data['score'];
            $formats[] = '%d';
        }
        if (array_key_exists('result_id', $data)) {
            $row['result_id'] = $data['result_id'] === null ? null : (int) $data['result_id'];
            $formats[] = '%d';
        }
        if (array_key_exists('result_breakdown', $data)) {
            $row['result_breakdown'] = $data['result_breakdown'] === null
                ? null
                : wp_json_encode($data['result_breakdown']);
            $formats[] = '%s';
        }

        $ok = $this->db->update($this->table, $row, ['uuid' => $uuid], $formats, ['%s']);
        return false !== $ok;
    }

    public function attach_lead(string $uuid, int $lead_id): bool
    {
        $ok = $this->db->update(
            $this->table,
            ['lead_id' => $lead_id],
            ['uuid' => $uuid],
            ['%d'],
            ['%s']
        );
        return false !== $ok;
    }

    /**
     * @param array{status?:string,date_from?:string,date_to?:string,limit?:int,offset?:int,orderby?:string,order?:string} $filters
     * @return array<int,array>
     */
    public function list_by_quiz(int $quiz_id, array $filters = []): array
    {
        $where  = ['quiz_id = %d'];
        $values = [$quiz_id];

        if ( ! empty($filters['status']) ) {
            $where[]  = 'status = %s';
            $values[] = (string) $filters['status'];
        }
        if ( ! empty($filters['date_from']) ) {
            $where[]  = 'started_at >= %s';
            $values[] = (string) $filters['date_from'];
        }
        if ( ! empty($filters['date_to']) ) {
            $where[]  = 'started_at <= %s';
            $values[] = (string) $filters['date_to'];
        }

        $orderby = in_array($filters['orderby'] ?? '', ['started_at','completed_at','score','id'], true)
            ? $filters['orderby']
            : 'started_at';
        $order = strtoupper((string) ($filters['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $limit  = max(1, min(200, (int) ($filters['limit'] ?? 20)));
        $offset = max(0, (int) ($filters['offset'] ?? 0));

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where)
            . " ORDER BY {$orderby} {$order} LIMIT {$limit} OFFSET {$offset}";
        $sql = $this->db->prepare($sql, ...$values);

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    /** Site-wide submission count, optionally limited to one status. */
    public function count(?string $status = null): int
    {
        if ($status === null) {
            return (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->table}");
        }
        return (int) $this->db->get_var(
            $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = %s", $status)
        );
    }

    public function count_by_quiz(int $quiz_id, ?string $status = null): int
    {
        if ($status === null) {
            $sql = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE quiz_id = %d", $quiz_id);
        } else {
            $sql = $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE quiz_id = %d AND status = %s",
                $quiz_id,
                $status
            );
        }
        return (int) $this->db->get_var($sql);
    }

    /**
     * Return submission totals and completed counts for a set of quiz IDs in one
     * GROUP BY query instead of 2 × N individual count calls.
     *
     * @param  int[] $quiz_ids
     * @return array<int,array{total:int,completed:int}>  keyed by quiz_id
     */
    public function count_by_quiz_batch(array $quiz_ids): array
    {
        if (empty($quiz_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($quiz_ids), '%d'));
        $sql          = $this->db->prepare(
            "SELECT quiz_id, status, COUNT(*) AS cnt FROM {$this->table}
             WHERE quiz_id IN ($placeholders) GROUP BY quiz_id, status",
            ...$quiz_ids
        );

        $rows   = $this->db->get_results($sql, ARRAY_A) ?: [];
        $result = [];

        foreach ($rows as $row) {
            $qid = (int) $row['quiz_id'];
            if ( ! isset($result[$qid]) ) {
                $result[$qid] = ['total' => 0, 'completed' => 0];
            }
            $result[$qid]['total'] += (int) $row['cnt'];
            if ('completed' === $row['status']) {
                $result[$qid]['completed'] += (int) $row['cnt'];
            }
        }

        return $result;
    }

    /**
     * Return the most recent completed submission for a lead, or null if none exists.
     */
    public function find_latest_by_lead_id(int $lead_id): ?array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE lead_id = %d AND status = 'completed'
             ORDER BY completed_at DESC LIMIT 1",
            $lead_id
        );
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    /**
     * Return the most recent submission for a lead in any status, or null if none exists.
     * Used when a lead confirms their email: the visitor may confirm before or after finishing.
     */
    public function find_latest_any_by_lead_id(int $lead_id): ?array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE lead_id = %d ORDER BY id DESC LIMIT 1",
            $lead_id
        );
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    public function delete(int $id): bool
    {
        return false !== $this->db->delete($this->table, ['id' => $id], ['%d']);
    }

    public function delete_by_quiz(int $quiz_id): bool
    {
        return false !== $this->db->delete($this->table, ['quiz_id' => $quiz_id], ['%d']);
    }
}
