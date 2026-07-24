<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PpaQueryCacheSynchronizer;
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

$directory = sys_get_temp_dir() . '/ppa-query-sync-test-' . bin2hex(random_bytes(8));
$cache = new PpaQueryFileCache($directory);
$indicator = (object) ['id' => 12, 'codigo_indicador' => 'PPA-TESTE'];
$baseLink = (object) [
    'external_query_id' => 7,
    'campo_resultado' => 'base_familias_teste',
];
$rmaLink = (object) [
    'external_query_id' => 8,
    'campo_resultado' => 'serie_mensal_unidade_teste',
];
$queries = [
    7 => (object) ['id' => 7, 'source_id' => 3, 'sql_query' => 'SELECT base FROM data'],
    8 => (object) ['id' => 8, 'source_id' => 3, 'sql_query' => 'SELECT month FROM data'],
];
$source = (object) ['id' => 3];
$limits = [];
$runner = static function (object $link, int $limit) use (&$limits, $queries, $source): array {
    $limits[] = $limit;

    return [
        'rows' => [['external_query_id' => $link->external_query_id]],
        'query' => $queries[$link->external_query_id],
        'source' => $source,
    ];
};

$result = (new PpaQueryCacheSynchronizer($cache, $runner(...)))
    ->synchronize($indicator, [$baseLink, $rmaLink]);

$assertSame(true, $result['success'] ?? null, 'synchronizes one indicator');
$assertSame(2, $result['entries_written'] ?? null, 'writes all active links');
$assertSame([500, 5000], $limits, 'uses the dashboard limits for base and series');
$assertSame(
    [['external_query_id' => 7]],
    $cache->read($source, $queries[7], 500)['rows'] ?? null,
    'stores base rows'
);
$assertSame(
    [['external_query_id' => 8]],
    $cache->read($source, $queries[8], 5000)['rows'] ?? null,
    'stores series rows'
);
$assertSame(
    ['base_familias_teste', 'serie_mensal_unidade_teste'],
    array_column($result['entries'] ?? [], 'result_key'),
    'reports written result keys'
);

$failedDirectory = sys_get_temp_dir() . '/ppa-query-sync-failure-test-' . bin2hex(random_bytes(8));
$failedCache = new PpaQueryFileCache($failedDirectory);
$calls = 0;
$failedRunner = static function (object $link, int $limit) use (&$calls, $queries, $source): array|string {
    $calls++;

    if ($calls === 2) {
        return 'Falha controlada na segunda consulta.';
    }

    return [
        'rows' => [['external_query_id' => $link->external_query_id]],
        'query' => $queries[$link->external_query_id],
        'source' => $source,
    ];
};
$failed = (new PpaQueryCacheSynchronizer($failedCache, $failedRunner(...)))
    ->synchronize($indicator, [$baseLink, $rmaLink]);

$assertSame(false, $failed['success'] ?? null, 'reports query failure');
$assertSame(
    'serie_mensal_unidade_teste',
    $failed['result_key'] ?? null,
    'reports the failed result key'
);
$assertSame(
    null,
    $failedCache->read($source, $queries[7], 500),
    'does not write entries before every query succeeds'
);

$empty = (new PpaQueryCacheSynchronizer($cache, $runner(...)))
    ->synchronize($indicator, []);
$assertSame(false, $empty['success'] ?? null, 'rejects an indicator without active links');

foreach ([$directory, $failedDirectory] as $path) {
    if (!is_dir($path)) {
        continue;
    }

    foreach (glob($path . '/*') ?: [] as $file) {
        unlink($file);
    }

    rmdir($path);
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (10 assertions)\n");
