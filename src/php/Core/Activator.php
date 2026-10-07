<?php
namespace Quizably\Core;

use Quizably\Database\Installer;
use Quizably\REST\BaseController;

defined( 'ABSPATH' ) || exit;

final class Activator
{
    public static function activate(): void
    {
        ( new Installer() )->install();
        update_option('quizably_version', QUIZABLY_VERSION);
        \Quizably\Admin\ReviewPrompt::ensure_installed_at();

        if ( function_exists('get_role') ) {
            $admin = get_role('administrator');
            if ( $admin && ! $admin->has_cap(BaseController::CAPABILITY) ) {
                $admin->add_cap(BaseController::CAPABILITY);
            }
        }

        flush_rewrite_rules();
    }
}
