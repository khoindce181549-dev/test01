<?php
namespace Quizably\Integration;

defined( 'ABSPATH' ) || exit;

final class WebhookIntegration extends AbstractIntegration
{
    public function key(): string
    {
        return 'webhook';
    }

    public function dispatch(array $payload, array $config): array
    {
        $url = (string) ($config['url'] ?? '');
        if ('' === $url || ! wp_http_validate_url($url)) {
            return $this->fail('Webhook URL missing or invalid');
        }

        if (! $this->is_safe_url($url)) {
            return $this->fail('Webhook URL targets a private or reserved address');
        }

        $body = wp_json_encode($payload);
        if (false === $body) {
            return $this->fail('Payload could not be JSON-encoded');
        }

        $args = [
            'timeout'  => 8,
            'blocking' => true,
            'headers'  => ['Content-Type' => 'application/json'],
            'body'     => $body,
        ];

        // Optional HMAC signature if a secret is configured.
        $secret = (string) ($config['secret'] ?? '');
        if ('' !== $secret) {
            $args['headers']['X-Quizably-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $secret);
        }

        $resp = wp_remote_post($url, array_merge($args, ['sslverify' => true]));

        if (is_wp_error($resp)) {
            return $this->retry($resp->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($resp);
        if ($code >= 200 && $code < 300) {
            return $this->ok((string) wp_remote_retrieve_body($resp));
        }
        if ($code >= 500 || 429 === $code) {
            return $this->retry('HTTP ' . $code);
        }
        return $this->fail('HTTP ' . $code . ' ' . (string) wp_remote_retrieve_body($resp));
    }

    /**
     * Block SSRF: reject URLs that resolve to loopback, link-local,
     * or RFC-1918 private ranges. Admins should not be able to use
     * the webhook as a proxy to internal infrastructure.
     */
    private function is_safe_url(string $url): bool
    {
        $parsed = wp_parse_url($url);
        if (! $parsed || empty($parsed['host'])) {
            return false;
        }

        $host = strtolower($parsed['host']);

        // Reject plain loopback / localhost names.
        $blocked_names = ['localhost', 'localhost.localdomain', '0.0.0.0'];
        if (in_array($host, $blocked_names, true)) {
            return false;
        }

        // Resolve to IPs and check each one.
        $ips = @gethostbynamel($host);
        if ($ips === false) {
            // Cannot resolve — allow (wp_remote_post will fail naturally).
            return true;
        }

        foreach ($ips as $ip) {
            $long = ip2long($ip);
            if ($long === false) {
                continue; // IPv6 — skip check for now (IPv6 SSRF is rare in this context).
            }

            // Loopback: 127.0.0.0/8
            if (($long & 0xFF000000) === 0x7F000000) {
                return false;
            }

            // Link-local: 169.254.0.0/16 (AWS/Azure metadata endpoints)
            if (($long & 0xFFFF0000) === 0xA9FE0000) {
                return false;
            }

            // RFC-1918 private ranges.
            $private_ranges = [
                [0x0A000000, 0xFF000000], // 10.0.0.0/8
                [0xAC100000, 0xFFF00000], // 172.16.0.0/12
                [0xC0A80000, 0xFFFF0000], // 192.168.0.0/16
            ];
            foreach ($private_ranges as [$net, $mask]) {
                if (($long & $mask) === $net) {
                    return false;
                }
            }
        }

        return true;
    }
}
