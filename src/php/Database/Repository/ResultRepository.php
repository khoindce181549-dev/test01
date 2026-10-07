<?php
namespace Quizably\Database\Repository;

use Quizably\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class ResultRepository
{
    private \wpdb $db;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = Schema::table_name('results', $wpdb);
    }

    /**
     * @param array{quiz_id:int,title:string,content?:string|null,image_url?:string|null,cta_label?:string|null,cta_url?:string|null,redirect_url?:string|null,score_min?:int|null,score_max?:int|null,conditions?:array|null,settings?:array|null,position?:int} $data
     */
    public function insert(array $data): int
    {
        $row = [
            'quiz_id'      => (int) $data['quiz_id'],
            'title'        => (string) $data['title'],
            'content'      => isset($data['content']) ? (string) $data['content'] : null,
            'image_url'    => isset($data['image_url']) ? (string) $data['image_url'] : null,
            'cta_label'    => isset($data['cta_label']) ? (string) $data['cta_label'] : null,
            'cta_url'      => isset($data['cta_url']) ? (string) $data['cta_url'] : null,
            'redirect_url' => isset($data['redirect_url']) ? (string) $data['redirect_url'] : null,
            'score_min'    => array_key_exists('score_min', $data) && $data['score_min'] !== null ? (int) $data['score_min'] : null,
            'score_max'    => array_key_exists('score_max', $data) && $data['score_max'] !== null ? (int) $data['score_max'] : null,
            'conditions'   => isset($data['conditions']) ? wp_json_encode($data['conditions']) : null,
            'settings'     => isset($data['settings']) ? wp_json_encode($data['settings']) : null,
            'position'     => (int) ($data['position'] ?? 0),
        ];
        $formats = ['%d','%s','%s','%s','%s','%s','%s','%d','%d','%s','%s','%d'];

        $ok = $this->db->insert($this->table, $row, $formats);
        if (false === $ok) {
            \Quizably\Database\DbError::throw_for('ResultRepository::insert', $this->db);
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

    /** @return array<int,array> */
    public function find_by_quiz(int $quiz_id): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE quiz_id = %d ORDER BY position ASC, id ASC",
                $quiz_id
            ),
            ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Return the first result row whose score bounds accept the given score.
     * A NULL bound is treated as "unbounded" on that side.
     */
    public function find_by_score(int $quiz_id, int $score): ?array
    {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table}
                 WHERE quiz_id = %d
                   AND (score_min IS NULL OR score_min <= %d)
                   AND (score_max IS NULL OR score_max >= %d)
                 ORDER BY position ASC, id ASC
                 LIMIT 1",
                $quiz_id,
                $score,
                $score
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): bool
    {
        $allowed = ['quiz_id','title','content','image_url','cta_label','cta_url','redirect_url','score_min','score_max','conditions','settings','position'];
        $row = [];
        $formats = [];
        foreach ($allowed as $col) {
            if ( ! array_key_exists($col, $data) ) {
                continue;
            }
            $value = $data[$col];
            if (in_array($col, ['conditions','settings'], true) && is_array($value)) {
                $row[$col] = wp_json_encode($value);
                $formats[] = '%s';
            } elseif (in_array($col, ['quiz_id','position'], true)) {
                $row[$col] = (int) $value;
                $formats[] = '%d';
            } elseif (in_array($col, ['score_min','score_max'], true)) {
                $row[$col] = $value === null ? null : (int) $value;
                $formats[] = '%d';
            } else {
                $row[$col] = $value;
                $formats[] = '%s';
            }
        }
        if ( ! $row ) {
            return true;
        }
        $ok = $this->db->update($this->table, $row, ['id' => $id], $formats, ['%d']);
        return false !== $ok;
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
