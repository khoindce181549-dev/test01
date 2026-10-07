<?php
namespace Quizably\Database\Repository;

use Quizably\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class QuestionRepository
{
    private \wpdb $db;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = Schema::table_name('questions', $wpdb);
    }

    /**
     * @param array{quiz_id:int,type?:string,title:string,description?:string|null,media_url?:string|null,media_type?:string|null,required?:int|bool,position?:int,settings?:array|null,logic?:array|null} $data
     */
    public function insert(array $data): int
    {
        $row = [
            'quiz_id'     => (int) $data['quiz_id'],
            'type'        => (string) ($data['type'] ?? 'single'),
            'title'       => (string) $data['title'],
            'description' => isset($data['description']) ? (string) $data['description'] : null,
            'media_url'   => isset($data['media_url']) ? (string) $data['media_url'] : null,
            'media_type'  => isset($data['media_type']) ? (string) $data['media_type'] : null,
            'required'    => ! empty($data['required']) ? 1 : 0,
            'position'    => (int) ($data['position'] ?? 0),
            'settings'    => isset($data['settings']) ? wp_json_encode($data['settings']) : null,
            'logic'       => isset($data['logic']) ? wp_json_encode($data['logic']) : null,
        ];
        $formats = ['%d','%s','%s','%s','%s','%s','%d','%d','%s','%s'];

        $ok = $this->db->insert($this->table, $row, $formats);
        if (false === $ok) {
            \Quizably\Database\DbError::throw_for('QuestionRepository::insert', $this->db);
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

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): bool
    {
        $allowed = ['quiz_id','type','title','description','media_url','media_type','required','position','settings','logic'];
        $row = [];
        $formats = [];
        foreach ($allowed as $col) {
            if ( ! array_key_exists($col, $data) ) {
                continue;
            }
            $value = $data[$col];
            if (in_array($col, ['settings','logic'], true) && is_array($value)) {
                $row[$col] = wp_json_encode($value);
                $formats[] = '%s';
            } elseif (in_array($col, ['quiz_id','required','position'], true)) {
                $row[$col] = $col === 'required' ? ($value ? 1 : 0) : (int) $value;
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

    /**
     * Reorder questions for a quiz. `$id_order` is an ordered list of question IDs
     * belonging to the quiz; their `position` becomes `index + 1`.
     *
     * @param array<int,int> $id_order
     */
    public function reorder(int $quiz_id, array $id_order): bool
    {
        if ( ! $id_order) {
            return true;
        }
        $position = 1;
        foreach ($id_order as $id) {
            $ok = $this->db->update(
                $this->table,
                ['position' => $position],
                ['id' => (int) $id, 'quiz_id' => $quiz_id],
                ['%d'],
                ['%d', '%d']
            );
            if (false === $ok) {
                return false;
            }
            $position++;
        }
        return true;
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
