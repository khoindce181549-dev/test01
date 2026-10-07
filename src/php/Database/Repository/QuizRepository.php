<?php
namespace Quizably\Database\Repository;

use Quizably\Database\Schema;
use Quizably\Database\Uuid;

defined( 'ABSPATH' ) || exit;

final class QuizRepository
{
    private \wpdb $db;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = Schema::table_name('quizzes', $wpdb);
    }

    /**
     * @param array{title:string,slug:string,type?:string,template?:string,settings?:array|null,design?:array|null,author_id?:int} $data
     */
    public function insert(array $data): int
    {
        $now = current_time('mysql', true);
        $row = [
            'uuid'       => Uuid::v4(),
            'title'      => (string) $data['title'],
            'slug'       => (string) $data['slug'],
            'type'       => (string) ($data['type'] ?? 'personality'),
            'status'     => (string) ($data['status'] ?? 'draft'),
            'template'   => (string) ($data['template'] ?? 'classic'),
            'settings'   => isset($data['settings']) ? wp_json_encode($data['settings']) : null,
            'design'     => isset($data['design']) ? wp_json_encode($data['design']) : null,
            'author_id'  => (int) ($data['author_id'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $formats = ['%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'];

        $ok = $this->db->insert($this->table, $row, $formats);
        if (false === $ok) {
            \Quizably\Database\DbError::throw_for('QuizRepository::insert', $this->db);
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

    public function increment_views(int $id): void
    {
        $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = %d",
                $id
            )
        );
    }

    /** @return array|null */
    public function find_by_slug(string $slug): ?array
    {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = %s", $slug),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * @param array{status?:string,type?:string,search?:string,limit?:int,offset?:int,orderby?:string,order?:string} $filters
     * @return array<int,array>
     */
    public function list(array $filters = []): array
    {
        $where  = ['1=1'];
        $values = [];

        if ( ! empty($filters['status']) ) {
            $where[]  = 'status = %s';
            $values[] = (string) $filters['status'];
        }
        if ( ! empty($filters['type']) ) {
            $where[]  = 'type = %s';
            $values[] = (string) $filters['type'];
        }
        if ( ! empty($filters['search']) ) {
            $where[]  = '(title LIKE %s OR slug LIKE %s)';
            $like     = '%' . $this->db->esc_like((string) $filters['search']) . '%';
            $values[] = $like;
            $values[] = $like;
        }

        $orderby = in_array($filters['orderby'] ?? '', ['created_at', 'updated_at', 'title'], true)
            ? $filters['orderby']
            : 'updated_at';
        $order = strtoupper((string) ($filters['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $limit  = max(1, min(200, (int) ($filters['limit'] ?? 20)));
        $offset = max(0, (int) ($filters['offset'] ?? 0));

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where)
            . " ORDER BY {$orderby} {$order} LIMIT {$limit} OFFSET {$offset}";

        if ($values) {
            $sql = $this->db->prepare($sql, ...$values);
        }
        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): bool
    {
        $allowed = ['title','slug','type','status','template','settings','design'];
        $row = [];
        $formats = [];
        foreach ($allowed as $col) {
            if ( ! array_key_exists($col, $data) ) {
                continue;
            }
            if (in_array($col, ['settings','design'], true) && is_array($data[$col])) {
                $row[$col] = wp_json_encode($data[$col]);
            } else {
                $row[$col] = $data[$col];
            }
            $formats[] = '%s';
        }
        if ( ! $row ) {
            return true;
        }
        $row['updated_at'] = current_time('mysql', true);
        $formats[] = '%s';

        $ok = $this->db->update($this->table, $row, ['id' => $id], $formats, ['%d']);
        return false !== $ok;
    }

    public function delete(int $id): bool
    {
        return false !== $this->db->delete($this->table, ['id' => $id], ['%d']);
    }

    public function count(array $filters = []): int
    {
        $where  = ['1=1'];
        $values = [];
        if ( ! empty($filters['status']) ) {
            $where[] = 'status = %s';
            $values[] = (string) $filters['status'];
        }
        if ( ! empty($filters['type']) ) {
            $where[] = 'type = %s';
            $values[] = (string) $filters['type'];
        }
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $where);
        if ($values) {
            $sql = $this->db->prepare($sql, ...$values);
        }
        return (int) $this->db->get_var($sql);
    }
}
