<?php

namespace Quizably\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a $wpdb write failure into a RuntimeException that is safe to surface
 * to a REST client.
 *
 * Several REST controllers (AnswerController, QuestionController,
 * QuizController, ResultController, PresetController, QuestionBankController)
 * catch a repository's RuntimeException and return $e->getMessage() verbatim
 * as the API error response. $wpdb->last_error is the raw MySQL error text —
 * it can include table and column names, and fragments of the query itself —
 * so it must never end up in the exception message. It's logged instead,
 * gated on WP_DEBUG like WordPress's own debug logging, so a site owner can
 * still see the real cause without an unauthenticated client requesting a
 * malformed payload being able to fish for schema details in the response.
 */
final class DbError
{
    /**
     * @param string $context e.g. 'AnswerRepository::insert'.
     * @param \wpdb  $db      The $wpdb instance whose last_error caused the failure.
     *
     * @throws \RuntimeException Always — this method never returns.
     */
    public static function throw_for(string $context, \wpdb $db): void
    {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            // Deliberate, WP_DEBUG-gated debug logging — not a forgotten debug statement.
            error_log( sprintf('[Quizably] %s failed: %s', $context, $db->last_error) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        // $context is always a literal string a call site wrote in its own source (e.g.
        // 'AnswerRepository::insert'), never request- or database-derived; there is
        // nothing here for an escaping function to sanitise.
        throw new \RuntimeException($context . ' failed.'); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
    }
}
