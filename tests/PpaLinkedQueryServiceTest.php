<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ExternalDataSourceModel;
use App\Models\ExternalQueryModel;
use App\Services\PpaLinkedQueryService;
use App\Services\PpaQueryFileCache;

final class FakeExternalQueryModel extends ExternalQueryModel
{
    public function __construct(private object|string $result)
    {
    }

    public function readById(int $id): object|string
    {
        return $this->result;
    }
}

final class FakeExternalDataSourceModel extends ExternalDataSourceModel
{
    public function __construct(private object|string $result)
    {
    }

    public function readById(int $id): object|string
    {
        return $this->result;
    }
}

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

$link = (object) [
    'external_query_id' => 7,
    'indicador_id' => 12,
    'codigo_indicador' => 'PPA-TESTE',
];
$query = (object) ['id' => 7, 'source_id' => 3, 'sql_query' => 'SELECT 1'];
$source = (object) ['id' => 3, 'nome' => 'Fonte teste'];

$queryError = (new PpaLinkedQueryService(
    new FakeExternalQueryModel('Consulta externa nao encontrada.')
))->run($link, 500);
$assertSame('Consulta externa nao encontrada.', $queryError, 'preserves query lookup errors');

$sourceError = (new PpaLinkedQueryService(
    new FakeExternalQueryModel($query),
    new FakeExternalDataSourceModel('Conexao externa nao encontrada.')
))->run($link, 500);
$assertSame('Conexao externa nao encontrada.', $sourceError, 'preserves source lookup errors');

$captured = [];
$runner = static function (
    object $receivedSource,
    object $receivedQuery,
    int $limit,
    array $context
) use (&$captured): array {
    $captured = compact('receivedSource', 'receivedQuery', 'limit', 'context');

    return [
        'success' => true,
        'rows' => [['total' => 42]],
    ];
};
$success = (new PpaLinkedQueryService(
    new FakeExternalQueryModel($query),
    new FakeExternalDataSourceModel($source),
    $runner(...)
))->run($link, 5000);

$assertSame([['total' => 42]], $success['rows'] ?? null, 'returns query rows');
$assertSame($query, $success['query'] ?? null, 'returns query metadata');
$assertSame($source, $success['source'] ?? null, 'returns source metadata');
$assertSame(5000, $captured['limit'] ?? null, 'forwards the requested limit');
$assertSame(
    ['indicator_id' => 12, 'indicator_code' => 'PPA-TESTE'],
    $captured['context'] ?? null,
    'forwards performance context'
);
$assertSame(false, $success['cache']['hit'] ?? null, 'reports an external cache miss');

$runtimeError = (new PpaLinkedQueryService(
    new FakeExternalQueryModel($query),
    new FakeExternalDataSourceModel($source),
    static fn (): array => ['success' => false, 'message' => 'Falha controlada.']
))->run($link, 500);
$assertSame('Falha controlada.', $runtimeError, 'preserves runtime errors');

$cacheDirectory = sys_get_temp_dir() . '/ppa-linked-query-cache-test-' . bin2hex(random_bytes(8));
$fileCache = new PpaQueryFileCache($cacheDirectory);
$cachedRows = [['total' => 99]];
$fileCache->write($source, $query, 5000, $cachedRows, new DateTimeImmutable('2026-07-24T12:00:00-03:00'));
$externalCalls = 0;
$cacheHit = (new PpaLinkedQueryService(
    new FakeExternalQueryModel($query),
    new FakeExternalDataSourceModel($source),
    static function () use (&$externalCalls): array {
        $externalCalls++;

        return ['success' => true, 'rows' => [['total' => 1]]];
    },
    $fileCache
))->run($link, 5000);

$assertSame($cachedRows, $cacheHit['rows'] ?? null, 'returns rows from a compatible file cache');
$assertSame(true, $cacheHit['cache']['hit'] ?? null, 'reports a file cache hit');
$assertSame(
    '2026-07-24T12:00:00-03:00',
    $cacheHit['cache']['generated_at'] ?? null,
    'reports cache generation time'
);
$assertSame(0, $externalCalls, 'does not execute the external query on cache hit');

$cacheMiss = (new PpaLinkedQueryService(
    new FakeExternalQueryModel($query),
    new FakeExternalDataSourceModel($source),
    static function () use (&$externalCalls): array {
        $externalCalls++;

        return ['success' => true, 'rows' => [['total' => 2]]];
    },
    $fileCache
))->run($link, 500);

$assertSame([['total' => 2]], $cacheMiss['rows'] ?? null, 'falls back when the limit is incompatible');
$assertSame(1, $externalCalls, 'executes the external query on cache miss');

foreach (glob($cacheDirectory . '/*') ?: [] as $file) {
    unlink($file);
}
rmdir($cacheDirectory);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (15 assertions)\n");
