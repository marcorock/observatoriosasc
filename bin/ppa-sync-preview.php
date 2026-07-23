#!/usr/bin/env php
<?php

use App\Controllers\PpaController;
use App\Models\PpaResultModel;
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
$commit = in_array('--commit', $arguments, true);
$arguments = array_values(array_filter(
    $arguments,
    static fn (string $argument): bool => $argument !== '--commit'
));
$slug = trim((string) ($arguments[0] ?? ''));

if ($slug === '' || in_array($slug, ['-h', '--help'], true)) {
    fwrite(STDOUT, "Uso: php bin/ppa-sync-preview.php <slug-ou-codigo> [--commit]\n");
    fwrite(STDOUT, "Sem --commit, executa somente uma simulacao e nao grava dados.\n");
    exit($slug === '' ? 1 : 0);
}

$startedAt = microtime(true);
$preview = (new PpaController())->buildCatalogSyncPreview($slug);
$preview['execution_time_ms'] = round((microtime(true) - $startedAt) * 1000, 3);

if ($preview['success'] && $commit) {
    try {
        $preview['persistence'] = (new PpaResultModel())->storeValidatedPreview($preview);
        $preview['dry_run'] = false;
    } catch (\Throwable $e) {
        $preview['success'] = false;
        $preview['dry_run'] = false;
        $preview['error'] = $e->getMessage();
    }
}

fwrite(
    $preview['success'] ? STDOUT : STDERR,
    json_encode(
        $preview,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ) . PHP_EOL
);

exit($preview['success'] ? 0 : 1);
