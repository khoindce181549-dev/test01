<?php
namespace Quizably\Core;

defined( 'ABSPATH' ) || exit;

final class Deactivator
{
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
