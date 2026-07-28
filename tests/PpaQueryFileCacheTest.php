<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PpaQueryFileCache;

$failures = [];
$assertSame = static function ($expected, $actual, string $label) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = sprintf(
            "%s\nExpected: %s\nActual:   %s",
            $label,
            var_export($expected, true),
            var_export($actual, true)
        );
    }
};
$assertTrue = static function ($actual, string $label) use (&$failures): void {
    if ($actual !== true) {
        $failures[] = "{$label}\nExpected: true\nActual:   " . var_export($actual, true);
    }
};

$directory = sys_get_temp_dir() . '/ppa-query-cache-test-' . bin2hex(random_bytes(8));
$source = (object) ['id' => 3];
$query = (object) [
    'id' => 7,
    'sql_query' => " SELECT id, total FROM source_data; \n",
];
$rows = [
    ['id' => 1, 'total' => 10],
    ['id' => 2, 'total' => 20],
];
$cache = new PpaQueryFileCache($directory);
$generatedAt = new DateTimeImmutable('2026-07-24T12:00:00-03:00');
$payload = $cache->write($source, $query, 500, $rows, $generatedAt);

$assertSame(1, $payload['schema_version'] ?? null, 'writes the schema version');
$assertSame(3, $payload['source_id'] ?? null, 'writes the source identity');
$assertSame(7, $payload['query_id'] ?? null, 'writes the query identity');
$assertSame(500, $payload['limit'] ?? null, 'writes the requested limit');
$assertSame(2, $payload['row_count'] ?? null, 'writes the row count');
$assertSame($rows, $payload['rows'] ?? null, 'writes the aggregated rows');
$assertSame(
    '2026-07-24T12:00:00-03:00',
    $payload['generated_at'] ?? null,
    'writes the generation timestamp'
);

$cached = $cache->read($source, $query, 500);
$assertSame($payload, $cached, 'reads a valid cache entry');
$assertSame(null, $cache->read($source, $query, 501), 'does not reuse a different limit');

$changedQuery = clone $query;
$changedQuery->sql_query = 'SELECT id, total FROM source_data WHERE active = 1';
$assertSame(null, $cache->read($source, $changedQuery, 500), 'does not reuse changed SQL');

$jsonFiles = glob($directory . '/*.json') ?: [];
$assertSame(1, count($jsonFiles), 'stores one JSON entry');
$assertTrue(is_file($jsonFiles[0] . '.lock'), 'creates a lock beside the entry');

file_put_contents($jsonFiles[0], '{"schema_version":1,"broken":true}');
$assertSame(null, $cache->read($source, $query, 500), 'rejects a corrupted envelope');

$cache->write($source, $query, 500, $rows, $generatedAt);
$writeFailed = false;

try {
    $cache->write($source, $query, 500, [['valid' => true], new stdClass()], $generatedAt);
} catch (RuntimeException) {
    $writeFailed = true;
}

$assertTrue($writeFailed, 'rejects invalid rows before replacing the entry');
$assertSame($rows, $cache->read($source, $query, 500)['rows'] ?? null, 'preserves the last valid entry');
$assertSame([], glob($directory . '/.ppa-cache-*') ?: [], 'does not leave temporary files');

$blockedDirectory = sys_get_temp_dir() . '/ppa-query-cache-blocked-' . bin2hex(random_bytes(8));
mkdir($blockedDirectory, 0555);
chmod($blockedDirectory, 0555);
$warnings = [];
$previousErrorHandler = set_error_handler(
    static function (int $severity, string $message) use (&$warnings): bool {
        $warnings[] = [$severity, $message];
        return true;
    }
);
$permissionMessage = null;

try {
    (new PpaQueryFileCache($blockedDirectory))->write($source, $query, 500, $rows, $generatedAt);
} catch (RuntimeException $exception) {
    $permissionMessage = $exception->getMessage();
} finally {
    restore_error_handler();
    chmod($blockedDirectory, 0755);
    rmdir($blockedDirectory);
}

$assertSame(
    'O diretorio de cache do PPA nao possui permissao de escrita.',
    $permissionMessage,
    'returns a controlled message when the cache directory is not writable'
);
$assertSame([], $warnings, 'does not expose a native PHP warning when cache permissions are invalid');

foreach (glob($directory . '/*') ?: [] as $file) {
    unlink($file);
}
rmdir($directory);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (18 assertions)\n");
