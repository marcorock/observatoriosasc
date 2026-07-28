<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\PpaController;
use App\Models\ExternalDataSourceModel;
use App\Models\ExternalQueryModel;
use App\Models\PpaIndicatorModel;
use App\Models\PpaIndicatorQueryModel;
use App\Services\PpaLinkedQueryService;
use App\Services\PpaQueryFileCache;

final class PpaFilterCacheResponseCaptured extends RuntimeException
{
    public function __construct(public array $payload)
    {
        parent::__construct('JSON response captured.');
    }
}

final class PpaFilterCacheController extends PpaController
{
    protected function json(array $payload): void
    {
        throw new PpaFilterCacheResponseCaptured($payload);
    }
}

final class PpaFilterCacheIndicatorModel extends PpaIndicatorModel
{
    public function __construct(private object $indicator)
    {
    }

    public function readPublicBySlug(string $slug): object|string
    {
        return $this->indicator;
    }
}

final class PpaFilterCacheLinkModel extends PpaIndicatorQueryModel
{
    public function __construct(private object $link)
    {
    }

    public function readActiveLinksByIndicatorId(int $indicatorId): array|string
    {
        return [$this->link];
    }
}

final class PpaFilterCacheQueryModel extends ExternalQueryModel
{
    public function __construct(private object $query)
    {
    }

    public function readById(int $id): object|string
    {
        return $this->query;
    }
}

final class PpaFilterCacheSourceModel extends ExternalDataSourceModel
{
    public function __construct(private object $source)
    {
    }

    public function readById(int $id): object|string
    {
        return $this->source;
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

$cacheDirectory = sys_get_temp_dir() . '/ppa-filter-cache-smoke-' . bin2hex(random_bytes(8));
$cache = new PpaQueryFileCache($cacheDirectory);
$indicator = (object) [
    'id' => 42,
    'slug' => 'indicador-cache',
    'codigo_indicador' => 'PPA-TESTE-CACHE',
];
$link = (object) [
    'external_query_id' => 7,
    'indicador_id' => 42,
    'codigo_indicador' => 'PPA-TESTE-CACHE',
    'campo_resultado' => 'consulta_simples',
];
$query = (object) [
    'id' => 7,
    'source_id' => 3,
    'sql_query' => 'SELECT cras, regiao, bairro, total_familias, total_pessoas, ref_cad FROM resumo',
];
$source = (object) [
    'id' => 3,
    'nome' => 'Fonte teste',
];
$cachedRows = [
    [
        'cras' => 'CRAS CENTRO',
        'regiao' => 'Centro',
        'bairro' => 'Jardim Central',
        'total_familias' => 12,
        'total_pessoas' => 30,
        'ref_cad' => '2026-06-01',
    ],
    [
        'cras' => 'CRAS NORTE',
        'regiao' => 'Norte',
        'bairro' => 'Jardim Norte',
        'total_familias' => 8,
        'total_pessoas' => 20,
        'ref_cad' => '2026-06-01',
    ],
];
$cache->write(
    $source,
    $query,
    5000,
    $cachedRows,
    new DateTimeImmutable('2026-07-28T12:00:00-03:00')
);

$externalCalls = 0;
$linkedQueryService = new PpaLinkedQueryService(
    new PpaFilterCacheQueryModel($query),
    new PpaFilterCacheSourceModel($source),
    static function () use (&$externalCalls, $cachedRows): array {
        $externalCalls++;

        return ['success' => true, 'rows' => $cachedRows];
    },
    $cache
);
$controller = new PpaFilterCacheController(
    new PpaFilterCacheIndicatorModel($indicator),
    null,
    new PpaFilterCacheLinkModel($link),
    $linkedQueryService
);

$_GET = [
    'cras' => 'CRAS CENTRO',
    'regiao' => 'Centro',
];
http_response_code(200);

try {
    $controller->dashboardData($indicator->slug);
    throw new RuntimeException('The controller did not emit a JSON response.');
} catch (PpaFilterCacheResponseCaptured $response) {
    $payload = $response->payload;
}

$assertSame(200, http_response_code(), 'keeps HTTP 200 for a successful cached response');
$assertSame('single_query', $payload['type'] ?? null, 'keeps the single-query response type');
$assertSame(12, $payload['dados']['total_geral'] ?? null, 'keeps only the requested CRAS families');
$assertSame(30, $payload['dados']['total_pessoas'] ?? null, 'keeps only the requested CRAS people');
$assertSame(1, count($payload['dados']['tabela_cras'] ?? []), 'returns one filtered CRAS row');
$assertSame(
    ['cras' => 'CRAS CENTRO', 'regiao' => 'Centro'],
    $payload['dados']['filtros_ativos'] ?? null,
    'reports the active filters'
);
$assertSame(0, $externalCalls, 'does not query the external source on cache hit');

foreach (glob($cacheDirectory . '/*') ?: [] as $file) {
    unlink($file);
}
$_GET = [
    'cras' => 'CRAS NORTE',
    'regiao' => 'Norte',
];
http_response_code(200);

try {
    $controller->dashboardData($indicator->slug);
    throw new RuntimeException('The controller did not emit a JSON response.');
} catch (PpaFilterCacheResponseCaptured $response) {
    $missPayload = $response->payload;
}

$assertSame(200, http_response_code(), 'keeps HTTP 200 after a controlled cache miss');
$assertSame(1, $externalCalls, 'queries the external source once on cache miss');
$assertSame(8, $missPayload['dados']['total_geral'] ?? null, 'filters external fallback rows');
$assertSame(
    ['cras' => 'CRAS NORTE', 'regiao' => 'Norte'],
    $missPayload['dados']['filtros_ativos'] ?? null,
    'reports filters after the external fallback'
);

$_GET = [];
foreach (glob($cacheDirectory . '/*') ?: [] as $file) {
    unlink($file);
}
rmdir($cacheDirectory);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (11 assertions)\n");
