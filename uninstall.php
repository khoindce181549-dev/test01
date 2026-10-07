<?php
/**
 * Fires when the user deletes the plugin via the WP admin.
 *
 * By default this removes NOTHING: quizzes, leads, submissions, settings and
 * integration configuration all stay in the database, so deleting (or
 * reinstalling / updating by delete-and-upload) never loses a site's work.
 *
 * To wipe everything on deletion (e.g. for GDPR erasure), add this to
 * wp-config.php BEFORE deleting the plugin:
 *
 *     define( 'QUIZABLY_REMOVE_ALL_DATA', true );
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

if (!defined('QUIZABLY_REMOVE_ALL_DATA') || !QUIZABLY_REMOVE_ALL_DATA) {
    return;
}

// No Composer dependency at runtime — see quizably.php for why. quizably.php is not
// loaded during uninstall, so register the plugin's shared autoloader here;
// Installer::uninstall() needs more than one class (Schema).
require_once __DIR__ . '/src/php/autoload.php';

if (class_exists(\Quizably\Database\Installer::class)) {
    (new \Quizably\Database\Installer())->uninstall();
}

// Every option this plugin stores is prefixed `quizably_` (version, integration
// configs, double opt-in secret, ...), so remove them by prefix.
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like('quizably_') . '%'
    )
);

// Per-user state (the dashboard review prompt's answer).
delete_metadata('user', 0, 'quizably_review_prompt', '', true);
