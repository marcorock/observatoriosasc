<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PpaDashboardResolver;

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
$links = static fn (array $keys): array => array_map(
    static fn (string $key): object => (object) ['campo_resultado' => $key],
    $keys
);

$classificationCases = [
    [
        ['base_familias_pbf', 'familias_atualizadas_c3_mensal', 'serie_mensal_unidade_c3'],
        'family_snapshot_rma_progress',
        'snapshot has precedence when all three link families exist',
    ],
    [
        ['base_familias_pbf', 'familias_acompanhadas_b2_mensal'],
        'family_rma_progress',
        'base and accompanied links resolve family progress',
    ],
    [
        ['base_familias_meio_sm', 'familias_atualizadas_a2_mensal'],
        'family_rma_progress',
        'updated families also resolve family progress',
    ],
    [
        ['serie_mensal_unidade_mse'],
        'monthly_unit_progress',
        'monthly series resolves unit progress',
    ],
    [
        ['total_familias'],
        'single_query',
        'unrecognized link resolves the default dashboard',
    ],
    [
        [],
        'single_query',
        'missing links resolve the default dashboard',
    ],
];

foreach ($classificationCases as [$keys, $expected, $label]) {
    $assertSame($expected, PpaDashboardResolver::dashboardType($links($keys)), $label);
}

$trimmedLink = (object) ['campo_resultado' => '  serie_mensal_unidade_creas  ', 'id' => 9];
$assertSame(
    $trimmedLink,
    PpaDashboardResolver::findLinkByResultKey([$trimmedLink], 'serie_mensal_unidade_creas'),
    'finds a link after trimming its result key'
);
$assertSame(
    null,
    PpaDashboardResolver::findLinkByResultKey([$trimmedLink], 'base_familias_pbf'),
    'returns null when the result key is absent'
);
$assertSame(
    'familias_acompanhadas_b2_mensal',
    PpaDashboardResolver::findLinkKeyByPrefixes(
        $links(['total', 'familias_acompanhadas_b2_mensal']),
        ['familias_acompanhadas_']
    ),
    'finds the first link key matching a prefix'
);
$assertSame(
    null,
    PpaDashboardResolver::firstKeyByPrefixes(['', 'total'], ['base_familias_']),
    'returns null when no prefix matches'
);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf("OK (%d assertions)\n", count($classificationCases) + 4));
