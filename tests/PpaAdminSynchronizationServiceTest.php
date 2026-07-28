<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PpaAdminSynchronizationService;

$assertions = 0;

$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$assertions): void {
    $assertions++;

    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$indicator = (object) [
    'id' => 7,
    'codigo_indicador' => 'PPA-TESTE',
    'ativo' => 1,
];
$links = [(object) ['id' => 11, 'campo_resultado' => 'principal']];
$calls = [];

$service = new PpaAdminSynchronizationService(
    indicatorLoader: static function (int $id) use ($indicator, &$calls): object {
        $calls[] = 'indicator:' . $id;
        return $indicator;
    },
    linksLoader: static function (int $id) use ($links, &$calls): array {
        $calls[] = 'links:' . $id;
        return $links;
    },
    cacheSynchronizer: static function (object $loadedIndicator, array $loadedLinks) use (&$calls): array {
        $calls[] = 'cache:' . $loadedIndicator->id . ':' . count($loadedLinks);
        return ['success' => true, 'entries_written' => 2];
    },
    catalogPreview: static function (string $code) use (&$calls): array {
        $calls[] = 'preview:' . $code;
        return ['success' => true, 'indicator_code' => $code, 'valor_resultado' => 12];
    },
    resultPersister: static function (array $preview, string $executionType) use (&$calls): array {
        $calls[] = 'persist:' . $preview['indicator_code'] . ':' . $executionType;
        return ['inserted' => true, 'message' => 'Resultado gravado.'];
    }
);

$result = $service->synchronizeIndicator(7);

$assertSame(true, $result['success'], 'a sincronização completa deve terminar com sucesso');
$assertSame(2, $result['entries_written'], 'deve informar quantas entradas de cache foram gravadas');
$assertSame(true, $result['result_inserted'], 'deve informar que um resultado novo foi consolidado');
$assertSame(
    ['indicator:7', 'links:7', 'cache:7:1', 'preview:PPA-TESTE', 'persist:PPA-TESTE:manual'],
    $calls,
    'cache, prévia e persistência devem acontecer na ordem segura'
);

$invalid = $service->synchronizeIndicator(0);
$assertSame(false, $invalid['success'], 'um indicador inválido deve ser rejeitado');

$inactiveService = new PpaAdminSynchronizationService(
    indicatorLoader: static fn (): object => (object) [
        'id' => 8,
        'codigo_indicador' => 'PPA-INATIVO',
        'ativo' => 0,
    ]
);
$inactive = $inactiveService->synchronizeIndicator(8);
$assertSame(false, $inactive['success'], 'um indicador inativo deve ser rejeitado');

$noLinksService = new PpaAdminSynchronizationService(
    indicatorLoader: static fn (): object => $indicator,
    linksLoader: static fn (): array => []
);
$noLinks = $noLinksService->synchronizeIndicator(7);
$assertSame(false, $noLinks['success'], 'um indicador sem vínculos deve ser rejeitado');

$persistCalled = false;
$cacheFailureService = new PpaAdminSynchronizationService(
    indicatorLoader: static fn (): object => $indicator,
    linksLoader: static fn (): array => $links,
    cacheSynchronizer: static fn (): array => [
        'success' => false,
        'error' => 'Fonte externa indisponível.',
    ],
    catalogPreview: static fn (): array => ['success' => true],
    resultPersister: static function () use (&$persistCalled): array {
        $persistCalled = true;
        return [];
    }
);
$cacheFailure = $cacheFailureService->synchronizeIndicator(7);
$assertSame(false, $cacheFailure['success'], 'uma falha externa deve interromper a sincronização');
$assertSame(false, $persistCalled, 'uma falha externa não deve gravar resultado consolidado');

$previewFailureService = new PpaAdminSynchronizationService(
    indicatorLoader: static fn (): object => $indicator,
    linksLoader: static fn (): array => $links,
    cacheSynchronizer: static fn (): array => ['success' => true, 'entries_written' => 1],
    catalogPreview: static fn (): array => [
        'success' => false,
        'error' => 'Resumo indisponível.',
    ]
);
$previewFailure = $previewFailureService->synchronizeIndicator(7);
$assertSame(false, $previewFailure['success'], 'uma falha no resumo deve ser informada');
$assertSame(1, $previewFailure['cache']['entries_written'], 'deve informar que o cache já foi atualizado');

$automaticExecutionType = null;
$automaticService = new PpaAdminSynchronizationService(
    indicatorLoader: static fn (): object => $indicator,
    linksLoader: static fn (): array => $links,
    cacheSynchronizer: static fn (): array => ['success' => true, 'entries_written' => 1],
    catalogPreview: static fn (): array => ['success' => true],
    resultPersister: static function (array $preview, string $executionType) use (&$automaticExecutionType): array {
        $automaticExecutionType = $executionType;
        return ['inserted' => false, 'message' => 'Resultado já atualizado.'];
    },
    executionType: 'automatico'
);
$automaticService->synchronizeIndicator(7);
$assertSame('automatico', $automaticExecutionType, 'a execução programada deve ser registrada como automática');

fwrite(STDOUT, "OK ({$assertions} assertions)\n");
