<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\PpaController;

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

$controller = (new ReflectionClass(PpaController::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod(PpaController::class, 'buildFamilySnapshotRmaPayload');
$method->setAccessible(true);

$indicator = (object) [
    'codigo_indicador' => 'PPA-CRAS-ATUALIZACAO-C3',
    'indice_futuro' => 85,
];
$baseRows = [
    [
        'cras' => 'CRAS MARIANA 2',
        'regiao' => 'Norte',
        'total_familias_pbf' => 100,
        'ref_cad_referencia' => '2026-06',
    ],
    [
        'cras' => 'UNIDADE PERNAMBUCANO',
        'regiao' => 'Sul',
        'total_familias' => 50,
    ],
    [
        'cras' => 'CRAS ZERO',
        'regiao' => 'Leste',
        'total_familias_pbf' => 0,
    ],
];
$updatedRows = [
    [
        'cras' => 'CRAS MARIANA',
        'total_familias_atualizadas' => 60,
    ],
    [
        'cras' => 'UNIDADE PERNAMBUCANO',
        'total_familias_acompanhadas' => 15,
    ],
    [
        'cras' => 'CRAS SOMENTE ATUALIZADAS',
        'total_familias_atualizadas' => 10,
        'mes_referencia' => '2026-05',
    ],
    [
        'cras' => 'CRAS ZERO',
        'total_familias_atualizadas' => 0,
    ],
];
$rmaRows = [
    [
        'mes_referencia' => '2026-01-01',
        'cras' => 'CRAS MARIANA',
        'total_inseridos' => 5,
    ],
    [
        'mes_referencia' => '2026-02-01',
        'nome_unidade' => 'CRAS MARIANA 2',
        'total_familias_acompanhadas' => 7,
    ],
    [
        'mes_referencia' => '2026-02-01',
        'unidade' => 'UNIDADE PERNAMBUCANO',
        'total_inseridos' => 3,
    ],
    [
        'mes_referencia' => '2026-03-01',
        'cras' => 'CRAS SOMENTE ATUALIZADAS',
        'total_inseridos' => 2,
    ],
    [
        'mes_referencia' => '',
        'cras' => 'CRAS IGNORADO',
        'total_inseridos' => 99,
    ],
];

$payload = $method->invoke($controller, $baseRows, $updatedRows, $rmaRows, $indicator, []);

$assertSame(
    [
        'total_geral',
        'meta_familias',
        'familias_acompanhadas_total',
        'percentual_alcancado_total',
        'percentual_periodo',
        'meses_periodo',
        'referencia',
        'ano_apuracao',
        'grafico_mensal',
        'grafico_cras',
        'grafico_meta',
        'tabela_mensal',
        'tabela_cras',
        'filtros_ativos',
    ],
    array_keys($payload),
    'preserves the complete legacy payload contract'
);
$assertSame(150, $payload['total_geral'], 'sums base rows using supported aliases');
$assertSame(127.5, $payload['meta_familias'], 'calculates the 85 percent target');
$assertSame(85, $payload['familias_acompanhadas_total'], 'uses the updated snapshot as realized total');
$assertSame((85 / 127.5) * 100, $payload['percentual_alcancado_total'], 'calculates total snapshot progress');
$assertSame(3, $payload['meses_periodo'], 'counts valid RMA months');
$assertSame(25.0, $payload['percentual_periodo'], 'calculates period progress over twelve months');
$assertSame('2026-06', $payload['referencia'], 'keeps the first base reference');
$assertSame('2026', $payload['ano_apuracao'], 'extracts the RMA assessment year');
$assertSame([], $payload['grafico_meta'], 'keeps the legacy empty target chart');
$assertSame(5, $payload['tabela_mensal'][0]['familias_acompanhadas'], 'uses total_inseridos in the monthly series');
$assertSame(10, $payload['tabela_mensal'][1]['familias_acompanhadas'], 'uses the monthly fallback and aggregates rows');
$assertSame(17, $payload['tabela_mensal'][2]['acumulado'], 'builds the monthly accumulated value');
$assertSame('CRAS MARIANA', $payload['tabela_cras'][0]['cras'], 'merges the Mariana alias');
$assertSame(60, $payload['tabela_cras'][0]['familias_acompanhadas'], 'sorts CRAS rows by updated total');
$assertSame('CRAS PARQUE SANTA RITA', $payload['tabela_cras'][1]['cras'], 'normalizes the Pernambucano alias');
$assertSame(0, $payload['tabela_cras'][2]['base_familias_pbf'], 'keeps CRAS present only in updated rows');
$assertSame(0, $payload['tabela_cras'][3]['familias_acompanhadas'], 'keeps valid zero values');

$filtered = $method->invoke($controller, $baseRows, $updatedRows, $rmaRows, $indicator, [
    'cras' => 'CRAS MARIANA',
    'mes_referencia' => '2026-02-01',
]);
$assertSame(100, $filtered['total_geral'], 'filters the base snapshot by CRAS');
$assertSame(60, $filtered['familias_acompanhadas_total'], 'filters updated rows by CRAS');
$assertSame(7, $filtered['tabela_mensal'][0]['familias_acompanhadas'], 'filters RMA rows by CRAS and month');
$assertSame(
    ['cras' => 'CRAS MARIANA', 'mes_referencia' => '2026-02-01'],
    $filtered['filtros_ativos'],
    'returns normalized active filters'
);

$defaultTarget = $method->invoke(
    $controller,
    [['cras' => 'CRAS TESTE', 'total_familias_pbf' => 100]],
    [],
    [],
    (object) [],
    []
);
$assertSame(85.0, $defaultTarget['meta_familias'], 'uses 85 percent as the legacy default target');

$empty = $method->invoke($controller, [], [], [], $indicator, []);
$assertSame(0, $empty['total_geral'], 'supports an empty base');
$assertSame(0, $empty['percentual_alcancado_total'], 'avoids division by zero');
$assertSame(null, $empty['ano_apuracao'], 'keeps an empty assessment year');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (26 assertions)\n");
