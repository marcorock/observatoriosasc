<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ExternalDataSourceModel;
use App\Models\ExternalQueryModel;
use App\Services\PpaLinkedQueryService;

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

$runtimeError = (new PpaLinkedQueryService(
    new FakeExternalQueryModel($query),
    new FakeExternalDataSourceModel($source),
    static fn (): array => ['success' => false, 'message' => 'Falha controlada.']
))->run($link, 500);
$assertSame('Falha controlada.', $runtimeError, 'preserves runtime errors');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (8 assertions)\n");
