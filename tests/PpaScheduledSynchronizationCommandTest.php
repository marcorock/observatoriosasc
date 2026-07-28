<?php

declare(strict_types=1);

$commandPath = __DIR__ . '/../bin/ppa-scheduled-sync.php';
$schedulePath = __DIR__ . '/../deploy/cron/observatoriosasc-ppa';
$command = file_get_contents($commandPath);
$schedule = file_get_contents($schedulePath);
$assertions = 0;

$assertTrue = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;

    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assertTrue(is_string($command), 'deve existir um comando de sincronização programada');
$assertTrue(
    str_contains((string) $command, 'PpaScheduledSynchronizationBatchService'),
    'o comando deve reutilizar o serviço de lote testado'
);
$assertTrue(
    str_contains((string) $command, 'LOCK_EX | LOCK_NB'),
    'o comando deve impedir execuções simultâneas sem ficar bloqueado'
);
$assertTrue(
    str_contains((string) $command, "'execution_type'] = 'automatico'"),
    'o resumo deve identificar a execução automática'
);
$assertTrue(is_string($schedule), 'deve existir uma receita de agendamento');
$assertTrue(
    str_contains((string) $schedule, 'CRON_TZ=America/Sao_Paulo'),
    'o agendamento deve declarar o horário de São Paulo'
);
$assertTrue(
    str_contains((string) $schedule, '15 4 * * * www-data'),
    'o agendamento deve executar diariamente às 04:15 com o usuário web'
);
$assertTrue(
    str_contains((string) $schedule, 'bin/ppa-scheduled-sync.php --all'),
    'o agendamento deve chamar a sincronização completa'
);

$output = [];
$exitCode = 1;
exec(
    escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($commandPath) . ' --help 2>&1',
    $output,
    $exitCode
);

$assertTrue($exitCode === 0, 'a ajuda do comando deve terminar com sucesso');
$assertTrue(
    str_contains(implode("\n", $output), 'ppa-scheduled-sync.php --all'),
    'a ajuda deve explicar como executar o lote'
);

fwrite(STDOUT, "OK ({$assertions} assertions)\n");
