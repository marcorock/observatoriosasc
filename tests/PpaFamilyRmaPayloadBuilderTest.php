<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PpaFamilyRmaPayloadBuilder;

$failures = [];
$assertSame = static function ($expected, $actual, string $label) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = sprintf("%s\nExpected: %s\nActual:   %s", $label, var_export($expected, true), var_export($actual, true));
    }
};

$indicator = (object) ['codigo_indicador' => 'PPA-ACOMPANHAR-FAMILIAS-PBF', 'indice_futuro' => 10];
$baseRows = [
    ['cras' => 'CRAS MARIANA 2', 'regiao' => 'Norte', 'total_familias_pbf' => 100, 'ref_cad' => '2026-06'],
    ['cras' => 'CRAS MARIANA', 'regiao' => 'Norte', 'total_familias_pbf' => 50],
    ['cras' => 'UNIDADE PERNAMBUCANO', 'regiao' => 'Sul', 'total_familias_pbf' => 50],
];
$rmaRows = [
    ['mes_referencia' => '2026-01-01', 'cras' => 'CRAS MARIANA', 'total_familias_acompanhadas' => 5],
    ['mes_referencia' => '2026-02-01', 'nome_unidade' => 'CRAS MARIANA 2', 'total_familias_acompanhadas' => 10],
    ['mes_referencia' => '2026-02-01', 'cras' => 'UNIDADE PERNAMBUCANO', 'total_familias_acompanhadas' => 5],
];
$payload = PpaFamilyRmaPayloadBuilder::build($baseRows, $rmaRows, $indicator, []);
$assertSame(200, $payload['total_geral'], 'sums base families');
$assertSame(20.0, $payload['meta_familias'], 'calculates target families');
$assertSame(20, $payload['familias_acompanhadas_total'], 'sums accompanied families');
$assertSame(100.0, $payload['percentual_alcancado_total'], 'calculates total progress');
$assertSame(2, $payload['meses_periodo'], 'counts monthly periods');
$assertSame('2026-06', $payload['referencia'], 'keeps first base reference');
$assertSame(2, count($payload['tabela_cras']), 'merges CRAS aliases');

$filtered = PpaFamilyRmaPayloadBuilder::build($baseRows, $rmaRows, $indicator, [
    'cras' => 'CRAS MARIANA',
    'mes_referencia' => '2026-02-01',
]);
$assertSame(150, $filtered['total_geral'], 'filters base by CRAS');
$assertSame(10, $filtered['familias_acompanhadas_total'], 'filters RMA by CRAS and month');
$assertSame(
    ['cras' => 'CRAS MARIANA', 'mes_referencia' => '2026-02-01'],
    $filtered['filtros_ativos'],
    'returns active filters'
);

$empty = PpaFamilyRmaPayloadBuilder::empty();
$assertSame(0, $empty['total_geral'], 'empty payload has zero total');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (11 assertions)\n");
