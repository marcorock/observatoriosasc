<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ExternalDatabaseRuntime;

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

$applyLimit = new ReflectionMethod(ExternalDatabaseRuntime::class, 'applyLimit');
$applyLimit->setAccessible(true);

$limitCases = [
    [
        'SELECT id FROM items',
        100,
        "SELECT id FROM items\nLIMIT 100",
        'adds LIMIT to a SELECT without LIMIT',
    ],
    [
        'SELECT id FROM items LIMIT 250',
        100,
        "SELECT id FROM items\nLIMIT 100",
        'reduces an existing LIMIT to the requested maximum',
    ],
    [
        'SELECT id FROM items ORDER BY id DESC LIMIT 50',
        100,
        "SELECT id FROM items ORDER BY id DESC\nLIMIT 50",
        'preserves an existing lower LIMIT after ORDER BY',
    ],
    [
        'SELECT id FROM items LIMIT 20 OFFSET 10',
        100,
        "SELECT id FROM items\nLIMIT 20 OFFSET 10",
        'preserves LIMIT with OFFSET',
    ],
    [
        'SELECT id FROM items LIMIT 10, 20',
        15,
        "SELECT id FROM items\nLIMIT 15 OFFSET 10",
        'normalizes comma LIMIT and applies the requested maximum',
    ],
];

foreach ($limitCases as [$sql, $limit, $expected, $label]) {
    $actual = $applyLimit->invoke(null, $sql, $limit);
    $assertSame($expected, $actual, $label);
}

$assertSame(true, ExternalDatabaseRuntime::validateSelectQuery('SELECT id FROM items'), 'accepts SELECT');
$assertSame(
    true,
    ExternalDatabaseRuntime::validateSelectQuery('SELECT id FROM items;'),
    'accepts one trailing semicolon after normalization'
);
$assertSame(
    'Somente consultas iniciadas com SELECT sao permitidas neste modulo.',
    ExternalDatabaseRuntime::validateSelectQuery('DELETE FROM items'),
    'rejects non-SELECT statements'
);
$assertSame(
    'Nao use multiplas instrucoes SQL nesta consulta.',
    ExternalDatabaseRuntime::validateSelectQuery('SELECT id FROM items; SELECT id FROM users'),
    'rejects multiple statements'
);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf("OK (%d assertions)\n", count($limitCases) + 4));
