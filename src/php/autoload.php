<?php
/**
 * Minimal PSR-4 autoloader for the plugin's own `Quizably\*` namespace.
 *
 * Shared by quizably.php (normal boot) and uninstall.php (plugin deletion).
 * uninstall.php runs WITHOUT quizably.php being loaded, so anything it needs
 * beyond a single class must be resolvable through this file — loading just
 * Database/Installer.php left Installer::uninstall() fataling on the
 * unloaded Schema class, which made "Delete plugin" fail with a critical error.
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register( static function ( string $class_name ): void {
    $prefix = 'Quizably\\';
    if ( 0 !== strncmp( $class_name, $prefix, strlen( $prefix ) ) ) {
        return;
    }
    $relative = substr( $class_name, strlen( $prefix ) );
    $path     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';
    if ( file_exists( $path ) ) {
        require $path;
    }
} );
