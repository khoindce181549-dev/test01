<?php
namespace Quizably\Database\Repository;

use Quizably\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class LeadRepository
{
    /**
     * SQL condition for a lead that counts: it never needed confirmation (NULL) or it confirmed (1).
     * Leads still waiting on a double opt-in click (0) are stored and listed, but not counted.
     */
    public const COUNTED_SQL = '(double_optin_verified IS NULL OR double_optin_verified = 1)';

    private \wpdb $db;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = Schema::table_name('leads', $wpdb);
    }

    /**
     * Upsert on (quiz_id, email). Returns the lead id.
     *
     * @param array{quiz_id:int,email:string,name?:string|null,phone?:string|null,extra_fields?:array|null,consent_gdpr?:int|bool,double_optin_verified?:int|bool|null,integration_sync_status?:array|null} $data
     */
    public function insert_or_update(array $data): int
    {
        $quiz_id = (int) $data['quiz_id'];
        $email   = (string) $data['email'];

        $existing = $this->find_by_email($quiz_id, $email);

        $row = [];
        $formats = [];
        if (array_key_exists('name', $data)) {
            $row['name'] = $data['name'] === null ? null : (string) $data['name'];
            $formats[] = '%s';
        }
        if (array_key_exists('phone', $data)) {
            $row['phone'] = $data['phone'] === null ? null : (string) $data['phone'];
            $formats[] = '%s';
        }
        if (array_key_exists('extra_fields', $data)) {
            $row['extra_fields'] = $data['extra_fields'] === null ? null : wp_json_encode($data['extra_fields']);
            $formats[] = '%s';
        }
        if (array_key_exists('consent_gdpr', $data)) {
            $row['consent_gdpr'] = ! empty($data['consent_gdpr']) ? 1 : 0;
            $formats[] = '%d';
        }
        if (array_key_exists('double_optin_verified', $data)) {
            $row['double_optin_verified'] = $data['double_optin_verified'] === null
                ? null
                : ( ! empty($data['double_optin_verified']) ? 1 : 0);
            $formats[] = '%d';
        }
        if (array_key_exists('integration_sync_status', $data)) {
            $row['integration_sync_status'] = $data['integration_sync_status'] === null
                ? null
                : wp_json_encode($data['integration_sync_status']);
            $formats[] = '%s';
        }

        if ($existing !== null) {
            if ($row) {
                $ok = $this->db->update($this->table, $row, ['id' => (int) $existing['id']], $formats, ['%d']);
                if (false === $ok) {
                    \Quizably\Database\DbError::throw_for('LeadRepository::insert_or_update', $this->db);
                }
            }
            return (int) $existing['id'];
        }

        // Insert path — require full column set so defaults are explicit.
        $insert = array_merge(
            [
                'quiz_id'                 => $quiz_id,
                'email'                   => $email,
                'name'                    => null,
                'phone'                   => null,
                'extra_fields'            => null,
                'consent_gdpr'            => 0,
                'double_optin_verified'   => null,
                'integration_sync_status' => null,
                'created_at'              => current_time('mysql', true),
            ],
            $row
        );
        $insert_formats = ['%d','%s','%s','%s','%s','%d','%d','%s','%s'];

        $ok = $this->db->insert($this->table, $insert, $insert_formats);
        if (false === $ok) {
            \Quizably\Database\DbError::throw_for('LeadRepository::insert_or_update', $this->db);
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
    public function find_by_email(int $quiz_id, string $email): ?array
    {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE quiz_id = %d AND email = %s",
                $quiz_id,
                $email
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * @param array{quiz_id?:int,email?:string,date_from?:string,date_to?:string,result_id?:int,score_min?:int,score_max?:int,limit?:int,offset?:int,orderby?:string,order?:string} $filters
     * @return array<int,array>
     */
    public function list(array $filters = []): array
    {
        $sql_parts = $this->build_where($filters);
        $where     = $sql_parts['where'];
        $values    = $sql_parts['values'];
        $join      = $sql_parts['join'];

        $orderby = in_array($filters['orderby'] ?? '', ['created_at','email','id'], true)
            ? 'l.' . $filters['orderby']
            : 'l.created_at';
        $order = strtoupper((string) ($filters['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $limit  = max(1, min(200, (int) ($filters['limit'] ?? 20)));
        $offset = max(0, (int) ($filters['offset'] ?? 0));

        global $wpdb;
        $sub_table = Schema::table_name('submissions', $wpdb);
        // LEFT JOIN to the most recent completed submission for this lead so we
        // can surface UTM data captured at quiz-start time in the admin leads list.
        $utm_join = " LEFT JOIN {$sub_table} sub_utm"
            . " ON sub_utm.lead_id = l.id"
            . " AND sub_utm.id = ("
            . "SELECT MAX(s2.id) FROM {$sub_table} s2"
            . " WHERE s2.lead_id = l.id"
            . ")";

        $sql = "SELECT l.*, sub_utm.utm AS submission_utm FROM {$this->table} l{$join}{$utm_join} WHERE " . implode(' AND ', $where)
            . " ORDER BY {$orderby} {$order} LIMIT {$limit} OFFSET {$offset}";
        if ($values) {
            $sql = $this->db->prepare($sql, ...$values);
        }
        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function count(array $filters = []): int
    {
        $sql_parts = $this->build_where($filters);
        $where     = $sql_parts['where'];
        $values    = $sql_parts['values'];
        $join      = $sql_parts['join'];

        $sql = "SELECT COUNT(DISTINCT l.id) FROM {$this->table} l{$join} WHERE " . implode(' AND ', $where);
        if ($values) {
            $sql = $this->db->prepare($sql, ...$values);
        }
        return (int) $this->db->get_var($sql);
    }

    /**
     * Read-modify-write the `integration_sync_status` JSON column to merge
     * `{integration: status}` into the existing map.
     */
    public function update_sync_status(int $id, string $integration, string $status): bool
    {
        $lead = $this->find($id);
        if ($lead === null) {
            return false;
        }
        $current = [];
        if ( ! empty($lead['integration_sync_status']) ) {
            $decoded = json_decode((string) $lead['integration_sync_status'], true);
            if (is_array($decoded)) {
                $current = $decoded;
            }
        }
        $current[$integration] = $status;

        $ok = $this->db->update(
            $this->table,
            ['integration_sync_status' => wp_json_encode($current)],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
        return false !== $ok;
    }

    public function set_double_optin_status(int $lead_id, int $status): void
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'quizably_leads',
            ['double_optin_verified' => $status],
            ['id' => $lead_id],
            ['%d'],
            ['%d']
        );
    }

    public function delete(int $id): bool
    {
        return false !== $this->db->delete($this->table, ['id' => $id], ['%d']);
    }

    public function delete_by_quiz(int $quiz_id): bool
    {
        return false !== $this->db->delete($this->table, ['quiz_id' => $quiz_id], ['%d']);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{where:array<int,string>,values:array<int,mixed>,join:string}
     */
    private function build_where(array $filters): array
    {
        global $wpdb;
        $where  = ['1=1'];
        $values = [];
        $join   = '';

        if ( ! empty($filters['quiz_id']) ) {
            $where[]  = 'l.quiz_id = %d';
            $values[] = (int) $filters['quiz_id'];
        }
        if ( ! empty($filters['email']) ) {
            $where[]  = 'l.email LIKE %s';
            $values[] = '%' . $this->db->esc_like((string) $filters['email']) . '%';
        }
        if ( ! empty($filters['date_from']) ) {
            $where[]  = 'l.created_at >= %s';
            $values[] = (string) $filters['date_from'];
        }
        if ( ! empty($filters['date_to']) ) {
            $where[]  = 'l.created_at <= %s';
            $values[] = (string) $filters['date_to'];
        }

        // Advanced (Pro) filters — require a JOIN on submissions when active.
        $needs_sub = ! empty($filters['result_id'])
            || ( isset($filters['score_min']) && $filters['score_min'] !== '' && $filters['score_min'] !== null )
            || ( isset($filters['score_max']) && $filters['score_max'] !== '' && $filters['score_max'] !== null );

        if ($needs_sub) {
            $sub_table = Schema::table_name('submissions', $wpdb);
            $join      = " INNER JOIN {$sub_table} s ON s.lead_id = l.id AND s.status = 'completed'";

            if ( ! empty($filters['result_id']) ) {
                $where[]  = 's.result_id = %d';
                $values[] = (int) $filters['result_id'];
            }
            if ( isset($filters['score_min']) && $filters['score_min'] !== '' && $filters['score_min'] !== null ) {
                $where[]  = 's.score >= %d';
                $values[] = (int) $filters['score_min'];
            }
            if ( isset($filters['score_max']) && $filters['score_max'] !== '' && $filters['score_max'] !== null ) {
                $where[]  = 's.score <= %d';
                $values[] = (int) $filters['score_max'];
            }
        }

        return ['where' => $where, 'values' => $values, 'join' => $join];
    }
}
