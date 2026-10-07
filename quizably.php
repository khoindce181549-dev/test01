<?php
/**
 * Plugin Name:       Quizably – Quiz Maker, Personality Quiz & Survey Builder
 * Plugin URI:        https://wpgrowkit.com/plugins/quizably/
 * Description:       Modern Vue-powered quiz builder with multiple quiz types, templates, forms, and dynamic results.
 * Version:           1.2.3
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WP Grow Kit
 * Author URI:        https://wpgrowkit.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quizably
 * Domain Path:       /languages
 *
 * @package Quizably
 */

defined( 'ABSPATH' ) || exit;

define( 'QUIZABLY_VERSION', '1.2.3' );
define( 'QUIZABLY_PLUGIN_FILE', __FILE__ );
define( 'QUIZABLY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUIZABLY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QUIZABLY_MIN_PHP', '7.4' );
define( 'QUIZABLY_MIN_WP', '6.0' );

// PHP version guard.
if ( version_compare( PHP_VERSION, QUIZABLY_MIN_PHP, '<' ) ) {
    add_action( 'admin_notices', static function () {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html( sprintf(
                /* translators: %s: required PHP version */
                __( 'Quizably requires PHP %s or higher.', 'quizably' ),
                QUIZABLY_MIN_PHP
            ) )
        );
    } );
    return;
}

// Class autoloader.
//
// The plugin has zero runtime PHP dependencies — every `Quizably\*`
// class lives under src/php/ and nothing else is required to boot. Composer
// is still used in development (composer.json's require-dev pulls in
// PHPUnit/Brain\Monkey/WPCS for testing/linting), but a distributed copy of
// the plugin must never depend on `vendor/` existing: that directory is
// gitignored and isn't part of a release zip, and requiring end users to run
// `composer install` on a shared host isn't reasonable for a WP.org-style
// plugin. So we register a small PSR-4 autoloader for our own namespace
// instead of requiring vendor/autoload.php (see src/php/autoload.php, also
// used by uninstall.php).
require_once QUIZABLY_PLUGIN_DIR . 'src/php/autoload.php';

// Activation / deactivation.
register_activation_hook( __FILE__, [ \Quizably\Core\Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \Quizably\Core\Deactivator::class, 'deactivate' ] );

// Bootstrap.
add_action( 'plugins_loaded', static function () {
    \Quizably\Core\Plugin::instance()->boot();
} );
