<?php
namespace Quizably\Admin;

defined( 'ABSPATH' ) || exit;

final class Assets
{
    public const HANDLE = 'quizably-admin';

    /**
     * Handle for the "assets aren't built yet" fallback notice's CSS (see
     * app-root.php). Carried entirely as inline data via wp_add_inline_style()
     * — this fires precisely when self::HANDLE's real bundle is missing, so
     * it can't lean on that handle for its styling.
     */
    private const BUILD_NOTICE_HANDLE = 'quizably-admin-build-notice';

    /** Entry file whose presence means `npm run build` has been run. */
    private const ENTRY = 'assets/dist/admin.js';

    private ?ReviewPrompt $review_prompt;

    public function __construct(?ReviewPrompt $review_prompt = null)
    {
        $this->review_prompt = $review_prompt;
    }

    /**
     * Whether the Vite build output exists for a given plugin directory.
     *
     * `assets/dist/` is gitignored (build output, not source), so a fresh
     * `git clone` has no build until `npm install && npm run build` runs.
     * Shared by {@see enqueue()} (to decide whether to enqueue the bundle)
     * and the admin view (to decide whether to show a build notice instead
     * of an infinite "Loading…" placeholder) so the two can never disagree
     * about what "built" means.
     *
     * Takes the plugin dir as a parameter (rather than reading
     * `QUIZABLY_PLUGIN_DIR` directly) so it stays a pure, unit-testable check
     * against a real filesystem path instead of the live constant.
     */
    public static function is_built(string $plugin_dir): bool
    {
        return file_exists($plugin_dir . self::ENTRY);
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_filter('script_loader_tag', [$this, 'module_tag'], 10, 3);
    }

    /**
     * `script_loader_tag` filter callback: rewrites the tag WordPress already
     * generated for `self::HANDLE` (registered via wp_enqueue_script() in
     * enqueue() below) so it carries `type="module"`. Needed for
     * cross-version support down to the declared "Requires at least: 6.0" —
     * the dedicated Script Modules API (wp_enqueue_script_module()) only
     * exists from WP 6.5. This is the standard, WordPress-documented use of
     * this filter, not an unenqueued script.
     */
    public function module_tag(string $tag, string $handle, string $src): string
    {
        if (self::HANDLE === $handle) {
            return sprintf(
                '<script type="module" src="%s" id="%s-js"></script>' . "\n", // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
                esc_url($src),
                esc_attr(self::HANDLE)
            );
        }
        return $tag;
    }

    public function enqueue(string $hook): void
    {
        if ( ! $this->is_plugin_page($hook) ) {
            return;
        }

        if ( ! self::is_built(QUIZABLY_PLUGIN_DIR) ) {
            $this->enqueue_build_notice_styles();
            return;
        }
        $dist = QUIZABLY_PLUGIN_DIR . self::ENTRY;

        // Load wp.media (Backbone) so the MediaPicker Vue component can open
        // the WP media modal for image fields throughout the admin SPA.
        wp_enqueue_media();

        wp_enqueue_script(
            self::HANDLE,
            QUIZABLY_PLUGIN_URL . 'assets/dist/admin.js',
            ['wp-i18n'],
            (string) filemtime($dist),
            true
        );

        // Load the site's translations for the SPA's JS strings (src/shared/i18n.js).
        wp_set_script_translations(self::HANDLE, 'quizably', QUIZABLY_PLUGIN_DIR . 'languages');

        $css = QUIZABLY_PLUGIN_DIR . 'assets/dist/styles.css';
        wp_enqueue_style(
            self::HANDLE,
            QUIZABLY_PLUGIN_URL . 'assets/dist/styles.css',
            [],
            file_exists($css) ? (string) filemtime($css) : QUIZABLY_VERSION
        );

        // Reset WP admin chrome on our pages: drop the default 20px left
        // padding on #wpcontent and the bottom padding on #wpbody-content
        // so our SPA controls full-bleed layout without negative-margin
        // hacks. Scoped to plugin pages because the enqueue itself is.
        //
        // WP puts that gutter on the leading edge, so it is the right edge
        // under RTL locales. `!important` is a justified exception: it must
        // beat WP core admin CSS (`.auto-fold #wpcontent{padding-left:20px}`,
        // and its rtl.css counterpart), which we do not control.
        $content_gutter = is_rtl() ? 'padding-right' : 'padding-left';
        wp_add_inline_style(
            self::HANDLE,
            '#wpcontent{' . $content_gutter . ':0!important;}'
            . '#wpbody-content{padding-bottom:0!important;}'
            . '#wpfooter{display:none;}'
        );

        wp_localize_script(self::HANDLE, 'QUIZABLY_ADMIN', [
            'apiRoot'    => esc_url_raw( rest_url('quizably/v1/') ),
            'nonce'      => wp_create_nonce('wp_rest'),
            'pluginUrl'  => QUIZABLY_PLUGIN_URL,
            'version'    => QUIZABLY_VERSION,
            'homeUrl'    => esc_url_raw( home_url('/') ),
            // True when the Pro plugin is active and licensed. Pro hooks
            // into `quizably/is_pro` and returns true. Free always sees false.
            'isPro'      => \Quizably\Pro\Gate::is_pro(),
            // Whether the free plugin may *mention* Pro at all: badges, locked
            // toggles, "Upgrade" prompts, teaser tiles, the license screen.
            // OFF by default — there is no paid product or site to point at
            // yet, so a "Pro" label would only confuse people. Flip the default
            // to true (or `add_filter('quizably/show_pro_promo', '__return_true')`
            // from the Pro add-on) once Pro is published. Has no effect on an
            // active Pro install, which always shows its real features.
            'proPromo'   => (bool) apply_filters('quizably/show_pro_promo', false),
            // Review request for the dashboard, or null when it shouldn't show
            // (see ReviewPrompt for the rules).
            'reviewPrompt' => $this->review_prompt ? $this->review_prompt->for_user(get_current_user_id()) : null,
        ]);
    }

    private function is_plugin_page(string $hook): bool
    {
        return strpos($hook, Menu::SLUG) !== false;
    }

    private function enqueue_build_notice_styles(): void
    {
        wp_register_style(self::BUILD_NOTICE_HANDLE, false, [], QUIZABLY_VERSION);
        wp_enqueue_style(self::BUILD_NOTICE_HANDLE);
        wp_add_inline_style(
            self::BUILD_NOTICE_HANDLE,
            '.quizably-build-notice{max-width:640px;margin:48px auto;padding:32px 36px;background:#FFFFFF;'
            . 'border:1px solid #EAE8E1;border-radius:14px;'
            . 'box-shadow:0 4px 6px -1px rgba(10,10,11,.06),0 2px 4px -2px rgba(10,10,11,.04);'
            . 'font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;'
            . 'color:#2A2A2E;line-height:1.55}'
            . '.quizably-build-notice h2{margin:0 0 12px;font-size:18px;font-weight:600;color:#0A0A0B}'
            . '.quizably-build-notice p{margin:0 0 12px}'
            . '.quizably-build-notice pre{margin:0 0 16px;padding:14px 16px;background:#0A0A0B;color:#F4F3EE;'
            . 'border-radius:10px;font-family:ui-monospace,"SF Mono",Menlo,monospace;font-size:13px;overflow-x:auto}'
            . '.quizably-build-notice .quizably-build-notice__hint{color:#6B6B70;font-size:13px}'
        );
    }
}
