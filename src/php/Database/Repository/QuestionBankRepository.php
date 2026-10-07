<?php
namespace Quizably\Database\Repository;

use Quizably\Database\Schema;
use Quizably\Database\Uuid;

defined( 'ABSPATH' ) || exit;

/**
 * Reusable Question Bank — author once, insert into many quizzes.
 *
 * Bank rows are templates. They never participate in scoring or branching
 * directly; QuestionBankController::insert() copies a row into the per-quiz
 * tables (quizably_questions + quizably_answers) at insert time. The new question
 * gets `bank_origin_id` set so the bank UI can show usage counts.
 */
final class QuestionBankRepository
{
    private \wpdb $db;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = Schema::table_name('question_bank', $wpdb);
    }

    /**
     * @param array{type?:string,title:string,description?:string|null,media_url?:string|null,media_type?:string|null,settings?:array|null,answers?:array|null,tags?:array|string|null,author_id?:int} $data
     */
    public function insert(array $data): int
    {
        $now = current_time('mysql', true);
        $row = [
            'uuid'        => Uuid::v4(),
            'type'        => (string) ($data['type'] ?? 'single'),
            'title'       => (string) $data['title'],
            'description' => isset($data['description']) ? (string) $data['description'] : null,
            'media_url'   => isset($data['media_url']) ? (string) $data['media_url'] : null,
            'media_type'  => isset($data['media_type']) ? (string) $data['media_type'] : null,
            'settings'    => isset($data['settings']) ? wp_json_encode($data['settings']) : null,
            'answers'     => isset($data['answers']) ? wp_json_encode($data['answers']) : null,
            'tags'        => self::normalize_tags($data['tags'] ?? null),
            'author_id'   => (int) ($data['author_id'] ?? 0),
            'usage_count' => 0,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];
        $formats = ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%d','%s','%s'];

        $ok = $this->db->insert($this->table, $row, $formats);
        if (false === $ok) {
            \Quizably\Database\DbError::throw_for('QuestionBankRepository::insert', $this->db);
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

    /**
     * Search the bank with optional text, tag, and type filters. The result
     * shape mirrors the rest of the REST controllers (`['items', 'total']`)
     * so the Pinia store can use the same merge pattern as quizzes/leads.
     *
     * @param array{search?:string,tags?:array<int,string>,type?:string,orderby?:string,order?:string,limit?:int,offset?:int} $args
     * @return array{items:array<int,array>,total:int}
     */
    public function search(array $args = []): array
    {
        $where  = [];
        $params = [];

        if ( ! empty($args['search']) ) {
            $like = '%' . $this->db->esc_like((string) $args['search']) . '%';
            $where[]  = '(title LIKE %s OR description LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        if ( ! empty($args['type']) ) {
            $where[]  = 'type = %s';
            $params[] = (string) $args['type'];
        }

        // Tag filter is OR across the requested tags — a row matches if any
        // of its comma-separated tags appears. Wrapping the column with
        // commas on both sides ensures partial slugs ("pro" vs "product")
        // don't accidentally match.
        if ( ! empty($args['tags']) && is_array($args['tags']) ) {
            $tagWhere = [];
            foreach ($args['tags'] as $tag) {
                $tagSlug = self::slugify_tag((string) $tag);
                if ('' === $tagSlug) {
                    continue;
                }
                $tagWhere[] = 'CONCAT(",", REPLACE(IFNULL(tags, ""), ", ", ","), ",") LIKE %s';
                $params[]   = '%,' . $this->db->esc_like($tagSlug) . ',%';
            }
            if ($tagWhere) {
                $where[] = '(' . implode(' OR ', $tagWhere) . ')';
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $orderby = in_array($args['orderby'] ?? '', ['updated_at', 'created_at', 'title', 'usage_count'], true)
            ? $args['orderby']
            : 'updated_at';
        $order = strtoupper($args['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';
        $limit  = isset($args['limit'])  ? max(1, min(200, (int) $args['limit']))  : 50;
        $offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;

        // phpcs:disable WordPress.DB.PreparedSQL
        $countSql = "SELECT COUNT(*) FROM {$this->table} {$whereSql}";
        $total = (int) ( $params
            ? $this->db->get_var($this->db->prepare($countSql, $params))
            : $this->db->get_var($countSql)
        );

        $listSql = "SELECT * FROM {$this->table} {$whereSql} "
            . "ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $listParams = array_merge($params, [$limit, $offset]);
        $rows = $this->db->get_results($this->db->prepare($listSql, $listParams), ARRAY_A);
        // phpcs:enable WordPress.DB.PreparedSQL

        return [
            'items' => $rows ?: [],
            'total' => $total,
        ];
    }

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): bool
    {
        $allowed = ['type','title','description','media_url','media_type','settings','answers','tags'];
        $row = [];
        $formats = [];
        foreach ($allowed as $col) {
            if ( ! array_key_exists($col, $data) ) {
                continue;
            }
            $value = $data[$col];
            if (in_array($col, ['settings','answers'], true) && is_array($value)) {
                $row[$col] = wp_json_encode($value);
                $formats[] = '%s';
            } elseif ('tags' === $col) {
                $row[$col] = self::normalize_tags($value);
                $formats[] = '%s';
            } else {
                $row[$col] = $value;
                $formats[] = '%s';
            }
        }
        if ( ! $row ) {
            return true;
        }
        $row['updated_at'] = current_time('mysql', true);
        $formats[]         = '%s';

        $ok = $this->db->update($this->table, $row, ['id' => $id], $formats, ['%d']);
        return false !== $ok;
    }

    public function delete(int $id): bool
    {
        return false !== $this->db->delete($this->table, ['id' => $id], ['%d']);
    }

    public function increment_usage(int $id): void
    {
        // phpcs:ignore WordPress.DB.PreparedSQL
        $this->db->query($this->db->prepare(
            "UPDATE {$this->table} SET usage_count = usage_count + 1, updated_at = %s WHERE id = %d",
            current_time('mysql', true),
            $id
        ));
    }

    /**
     * Distinct tag list with counts. Used by the bank toolbar's tag filter.
     * Cheap-and-cheerful: pulls all rows and tallies in PHP. If a user
     * accumulates ~10k bank questions this becomes a candidate for a
     * dedicated tags table — flagged as a §10 risk in the plan doc.
     *
     * @return array<int,array{name:string,count:int}>
     */
    public function tags_index(): array
    {
        $rows = $this->db->get_col("SELECT tags FROM {$this->table} WHERE tags IS NOT NULL AND tags <> ''");
        $counts = [];
        foreach ($rows as $tagsCsv) {
            foreach (array_filter(array_map('trim', explode(',', (string) $tagsCsv))) as $tag) {
                if ( ! isset($counts[$tag]) ) {
                    $counts[$tag] = 0;
                }
                $counts[$tag]++;
            }
        }
        ksort($counts);
        $out = [];
        foreach ($counts as $name => $count) {
            $out[] = ['name' => $name, 'count' => $count];
        }
        return $out;
    }

    /**
     * Tag normalization: accepts an array or comma-separated string, slugifies
     * each value, dedupes, and caps at 10 tags per question. Returns a clean
     * comma-separated string suitable for storage and LIKE-based filtering.
     *
     * @param array<int,string>|string|null $value
     */
    private static function normalize_tags($value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if ( ! is_array($value) ) {
            return null;
        }
        $clean = [];
        foreach ($value as $tag) {
            $slug = self::slugify_tag((string) $tag);
            if ('' === $slug) {
                continue;
            }
            $clean[$slug] = true;
            if (count($clean) >= 10) {
                break;
            }
        }
        return $clean ? implode(',', array_keys($clean)) : null;
    }

    /** Lowercase, hyphenated tag slug (a-z, 0-9, hyphen). */
    private static function slugify_tag(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim((string) $value, '-');
    }
}
