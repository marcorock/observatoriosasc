<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\PpaController;
use App\Models\PpaResultModel;

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
$metricsMethod = new ReflectionMethod(PpaController::class, 'catalogIndicatorMetrics');
$metricsMethod->setAccessible(true);

$indicator = (object) [
    'indice_futuro' => '10.0000',
    'indice_recente' => null,
];

$fallback = $metricsMethod->invoke($controller, $indicator, null);
$assertSame(10.0, $fallback['meta'], 'uses indicator target when no local result exists');
$assertSame(null, $fallback['realizado'], 'keeps no-reading fallback without a local result');
$assertSame(null, $fallback['percentual'], 'does not invent a percentage without a result');

$localResult = (object) [
    'valor_meta_quantitativa' => '250.0000',
    'valor_resultado' => '125.0000',
    'indice_futuro' => null,
    'indice_recente' => null,
];

$snapshot = $metricsMethod->invoke($controller, $indicator, $localResult);
$assertSame(250.0, $snapshot['meta'], 'prefers the local quantitative target');
$assertSame(125.0, $snapshot['realizado'], 'uses the local consolidated result');
$assertSame(50.0, $snapshot['percentual'], 'calculates percentage from local values');

$zeroResult = clone $localResult;
$zeroResult->valor_resultado = '0.0000';
$zero = $metricsMethod->invoke($controller, $indicator, $zeroResult);
$assertSame(0.0, $zero['realizado'], 'preserves a valid zero result');
$assertSame(0.0, $zero['percentual'], 'calculates zero percent for a zero result');

$previewMethod = new ReflectionMethod(PpaController::class, 'catalogMetricsFromDashboardPayload');
$previewMethod->setAccessible(true);
$preview = $previewMethod->invoke($controller, (object) [
    'indice_futuro' => '85.0000',
    'unidade_medida' => 'percentual',
], [
    'type' => 'family_rma_progress',
    'dados' => [
        'meta_familias' => 49185.25,
        'familias_acompanhadas_total' => 11480,
        'referencia' => '2026-05-08',
        'ano_apuracao' => '2026',
    ],
]);
$assertSame(49185.25, $preview['valor_meta_quantitativa'], 'extracts quantitative target from dashboard');
$assertSame(11480.0, $preview['valor_resultado'], 'extracts consolidated dashboard result');
$assertSame(2026, $preview['ano_referencia'], 'extracts the dashboard reference year');
$assertSame(
    23.34033068857025,
    $preview['percentual_atingido'],
    'preserves the dashboard percentage formula'
);

$unsupported = $previewMethod->invoke($controller, $indicator, [
    'type' => 'single_query',
    'dados' => ['total_familias' => 100],
]);
$assertSame(null, $unsupported, 'rejects dashboard types without a safe metric mapping');

$normalized = PpaResultModel::normalizeSyncPreview([
    'success' => true,
    'indicator_id' => 10,
    'indicator_code' => 'PPA-CRAS-ATUALIZACAO-C3',
    'metrics' => $preview,
]);
$assertSame(10, $normalized['indicator_id'], 'normalizes the indicator before persistence');
$assertSame(2026, $normalized['year'], 'normalizes the reference year before persistence');
$assertSame(49185.25, $normalized['target'], 'normalizes the target before persistence');
$assertSame(11480.0, $normalized['result'], 'normalizes the result before persistence');
$assertSame(
    'A previa precisa ser concluida com sucesso antes da gravacao.',
    PpaResultModel::normalizeSyncPreview(['success' => false]),
    'rejects a failed preview'
);
$invalidResult = $preview;
$invalidResult['valor_resultado'] = -1;
$assertSame(
    'O valor realizado da previa e invalido.',
    PpaResultModel::normalizeSyncPreview([
        'success' => true,
        'indicator_id' => 10,
        'metrics' => $invalidResult,
    ]),
    'rejects a negative result'
);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (19 assertions)\n");
