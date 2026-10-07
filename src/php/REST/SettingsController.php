<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

use Quizably\Quiz\NewQuizDefaults;

final class SettingsController extends BaseController
{
    /** @var array<int,string> */
    private const ALLOWED_KEYS = [
        'default_template',
        'default_optin_placement',
        // Branding (flat keys — keep nested-JSON complexity out of the
        // controller; SettingsRepository stores scalars as-is). Applied to
        // new quizzes by Quizably\Quiz\NewQuizDefaults.
        'branding_primary_color',
        'branding_accent_color',
        // Defaults for new quizzes (NewQuizDefaults)
        'defaults_font_family',
        'defaults_button_radius',
        // GDPR (copied into a new quiz's form by NewQuizDefaults)
        'gdpr_default_consent_text',
        // Notifications (Notification\Mailer)
        'notifications_email_on_submission',
        'notifications_email_on_lead',
        'notifications_recipient',
        // Email (transactional, Notification\Mailer)
        'email_from_name',
        'email_from_address',
        'email_reply_to_lead',
        'email_template_subject',
        'email_template_body',
        // Integrations
        'default_webhook_url',
    ];

    /** @var array<int,string> */
    // 'start' / 'end' are the current values (form before / after the quiz).
    // 'gate' / 'optional' are the legacy names for the same two positions and
    // stay valid so an already-saved setting is never rejected on the next save.
    private const OPTIN_PLACEMENTS = NewQuizDefaults::FORM_POSITIONS;

    /**
     * Keys whose value should be coerced to a "0"/"1" string before storage —
     * keeps the on-disk shape consistent and lets the JS layer treat them as booleans without per-key parsing.
     *
     * @var array<int,string>
     */
    private const BOOL_KEYS = [
        'notifications_email_on_submission',
        'notifications_email_on_lead',
        'email_reply_to_lead',
    ];

    /**
     * Integration keys that may be configured via the integrations endpoint.
     * Values are stored as WP options `quizably_integration_{key}`.
     */
    private const INTEGRATION_KEYS = [
        'mailchimp',
        'convertkit',
        'activecampaign',
        'mailerlite',
        'brevo',
    ];

    public function register_routes(): void
    {
        register_rest_route(RestBootstrap::NAMESPACE, '/settings', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_all'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/settings/integrations', [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'save_integration_config'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route(RestBootstrap::NAMESPACE, '/settings/integrations/(?P<key>[a-z0-9_]+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_integration_config'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
    }

    public function get_all(\WP_REST_Request $req)
    {
        $repos = $this->repos();
        return $this->ok($repos['settings']->all());
    }

    public function update(\WP_REST_Request $req)
    {
        $repos  = $this->repos();
        $params = $req->get_json_params();
        if ( ! is_array($params) || ! $params ) {
            // Fallback to regular params if JSON body is absent.
            $params = [];
            foreach (self::ALLOWED_KEYS as $k) {
                $v = $req->get_param($k);
                if ($v !== null) {
                    $params[$k] = $v;
                }
            }
        }

        if ( ! $params ) {
            return $this->error('quizably_bad_request', __('No settings provided', 'quizably'), 400);
        }

        // Reject unknown keys.
        $unknown = array_diff(array_keys($params), self::ALLOWED_KEYS);
        if ($unknown) {
            return $this->error(
                'quizably_bad_request',
                sprintf(
                    /* translators: 1: unknown keys, 2: allowed keys */
                    __('Unknown keys: %1$s. Allowed: %2$s', 'quizably'),
                    implode(', ', $unknown),
                    implode(', ', self::ALLOWED_KEYS)
                ),
                400
            );
        }

        foreach ($params as $key => $value) {
            // Booleans first — these win over the per-name switch below
            // so a single allowlist edit (BOOL_KEYS) is enough to add a flag.
            if (in_array($key, self::BOOL_KEYS, true)) {
                $repos['settings']->set($key, $this->normalize_bool($value) ? '1' : '0');
                continue;
            }

            switch ($key) {
                case 'default_template':
                    $repos['settings']->set($key, (string) $value);
                    break;
                case 'default_optin_placement':
                    if ( ! in_array((string) $value, self::OPTIN_PLACEMENTS, true) ) {
                        return $this->error(
                            'quizably_bad_request',
                            sprintf(
                                /* translators: %s: allowed form placement values */
                                __('default_optin_placement must be one of: %s', 'quizably'),
                                implode(', ', self::OPTIN_PLACEMENTS)
                            ),
                            400
                        );
                    }
                    $repos['settings']->set($key, (string) $value);
                    break;
                case 'branding_primary_color':
                case 'branding_accent_color':
                    // Empty clears it; otherwise a #rrggbb hex (what the colour picker sends).
                    $hex = trim((string) $value);
                    if ($hex !== '' && 1 !== preg_match('/^#[0-9a-f]{6}$/i', $hex)) {
                        return $this->error(
                            'quizably_bad_request',
                            sprintf(
                                /* translators: %s: setting key */
                                __('%s must be a hex colour such as #4F46E5', 'quizably'),
                                $key
                            ),
                            400
                        );
                    }
                    $repos['settings']->set($key, $hex);
                    break;
                case 'defaults_font_family':
                    $font = strtolower(trim((string) $value));
                    if ($font !== '' && ! in_array($font, NewQuizDefaults::FONTS, true)) {
                        return $this->error(
                            'quizably_bad_request',
                            sprintf(
                                /* translators: %s: allowed font values */
                                __('defaults_font_family must be one of: %s', 'quizably'),
                                implode(', ', NewQuizDefaults::FONTS)
                            ),
                            400
                        );
                    }
                    $repos['settings']->set($key, $font);
                    break;
                case 'defaults_button_radius':
                    // Stores the builder's button shape. Older values ('0', '10', 'pill', ...)
                    // are accepted and normalised so a stale tab can never be rejected.
                    $style = NewQuizDefaults::button_style($value);
                    if ($style === '' && trim((string) $value) !== '') {
                        return $this->error(
                            'quizably_bad_request',
                            sprintf(
                                /* translators: %s: allowed button styles */
                                __('defaults_button_radius must be one of: %s', 'quizably'),
                                implode(', ', NewQuizDefaults::BUTTON_STYLES)
                            ),
                            400
                        );
                    }
                    $repos['settings']->set($key, $style);
                    break;
                case 'notifications_recipient':
                case 'email_from_address':
                    $email = trim((string) $value);
                    if ($email !== '' && ! is_email($email)) {
                        return $this->error(
                            'quizably_bad_request',
                            sprintf(
                                /* translators: %s: setting key */
                                __('%s must be a valid email address', 'quizably'),
                                $key
                            ),
                            400
                        );
                    }
                    $repos['settings']->set($key, sanitize_email($email));
                    break;
                case 'email_from_name':
                case 'email_template_subject':
                    // Plain scalar string settings — sanitized as text.
                    $repos['settings']->set($key, sanitize_text_field((string) $value));
                    break;
                case 'default_webhook_url':
                    // Empty clears the default. Anything else must be an http(s) URL.
                    $url = trim((string) $value);
                    if ($url !== '') {
                        // esc_url_raw() would turn "not a url" into "http://notaurl", so check the
                        // shape first and only then sanitize.
                        if (false === filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
                            return $this->error(
                                'quizably_bad_request',
                                __('default_webhook_url must be a valid http or https URL', 'quizably'),
                                400
                            );
                        }
                        $url = esc_url_raw($url, ['http', 'https']);
                    }
                    $repos['settings']->set($key, $url);
                    break;
                case 'gdpr_default_consent_text':
                    // Multi-line plain text: newlines kept, tags stripped.
                    $repos['settings']->set($key, sanitize_textarea_field((string) $value));
                    break;
                case 'email_template_body':
                    // Written in a rich-text editor and sent as HTML, so keep an
                    // email-safe subset of formatting instead of stripping every
                    // tag. A body with no HTML is treated as plain text.
                    $repos['settings']->set($key, \Quizably\Notification\EmailTemplate::sanitize_body((string) $value));
                    break;
            }
        }

        return $this->ok($repos['settings']->all());
    }

    /**
     * PUT /quizably/v1/settings/integrations
     * Body: { "key": "mailchimp", "config": { "api_key": "...", ... } }
     * Saves the config as WP option `quizably_integration_{key}`.
     */
    public function save_integration_config(\WP_REST_Request $req)
    {
        $params = $req->get_json_params();
        $key    = isset($params['key']) ? sanitize_key((string) $params['key']) : '';
        $config = isset($params['config']) && is_array($params['config']) ? $params['config'] : null;

        if ( ! $key ) {
            return $this->error('quizably_bad_request', __('key is required', 'quizably'), 400);
        }

        if ( ! in_array($key, self::INTEGRATION_KEYS, true) ) {
            return $this->error(
                'quizably_bad_request',
                sprintf(
                    /* translators: %s: integration key */
                    __('Unknown integration key: %s', 'quizably'),
                    $key
                ),
                400
            );
        }

        if ( null === $config ) {
            return $this->error('quizably_bad_request', __('config must be an object', 'quizably'), 400);
        }

        // Sanitize config values as plain text strings.
        $sanitized = [];
        foreach ($config as $field => $value) {
            $sanitized[ sanitize_key($field) ] = sanitize_text_field((string) $value);
        }

        update_option('quizably_integration_' . $key, $sanitized, false);

        return $this->ok(['saved' => true]);
    }

    /**
     * GET /quizably/v1/settings/integrations/{key}
     * Returns the saved config for the given integration key or an empty object.
     */
    public function get_integration_config(\WP_REST_Request $req)
    {
        $key = sanitize_key((string) $req->get_param('key'));

        if ( ! in_array($key, self::INTEGRATION_KEYS, true) ) {
            return $this->error(
                'quizably_bad_request',
                sprintf(
                    /* translators: %s: integration key */
                    __('Unknown integration key: %s', 'quizably'),
                    $key
                ),
                400
            );
        }

        $config = get_option('quizably_integration_' . $key, []);
        if ( ! is_array($config) ) {
            $config = [];
        }

        return $this->ok($this->mask_sensitive_config($config));
    }

    /**
     * Coerce wire-format booleans (real bool, "1"/"0" string, "true"/"false")
     * down to a clean PHP bool. JS clients send native booleans through the
     * REST API; the legacy admin form posts strings — handle both.
     */
    private function normalize_bool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value !== 0;
        }
        if (is_string($value)) {
            $v = strtolower(trim($value));
            return ! in_array($v, ['', '0', 'false', 'no', 'off'], true);
        }
        return (bool) $value;
    }

    /**
     * Mask sensitive credential fields in integration config before returning
     * to the client. Shows only the last 4 characters of API keys/secrets so
     * the admin can confirm a key is set without the full value being exposed
     * in browser history or logs.
     *
     * The save endpoint still writes/reads the full value — only the GET
     * response is masked. When the admin saves a new key, the frontend sends
     * the full value in the PUT body (never reads back from this GET endpoint).
     */
    private function mask_sensitive_config(array $config): array
    {
        $sensitive_substrings = ['key', 'secret', 'token', 'password', 'pass'];
        $masked = [];

        foreach ($config as $field => $value) {
            $field_lower = strtolower((string) $field);
            $is_sensitive = false;
            foreach ($sensitive_substrings as $sub) {
                if (str_contains($field_lower, $sub)) {
                    $is_sensitive = true;
                    break;
                }
            }

            if ($is_sensitive && is_string($value) && $value !== '') {
                $len = strlen($value);
                $masked[$field] = $len > 4
                    ? str_repeat('*', $len - 4) . substr($value, -4)
                    : str_repeat('*', $len);
                $masked[$field . '_is_set'] = true;
            } else {
                $masked[$field] = $value;
            }
        }

        return $masked;
    }
}
