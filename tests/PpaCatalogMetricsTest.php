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

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (8 assertions)\n");
