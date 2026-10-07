<?php
namespace Quizably\Shortcode;

use Quizably\Core\Plugin;
use Quizably\Frontend\Assets;

defined( 'ABSPATH' ) || exit;

final class QuizPopupShortcode
{
    public const TAG = 'quizably_quiz_popup';

    /**
     * Handle for the popup's own small, static CSS — carried entirely as
     * inline data (no physical file), registered on every front-end request
     * regardless of whether this shortcode is actually used on the page.
     *
     * That "always registered" part matters: it's what lets
     * register_inline_assets() run inside the `wp_enqueue_scripts` action,
     * which fires before WordPress prints `<head>` styles — so
     * wp_add_inline_style() below always has a registered handle to attach
     * to, and the output lands in the normal enqueue pipeline instead of a
     * hand-rolled `<style>` tag inside the shortcode's returned HTML. The
     * behaviour (open/close/focus/triggers) is the script shared with the
     * slide-in, see EmbedTrigger.
     */
    private const HANDLE = 'quizably-quiz-popup';

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

        // In the footer: the script queries the embeds on load, so it must run
        // after their markup exists in the DOM (see EmbedTrigger::register_script()).
        EmbedTrigger::register_script();
    }

    /** @param array<string,mixed>|string $atts */
    public function render($atts = []): string
    {
        $atts = shortcode_atts(
            ['id' => '', 'label' => 'Take the quiz'] + EmbedTrigger::default_atts(),
            is_array($atts) ? $atts : [],
            self::TAG
        );

        $uuid    = sanitize_text_field((string) $atts['id']);
        $label   = sanitize_text_field((string) $atts['label']);
        $trigger = EmbedTrigger::parse($atts);

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

        $uuid_attr  = esc_attr((string) $quiz['uuid']);
        $nonce      = esc_attr(wp_create_nonce('wp_rest'));
        $api_root   = esc_attr(esc_url_raw(rest_url('quizably/v1/public/')));
        $label_html = esc_html($label);
        $label_attr = esc_attr($label);
        $dialog_id  = esc_attr(EmbedTrigger::next_id('popup'));

        $html = sprintf(
            '<div class="quizably-popup-embed" data-quizably-embed="popup" data-uuid="%1$s"%6$s>
  <button type="button" class="quizably-popup-trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="%7$s">%2$s</button>
  <div class="quizably-popup-overlay" hidden>
    <div class="quizably-popup-inner" id="%7$s" role="dialog" aria-modal="true" aria-label="%8$s" tabindex="-1">
      <button type="button" class="quizably-popup-close" aria-label="%3$s">&times;</button>
      <div class="quizably-quiz-root" data-quiz-uuid="%1$s" data-nonce="%4$s" data-api-root="%5$s"></div>
    </div>
  </div>
</div>',
            $uuid_attr,
            $label_html,
            esc_attr__('Close quiz', 'quizably'),
            $nonce,
            $api_root,
            EmbedTrigger::data_attributes($trigger),
            $dialog_id,
            $label_attr
        );

        // The player reads the quiz from this tag; without it the popup would open an empty quiz.
        $data = QuizEmbedData::data_script($quiz);
        return '' === $data ? $html : $html . "\n" . $data;
    }

    /** Raw CSS only — no wrapping <style> tag, this is handed to wp_add_inline_style(). */
    private function inline_styles(): string
    {
        return '
.quizably-popup-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:99999}
.quizably-popup-overlay[hidden]{display:none!important}
.quizably-popup-inner{position:relative;background:#fff;border-radius:12px;padding:0;max-width:640px;width:90%;max-height:90vh;overflow-y:auto}
.quizably-popup-inner:focus{outline:none}
.quizably-popup-close{position:absolute;top:12px;inset-inline-end:12px;background:none;border:none;font-size:24px;cursor:pointer;z-index:1;line-height:1}
.quizably-popup-trigger{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:var(--quizably-quiz-brand,#4F46E5);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer}
';
    }
}
