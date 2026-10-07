<?php
namespace Quizably\Frontend;

defined( 'ABSPATH' ) || exit;

final class Assets
{
    public const HANDLE = 'quizably-frontend';

    private bool $enqueued = false;

    public function register(): void
    {
        // Registered (not enqueued) so shortcode can trigger enqueue on demand.
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_filter('script_loader_tag', [$this, 'module_tag'], 10, 3);
    }

    public function module_tag(string $tag, string $handle, string $src): string
    {
        if (self::HANDLE === $handle) {
            return sprintf(
                '<script type="module" src="%s" id="%s-js"></script>' . "\n", // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- rewrites the tag of a script already enqueued via wp_enqueue_script() in ensure_enqueued(); see Admin\Assets::module_tag()
                esc_url($src),
                esc_attr(self::HANDLE)
            );
        }
        return $tag;
    }

    private bool $registered = false;

    public function register_assets(): void
    {
        // Idempotent: on most pages this runs once, early, off the
        // `wp_enqueue_scripts` hook (see register()). But QuizEmbedHandler's
        // bare iframe page calls ensure_enqueued() (below) *before* that hook
        // has fired — via QuizShortcode::render(), from inside do_shortcode(),
        // ahead of its own manual wp_head() call — so ensure_enqueued() also
        // calls this directly to guarantee registration exists before it
        // touches wp_enqueue_style()/wp_localize_script(). Both call sites can
        // land here for the same request; re-registering would replace the
        // _WP_Dependency object and silently drop any inline data or
        // localized script data already attached to the earlier one, so this
        // guard makes the second call a no-op instead.
        if ( $this->registered ) {
            return;
        }

        $dist = QUIZABLY_PLUGIN_DIR . 'assets/dist/frontend.js';
        if ( ! file_exists($dist) ) {
            return;
        }

        // filemtime(), not QUIZABLY_VERSION: a static version string never
        // changes between code fixes shipped under the same plugin version,
        // so a visitor's browser (or an intermediate cache/CDN) that already
        // fetched frontend.js/styles.css once would keep serving the stale
        // copy forever — silently hiding any CSS/JS fix from anyone who'd
        // already loaded a quiz. Matches Admin\Assets::enqueue()'s cache-busting.
        wp_register_script(
            self::HANDLE,
            QUIZABLY_PLUGIN_URL . 'assets/dist/frontend.js',
            ['wp-i18n'],
            (string) filemtime($dist),
            true
        );

        // Translations for the player's JS strings (src/shared/i18n.js).
        wp_set_script_translations(self::HANDLE, 'quizably', QUIZABLY_PLUGIN_DIR . 'languages');

        $css = QUIZABLY_PLUGIN_DIR . 'assets/dist/styles.css';
        wp_register_style(
            self::HANDLE,
            QUIZABLY_PLUGIN_URL . 'assets/dist/styles.css',
            [],
            file_exists($css) ? (string) filemtime($css) : QUIZABLY_VERSION
        );

        $this->registered = true;
    }

    public function ensure_enqueued(): void
    {
        if ( $this->enqueued ) {
            return;
        }

        // Guarantee registration regardless of whether the `wp_enqueue_scripts`
        // hook has fired yet for this request — see the comment in
        // register_assets().
        $this->register_assets();

        wp_enqueue_script(self::HANDLE);
        wp_enqueue_style(self::HANDLE);

        wp_localize_script(self::HANDLE, 'QUIZABLY_FRONTEND', [
            'apiRoot'   => esc_url_raw( rest_url('quizably/v1/public/') ),
            'pluginUrl' => QUIZABLY_PLUGIN_URL,
            'version'   => QUIZABLY_VERSION,
        ]);

        $this->enqueued = true;
    }
}
