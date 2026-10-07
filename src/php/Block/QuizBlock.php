<?php
namespace Quizably\Block;

use Quizably\Shortcode\QuizShortcode;

defined( 'ABSPATH' ) || exit;

final class QuizBlock
{
    public const BLOCK_NAME = 'quizably/quiz';
    public const HANDLE     = 'quizably-block';

    private QuizShortcode $shortcode;

    public function __construct(QuizShortcode $shortcode)
    {
        $this->shortcode = $shortcode;
    }

    public function register(): void
    {
        add_action('init', [$this, 'register_block']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor']);
        add_filter('script_loader_tag', [$this, 'module_tag'], 10, 3);
    }

    /**
     * Emit the block editor script as a real ES module so Vite's output
     * (which uses `import { ... } from '@wordpress/...'` semantics at
     * build time) parses correctly in the browser. @wordpress/* imports
     * are externalized to `window.wp.*` via Rollup `globals`.
     */
    public function module_tag(string $tag, string $handle, string $src): string
    {
        if (self::HANDLE === $handle) {
            return sprintf(
                '<script type="module" src="%s" id="%s-js"></script>' . "\n", // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- rewrites the tag of a script already enqueued via wp_enqueue_script() below; see Admin\Assets::module_tag()
                esc_url($src),
                esc_attr(self::HANDLE)
            );
        }
        return $tag;
    }

    public function register_block(): void
    {
        if ( ! function_exists('register_block_type')) {
            return;
        }

        register_block_type(self::BLOCK_NAME, [
            'api_version'     => 3,
            'title'           => __('Advance Quiz', 'quizably'),
            'category'        => 'widgets',
            'icon'            => 'editor-help',
            'keywords'        => ['quiz', 'quizably', 'form'],
            'render_callback' => [$this, 'render'],
            'attributes'      => [
                'quizId'    => ['type' => 'string',  'default' => ''],
                'autoStart' => ['type' => 'boolean', 'default' => false],
            ],
            'editor_script'   => self::HANDLE,
        ]);
    }

    public function enqueue_editor(): void
    {
        $path = QUIZABLY_PLUGIN_DIR . 'assets/dist/block-editor.js';
        if ( ! file_exists($path)) {
            return;
        }

        wp_enqueue_script(
            self::HANDLE,
            QUIZABLY_PLUGIN_URL . 'assets/dist/block-editor.js',
            [
                'wp-blocks',
                'wp-element',
                'wp-block-editor',
                'wp-components',
                'wp-i18n',
                'wp-api-fetch',
            ],
            QUIZABLY_VERSION,
            true
        );
    }

    /**
     * @param array<string,mixed> $atts
     */
    public function render(array $atts): string
    {
        $id = sanitize_text_field((string) ($atts['quizId'] ?? ''));
        if ('' === $id) {
            return '<div class="quizably-block-placeholder">'
                . esc_html__('Choose a quiz in the block settings.', 'quizably')
                . '</div>';
        }
        // The block's "Auto-start on load" toggle: skip the intro screen.
        return $this->shortcode->render([
            'id'        => $id,
            'autostart' => ! empty($atts['autoStart']) ? '1' : '',
        ]);
    }
}
