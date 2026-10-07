<?php
namespace Quizably\Database\Repository;

use Quizably\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class AnswerRepository
{
    private \wpdb $db;
    private string $table;
    private string $questions_table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = Schema::table_name('answers', $wpdb);
        $this->questions_table = Schema::table_name('questions', $wpdb);
    }

    /**
     * @param array{question_id:int,label:string,value?:string,is_correct?:int|bool,points?:int,weights?:array|null,personality_result_id?:int|null,media_url?:string|null,position?:int} $data
     */
    public function insert(array $data): int
    {
        $row = [
            'question_id'           => (int) $data['question_id'],
            'label'                 => (string) $data['label'],
            'value'                 => (string) ($data['value'] ?? ''),
            'is_correct'            => ! empty($data['is_correct']) ? 1 : 0,
            'points'                => (int) ($data['points'] ?? 0),
            'weights'               => isset($data['weights']) ? wp_json_encode($data['weights']) : null,
            'personality_result_id' => isset($data['personality_result_id']) ? (int) $data['personality_result_id'] : null,
            'media_url'             => isset($data['media_url']) ? (string) $data['media_url'] : null,
            'position'              => (int) ($data['position'] ?? 0),
        ];
        $formats = ['%d','%s','%s','%d','%d','%s','%d','%s','%d'];

        $ok = $this->db->insert($this->table, $row, $formats);
        if (false === $ok) {
            \Quizably\Database\DbError::throw_for('AnswerRepository::insert', $this->db);
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
    public function find_by_question(int $question_id): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE question_id = %d ORDER BY position ASC, id ASC",
                $question_id
            ),
            ARRAY_A
        );
        return $rows ?: [];
    }

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): bool
    {
        $allowed = ['question_id','label','value','is_correct','points','weights','personality_result_id','media_url','position'];
        $row = [];
        $formats = [];
        foreach ($allowed as $col) {
            if ( ! array_key_exists($col, $data) ) {
                continue;
            }
            $value = $data[$col];
            if ($col === 'weights' && is_array($value)) {
                $row[$col] = wp_json_encode($value);
                $formats[] = '%s';
            } elseif (in_array($col, ['question_id','is_correct','points','personality_result_id','position'], true)) {
                if ($col === 'is_correct') {
                    $row[$col] = $value ? 1 : 0;
                } elseif ($col === 'personality_result_id' && $value === null) {
                    $row[$col] = null;
                } else {
                    $row[$col] = (int) $value;
                }
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
     * Reorder answers for a question. `$id_order` is an ordered list of answer IDs
     * belonging to the question; their `position` becomes `index + 1`.
     *
     * @param array<int,int> $id_order
     */
    public function reorder(int $question_id, array $id_order): bool
    {
        if ( ! $id_order) {
            return true;
        }
        $position = 1;
        foreach ($id_order as $id) {
            $ok = $this->db->update(
                $this->table,
                ['position' => $position],
                ['id' => (int) $id, 'question_id' => $question_id],
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

    public function delete_by_question(int $question_id): bool
    {
        return false !== $this->db->delete($this->table, ['question_id' => $question_id], ['%d']);
    }

    public function delete_by_quiz(int $quiz_id): bool
    {
        $sql = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE question_id IN (SELECT id FROM {$this->questions_table} WHERE quiz_id = %d)",
            $quiz_id
        );
        return false !== $this->db->query($sql);
    }
}
