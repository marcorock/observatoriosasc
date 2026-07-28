#!/usr/bin/env php
<?php

use App\Models\PpaIndicatorModel;
use App\Services\PpaScheduledSynchronizationBatchService;
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

if ($target === '-h' || $target === '--help') {
    fwrite(STDOUT, "Uso: php bin/ppa-scheduled-sync.php --all\n");
    fwrite(STDOUT, "Sincroniza sequencialmente os indicadores publicos elegiveis do PPA.\n");
    exit(0);
}

if ($arguments !== ['--all']) {
    fwrite(STDERR, "Informe somente a opcao --all.\n");
    exit(1);
}

$lockPath = trim((string) ($_ENV['PPA_SCHEDULED_SYNC_LOCK'] ?? ''));
$lockPath = $lockPath !== ''
    ? $lockPath
    : sys_get_temp_dir() . '/observatoriosasc-ppa-scheduled-sync.lock';
$lock = @fopen($lockPath, 'c');

if ($lock === false) {
    fwrite(STDERR, json_encode([
        'success' => false,
        'error' => 'Nao foi possivel criar a trava da sincronizacao programada.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(1);
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fclose($lock);
    fwrite(STDOUT, json_encode([
        'success' => true,
        'already_running' => true,
        'message' => 'Uma sincronizacao programada do PPA ja esta em andamento.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(0);
}

$startedClock = microtime(true);
$startedAt = new DateTimeImmutable();

try {
    $indicators = (new PpaIndicatorModel())->readPublicCatalog();
    $result = is_string($indicators)
        ? ['success' => false, 'error' => $indicators]
        : (new PpaScheduledSynchronizationBatchService())->synchronize($indicators);
} catch (Throwable) {
    $result = [
        'success' => false,
        'error' => 'Falha inesperada ao iniciar a sincronizacao programada do PPA.',
    ];
}

$result['execution_type'] = 'automatico';
$result['started_at'] = $startedAt->format(DATE_ATOM);
$result['finished_at'] = (new DateTimeImmutable())->format(DATE_ATOM);
$result['execution_time_ms'] = round((microtime(true) - $startedClock) * 1000, 3);

flock($lock, LOCK_UN);
fclose($lock);

$json = json_encode(
    $result,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);
fwrite(($result['success'] ?? false) ? STDOUT : STDERR, $json . PHP_EOL);

exit(($result['success'] ?? false) ? 0 : 1);
