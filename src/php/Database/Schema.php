<?php
namespace Quizably\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Centralized table SQL. Used by Installer with dbDelta.
 *
 * All tables MUST follow dbDelta quirks:
 *  - Two spaces between PRIMARY KEY and (col)
 *  - Column definitions on their own lines
 *  - No backticks
 *  - Lowercase SQL keywords (preference)
 */
final class Schema
{
    public const TABLES = [
        'quizzes',
        'questions',
        'answers',
        'results',
        'submissions',
        'leads',
        'settings',
        'question_bank',
    ];

    public static function table_name(string $base, \wpdb $wpdb): string
    {
        return $wpdb->prefix . 'quizably_' . $base;
    }

    /** @return array<int,string> SQL statements suitable for dbDelta */
    public static function all_sql(\wpdb $wpdb): array
    {
        $charset = $wpdb->get_charset_collate();
        return [
            self::quizzes_sql($wpdb, $charset),
            self::questions_sql($wpdb, $charset),
            self::answers_sql($wpdb, $charset),
            self::results_sql($wpdb, $charset),
            self::submissions_sql($wpdb, $charset),
            self::leads_sql($wpdb, $charset),
            self::settings_sql($wpdb, $charset),
            self::question_bank_sql($wpdb, $charset),
        ];
    }

    private static function quizzes_sql(\wpdb $wpdb, string $charset): string
    {
        $t = self::table_name('quizzes', $wpdb);
        return "CREATE TABLE $t (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            type VARCHAR(32) NOT NULL DEFAULT 'personality',
            status VARCHAR(16) NOT NULL DEFAULT 'draft',
            template VARCHAR(64) NOT NULL DEFAULT 'classic',
            settings LONGTEXT NULL,
            design LONGTEXT NULL,
            view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
            author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            UNIQUE KEY slug (slug),
            KEY status_type (status, type),
            KEY author (author_id)
        ) $charset;";
    }

    private static function questions_sql(\wpdb $wpdb, string $charset): string
    {
        $t = self::table_name('questions', $wpdb);
        // bank_origin_id is added in schema 1.1.0 — when a question was
        // inserted from the Question Bank, this points back to the bank
        // row so we can show "X uses of this bank question" in the bank list.
        // NULL for hand-written questions (the default).
        return "CREATE TABLE $t (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            quiz_id BIGINT UNSIGNED NOT NULL,
            bank_origin_id BIGINT UNSIGNED NULL,
            type VARCHAR(32) NOT NULL DEFAULT 'single',
            title TEXT NOT NULL,
            description TEXT NULL,
            media_url VARCHAR(2048) NULL,
            media_type VARCHAR(16) NULL,
            required TINYINT(1) NOT NULL DEFAULT 0,
            position INT NOT NULL DEFAULT 0,
            settings LONGTEXT NULL,
            logic LONGTEXT NULL,
            PRIMARY KEY  (id),
            KEY quiz_pos (quiz_id, position),
            KEY bank_origin (bank_origin_id)
        ) $charset;";
    }

    private static function answers_sql(\wpdb $wpdb, string $charset): string
    {
        $t = self::table_name('answers', $wpdb);
        return "CREATE TABLE $t (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            question_id BIGINT UNSIGNED NOT NULL,
            label TEXT NOT NULL,
            value VARCHAR(255) NOT NULL DEFAULT '',
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            points INT NOT NULL DEFAULT 0,
            weights LONGTEXT NULL,
            personality_result_id BIGINT UNSIGNED NULL,
            media_url VARCHAR(2048) NULL,
            position INT NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY question_pos (question_id, position),
            KEY personality (personality_result_id)
        ) $charset;";
    }

    private static function results_sql(\wpdb $wpdb, string $charset): string
    {
        $t = self::table_name('results', $wpdb);
        // settings is added in schema 1.2.0 — JSON blob for per-result
        // settings (currently: background_image / overlay_color /
        // overlay_opacity / enabled). Existing installs get the column via
        // dbDelta during maybe_upgrade().
        return "CREATE TABLE $t (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            quiz_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NULL,
            image_url VARCHAR(2048) NULL,
            cta_label VARCHAR(255) NULL,
            cta_url VARCHAR(2048) NULL,
            redirect_url VARCHAR(2048) NULL,
            score_min INT NULL,
            score_max INT NULL,
            conditions LONGTEXT NULL,
            settings LONGTEXT NULL,
            position INT NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY quiz_pos (quiz_id, position)
        ) $charset;";
    }

    private static function submissions_sql(\wpdb $wpdb, string $charset): string
    {
        $t = self::table_name('submissions', $wpdb);
        return "CREATE TABLE $t (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            quiz_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            lead_id BIGINT UNSIGNED NULL,
            answers LONGTEXT NULL,
            score INT NULL,
            result_id BIGINT UNSIGNED NULL,
            result_breakdown LONGTEXT NULL,
            ip_hash CHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            utm LONGTEXT NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'in_progress',
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            KEY quiz_status (quiz_id, status, completed_at),
            KEY lead_id (lead_id)
        ) $charset;";
    }

    private static function leads_sql(\wpdb $wpdb, string $charset): string
    {
        $t = self::table_name('leads', $wpdb);
        return "CREATE TABLE $t (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            quiz_id BIGINT UNSIGNED NOT NULL,
            email VARCHAR(320) NOT NULL,
            name VARCHAR(255) NULL,
            phone VARCHAR(64) NULL,
            extra_fields LONGTEXT NULL,
            consent_gdpr TINYINT(1) NOT NULL DEFAULT 0,
            double_optin_verified TINYINT(1) NULL,
            integration_sync_status LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY quiz_email (quiz_id, email),
            KEY quiz (quiz_id)
        ) $charset;";
    }

    private static function settings_sql(\wpdb $wpdb, string $charset): string
    {
        $t = self::table_name('settings', $wpdb);
        return "CREATE TABLE $t (
            setting_key VARCHAR(128) NOT NULL,
            value LONGTEXT NULL,
            PRIMARY KEY  (setting_key)
        ) $charset;";
    }

    /**
     * Reusable Question Bank — each row is a "template" that can be copied
     * into one or more quizzes via QuestionBankController::insert(). The
     * `answers` column is a JSON array because bank questions don't share
     * the per-quiz wp_quizably_answers table (no question_id until insert).
     * `tags` is a comma-separated, lowercase, slugified list — cheap to
     * filter with LIKE and avoids a third table for v1. `usage_count`
     * increments on every successful insert into a quiz.
     */
    private static function question_bank_sql(\wpdb $wpdb, string $charset): string
    {
        $t = self::table_name('question_bank', $wpdb);
        return "CREATE TABLE $t (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            type VARCHAR(32) NOT NULL DEFAULT 'single',
            title TEXT NOT NULL,
            description TEXT NULL,
            media_url VARCHAR(2048) NULL,
            media_type VARCHAR(16) NULL,
            settings LONGTEXT NULL,
            answers LONGTEXT NULL,
            tags VARCHAR(512) NULL,
            author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            usage_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            KEY type_updated (type, updated_at),
            KEY author (author_id)
        ) $charset;";
    }
}
