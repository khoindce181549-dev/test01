<?php
namespace Quizably\Integration;

defined( 'ABSPATH' ) || exit;

interface IntegrationInterface
{
    public function key(): string;

    /**
     * @param array $payload  { quiz, submission_uuid, result, score, breakdown, lead (if form submitted), timestamp }
     * @param array $config   Per-quiz config from settings
     * @return array { status: 'sent'|'failed'|'retry', response: string|null, error: string|null }
     */
    public function dispatch(array $payload, array $config): array;
}
