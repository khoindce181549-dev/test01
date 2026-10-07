<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

final class IntegrationController extends BaseController
{
    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/integrations', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'list'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    public function list(\WP_REST_Request $req)
    {
        $repos    = $this->repos();
        $settings = isset($repos['settings']) ? $repos['settings']->all() : [];

        // Determine webhook configured flag: any setting key starting with
        // `webhook_url_` (or the default_webhook_url) is non-empty.
        $webhook_configured = false;
        foreach ($settings as $k => $v) {
            if ($k === 'default_webhook_url' && is_string($v) && $v !== '') {
                $webhook_configured = true;
                break;
            }
            if (strpos((string) $k, 'webhook_url_') === 0 && ! empty($v)) {
                $webhook_configured = true;
                break;
            }
        }

        $list = [
            [
                'key'        => 'webhook',
                'label'      => 'Webhook',
                'pro'        => false,
                'configured' => $webhook_configured,
            ],
            ['key' => 'mailchimp',      'label' => 'Mailchimp',      'pro' => true, 'configured' => $this->integration_configured('mailchimp')],
            ['key' => 'convertkit',     'label' => 'ConvertKit',     'pro' => true, 'configured' => $this->integration_configured('convertkit')],
            ['key' => 'activecampaign', 'label' => 'ActiveCampaign', 'pro' => true, 'configured' => $this->integration_configured('activecampaign')],
            ['key' => 'mailerlite',     'label' => 'MailerLite',     'pro' => true, 'configured' => $this->integration_configured('mailerlite')],
            ['key' => 'brevo',          'label' => 'Brevo',          'pro' => true, 'configured' => $this->integration_configured('brevo')],
        ];

        /**
         * Filter the list of registered integrations.
         *
         * @param array<int,array<string,mixed>> $list
         */
        $list = apply_filters('quizably_integrations', $list);

        return $this->ok(['items' => array_values($list)]);
    }

    /**
     * An integration is "configured" when its stored option (quizably_integration_{key})
     * is a non-empty array with at least one non-empty scalar field — i.e. the admin
     * has saved API credentials for it at least once.
     */
    private function integration_configured(string $key): bool
    {
        $config = get_option('quizably_integration_' . $key, []);
        if ( ! is_array($config) || empty($config) ) {
            return false;
        }
        foreach ($config as $v) {
            if ( is_string($v) && $v !== '' ) {
                return true;
            }
        }
        return false;
    }
}
