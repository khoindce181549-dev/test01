<?php
namespace Quizably\Shortcode;

use Quizably\Core\Plugin;
use Quizably\Frontend\Assets;

defined( 'ABSPATH' ) || exit;

final class QuizSlideinShortcode
{
    public const TAG = 'quizably_quiz_slidein';

    /** Allowed position values. */
    private const ALLOWED_POSITIONS = ['bottom-right', 'bottom-left'];

    /**
     * Handle for this shortcode's own small, static CSS. See the identical
     * comment on QuizPopupShortcode::HANDLE for why this is registered
     * unconditionally on `wp_enqueue_scripts` rather than only when the
     * shortcode actually renders.
     */
    private const HANDLE = 'quizably-quiz-slidein';

    private Assets $assets;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
    }

    public function register(): void
    {
        add_shortcode(self::TAG, [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'register_inline_assets']);
    }

    public function register_inline_assets(): void
    {
        wp_register_style(self::HANDLE, false, [], QUIZABLY_VERSION);
        wp_enqueue_style(self::HANDLE);
        wp_add_inline_style(self::HANDLE, $this->inline_styles());

        // Behaviour is the script shared with the popup — see EmbedTrigger.
        EmbedTrigger::register_script();
    }

    /** @param array<string,mixed>|string $atts */
    public function render($atts = []): string
    {
        $atts = shortcode_atts(
            ['id' => '', 'label' => 'Take quiz', 'position' => ''] + EmbedTrigger::default_atts(),
            is_array($atts) ? $atts : [],
            self::TAG
        );

        $uuid     = sanitize_text_field((string) $atts['id']);
        $label    = sanitize_text_field((string) $atts['label']);
        $position = sanitize_text_field((string) $atts['position']);
        $trigger  = EmbedTrigger::parse($atts);

        // Corner decision (RTL): an explicit `bottom-left` / `bottom-right` is the
        // author's physical choice and is honoured as-is. When unspecified (or
        // invalid) the widget sits in the inline-end corner, so it is bottom-right
        // on LTR sites and bottom-left on RTL sites, like a chat launcher should.
        if ( ! in_array($position, self::ALLOWED_POSITIONS, true)) {
            $position = 'bottom-end';
        }

        if ('' === $uuid) {
            return '';
        }

        // Numeric quiz id or UUID, published quizzes only (same rules as [quizably_quiz]).
        $quiz = QuizEmbedData::find_published($uuid);
        if ( ! $quiz) {
            return '';
        }

        $repos = Plugin::instance()->services()['repos'] ?? [];
        $repos['quizzes']->increment_views((int) $quiz['id']);

        // Enqueue frontend assets.
        $this->assets->ensure_enqueued();

        $uuid_attr    = esc_attr((string) $quiz['uuid']);
        $nonce        = esc_attr(wp_create_nonce('wp_rest'));
        $api_root     = esc_attr(esc_url_raw(rest_url('quizably/v1/public/')));
        $label_html   = esc_html($label);
        $position_cls = esc_attr($position);
        $label_attr   = esc_attr($label);
        $panel_id     = esc_attr(EmbedTrigger::next_id('slidein'));

        $html = sprintf(
            '<div class="quizably-slidein-embed quizably-slidein-embed--%1$s" data-quizably-embed="slidein" data-uuid="%2$s"%7$s>
  <button type="button" class="quizably-slidein-trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="%8$s">%3$s</button>
  <div class="quizably-slidein-panel" id="%8$s" role="dialog" aria-modal="false" aria-label="%9$s" tabindex="-1" hidden>
    <div class="quizably-slidein-header">
      <span>%3$s</span>
      <button type="button" class="quizably-slidein-close" aria-label="%4$s">&times;</button>
    </div>
    <div class="quizably-quiz-root" data-quiz-uuid="%2$s" data-nonce="%5$s" data-api-root="%6$s"></div>
  </div>
</div>',
            $position_cls,
            $uuid_attr,
            $label_html,
            esc_attr__('Close', 'quizably'),
            $nonce,
            $api_root,
            EmbedTrigger::data_attributes($trigger),
            $panel_id,
            $label_attr
        );

        // The player reads the quiz from this tag; without it the slide-in would open an empty quiz.
        $data = QuizEmbedData::data_script($quiz);
        return '' === $data ? $html : $html . "
" . $data;
    }

    /** Raw CSS only — no wrapping <style> tag, this is handed to wp_add_inline_style(). */
    private function inline_styles(): string
    {
        return '
.quizably-slidein-embed{position:fixed;bottom:24px;inset-inline-end:24px;z-index:9999}
.quizably-slidein-embed--bottom-left{inset-inline-end:auto;right:auto;left:24px}
.quizably-slidein-embed--bottom-right{inset-inline-end:auto;left:auto;right:24px}
.quizably-slidein-trigger{display:inline-flex;align-items:center;padding:14px 20px;background:var(--quizably-quiz-brand,#4F46E5);color:#fff;border:none;border-radius:50px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.2)}
.quizably-slidein-panel{position:absolute;bottom:64px;inset-inline-end:0;width:360px;max-height:520px;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.18);overflow-y:auto;display:flex;flex-direction:column}
.quizably-slidein-embed--bottom-left .quizably-slidein-panel{inset-inline-end:auto;right:auto;left:0}
.quizably-slidein-embed--bottom-right .quizably-slidein-panel{inset-inline-end:auto;left:auto;right:0}
.quizably-slidein-panel:focus{outline:none}
.quizably-slidein-panel[hidden]{display:none!important}
.quizably-slidein-header{display:flex;align-items:center;justify-content:space-between;padding:16px 16px 0;font-weight:600;font-size:14px}
.quizably-slidein-close{background:none;border:none;font-size:20px;cursor:pointer;line-height:1}
';
    }
}
