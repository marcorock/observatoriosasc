<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PpaScheduledSynchronizationBatchService;

$assertions = 0;
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$assertions): void {
    $assertions++;

    if ($expected !== $actual) {
        fwrite(STDERR, $message . sprintf(
            "\nExpected: %s\nActual:   %s\n",
            var_export($expected, true),
            var_export($actual, true)
        ));
        exit(1);
    }
};

$indicators = [
    (object) [
        'id' => 1,
        'codigo_indicador' => 'PPA-SUCESSO',
        'vinculos_ativos' => 2,
    ],
    (object) [
        'id' => 2,
        'codigo_indicador' => 'PPA-FALHA',
        'vinculos_ativos' => 1,
    ],
    (object) [
        'id' => 3,
        'codigo_indicador' => 'PPA-SEM-VINCULO',
        'vinculos_ativos' => 0,
    ],
    (object) [
        'id' => 4,
        'codigo_indicador' => 'PPA-ERRADICAR-POBREZA',
        'vinculos_ativos' => 1,
    ],
];
$attemptedIds = [];
$service = new PpaScheduledSynchronizationBatchService(
    indicatorSynchronizer: static function (int $indicatorId) use (&$attemptedIds): array {
        $attemptedIds[] = $indicatorId;

        if ($indicatorId === 2) {
            return [
                'success' => false,
                'indicator_id' => $indicatorId,
                'error' => 'Fonte temporariamente indisponível.',
            ];
        }

        return [
            'success' => true,
            'indicator_id' => $indicatorId,
            'entries_written' => 3,
        ];
    }
);

$result = $service->synchronize($indicators);

$assertSame(false, $result['success'], 'uma falha real deve marcar o lote como parcial');
$assertSame(4, $result['indicators_total'], 'deve contar todos os indicadores recebidos');
$assertSame(2, $result['indicators_attempted'], 'deve tentar somente indicadores elegíveis');
$assertSame(1, $result['indicators_succeeded'], 'deve contar sincronizações concluídas');
$assertSame(1, $result['indicators_failed'], 'deve contar falhas reais');
$assertSame(2, $result['indicators_skipped'], 'deve contar indicadores ignorados');
$assertSame(3, $result['entries_written'], 'deve somar entradas gravadas com sucesso');
$assertSame([1, 2], $attemptedIds, 'deve continuar a fila depois de processar cada elegível');
$assertSame(true, $result['results'][2]['skipped'], 'indicador sem vínculo deve ser ignorado');
$assertSame(true, $result['results'][3]['skipped'], 'visão geral deve ser ignorada');

$successful = (new PpaScheduledSynchronizationBatchService(
    indicatorSynchronizer: static fn (int $indicatorId): array => [
        'success' => true,
        'indicator_id' => $indicatorId,
        'entries_written' => 1,
    ]
))->synchronize([$indicators[0]]);

$assertSame(true, $successful['success'], 'um lote sem falhas deve terminar com sucesso');

fwrite(STDOUT, "OK ({$assertions} assertions)\n");
