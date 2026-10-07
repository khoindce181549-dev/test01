<?php
namespace Quizably\REST;

use Quizably\Core\Plugin;

defined( 'ABSPATH' ) || exit;

abstract class BaseController
{
    public const CAPABILITY = 'quizably_manage_quizzes';

    abstract public function register_routes(): void;

    /** @param \WP_REST_Request $req */
    public function permission_check($req)
    {
        if ( ! current_user_can(self::CAPABILITY) ) {
            return new \WP_Error(
                'quizably_forbidden',
                __('Insufficient permissions.', 'quizably'),
                ['status' => 403]
            );
        }
        return true;
    }

    /** @return array<string,object> */
    protected function repos(): array
    {
        $services = Plugin::instance()->services();
        return isset($services['repos']) && is_array($services['repos']) ? $services['repos'] : [];
    }

    /**
     * @param mixed $data
     */
    protected function ok($data, int $status = 200): \WP_REST_Response
    {
        return new \WP_REST_Response($this->normalize_dates($data), $status);
    }

    /**
     * Repository timestamp columns are written as naive "Y-m-d H:i:s" strings
     * in UTC (current_time('mysql', true) never embeds an offset). Sent to
     * the browser unchanged, that string isn't valid ISO 8601 — `new Date()`
     * / `Date.parse()` treat a space-separated, offset-less datetime as
     * *local* time in every major browser, so every "created/updated X ago"
     * label silently drifts by the visitor's UTC offset (e.g. a quiz saved
     * seconds ago in IST reads "6h ago"). Rewriting every `*_at` value into
     * "Y-m-d\TH:i:s\Z" here — once, at the response boundary every
     * controller shares — makes the UTC-ness explicit so the client parses
     * it correctly, instead of patching each of the admin's relative-time
     * helpers to guess the server's intent.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function normalize_dates($value)
    {
        if ( ! is_array($value) ) {
            return $value;
        }
        $out = [];
        foreach ($value as $key => $v) {
            if (is_string($key) && '_at' === substr($key, -3) && is_string($v)
                && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $v)) {
                $out[$key] = str_replace(' ', 'T', $v) . 'Z';
            } else {
                $out[$key] = $this->normalize_dates($v);
            }
        }
        return $out;
    }

    protected function error(string $code, string $message, int $status): \WP_Error
    {
        return new \WP_Error($code, $message, ['status' => $status]);
    }

    protected function not_found(string $resource = 'Resource'): \WP_Error
    {
        return $this->error(
            'quizably_not_found',
            sprintf(
                /* translators: %s: resource name (e.g. Quiz, Question) */
                __('%s not found', 'quizably'),
                $resource
            ),
            404
        );
    }

    /**
     * Decode a JSON string column into a PHP array.
     * Returns null for null/empty values or invalid JSON.
     * If the value is already an array, it is returned as-is.
     *
     * @param mixed $value
     * @return array|null
     */
    protected function decode_json_string($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);
        if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded)) {
            return null;
        }
        return $decoded;
    }

    /**
     * Decode specific columns from JSON strings into arrays/objects. If a field
     * is missing or empty or not valid JSON, it is normalized to null.
     *
     * @param array<string,mixed> $row
     * @param array<int,string>   $fields
     * @return array<string,mixed>
     */
    protected function decode_json_fields(array $row, array $fields): array
    {
        foreach ($fields as $field) {
            if ( ! array_key_exists($field, $row) || null === $row[$field] || '' === $row[$field] ) {
                $row[$field] = null;
                continue;
            }
            $value = $row[$field];
            if (is_array($value)) {
                // Already decoded.
                continue;
            }
            $decoded = json_decode((string) $value, true);
            $row[$field] = (JSON_ERROR_NONE === json_last_error()) ? $decoded : null;
        }
        return $row;
    }
}
