<?php
namespace Quizably\Integration;

defined( 'ABSPATH' ) || exit;

final class Registry
{
    /** @var array<string, IntegrationInterface> */
    private static array $items = [];

    public static function register(IntegrationInterface $integration): void
    {
        self::$items[$integration->key()] = $integration;
    }

    public static function get(string $key): ?IntegrationInterface
    {
        return self::$items[$key] ?? null;
    }

    /** @return array<string, IntegrationInterface> */
    public static function all(): array
    {
        return self::$items;
    }

    public static function reset(): void
    {
        self::$items = [];
    }
}
