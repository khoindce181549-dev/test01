<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

final class TemplateController extends BaseController
{
    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/templates', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'list'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    public function list(\WP_REST_Request $req)
    {
        $base_url = defined('QUIZABLY_PLUGIN_URL') ? (string) QUIZABLY_PLUGIN_URL : '';
        $list = [
            [
                'key'       => 'classic',
                'label'     => 'Classic',
                'thumbnail' => $base_url . 'assets/templates/classic.svg',
                'pro'       => false,
            ],
            [
                'key'       => 'minimal',
                'label'     => 'Minimal',
                'thumbnail' => $base_url . 'assets/templates/minimal.svg',
                'pro'       => false,
            ],
            // Free: Full Screen (dark, immersive) and Conversational (chat-style).
            // Both are self-contained and work for every quiz type. Keep this list
            // in step with PRESET_TEMPLATES in src/admin/data/presets.js (a test does).
            ['key' => 'fullscreen',     'label' => 'Full Screen',    'thumbnail' => $base_url . 'assets/templates/fullscreen.svg',     'pro' => false],
            ['key' => 'splitscreen',    'label' => 'Split Screen',   'thumbnail' => $base_url . 'assets/templates/splitscreen.svg',    'pro' => true],
            ['key' => 'cardstack',      'label' => 'Card Stack',     'thumbnail' => $base_url . 'assets/templates/cardstack.svg',      'pro' => true],
            ['key' => 'conversational', 'label' => 'Conversational', 'thumbnail' => $base_url . 'assets/templates/conversational.svg', 'pro' => false],
            ['key' => 'gamified',       'label' => 'Gamified',       'thumbnail' => $base_url . 'assets/templates/gamified.svg',       'pro' => true],
            ['key' => 'magazine',       'label' => 'Magazine',       'thumbnail' => $base_url . 'assets/templates/magazine.svg',       'pro' => true],
        ];

        /**
         * Filter the list of registered quiz templates.
         *
         * @param array<int,array<string,mixed>> $list
         */
        $list = apply_filters('quizably_templates', $list);

        return $this->ok(['items' => array_values($list)]);
    }
}
