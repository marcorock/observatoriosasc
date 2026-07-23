<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PpaSingleQueryPayloadBuilder;
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

$rows = [
    [
        'cras' => 'CRAS MARIANA 2',
        'bairro' => 'Centro',
        'regiao' => 'Norte',
        'total_familias' => '10',
        'total_pessoas' => '24',
        'ref_cad' => '2026-06',
    ],
    [
        'cras' => 'CRAS MARIANA',
        'bairro' => 'centro',
        'regiao' => 'Norte',
        'total_familias' => 5,
        'total_pessoas' => 11,
        'ref_cad' => '2026-07',
    ],
    [
        'cras' => 'UNIDADE PERNAMBUCANO',
        'bairro' => '"Jardim Sul"',
        'regiao' => 'Sul',
        'total_familias' => 8,
        'total_pessoas' => 16,
        'ref_cad' => '',
    ],
];

$payload = PpaSingleQueryPayloadBuilder::build($rows);
$controller = (new ReflectionClass(PpaController::class))->newInstanceWithoutConstructor();
$legacyBuild = new ReflectionMethod(PpaController::class, 'buildSingleQueryPayload');
$legacyBuild->setAccessible(true);
$legacyEmpty = new ReflectionMethod(PpaController::class, 'emptySingleQueryPayload');
$legacyEmpty->setAccessible(true);

$assertSame($legacyBuild->invoke($controller, $rows), $payload, 'matches the previous controller payload');
$assertSame(23, $payload['total_geral'], 'sums families');
$assertSame(51, $payload['total_pessoas'], 'sums people');
$assertSame(2, $payload['cras_total'], 'merges CRAS aliases');
$assertSame(2, $payload['bairro_total'], 'merges case-insensitive neighborhood keys');
$assertSame('2026-06', $payload['referencia'], 'keeps the first non-empty reference');
$assertSame(
    [
        ['cras' => 'CRAS MARIANA', 'total' => 15],
        ['cras' => 'CRAS PARQUE SANTA RITA', 'total' => 8],
    ],
    $payload['familias_por_cras'],
    'groups and sorts families by normalized CRAS'
);
$assertSame($rows, $payload['registros'], 'preserves original rows');

$filtered = PpaSingleQueryPayloadBuilder::build($rows, [
    'cras' => ' CRAS MARIANA ',
    'regiao' => 'Norte',
]);
$assertSame(15, $filtered['total_geral'], 'applies CRAS and region filters');
$assertSame(
    ['cras' => 'CRAS MARIANA', 'regiao' => 'Norte'],
    $filtered['filtros_ativos'],
    'returns normalized active filters'
);

$empty = PpaSingleQueryPayloadBuilder::empty(['cras' => '  ', 'regiao' => "'Sul'"]);
$assertSame(
    $legacyEmpty->invoke($controller, ['cras' => '  ', 'regiao' => "'Sul'"]),
    $empty,
    'matches the previous empty controller payload'
);
$assertSame(0, $empty['total_geral'], 'empty payload has zero totals');
$assertSame(
    ['cras' => null, 'regiao' => 'Sul'],
    $empty['filtros_ativos'],
    'empty payload normalizes filters'
);
$assertSame([], $empty['registros'], 'empty payload has no records');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (14 assertions)\n");
