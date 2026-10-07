<?php
namespace Quizably\Database\Repository;

use Quizably\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class SettingsRepository
{
    private \wpdb $db;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = Schema::table_name('settings', $wpdb);
    }

    /**
     * Read a single setting. If the stored value starts with `{` or `[`, it is
     * JSON-decoded before returning; otherwise it is returned as-is.
     *
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $value = $this->db->get_var(
            $this->db->prepare("SELECT value FROM {$this->table} WHERE setting_key = %s", $key)
        );
        if ($value === null) {
            return $default;
        }
        return $this->maybe_decode((string) $value);
    }

    /**
     * Upsert a setting. Non-scalar values are JSON-encoded.
     *
     * @param mixed $value
     */
    public function set(string $key, $value): bool
    {
        $stored = is_scalar($value) || $value === null
            ? ($value === null ? null : (string) $value)
            : wp_json_encode($value);

        $exists = null !== $this->db->get_var(
            $this->db->prepare("SELECT setting_key FROM {$this->table} WHERE setting_key = %s", $key)
        );

        if ($exists) {
            $ok = $this->db->update(
                $this->table,
                ['value' => $stored],
                ['setting_key' => $key],
                ['%s'],
                ['%s']
            );
        } else {
            $ok = $this->db->insert(
                $this->table,
                ['setting_key' => $key, 'value' => $stored],
                ['%s', '%s']
            );
        }
        return false !== $ok;
    }

    public function delete(string $key): bool
    {
        return false !== $this->db->delete($this->table, ['setting_key' => $key], ['%s']);
    }

    /**
     * Return the full key/value map, with JSON values decoded.
     *
     * @return array<string,mixed>
     */
    public function all(): array
    {
        $rows = $this->db->get_results("SELECT setting_key, value FROM {$this->table}", ARRAY_A) ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['setting_key']] = $row['value'] === null
                ? null
                : $this->maybe_decode((string) $row['value']);
        }
        return $out;
    }

    /**
     * @return mixed
     */
    private function maybe_decode(string $raw)
    {
        $first = substr($raw, 0, 1);
        if ($first === '{' || $first === '[') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $raw;
    }
}
