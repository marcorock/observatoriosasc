#!/usr/bin/env php
<?php

use App\Models\PpaIndicatorModel;
use App\Models\PpaIndicatorQueryModel;
use App\Services\PpaQueryCacheBatchSynchronizer;
use App\Services\PpaQueryCacheSynchronizer;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/ExternalDbCrypto.php';

Dotenv::createImmutable(dirname(__DIR__))->load();

foreach ([
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'observatoriosasc',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_CHARSET' => 'utf8mb4',
] as $constant => $default) {
    if (!defined($constant)) {
        define($constant, $_ENV[$constant] ?? $default);
    }
}

$arguments = array_slice($argv, 1);
$target = trim((string) ($arguments[0] ?? ''));

if ($target === '' || in_array($target, ['-h', '--help'], true)) {
    fwrite(STDOUT, "Uso: php bin/ppa-dashboard-cache.php <slug-ou-codigo|--all>\n");
    fwrite(STDOUT, "Atualiza um indicador ou todos os indicadores publicos, sequencialmente.\n");
    exit($target === '' ? 1 : 0);
}

if (count($arguments) !== 1 || (str_starts_with($target, '--') && $target !== '--all')) {
    fwrite(STDERR, "Informe um slug, um codigo de indicador ou a opcao --all.\n");
    exit(1);
}

$startedAt = microtime(true);
$indicatorModel = new PpaIndicatorModel();

if ($target === '--all') {
    $indicators = $indicatorModel->readPublicCatalog();

    $result = is_string($indicators)
        ? ['success' => false, 'error' => $indicators]
        : (new PpaQueryCacheBatchSynchronizer())->synchronize($indicators);
} else {
    $indicator = $indicatorModel->readPublicBySlug($target);

    if (is_string($indicator)) {
        $result = [
            'success' => false,
            'error' => $indicator,
        ];
    } else {
        $links = (new PpaIndicatorQueryModel())->readActiveLinksByIndicatorId((int) $indicator->id);

        if (is_string($links)) {
            $result = [
                'success' => false,
                'indicator_id' => (int) $indicator->id,
                'indicator_code' => (string) ($indicator->codigo_indicador ?? ''),
                'error' => $links,
            ];
        } else {
            $result = (new PpaQueryCacheSynchronizer())->synchronize($indicator, $links);
        }
    }
}

$result['execution_time_ms'] = round((microtime(true) - $startedAt) * 1000, 3);

fwrite(
    ($result['success'] ?? false) ? STDOUT : STDERR,
    json_encode(
        $result,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ) . PHP_EOL
);

exit(($result['success'] ?? false) ? 0 : 1);
