<?php

declare(strict_types=1);

$scriptPath = __DIR__ . '/../deploy/cron/install-ppa-sync-cron.sh';
$script = file_get_contents($scriptPath);
$assertions = 0;

$assertTrue = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;

    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assertTrue(is_string($script), 'o instalador do cron deve existir');
$assertTrue(str_contains((string) $script, 'set -euo pipefail'), 'o instalador deve falhar com segurança');
$assertTrue(str_contains((string) $script, '"${EUID}" -ne 0'), 'o instalador deve exigir root');
$assertTrue(str_contains((string) $script, '[[ ! -d "/etc/cron.d" ]]'), 'deve confirmar que cron está instalado');
$assertTrue(
    str_contains((string) $script, '"$PROJECT_DIR" =~ [[:space:]]'),
    'deve rejeitar caminhos que quebrariam a regra'
);
$assertTrue(str_contains((string) $script, 'getent passwd "$CRON_USER"'), 'deve validar o usuário do cron');
$assertTrue(
    str_contains((string) $script, 'runuser -u "$CRON_USER" -- test -w "$CACHE_DIR"'),
    'deve validar a escrita no cache como o usuário real'
);
$assertTrue(
    str_contains((string) $script, 'runuser -u "$CRON_USER" -- "$PHP_BIN" "$SYNC_COMMAND" --help'),
    'deve validar o comando como o usuário real'
);
$assertTrue(str_contains((string) $script, 'temporary_file="$(mktemp)"'), 'deve preparar o arquivo temporariamente');
$assertTrue(
    str_contains((string) $script, 'install -o root -g root -m 0644'),
    'deve instalar a regra com dono e modo seguros'
);
$assertTrue(
    str_contains((string) $script, 'CRON_TZ=America/Sao_Paulo'),
    'deve manter o fuso horário combinado'
);
$assertTrue(
    !str_contains((string) $script, '.env'),
    'o instalador não deve copiar ou exibir credenciais'
);

$output = [];
$exitCode = 1;
exec(
    'bash ' . escapeshellarg($scriptPath) . ' --help 2>&1',
    $output,
    $exitCode
);

$assertTrue($exitCode === 0, 'a ajuda deve funcionar sem privilégios administrativos');
$assertTrue(
    str_contains(implode("\n", $output), 'sudo bash install-ppa-sync-cron.sh'),
    'a ajuda deve mostrar o comando que o sysadmin executará'
);

fwrite(STDOUT, "OK ({$assertions} assertions)\n");
