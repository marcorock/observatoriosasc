<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\PpaController;
use App\Services\PpaMonthlyUnitPayloadBuilder;

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

$indicator = (object) [
    'codigo_indicador' => 'PPA-CRAS-ATENDIMENTOS-C1',
    'indice_futuro' => 100,
];
$rows = [
    ['mes_referencia' => '2026-01-01', 'unidade' => 'CRAS MARIANA 2', 'total_inseridos' => 10],
    ['mes_referencia' => '2026-01-01', 'nome_unidade' => 'CRAS MARIANA', 'total_casos' => 5],
    ['mes_referencia' => '2026-02-01', 'id_cras' => 'UNIDADE PERNAMBUCANO', 'total_inseridos' => 20],
    ['mes_referencia' => '', 'unidade' => 'CRAS IGNORADO', 'total_inseridos' => 99],
];
$controller = (new ReflectionClass(PpaController::class))->newInstanceWithoutConstructor();
$legacyBuild = new ReflectionMethod(PpaController::class, 'buildMonthlyUnitPayload');
$legacyBuild->setAccessible(true);
$legacyEmpty = new ReflectionMethod(PpaController::class, 'emptyMonthlyUnitPayload');
$legacyEmpty->setAccessible(true);

$payload = PpaMonthlyUnitPayloadBuilder::build($rows, $indicator, []);
$assertSame(
    $legacyBuild->invoke($controller, $rows, $indicator, []),
    $payload,
    'matches the previous controller payload'
);
$assertSame(2, $payload['total_unidades'], 'merges CRAS aliases');
$assertSame(35, $payload['total_inseridos'], 'sums all valid monthly values');
$assertSame(35.0, $payload['percentual_alcancado_total'], 'calculates annual target progress');
$assertSame(2, $payload['meses_periodo'], 'counts distinct months');
$assertSame((2 / 12) * 100, $payload['percentual_periodo'], 'calculates elapsed period percentage');
$assertSame('2026', $payload['ano_apuracao'], 'extracts assessment year');
$assertSame(
    ['unidade' => 'CRAS PARQUE SANTA RITA', 'total' => 20],
    $payload['grafico_unidades'][0],
    'sorts unit chart by total'
);

$filtered = PpaMonthlyUnitPayloadBuilder::build($rows, $indicator, [
    'unidade' => 'CRAS MARIANA',
    'mes_referencia' => '2026-01-01',
]);
$assertSame(15, $filtered['total_inseridos'], 'applies unit and month filters');
$assertSame(
    ['unidade' => 'CRAS MARIANA', 'mes_referencia' => '2026-01-01'],
    $filtered['filtros_ativos'],
    'returns normalized filters'
);

$invalidFilter = PpaMonthlyUnitPayloadBuilder::build($rows, $indicator, [
    'mes_referencia' => '2026-01',
]);
$assertSame(null, $invalidFilter['filtros_ativos']['mes_referencia'], 'rejects an invalid date filter');

$empty = PpaMonthlyUnitPayloadBuilder::empty();
$assertSame($legacyEmpty->invoke($controller), $empty, 'matches the previous empty payload');
$assertSame(0, $empty['total_unidades'], 'empty payload has zero units');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (13 assertions)\n");
