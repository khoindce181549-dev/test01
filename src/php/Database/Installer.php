<?php
namespace Quizably\Database;

defined( 'ABSPATH' ) || exit;

final class Installer
{
    public const VERSION_OPTION = 'quizably_db_version';
    // 1.1.0 — added wp_quizably_question_bank table and quizably_questions.bank_origin_id
    // column for the Free Question Bank feature (2026-04-30).
    // 1.2.0 — added wp_quizably_results.settings column for per-result background
    // / overlay JSON (2026-05-02).
    // 1.3.0 — added quizably_quizzes.view_count for analytics "views vs completions"
    // (2026-05-14).
    // 1.3.1 — renamed the submissions index `lead` to `lead_id`. LEAD is a reserved
    // word in MySQL 8, so the old CREATE TABLE failed there (and on WordPress
    // Playground's SQLite) and the submissions table was never created. Bumping the
    // version makes maybe_upgrade() create it on sites that are missing it (2026-10-06).
    public const SCHEMA_VERSION = '1.3.1';

    public function install(): void
    {
        global $wpdb;
        if ( ! function_exists('dbDelta') ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        foreach (Schema::all_sql($wpdb) as $sql) {
            dbDelta($sql);
        }
        $this->drop_legacy_lead_index($wpdb);

        update_option(self::VERSION_OPTION, self::SCHEMA_VERSION);
    }

    /**
     * Sites created before schema 1.3.1 on MySQL 5.7 or MariaDB have the submissions
     * index under its old name `lead`. dbDelta adds the new `lead_id` index but never
     * drops indexes, so remove the old duplicate here.
     */
    private function drop_legacy_lead_index(\wpdb $wpdb): void
    {
        $table = Schema::table_name('submissions', $wpdb);
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Schema.
        $has_legacy = $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$table} WHERE Key_name = %s", 'lead'));
        if ($has_legacy) {
            $wpdb->query("ALTER TABLE {$table} DROP INDEX `lead`"); // phpcs:ignore WordPress.DB.PreparedSQL
        }
    }

    public function maybe_upgrade(): void
    {
        $installed = (string) get_option(self::VERSION_OPTION, '');
        if ($installed === self::SCHEMA_VERSION) {
            return;
        }
        $this->install();
    }

    public function uninstall(): void
    {
        global $wpdb;
        foreach (Schema::TABLES as $base) {
            $table = Schema::table_name($base, $wpdb);
            $wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL
        }
        delete_option(self::VERSION_OPTION);
    }
}
