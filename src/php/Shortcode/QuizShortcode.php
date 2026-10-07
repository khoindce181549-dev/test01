<?php
namespace Quizably\Shortcode;

use Quizably\Core\Plugin;
use Quizably\Frontend\Assets;

defined( 'ABSPATH' ) || exit;

final class QuizShortcode
{
    public const TAG = 'quizably_quiz';

    private Assets $assets;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
    }

    public function register(): void
    {
        add_shortcode(self::TAG, [$this, 'render']);
    }

    /** @param array<string,mixed>|string $atts */
    public function render($atts = []): string
    {
        $atts = shortcode_atts(
            ['id' => '', 'autostart' => ''],
            is_array($atts) ? $atts : [],
            self::TAG
        );

        $quiz = QuizEmbedData::find_published(sanitize_text_field((string) $atts['id']));
        if ( ! $quiz) {
            return '';
        }

        $repos = Plugin::instance()->services()['repos'] ?? [];
        $repos['quizzes']->increment_views((int) $quiz['id']);

        $this->assets->ensure_enqueued();

        $uuid     = esc_attr((string) $quiz['uuid']);
        $nonce    = esc_attr(wp_create_nonce('wp_rest'));
        $api_root = esc_attr( esc_url_raw( rest_url('quizably/v1/public/') ) );

        // autostart="1" (the block's "Auto-start on load" toggle): the player skips its intro screen
        // and goes straight to the first question. Read from this attribute by frontend/main.js.
        $autostart = self::truthy($atts['autostart']) ? ' data-auto-start="1"' : '';

        $html = sprintf(
            '<div class="quizably-quiz-root" data-quiz-uuid="%s" data-nonce="%s" data-api-root="%s"%s><noscript>%s</noscript></div>',
            $uuid,
            $nonce,
            $api_root,
            $autostart,
            esc_html__('This quiz requires JavaScript.', 'quizably')
        );

        $data = QuizEmbedData::data_script($quiz);
        return '' === $data ? $html : $html . "\n" . $data;
    }

    /** Shortcode-style boolean: "1", "true", "yes", "on" (any case) are on; anything else is off. */
    public static function truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
