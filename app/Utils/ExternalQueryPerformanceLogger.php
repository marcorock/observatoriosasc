<?php

namespace App\Utils;

final class ExternalQueryPerformanceLogger
{
    private const PREFIX = '[external-query-performance] ';

    public static function record(array $data): void
    {
        if (!self::enabled()) {
            return;
        }

        try {
            $payload = array_merge([
                'timestamp' => date(DATE_ATOM),
                'endpoint' => self::endpoint(),
            ], $data);

            $json = json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );

            if (is_string($json)) {
                error_log(self::PREFIX . $json);
            }
        } catch (\Throwable $e) {
            // Performance logging must never interrupt an application request.
        }
    }

    public static function enabled(): bool
    {
        $value = $_ENV['EXTERNAL_QUERY_PERFORMANCE_LOG'] ?? getenv('EXTERNAL_QUERY_PERFORMANCE_LOG');

        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private static function endpoint(): string
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? 'cli');
        $path = parse_url($requestUri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : 'cli';
    }
}
