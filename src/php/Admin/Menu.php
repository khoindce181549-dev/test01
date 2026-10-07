<?php
namespace Quizably\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Top-level + submenu registration.
 *
 * Layout (in display order):
 *   - Quiz Builder (parent — clicking it lands on Dashboard)
 *     - Dashboard          (bare slug, SPA Router redirects '/' → '/dashboard')
 *     - All Quizzes        (#/quizzes)
 *     - Question Bank      (#/question-bank)
 *     - Leads              (#/leads)
 *     - Logic & Branching  (#/logic — Pro-tier; only listed when Pro is active
 *                           or Pro promotion is on, see submenus())
 *     - Integrations       (#/integrations)
 *     - Settings           (#/settings)
 *
 * WordPress auto-creates a submenu mirror of the parent slug; we remove
 * it because we register an explicit "Dashboard" entry on the bare slug
 * — otherwise the menu would show two top items pointing to the same URL.
 */
final class Menu
{
    public const CAP = 'manage_options';
    public const SLUG = 'quizably';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('current_screen', [$this, 'suppress_notices']);
    }

    /**
     * Remove all admin notice hooks when we're on any Quizably screen so third-party
     * and WP core notices don't bleed into the SPA chrome.
     */
    public function suppress_notices(\WP_Screen $screen): void
    {
        if ( false === strpos($screen->id, self::SLUG) ) {
            return;
        }
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');
    }

    public function register_menu(): void
    {
        $icon_path = QUIZABLY_PLUGIN_DIR . 'assets/icons/menu.svg';
        if ( file_exists($icon_path) ) {
            $icon = 'data:image/svg+xml;base64,' . base64_encode( (string) file_get_contents($icon_path) );
        } else {
            $icon = 'dashicons-forms';
        }

        add_menu_page(
            __('Quiz Builder', 'quizably'),
            __('Quiz Builder', 'quizably'),
            self::CAP,
            self::SLUG,
            [$this, 'render_app'],
            $icon,
            26
        );

        // First submenu uses the bare slug — clicking the parent menu lands
        // here. The Vue router redirects '/' → '/dashboard' so this becomes
        // the Dashboard.
        add_submenu_page(
            self::SLUG,
            __('Dashboard', 'quizably'),
            __('Dashboard', 'quizably'),
            self::CAP,
            self::SLUG,
            [$this, 'render_app']
        );

        foreach ($this->submenus() as $hash => $label) {
            add_submenu_page(
                self::SLUG,
                $label,
                $label,
                self::CAP,
                self::SLUG . '#/' . $hash,
                [$this, 'render_app']
            );
        }
    }

    public function render_app(): void
    {
        // Passed to the view so it can show a build notice instead of an
        // infinite "Loading…" placeholder when assets/dist/ doesn't exist
        // (fresh git checkout, `npm run build` not run yet). See Assets::is_built().
        $quizably_built = Assets::is_built(QUIZABLY_PLUGIN_DIR);
        require QUIZABLY_PLUGIN_DIR . 'src/php/Admin/views/app-root.php';
    }

    /**
     * Hash → label map for non-default submenus. Order here is the order
     * WordPress will render them in the admin nav.
     *
     * @return array<string,string>
     */
    private function submenus(): array
    {
        $menu = [
            'quizzes'       => __('All Quizzes', 'quizably'),
            'question-bank' => __('Question Bank', 'quizably'),
            'leads'         => __('Leads', 'quizably'),
            'logic'         => __('Logic & Branching', 'quizably'),
            'integrations'  => __('Integrations', 'quizably'),
            'settings'      => __('Settings', 'quizably'),
        ];

        // Logic & Branching is a Pro-tier page. Without Pro (and with Pro
        // promotion off) the SPA redirects #/logic to the dashboard, so listing
        // it here would be a menu item that goes nowhere. Same rule as the
        // SPA's route guard (proFeaturesVisible() in src/admin/api/pro.js).
        $logic_visible = \Quizably\Pro\Gate::is_pro()
            || (bool) apply_filters('quizably/show_pro_promo', false);
        if ( ! $logic_visible ) {
            unset($menu['logic']);
        }

        return $menu;
    }
}
