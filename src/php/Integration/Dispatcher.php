<?php
namespace Quizably\Integration;

use Quizably\Core\Plugin;

defined( 'ABSPATH' ) || exit;

final class Dispatcher
{
    public const RETRY_ACTION   = 'quizably_integration_retry';
    /**
     * WP-Cron hook for the first delivery attempt. Delivery is deferred so the visitor's
     * quiz-completion / lead-capture REST request never waits on a (up to 8 s) outbound HTTP call.
     * Args: [ $key, $payload, $config ] — the same shape as RETRY_ACTION, handled by run_retry().
     */
    public const DELIVER_ACTION = 'quizably_integration_deliver';
    public const MAX_RETRIES  = 3;

    public function register(): void
    {
        // Auto-register built-in integrations.
        Registry::register(new WebhookIntegration());

        /**
         * Fires so third-party code can register additional integrations with
         * the Registry before dispatch listeners are wired up.
         */
        do_action('quizably_register_integrations');

        add_action('quizably_submission_completed', [$this, 'on_completed'], 10, 4);
        add_action('quizably_lead_captured', [$this, 'on_lead_captured'], 10, 3);
        add_action(self::RETRY_ACTION, [$this, 'run_retry'], 10, 3);
        add_action(self::DELIVER_ACTION, [$this, 'run_retry'], 10, 3);
    }

    /**
     * @param array<string,mixed>      $quiz
     * @param array<string,mixed>|null $result
     * @param array<string,mixed>      $score
     */
    public function on_completed(string $uuid, array $quiz, $result, array $score): void
    {
        // Carry the lead too, when this visitor already gave their details earlier in the visit,
        // so one payload holds both the lead and the result. Consumers that want exactly one
        // record per visitor can act on event = "submission_completed" and ignore "lead_captured".
        $this->dispatch_all($uuid, $quiz, is_array($result) ? $result : null, $score, $this->lead_for_submission($uuid), 'submission_completed');
    }

    /**
     * The lead attached to a submission, or null when the visitor did not leave their details.
     *
     * @return array<string,mixed>|null
     */
    private function lead_for_submission(string $uuid): ?array
    {
        $repos = Plugin::instance()->services()['repos'] ?? [];
        if (empty($repos['submissions']) || empty($repos['leads'])) {
            return null;
        }
        $sub = $repos['submissions']->find_by_uuid($uuid);
        $lead_id = (int) ($sub['lead_id'] ?? 0);
        if ($lead_id < 1) {
            return null;
        }
        $lead = $repos['leads']->find($lead_id);
        return is_array($lead) ? $lead : null;
    }

    /**
     * @param array<string,mixed> $sub
     * @param mixed               $req
     */
    public function on_lead_captured(int $lead_id, array $sub, $req): void
    {
        $repos = Plugin::instance()->services()['repos'] ?? [];
        if ( ! $repos) {
            return;
        }

        $quiz = $repos['quizzes']->find((int) $sub['quiz_id']);
        if ( ! $quiz) {
            return;
        }

        $result = null;
        if ( ! empty($sub['result_id']) ) {
            $result = $repos['results']->find((int) $sub['result_id']);
        }

        $breakdown = null;
        if (isset($sub['result_breakdown']) && is_string($sub['result_breakdown']) && $sub['result_breakdown'] !== '') {
            $decoded = json_decode($sub['result_breakdown'], true);
            if (is_array($decoded)) {
                $breakdown = $decoded;
            }
        }

        $lead = $repos['leads']->find($lead_id);

        $this->dispatch_all(
            (string) $sub['uuid'],
            $quiz,
            $result,
            [
                'score'     => $sub['score'] ?? null,
                'breakdown' => $breakdown,
                'result_id' => $sub['result_id'] ?? null,
            ],
            is_array($lead) ? $lead : null,
            'lead_captured'
        );
    }

    /**
     * @param array<string,mixed>      $quiz
     * @param array<string,mixed>|null $result
     * @param array<string,mixed>      $score
     * @param array<string,mixed>|null $lead
     */
    private function dispatch_all(string $submission_uuid, array $quiz, ?array $result, array $score, ?array $lead, string $event): void
    {
        $settings = isset($quiz['settings']) && is_string($quiz['settings']) && $quiz['settings'] !== ''
            ? json_decode($quiz['settings'], true)
            : ($quiz['settings'] ?? []);
        if ( ! is_array($settings)) {
            $settings = [];
        }
        $perQuiz = isset($settings['integrations']) && is_array($settings['integrations'])
            ? $settings['integrations']
            : [];

        $payload = [
            // "submission_completed" (scored, carries the lead if one was given) or "lead_captured"
            // (the lead form was submitted, possibly before the quiz was finished).
            'event'           => $event,
            'quiz_uuid'       => $quiz['uuid'] ?? null,
            'quiz_title'      => $quiz['title'] ?? null,
            'quiz_type'       => $quiz['type'] ?? null,
            'submission_uuid' => $submission_uuid,
            'result'          => $result ? [
                'id'    => isset($result['id']) ? (int) $result['id'] : null,
                'title' => $result['title'] ?? null,
            ] : null,
            'score'           => $score['score'] ?? null,
            'breakdown'       => $score['breakdown'] ?? null,
            'lead'            => $lead ? [
                'id'    => isset($lead['id']) ? (int) $lead['id'] : null,
                'email' => $lead['email'] ?? null,
                'name'  => $lead['name'] ?? null,
                'phone' => $lead['phone'] ?? null,
                // null: the quiz does not use double opt-in; false: waiting for the click; true: confirmed.
                'double_optin_verified' => isset($lead['double_optin_verified']) && '' !== $lead['double_optin_verified']
                    ? 1 === (int) $lead['double_optin_verified']
                    : null,
            ] : null,
            'timestamp'       => gmdate('c'),
        ];

        /**
         * Filter the payload shipped to integrations.
         *
         * @param array<string,mixed> $payload
         * @param array<string,mixed> $quiz
         */
        $payload = apply_filters('quizably_integration_payload', $payload, $quiz);

        foreach (Registry::all() as $key => $integration) {
            // Multi-webhook array path — Pro only.
            if ('webhook' === $key && apply_filters('quizably/is_pro', false)) {
                $webhooks = isset($perQuiz['webhooks']) && is_array($perQuiz['webhooks'])
                    ? $perQuiz['webhooks']
                    : [];

                foreach ($webhooks as $wh) {
                    if ( ! is_array($wh) || empty($wh['enabled'])) {
                        continue;
                    }
                    $wh_id = isset($wh['id']) ? (string) $wh['id'] : 'wh_unknown';
                    $this->enqueue_one(
                        'webhook:' . $wh_id,
                        $integration,
                        $payload,
                        $wh,
                        $submission_uuid
                    );
                }
                continue; // Skip the single-webhook path when Pro is active.
            }

            // Single-webhook path — free tier (backwards-compat).
            $cfg = $perQuiz[$key] ?? null;
            if ('webhook' === $key) {
                $cfg = $this->apply_default_webhook(is_array($cfg) ? $cfg : null);
            }
            if ( ! is_array($cfg) || empty($cfg['enabled'])) {
                continue;
            }
            $this->enqueue_one($key, $integration, $payload, $cfg, $submission_uuid);
        }

        $this->kick_cron();
    }

    /**
     * Hand one delivery to WP-Cron instead of making the HTTP call inside the visitor's request.
     *
     * The event carries the full payload and config, so the retry/backoff chain in dispatch_one()
     * (1, 2 and 4 minutes) continues unchanged from the cron handler. Falls back to delivering inline
     * only when the event cannot be scheduled at all, so a delivery is never silently dropped.
     * Sites can force inline delivery with add_filter('quizably_integration_async', '__return_false').
     *
     * Note: the event args (payload + per-webhook config, including any signing secret) are stored in
     * the `cron` option until the event runs, exactly as retry events already were.
     *
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $config
     */
    private function enqueue_one(string $key, IntegrationInterface $integration, array $payload, array $config, string $submission_uuid): void
    {
        if ( ! function_exists('wp_schedule_single_event') || ! apply_filters('quizably_integration_async', true)) {
            $this->dispatch_one($key, $integration, $payload, $config, $submission_uuid, 0);
            return;
        }

        $scheduled = wp_schedule_single_event(
            time(),
            self::DELIVER_ACTION,
            [$key, $payload, $config + ['__attempt' => 0, '__sub_uuid' => $submission_uuid]]
        );
        if (true !== $scheduled) {
            $this->dispatch_one($key, $integration, $payload, $config, $submission_uuid, 0);
        }
    }

    /**
     * Ask WP-Cron to run due events now (non-blocking loopback) so deliveries go out promptly rather
     * than waiting for the next page view. A no-op when WP-Cron is disabled (a system cron then runs
     * wp-cron.php) or when WordPress already spawned cron this request.
     */
    private function kick_cron(): void
    {
        if (function_exists('spawn_cron') && apply_filters('quizably_integration_async', true)) {
            spawn_cron();
        }
    }

    /**
     * Apply the site-wide "Default webhook URL" (Integrations screen) to a quiz's webhook settings.
     *
     * A quiz with its own URL keeps it. A quiz that turned its webhook off on purpose stays off.
     * A quiz that never set one (no settings at all, or switched on with a blank URL) sends to the
     * default, when there is one.
     *
     * @param array<string,mixed>|null $cfg The quiz's own webhook settings, if any.
     * @return array<string,mixed>|null
     */
    private function apply_default_webhook(?array $cfg): ?array
    {
        $repos   = Plugin::instance()->services()['repos'] ?? [];
        $default = isset($repos['settings']) ? trim((string) $repos['settings']->get('default_webhook_url', '')) : '';
        if ('' === $default) {
            return $cfg;
        }

        if (null === $cfg) {
            return ['enabled' => true, 'url' => $default];
        }
        if (array_key_exists('enabled', $cfg) && empty($cfg['enabled'])) {
            return $cfg; // switched off on purpose for this quiz
        }
        if ('' === trim((string) ($cfg['url'] ?? ''))) {
            $cfg['enabled'] = true;
            $cfg['url']     = $default;
        }
        return $cfg;
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $config
     */
    private function dispatch_one(string $key, IntegrationInterface $integration, array $payload, array $config, string $submission_uuid, int $attempt): void
    {
        $res = $integration->dispatch($payload, $config);

        /**
         * Fires after each dispatch attempt — observers can persist sync state.
         *
         * @param string              $key
         * @param array<string,mixed> $res
         * @param array<string,mixed> $payload
         * @param int                 $attempt
         */
        do_action('quizably_integration_dispatched', $key, $res, $payload, $attempt);

        if ('retry' === ($res['status'] ?? '') && $attempt < self::MAX_RETRIES) {
            $delay = 60 * (2 ** $attempt);
            if (function_exists('wp_schedule_single_event')) {
                $retry_config = $config + [
                    '__attempt'  => $attempt + 1,
                    '__sub_uuid' => $submission_uuid,
                ];
                wp_schedule_single_event(
                    time() + $delay,
                    self::RETRY_ACTION,
                    [$key, $payload, $retry_config]
                );
            }
        }
    }

    /**
     * WP-Cron handler for retry attempts.
     *
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $config
     */
    public function run_retry(string $key, array $payload, array $config): void
    {
        $attempt  = (int) ($config['__attempt'] ?? 0);
        $sub_uuid = (string) ($config['__sub_uuid'] ?? '');
        unset($config['__attempt'], $config['__sub_uuid']);

        // Multi-webhook keys are stored as "webhook:<id>"; resolve back to the
        // base "webhook" key for the Registry lookup.
        $registry_key = (strpos($key, 'webhook:') === 0) ? 'webhook' : $key;

        $integration = Registry::get($registry_key);
        if ( ! $integration) {
            return;
        }
        $this->dispatch_one($key, $integration, $payload, $config, $sub_uuid, $attempt);
    }
}
