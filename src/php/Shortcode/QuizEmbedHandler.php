<?php
namespace Quizably\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Standalone iframe embed endpoint.
 *
 * Handles GET /?quizably_embed={quiz_id} and outputs a minimal bare-bones HTML page
 * that renders only the quiz — no WordPress header, footer, or admin chrome.
 * This URL is what the Summary tab puts into the <iframe src="..."> snippet so
 * authors can embed the quiz on any external site without needing a WordPress page.
 *
 * Optional URL params:
 *   max_width  — override the quiz card's max-width, e.g. ?max_width=100% or ?max_width=900px
 *   bg         — page background colour (hex without #, e.g. ?bg=f5f5f5)
 */
final class QuizEmbedHandler
{
    /**
     * Handle for this page's own reset/layout CSS. Its content is per-request
     * (the ?max_width=/?bg= overrides), so unlike the other shortcodes' HANDLE
     * constants this can't be registered blindly on every page load — it's
     * only relevant on an actual /?quizably_embed= request, and its data has to be
     * built from that request's query args. See maybe_render() for how it's
     * still guaranteed to be registered before wp_head() prints styles: the
     * hook is added synchronously, earlier in the same method, before the
     * manual wp_head() call below fires `wp_enqueue_scripts` at priority 1.
     */
    private const HANDLE = 'quizably-quiz-embed';

    public function register(): void
    {
        add_filter('query_vars', [$this, 'add_query_var']);
        add_action('template_redirect', [$this, 'maybe_render']);
    }

    public function add_query_var(array $vars): array
    {
        $vars[] = 'quizably_embed';
        return $vars;
    }

    public function maybe_render(): void
    {
        $raw_id = get_query_var('quizably_embed', '');
        if ('' === $raw_id) {
            return;
        }

        $quiz_id = absint($raw_id);
        if ($quiz_id <= 0) {
            status_header(400);
            exit;
        }

        // ── Optional URL overrides ────────────────────────────────────────────
        // max_width: CSS length that overrides the quiz card's max-width.
        //   Valid: 100%, 900px, 60em, 80vw, none
        $raw_max_width = isset($_GET['max_width']) ? sanitize_text_field(wp_unslash($_GET['max_width'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $max_width_css = '';
        if ('' !== $raw_max_width && preg_match('/^(\d+(\.\d+)?(px|em|rem|vw|ch|%)|none)$/i', $raw_max_width)) {
            $max_width_css = $raw_max_width;
        }

        // bg: page background colour (hex, 3 or 6 chars, no #).
        $raw_bg = isset($_GET['bg']) ? sanitize_text_field(wp_unslash($_GET['bg'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bg_css = '';
        if ('' !== $raw_bg && preg_match('/^[0-9a-fA-F]{3,8}$/', $raw_bg)) {
            $bg_css = '#' . $raw_bg;
        }

        // Register this request's reset/layout CSS through the normal enqueue
        // API instead of a literal <style> tag in the template below. Hooked
        // here (before wp_head() is called further down) so it's ready by the
        // time `wp_enqueue_scripts` fires at wp_head's priority 1 — same
        // guarantee the other shortcodes rely on, just built per-request
        // instead of registered unconditionally, since the CSS depends on
        // this request's ?bg=/?max_width= overrides.
        add_action('wp_enqueue_scripts', function () use ($bg_css, $max_width_css): void {
            wp_register_style(self::HANDLE, false, [], QUIZABLY_VERSION);
            wp_enqueue_style(self::HANDLE);
            wp_add_inline_style(self::HANDLE, $this->embed_styles($bg_css, $max_width_css));
        });

        // Render the quiz shortcode so all assets are enqueued normally.
        $content = do_shortcode('[quizably_quiz id="' . $quiz_id . '"]');

        status_header(200);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Frame-Options: ALLOWALL');

        ob_start();
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?php echo esc_html(get_bloginfo('name')); ?> — Quiz</title>
<?php wp_head(); ?>
</head>
<body>
<div class="quizably-embed-wrap">
<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already sanitised by do_shortcode / QuizShortcode::render() ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
        <?php
        echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Raw CSS only — no wrapping <style> tag, this is handed to
     * wp_add_inline_style(). $bg_css/$max_width_css are already validated
     * against strict whitelists in maybe_render() (hex colour / CSS length
     * patterns), so neither can contain `<`, `>`, `&`, or a stray `}` that
     * would let the value escape the property or rule it's placed in.
     */
    private function embed_styles(string $bg_css, string $max_width_css): string
    {
        $body_bg = '' !== $bg_css ? 'background:' . $bg_css . ';' : '';

        $max_width_rule = '' !== $max_width_css
            ? '.quizably-quiz{--quizably-card-max-width:' . $max_width_css . ' !important;}'
            : '';

        return '
/* ── Reset ───────────────────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;}
html,body{margin:0;padding:0;height:100%;}
body{display:flex;flex-direction:column;min-height:100vh;' . $body_bg . '}

/* ── Wrapper: full viewport, flex column so child fills height ─────── */
.quizably-embed-wrap{flex:1;display:flex;flex-direction:column;width:100%;}

/* ── Mount point: Vue renders .quizably-quiz inside here ───────────────── */
.quizably-quiz-root{flex:1;display:flex;flex-direction:column;width:100%;}

/*
 * .quizably-quiz is the Vue component root.
 * It must fill .quizably-quiz-root vertically (flex:1) and span full width.
 * Quiz.vue sets display/min-height/justify-content only when a background
 * image is active; for embed we always want the full-height flex layout
 * so templates are centred in the viewport regardless of background setting.
 */
.quizably-quiz{flex:1 !important;display:flex !important;flex-direction:column !important;width:100% !important;min-height:100vh;}

/* Content wrapper inside Quiz.vue — fill remaining height */
.quizably-quiz__content{flex:1;display:flex;flex-direction:column;}

/*
 * Templates centre their card horizontally.
 * --quizably-card-max-width is set by Quiz.vue from the admin Design settings.
 * ?max_width= URL param overrides it for quick iframe sizing.
 */
' . $max_width_rule;
    }
}
