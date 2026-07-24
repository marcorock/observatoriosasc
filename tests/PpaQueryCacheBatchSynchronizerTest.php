<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PpaQueryCacheBatchSynchronizer;

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

$indicators = [
    (object) ['id' => 1, 'codigo_indicador' => 'PPA-UM'],
    (object) ['id' => 2, 'codigo_indicador' => 'PPA-DOIS'],
    (object) ['id' => 3, 'codigo_indicador' => 'PPA-TRES'],
];
$loadedIds = [];
$synchronizedIds = [];
$linksLoader = static function (int $indicatorId) use (&$loadedIds): array|string {
    $loadedIds[] = $indicatorId;

    return $indicatorId === 2
        ? 'Falha controlada ao carregar vinculos.'
        : [(object) ['campo_resultado' => 'serie_mensal_unidade_teste']];
};
$indicatorSynchronizer = static function (object $indicator, array $links) use (&$synchronizedIds): array {
    $synchronizedIds[] = (int) $indicator->id;

    if ((int) $indicator->id === 3) {
        return [
            'success' => false,
            'indicator_id' => 3,
            'indicator_code' => 'PPA-TRES',
            'error' => 'Falha controlada na consulta.',
        ];
    }

    return [
        'success' => true,
        'indicator_id' => 1,
        'indicator_code' => 'PPA-UM',
        'entries_written' => 2,
    ];
};

$result = (new PpaQueryCacheBatchSynchronizer(
    $linksLoader(...),
    $indicatorSynchronizer(...)
))->synchronize($indicators);

$assertSame(false, $result['success'] ?? null, 'reports a partial batch failure');
$assertSame(3, $result['indicators_total'] ?? null, 'reports total indicators');
$assertSame(1, $result['indicators_succeeded'] ?? null, 'reports successful indicators');
$assertSame(2, $result['indicators_failed'] ?? null, 'reports failed indicators');
$assertSame(2, $result['entries_written'] ?? null, 'sums entries from successful indicators');
$assertSame([1, 2, 3], $loadedIds, 'continues loading after individual failures');
$assertSame([1, 3], $synchronizedIds, 'skips only the indicator with link lookup failure');
$assertSame(
    ['PPA-UM', 'PPA-DOIS', 'PPA-TRES'],
    array_column($result['results'] ?? [], 'indicator_code'),
    'keeps one ordered result per indicator'
);

$empty = (new PpaQueryCacheBatchSynchronizer())->synchronize([]);
$assertSame(true, $empty['success'] ?? null, 'accepts an empty batch');
$assertSame(0, $empty['indicators_total'] ?? null, 'reports an empty batch');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (10 assertions)\n");
