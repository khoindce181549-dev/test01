<?php
namespace Quizably\RateLimit;

defined( 'ABSPATH' ) || exit;

/**
 * Simple per-identifier rate limiter backed by WP transients.
 *
 * Uses two rolling windows (one minute and one hour) to smooth bursts
 * while capping overall usage. Identifiers are derived from hashed IPs
 * so nothing personally identifying is ever persisted.
 */
final class TokenBucket
{
    public const DEFAULT_LIMIT_PER_MINUTE = 60;
    public const DEFAULT_HOURLY_CAP = 300;

    public function check(string $identifier): bool
    {
        $perMinute = (int) apply_filters('quizably_rate_limit_per_minute', self::DEFAULT_LIMIT_PER_MINUTE);
        $hourly = (int) apply_filters('quizably_rate_limit_per_hour', self::DEFAULT_HOURLY_CAP);

        $keyMinute = 'quizably_rl_m_' . $identifier . '_' . floor(time() / 60);
        $keyHour = 'quizably_rl_h_' . $identifier . '_' . floor(time() / 3600);

        $mCount = (int) get_transient($keyMinute);
        $hCount = (int) get_transient($keyHour);

        if ($mCount >= $perMinute || $hCount >= $hourly) {
            return false;
        }

        set_transient($keyMinute, $mCount + 1, 65);
        set_transient($keyHour, $hCount + 1, 3700);

        return true;
    }

    public static function identifier_from_ip(): string
    {
        // Always use REMOTE_ADDR as the base — never trust client-supplied
        // X-Forwarded-For / CF-Connecting-IP headers directly, as they can be
        // spoofed to bypass rate limiting. If this site runs behind a trusted
        // reverse proxy that sets REMOTE_ADDR to the real client IP (the
        // correct infrastructure-level fix), this works automatically.
        //
        // Site owners who need proxy-aware IP detection can hook
        // 'quizably_rate_limit_ip' to supply the resolved real IP themselves,
        // e.g. after validating the proxy's source IP against a whitelist.
        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR']))
            : '0.0.0.0';
        $ip = (string) apply_filters('quizably_rate_limit_ip', $ip);
        $salt = function_exists('wp_salt') ? wp_salt() : '';
        return substr(hash('sha256', $ip . '|' . $salt), 0, 16);
    }
}
